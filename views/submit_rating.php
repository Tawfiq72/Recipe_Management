<?php
session_start();
require_once '../config/db.php';
require_once '../controllers/RecipeController.php';

$controller = new RecipeController($conn);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['recipe_id']) && isset($_POST['rating']) && isset($_SESSION['user_id'])) {
    $recipe_id = (int)$_POST['recipe_id'];
    $user_id = (int)$_SESSION['user_id'];
    $rating = (float)$_POST['rating'];
    $review = isset($_POST['review']) ? trim($_POST['review']) : null;
    
    $result = $controller->submitRating($recipe_id, $user_id, $rating, $review);
    
    if ($result === true) {
        // Fetch updated ratings and average
        $ratings = $controller->getRatings($recipe_id);
        $average_rating = $controller->getAverageRating($recipe_id);
        echo json_encode([
            'success' => true,
            'average_rating' => $average_rating ? number_format($average_rating, 1) : 'N/A',
            'ratings' => $ratings
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => $result]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request or user not logged in']);
}
?>