<?php
// Harden session cookies before starting the session
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => false, // Set to true when using HTTPS
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}
?>
