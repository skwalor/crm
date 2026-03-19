<?php
require_once __DIR__ . '/includes/session_config.php';

// If user is already logged in, redirect to index page
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: index.php");
    exit;
}

require_once 'db_connect.php';
require_once 'includes/functions.php';

$message = '';

// Check for registration success message
if (isset($_GET['status']) && $_GET['status'] === 'reg_success') {
    $message = 'Registration successful! Please wait for an administrator to approve your account.';
}

$csrf_token = generate_csrf_token();

// Handle POST login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid request. Please try again.';
    } else {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $message = 'Please enter both username and password.';
    } else {
        // Include profile fields in query
        $stmt = $conn->prepare("SELECT id, username, password_hash, approved, role, COALESCE(active, 1) as active, first_name, last_name, display_name, profile_photo FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (!$user['approved']) {
                $message = 'Account pending approval.';
            } elseif (!$user['active']) {
                $message = 'Your account has been deactivated. Please contact an administrator.';
            } elseif (password_verify($password, $user['password_hash'])) {
                // Password is correct, so start a new session
                session_regenerate_id(true); // Prevent session fixation

                // Store data in session variables
                $_SESSION['loggedin'] = true;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['permissions'] = get_permissions_for_role($conn, $user['role']);
                
                // Store profile info in session
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                $_SESSION['display_name'] = $user['display_name'] ?: $user['username']; // Fallback to username
                $_SESSION['profile_photo'] = $user['profile_photo'];

                // Redirect user to index page
                header("location: index.php");
                exit;
            } else {
                $message = 'Invalid username or password.';
            }
        } else {
            $message = 'Invalid username or password.';
        }
        $stmt->close();
    }
    $conn->close();
    } // end CSRF validation else
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Login - CRM</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="icon" type="image/x-icon" href="favicon.ico">
<link rel="icon" type="image/png" sizes="192x192" href="favicon-192.png">
<link rel="apple-touch-icon" href="apple-touch-icon.png">
<style>
    body {font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);display:flex;align-items:center;justify-content:center;height:100vh;margin:0;}
    .wrapper{width:90%;max-width:400px;padding:20px 30px;background:rgba(255,255,255,0.95);border-radius:15px;box-shadow:0 8px 32px rgba(0,0,0,0.1);}
    h2{text-align:center;margin-bottom:20px;color:#333;}
    .form-group{margin-bottom:15px;}
    label{display:block;font-weight:bold;margin-bottom:5px;color:#444;}
    input[type="text"],input[type="password"],input[type="text"].pw-visible{width:100%;box-sizing:border-box;padding:10px;border:1px solid #ccc;border-radius:6px;}
    .toggle-password{background:none!important;border:none;cursor:pointer;font-size:0.8rem;color:#764ba2;padding:0;margin-top:5px;display:block;text-align:right;width:auto;-webkit-appearance:none;}
    .toggle-password:hover{text-decoration:underline;background:none!important;}
    .remember{display:flex;align-items:center;margin-bottom:15px;}
    .remember input{margin-right:10px;}
    button[type="submit"]{width:100%;background:#667eea;color:white;padding:10px;border:none;border-radius:6px;cursor:pointer;font-weight:bold;}
    button[type="submit"]:hover{background:#5a67d8;}
    .message{margin-top:15px;text-align:center;padding:10px;border-radius:6px;}
    .message.error { color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; }
    .message.success { color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb; }
    .register-link{margin-top:10px;text-align:center;font-size:0.9em;}
    .register-link a{color:#764ba2;text-decoration:none;}
    .register-link a:hover{text-decoration:underline;}
    .login-logo{text-align:center;margin-bottom:15px;}
    .login-logo img{max-width:120px;height:auto;}
</style>
</head>
<body>
<div class="wrapper">
    <div class="login-logo">
        <img src="login-logo.png" alt="Logo">
    </div>
    <p style="text-align:center;color:#667eea;font-weight:600;margin-bottom:20px;font-size:1.1em;">Welcome to the Savagely Spicy Carrot CRM</p>
    <h2>Login</h2>
    <form method="POST" action="login.php">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" autocomplete="username" required>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" autocomplete="current-password" required>
            <button type="button" class="toggle-password" onclick="togglePasswordVisibility()" aria-label="Show password">Show password</button>
        </div>
        <button type="submit" id="loginBtn">Login</button>
        <div class="register-link">
            Don't have an account? <a href="register.php">Register here</a>
        </div>
        <div style="text-align:center;font-size:0.75em;color:#999;margin-top:15px;">© 2025 by IntePros Federal</div>
        <?php if ($message): ?>
             <div class="message <?= (isset($_GET['status']) && $_GET['status'] === 'reg_success') ? 'success' : 'error' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
    </form>
</div>
<script>
function togglePasswordVisibility() {
    const input = document.getElementById('password');
    const btn = document.querySelector('.toggle-password');
    if (input.type === 'password') {
        input.type = 'text';
        btn.textContent = 'Hide password';
    } else {
        input.type = 'password';
        btn.textContent = 'Show password';
    }
}
document.querySelector('form').addEventListener('submit', function() {
    const btn = document.getElementById('loginBtn');
    btn.disabled = true;
    btn.textContent = 'Logging in...';
    btn.style.opacity = '0.7';
});
</script>
</body>
</html>
