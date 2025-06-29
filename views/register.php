<?php
session_start();

require_once '../config/db.php';
require_once '../controllers/UserController.php';

$controller = new UserController($conn);
$error = '';

// Handle both JSON and form-based POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if the request is JSON
    if (strpos($_SERVER["CONTENT_TYPE"] ?? '', "application/json") !== false) {
        $input=json_decode(file_get_contents("php://input"), true);
        $username=trim($input['username'] ?? '');
        $email=trim($input['email'] ?? '');
        $password=trim($input['password'] ?? '');
        $confirm_password=trim($input['confirm_password'] ?? '');
        $isJson=true;
    } else {
        $username=trim($_POST['username'] ?? '');
        $email=trim($_POST['email'] ?? '');
        $password=trim($_POST['password'] ?? '');
        $confirm_password=trim($_POST['confirm_password'] ?? '');
        $isJson=false;
    }

    // Validation
    if (empty($username)||empty($email)||empty($password)||empty($confirm_password)){
        $error="All fields are required.";
    } 
    elseif($password!==$confirm_password){

        $error = "Passwords do not match.";
    } 
    elseif(strlen($password)<6){
        
        $error = "Password must be at least 6 characters long.";
    } 
    elseif(!filter_var($email,FILTER_VALIDATE_EMAIL)){
        $error = "Invalid email format.";
    } else {
        $result = $controller->register($username, $email, $password);
        if ($result === true) {
            $_SESSION['username'] = $username;
            $_SESSION['user_id'] = $controller->getUserId($username);
            if ($isJson) {
                echo json_encode(["status" => "success"]);
                exit();
            } else {
                header("Location: login.php");
                exit();
            }
        } else {
            $error = $result;
        }
    }

    // Return JSON error if it was a JSON request
    if ($isJson) {
        echo json_encode(["status" => "error", "message" => $error]);
        exit();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Recipe Management - Register</title>
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
        .login-link {
            margin-top: 10px;
            text-decoration: none;
            color: #333;
        }
        .login-link:hover {
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
    <form id="registerForm">
        <input type="text" name="username" placeholder="Username" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <input type="password" name="confirm_password" placeholder="Confirm Password" required>
        <button type="submit">Register</button>
    </form>
    <p id="responseMessage" class="error"></p>
    <a href="login.php" class="login-link">Already have an account? Login</a>
</div>

<script>
document.getElementById('registerForm').addEventListener('submit', async function (e) {
    e.preventDefault();

    const form = e.target;
    const username = form.username.value.trim();
    const email = form.email.value.trim();
    const password = form.password.value.trim();
    const confirm_password = form.confirm_password.value.trim();
    const errorElement = document.getElementById('responseMessage');
    errorElement.textContent = '';

    // JS Validation
    if (!username || !email || !password || !confirm_password) {
        errorElement.textContent = "All fields are required.";
        return;
    }
    if (password !== confirm_password) {
        errorElement.textContent = "Passwords do not match.";
        return;
    }
    if (password.length < 6) {
        errorElement.textContent = "Password must be at least 6 characters long.";
        return;
    }
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        errorElement.textContent = "Invalid email format.";
        return;
    }

    // Send JSON to PHP
    const response = await fetch('register.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ username, email, password, confirm_password })
    });

    const result = await response.json();

    if (result.status === 'success') {
        window.location.href = 'login.php';
    } else {
        errorElement.textContent = result.message;
    }
});
</script>
</body>
</html>
