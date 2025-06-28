<?php 
session_start();
if(!isset($_SESSION['role']) || $_SESSION['role']!='admin'){
    header("Location:login.php");
    exit();
}
require_once '../config/db.php';
require_once '../controllers/RecipeController.php';
$controller=new RecipeController($conn);

$cuisines_query="SELECT id,name FROM cuisines";
$cuisines_result=mysqli_query($conn,$cuisines_query);
$cuisines=mysqli_fetch_all($cuisines_result,MYSQLI_ASSOC);


$meal_types_query="SELECT id,name FROM meal_types";
$meal_types_result=mysqli_query($conn,$meal_types_query);
$meal_types=mysqli_fetch_all($meal_types_result,MYSQLI_ASSOC);


$error='';
$success='';
$edit_mode=false;
$edit_recipe=null;
if($_SERVER['REQUEST_METHOD']=='POST'){
    $id=isset($_POST['id'])?(int)$_POST['id']:null;
    $title=$_POST['title'];
    $description=$_POST['description'];
    $details=$_POST['details'];
    $servings=(int)$_POST['servings'];
    $cuisine_id=(int)$_POST['cuisine_id'];
    $meal_type_id=(int)$_POST['meal_type_id'];
    $image=$_FILES['image'];
    $ingredients=[
        'name'=>$_POST['ingredient_name'],
        'quantity'=>$_POST['ingredient_quantity'],
        'unit'=>$_POST['ingredient_unit']
    ];

    if($id){
        
        $result=$controller->updateRecipe($id,$title,$description,$details,$servings,$cuisine_id,$meal_type_id,$image,$ingredients);
        if ($result===true){
            $success="Recipe updated successfully";
            header("Refresh:2;url=admin_add_recipe.php");
        }
        else{
            $error=$result;
            $edit_recipe=$controller->getRecipe($id);
            $edit_mode=true;
        }
    } 
    else{
        $result=$controller->addRecipe($title,$description,$details,$servings,$cuisine_id,$meal_type_id,$image,$ingredients);
        if($result===true){
            $success="Recipe added successfully";
            header("Refresh:2;url=admin_add_recipe.php");
        } 
        else{
            $error=$result;
        }
    }
}
if(isset($_GET['edit'])&&!isset($_POST['id'])){
    $edit_id=(int)$_GET['edit'];
    $edit_recipe=$controller->getRecipe($edit_id);
    if($edit_recipe){
        $edit_mode=true;
    }
     else{
        $error="Recipe not found";
    }
}
if (isset($_GET['delete'])&& isset($_GET['id'])){
    $id=(int)$_GET['id'];
    $result=$controller->deleteRecipe($id);
    if($result===true) {
        $success="Recipe deleted successfully";
        header("Refresh:2;url=admin_add_recipe.php");
    } 
    else{
        $error=$result;
    }
}


$recipes = $controller->getRecipes();



?>


