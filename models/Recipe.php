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


    public function getFilteredRecipes($cuisine_id = null, $meal_type_id = null) {
        $query = "SELECT r.id, r.title, r.description, r.image, c.name as cuisine, m.name as meal_type 
                  FROM recipes r 
                  LEFT JOIN cuisines c ON r.cuisine_id = c.id 
                  LEFT JOIN meal_types m ON r.meal_type_id = m.id 
                  WHERE 1=1";
        $params = [];
        if ($cuisine_id) {
            $query .= " AND r.cuisine_id = ?";
            $params[] = $cuisine_id;
        }
        if ($meal_type_id) {
            $query .= " AND r.meal_type_id = ?";
            $params[] = $meal_type_id;
        }
        $stmt = mysqli_prepare($this->conn, $query);
        if (!empty($params)) {
            mysqli_stmt_bind_param($stmt, str_repeat("i", count($params)), ...$params);
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

     // Get single recipe by ID
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
            // Get ingredients
            $recipe['ingredients'] = $this->getIngredients($id);
        }
        return $recipe;
    }

    public function addRecipe($title, $description, $details, $servings, $cuisine_id, $meal_type_id, $image_path) {
        $query = "INSERT INTO recipes (title, description, details, servings, cuisine_id, meal_type_id, image) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "sssiiis", $title, $description, $details, $servings, $cuisine_id, $meal_type_id, $image_path);
        $success = mysqli_stmt_execute($stmt);
        if ($success) {
            return mysqli_insert_id($this->conn); // Return new recipe ID
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

}

