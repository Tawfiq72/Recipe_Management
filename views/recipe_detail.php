<?php
session_start();

require_once '../config/db.php';
require_once '../controllers/RecipeController.php';

$controller = new RecipeController($conn);

// Get recipe ID from URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$recipe = $controller->getRecipe($id);

if (!$recipe) {
    die("Recipe not found.");
}

// Handle timer submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['new_timer_duration'])) {
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
    $label = $_POST['new_timer_label'] ?: "Step " . (count($timers) + 1);
    $duration_minutes = (int)$_POST['new_timer_duration'];
    if ($duration_minutes > 0 && $user_id) {
        $duration_seconds = $duration_minutes * 60; // Convert minutes to seconds
        $query = "INSERT INTO timers (user_id, recipe_id, duration, label) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "iiis", $user_id, $id, $duration_seconds, $label);
        mysqli_stmt_execute($stmt);
        header("Refresh:0"); // Refresh to show new timer
    }
}

// Handle timer deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_timer_id'])) {
    $timer_id = (int)$_POST['delete_timer_id'];
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
    $query = "DELETE FROM timers WHERE id = ? AND user_id = ? AND recipe_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "iii", $timer_id, $user_id, $id);
    mysqli_stmt_execute($stmt);
    header("Refresh:0"); // Refresh to remove deleted timer
}

// Fetch existing timers for the recipe and user
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
$timers = [];
if ($user_id) {
    $query = "SELECT id, label, duration FROM timers WHERE user_id = ? AND recipe_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $timers[] = $row;
    }
}

