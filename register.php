<?php
session_start();
require 'db_connect.php';

$username = $password = $confirm_password = "";
$username_err = $password_err = $confirm_password_err = $register_err = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"] ?? '');
    $password = trim($_POST["password"] ?? '');
    $confirm_password = trim($_POST["confirm_password"] ?? '');

    if (empty($username)) $username_err = "Please enter a username.";
    if (empty($password)) $password_err = "Please enter a password.";
    if (strlen($password) < 6) $password_err = "Password must have at least 6 characters.";
    if ($password !== $confirm_password) $confirm_password_err = "Passwords do not match.";

    if (empty($username_err) && empty($password_err) && empty($confirm_password_err)) {
        $sql = "SELECT id FROM users WHERE username = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $username_err = "This username is already taken.";
            } else {
                $stmt->close();
                $sql = "INSERT INTO users (username, password_hash, approved) VALUES (?, ?, 0)";
                if ($stmt = $conn->prepare($sql)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt->bind_param("ss", $username, $hashed_password);
                    if ($stmt->execute()) {
                        header("location: login.php?status=reg_success");
                        exit;
                    } else {
                        $register_err = "Something went wrong. Please try again later.";
                    }
                }
            }
             $stmt->close();
        }
    }
     $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Create Account - CRM</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
    body {font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);display:flex;align-items:center;justify-content:center;height:100vh;margin:0;}
    .wrapper{width:90%;max-width:400px;padding:20px 30px;background:rgba(255,255,255,0.95);border-radius:15px;box-shadow:0 8px 32px rgba(0,0,0,0.1);}
    h2{text-align:center;margin-bottom:20px;color:#333;}
    .form-group{margin-bottom:15px;}
    label{display:block;font-weight:bold;margin-bottom:5px;color:#444;}
    input[type="text"],input[type="password"]{width:100%;padding:10px;border:1px solid #ccc;border-radius:6px;box-sizing:border-box;}
    button{width:100%;background:#667eea;color:white;padding:10px;border:none;border-radius:6px;cursor:pointer;font-weight:bold;}
    button:hover{background:#5a67d8;}
    .message{margin-top:15px;text-align:center;color:red;}
    .error-msg {color: #d93025; font-size: 0.9em; margin-top: 5px;}
    .login-link{margin-top:15px;text-align:center;font-size:0.9em;}
    .login-link a{color:#764ba2;text-decoration:none;}
    .login-link a:hover{text-decoration:underline;}
</style>
</head>
<body>
<div class="wrapper">
    <h2>Create Account</h2>
    <p style="text-align:center; color: #555; margin-bottom: 20px;">Your account will require admin approval after registration.</p>
    <form action="register.php" method="post">
        <?php if(!empty($register_err)): ?>
            <div class="message"><?= htmlspecialchars($register_err); ?></div>
        <?php endif; ?>

        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" value="<?= htmlspecialchars($username); ?>" required>
            <?php if(!empty($username_err)): ?><div class="error-msg"><?= $username_err; ?></div><?php endif; ?>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
            <?php if(!empty($password_err)): ?><div class="error-msg"><?= $password_err; ?></div><?php endif; ?>
        </div>
        <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
            <?php if(!empty($confirm_password_err)): ?><div class="error-msg"><?= $confirm_password_err; ?></div><?php endif; ?>
        </div>
        <button type="submit">Register</button>
        <div class="login-link">
            Already have an account? <a href="login.php">Login here</a>
        </div>
    </form>
</div>
</body>
</html>
