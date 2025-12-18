
<?php
session_start();
include 'php/db_connect.php';

// Check if parent is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') {
    header("Location: php/login.php");
    exit;
}

$parent_id = $_SESSION['user_id'];

// Fetch all children for this parent
$stmt = $conn->prepare("SELECT user_id, full_name, email FROM users WHERE role='child' AND parent_id = ? ORDER BY full_name");
$stmt->bind_param("i", $parent_id);
$stmt->execute();
$result = $stmt->get_result();
$children = $result->fetch_all(MYSQLI_ASSOC);

// Handle messages
$msg = $_GET['msg'] ?? '';
$error = $_GET['error'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Children Management</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: radial-gradient(circle at top left, #1b2b6f, #0a0f2e 60%);
            min-height: 100vh;
            padding: 20px;
        }

        header {
            background: linear-gradient(135deg, #102a43, #1b3a6e);
            color: white;
            padding: 25px;
            text-align: center;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        header h1 {
            font-size: 28px;
            margin-bottom: 5px;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 35px;
            border-radius: 18px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }

        h2 {
            color: #2c3e50;
            font-size: 24px;
        }

        .btn-group {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 12px 20px;
            background: linear-gradient(135deg, #ff9f1c, #ff3b3b);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 59, 59, 0.3);
        }

        .btn-secondary {
            background: #2d6bff;
        }

        .btn-secondary:hover {
            background: #1a4fc9;
        }

        .alert {
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 10px;
            font-weight: 500;
            border-left: 5px solid;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left-color: #28a745;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left-color: #dc3545;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th {
            background: linear-gradient(135deg, #ff9f1c, #ff3b3b);
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-small {
            padding: 8px 12px;
            font-size: 13px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: 0.3s;
            font-weight: 600;
        }

        .btn-edit {
            background: #2d6bff;
            color: white;
        }

        .btn-edit:hover {
            background: #1a4fc9;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
        }

        .btn-delete:hover {
            background: #c82333;
        }

        .empty-message {
            text-align: center;
            padding: 40px;
            color: #999;
            font-size: 16px;
        }

        .empty-message p {
            margin-bottom: 15px;
        }

        @media (max-width: 768px) {
            .top-bar {
                flex-direction: column;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn-small {
                width: 100%;
            }
        }
    </style>
</head>

<body>

    <header>
        <h1>👨‍👧 Children Management</h1>
        <p>Manage your children's accounts</p>
    </header>

    <div class="container">

        <div class="top-bar">
            <h2>Registered Children</h2>
            <div class="btn-group">
                <a href="add_child.php" class="btn">➕ Add Child</a>
                <a href="dashboard.php" class="btn btn-secondary">← Back</a>
            </div>
        </div>

        <!-- Success / Error Messages -->
        <?php if ($msg): ?>
            <div class="alert alert-success">✓ <?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error">✗ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Children Table -->
        <?php if (!empty($children)): ?>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; foreach ($children as $child): ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><?= htmlspecialchars($child['full_name']) ?></td>
                            <td><?= htmlspecialchars($child['email']) ?></td>
                            <td>
                                <div class="action-buttons">
                                    <form action="edit_child.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="user_id" value="<?= $child['user_id'] ?>">
                                        <button type="submit" class="btn-small btn-edit">✏️ Edit</button>
                                    </form>
                                    <form action="delete_child.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this child account?');">
                                        <input type="hidden" name="user_id" value="<?= $child['user_id'] ?>">
                                        <button type="submit" class="btn-small btn-delete">🗑️ Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-message">
                <p>📭 No children added yet</p>
                <a href="add_child.php" class="btn">➕ Add Your First Child</a>
            </div>
        <?php endif; ?>

    </div>

</body>

</html>