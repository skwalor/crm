<?php
$servername = "127.0.0.1";
$username = "root";
$password = "***REMOVED***";
$dbname = "crm_db";
$port = 3307;

$conn = new mysqli($servername, $username, $password, $dbname, $port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>