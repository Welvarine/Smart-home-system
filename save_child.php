
<?php
session_start();
include 'db_connect.php';

$full_name = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$role = 'child';

function showError($message) {
    echo "
    <!DOCTYPE html>
    <html>
    <head>
        <title>Error</title>
        <style>
            body {
                font-family: Arial, Helvetica, sans-serif;
                background: #f4f6f8;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
            }
            .box {
                background: white;
                padding: 30px;
                width: 420px;
                border-radius: 12px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.15);
                text-align: center;
            }
            .error {
                background: #f8d7da;
                color: #721c24;
                padding: 15px;
                border-radius: 8px;
                margin-bottom: 20px;
                font-weight: bold;
            }
            .btn {
                display: inline-block;
                margin-top: 10px;
                padding: 12px 20px;
                background: #2c3e50;
                color: white;
                text-decoration: none;
                border-radius: 6px;
                font-weight: bold;
            }
            .btn:hover {
                background: #1a252f;
            }
        </style>
    </head>
    <body>
        <div class='box'>
            <div class='error'>$message</div>
            <a href='../dashboard.php' class='btn'>← Back to Dashboard</a><br><br>
            <a href='../children.php' class='btn' style='background:#f4b942;color:#2c3e50;'>← Back to Children</a>
        </div>
    </body>
    </html>
    ";
    exit;
}

if (empty($full_name) || empty($email) || empty($password)) {
    showError("All fields are required.");
}

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare(
    "INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, ?)"
);
$stmt->bind_param("ssss", $full_name, $email, $hashedPassword, $role);

try {
    $stmt->execute();
    header("Location: ../children.php?msg=Child+added+successfully");
    exit;
} catch (mysqli_sql_exception $e) {
    if ($e->getCode() === 1062) {
        showError("This email is already registered. Please use another email.");
    } else {
        showError("Something went wrong. Please try again.");
    }
}
