<?php 
require_once '../config/db.php';
require_once '../models/Recipe.php';

class RecipeController{

    private $recipeModel;

    public function __construct($conn){

        $this->recipeModel=new Recipe($conn);
    }

    public function getRecipes(){
        return $this->recipeModel->getAllRecipes();
    }
    public function getFilteredRecipes($cuisine_id=null,$meal_type_id=null){
        return $this->getFilteredRecipes($cuisine_id,$meal_type_id);
    }

    public function getRecipe($id){
        return $this->recipeModel->getRecipeById($id);
    }

    public function addRecipe($title,$description,$details,$servings,$cuisine_id,$meal_type_id,$image,$ingredients){
        $image_path=null;
        if($image['name']){
            $target_dir="../uploads/";
            $image_ext=strtolower(pathinfo($image['name'],PATHINFO_EXTENSION));
            $allowed_exts=['jpg','jpeg','png'];
            if(!in_array($image_ext,$allowed_exts)){
                return "Invalid image format(use jpg or png)";

            }
            if($image['size']>5000000){
                return "Image size is too large (MAX 5MB)";
            }
            $unique_name=uniqid().'.'.$image_ext;
            $image_path='/recipe_management/uploads/'.$unique_name;
            if(!move_uploaded_file($image['tmp_name'],$target_dir.$unique_name)){
                return "Failed to upload image.";
            }
        }
        $recipe_id=$this->recipeModel->addRecipe($title,$description,$details,$servings,$cuisine_id,$meal_type_id,$image_path);
       
        if(!$recipe_id){
            return "Failed to save recipe";
        }
        if(!empty($ingredients['name'])){
            $ingredient_data=[];
            for($i=0;$i<count($ingredients['name']);$i++)
            {
                if(!empty($ingredients['name'][$i]) && !empty($ingredients['quantity'][$i]) && !empty($ingredients['unit'][$i])){
                    $ingredient_data[]=[
                        'name'=>$ingredients['name'][$i],
                        'quantity'=>(float)$ingredients['quantity'][$i],
                        'unit'=>$ingredients['unit'][$i]
                    ];
                }
            }
            if(empty($ingredient_data)){
                return "At leas one valid ingredient is required";
            }
            if(!this->recipeModel->addIngredients($recipe_id,$ingredient_data)){
                return "Failed to save ingredients";
            }
        }
           
        else {
                  return "At leas one valid ingredient is required";
            }
            return true;
        
    }

    public function updateRecipe($id,$title,$description,$details,$servings,$cuisine_id,$meal_type_id,$image,$ingredients){
         $image_path=null;
        if($image['name']){
            $target_dir="../uploads/";
            $image_ext=strtolower(pathinfo($image['name'],PATHINFO_EXTENSION));
            $allowed_exts=['jpg','jpeg','png'];
            if(!in_array($image_ext,$allowed_exts)){
                return "Invalid image format(use jpg or png)";

            }
            if($image['size']>5000000){
                return "Image size is too large (MAX 5MB)";
            }
            $unique_name=uniqid().'.'.$image_ext;
            $image_path='/recipe_management/uploads/'.$unique_name;
            if(!move_uploaded_file($image['tmp_name'],$target_dir.$unique_name)){
                return "Failed to upload image.";
            }
        }
        else {
            $recipe=$this->getRecipe($id);
            $image_path=$recipe['image'];
        }
        $success=$this->recipeModel->updateRecipe($id,$title,$description,$details,$servings,$cuisine_id,$meal_type_id,$image_path);
        if($success && !empty($ingredients['name'])){
            $ingredient_data=[];
            for($i=0;$i<count($ingredients['name']);$i++){
                if(!empty($ingredients['name'][$i]) && !empty($ingredients['quantity'][$i]) && !empty($ingredients['unit'][$i])){
                    $ingredient_data[]=[
                        'name'=>$ingredients['name'][$i],
                        'quantity'=>(float)$ingredients['quantity'][$i],
                        'unit'=>$ingredients['unit'][$i]
                    ];
                }
            }
             if(empty($ingredient_data)){
                return "At leas one valid ingredient is required";
            }
            if(!this->recipeModel->addIngredients($recipe_id,$ingredient_data)){
                return "Failed to save ingredients";
            }
        }

        return $success?true:"Failed to update recipe";
    }

    public function deleteRecipe($id){
        $success=$this->recipeModel->deleteRecipe($id);
        return $success?true:"Failed to delete recipe";
    }

    public function submitRating($recipe_id,$user_id,$rating,$review){
        if(!is_numeric($rating)|| $rating<1 || $rating>5)
        {
            return "Invalid rating (Rating must be between 1 and 5";
        }
        $review=$review?trim($review):null;
        $success=$this->recipeModel->addRating($recipe_id,$user_id,$rating,$review);
        return $success?true:"Failed to submit rating";
    }
    public function getRatings($recipe_id){

        return $this->recipeModel->getRatings($recipe_id);
    }
    public function getAverageRating($recipe_id){
        return $this->recipeModel->getAverageRating($recipe_id);
    }
}
?>