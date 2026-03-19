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
    .toggle-password{background:none;border:none;cursor:pointer;font-size:0.8rem;color:#764ba2;padding:0;margin-top:5px;display:inline-block;width:auto;}
    .toggle-password:hover{text-decoration:underline;background:none;}
    .strength-bar-container{height:4px;background:#e0e0e0;border-radius:2px;margin-top:6px;overflow:hidden;}
    .strength-bar{height:100%;width:0;border-radius:2px;transition:width 0.3s,background 0.3s;}
    .strength-text{font-size:0.8rem;margin-top:3px;color:#666;}
    .match-indicator{font-size:0.8rem;margin-top:3px;}
    .match-ok{color:#28a745;}
    .match-no{color:#dc3545;}
    button{width:100%;background:#667eea;color:white;padding:10px;border:none;border-radius:6px;cursor:pointer;font-weight:bold;}
    button:hover{background:#5a67d8;}
    button:disabled{opacity:0.7;cursor:not-allowed;}
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
            <input type="password" id="password" name="password" required oninput="updateStrength(); checkMatch();">
            <button type="button" class="toggle-password" onclick="toggleVis('password', this)" aria-label="Show password">Show password</button>
            <div class="strength-bar-container"><div class="strength-bar" id="strengthBar"></div></div>
            <div class="strength-text" id="strengthText"></div>
            <?php if(!empty($password_err)): ?><div class="error-msg"><?= $password_err; ?></div><?php endif; ?>
        </div>
        <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" required oninput="checkMatch();">
            <button type="button" class="toggle-password" onclick="toggleVis('confirm_password', this)" aria-label="Show password">Show password</button>
            <div id="matchIndicator" class="match-indicator"></div>
            <?php if(!empty($confirm_password_err)): ?><div class="error-msg"><?= $confirm_password_err; ?></div><?php endif; ?>
        </div>
        <button type="submit" id="registerBtn">Register</button>
        <div class="login-link">
            Already have an account? <a href="login.php">Login here</a>
        </div>
    </form>
</div>
<script>
function toggleVis(inputId, btn) {
    const input = document.getElementById(inputId);
    if (input.type === 'password') { input.type = 'text'; btn.textContent = 'Hide password'; }
    else { input.type = 'password'; btn.textContent = 'Show password'; }
}
function updateStrength() {
    const pw = document.getElementById('password').value;
    const bar = document.getElementById('strengthBar');
    const text = document.getElementById('strengthText');
    let score = 0;
    if (pw.length >= 6) score++;
    if (pw.length >= 10) score++;
    if (/[A-Z]/.test(pw)) score++;
    if (/[0-9]/.test(pw)) score++;
    if (/[^A-Za-z0-9]/.test(pw)) score++;
    const pct = (score / 5) * 100;
    const colors = ['#dc3545','#dc3545','#ffc107','#28a745','#28a745','#28a745'];
    const labels = ['','Weak','Fair','Good','Strong','Very strong'];
    bar.style.width = pct + '%';
    bar.style.background = colors[score];
    text.textContent = pw.length > 0 ? labels[score] : '';
}
function checkMatch() {
    const pw = document.getElementById('password').value;
    const cpw = document.getElementById('confirm_password').value;
    const el = document.getElementById('matchIndicator');
    if (cpw.length === 0) { el.textContent = ''; return; }
    if (pw === cpw) { el.textContent = 'Passwords match'; el.className = 'match-indicator match-ok'; }
    else { el.textContent = 'Passwords do not match'; el.className = 'match-indicator match-no'; }
}
document.querySelector('form').addEventListener('submit', function() {
    const btn = document.getElementById('registerBtn');
    btn.disabled = true;
    btn.textContent = 'Creating account...';
});
</script>
</body>
</html>