$ratings = $controller->getRatings($id);
$average_rating = $controller->getAverageRating($id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recipe Management - <?php echo htmlspecialchars($recipe['title']); ?></title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap');

        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 0;
        }

        .header {
            background-color: #2c3e50;
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .header h1 {
            margin: 0;
            font-size: 28px;
        }

        .login-section a, .login-section span {
            color: white;
            text-decoration: none;
            margin-left: 15px;
        }

        .login-section a:hover {
            text-decoration: underline;
        }

        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 30px;
        }

        .back-button {
            display: inline-block;
            margin-bottom: 20px;
            padding: 10px 20px;
            background-color: #e74c3c;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        .back-button:hover {
            background-color: #c0392b;
        }

        .recipe-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .recipe-card h2 {
            margin: 15px 20px;
            font-size: 28px;
            color: #333;
            text-align: center;
        }

        .recipe-content {
            display: flex;
            flex-direction: row;
        }

        .recipe-image {
            flex: 1;
            padding: 20px;
        }

        .recipe-image img {
            width: 100%;
            height: auto;
            border-radius: 10px;
        }

        .recipe-details {
            flex: 2;
            padding: 20px;
            text-align: left;
        }

        .recipe-details p {
            margin: 10px 0;
            color: #666;
            font-size: 16px;
        }

        .recipe-details ul {
            list-style-type: disc;
            padding-left: 20px;
            margin: 10px 0;
        }

        .recipe-details ul li {
            margin-bottom: 5px;
            color: #666;
            font-size: 16px;
        }

        .recipe-details h3 {
            margin: 15px 0 10px;
            font-size: 20px;
            color: #333;
        }

        .print-button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
            transition: background-color 0.3s;
        }

        .print-button:hover {
            background-color: #2980b9;
        }

        .rating-section {
            margin: 20px 0;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .rating-section h3 {
            margin: 0 0 15px;
            font-size: 20px;
            color: #333;
        }

        .rating-section form {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .rating-section select, .rating-section textarea {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }

        .rating-section button {
            padding: 10px 20px;
            background-color: #2ecc71;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .rating-section button:hover {
            background-color: #27ae60;
        }

        .rating-list {
            list-style: none;
            padding: 0;
            margin-top: 20px;
        }

        .rating-list li {
            margin: 10px 0;
            color: #666;
            font-size: 16px;
        }

        .rating-list li p {
            margin: 5px 0;
            color: #555;
        }

        .timer-section, .conversion-section, .substitution-section {
            margin: 20px 0;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .timer-section h3, .conversion-section h3, .substitution-section h3 {
            margin: 0 0 10px;
            font-size: 20px;
            color: #333;
        }

        .timer-panel {
            margin-bottom: 15px;
        }

        .timer-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .timer-display {
            font-size: 18px;
            font-weight: bold;
            margin-right: 10px;
            min-width: 80px;
        }

        .timer-controls button {
            padding: 5px 10px;
            margin-right: 5px;
            background-color: #2c3e50;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .timer-controls button:hover {
            background-color: #34495e;
        }

        .timer-controls button:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }

        .delete-btn {
            padding: 5px 10px;
            background-color: #e74c3c;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
            margin-left: 5px;
            transition: background-color 0.3s;
        }

        .delete-btn:hover {
            background-color: #c0392b;
        }

        .new-timer-form, .conversion-form, .substitution-form {
            margin-top: 10px;
            display: flex;
            gap: 10px;
        }

        .new-timer-form input, .conversion-form input, .substitution-form select {
            padding: 8px;
            font-size: 14px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .new-timer-form button, .conversion-form button, .substitution-form button {
            padding: 8px 15px;
            background-color: #2c3e50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .new-timer-form button:hover, .conversion-form button:hover, .substitution-form button:hover {
            background-color: #34495e;
        }

        .conversion-result, .substitution-result {
            margin-top: 10px;
            font-weight: bold;
            color: #333;
        }

        @media print {
            body * {
                visibility: hidden;
            }
            .recipe-card, .recipe-card * {
                visibility: visible;
            }
            .recipe-card {
                box-shadow: none;
                border: none;
                margin: 0;
                padding: 0;
            }
            .header, .back-button, .timer-section, .conversion-section, .substitution-section, .print-button {
                display: none;
            }
            .recipe-content {
                display: block;
            }
            .recipe-image {
                float: left;
                width: 30%;
                padding: 10px;
            }
            .recipe-details {
                width: 65%;
                float: right;
                padding: 10px;
            }
            .rating-section form, .rating-section ul, .rating-section h3 {
                display: none;
            }
            .rating-section p {
                visibility: visible;
            }
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
        <a href="home.php" class="back-button">Back to Home</a>
        <div class="recipe-card">
            <h2><?php echo htmlspecialchars($recipe['title']); ?></h2>
            <div class="recipe-content">
                <div class="recipe-image">
                    <img src="<?php echo htmlspecialchars($recipe['image']); ?>" alt="<?php echo htmlspecialchars($recipe['title']); ?>">
                </div>
                <div class="recipe-details">
                    <p><strong>Cuisine:</strong> <?php echo htmlspecialchars($recipe['cuisine']); ?></p>
                    <p><strong>Meal Type:</strong> <?php echo htmlspecialchars($recipe['meal_type']); ?></p>
                    <p><strong>Servings:</strong> <?php echo htmlspecialchars($recipe['servings']); ?></p>
                    <p><strong>Description:</strong> <?php echo htmlspecialchars($recipe['description']); ?></p>
                    <p><strong>Details:</strong> <?php echo nl2br(htmlspecialchars($recipe['details'])); ?></p>
                    <h3>Ingredients</h3>
                    <?php if (!empty($recipe['ingredients'])): ?>
                        <ul>
                            <?php foreach ($recipe['ingredients'] as $ingredient): ?>
                                <li><?php echo htmlspecialchars($ingredient['quantity']); ?> <?php echo htmlspecialchars($ingredient['unit']); ?> <?php echo htmlspecialchars($ingredient['name']); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p>No ingredients listed for this recipe.</p>
                    <?php endif; ?>
                    <a href="#" class="print-button" onclick="window.print()">Print Recipe</a>
                    <div class="rating-section">
                        <h3>Ratings & Reviews</h3>
                        <p><strong>Average Rating:</strong> <?php echo $average_rating ? number_format($average_rating, 1) : 'N/A'; ?> / 5</p>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <form id="ratingForm" method="post" onsubmit="return submitRating(event)">
                                <select name="rating" required>
                                    <option value="">Select Rating</option>
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                    <?php endfor; ?>
                                </select>
                                <textarea name="review" placeholder="Add a review (optional)"></textarea>
                                <button type="submit">Submit Rating</button>
                            </form>
                        <?php else: ?>
                            <p>Please <a href="login.php">Login</a> to rate this recipe.</p>
                        <?php endif; ?>
                        <?php if (!empty($error)): ?>
                            <p style="color: red;" id="ratingError"><?php echo $error; ?></p>
                        <?php endif; ?>
                        <ul class="rating-list" id="ratingList">
                            <?php if (!empty($ratings)): ?>
                                <?php foreach ($ratings as $rating): ?>
                                    <li>
                                        <strong><?php echo htmlspecialchars($rating['username']); ?>:</strong> <?php echo $rating['rating']; ?> / 5
                                        <?php if (!empty($rating['review'])): ?>
                                            <p><?php echo htmlspecialchars($rating['review']); ?></p>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p>No ratings yet.</p>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="timer-section">
            <h3>Multi-Timer Panel</h3>
            <div class="timer-panel" id="timerPanel">
                <?php foreach ($timers as $timer): ?>
                    <div class="timer-item" data-id="<?php echo $timer['id']; ?>" data-duration="<?php echo $timer['duration']; ?>">
                        <span class="timer-display" id="timer_<?php echo $timer['id']; ?>"><?php echo gmdate("i:s", $timer['duration']); ?></span>
                        <span><?php echo htmlspecialchars($timer['label']); ?></span>
                        <div class="timer-controls">
                            <button class="startTimer" data-id="<?php echo $timer['id']; ?>">Start</button>
                            <button class="pauseTimer" data-id="<?php echo $timer['id']; ?>" disabled>Pause</button>
                            <button class="resetTimer" data-id="<?php echo $timer['id']; ?>">Reset</button>
                        </div>
                        <form method="post" action="" style="display:inline;">
                            <input type="hidden" name="delete_timer_id" value="<?php echo $timer['id']; ?>">
                            <button type="submit" class="delete-btn" onclick="return confirm('Are you sure you want to delete this timer?')">Delete</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if (isset($_SESSION['user_id'])): ?>
                <form class="new-timer-form" method="post" action="" onsubmit="return validateTimer()">
                    <input type="text" name="new_timer_label" placeholder="Timer name (e.g., Step 1)" maxlength="100">
                    <input type="number" name="new_timer_duration" placeholder="Duration (minutes)" min="1" required>
                    <button type="submit">Add Timer</button>
                </form>
            <?php else: ?>
                <p>Please <a href="login.php">Login</a> to add timers.</p>
            <?php endif; ?>
        </div>
        <div class="conversion-section">
            <h3>Unit Conversion Calculator</h3>
            <form class="conversion-form" id="conversionForm" onsubmit="convertUnit(event)">
                <input type="number" id="quantity" placeholder="Quantity" step="0.1" required>
                <select id="fromUnit">
                    <option value="cup">Cup</option>
                    <option value="oz">Ounce (oz)</option>
                    <option value="tsp">Teaspoon (tsp)</option>
                    <option value="tbsp">Tablespoon (tbsp)</option>
                </select>
                <select id="toUnit">
                    <option value="ml">Milliliter (ml)</option>
                    <option value="g">Gram (g)</option>
                    <option value="tsp">Teaspoon (tsp)</option>
                    <option value="tbsp">Tablespoon (tbsp)</option>
                </select>
                <button type="submit">Convert</button>
            </form>
            <div class="conversion-result" id="conversionResult"></div>
        </div>
        <div class="substitution-section">
            <h3>Ingredient Substitution Suggestions</h3>
            <form class="substitution-form" id="substitutionForm" onsubmit="suggestSubstitution(event)">
                <select id="ingredient">
                    <option value="">Select Ingredient</option>
                    <option value="butter">Butter</option>
                    <option value="milk">Milk</option>
                    <option value="egg">Egg</option>
                    <option value="flour">Flour</option>
                    <option value="sugar">Sugar</option>
                </select>
                <button type="submit">Get Substitution</button>
            </form>
            <div class="substitution-result" id="substitutionResult"></div>
        </div>
    </div>
    <script>
        // Debug logging
        console.log('Recipe Data:', <?php echo json_encode($recipe); ?>);
        console.log('Timers:', <?php echo json_encode($timers); ?>);
        console.log('Ratings:', <?php echo json_encode($ratings); ?>);

        const timers = {};

        // Initialize existing timers
        document.querySelectorAll('.timer-item').forEach(item => {
            const id = item.dataset.id;
            timers[id] = {
                duration: parseInt(item.dataset.duration),
                remaining: parseInt(item.dataset.duration),
                interval: null,
                isRunning: false
            };
            updateDisplay(id);
        });

        function updateDisplay(id) {
            const minutes = Math.floor(timers[id].remaining / 60);
            const seconds = timers[id].remaining % 60;
            document.getElementById(`timer_${id}`).textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
        }

        document.querySelectorAll('.startTimer').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault(); // Prevent any form submission
                const id = this.dataset.id;
                if (!timers[id].isRunning) {
                    // Start timer immediately
                    timers[id].isRunning = true;
                    this.disabled = true;
                    document.querySelector(`.pauseTimer[data-id="${id}"]`).disabled = false;
                    
                    timers[id].interval = setInterval(() => {
                        if (timers[id].remaining > 0) {
                            timers[id].remaining--;
                            updateDisplay(id);
                        } else {
                            clearInterval(timers[id].interval);
                            timers[id].isRunning = false;
                            this.disabled = true;
                            document.querySelector(`.pauseTimer[data-id="${id}"]`).disabled = true;
                            alert(`${document.querySelector(`.timer-item[data-id="${id}"] span:nth-child(2)`).textContent} is complete!`);
                        }
                    }, 1000);

                    // Validate timer with AJAX in the background
                    fetch('start_timer.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `timer_id=${id}`
                    })
                    .then(response => response.json())
                    .then(result => {
                        if (!result.success) {
                            // Stop timer if validation fails
                            clearInterval(timers[id].interval);
                            timers[id].isRunning = false;
                            timers[id].remaining = timers[id].duration;
                            updateDisplay(id);
                            this.disabled = false;
                            document.querySelector(`.pauseTimer[data-id="${id}"]`).disabled = true;
                            alert('Error starting timer: ' + (result.error || 'Unknown error'));
                        }
                    })
                    .catch(error => {
                        // Stop timer on network error
                        clearInterval(timers[id].interval);
                        timers[id].isRunning = false;
                        timers[id].remaining = timers[id].duration;
                        updateDisplay(id);
                        this.disabled = false;
                        document.querySelector(`.pauseTimer[data-id="${id}"]`).disabled = true;
                        alert('Network error: Unable to validate timer');
                    });
                }
            });
        });

        document.querySelectorAll('.pauseTimer').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.dataset.id;
                if (timers[id].isRunning) {
                    clearInterval(timers[id].interval);
                    timers[id].isRunning = false;
                    this.disabled = true;
                    document.querySelector(`.startTimer[data-id="${id}"]`).disabled = false;
                }
            });
        });

        document.querySelectorAll('.resetTimer').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.dataset.id;
                clearInterval(timers[id].interval);
                timers[id].remaining = timers[id].duration;
                timers[id].isRunning = false;
                updateDisplay(id);
                document.querySelector(`.startTimer[data-id="${id}"]`).disabled = false;
                document.querySelector(`.pauseTimer[data-id="${id}"]`).disabled = true;
            });
        });

        async function submitRating(event) {
            event.preventDefault();
            const form = document.getElementById('ratingForm');
            const rating = form.querySelector('select[name="rating"]').value;
            const review = form.querySelector('textarea[name="review"]').value;
            const recipeId = <?php echo $id; ?>;
            
            if (!rating) {
                alert('Please select a rating before submitting.');
                return false;
            }

            const response = await fetch('submit_rating.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `recipe_id=${recipeId}&rating=${rating}&review=${encodeURIComponent(review)}`
            });
            const result = await response.json();
            
            const errorElement = document.getElementById('ratingError');
            const ratingList = document.getElementById('ratingList');
            
            if (result.success) {
                // Update average rating
                document.querySelector('.rating-section p').textContent = `Average Rating: ${result.average_rating} / 5`;
                
                // Update ratings list
                if (result.ratings.length > 0) {
                    ratingList.innerHTML = '';
                    result.ratings.forEach(rating => {
                        const li = document.createElement('li');
                        li.innerHTML = `<strong>${rating.username}:</strong> ${rating.rating} / 5` + 
                            (rating.review ? `<p>${rating.review}</p>` : '');
                        ratingList.appendChild(li);
                    });
                } else {
                    ratingList.innerHTML = '<p>No ratings yet.</p>';
                }
                
                // Clear form
                form.reset();
                if (errorElement) errorElement.remove();
            } else {
                if (errorElement) {
                    errorElement.textContent = result.error;
                } else {
                    const p = document.createElement('p');
                    p.id = 'ratingError';
                    p.style.color = 'red';
                    p.textContent = result.error;
                    form.after(p);
                }
            }
            
            return false;
        }

        function validateTimer() {
            const duration = document.querySelector('input[name="new_timer_duration"]').value;
            if (duration <= 0) {
                alert('Please enter a duration greater than 0 minutes.');
                return false;
            }
            return true;
        }

        function convertUnit(event) {
            event.preventDefault();
            const quantity = parseFloat(document.getElementById('quantity').value);
            const fromUnit = document.getElementById('fromUnit').value;
            const toUnit = document.getElementById('toUnit').value;
            if (isNaN(quantity) || quantity <= 0) {
                document.getElementById('conversionResult').textContent = 'Please enter a valid quantity.';
                return;
            }
            let result = 0;

            const conversions = {
                cup: { ml: 240, g: 200, tsp: 48, tbsp: 16 },
                oz: { ml: 29.5735, g: 28.3495, tsp: 6, tbsp: 2 },
                tsp: { ml: 4.92892, g: 4.16667, cup: 1/48, tbsp: 1/3 },
                tbsp: { ml: 14.7868, g: 12.5, cup: 1/16, tsp: 3 }
            };

            if (fromUnit === toUnit) {
                result = quantity;
            } else if (conversions[fromUnit] && conversions[fromUnit][toUnit]) {
                result = quantity * conversions[fromUnit][toUnit];
            } else {
                const intermediate = quantity * conversions[fromUnit].ml;
                result = intermediate / conversions[toUnit].ml;
            }

            document.getElementById('conversionResult').textContent = `${quantity} ${fromUnit} = ${result.toFixed(2)} ${toUnit}`;
        }

        function suggestSubstitution(event) {
            event.preventDefault();
            const ingredient = document.getElementById('ingredient').value;
            if (!ingredient) {
                document.getElementById('substitutionResult').innerHTML = 'Please select an ingredient.';
                return;
            }
            const substitutions = {
                butter: "Margarine, Coconut Oil, or Applesauce (for baking)",
                milk: "Almond Milk, Soy Milk, or Oat Milk",
                egg: "Flaxseed Meal (1 tbsp + 3 tbsp water), Applesauce (1/4 cup), or Banana (1/2 mashed)",
                flour: "Whole Wheat Flour, Almond Flour, or Cornstarch (as thickener)",
                sugar: "Honey, Maple Syrup, or Agave Nectar"
            };

            const result = substitutions[ingredient] || "No substitution available for this ingredient.";
            document.getElementById('substitutionResult').innerHTML = `<strong>${ingredient.charAt(0).toUpperCase() + ingredient.slice(1)}:</strong> ${result}`;
        }
    </script>
</body>
</html>