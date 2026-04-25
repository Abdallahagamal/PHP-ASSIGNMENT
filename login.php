<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {
        $conn = new mysqli("sql113.infinityfree.com","if0_41747456","ryH6NrJNuBGHCR","if0_41747456_email_db");
        
        if ($conn->connect_error) {
            $error = "Connection failed: " . $conn->connect_error;
        } else {
            $stmt = $conn->prepare("SELECT id, name, password FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                // Check if the password matches.
                // NOTE: If you are using plain text passwords, use $password === $user['password'].
                // If using hashed passwords, use password_verify($password, $user['password']).
                // We'll support both for safety, defaulting to password_verify then fallback to plain text.
                if (password_verify($password, $user['password']) || $password === $user['password']) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $email;
                    header("Location: index.php");
                    exit();
                } else {
                    $error = "Invalid password.";
                }
            } else {
                $error = "No user found with that email address.";
            }
            $stmt->close();
            $conn->close();
        }
    } else {
        $error = "Please enter both email and password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - EmailManager</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'DM Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-md bg-white rounded-3xl shadow-xl overflow-hidden border border-slate-100">
        <div class="px-8 pt-10 pb-8 h-full flex flex-col">
            <div class="mb-8 text-center flex-1">
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-slate-900 text-white mb-4 shadow-lg">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h1 class="text-2xl font-semibold text-slate-900 mb-2">Welcome Back</h1>
                <p class="text-sm text-slate-500">Please enter your details to sign in.</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="mb-4 bg-red-50 text-red-600 border border-red-200 text-sm rounded-xl px-4 py-3">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php" class="space-y-5">
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">Email address</label>
                    <input type="email" name="email" id="email" required autocomplete="email"
                        class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-100 transition-all placeholder-slate-400"
                        placeholder="hello@example.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700 mb-1.5">Password</label>
                    <input type="password" name="password" id="password" required autocomplete="current-password"
                        class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-100 transition-all placeholder-slate-400"
                        placeholder="••••••••">
                </div>

                <div class="flex items-center justify-between pt-2">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" class="rounded border-slate-300 text-slate-900 focus:ring-slate-500 rounded-sm">
                        <span class="text-sm text-slate-600">Remember me</span>
                    </label>
                </div>

                <button type="submit" class="w-full rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white shadow-md hover:bg-slate-800 transition-colors mt-2">
                    Sign in
                </button>
            </form>

            <p class="mt-8 text-center text-sm text-slate-500">
                Don't have an account? 
                <a href="signup.php" class="font-medium text-slate-900 hover:underline focus:outline-none">Sign up</a>
            </p>
        </div>
    </div>

</body>
</html>
