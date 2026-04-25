<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
@include 'Upload.php';
$conn = new mysqli("sql113.infinityfree.com","if0_41747456","ryH6NrJNuBGHCR","if0_41747456_email_db");
if ($conn->connect_error) {
    echo json_encode(["message" => "Database connection failed: " . $conn->connect_error]);
    exit;
}
$sender_id = $_SESSION['user_id'] ?? null;



// ADD
if(isset($_GET['action']) && $_GET['action'] === "add"){
    $input = json_decode(file_get_contents('php://input'), true);
    $receiver_email = trim($input['composeEmail'] ?? '');
    $subject = trim($input['composeSubject'] ?? '');
    $message = trim($input['composeBody'] ?? '');

    $missing = [];
    if(empty($sender_id)) $missing[] = 'sender_id';
    if(empty($receiver_email)) $missing[] = 'receiver_email';
    if(empty($subject)) $missing[] = 'subject';
    if(empty($message)) $missing[] = 'message';
    if(!empty($missing)){
        echo json_encode(["message" => "Missing required fields: " . implode(', ', $missing)]);
        exit;
    }

   //make sure receiver email is valid and exist in users table
    $stmt = $conn->prepare("SELECT id FROM users WHERE LOWER(email) = LOWER(?)");
    $stmt->bind_param("s", $receiver_email);
    $stmt->execute();
    $result = $stmt->get_result();
 
    if($result->num_rows === 0){
        echo json_encode(["message" => "Receiver email not found: " . $receiver_email]);
        exit;
    }

    $receiver = $result->fetch_assoc();
    $receiver_id = (int) $receiver['id'];
    $receiver_name = $receiver['name'] ?? $receiver_email; // Fallback to email if no name


    if (!$conn->begin_transaction()) {
        echo json_encode(["message" => "Failed to start transaction"]);
        exit;
    }

    // Always create a new chat for each email
    $stmt = $conn->prepare("INSERT INTO chats (user1_id, user2_id, created_at) VALUES (?, ?, NOW())");
    if (!$stmt) {
        $conn->rollback();
        echo json_encode(["message" => "Prepare failed: " . $conn->error]);
        exit;
    }
    $stmt->bind_param("ii", $sender_id, $receiver_id);
    if (!$stmt->execute()) {
        $conn->rollback();
        echo json_encode(["message" => "Execute failed: " . $stmt->error]);
        exit;
    }
    $chat_id = $conn->insert_id;

    $stmt = $conn->prepare("INSERT INTO emails (chat_id, sender_id, subject, message, sent_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("iiss", $chat_id, $sender_id, $subject, $message);
    if (!$stmt) {
        $conn->rollback();
        echo json_encode(["message" => "Prepare failed: " . $conn->error]);
        exit;
    }
    if (!$stmt->execute()) {
        $conn->rollback();
        echo json_encode(["message" => "Execute failed: " . $stmt->error]);
        exit;
    }
    $email_id = $conn->insert_id;

    if (!$conn->commit()) {
        echo json_encode(["message" => "Commit failed: " . $conn->error]);
        exit;
    }
    echo json_encode(["message" => "Email added", "chat_id" => $chat_id, "email_id" => $email_id, "receiver_name" => $receiver_name]);
    
}

// UPDATE (for replies)
if(isset($_GET['action']) && $_GET['action'] === "update"){
    $input = json_decode(file_get_contents('php://input'), true);
    $chat_id = (int) ($input['chat_id'] ?? 0);
    $message = trim($input['message'] ?? '');

    if(empty($sender_id) || empty($chat_id) || empty($message)){
        echo json_encode(["message" => "Missing required fields"]);
        exit;
    }

    // Check if chat exists and user is part of it
    $stmt = $conn->prepare("SELECT id FROM chats WHERE id = ? AND (user1_id = ? OR user2_id = ?)");
    $stmt->bind_param("iii", $chat_id, $sender_id, $sender_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if($result->num_rows === 0){
        echo json_encode(["message" => "Chat not found or access denied"]);
        exit;
    }

    // Get subject from the first email in the chat
    $stmt = $conn->prepare("SELECT subject FROM emails WHERE chat_id = ? ORDER BY sent_at ASC LIMIT 1");
    $stmt->bind_param("i", $chat_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $subject = '';
    if($result->num_rows > 0){
        $row = $result->fetch_assoc();
        $subject = $row['subject'];
    }

    // Insert the reply
    $stmt = $conn->prepare("INSERT INTO emails (chat_id, sender_id, subject, message, sent_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("iiss", $chat_id, $sender_id, $subject, $message);
    if (!$stmt->execute()) {
        echo json_encode(["message" => "Failed to add reply: " . $stmt->error]);
        exit;
    }

    echo json_encode(["message" => "Reply added"]);
}

// READ
if(isset($_GET['action']) && $_GET['action'] == "read"){

    // Get the logged in user ID from the session securely
    $user_id = $_SESSION['user_id'] ?? 1; // Fallback to 1 if testing without logging in

    $stmt = $conn->prepare("
        SELECT 
            e.id,
            e.chat_id,
            e.subject,
            e.message,
            e.sent_at,
            u.name AS sender_name,
            u.email AS sender_email,
            u2.name AS recipient_name,
            u2.email AS recipient_email
        FROM emails e
        JOIN users u ON u.id = e.sender_id
        JOIN chats c ON c.id = e.chat_id
        LEFT JOIN users u2 ON u2.id = IF(e.sender_id = c.user1_id, c.user2_id, c.user1_id)
        WHERE c.user1_id = ? OR c.user2_id = ?
        ORDER BY e.sent_at DESC
    ");

    $stmt->bind_param("ii", $user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while($row = $result->fetch_assoc()){
        $data[] = $row;
    }

    echo json_encode($data);
    return;
}

// DELETE
if(isset($_GET['action']) && $_GET['action'] === "delete"){
    $ids = explode(',', $_GET['id']);
    $deleted_count = 0;
    $chats_to_delete = [];

    foreach($ids as $id){
        $id = (int) trim($id);
        if($id <= 0) continue;

        // Get chat_id for this email
        $stmt = $conn->prepare("SELECT chat_id FROM emails WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if($result->num_rows > 0){
            $row = $result->fetch_assoc();
            $chat_id = (int) $row['chat_id'];
            $chats_to_delete[] = $chat_id;
        }
    }

    // Remove duplicates
    $chats_to_delete = array_unique($chats_to_delete);

    foreach($chats_to_delete as $chat_id){
        // Delete all emails in this chat
        $stmt = $conn->prepare("DELETE FROM emails WHERE chat_id = ?");
        $stmt->bind_param("i", $chat_id);
        $stmt->execute();
        $deleted_count += $stmt->affected_rows;

        // Delete the chat
        $stmt = $conn->prepare("DELETE FROM chats WHERE id = ?");
        $stmt->bind_param("i", $chat_id);
        $stmt->execute();
    }

    echo json_encode(["message" => "Deleted $deleted_count messages and associated chats"]);
}


?>