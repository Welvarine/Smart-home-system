
<?php
session_start();
include 'php/db_connect.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if ($email) {
        $stmt = $conn->prepare("SELECT user_id, full_name FROM users WHERE email=? AND role='child'");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $child = $result->fetch_assoc();

        if ($child) {
            $_SESSION['child_id'] = $child['user_id'];
            $_SESSION['child_email'] = $email;
            $_SESSION['child_name'] = $child['full_name'];
            header("Location: child_dashboard.php");
            exit;
        } else {
            $error = "Email not recognized. Please contact your parent.";
        }
    } else {
        $error = "Please enter your email.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Child Login – Smart Home</title>
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

        .alert {
            padding: 12px 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 500;
            background: #ffe0e0;
            color: #d32f2f;
            border: 1px solid #ff9999;
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

        button, .back-btn {
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
            text-decoration: none;
            display: block;
            text-align: center;
            margin-bottom: 10px;
        }

        button:hover, .back-btn:hover {
            transform: translateY(-3px);
            box-shadow:
                0 12px 25px rgba(255, 59, 59, 0.45),
                0 0 0 3px rgba(255, 159, 28, 0.35);
        }

        .back-btn {
            background: #2d6bff;
        }

        .back-btn:hover {
            background: #1a4fc9;
            box-shadow: 0 12px 25px rgba(45, 107, 255, 0.3);
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
        <h2> Child Login</h2>

        <?php if ($error): ?>
            <div class="alert"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <label>Your Email</label>
            <input type="email" name="email" placeholder="Enter your email" required>

            <button type="submit">Login</button>
        </form>

        <a href="index.html" class="back-btn">← Back to Home</a>

        <p class="note">
            Ask your parent for your login email.
        </p>
    </div>
</body>
</html>