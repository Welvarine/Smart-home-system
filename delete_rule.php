
<?php
session_start();
include 'php/db_connect.php';

// Check if parent is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') {
    header("Location: php/login.php");
    exit;
}

$parent_id = $_SESSION['user_id'];
$rule_id = $_POST['rule_id'] ?? '';

if (empty($rule_id)) {
    header("Location: rules_chart.php?error=Invalid+rule");
    exit;
}

// Verify rule belongs to parent's device
$stmt = $conn->prepare("
    SELECT r.rule_id FROM rules r
    JOIN devices d ON r.device_id = d.device_id
    WHERE r.rule_id = ? AND d.parent_id = ?
");
$stmt->bind_param("ii", $rule_id, $parent_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: rules_chart.php?error=Rule+not+found+or+unauthorized");
    exit;
}

// Delete the rule
$stmt = $conn->prepare("DELETE FROM rules WHERE rule_id = ?");
$stmt->bind_param("i", $rule_id);

if ($stmt->execute()) {
    header("Location: rules_chart.php?msg=Rule+deleted+successfully");
    exit;
} else {
    header("Location: rules_chart.php?error=Error+deleting+rule");
    exit;
}
?>