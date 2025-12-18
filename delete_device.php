
<?php
session_start();
include 'php/db_connect.php';

// Check if parent is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') {
    header("Location: php/login.php");
    exit;
}

$parent_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $device_id = $_POST['device_id'] ?? '';

    if (empty($device_id)) {
        header("Location: devices.php?error=Invalid+device");
        exit;
    }

    // Verify device belongs to this parent before deleting
    $stmt = $conn->prepare("SELECT device_id FROM devices WHERE device_id = ? AND parent_id = ?");
    $stmt->bind_param("ii", $device_id, $parent_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        header("Location: devices.php?error=Device+not+found+or+unauthorized");
        exit;
    }

    // Delete the device
    $stmt = $conn->prepare("DELETE FROM devices WHERE device_id = ? AND parent_id = ?");
    $stmt->bind_param("ii", $device_id, $parent_id);

    if ($stmt->execute()) {
        header("Location: devices.php?msg=Device+deleted+successfully");
        exit;
    } else {
        header("Location: devices.php?error=Error+deleting+device");
        exit;
    }
} else {
    // If not POST request, redirect to devices page
    header("Location: devices.php");
    exit;
}
?>