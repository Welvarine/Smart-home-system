
<?php
session_start();
include 'php/db_connect.php';

// Check if parent is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') {
    header("Location: php/login.php");
    exit;
}

$parent_id = $_SESSION['user_id'];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $device_name = trim($_POST['device_name'] ?? '');
    $device_type = trim($_POST['device_type'] ?? '');
    $assigned_to = trim($_POST['assigned_to'] ?? '');

    // Validate required fields
    if (empty($device_name)) {
        $error = "Device name is required.";
    } elseif (empty($assigned_to)) {
        $error = "Please select a child to assign the device to.";
    } else {
        // Validate that assigned_to exists and belongs to this parent
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE user_id = ? AND role='child' AND parent_id = ?");
        $stmt->bind_param("ii", $assigned_to, $parent_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $error = "Invalid child selected.";
        } else {
            // Insert device
            $stmt = $conn->prepare("INSERT INTO devices (device_name, device_type, assigned_to, parent_id, status) VALUES (?, ?, ?, ?, 'OFF')");
            $stmt->bind_param("ssii", $device_name, $device_type, $assigned_to, $parent_id);

            if ($stmt->execute()) {
                $success = "Device added successfully!";
                header("refresh:2;url=devices.php");
                $_POST = array();
            } else {
                $error = "Error adding device. Please try again.";
            }
        }
    }
}

// Fetch children for dropdown
$stmt = $conn->prepare("SELECT user_id, full_name FROM users WHERE role='child' AND parent_id = ? ORDER BY full_name");
$stmt->bind_param("i", $parent_id);
$stmt->execute();
$result = $stmt->get_result();
$children = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Device</title>
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

        .note {
            text-align: center;
            margin-top: 16px;
            font-size: 13px;
            color: #5f6b8a;
        }

        .empty-children {
            background: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .empty-children a {
            color: #2d6bff;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>

<body>

    <div class="card">
        <a href="devices.php" class="back-link">← Back to Devices</a>

        <h2>Add Device</h2>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <p style="text-align: center; color: #388e3c; font-size: 13px; margin-top: 10px;">
                Redirecting in 2 seconds... <a href="devices.php" style="color: #2d6bff;">Click here if not redirected</a>
            </p>
        <?php else: ?>

            <?php if (empty($children)): ?>
                <div class="empty-children">
                    ⚠️ No children found. Please <a href="add_child.php">add a child</a> first before adding devices.
                </div>
            <?php else: ?>

                <form method="POST">
                    <label>Device Name *</label>
                    <input type="text" name="device_name" placeholder="e.g., Living Room TV, Bedroom Lamp" value="<?= htmlspecialchars($_POST['device_name'] ?? '') ?>" required>

                    <label>Device Type</label>
                    <select name="device_type">
                        <option value="">Select device type (optional)</option>
                        <optgroup label="Smart Home">
                            <option value="Smart TV" <?= ($_POST['device_type'] ?? '') === 'Smart TV' ? 'selected' : '' ?>>Smart TV</option>
                            <option value="Light" <?= ($_POST['device_type'] ?? '') === 'Light' ? 'selected' : '' ?>>Light</option>
                            <option value="Speaker" <?= ($_POST['device_type'] ?? '') === 'Speaker' ? 'selected' : '' ?>>Speaker</option>
                            <option value="Thermostat" <?= ($_POST['device_type'] ?? '') === 'Thermostat' ? 'selected' : '' ?>>Thermostat</option>
                            <option value="Door Lock" <?= ($_POST['device_type'] ?? '') === 'Door Lock' ? 'selected' : '' ?>>Door Lock</option>
                            <option value="Camera" <?= ($_POST['device_type'] ?? '') === 'Camera' ? 'selected' : '' ?>>Camera</option>
                        </optgroup>
                        <optgroup label="Personal Devices">
                            <option value="Smartphone" <?= ($_POST['device_type'] ?? '') === 'Smartphone' ? 'selected' : '' ?>>📱 Smartphone</option>
                            <option value="Laptop" <?= ($_POST['device_type'] ?? '') === 'Laptop' ? 'selected' : '' ?>>💻 Laptop</option>
                            <option value="Tablet" <?= ($_POST['device_type'] ?? '') === 'Tablet' ? 'selected' : '' ?>>📲 Tablet</option>
                            <option value="Desktop Computer" <?= ($_POST['device_type'] ?? '') === 'Desktop Computer' ? 'selected' : '' ?>>🖥️ Desktop Computer</option>
                            <option value="Gaming Console" <?= ($_POST['device_type'] ?? '') === 'Gaming Console' ? 'selected' : '' ?>>🎮 Gaming Console</option>
                        </optgroup>
                        <optgroup label="Other">
                            <option value="Other" <?= ($_POST['device_type'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                        </optgroup>
                    </select>

                    <label>Assign to Child *</label>
                    <select name="assigned_to" required>
                        <option value="">Select a child</option>
                        <?php foreach ($children as $child): ?>
                            <option value="<?= $child['user_id'] ?>" <?= ($_POST['assigned_to'] ?? '') == $child['user_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($child['full_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit">Add Device</button>
                </form>

            <?php endif; ?>

        <?php endif; ?>

        <p class="note">
            Devices will be set to OFF status by default.
        </p>
    </div>

</body>
</html>