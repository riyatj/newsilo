<?php
$conn = new mysqli("localhost", "root", "", "db");

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (!empty($query)) {
    $query = $conn->real_escape_string($query);
    $sql = "SELECT title, url FROM news_articles 
            WHERE title LIKE CONCAT('%', ?, '%') 
            OR summary LIKE CONCAT('%', ?, '%') 
            ORDER BY published_date DESC LIMIT 10";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $query, $query);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "<ul class='list-group'>";
        while ($row = $result->fetch_assoc()) {
            echo "<li class='list-group-item'><a href='" . $row['url'] . "' target='_blank'>" . $row['title'] . "</a></li>";
        }
        echo "</ul>";
    } else {
        echo "<p class='text-muted'>No results found.</p>";
    }
    $stmt->close();
}
$conn->close();
?>
