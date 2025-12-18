
<?php
session_start();
include 'php/db_connect.php';

// Check if parent is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') {
    header("Location: php/login.php");
    exit;
}

$parent_id = $_SESSION['user_id'];

// Handle messages
$msg = $_GET['msg'] ?? '';
$error = $_GET['error'] ?? '';

// Fetch all rules for this parent's devices
$stmt = $conn->prepare("
    SELECT r.rule_id, r.device_id, r.max_screen_time, r.allowed_start, r.allowed_end, r.created_at,
           d.device_name
    FROM rules r
    JOIN devices d ON r.device_id = d.device_id
    WHERE d.parent_id = ?
    ORDER BY d.device_name
");
$stmt->bind_param("i", $parent_id);
$stmt->execute();
$result = $stmt->get_result();
$rules = $result->fetch_all(MYSQLI_ASSOC);

// Prepare chart data with real-time calculations
$devices_chart = [];
$max_hours_chart = [];
$time_used_chart = [];
$time_remaining_chart = [];
$percentage_chart = [];

$current_time = new DateTime();

foreach ($rules as $rule) {
    // Parse start and end times
    $start = new DateTime($rule['allowed_start']);
    $end = new DateTime($rule['allowed_end']);
    
    // Handle case where end time is next day (e.g., 23:00 to 06:00)
    if ($end <= $start) {
        $end->modify('+1 day');
    }
    
    // Check if current time is within allowed window
    $is_within_window = ($current_time >= $start && $current_time <= $end);
    
    // Calculate hours used so far (from start to now)
    $interval_used = $start->diff($current_time);
    $hours_used = $interval_used->h + ($interval_used->i / 60);
    
    // Calculate hours remaining (from now to end)
    $interval_remaining = $current_time->diff($end);
    $hours_remaining = $interval_remaining->h + ($interval_remaining->i / 60);
    
    // Calculate screen time used vs allowed
    $screen_time_used = min($hours_used, $rule['max_screen_time']);
    $screen_time_remaining = max(0, $rule['max_screen_time'] - $hours_used);
    
    // Calculate percentage of allowed screen time used
    $percentage_used = ($screen_time_used / $rule['max_screen_time']) * 100;
    
    $devices_chart[] = htmlspecialchars($rule['device_name']);
    $max_hours_chart[] = (float)$rule['max_screen_time'];
    $time_used_chart[] = round($screen_time_used, 2);
    $time_remaining_chart[] = round($screen_time_remaining, 2);
    $percentage_chart[] = round($percentage_used, 1);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Device Rules Management</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            padding: 30px 20px;
        }

        .container {
            max-width: 1100px;
            margin: 0 auto;
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

        .current-time {
            color: #f4b942;
            font-size: 14px;
            margin-top: 10px;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
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

        .chart-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            margin-bottom: 30px;
        }

        .chart-section h2 {
            color: #2c3e50;
            margin-bottom: 20px;
        }

        .chart-container {
            position: relative;
            height: 350px;
            margin-bottom: 20px;
        }

        .chart-legend {
            display: flex;
            justify-content: center;
            gap: 30px;
            flex-wrap: wrap;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 4px;
        }

        .table-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }

        .table-section h2 {
            color: #2c3e50;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
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

        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2d6bff;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            font-size: 14px;
            color: #1565c0;
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

            .chart-container {
                height: 250px;
            }

            .chart-legend {
                gap: 15px;
            }
        }
    </style>
</head>

<body>
    <div class="container">

        <header>
            <h1>📋 Device Rules Management</h1>
            <p>Monitor real-time screen time usage across all devices</p>
            <div class="current-time">🕐 Current Time: <?= $current_time->format('H:i:s') ?></div>
        </header>

        <div class="top-bar">
            <div></div>
            <div class="btn-group">
                <a href="add_rule.php" class="btn">➕ Add Rule</a>
                <a href="dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
            </div>
        </div>

        <!-- Success / Error Messages -->
        <?php if ($msg): ?>
            <div class="alert alert-success">✓ <?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error">✗ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Chart Section -->
        <?php if (!empty($devices_chart)): ?>
            <div class="chart-section">
                <h2>Real-time Screen Time Usage</h2>
                
                <div class="info-box">
                    📊 <strong>Chart Information:</strong> Blue bars show screen time already used. Orange bars show remaining screen time available. Based on current time and allowed time windows.
                </div>

                <div class="chart-container">
                    <canvas id="rulesChart"></canvas>
                </div>

                <div class="chart-legend">
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: #2d6bff;"></div>
                        <span>Screen Time Used (hours)</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: #ff9f1c;"></div>
                        <span>Screen Time Remaining (hours)</span>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Rules Table -->
        <div class="table-section">
            <h2>All Rules with Real-time Status</h2>

            <?php if (!empty($rules)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Device</th>
                            <th>Time Window</th>
                            <th>Max Hours</th>
                            <th>Used (current)</th>
                            <th>Remaining</th>
                            <th>Usage %</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rules as $index => $row): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($row['device_name']) ?></strong></td>
                                <td><?= htmlspecialchars($row['allowed_start']) ?> - <?= htmlspecialchars($row['allowed_end']) ?></td>
                                <td><?= htmlspecialchars($row['max_screen_time']) ?> hrs</td>
                                <td><?= $time_used_chart[$index] ?> hrs</td>
                                <td><?= $time_remaining_chart[$index] ?> hrs</td>
                                <td>
                                    <span style="background: <?= $percentage_chart[$index] > 100 ? '#ffcccc' : '#ccffcc' ?>; padding: 4px 8px; border-radius: 4px; font-weight: bold;">
                                        <?= $percentage_chart[$index] ?>%
                                    </span>
                                </td>
                                <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <form style="display:inline;" action="edit_rule.php" method="POST">
                                            <input type="hidden" name="rule_id" value="<?= $row['rule_id'] ?>">
                                            <button type="submit" class="btn-small btn-edit">✏️ Edit</button>
                                        </form>
                                        <form style="display:inline;" action="delete_rule.php" method="POST" 
                                              onsubmit="return confirm('Are you sure you want to delete this rule?');">
                                            <input type="hidden" name="rule_id" value="<?= $row['rule_id'] ?>">
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
                    <p>📭 No rules created yet</p>
                    <a href="add_rule.php" class="btn">➕ Create Your First Rule</a>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <?php if (!empty($devices_chart)): ?>
        <script>
            const ctx = document.getElementById('rulesChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?= json_encode($devices_chart); ?>,
                    datasets: [
                        {
                            label: 'Screen Time Used (hours)',
                            data: <?= json_encode($time_used_chart); ?>,
                            backgroundColor: '#2d6bff',
                            borderColor: '#1a4fc9',
                            borderWidth: 2
                        },
                        {
                            label: 'Screen Time Remaining (hours)',
                            data: <?= json_encode($time_remaining_chart); ?>,
                            backgroundColor: '#ff9f1c',
                            borderColor: '#e0a82e',
                            borderWidth: 2
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value + ' hrs';
                                }
                            },
                            title: {
                                display: true,
                                text: 'Hours'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                padding: 15,
                                font: {
                                    size: 14
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    label += Number(context.parsed.y).toFixed(2) + ' hours';
                                    return label;
                                }
                            }
                        }
                    }
                }
            });
        </script>
    <?php endif; ?>

</body>
</html>