<?php
require_once '../config/db.php';
require_once '../models/Recipe.php';

class RecipeController {
    public function __construct($conn) {
        $this->recipeModel = new Recipe($conn);
    }

    public function getRecipes() {
        return $this->recipeModel->getAllRecipes();
    }

    public function getFilteredRecipes($cuisine_id = null, $meal_type_id = null) {
        return $this->recipeModel->getFilteredRecipes($cuisine_id, $meal_type_id);
    }

    // Get single recipe for detail page or edit
    public function getRecipe($id) {
        return $this->recipeModel->getRecipeById($id);
    }

    public function addRecipe($title, $description, $details, $servings, $cuisine_id, $meal_type_id, $image, $ingredients) {
        // Handle image upload
        $image_path = null;
        if ($image['name']) {
            $target_dir = "../uploads/";
            $image_ext = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));
            $allowed_exts = ['jpg', 'jpeg', 'png'];
            if (!in_array($image_ext, $allowed_exts)) {
                return "Invalid image format. Use JPG or PNG.";
            }
            if ($image['size'] > 5000000) { // 5MB limit
                return "Image size too large. Max 5MB.";
            }
            $unique_name = uniqid() . '.' . $image_ext;
            $image_path = '/recipe_app/uploads/' . $unique_name;
            if (!move_uploaded_file($image['tmp_name'], $target_dir . $unique_name)) {
                return "Failed to upload image.";
            }
        }
        // Save recipe
        $recipe_id = $this->recipeModel->addRecipe($title, $description, $details, $servings, $cuisine_id, $meal_type_id, $image_path);
        if (!$recipe_id) {
            return "Failed to save recipe.";
        }
        // Save ingredients
        if (!empty($ingredients['name'])) {
            $ingredient_data = [];
            for ($i = 0; $i < count($ingredients['name']); $i++) {
                if (!empty($ingredients['name'][$i]) && !empty($ingredients['quantity'][$i]) && !empty($ingredients['unit'][$i])) {
                    $ingredient_data[] = [
                        'name' => $ingredients['name'][$i],
                        'quantity' => (float)$ingredients['quantity'][$i],
                        'unit' => $ingredients['unit'][$i]
                    ];
                }
            }
            if (empty($ingredient_data)) {
                return "At least one valid ingredient is required.";
            }
            if (!$this->recipeModel->addIngredients($recipe_id, $ingredient_data)) {
                return "Failed to save ingredients.";
            }
        } else {
            return "At least one valid ingredient is required.";
        }
        return true;
    }
}


?>