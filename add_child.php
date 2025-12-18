
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validation
    if (empty($full_name) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "Email already exists. Please use a different email.";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            // Insert child into database
            $stmt = $conn->prepare("
                INSERT INTO users (full_name, email, password, role, parent_id)
                VALUES (?, ?, ?, 'child', ?)
            ");
            $stmt->bind_param("sssi", $full_name, $email, $hashed_password, $parent_id);

            if ($stmt->execute()) {
                $success = "Child account created successfully! Email: " . htmlspecialchars($email);
                // Redirect to children list after 2 seconds
                header("refresh:2;url=children.php");
                // Clear form
                $_POST = array();
            } else {
                $error = "Error creating child account. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Child Account</title>

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
            width: 420px;
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

        .note {
            text-align: center;
            margin-top: 16px;
            font-size: 13px;
            color: #5f6b8a;
        }
    </style>
</head>

<body>

    <div class="card">
        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>

        <h2>Add Child Account</h2>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <p style="text-align: center; color: #388e3c; font-size: 13px; margin-top: 10px;">
                Redirecting to children list in 2 seconds... <a href="children.php" style="color: #2d6bff;">Click here if not redirected</a>
            </p>
        <?php endif; ?>

        <form method="POST">
            <label>Child Full Name</label>
            <input type="text" name="full_name" placeholder="Enter child's name" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required>

            <label>Child Email</label>
            <input type="email" name="email" placeholder="Enter child's email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>

            <label>Temporary Password</label>
            <input type="password" name="password" placeholder="Temporary password (minimum 6 characters)" required>

            <button type="submit">Create Child Account</button>
        </form>

        <p class="note">
            Child accounts are created with limited permissions.
        </p>
    </div>

</body>
</html>