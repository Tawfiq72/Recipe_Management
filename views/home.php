<?php
session_start();

// Include database connection and controller
require_once '../config/db.php';
require_once '../controllers/RecipeController.php';

// Initialize controller
$controller = new RecipeController($conn);

// Fetch cuisines and meal types for dropdowns
$cuisines_query = "SELECT id, name FROM cuisines";
$cuisines_result = mysqli_query($conn, $cuisines_query);
$cuisines = mysqli_fetch_all($cuisines_result, MYSQLI_ASSOC);

$meal_types_query = "SELECT id, name FROM meal_types";
$meal_types_result = mysqli_query($conn, $meal_types_query);
$meal_types = mysqli_fetch_all($meal_types_result, MYSQLI_ASSOC);

// Handle filter submission
$cuisine_id = isset($_GET['cuisine_id']) ? (int)$_GET['cuisine_id'] : null;
$meal_type_id = isset($_GET['meal_type_id']) ? (int)$_GET['meal_type_id'] : null;

if (isset($_GET['filter'])) {
    $recipes = $controller->getFilteredRecipes($cuisine_id, $meal_type_id);
} else {
    $recipes = $controller->getRecipes();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recipe Management - Home</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .header {
            background-color: #333;
            color: white;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .login-section a, .login-section span {
            color: white;
            text-decoration: none;
            margin-left: 10px;
        }
        .login-section a:hover {
            text-decoration: underline;
        }
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
        }
        
    </style>
</head>
<body>
    <div class="header">
        <h1>Recipe Management</h1>
        <div class="login-section">
            <?php if (isset($_SESSION['username'])): ?>
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                <a href="login.php?action=logout">Logout</a>
            <?php else: ?>
                <a href="login.php">Login</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="container">
        <div class="filter-section">
            <form method="get" action="">
                <select name="cuisine_id" onchange="this.form.submit()">
                    <option value="">All Cuisines</option>
                    <?php foreach ($cuisines as $cuisine): ?>
                        <option value="<?php echo $cuisine['id']; ?>" <?php echo $cuisine_id == $cuisine['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cuisine['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="meal_type_id" onchange="this.form.submit()">
                    <option value="">All Meal Types</option>
                    <?php foreach ($meal_types as $meal_type): ?>
                        <option value="<?php echo $meal_type['id']; ?>" <?php echo $meal_type_id == $meal_type['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($meal_type['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" name="filter" value="1">
            </form>
        </div>
        <div class="recipe-grid">
            <?php foreach ($recipes as $recipe): ?>
                <div class="recipe-card">
                    <a href="recipe_detail.php?id=<?php echo $recipe['id']; ?>">
                        <img src="<?php echo htmlspecialchars($recipe['image']); ?>" alt="<?php echo htmlspecialchars($recipe['title']); ?>">
                        <h3><?php echo htmlspecialchars($recipe['title']); ?></h3>
                        <p><?php echo htmlspecialchars($recipe['cuisine']); ?> - <?php echo htmlspecialchars($recipe['meal_type']); ?></p>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <a href="admin_add_recipe.php" class="admin-link">Add New Recipe</a>
        <?php endif; ?>
    </div>
</body>
</html>