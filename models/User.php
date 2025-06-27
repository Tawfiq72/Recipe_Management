<?php
class User {
    private $conn;

    public function __construct($conn){
        $this->conn = $conn;
    }

    public function usernameExists($username){
        $query = "SELECT id FROM users WHERE username = ?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        return mysqli_stmt_num_rows($stmt) > 0;
    }

    public function emailExists($email){
        $query = "SELECT id FROM users WHERE email = ?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        return mysqli_stmt_num_rows($stmt) > 0;
    }

    public function create($username, $email, $password, $role){
        $query = "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "ssss", $username, $email, $password, $role);
        return mysqli_stmt_execute($stmt);
    }

    public function getUserByUsername($username){
        $query = "SELECT id, username, password, role FROM users WHERE username = ?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $id, $username, $password, $role);
        if (mysqli_stmt_fetch($stmt)) {
            return ['id' => $id, 'username' => $username, 'password' => $password, 'role' => $role];
        }
        return null;
    }

    public function getIdByUsername($username){
        $user = $this->getUserByUsername($username);
        return $user ? $user['id'] : null;
    }
}
?>