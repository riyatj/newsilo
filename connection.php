<?php
// connection.php
$servername = "localhost";
$username = "root";
$password = "";
$db_name = "db";

function getConnection() {
    global $servername, $username, $password, $db_name;
    $conn = new mysqli($servername, $username, $password, $db_name, 3306);
    if($conn->connect_error){
        die("Connection failed: ".$conn->connect_error);
    }
    return $conn;
}

// For backward compatibility, also create the $conn variable
$conn = new mysqli($servername, $username, $password, $db_name, 3306);
if($conn->connect_error){
    die("Connection failed: ".$conn->connect_error);
}
echo "";
?>