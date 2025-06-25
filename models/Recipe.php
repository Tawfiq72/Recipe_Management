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

}

