
<?php
session_start();
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rule_id = $_POST['rule_id'] ?? '';
    $device_id = $_POST['device_id'] ?? '';
    $max_screen_time = $_POST['max_screen_time'] ?? '';
    $allowed_start = $_POST['allowed_start'] ?? '';
    $allowed_end = $_POST['allowed_end'] ?? '';

    if (!$rule_id || !$device_id || !$max_screen_time || !$allowed_start || !$allowed_end) {
        die("All fields are required. <a href='../rules.php'>Go back</a>");
    }

    $stmt = $conn->prepare("UPDATE rules SET device_id=?, max_screen_time=?, allowed_start=?, allowed_end=? WHERE rule_id=?");
    $stmt->bind_param("isssi", $device_id, $max_screen_time, $allowed_start, $allowed_end, $rule_id);

    if ($stmt->execute()) {
        header("Location: ../rules.php?msg=Rule updated successfully");
        exit;
    } else {
        echo "Error updating rule: " . $conn->error . " <a href='../rules.php'>Go back</a>";
    }
}
?>
