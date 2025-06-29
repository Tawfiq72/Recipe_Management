<?php
require_once '../config/db.php';
require_once '../controllers/RecipeController.php';

header('Content-Type: application/json');

$controller = new RecipeController($conn);
$data = json_decode(file_get_contents("php://input"), true);

$cuisine_id = !empty($data['cuisine_id']) ? (int)$data['cuisine_id'] : null;
$meal_type_id = !empty($data['meal_type_id']) ? (int)$data['meal_type_id'] : null;
$search_term = !empty($data['search_term']) ? trim($data['search_term']) : null;

$recipes = $controller->getFilteredRecipes($cuisine_id, $meal_type_id, $search_term);

echo json_encode($recipes);
