
<?php
session_start();
include 'php/db_connect.php';

// Check if child is logged in
if (!isset($_SESSION['child_id'])) {
    header("Location: child_login.php");
    exit;
}

$child_id = $_SESSION['child_id'];

// Update device status to OFF when child logs out using prepared statement
$stmt = $conn->prepare("UPDATE devices SET status='OFF' WHERE assigned_to = ?");
$stmt->bind_param("i", $child_id);
$stmt->execute();

// Destroy session
session_unset();
session_destroy();

// Redirect to child login
header("Location: child_login.php");
exit;
?>