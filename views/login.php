<?php
session_start();

require_once '../config/db.php';
require_once '../controllers/UserController.php';

$controller = new UserController($conn);
$error = '';

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Support JSON (AJAX) or normal form POST
    if (strpos($_SERVER["CONTENT_TYPE"] ?? '', "application/json") !== false) {
        $data = json_decode(file_get_contents("php://input"), true);
        $username = trim($data['username'] ?? '');
        $password = trim($data['password'] ?? '');
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
    }

    if (empty($username) || empty($password)) {
        $error = "All fields are required.";
        if (isset($data)) {
            echo json_encode(["status" => "error", "message" => $error]);
            exit();
        }
    } else {
        $result = $controller->login($username, $password);
        if ($result === true) {
            if (isset($data)) {
                echo json_encode(["status" => "success"]);
                exit();
            } else {
                header("Location: home.php");
                exit();
            }
        } else {
            $error = $result;
            if (isset($data)) {
                echo json_encode(["status" => "error", "message" => $error]);
                exit();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
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
        <form method="post" action="" id="loginForm">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
        <a href="register.php" class="register-link">Need an account? Register</a>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', async function(event) {
            const form = this;
            const username = form.username.value.trim();
            const password = form.password.value.trim();
            const existingError = document.querySelector('.error');

            if (existingError) {
                existingError.textContent = '';
            }

            if (!username || !password) {
                event.preventDefault();
                if (existingError) {
                    existingError.textContent = 'All fields are required.';
                } else {
                    const error = document.createElement('p');
                    error.className = 'error';
                    error.textContent = 'All fields are required.';
                    form.insertAdjacentElement('beforebegin', error);
                }
                return;
            }

            // Optional AJAX JSON submission
            // Comment out below to fall back to normal form
            event.preventDefault(); // Prevent full form submission

            const response = await fetch('login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ username, password })
            });

            const result = await response.json();

            if (result.status === 'success') {
                window.location.href = 'home.php';
            } else {
                if (existingError) {
                    existingError.textContent = result.message;
                } else {
                    const error = document.createElement('p');
                    error.className = 'error';
                    error.textContent = result.message;
                    form.insertAdjacentElement('beforebegin', error);
                }
            }
        });
    </script>
</body>
</html>
