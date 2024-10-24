<?php
require_once '../src/functions.php';

$session_id = get_custom_session_id();

if (!is_admin($session_id)) {
    header('Location: index.php');
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Recipe</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        h1, p {
            color: #333;
        }
        .recipe {
            margin-top: 20px;
            background: #fff;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        form {
            margin-top: 20px;
        }
        button {
            background-color: #0084ff;
            color: white;
            border: none;
            padding: 10px 20px;
            text-transform: uppercase;
            cursor: pointer;
            border-radius: 5px;
        }
        button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="recipe">
    <h2>Cookie Recipe</h2>
        <p>Ingredients:</p>
        <ul>
            <li>1 teaspoon of baking powder</li>
            <li>1 teaspoon of salt</li>
            <li>100 g of dark chocolate</li>
            <li>150 g of flour</li>
            <li>1 packet of vanilla sugar</li>
            <li>85 g of sugar</li>
            <li>85 g of soft butter</li>
            <li>1 egg</li>
            <li>ECW{S@cUr3_yOur_C0ok13s_</li>
        </ul>
        <p>Preparation:</p>
        <ol>
            <li>Chop the chocolate into chips.</li>
            <li>Preheat the oven to 180°C (thermostat 6). In a bowl, put 75 g of butter, the sugar, the whole egg, the vanilla, and mix everything together with a wooden spoon.</li>
            <li>Gradually add the flour mixed with the baking powder, salt, and chocolate.</li>
            <li>Using a piece of kitchen paper, butter a baking tray and form the cookies on the tray.</li>
            <li>To shape the cookies, use 2 tablespoons and make small mounds spaced apart from each other; they will grow during baking.</li>
            <li>Bake for 10 minutes.</li>
        </ol>
    </div>
    <a href="admin.php">Back to Home</a>
</body>
</html>

Back to Profile