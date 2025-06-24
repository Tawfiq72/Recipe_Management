<?php
session_start();

// Include database connection and controller
require_once '../config/db.php';
require_once '../controllers/UserController.php';

// Initialize controller
$controller = new UserController($conn);
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $error = "All fields are required.";
    } else {
        $result = $controller->login($username, $password);
        if ($result === true) {
            header("Location: home.php");
            exit();
        } else {
            $error = $result;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recipe Management - Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .login-container {
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
            width: 300px;
            text-align: center;
        }
        .login-container h2 {
            margin: 0 0 15px;
            font-size: 24px;
        }
        .login-container form {
            display: flex;
            flex-direction: column;
        }
        .login-container input {
            padding: 8px;
            margin-bottom: 10px;
            font-size: 14px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .login-container button {
            padding: 8px;
            background-color: #333;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
        }
        .login-container button:hover {
            background-color: #555;
        }
        .error {
            color: red;
            margin-bottom: 10px;
        }
        .register-link {
            margin-top: 10px;
            text-decoration: none;
            color: #333;
        }
        .register-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Login</h2>
        <?php if ($error): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>
        <form method="post" action="">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
        <a href="register.php" class="register-link">Need an account? Register</a>
    </div>
</body>
</html>