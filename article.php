<?php
// Include session check if needed
// session_start();
// if(!isset($_SESSION['loggedin']) || isset($_SESSION['loggedin'])!=true){
//     header("location:login.php");
//     exit;
// }
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>NEWSILO - Article</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="nav.css">
    <style>
      .article-container {
        margin-top: 80px;
        margin-bottom: 50px;
      }
      .article-image {
        max-height: 400px;
        object-fit: cover;
        width: 100%;
        margin-bottom: 20px;
      }
      .article-metadata {
        margin-bottom: 20px;
        color: #6c757d;
      }
      .article-content {
        line-height: 1.8;
        font-size: 1.1rem;
      }
      .back-button {
        margin-top: 30px;
      }
    </style>
  </head>
  <body>
    <?php
      include "navbar.php";
      include "news_functions.php";
      
      // Get article ID from URL
      $article_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
      
      if ($article_id <= 0) {
        header("Location: welcome.php");
        exit;
      }
      
      // Get specific article by ID (You need to add this function to news_functions.php)
      $article = getArticleById($article_id);
      
      if (!$article || isset($article['error'])) {
        header("Location: welcome.php");
        exit;
      }
    ?>
    
    <div class="container article-container">
      <div class="row">
        <div class="col-md-8 offset-md-2">
          <h1><?php echo htmlspecialchars($article['title']); ?></h1>
          
          <div class="article-metadata">
            <span class="source"><?php echo htmlspecialchars($article['source']); ?></span> | 
            <span class="date"><?php echo date('F j, Y', strtotime($article['published_date'])); ?></span>
            <?php if (!empty($article['author'])): ?>
              | <span class="author">By <?php echo htmlspecialchars($article['author']); ?></span>
            <?php endif; ?>
          </div>
          
          <?php if (!empty($article['image_url'])): ?>
            <img src="<?php echo htmlspecialchars($article['image_url']); ?>" 
                 alt="<?php echo htmlspecialchars($article['title']); ?>" 
                 class="article-image"
                 onerror="this.src='images/default-news.jpg';">
          <?php endif; ?>
          
          <div class="article-content">
            <?php 
              if (!empty($article['content_snippet'])) {
                echo '<p>' . nl2br(htmlspecialchars($article['content_snippet'])) . '</p>';
              } else if (!empty($article['summary'])) {
                echo '<p>' . nl2br(htmlspecialchars($article['summary'])) . '</p>';
              } else {
                echo '<p>Full article content is not available.</p>';
              }
            ?>
            
            <div class="alert alert-info mt-4">
              <p>This is a news aggregator. To read the full article, please visit the original source:</p>
              <a href="<?php echo htmlspecialchars($article['url']); ?>" class="btn btn-primary" target="_blank">Read Full Article at <?php echo htmlspecialchars($article['source']); ?></a>
            </div>
          </div>
          
          <div class="back-button">
            <a href="javascript:history.back()" class="btn btn-outline-secondary">← Back to News</a>
          </div>
        </div>
      </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
  </body>
</html>