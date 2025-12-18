
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
    $device_id = trim($_POST['device_id'] ?? '');
    $max_screen_time = trim($_POST['max_screen_time'] ?? '');
    $allowed_start = trim($_POST['allowed_start'] ?? '');
    $allowed_end = trim($_POST['allowed_end'] ?? '');

    // Validation
    if (empty($device_id) || empty($max_screen_time) || empty($allowed_start) || empty($allowed_end)) {
        $error = "All fields are required.";
    } elseif ($max_screen_time < 0) {
        $error = "Max screen time must be positive.";
    } else {
        // Verify device belongs to this parent
        $stmt = $conn->prepare("SELECT device_id FROM devices WHERE device_id = ? AND parent_id = ?");
        $stmt->bind_param("ii", $device_id, $parent_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $error = "Invalid device selected.";
        } else {
            // Insert rule
            $stmt = $conn->prepare("INSERT INTO rules (device_id, max_screen_time, allowed_start, allowed_end, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param("isss", $device_id, $max_screen_time, $allowed_start, $allowed_end);

            if ($stmt->execute()) {
                $success = "Rule added successfully!";
                header("refresh:2;url=rules_chart.php");
                $_POST = array();
            } else {
                $error = "Error adding rule. Please try again.";
            }
        }
    }
}

// Fetch devices for this parent
$stmt = $conn->prepare("SELECT device_id, device_name FROM devices WHERE parent_id = ? ORDER BY device_name");
$stmt->bind_param("i", $parent_id);
$stmt->execute();
$result = $stmt->get_result();
$devices = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Rule</title>
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

        .empty-devices {
            background: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .empty-devices a {
            color: #2d6bff;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>

<body>

    <div class="card">
        <a href="rules_chart.php" class="back-link">← Back to Rules</a>

        <h2>Add Rule</h2>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <p style="text-align: center; color: #388e3c; font-size: 13px; margin-top: 10px;">
                Redirecting in 2 seconds... <a href="rules_chart.php" style="color: #2d6bff;">Click here if not redirected</a>
            </p>
        <?php else: ?>

            <?php if (empty($devices)): ?>
                <div class="empty-devices">
                    ⚠️ No devices found. Please <a href="add_device.php">add a device</a> first before adding rules.
                </div>
            <?php else: ?>

                <form method="POST">
                    <label>Select Device *</label>
                    <select name="device_id" required>
                        <option value="">-- Select a Device --</option>
                        <?php foreach ($devices as $device): ?>
                            <option value="<?= $device['device_id'] ?>" <?= ($_POST['device_id'] ?? '') == $device['device_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($device['device_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label>Max Screen Time (hours) *</label>
                    <input type="number" name="max_screen_time" placeholder="e.g., 2" min="0" step="0.5" value="<?= htmlspecialchars($_POST['max_screen_time'] ?? '') ?>" required>

                    <label>Allowed Start Time *</label>
                    <input type="time" name="allowed_start" value="<?= htmlspecialchars($_POST['allowed_start'] ?? '') ?>" required>

                    <label>Allowed End Time *</label>
                    <input type="time" name="allowed_end" value="<?= htmlspecialchars($_POST['allowed_end'] ?? '') ?>" required>

                    <button type="submit">Add Rule</button>
                </form>

            <?php endif; ?>

        <?php endif; ?>

        <p class="note">
            Rules control device usage time and allowed hours for children.
        </p>
    </div>

</body>
</html>