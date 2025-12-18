
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

// Fetch child data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $user_id = intval($_POST['user_id']);

    $stmt = $conn->prepare("SELECT full_name, email FROM users WHERE user_id = ? AND role='child' AND parent_id = ?");
    $stmt->bind_param("ii", $user_id, $parent_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $child = $result->fetch_assoc();

    if (!$child) {
        header("Location: children.php?error=Child+not+found");
        exit;
    }
}

// Update child data
if (isset($_POST['update_child'])) {
    $user_id = intval($_POST['user_id']);
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if (empty($full_name) || empty($email)) {
        $error = "All fields are required.";
    } else {
        // Verify child belongs to parent
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE user_id = ? AND parent_id = ?");
        $stmt->bind_param("ii", $user_id, $parent_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            header("Location: children.php?error=Unauthorized");
            exit;
        }

        $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ? WHERE user_id = ? AND parent_id = ?");
        $stmt->bind_param("ssii", $full_name, $email, $user_id, $parent_id);

        if ($stmt->execute()) {
            $success = "Child updated successfully!";
            header("refresh:2;url=children.php");
        } else {
            if ($stmt->errno === 1062) {
                $error = "Email already exists. Please use a different email.";
            } else {
                $error = "Update failed. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Child</title>
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

        input {
            width: 100%;
            padding: 13px 15px;
            margin-bottom: 20px;
            border-radius: 12px;
            border: 1px solid #d6dbea;
            background: #ffffff;
            font-size: 15px;
            transition: 0.3s;
        }

        input:focus {
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
    </style>
</head>

<body>

    <div class="card">
        <a href="children.php" class="back-link">← Back to Children</a>

        <h2>Edit Child</h2>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <p style="text-align: center; color: #388e3c; font-size: 13px; margin-top: 10px;">
                Redirecting in 2 seconds... <a href="children.php" style="color: #2d6bff;">Click here if not redirected</a>
            </p>
        <?php else: ?>

            <form method="POST">
                <input type="hidden" name="user_id" value="<?= $user_id ?? '' ?>">

                <label>Full Name *</label>
                <input type="text" name="full_name" placeholder="Child's full name" value="<?= htmlspecialchars($child['full_name'] ?? '') ?>" required>

                <label>Email *</label>
                <input type="email" name="email" placeholder="Child's email" value="<?= htmlspecialchars($child['email'] ?? '') ?>" required>

                <button type="submit" name="update_child">Update Child</button>
            </form>

        <?php endif; ?>

    </div>

</body>
</html>