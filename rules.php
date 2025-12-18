
<?php
session_start();
include 'php/db_connect.php';

// Fetch all rules with associated device names
$result = $conn->query("
    SELECT r.rule_id, r.device_id, r.max_screen_time, r.allowed_start, r.allowed_end, r.created_at,
           d.device_name
    FROM rules r
    LEFT JOIN devices d ON r.device_id = d.device_id
");

// Fetch all devices for the add rule dropdown
$devicesRes = $conn->query("SELECT device_id, device_name FROM devices");
$devices = [];
while ($d = $devicesRes->fetch_assoc()) { $devices[] = $d; }

// Get messages from URL query
$msg = $_GET['msg'] ?? '';
$error = $_GET['error'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Device Rules Management</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; font-family:Arial, Helvetica, sans-serif; }
body { min-height:100vh; background:#eef6f8; display:flex; justify-content:center; align-items:flex-start; padding:40px; }
.container { background:#fff; width:700px; padding:35px; border-radius:18px; box-shadow:0 15px 35px rgba(0,0,0,0.15); }
h2 { text-align:center; margin-bottom:25px; color:#2c3e50; }
input, select { width:100%; padding:12px; margin-bottom:15px; border:2px solid #dcdde1; border-radius:8px; font-size:15px; }
input:focus, select:focus { outline:none; border-color:#f4b942; box-shadow:0 0 5px rgba(244,185,66,0.5); }
button { padding:9px 14px; margin:2px; background:#f4b942; border:none; border-radius:6px; font-size:14px; font-weight:bold; cursor:pointer; transition:0.3s; }
button:hover { background:#e0a82e; }
table { width:100%; border-collapse:collapse; margin-top:25px; }
th, td { border:1px solid #ccc; padding:10px; text-align:left; }
th { background:#f7f7f7; }
.alert { padding:10px; margin-bottom:15px; border-radius:6px; }
.success { background:#2ecc71; color:#fff; }
.error { background:#e74c3c; color:#fff; }
.back-btn { display:inline-block; margin-bottom:20px; background:#3498db; color:#fff; text-decoration:none; padding:10px 15px; border-radius:6px; }
.back-btn:hover { background:#2980b9; }
</style>
</head>
<body>
<div class="container">

<a href="dashboard.php" class="back-btn">← Back to Dashboard</a>

<h2>Device Rules Management</h2>

<?php if($msg): ?>
    <div class="alert success"><?= htmlspecialchars($msg); ?></div>
<?php endif; ?>
<?php if($error): ?>
    <div class="alert error"><?= htmlspecialchars($error); ?></div>
<?php endif; ?>

<!-- ADD RULE FORM -->
<form action="php/add_rule.php" method="POST">
    <select name="device_id" required>
        <option value="">Select Device</option>
        <?php foreach($devices as $d): ?>
            <option value="<?= $d['device_id']; ?>"><?= htmlspecialchars($d['device_name']); ?></option>
        <?php endforeach; ?>
    </select>
    <input type="number" name="max_screen_time" placeholder="Max Screen Time (hours)" min="0" required>
    <input type="time" name="allowed_start" required>
    <input type="time" name="allowed_end" required>
    <button type="submit">Add Rule</button>
</form>

<!-- RULES TABLE -->
<table>
    <tr>
        <th>Device</th>
        <th>Max Hours</th>
        <th>Start</th>
        <th>End</th>
        <th>Created At</th>
        <th>Actions</th>
    </tr>
    <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['device_name']); ?></td>
            <td><?= htmlspecialchars($row['max_screen_time']); ?></td>
            <td><?= htmlspecialchars($row['allowed_start']); ?></td>
            <td><?= htmlspecialchars($row['allowed_end']); ?></td>
            <td><?= htmlspecialchars($row['created_at']); ?></td>
            <td>
                <form style="display:inline;" action="php/edit_rule.php" method="POST">
                    <input type="hidden" name="rule_id" value="<?= $row['rule_id']; ?>">
                    <button type="submit">Edit</button>
                </form>
                <form style="display:inline;" action="php/delete_rule.php" method="POST" onsubmit="return confirm('Delete this rule?');">
                    <input type="hidden" name="rule_id" value="<?= $row['rule_id']; ?>">
                    <button type="submit" style="background:#e74c3c;color:#fff;">Delete</button>
                </form>
            </td>
        </tr>
    <?php endwhile; ?>
</table>

</div>
</body>
</html>
