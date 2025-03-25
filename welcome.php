<?php
    session_start();
    if(!isset($_SESSION['loggedin']) || isset($_SESSION['loggedin'])!=true){
        header("location:login.php");
        exit;
    }
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>NEWSILO - Your News Aggregator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="nav.css">
    <style>
      .news-section {
        margin-top: 50px;
        margin-bottom: 50px;
      }
      .news-section h2 {
        margin-bottom: 30px;
        text-align: center;
        font-weight: bold;
      }
      .view-more {
        text-align: center;
        margin-top: 20px;
      }
    </style>
</head>
  <body>
  <?php
    include "navbar.php";
    ?>
    <br><br><br>
    
    <div id="form_quote">
        <p class="quote">ALL NEW NEWS IS OLD NEWS HAPPENING TO NEW PEOPLES.<p>
          <p class="author">- Malcolm Muggeridge</p>
    </div>
    
    <?php include "news_functions.php"; ?>
    <div class="container news-section">
        <h2>Latest News Headlines</h2>
        <div class="row">
            <?php 
            $latest_news = fetchNews(null, null, 6, 0);
            if (!isset($latest_news['error'])) {
                foreach ($latest_news as $article) {
                    echo renderNewsCard($article);
                }
            } else {
                echo '<div class="col-12"><div class="alert alert-info">News headlines are currently unavailable. Please check back later.</div></div>';
            }
            ?>
        </div>
        <div class="view-more">
            <a href="category.php?category=technology" class="btn btn-dark">View More News</a>
        </div>
    </div>
    <?php 
      include "footer.php";
    ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
  </body>
</html>
