
<?php
session_start();
include 'php/db_connect.php';

// Check if parent is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') {
    header("Location: php/login.php");
    exit;
}

$parent_id = $_SESSION['user_id'];

// Fetch parent's name
$stmt = $conn->prepare("SELECT full_name FROM users WHERE user_id = ? AND role = 'parent'");
$stmt->bind_param("i", $parent_id);
$stmt->execute();
$result = $stmt->get_result();
$parent = $result->fetch_assoc();
$parent_name = $parent['full_name'] ?? 'Parent';

// Fetch all children assigned to this parent
$stmt = $conn->prepare("SELECT user_id, full_name FROM users WHERE parent_id = ? AND role = 'child'");
$stmt->bind_param("i", $parent_id);
$stmt->execute();
$result = $stmt->get_result();
$children = $result->fetch_all(MYSQLI_ASSOC);

// Fetch all devices assigned to this parent's children
$stmt = $conn->prepare("
    SELECT d.device_id, d.device_name, d.device_type, d.status, u.full_name as child_name
    FROM devices d
    LEFT JOIN users u ON d.assigned_to = u.user_id
    WHERE d.parent_id = ?
    ORDER BY d.device_name
");
$stmt->bind_param("i", $parent_id);
$stmt->execute();
$result = $stmt->get_result();
$devices = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Parent Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            min-height: 100vh;
            background: rgba(16, 42, 67, 0.85);
            display: flex;
            flex-direction: column;
        }

        /* Navigation Bar */
        nav {
            background-color: #102a43;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }

        .nav-brand {
            color: #f4b942;
            font-size: 24px;
            font-weight: bold;
        }

        .nav-links {
            display: flex;
            gap: 20px;
            list-style: none;
            align-items: center;
        }

        .nav-links a {
            color: #fff;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 4px;
            transition: background-color 0.3s;
        }

        .nav-links a:hover {
            background-color: #f4b942;
            color: #102a43;
        }

        .user-info {
            color: #f4b942;
            font-size: 14px;
        }

        /* Main Container */
        .main-container {
            display: flex;
            flex: 1;
            padding: 30px;
            gap: 30px;
        }

        /* Sidebar */
        .sidebar {
            width: 250px;
            background: #fff;
            padding: 25px;
            border-radius: 18px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
            height: fit-content;
        }

        .sidebar h3 {
            color: #2c3e50;
            margin-bottom: 20px;
            border-bottom: 2px solid #f4b942;
            padding-bottom: 10px;
        }

        .sidebar-menu {
            list-style: none;
        }

        .sidebar-menu li {
            margin-bottom: 10px;
        }

        .sidebar-menu a {
            display: block;
            color: #2c3e50;
            text-decoration: none;
            padding: 12px 15px;
            background-color: #f0f0f0;
            border-radius: 6px;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }

        .sidebar-menu a:hover {
            background-color: #f4b942;
            color: #102a43;
            border-left-color: #102a43;
            padding-left: 20px;
        }

        /* Content Area */
        .content {
            flex: 1;
            background: #fff;
            padding: 35px;
            border-radius: 18px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
            max-height: 80vh;
            overflow-y: auto;
        }

        h2 {
            color: #2c3e50;
            margin-bottom: 25px;
            border-bottom: 2px solid #f4b942;
            padding-bottom: 10px;
        }

        h3 {
            color: #102a43;
            margin-top: 25px;
            margin-bottom: 15px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th, td {
            border: 1px solid #f4b942;
            padding: 12px;
            text-align: left;
        }

        th {
            background-color: #f4b942;
            color: #2c3e50;
            font-weight: bold;
        }

        td {
            background-color: #fff5e6;
            color: #2c3e50;
        }

        .empty-message {
            text-align: center;
            color: #666;
            padding: 20px;
            font-style: italic;
        }

        .status-on {
            color: #28a745;
            font-weight: bold;
        }

        .status-off {
            color: #dc3545;
            font-weight: bold;
        }

        @media (max-width: 768px) {
            .main-container {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
            }

            .nav-links {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>

<body>
    <!-- Navigation Bar -->
    <nav>
        <div class="nav-brand">Smart Home System</div>
        <ul class="nav-links">
            <li><a href="dashboard.php">Dashboard</a></li>
            <li class="user-info">👤 <?= htmlspecialchars($parent_name) ?></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>

    <div class="main-container">
        <!-- Sidebar Menu -->
        <aside class="sidebar">
            <h3>Children</h3>
            <ul class="sidebar-menu">
                <li><a href="add_child.php">➕ Add Child</a></li>
                <li><a href="children.php">👨‍👧 View Children</a></li>
            </ul>

            <h3 style="margin-top: 25px;">Devices</h3>
            <ul class="sidebar-menu">
                <li><a href="add_device.php">➕ Add Device</a></li>
                <li><a href="devices.php">📱 View Devices</a></li>
            </ul>

            <h3 style="margin-top: 25px;">Rules</h3>
            <ul class="sidebar-menu">
                <li><a href="add_rule.php">➕ Add Rule</a></li>
                <li><a href="rules_chart.php">📊 View Rules</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <div class="content">
            <h2>Welcome, <?= htmlspecialchars($parent_name) ?> 👋</h2>

            <h3>Your Children</h3>
            <?php if (!empty($children)): ?>
                <table>
                    <tr>
                        <th>Child Name</th>
                        <th>User ID</th>
                    </tr>
                    <?php foreach ($children as $child): ?>
                        <tr>
                            <td><?= htmlspecialchars($child['full_name']) ?></td>
                            <td><?= htmlspecialchars($child['user_id']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php else: ?>
                <p class="empty-message">No children assigned yet. <a href="add_child.php">Add a child</a></p>
            <?php endif; ?>

            <h3>Your Devices</h3>
            <?php if (!empty($devices)): ?>
                <table>
                    <tr>
                        <th>Device Name</th>
                        <th>Device Type</th>
                        <th>Status</th>
                        <th>Assigned To</th>
                    </tr>
                    <?php foreach ($devices as $device): ?>
                        <tr>
                            <td><?= htmlspecialchars($device['device_name']) ?></td>
                            <td><?= htmlspecialchars($device['device_type']) ?></td>
                            <td>
                                <span class="<?= $device['status'] === 'ON' ? 'status-on' : 'status-off' ?>">
                                    <?= htmlspecialchars($device['status']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($device['child_name'] ?? 'Unassigned') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php else: ?>
                <p class="empty-message">No devices added yet. <a href="add_device.php">Add a device</a></p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>