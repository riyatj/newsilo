<?php
session_start();
if(!isset($_SESSION['loggedin']) || $_SESSION['loggedin']!=true){
    header("location:login.php");
    exit;
}

// Include the news functions
include "connection.php";
include "news_functions.php";

// Get the category from the URL parameter
$category = isset($_GET['category']) ? $_GET['category'] : 'technology';
$category = strtolower($category);

// Map URL parameter to proper category name
$categoryMap = [
    'tech' => 'technology',
    'entertainment' => 'entertainment',
    'politics' => 'politics',
    'edu' => 'education',
    'sports' => 'sports',
    'travel' => 'travel'
];

if (isset($categoryMap[$category])) {
    $category = $categoryMap[$category];
}

// Validate category
$validCategories = ['technology', 'entertainment', 'politics', 'education', 'sports', 'travel'];
if (!in_array($category, $validCategories)) {
    $category = 'technology';  // Default to technology if invalid
}

// Fetch articles for the selected category
$articles = fetchNews($category, null, 20, 0);

// Format the category name for display
$categoryName = ucfirst($category);

// Check if the news service is available
$serviceAvailable = isNewsServiceAvailable();
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $categoryName ?> News - NEWSILO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="nav.css">
    <style>
        .category-header {
            background-color: #f8f9fa;
            padding: 20px 0;
            margin-bottom: 30px;
            border-bottom: 1px solid #e9ecef;
        }
        .service-alert {
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <?php include "navbar.php"; ?>
    
    <div class="container mt-4">
        <div class="category-header">
            <h1 class="text-center"><?= $categoryName ?> News</h1>
        </div>
        
        <?php if (!$serviceAvailable): ?>
        <div class="alert alert-warning service-alert" role="alert">
            <strong>News service is currently unavailable.</strong> We're working to restore it as soon as possible.
        </div>
        <?php endif; ?>
        
        <div class="row">
            <?php 
            if (isset($articles['error'])) {
                echo '<div class="col-12"><div class="alert alert-danger">' . $articles['error'] . '</div></div>';
            } elseif (empty($articles)) {
                echo '<div class="col-12"><div class="alert alert-info">No news articles found for this category.</div></div>';
            } else {
                foreach ($articles as $article) {
                    echo renderNewsCard($article);
                }
            }
            ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
</body>
</html>
