
<?php
session_start();
include 'php/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') {
    header("Location: index.php");
    exit;
}

$child_email = $_GET['child_email'] ?? '';
$child = null;
$devices = [];
$device_usage = [];

if ($child_email != '') {
    // Fetch child details
    $stmt = $conn->prepare("SELECT user_id, full_name, email FROM users WHERE role='child' AND email=?");
    $stmt->bind_param("s", $child_email);
    $stmt->execute();
    $child_result = $stmt->get_result();
    $child = $child_result->fetch_assoc();

    if ($child) {
        $child_id = $child['user_id'];

        // Fetch devices assigned to child and rules
        $stmt = $conn->prepare("
            SELECT d.device_id, d.device_name, d.device_type, d.status,
                   r.max_screen_time, r.allowed_start, r.allowed_end
            FROM devices d
            LEFT JOIN rules r ON d.device_id = r.device_id
            WHERE d.assigned_to = ?
        ");
        $stmt->bind_param("i", $child_id);
        $stmt->execute();
        $device_result = $stmt->get_result();

        while ($row = $device_result->fetch_assoc()) {
            $devices[] = $row;

            // Fetch usage for chart
            $stmt2 = $conn->prepare("SELECT hours_used, usage_date FROM device_usage WHERE device_id=? AND child_id=?");
            $stmt2->bind_param("ii", $row['device_id'], $child_id);
            $stmt2->execute();
            $res2 = $stmt2->get_result();

            $hours = [];
            $dates = [];
            while ($u = $res2->fetch_assoc()) {
                $hours[] = $u['hours_used'];
                $dates[] = $u['usage_date'];
            }

            $device_usage[$row['device_name']] = [
                'hours' => $hours,
                'dates' => $dates,
                'max_screen_time' => $row['max_screen_time'],
                'allowed_start' => $row['allowed_start'],
                'allowed_end' => $row['allowed_end']
            ];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Child Device Report</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; font-family:Arial, Helvetica, sans-serif; }
body { min-height:100vh; background:#e6f2ff; display:flex; justify-content:center; align-items:flex-start; padding:40px; }
.container { background:#fff; width:850px; padding:35px; border-radius:18px; box-shadow:0 15px 35px rgba(0,0,0,0.15); }
h2,h3,h4 { text-align:center; margin-bottom:20px; color:#2c3e50; }
table { width:100%; border-collapse:collapse; margin-bottom:25px; }
th, td { border:1px solid #f4b942; padding:10px; text-align:left; }
th { background:#f4b942; color:#2c3e50; }
td { background:#fff5e6; color:#2c3e50; }
input { width:100%; padding:10px; margin-bottom:10px; border-radius:6px; border:1px solid #ccc; }
button { padding:10px 15px; background:#f4b942; border:none; border-radius:6px; cursor:pointer; font-weight:bold; }
button:hover { background:#e0a82e; }
.back-btn { display:inline-block; margin-bottom:20px; background:#3498db; color:#fff; text-decoration:none; padding:10px 15px; border-radius:6px; }
.back-btn:hover { background:#2980b9; }
.chart-container { margin-top:30px; }
</style>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<div class="container">

<a href="dashboard.php" class="back-btn">← Back to Dashboard</a>

<h2>Search Child Device Report</h2>

<form method="GET">
    <input type="email" name="child_email" placeholder="Enter child email" value="<?= htmlspecialchars($child_email); ?>" required>
    <button type="submit">Search</button>
</form>

<?php if($child): ?>
    <h3>Child Details</h3>
    <table>
        <tr><th>Name</th><td><?= htmlspecialchars($child['full_name']); ?></td></tr>
        <tr><th>Email</th><td><?= htmlspecialchars($child['email']); ?></td></tr>
    </table>

    <h3>Assigned Devices & Rules</h3>
    <table>
        <tr>
            <th>Device Name</th>
            <th>Type</th>
            <th>Status</th>
            <th>Max Screen Time (hrs)</th>
            <th>Allowed Start</th>
            <th>Allowed End</th>
        </tr>
        <?php foreach($devices as $d): ?>
            <tr>
                <td><?= htmlspecialchars($d['device_name']); ?></td>
                <td><?= htmlspecialchars($d['device_type']); ?></td>
                <td><?= htmlspecialchars($d['status']); ?></td>
                <td><?= htmlspecialchars($d['max_screen_time']); ?></td>
                <td><?= htmlspecialchars($d['allowed_start']); ?></td>
                <td><?= htmlspecialchars($d['allowed_end']); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h3>Device Usage Charts</h3>
    <?php foreach($device_usage as $dev_name => $data): ?>
        <h4><?= htmlspecialchars($dev_name); ?></h4>
        <canvas id="chart_<?= md5($dev_name); ?>" class="chart-container" width="800" height="300"></canvas>
        <script>
        const ctx_<?= md5($dev_name); ?> = document.getElementById('chart_<?= md5($dev_name); ?>').getContext('2d');
        new Chart(ctx_<?= md5($dev_name); ?>, {
            type: 'bar',
            data: {
                labels: <?= json_encode($data['dates']); ?>,
                datasets: [
                    {
                        label: 'Hours Used',
                        data: <?= json_encode($data['hours']); ?>,
                        backgroundColor: '#2e5aac'
                    },
                    {
                        label: 'Max Screen Time',
                        data: Array(<?= count($data['hours']); ?>).fill(<?= $data['max_screen_time'] ?? 0; ?>),
                        type: 'line',
                        borderColor: '#f4b942',
                        borderWidth: 2,
                        fill: false,
                        tension: 0.1
                    }
                ]
            },
            options: {
                plugins: { tooltip: { mode: 'index', intersect: false } },
                scales: {
                    y: { beginAtZero: true, title: { display:true, text:'Hours' } },
                    x: { title: { display:true, text:'Date' } }
                }
            }
        });
        </script>
    <?php endforeach; ?>

<?php elseif($child_email): ?>
    <p style="color:red; text-align:center; margin-top:20px;">No child found with this email.</p>
<?php endif; ?>

</div>

</body>
</html>
