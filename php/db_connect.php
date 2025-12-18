
<?php
$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'smart_user';
$password = getenv('DB_PASSWORD') ?: 'smart_password';
$database = getenv('DB_NAME') ?: 'smart_home_system';

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>