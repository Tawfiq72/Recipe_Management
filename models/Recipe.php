<?php

class Recipe {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function getAllRecipes() {
        $query = "SELECT r.id, r.title, r.description, r.image, c.name as cuisine, m.name as meal_type 
                  FROM recipes r 
                  LEFT JOIN cuisines c ON r.cuisine_id = c.id 
                  LEFT JOIN meal_types m ON r.meal_type_id = m.id";
        $result = mysqli_query($this->conn, $query);
        $recipes = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $row['image'] = $row['image'] ? $row['image'] : 'https://via.placeholder.com/300x200?text=' . urlencode($row['title']);
            $recipes[] = $row;
        }
        return $recipes;
    }

    public function getFilteredRecipes($cuisine_id = null, $meal_type_id = null, $search_term = null) {
        $query = "SELECT r.id, r.title, r.description, r.image, c.name as cuisine, m.name as meal_type 
                  FROM recipes r 
                  LEFT JOIN cuisines c ON r.cuisine_id = c.id 
                  LEFT JOIN meal_types m ON r.meal_type_id = m.id 
                  WHERE 1=1";
        $params = [];
        $param_types = "";

        if ($cuisine_id) {
            $query .= " AND r.cuisine_id = ?";
            $params[] = $cuisine_id;
            $param_types .= "i";
        }
        if ($meal_type_id) {
            $query .= " AND r.meal_type_id = ?";
            $params[] = $meal_type_id;
            $param_types .= "i";
        }
        if ($search_term) {
            $query .= " AND r.title LIKE ?";
            $params[] = "%" . $search_term . "%";
            $param_types .= "s";
        }

        $stmt = mysqli_prepare($this->conn, $query);
        if (!empty($params)) {
            mysqli_stmt_bind_param($stmt, $param_types, ...$params);
        }
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $recipes = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $row['image'] = $row['image'] ? $row['image'] : 'https://via.placeholder.com/300x200?text=' . urlencode($row['title']);
            $recipes[] = $row;
        }
        return $recipes;
    }

    public function getRecipeById($id) {
        $query = "SELECT r.id, r.title, r.description, r.details, r.image, r.servings, 
                         c.name as cuisine, m.name as meal_type, r.cuisine_id, r.meal_type_id 
                  FROM recipes r 
                  LEFT JOIN cuisines c ON r.cuisine_id = c.id 
                  LEFT JOIN meal_types m ON r.meal_type_id = m.id 
                  WHERE r.id = ?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $recipe = mysqli_fetch_assoc($result);
        if ($recipe) {
            $recipe['image'] = $recipe['image'] ? $recipe['image'] : 'https://via.placeholder.com/300x200?text=' . urlencode($recipe['title']);
            $recipe['ingredients'] = $this->getIngredients($id);
        }
        return $recipe;
    }

    public function addRecipe($title, $description, $details, $servings, $cuisine_id, $meal_type_id, $image_path) {
        $query = "INSERT INTO recipes (title, description, details, servings, cuisine_id, meal_type_id, image) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "sssiiis", $title, $description, $details, $servings, $cuisine_id, $meal_type_id, $image_path);
        $success = mysqli_stmt_execute($stmt);
        if ($success) {
            return mysqli_insert_id($this->conn);
        }
        return false;
    }

    public function updateRecipe($id, $title, $description, $details, $servings, $cuisine_id, $meal_type_id, $image_path) {
        $query = "UPDATE recipes SET title = ?, description = ?, details = ?, servings = ?, cuisine_id = ?, meal_type_id = ?, image = ? 
                  WHERE id = ?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "sssiiisi", $title, $description, $details, $servings, $cuisine_id, $meal_type_id, $image_path, $id);
        return mysqli_stmt_execute($stmt);
    }

    public function deleteRecipe($id) {
        mysqli_begin_transaction($this->conn);
        try {
            $delete_ingredients = "DELETE FROM ingredients WHERE recipe_id = ?";
            $stmt1 = mysqli_prepare($this->conn, $delete_ingredients);
            mysqli_stmt_bind_param($stmt1, "i", $id);
            $success1 = mysqli_stmt_execute($stmt1);

            $delete_recipe = "DELETE FROM recipes WHERE id = ?";
            $stmt2 = mysqli_prepare($this->conn, $delete_recipe);
            mysqli_stmt_bind_param($stmt2, "i", $id);
            $success2 = mysqli_stmt_execute($stmt2);

            if ($success1 && $success2) {
                mysqli_commit($this->conn);
                return true;
            } else {
                mysqli_rollback($this->conn);
                return false;
            }
        } catch (Exception $e) {
            mysqli_rollback($this->conn);
            return false;
        }
    }

    public function addRating($recipe_id, $user_id, $rating, $review) {
        $query = "INSERT INTO ratings (recipe_id, user_id, rating) 
                  VALUES (?, ?, ?) 
                  ON DUPLICATE KEY UPDATE rating = ?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "iiid", $recipe_id, $user_id, $rating, $rating);
        return mysqli_stmt_execute($stmt);
    }

    public function getRatings($recipe_id) {
        $query = "SELECT r.rating, u.username 
                  FROM ratings r 
                  JOIN users u ON r.user_id = u.id 
                  WHERE r.recipe_id = ?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $recipe_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $ratings = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $ratings[] = $row;
        }
        return $ratings;
    }

    public function getAverageRating($recipe_id) {
        $query = "SELECT AVG(rating) as average_rating 
                  FROM ratings 
                  WHERE recipe_id = ?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $recipe_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        return $row['average_rating'] ? round($row['average_rating'], 1) : 0;
    }

    public function addIngredients($recipe_id, $ingredients) {
        $query = "INSERT INTO ingredients (recipe_id, name, quantity, unit) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($this->conn, $query);
        foreach ($ingredients as $ingredient) {
            $name = $ingredient['name'];
            $quantity = $ingredient['quantity'];
            $unit = $ingredient['unit'];
            mysqli_stmt_bind_param($stmt, "isds", $recipe_id, $name, $quantity, $unit);
            if (!mysqli_stmt_execute($stmt)) {
                return false;
            }
        }
        return true;
    }

    public function updateIngredients($recipe_id, $ingredients) {
        $delete_query = "DELETE FROM ingredients WHERE recipe_id = ?";
        $stmt = mysqli_prepare($this->conn, $delete_query);
        mysqli_stmt_bind_param($stmt, "i", $recipe_id);
        if (!mysqli_stmt_execute($stmt)) {
            return false;
        }

        $insert_query = "INSERT INTO ingredients (recipe_id, name, quantity, unit) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($this->conn, $insert_query);
        foreach ($ingredients as $ingredient) {
            $name = $ingredient['name'];
            $quantity = $ingredient['quantity'];
            $unit = $ingredient['unit'];
            mysqli_stmt_bind_param($stmt, "isds", $recipe_id, $name, $quantity, $unit);
            if (!mysqli_stmt_execute($stmt)) {
                return false;
            }
        }
        return true;
    }

    public function getIngredients($recipe_id) {
        $query = "SELECT name, quantity, unit FROM ingredients WHERE recipe_id = ?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $recipe_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $ingredients = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $ingredients[] = $row;
        }
        return $ingredients;
    }
}
?>