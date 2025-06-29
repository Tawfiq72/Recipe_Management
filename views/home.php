<?php
session_start();
require_once '../config/db.php';
require_once '../controllers/RecipeController.php';

$controller = new RecipeController($conn);

// Load cuisines and meal types for dropdowns
$cuisines_result=mysqli_query($conn,"SELECT id,name FROM cuisines");
$cuisines=mysqli_fetch_all($cuisines_result,MYSQLI_ASSOC);

$meal_types_result=mysqli_query($conn,"SELECT id, name FROM meal_types");
$meal_types=mysqli_fetch_all($meal_types_result,MYSQLI_ASSOC);

// Admin stats
$total_users=mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total_users FROM users"))['total_users'];
$total_reviews=mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total_reviews FROM ratings"))['total_reviews'];
$total_recipes=mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total_recipes FROM recipes"))['total_recipes'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Recipe Management - Home</title>
    <style>
        /* Your existing CSS styles remain unchanged */
        body{ 
            font-family: Arial, sans-serif;
             margin: 0; 
             background-color: #f4f4f4; }

        .header {
             background: #333;
              color: #fff; 
              padding: 10px 20px; 
              display: flex; 
              justify-content: 
                space-between; 
                align-items: center; }
        .container {
             max-width: 1200px; 
             margin: 20px auto; 
             padding: 0 20px; }
        .dashboard { 
            display: grid;
             grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
             gap: 20px; 
             margin-bottom: 20px; }
        .dashboard-card { background: #2c3e50;
             color: white; 
             border-radius: 10px; 
             padding: 20px; 
             text-align: center;
              }
        .dashboard-card:hover { 
            transform: translateY(-5px); 
        }
        .filter-section { 
            background: white;
             padding: 20px; 
             border-radius: 5px;
              margin-bottom: 20px; 
        }
        .filter-section label { 
            margin-right: 10px; 
        }
        .filter-section select, .filter-section input { 
            padding: 10px;
             border-radius: 5px;
              margin-right: 10px; 
            }
        .filter-section button { 
            padding: 10px 20px;
             border: none; 
             background: #333;
              color: white; 
              border-radius: 5px;
               cursor: pointer; }
        .recipe-grid { display: grid;
             grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
              gap: 20px; 
            }
        .recipe-card { background: #fff; 
            border: 1px solid #ddd; 
            border-radius: 5px; 
            padding: 15px;
             text-align: center;
              }
        .recipe-card:hover { box-shadow: 0 4px 15px rgba(0,0,0,0.1); 
            transform: translateY(-5px); 
        }
        .recipe-card img { max-width: 100%; 
            height: 200px;
             object-fit: cover;
              border-radius: 5px; }
        .error-msg { color: red; 
            font-size: 14px; 
            margin-bottom: 10px; 
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Recipe Management</h1>
        <div>
            <?php if (isset($_SESSION['username'])): ?>
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="login.php?action=logout" style="color:white; margin-left:10px;">Logout</a>
            <?php else: ?>
                <a href="login.php" style="color:white;">Login</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="container">
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] ==='admin'):?>
            <div class="dashboard">
                <div class="dashboard-card">Total Users<br><strong><?= $total_users ?></strong></div>
                <div class="dashboard-card">Total Recipes<br><strong><?= $total_recipes ?></strong></div>
                <div class="dashboard-card">Total Reviews<br><strong><?= $total_reviews ?></strong></div>
            </div>

            <div style="text-align:center; margin-bottom: 30px;">
                <a href="admin_add_recipe.php" style="
                    display: inline-block;
                    background-color:rgb(97, 4, 4);
                    color: white;
                    padding: 12px 24px;
                    border-radius: 8px;
                    text-decoration: none;
                    font-size: 16px;
                    font-weight: bold;
                    box-shadow: 0 4px 6px rgba(0,0,0,0.2);
                   
                " onmouseover="this.style.backgroundColor='rgba(93, 5, 5, 0.2)'" onmouseout="this.style.backgroundColor='rgb(24, 3, 20)'">
                    ➕ Manage Recipes
                </a>
            </div>
        <?php endif; ?>


        <div class="filter-section">
            <form id="filterForm">
                <label for="cuisine_id">Cuisine:</label>
                <select name="cuisine_id" id="cuisine_id">
                    <option value="">All</option>
                    <?php foreach ($cuisines as $cuisine): ?>
                        <option value="<?= $cuisine['id'] ?>"><?= htmlspecialchars($cuisine['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <label for="meal_type_id">Meal Type:</label>
                <select name="meal_type_id" id="meal_type_id">
                    <option value="">All</option>
                    <?php foreach ($meal_types as $meal): ?>
                        <option value="<?= $meal['id'] ?>"><?= htmlspecialchars($meal['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <label for="search_term">Search:</label>
                <input type="text" name="search_term" id="search_term" placeholder="e.g. Chicken Soup">
                <button type="submit">Apply Filters</button>
                <p class="error-msg" id="formError"></p>
            </form>
        </div>

        <div class="recipe-grid" id="recipeGrid">
            <!-- Recipes will be loaded here -->
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded',() =>{
            const form=document.getElementById('filterForm');
            const recipeGrid=document.getElementById('recipeGrid');
            const errorMsg=document.getElementById('formError');

            const loadRecipes=async (filters={}) =>{
                const response=await fetch('get_recipes.php', {
                    method:'POST',
                    headers:{'Content-Type':'application/json'},
                    body:JSON.stringify(filters)
                });

                const data=await response.json();
                recipeGrid.innerHTML='';

                if (data.length===0){
                    recipeGrid.innerHTML='<p>No recipes found.</p>';
                    return;
                }

                data.forEach(recipe =>{
                    recipeGrid.innerHTML +=`
                        <div class="recipe-card">
                            <a href="recipe_detail.php?id=${recipe.id}">
                                <img src="${recipe.image}" alt="${recipe.title}">
                                <h3>${recipe.title}</h3>
                                <p>${recipe.cuisine} - ${recipe.meal_type}</p>
                            </a>
                        </div>`;
                });
            };

            form.addEventListener('submit',function (e){
                e.preventDefault();
                errorMsg.textContent='';

                const cuisine_id=form.cuisine_id.value;
                const meal_type_id=form.meal_type_id.value;
                const search_term=form.search_term.value.trim();

                if (search_term && search_term.length < 2){
                    errorMsg.textContent="Search term must be at least 2 characters.";
                    return;
                }

                loadRecipes({cuisine_id,meal_type_id,search_term});
            });

            loadRecipes(); // Initial load
        });
    </script>
</body>
</html>
