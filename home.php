<?php
session_start();

// Include news functions
require_once 'news_functions.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Check if news service is available
$newsServiceAvailable = isNewsServiceAvailable();

// Fetch news (with error handling)
$news_items = $newsServiceAvailable 
    ? fetchNews(null, null, 6) 
    : [];

// Fallback if no news or service unavailable
if (!$newsServiceAvailable || empty($news_items)) {
    $news_items = [
        [
            'id' => 1,
            'title' => 'News Service Temporarily Unavailable',
            'summary' => 'We are experiencing technical difficulties. Please try again later.',
            'source' => 'Newsilo System',
            'url' => '#',
            'image_url' => 'images/default-news.jpg',
            'published_date' => date('Y-m-d H:i:s')
        ]
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NEWSILO - Home</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f4f4;
        }
        .news-section {
            padding: 50px 0;
        }
        .card {
            transition: transform 0.3s ease;
        }
        .card:hover {
            transform: scale(1.03);
        }
        .view-more-btn {
            background-color: #000;
            color: #fff;
        }
        .view-more-btn:hover {
            background-color: #333;
            color: #fff;
        }
    </style>
</head>
<body>
    <!-- Include Navbar -->
    <?php include 'navbar.php'; ?>

    <div class="container news-section">
        <h1 class="text-center mb-5">Latest News</h1>
        
        <div class="row">
            <?php 
            // Limit to 6 news items on home page
            $limited_news = array_slice($news_items, 0, 6); 
            foreach($limited_news as $news): 
                echo renderNewsCard($news);
            endforeach; 
            ?>
        </div>

        <div class="text-center mt-4">
            <a href="signup.php" class="btn view-more-btn btn-lg">View More News</a>
        </div>
    </div>

    <!-- Include Footer -->
    <?php include 'footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 