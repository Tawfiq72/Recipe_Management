<?php
session_start();

require_once '../config/db.php';
require_once '../controllers/RecipeController.php';

$controller = new RecipeController($conn);

// Fetch cuisines and meal types for dropdowns
$cuisines_query = "SELECT id, name FROM cuisines";
$cuisines_result = mysqli_query($conn, $cuisines_query);
$cuisines = mysqli_fetch_all($cuisines_result, MYSQLI_ASSOC);

$meal_types_query = "SELECT id, name FROM meal_types";
$meal_types_result = mysqli_query($conn, $meal_types_query);
$meal_types = mysqli_fetch_all($meal_types_result, MYSQLI_ASSOC);

// Fetch counts for admin statistics
$total_users_query="SELECT COUNT(*) as total_users FROM users";
$total_users_result=mysqli_query($conn,$total_users_query);
$total_users=mysqli_fetch_assoc($total_users_result)['total_users'];
 
$total_reviews_query="SELECT COUNT(*) as total_reviews FROM ratings";
$total_reviews_result=mysqli_query($conn, $total_reviews_query);
$total_reviews=mysqli_fetch_assoc($total_reviews_result)['total_reviews'];
 
$total_recipes_query="SELECT COUNT(*) as total_recipes FROM recipes";
$total_recipes_result=mysqli_query($conn, $total_recipes_query);
$total_recipes=mysqli_fetch_assoc($total_recipes_result)['total_recipes'];
 

// Handle filter submission
$cuisine_id = isset($_GET['cuisine_id']) ? (int)$_GET['cuisine_id'] : null;
$meal_type_id = isset($_GET['meal_type_id']) ? (int)$_GET['meal_type_id'] : null;
$search_term = isset($_GET['search_term']) ? $_GET['search_term'] : null;

if (isset($_GET['filter'])) {
    $recipes = $controller->getFilteredRecipes($cuisine_id, $meal_type_id, $search_term);
} else {
    $recipes = $controller->getRecipes();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Recipe Management - Home</title>
    <style>
        body{
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .header{
            background-color: #333;
            color: white;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1{
            margin: 0;
            font-size: 24px;
        }
        .login-section a,.login-section span{
            color: white;
            text-decoration: none;
            margin-left: 10px;
        }
        .login-section a:hover{
            text-decoration: underline;
        }
        .container{
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
        }


        .dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .dashboard-card {
            background-color: #fff;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
        }
        .dashboard-card.blue {
            background-color: #4a90e2;
            color: white;
        }
        .dashboard-card.red {
            background-color: #e74c3c;
            color: white;
        }
        .dashboard-card.green {
            background-color: #2ecc71;
            color: white;
        }
        .dashboard-card.black {
            background-color: #2c3e50;
            color: white;
        }
        .dashboard-card.gray {
            background-color: #95a5a6;
            color: white;
        }
        .dashboard-card h3 {
            margin: 0 0 10px;
            font-size: 18px;
        }
        .dashboard-card p {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
        }




        .filter-section{
            margin-bottom: 20px;
            background-color: white;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            
        }
        .filter-section label {
            margin-right: 5px;
            font-size: 16px;
            color: #333;
        }
        .filter-section select, .filter-section input[type="text"]{
            padding: 10px;
            font-size: 16px;
            border:1px solid #ddd;
            border-radius: 5px;
            
        }
        .filter-section select:focus, .filter-section input[type="text"]:focus{
            border-color: #666;
            box-shadow: 0 0 5px rgba(100, 100, 100, 0.3);
            outline: none;
        }
        .filter-section button{
            padding: 10px 20px;
            background-color: #333;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;

        }
        .filter-section button:hover{
            background-color: #555;
        }
        .recipe-grid{
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            
        }
        .recipe-card{
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            text-align: center;
        
        }
        .recipe-card:hover{
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transform: translateY(-5px);
        }
        .recipe-card img{
            max-width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        .recipe-card h3{
            margin: 10px 0 5px;
            font-size: 20px;
            color: #222;
        }
        .recipe-card p{
            margin: 5px 0;
            color: #555;
            font-size: 14px;
        }
        .admin-link{
            display: block;
            margin-top: 30px;
            text-align: center;
            text-decoration: none;
            color: #2c3e50;
            font-weight: bold;
           
        }
        .admin-link:hover{
        
            text-decoration: underline;
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
         <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <div class="dashboard">
                <div class="dashboard-card blue">
                    <h3>Total Regd. Users</h3>
                    <p><?php echo htmlspecialchars($total_users); ?></p>
                </div>
                <div class="dashboard-card red">
                    <h3>Total Listed Recipes</h3>
                    <p><?php echo htmlspecialchars($total_recipes); ?></p>
               </div>
                <div class="dashboard-card green">
                    <h3>Total Reviews</h3>
                    <p><?php echo htmlspecialchars($total_reviews); ?></p>
                </div>
            </div>
        <?php endif; ?>
        <div class="filter-section">
            <form method="get" action="">
                <label for="cuisine_id">Cuisine:</label>
                <select id="cuisine_id" name="cuisine_id">
                    <option value="">All Cuisines</option>
                    <?php foreach ($cuisines as $cuisine): ?>
                        <option value="<?php echo $cuisine['id']; ?>" <?php echo $cuisine_id == $cuisine['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cuisine['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <label for="meal_type_id">Meal Type:</label>
                <select id="meal_type_id" name="meal_type_id">
                    <option value="">All Meal Types</option>
                    <?php foreach ($meal_types as $meal_type): ?>
                        <option value="<?php echo $meal_type['id']; ?>" <?php echo $meal_type_id == $meal_type['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($meal_type['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <label for="search_term">Search:</label>
                <input type="text" id="search_term" name="search_term" placeholder="Search by recipe title" value="<?php echo isset($_GET['search_term']) ? htmlspecialchars($_GET['search_term']) : ''; ?>">
                <input type="hidden" name="filter" value="1">
                <button type="submit">Apply Filters</button>
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