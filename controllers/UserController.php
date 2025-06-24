<?php
require_once '../models/User.php';

class UserController {
    private $conn;
    private $user;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->user = new User($conn);
    }

    public function register($username, $email, $password) {
        if ($this->user->usernameExists($username)) {
            return "Username already taken.";
        }
        if ($this->user->emailExists($email)) {
            return "Email already registered.";
        }
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $role = 'user';
        if ($this->user->create($username, $email, $hashed_password, $role)) {
            return true;
        }
        return "Registration failed.";
    }

    public function login($username, $password) {
        $user = $this->user->getUserByUsername($username);
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['username'] = $username;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            return true;
        }
        return "Invalid username or password.";
    }

    public function getUserId($username) {
        $user = $this->user->getUserByUsername($username);
        return $user ? $user['id'] : null;
    }
}
?>