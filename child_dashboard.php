
<?php
session_start();
include 'php/db_connect.php';

// Check if child is logged in
if (!isset($_SESSION['child_id']) || !isset($_SESSION['child_name'])) {
    header("Location: child_login.php");
    exit;
}

$child_id = $_SESSION['child_id'];
$child_name = $_SESSION['child_name'];

// Handle logout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    // Destroy session
    session_destroy();
    header("Location: child_login.php");
    exit;
}

// Fetch devices with their rules for display and real-time calculation
$stmt = $conn->prepare("
    SELECT d.device_id, d.device_name, d.device_type, d.status,
           r.allowed_start, r.allowed_end, r.max_screen_time
    FROM devices d
    LEFT JOIN rules r ON d.device_id = r.device_id
    WHERE d.assigned_to=?
    ORDER BY d.device_name
");
$stmt->bind_param("i", $child_id);
$stmt->execute();
$result = $stmt->get_result();
$devices = $result->fetch_all(MYSQLI_ASSOC);

// Calculate real-time usage for each device
$devices_with_usage = [];
$current_time = new DateTime();

foreach ($devices as $device) {
    $device_data = $device;
    
    if ($device['allowed_start'] && $device['allowed_end']) {
        // Parse start and end times
        $start_time = new DateTime($device['allowed_start']);
        $end_time = new DateTime($device['allowed_end']);
        
        // Handle case where end time is next day (e.g., 23:00 to 06:00)
        if ($end_time <= $start_time) {
            $end_time->modify('+1 day');
        }
        
        // Check if current time is within allowed window
        $is_within_window = ($current_time >= $start_time && $current_time <= $end_time);
        
        if ($is_within_window) {
            // Calculate time used so far (from start to now)
            $interval = $start_time->diff($current_time);
            $hours_used = $interval->h + ($interval->i / 60) + ($interval->s / 3600);
            
            // Calculate time remaining (from now to end)
            $interval_remaining = $current_time->diff($end_time);
            $hours_remaining = $interval_remaining->h + ($interval_remaining->i / 60) + ($interval_remaining->s / 3600);
            
            // Calculate screen time used vs allowed
            $screen_time_used = min($hours_used, $device['max_screen_time']);
            $screen_time_remaining = max(0, $device['max_screen_time'] - $hours_used);
            
            // Calculate percentage of allowed screen time used
            $percentage_used = ($screen_time_used / $device['max_screen_time']) * 100;
            
            $device_data['is_active'] = true;
            $device_data['hours_used'] = round($hours_used, 2);
            $device_data['hours_remaining'] = round($hours_remaining, 2);
            $device_data['screen_time_used'] = round($screen_time_used, 2);
            $device_data['screen_time_remaining'] = round($screen_time_remaining, 2);
            $device_data['percentage_used'] = round($percentage_used, 1);
            $device_data['time_exceeded'] = $hours_used > $device['max_screen_time'];
        } else {
            $device_data['is_active'] = false;
            $device_data['message'] = 'Outside allowed time window';
        }
    } else {
        $device_data['is_active'] = false;
        $device_data['message'] = 'No rules set';
    }
    
    $devices_with_usage[] = $device_data;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Child Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            background: radial-gradient(circle at top left, #1b2b6f, #0a0f2e 60%);
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 40px 20px;
        }

        .container {
            background: #fff;
            width: 1000px;
            padding: 35px;
            border-radius: 18px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }

        .header {
            text-align: center;
            margin-bottom: 35px;
        }

        .header h2 {
            color: #2c3e50;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .current-time {
            color: #f4b942;
            font-size: 18px;
            font-weight: 600;
        }

        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2d6bff;
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 6px;
            font-size: 14px;
            color: #1565c0;
        }

        .devices-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .device-card {
            border: 2px solid #f4b942;
            border-radius: 12px;
            padding: 20px;
            background: #fff5e6;
            transition: all 0.3s;
        }

        .device-card:hover {
            box-shadow: 0 8px 20px rgba(244, 185, 66, 0.3);
            transform: translateY(-5px);
        }

        .device-card.inactive {
            opacity: 0.7;
            background: #f5f5f5;
            border-color: #ddd;
        }

        .device-name {
            font-size: 18px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 8px;
        }

        .device-type {
            font-size: 12px;
            color: #666;
            background: #f0f0f0;
            padding: 4px 8px;
            border-radius: 4px;
            display: inline-block;
            margin-bottom: 12px;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }

        .time-info {
            font-size: 13px;
            margin-bottom: 12px;
            padding: 8px;
            background: rgba(255, 255, 255, 0.6);
            border-radius: 6px;
        }

        .time-label {
            color: #666;
            font-weight: 600;
        }

        .time-value {
            color: #2c3e50;
            font-weight: 700;
        }

        .progress-container {
            margin: 15px 0;
        }

        .progress-label {
            font-size: 12px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 6px;
            display: flex;
            justify-content: space-between;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #2d6bff, #ff9f1c);
            border-radius: 4px;
            transition: width 0.3s;
        }

        .progress-fill.exceeded {
            background: linear-gradient(90deg, #dc3545, #ff6b6b);
        }

        .warning {
            background: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
            padding: 10px 12px;
            border-radius: 6px;
            font-size: 12px;
            margin-top: 10px;
            font-weight: 600;
        }

        .inactive-message {
            color: #999;
            font-style: italic;
            text-align: center;
            padding: 20px 10px;
        }

        .logout-btn {
            display: block;
            width: 200px;
            margin: 30px auto 0;
            padding: 15px;
            background: linear-gradient(135deg, #ff9f1c, #ff3b3b);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            font-size: 16px;
            transition: 0.3s;
        }

        .logout-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(255, 59, 59, 0.3);
        }

        .empty-message {
            text-align: center;
            padding: 40px;
            color: #999;
            font-style: italic;
        }

        @media (max-width: 768px) {
            .container {
                width: 100%;
            }

            .devices-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h2>👋 Welcome, <?= htmlspecialchars($child_name) ?></h2>
            <div class="current-time">🕐 Current Time: <?= $current_time->format('H:i:s') ?></div>
        </div>

        <div class="info-box">
            📱 <strong>Real-time Device Usage:</strong> Shows your current device usage based on the allowed time windows set by your parent. The progress bar indicates how much of your allowed screen time you've used.
        </div>

        <?php if (!empty($devices_with_usage)): ?>
            <div class="devices-grid">
                <?php foreach ($devices_with_usage as $device): ?>
                    <div class="device-card <?= !$device['is_active'] ? 'inactive' : '' ?>">
                        <div class="device-name"><?= htmlspecialchars($device['device_name']) ?></div>
                        <div class="device-type"><?= htmlspecialchars($device['device_type'] ?? 'Device') ?></div>

                        <?php if ($device['is_active']): ?>
                            <span class="status-badge status-active">✓ Active Now</span>

                            <!-- Time Window Info -->
                            <div class="time-info">
                                <div class="time-label">⏰ Time Window:</div>
                                <div class="time-value"><?= htmlspecialchars($device['allowed_start']) ?> - <?= htmlspecialchars($device['allowed_end']) ?></div>
                            </div>

                            <!-- Session Time Used -->
                            <div class="time-info">
                                <div class="time-label">⏱️ Time Used Since Start:</div>
                                <div class="time-value"><?= $device['hours_used'] ?> hours</div>
                            </div>

                            <!-- Time Remaining -->
                            <div class="time-info">
                                <div class="time-label">⏳ Time Until End:</div>
                                <div class="time-value"><?= $device['hours_remaining'] ?> hours</div>
                            </div>

                            <!-- Screen Time Progress -->
                            <div class="progress-container">
                                <div class="progress-label">
                                    <span>Screen Time Used</span>
                                    <span><?= $device['percentage_used'] ?>%</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill <?= $device['time_exceeded'] ? 'exceeded' : '' ?>" 
                                         style="width: <?= min($device['percentage_used'], 100) ?>%"></div>
                                </div>
                                <div class="progress-label" style="margin-top: 4px;">
                                    <span><?= $device['screen_time_used'] ?> / <?= $device['max_screen_time'] ?> hrs</span>
                                </div>
                            </div>

                            <?php if ($device['time_exceeded']): ?>
                                <div class="warning">
                                    ⚠️ You have exceeded your allowed screen time!
                                </div>
                            <?php elseif ($device['screen_time_remaining'] < 0.5): ?>
                                <div class="warning">
                                    ⚠️ Warning: Only <?= $device['screen_time_remaining'] ?> hours of screen time remaining!
                                </div>
                            <?php endif; ?>

                        <?php else: ?>
                            <span class="status-badge status-inactive">✗ Not Active</span>
                            <div class="inactive-message">
                                <?= htmlspecialchars($device['message']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-message">
                📭 No devices assigned to you yet. Please contact your parent to add devices.
            </div>
        <?php endif; ?>

        <form method="POST">
            <button type="submit" name="logout" class="logout-btn">Logout</button>
        </form>
    </div>

    <!-- Auto-refresh every second for real-time updates -->
    <script>
        setTimeout(function() {
            location.reload();
        }, 1000);
    </script>
</body>
</html>