<!DOCTYPE html>
<html>
<head>
    <title>Recipe Management - Admin Panel</title>
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
        .login-section a, .login-section span{
            color: white;
            text-decoration: none;
            margin-left: 10px;
        }
        .login-section a:hover{
            text-decoration: underline;
        }
        .container{
            max-width: 800px;
            margin: 50px auto;
            padding: 0 20px;
        }
        .admin-panel{
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
        }
        .admin-panel h2{
            margin: 0 0 20px;
            font-size: 24px;
            text-align: center;
        }
        .admin-panel label{
            display: block;
            margin: 10px 0 5px;
            font-size: 16px;
        }
        .admin-panel input, .admin-panel textarea, .admin-panel select{
            width: 100%;
            padding: 12px;
            font-size: 16px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .admin-panel textarea{
            height: 120px;
        }
        .admin-panel button{
            width: 100%;
            padding: 12px;
            background-color: #333;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            margin-top: 15px;
            cursor: pointer;
        }
        .admin-panel button:hover{
            background-color: #555;
        }
        .error{
            color: red;
            text-align: center;
            margin-bottom: 10px;
        }
        .success{
            color: green;
            text-align: center;
            margin-bottom: 10px;
        }
        .home-link{
            display: block;
            text-align: center;
            margin-top: 15px;
            text-decoration: none;
            color: #333;
            font-weight: bold;
        }
        .home-link:hover{
            text-decoration: underline;
        }
        .ingredient-row{
            display: flex;
            gap: 6px;
            margin-bottom: 10px;
            align-items: center;
        }
        .ingredient-row input, .ingredient-row select{
            flex: 1;
            padding: 12px;
            font-size: 16px;
            min-width: 100px;
        }
        .remove-btn{
            padding: 1px 4px;
            background-color: #d32f2f;
            color: white;
            border: none;
            border-radius: 2px;
            font-size: 11px;
            cursor: pointer;
            min-width: 40px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .remove-btn:hover{
            background-color: #b71c1c;
        }
        .remove-btn:disabled{
            background-color: #e57373;
            cursor: not-allowed;
        }
        .add-ingredient-btn{
            padding: 10px 20px;
            background-color: #388e3c;
            color: white;
            border: none;
            border-radius: 5px;
            margin-top: 10px;
            cursor: pointer;
        }
        .add-ingredient-btn:hover{
            background-color: #2e7d32;
        }
        .recipe-list{
            margin-top: 20px;
        }
        .recipe-item{
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        .edit-btn,.delete-btn{
            padding: 8px 15px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            color:white;
            text-decoration: none;
            line-height:36px;
            font-size: 14px;
            display: inline-block;
            text-align: center;
            width: 90px;
            height: 36px;
            box-shadow: 0 4px 6px rgba(175, 21, 21, 0.64);
            transition: all 0.3s ease;
        }

        .edit-btn{
            background-color: #1976d2;
            
        }

        .edit-btn:hover{
            background-color:rgb(33, 112, 203);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(21, 101, 192, 0.3);
        }

        .delete-btn{
            background-color: #d32f2f;
        }

        .delete-btn:hover{
            background-color: #b71c1c;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(183, 28, 28, 0.3);
        }
        .button-group{
            display:flex;
            align-items:center;
            gap:10px;
        }

    </style>
</head>
<body>
    <div class="header">
        <h1>Recipe Management</h1>
        <div class="login-section">
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
            <a href="login.php?action=logout">Logout</a>
        </div>
    </div>
    <div class="container">
        <div class="admin-panel">
            <h2>Admin Panel</h2>
            <?php if ($error): ?>
                <p class="error"><?php echo $error; ?></p>
            <?php endif; ?>
            <?php if ($success): ?>
                <p class="success"><?php echo $success; ?></p>
            <?php endif; ?>
            <form action="" method="post" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?php echo $edit_mode ? $edit_recipe['id'] : ''; ?>">
                <label for="title">Title</label>
                <input type="text" id="title" name="title" value="<?php echo $edit_mode ? htmlspecialchars($edit_recipe['title']) : ''; ?>" required>
                <label for="description">Description</label>
                <input type="text" id="description" name="description" value="<?php echo $edit_mode ? htmlspecialchars($edit_recipe['description']) : ''; ?>" required>
                <label for="details">Details (Instructions)</label>
                <textarea id="details" name="details" required><?php echo $edit_mode ? htmlspecialchars($edit_recipe['details']) : ''; ?></textarea>
                <label for="servings">Servings</label>
                <input type="number" id="servings" name="servings" min="1" value="<?php echo $edit_mode ? htmlspecialchars($edit_recipe['servings']) : ''; ?>" required>
                <label for="cuisine_id">Cuisine</label>
                <select id="cuisine_id" name="cuisine_id" required>
                    <?php foreach ($cuisines as $cuisine): ?>
                        <option value="<?php echo $cuisine['id']; ?>" <?php echo $edit_mode && $edit_recipe['cuisine_id'] == $cuisine['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cuisine['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <label for="meal_type_id">Meal Type</label>
                <select id="meal_type_id" name="meal_type_id" required>
                    <?php foreach ($meal_types as $meal_type): ?>
                        <option value="<?php echo $meal_type['id']; ?>" <?php echo $edit_mode && $edit_recipe['meal_type_id'] == $meal_type['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($meal_type['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <label for="image">Image (JPG/PNG, max 5MB) - Leave empty to keep existing</label>
                <input type="file" id="image" name="image" accept="image/jpeg,image/png">
                <h3>Ingredients</h3>
                <div id="ingredient-container">
                    <?php if ($edit_mode && !empty($edit_recipe['ingredients'])): ?>
                        <?php foreach ($edit_recipe['ingredients'] as $index => $ingredient): ?>
                            <div class="ingredient-row">
                                <input type="text" name="ingredient_name[]" value="<?php echo htmlspecialchars($ingredient['name']); ?>" placeholder="Ingredient Name" required>
                                <input type="number" name="ingredient_quantity[]" value="<?php echo htmlspecialchars($ingredient['quantity']); ?>" placeholder="Quantity" min="0.01" step="0.01" required>
                                <select name="ingredient_unit[]" required>
                                    <option value="cups" <?php echo $ingredient['unit'] == 'cups' ? 'selected' : ''; ?>>Cups</option>
                                    <option value="grams" <?php echo $ingredient['unit'] == 'grams' ? 'selected' : ''; ?>>Grams</option>
                                    <option value="teaspoons" <?php echo $ingredient['unit'] == 'teaspoons' ? 'selected' : ''; ?>>Teaspoons</option>
                                    <option value="tablespoons" <?php echo $ingredient['unit'] == 'tablespoons' ? 'selected' : ''; ?>>Tablespoons</option>
                                    <option value="pieces" <?php echo $ingredient['unit'] == 'pieces' ? 'selected' : ''; ?>>Pieces</option>
                                    <option value="ml" <?php echo $ingredient['unit'] == 'ml' ? 'selected' : ''; ?>>Milliliters</option>
                                </select>
                                <button type="button" class="remove-btn" <?php echo $index == 0 && count($edit_recipe['ingredients']) == 1 ? 'disabled' : ''; ?>>Remove</button>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="ingredient-row">
                            <input type="text" name="ingredient_name[]" placeholder="Ingredient Name" required>
                            <input type="number" name="ingredient_quantity[]" placeholder="Quantity" min="0.01" step="0.01" required>
                            <select name="ingredient_unit[]" required>
                                <option value="cups">Cups</option>
                                <option value="grams">Grams</option>
                                <option value="teaspoons">Teaspoons</option>
                                <option value="tablespoons">Tablespoons</option>
                                <option value="pieces">Pieces</option>
                                <option value="ml">Milliliters</option>
                            </select>
                            <button type="button" class="remove-btn" disabled>Remove</button>
                        </div>
                    <?php endif; ?>
                </div>
                <button type="button" class="add-ingredient-btn">Add Ingredient</button>
                <button type="submit"><?php echo $edit_mode ? 'Update Recipe' : 'Add Recipe'; ?></button>
            </form>
            <a href="home.php" class="home-link">Back to Home</a>

            <!-- Recipe List -->
            <div class="recipe-list">
                <h3>Existing Recipes</h3>
                <?php if (!empty($recipes)): ?>
                    <?php foreach ($recipes as $recipe): ?>
                        <div class="recipe-item">
                         <span><?php echo htmlspecialchars($recipe['title']); ?></span>
                          <div class="button-group">
                           <button class="edit-btn" onclick="window.location.href='?edit=<?php echo $recipe['id']; ?>'">Edit</button>
                            <button class="delete-btn" onclick="if(confirm('Are you sure you want to delete this recipe?')) window.location.href='?delete=1&id=<?php echo $recipe['id']; ?>'">Delete</button>
                         </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No recipes available.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script>
        document.querySelector('.add-ingredient-btn').addEventListener('click', function() {
            const container = document.getElementById('ingredient-container');
            const row = document.createElement('div');
            row.className = 'ingredient-row';
            row.innerHTML = `
                <input type="text" name="ingredient_name[]" placeholder="Ingredient Name" required>
                <input type="number" name="ingredient_quantity[]" placeholder="Quantity" min="0.01" step="0.01" required>
                <select name="ingredient_unit[]" required>
                    <option value="cups">Cups</option>
                    <option value="grams">Grams</option>
                    <option value="teaspoons">Teaspoons</option>
                    <option value="tablespoons">Tablespoons</option>
                    <option value="pieces">Pieces</option>
                    <option value="ml">Milliliters</option>
                </select>
                <button type="button" class="remove-btn">Remove</button>
            `;
            container.appendChild(row);
            updateRemoveButtons();
        });

        function updateRemoveButtons() {
            const rows = document.querySelectorAll('.ingredient-row');
            const removeButtons = document.querySelectorAll('.remove-btn');
            removeButtons.forEach(button => button.disabled = rows.length === 1);
            removeButtons.forEach(button => {
                button.onclick = function() {
                    if (rows.length > 1) {
                        button.parentElement.remove();
                        updateRemoveButtons();
                    }
                };
            });
        }

        // Initialize remove buttons
        updateRemoveButtons();
    </script>
</body>
</html>