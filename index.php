<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>NEWSILO - Home</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="nav.css">
    <style>
      .news-section {
        margin-top: 40px;
        margin-bottom: 50px;
      }
      .news-section h2 {
        margin-bottom: 30px;
        text-align: center;
        font-weight: bold;
        color: white;
        background-color: black;
        padding-top : 40px;
        padding-bottom : 30px;
      }
      .view-more {
        text-align: center;
        margin-top: 20px;
      }
      .background {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: -1;
      }
      .news-container {
        background-color: rgba(255, 255, 255, 0.9);
        border-radius: 10px;
        padding: 20px;
      }
    </style>
  </head>
  
  <body>
    <?php 
      include "indexnav.php";
      include "news_functions.php";
    ?>
    <div class="background"></div>
    
    <div class="container news-section">
      <h2>Trending News Headlines</h2>
      <div class="news-container">
        <div class="row">
          <?php 
          // Check if the news service is available
          if (isNewsServiceAvailable()) {
            $latest_news = fetchNews(null, null, 6, 0);
            if (!isset($latest_news['error'])) {
              foreach ($latest_news as $article) {
                echo renderNewsCard($article);
              }
            } else {
              echo '<div class="col-12"><div class="alert alert-info">News headlines are currently unavailable: ' . $latest_news['error'] . '</div></div>';
            }
          } else {
            echo '<div class="col-12"><div class="alert alert-warning">News service is not available. Please try again later.</div></div>';
          }
          ?>
        </div>
        <div class="view-more">
          <a href="signup.php" class="btn btn-dark">View More News</a>
        </div>
      </div>
    </div>
    <?php 
      include "footer.php";
    ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
  </body>
</html>