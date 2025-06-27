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

// Handle rating submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['rating'])) {
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
    $rating = (float)$_POST['rating'];
    $review = isset($_POST['review']) ? trim($_POST['review']) : null;
    $result = $controller->submitRating($id, $user_id, $rating, $review);
    if ($result === true) {
        header("Refresh:0"); // Refresh to show updated rating
    } else {
        $error = $result;
    }
}

$ratings = $controller->getRatings($id);
$average_rating = $controller->getAverageRating($id);

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

// Handle new timer submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['new_timer_duration'])) {
    $label = $_POST['new_timer_label'] ?: "Step " . (count($timers) + 1);
    $duration_minutes = (int)$_POST['new_timer_duration'];
    if ($duration_minutes > 0) {
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
    $query = "DELETE FROM timers WHERE id = ? AND user_id = ? AND recipe_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "iii", $timer_id, $user_id, $id);
    mysqli_stmt_execute($stmt);
    header("Refresh:0"); // Refresh to remove deleted timer
}
?>


<!DOCTYPE html>
<html>
<head>

    <title>Recipe Management - <?php echo htmlspecialchars($recipe['title']); ?></title>
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
            max-width: 800px;
            margin: 20px auto;
            padding: 0 20px;
        }
        .recipe-detail{
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
        }
        .recipe-detail img{
            max-width: 100%;
            height: auto;
            border-radius: 5px;
        }
        .recipe-detail h2{
            margin: 0 0 15px;
            font-size: 24px;
        }
        .recipe-detail h3{
            margin: 15px 0 10px;
            font-size: 18px;
        }
        .recipe-detail p{
            margin: 10px 0;
            color: #666;
        }
        .recipe-detail ul{
            list-style-type: disc;
            padding-left: 20px;
            margin: 10px 0;
        }
        .recipe-detail ul li{
            margin-bottom: 5px;
            color: #666;
        }
        .timer-section,.conversion-section,.substitution-section,.print-section,.rating-section{
            margin-top: 20px;
            padding: 15px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .timer-section h3,.conversion-section h3,.substitution-section h3,.print-section h3,.rating-section h3{
            margin: 0 0 10px;
            font-size: 18px;
        }
        .timer-panel{
            margin-bottom: 15px;
        }
        .timer-item{
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        .timer-display{
            font-size: 18px;
            font-weight: bold;
            margin-right: 10px;
            min-width: 80px;
        }
        .timer-controls button{
            padding: 5px 10px;
            margin-right: 5px;
            background-color: #333;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
        }
        .timer-controls button:hover{
            background-color: #555;
        }
        .timer-controls button:disabled{
            background-color: #ccc;
            cursor: not-allowed;
        }
        .delete-btn{
            padding: 5px 10px;
            background-color: #d32f2f;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
            margin-left: 5px;
        }
        .delete-btn:hover{
            background-color: #b71c1c;
        }
        .new-timer-form{
            margin-top: 10px;
        }
        .new-timer-form input, .new-timer-form button{
            padding: 8px;
            font-size: 14px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .new-timer-form button{
            background-color: #333;
            color: white;
            border: none;
            cursor: pointer;
        }
        .new-timer-form button:hover{
            background-color: #555;
        }
        .conversion-form{
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        .conversion-form input,.conversion-form select,.conversion-form button{
            padding: 8px;
            font-size: 14px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .conversion-form button{
            background-color: #333;
            color: white;
            border: none;
            cursor: pointer;
        }
        .conversion-form button:hover{
            background-color: #555;
        }
        .conversion-result{
            margin-top: 10px;
            font-weight: bold;
        }
        .substitution-form{
            margin-top: 10px;
        }
        .substitution-form select,.substitution-form button{
            padding: 8px;
            font-size: 14px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .substitution-form button{
            background-color: #333;
            color: white;
            border: none;
            cursor: pointer;
            margin-left: 10px;
        }
        .substitution-form button:hover{
            background-color: #555;
        }
        .substitution-result{
            margin-top: 10px;
        }
        .print-form{
            margin-top: 10px;
            display: flex;
            gap: 10px;
        }
        .print-form select,.print-form textarea,.print-form button{
            padding: 8px;
            font-size: 14px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .print-form button{
            background-color: #333;
            color: white;
            border: none;
            cursor: pointer;
        }
        .print-form button:hover{
            background-color: #555;
        }
        .print-preview{
            margin-top: 10px;
            display: none;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: white;
        }
        @media print{
            body * {
                visibility: hidden;
            }
            .print-preview,.print-preview * {
                visibility: visible;
            }
            .print-preview{
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
            img, .ad-placeholder{
                display: none !important;
            }
            .condensed .print-details p{
                margin: 5px 0;
                font-size: 12px;
            }
            .full .print-details p{
                margin: 10px 0;
                font-size: 14px;
            }
        }
        .rating-section{
            margin-top: 20px;
        }
        .rating-section h3{
            margin: 0 0 10px;
            font-size: 18px;
        }
        .rating-section form{
            margin-bottom: 10px;
        }
        .rating-section select, .rating-section textarea{
            padding: 8px;
            font-size: 16px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-right: 10px;
        }
        .rating-section button{
            padding: 8px 15px;
            background-color: #333;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }
        .rating-section button:hover{
            background-color: #555;
        }
        .rating-list{
            list-style: none;
            padding: 0;
        }
        .rating-list li{
            margin: 5px 0;
            color: #666;
        }
        .home-link{
            display: block;
            text-align: center;
            margin-top: 20px;
            text-decoration: none;
            color: #333;
            font-weight: bold;
        }
        .home-link:hover{
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
        <div class="recipe-detail">
            <h2><?php echo htmlspecialchars($recipe['title']); ?></h2>
            <img src="<?php echo htmlspecialchars($recipe['image']); ?>" alt="<?php echo htmlspecialchars($recipe['title']); ?>">
            <p><strong>Cuisine:</strong> <?php echo htmlspecialchars($recipe['cuisine']); ?></p>
            <p><strong>Meal Type:</strong> <?php echo htmlspecialchars($recipe['meal_type']); ?></p>
            <p><strong>Servings:</strong> <?php echo htmlspecialchars($recipe['servings']); ?></p>
            <p><strong>Description:</strong> <?php echo htmlspecialchars($recipe['description']); ?></p>
            <p><strong>Details:</strong> <?php echo nl2br(htmlspecialchars($recipe['details'])); ?></p>
            <!-- Ingredients Section -->
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
                    <form class="new-timer-form" method="post" action="">
                        <input type="text" name="new_timer_label" placeholder="Timer name (e.g., Step 1)" maxlength="100">
                        <input type="number" name="new_timer_duration" placeholder="Duration (minutes)" min="1" required>
                        <button type="submit">Add Timer</button>
                    </form>
                <?php else: ?>
                    <p>Please <a href="login.php">login</a> to add timers.</p>
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
            <div class="print-section">
                <h3>Print-Friendly View</h3>
                <form class="print-form" id="printForm" onsubmit="updatePrintPreview(event)">
                    <select id="layoutSelector" onchange="updatePrintPreview()">
                        <option value="full">Full Layout</option>
                        <option value="condensed">Condensed Layout</option>
                    </select>
                    <textarea id="customNotes" placeholder="Add personal notes here..." rows="3"></textarea>
                    <button type="submit">Update Preview</button>
                    <button type="button" onclick="window.print()">Print</button>
                </form>
                <div class="print-preview" id="printPreview">
                    <div class="print-details">
                        <h2><?php echo htmlspecialchars($recipe['title']); ?></h2>
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
                        <p><strong>Average Rating:</strong> <?php echo $average_rating ? number_format($average_rating, 1) : 'N/A'; ?> / 5</p>
                        <?php if (!empty($ratings)): ?>
                            <h3>Ratings</h3>
                            <ul>
                                <?php foreach ($ratings as $rating): ?>
                                    <li><?php echo htmlspecialchars($rating['username']); ?>: <?php echo $rating['rating']; ?> / 5</li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p>No ratings yet.</p>
                        <?php endif; ?>
                        <p id="printNotes"></p>
                    </div>
                </div>
            </div>
            <div class="rating-section">
                <h3>Ratings & Reviews</h3>
                <p><strong>Average Rating:</strong> <?php echo $average_rating ? number_format($average_rating, 1) : 'N/A'; ?> / 5</p>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <form method="post" action="">
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
                    <p>Please <a href="login.php">login</a> to rate this recipe.</p>
                <?php endif; ?>
                <?php if (!empty($error)): ?>
                    <p style="color: red;"><?php echo $error; ?></p>
                <?php endif; ?>
                <?php if (!empty($ratings)): ?>
                    <ul class="rating-list">
                        <?php foreach ($ratings as $rating): ?>
                            <li><?php echo htmlspecialchars($rating['username']); ?>: <?php echo $rating['rating']; ?> / 5</li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>No ratings yet.</p>
                <?php endif; ?>
            </div>
            <a href="home.php" class="home-link">Back to Home</a>
        </div>
    </div>
    <script>
        // Debug logging to check if data is loaded
        console.log('Recipe Data:', <?php echo json_encode($recipe); ?>);
        console.log('Timers:', <?php echo json_encode($timers); ?>);
        console.log('Ratings:', <?php echo json_encode($ratings); ?>);

        const timers = {};

        // Initialize existing timers
        document.querySelectorAll('.timer-item').forEach(item => {
            const id = item.dataset.id;
            timers[id] = {
                duration: parseInt(item.dataset.duration), // Duration is in seconds
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
            button.addEventListener('click', function() {
                const id = this.dataset.id;
                if (!timers[id].isRunning) {
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
                    timers[id].isRunning = true;
                    this.disabled = true;
                    document.querySelector(`.pauseTimer[data-id="${id}"]`).disabled = false;
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

        // Unit Conversion Logic
        function convertUnit(event) {
            event.preventDefault();
            const quantity = parseFloat(document.getElementById('quantity').value);
            const fromUnit = document.getElementById('fromUnit').value;
            const toUnit = document.getElementById('toUnit').value;
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
                result = quantity * (conversions[fromUnit][toUnit]);
            } else {
                const intermediate = quantity * conversions[fromUnit].ml;
                result = intermediate / conversions[toUnit].ml;
            }

            document.getElementById('conversionResult').textContent = `${quantity} ${fromUnit} = ${result.toFixed(2)} ${toUnit}`;
        }

        // Ingredient Substitution Logic
        function suggestSubstitution(event) {
            event.preventDefault();
            const ingredient = document.getElementById('ingredient').value;
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

        // Print-Friendly View Logic
        function updatePrintPreview(event) {
            if (event) event.preventDefault();
            const layout = document.getElementById('layoutSelector').value;
            const notes = document.getElementById('customNotes').value;
            const preview = document.getElementById('printPreview');
            const printNotes = document.getElementById('printNotes');

            preview.className = `print-preview ${layout}`;
            printNotes.textContent = notes || "No notes added.";
            preview.style.display = 'block';
        }

        // Initial preview load
        window.onload = function() {
            updatePrintPreview();
        };
    </script>
</body>
</html>