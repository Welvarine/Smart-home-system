
<?php
// register.php
include 'db_connect.php';

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if ($fullname && $email && $password) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, 'parent')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $fullname, $email, $hashed_password);

        if ($stmt->execute()) {
            $success = "Registration successful! <a href='login.php'>Go to login</a>";
        } else {
            $error = "Error: Could not register. Maybe email already exists.";
        }
    } else {
        $error = "Please fill in all fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Parent Registration</title>

<style>
body {
    font-family: Arial, sans-serif;
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    /* Background image */
    background-image: url("../assets/images/space-bg.jpg"); /* <-- replace with your image path */
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    /* Optional overlay to darken image */
    background-color: rgba(16, 42, 67, 0.5);
    background-blend-mode: overlay;
}
.container {
    background: #fff;
    padding: 40px;
    border-radius: 12px;
    width: 350px;
    box-shadow: 0 10px 20px rgba(0,0,0,0.2);
    text-align: center;
}
h2 {
    color: #f6c453;
    margin-bottom: 20px;
}
input {
    width: 100%;
    padding: 12px;
    margin-bottom: 15px;
    border-radius: 6px;
    border: 1px solid #ccc;
}
button {
    width: 100%;
    padding: 12px;
    background: #f6c453;
    color: #102a43;
    border: none;
    border-radius: 6px;
    font-weight: bold;
    cursor: pointer;
}
button:hover {
    background: #ffd97a;
}
.error { color: red; margin-bottom: 15px; }
.success { color: green; margin-bottom: 15px; }
.back-btn {
    display: block;
    margin-top: 15px;
    color: #102a43;
    text-decoration: none;
}
</style>
</head>
<body>

<div class="container">
    <h2>Parent Registration</h2>

    <?php if($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if($success): ?>
        <div class="success"><?= $success ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="text" name="fullname" placeholder="Full Name" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Register</button>
    </form>

    <a href="../index.html" class="back-btn">← Back to Home</a>
</div>

</body>
</html>
