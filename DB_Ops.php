<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
@include 'Upload.php';
$conn = new mysqli("localhost","root","","email_db");
$sender_id = $_SESSION['user_id'];



// ADD
if($_GET['action'] == "add"){
     
    $receiver_email = $_POST['composeEmail'];
    $subject = $_POST['composeSubject'];
    $message = $_POST['composeBody'];
    // $attachment = $_POST['attachment'];
// validation
    // if(empty($sender_id) || empty($receiver_email)  || empty($message)){
        
    //     exit;
    // }

   //make sure receiver email is valid and exist in users table
        $stmt = $conn->prepare("SELECT id FROM users WHERE email=?");
        $stmt->bind_param("s",$receiver_email);
        $stmt->execute();
        $result = $stmt->get_result();
        if($result->num_rows == 0){
            echo json_encode(["message"=>"Receiver email not found"]);
            exit;
        }

      
    $stmt = $conn->prepare("
    $user2_id = SELECT userID FROM users WHERE email='$receiver_email'
    INSERT INTO CHATS(user1_id,user2_id,created_at)
    VALUES ($sender_id,$user2_id,NOW())
    $chat_id = SELECT id FROM CHATS WHERE user1_id=$sender_id AND user2_id=$user2_id AND created_at=NOW()
    INSERT INTO emails(chat_id,sender_id,user2_id,subject,message,created_at)
    VALUES ($chat_id,$sender_id,$user2_id,$subject,$message,NOW())
    ");

    $stmt->bind_param("isss",$sender_id,$receiver_email,$subject,$message);
    $stmt->execute();

    echo json_encode(["message"=>"Email added"]);
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
if($_GET['action'] == "delete"){
    $id = $_GET['id'];

    $stmt = $conn->prepare("DELETE FROM emails WHERE id=?");
    $stmt->bind_param("i",$id);
    $stmt->execute();

    echo json_encode(["message"=>"Deleted"]);
}

// UPDATE
if($_GET['action'] == "update"){
    $id = $_POST['id'];
    $sender = 'mohamedahmedhamed500@gmail.com';
    $receiver = $_POST['composeEmail'];
    $subject = $_POST['composeSubject'];
    $message = $_POST['composeBody'];
    $attachment = $_POST['attachment'];
    $stmt = $conn->prepare("UPDATE emails SET sender=?, receiver=?, subject=?, message=? WHERE id=?");
    $stmt->bind_param("ssssi",$sender,$receiver,$subject,$message,$id);
    $stmt->execute();

    echo json_encode(["message"=>"Updated"]);
}
?>