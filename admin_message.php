<?php
$conn = new mysqli("localhost", "root", "", "db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$result = $conn->query("SELECT * FROM contact_messages ORDER BY created_at DESC");

echo "<h2>Contact Messages</h2>";
while ($row = $result->fetch_assoc()) {
    echo "<p><strong>{$row['name']} ({$row['email']})</strong><br>{$row['message']}<br><small>{$row['created_at']}</small></p><hr>";
}

$conn->close();
?>
