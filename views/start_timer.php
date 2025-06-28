<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['timer_id']) && isset($_SESSION['user_id'])) {
    $timer_id = (int)$_POST['timer_id'];
    $user_id = (int)$_SESSION['user_id'];
    
    // Verify timer exists and belongs to user
    $query = "SELECT id FROM timers WHERE id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $timer_id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result->num_rows > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid timer']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}
?>