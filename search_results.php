<?php
$conn = new mysqli("localhost", "root", "", "db");

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

function renderNewsCard($article) {
    $id = intval($article['id']);
    $title = htmlspecialchars($article['title']);
    $source = htmlspecialchars($article['source']);
    $url = htmlspecialchars($article['url']);
    $summary = isset($article['summary']) ? htmlspecialchars($article['summary']) : '';
    $imageUrl = isset($article['image_url']) && $article['image_url'] 
        ? htmlspecialchars($article['image_url']) 
        : 'images/default-news.jpg';
    
    $date = isset($article['published_date']) 
        ? date('M j, Y', strtotime($article['published_date'])) 
        : '';
    
    return <<<HTML
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <img src="{$imageUrl}" class="card-img-top" alt="{$title}" onerror="this.src='images/default-news.jpg';">
            <div class="card-body">
                <h5 class="card-title">{$title}</h5>
                <p class="card-text text-truncate">{$summary}</p>
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">{$source}</small>
                    <small class="text-muted">{$date}</small>
                </div>
            </div>
            <div class="card-footer bg-transparent border-top-0 d-flex justify-content-between">
                <a href="{$url}" class="btn btn-outline-dark" target="_blank">Read More</a>
            </div>
        </div>
    </div>
HTML;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - Newsilo</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <?php 
      include "navbar.php";
    ?>
    <div class="container mt-4">
        <h2 class="mb-4">Search Results for "<?php echo htmlspecialchars($query); ?>"</h2>

        <div class="row">
        <?php
        if (!empty($query)) {
            $query = $conn->real_escape_string($query);
            $sql = "SELECT id, title, source, url, summary, image_url, published_date FROM news_articles 
                    WHERE title LIKE CONCAT('%', ?, '%') 
                    OR summary LIKE CONCAT('%', ?, '%') 
                    ORDER BY published_date DESC LIMIT 10";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $query, $query);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo renderNewsCard($row);
                }
            } else {
                echo '<p>No results found.</p>';
            }
        }
        ?>
        </div>
    </div>
    <?php 
      include "footer.php";
    ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
