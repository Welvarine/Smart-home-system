
<?php
session_start();
include 'php/db_connect.php';

// Check if parent is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') {
    header("Location: php/login.php");
    exit;
}

$parent_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $user_id = intval($_POST['user_id']);

    // Verify child belongs to this parent
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE user_id = ? AND role='child' AND parent_id = ?");
    $stmt->bind_param("ii", $user_id, $parent_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        header("Location: children.php?error=Child+not+found");
        exit;
    }

    // Unassign devices first
    $stmt = $conn->prepare("UPDATE devices SET assigned_to = NULL WHERE assigned_to = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    // Delete child
    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ? AND role='child' AND parent_id = ?");
    $stmt->bind_param("ii", $user_id, $parent_id);

    if ($stmt->execute()) {
        header("Location: children.php?msg=Child+deleted+successfully");
        exit;
    } else {
        header("Location: children.php?error=Failed+to+delete+child");
        exit;
    }
} else {
    header("Location: children.php");
    exit;
}
?>