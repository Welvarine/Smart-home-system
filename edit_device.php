<?php
session_start();
include 'php/db_connect.php';

// Check if parent is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') {
    header("Location: php/login.php");
    exit;
}

$parent_id = $_SESSION['user_id'];
$device_id = $_POST['device_id'] ?? null;
$error = '';
$success = '';

if (!$device_id) {
    header("Location: devices.php");
    exit;
}

// Fetch device info - verify it belongs to parent
$stmt = $conn->prepare("SELECT * FROM devices WHERE device_id = ? AND parent_id = ?");
$stmt->bind_param("ii", $device_id, $parent_id);
$stmt->execute();
$result = $stmt->get_result();
$device = $result->fetch_assoc();

if (!$device) {
    header("Location: devices.php?error=Device+not+found");
    exit;
}

// Fetch children for dropdown
$stmt = $conn->prepare("SELECT user_id, full_name FROM users WHERE role='child' AND parent_id = ? ORDER BY full_name");
$stmt->bind_param("i", $parent_id);
$stmt->execute();
$result = $stmt->get_result();
$children = $result->fetch_all(MYSQLI_ASSOC);

// If form is submitted, update device
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_device'])) {
    $device_name = trim($_POST['device_name'] ?? '');
    $device_type = trim($_POST['device_type'] ?? '');
    $assigned_to = trim($_POST['assigned_to'] ?? '');

    // Validate
    if (empty($device_name)) {
        $error = "Device name is required.";
    } elseif (empty($assigned_to)) {
        $error = "Please select a child.";
    } else {
        // Verify assigned child belongs to this parent
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE user_id = ? AND role='child' AND parent_id = ?");
        $stmt->bind_param("ii", $assigned_to, $parent_id);
        $stmt->execute();
        $check = $stmt->get_result();

        if ($check->num_rows === 0) {
            $error = "Invalid child selected.";
        } else {
            // Update device
            $update = $conn->prepare("UPDATE devices SET device_name=?, device_type=?, assigned_to=? WHERE device_id=? AND parent_id=?");
            $update->bind_param("ssiii", $device_name, $device_type, $assigned_to, $device_id, $parent_id);
            
            if ($update->execute()) {
                $success = "Device updated successfully!";
                header("refresh:2;url=devices.php");
                // Refresh device data
                $stmt = $conn->prepare("SELECT * FROM devices WHERE device_id = ?");
                $stmt->bind_param("i", $device_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $device = $result->fetch_assoc();
            } else {
                $error = "Error updating device. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Device</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            min-height: 100vh;
            background: radial-gradient(
                circle at top left,
                #1b2b6f,
                #0a0f2e 60%
            );
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .card {
            width: 450px;
            background: linear-gradient(
                180deg,
                #ffffff,
                #f3f6fb
            );
            padding: 36px;
            border-radius: 22px;
            box-shadow:
                0 25px 50px rgba(0, 0, 0, 0.45),
                inset 0 0 0 1px rgba(255, 255, 255, 0.6);
            position: relative;
        }

        .card::before {
            content: "";
            position: absolute;
            inset: -2px;
            border-radius: 24px;
            background: linear-gradient(
                135deg,
                #ff3b3b,
                #ff9f1c,
                #2d6bff
            );
            z-index: -1;
        }

        h2 {
            text-align: center;
            margin-bottom: 26px;
            font-size: 28px;
            font-weight: 700;
            color: #1b2b6f;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #2d6bff;
            text-decoration: none;
            font-size: 14px;
            transition: 0.3s;
        }

        .back-link:hover {
            color: #ff9f1c;
        }

        .alert {
            padding: 12px 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 500;
        }

        .alert-error {
            background: #ffe0e0;
            color: #d32f2f;
            border: 1px solid #ff9999;
        }

        .alert-success {
            background: #e0ffe0;
            color: #388e3c;
            border: 1px solid #99ff99;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #2b2f4a;
        }

        input, select {
            width: 100%;
            padding: 13px 15px;
            margin-bottom: 20px;
            border-radius: 12px;
            border: 1px solid #d6dbea;
            background: #ffffff;
            font-size: 15px;
            transition: 0.3s;
        }

        input:focus, select:focus {
            outline: none;
            border-color: #2d6bff;
            box-shadow: 0 0 0 3px rgba(45, 107, 255, 0.25);
        }

        button {
            width: 100%;
            padding: 15px;
            background: linear-gradient(
                135deg,
                #ff9f1c,
                #ff3b3b
            );
            color: #fff;
            border: none;
            border-radius: 14px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.35s;
        }

        button:hover {
            transform: translateY(-3px);
            box-shadow:
                0 12px 25px rgba(255, 59, 59, 0.45),
                0 0 0 3px rgba(255, 159, 28, 0.35);
        }

        .device-info {
            background: #f0f0f0;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #666;
        }
    </style>
</head>

<body>

    <div class="card">
        <a href="devices.php" class="back-link">← Back to Devices</a>

        <h2>Edit Device</h2>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <p style="text-align: center; color: #388e3c; font-size: 13px; margin-top: 10px;">
                Redirecting in 2 seconds... <a href="devices.php" style="color: #2d6bff;">Click here if not redirected</a>
            </p>
        <?php else: ?>

            <div class="device-info">
                <strong>Device ID:</strong> #<?= $device['device_id'] ?><br>
                <strong>Created:</strong> <?= date('M d, Y', strtotime($device['created_at'] ?? 'now')) ?>
            </div>

            <form method="POST">
                <input type="hidden" name="device_id" value="<?= $device_id ?>">

                <label>Device Name *</label>
                <input type="text" name="device_name" placeholder="e.g., Living Room TV" value="<?= htmlspecialchars($device['device_name']) ?>" required>

                <label>Device Type</label>
                <select name="device_type">
                    <option value="">Select device type</option>
                    <optgroup label="Smart Home">
                        <option value="Smart TV" <?= $device['device_type'] === 'Smart TV' ? 'selected' : '' ?>>Smart TV</option>
                        <option value="Light" <?= $device['device_type'] === 'Light' ? 'selected' : '' ?>>Light</option>
                        <option value="Speaker" <?= $device['device_type'] === 'Speaker' ? 'selected' : '' ?>>Speaker</option>
                        <option value="Thermostat" <?= $device['device_type'] === 'Thermostat' ? 'selected' : '' ?>>Thermostat</option>
                        <option value="Door Lock" <?= $device['device_type'] === 'Door Lock' ? 'selected' : '' ?>>Door Lock</option>
                        <option value="Camera" <?= $device['device_type'] === 'Camera' ? 'selected' : '' ?>>Camera</option>
                    </optgroup>
                    <optgroup label="Personal Devices">
                        <option value="Smartphone" <?= $device['device_type'] === 'Smartphone' ? 'selected' : '' ?>>📱 Smartphone</option>
                        <option value="Laptop" <?= $device['device_type'] === 'Laptop' ? 'selected' : '' ?>>💻 Laptop</option>
                        <option value="Tablet" <?= $device['device_type'] === 'Tablet' ? 'selected' : '' ?>>📲 Tablet</option>
                        <option value="Desktop Computer" <?= $device['device_type'] === 'Desktop Computer' ? 'selected' : '' ?>>🖥️ Desktop Computer</option>
                        <option value="Gaming Console" <?= $device['device_type'] === 'Gaming Console' ? 'selected' : '' ?>>🎮 Gaming Console</option>
                    </optgroup>
                    <optgroup label="Other">
                        <option value="Other" <?= $device['device_type'] === 'Other' ? 'selected' : '' ?>>Other</option>
                    </optgroup>
                </select>

                <label>Assign to Child *</label>
                <select name="assigned_to" required>
                    <option value="">Select a child</option>
                    <?php foreach ($children as $child): ?>
                        <option value="<?= $child['user_id'] ?>" <?= $device['assigned_to'] == $child['user_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($child['full_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" name="update_device">Update Device</button>
            </form>

        <?php endif; ?>

    </div>

</body>
</html>