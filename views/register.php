<?php
session_start();

require_once '../config/db.php';
require_once '../controllers/UserController.php';

// Initialize controller
$controller = new UserController($conn);
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        $result = $controller->register($username, $email, $password);
        if ($result === true) {
            $_SESSION['username'] = $username;
            $_SESSION['user_id'] = $controller->getUserId($username);
            header("Location: home.php");
            exit();
        } else {
            $error = $result;
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Recipe Management - Register</title>
    <style>
        body{
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .login-container{
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
            width: 300px;
            text-align: center;
        }
        .login-container h2{
            margin: 0 0 15px;
            font-size: 24px;
        }
        .login-container form{
            display: flex;
            flex-direction: column;
        }
        .login-container input{
            padding: 8px;
            margin-bottom: 10px;
            font-size: 14px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .login-container button{
            padding: 8px;
            background-color: #333;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
        }
        .login-container button:hover{
            background-color: #555;
        }
        .error{
            color: red;
            margin-bottom: 10px;
        }
        .login-link{
            margin-top: 10px;
            text-decoration: none;
            color: #333;
        }
        .login-link:hover{
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Register</h2>
        <?php if ($error): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>
        <form method="post" action="">
            <input type="text" name="username" placeholder="Username" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="password" name="confirm_password" placeholder="Confirm Password" required>
            <button type="submit">Register</button>
        </form>
        <a href="login.php" class="login-link">Already have an account? Login</a>
    </div>
</body>
</html>