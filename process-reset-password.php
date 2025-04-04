<?php

$token = $_POST["token"];
$token_hash = hash("sha256", $token);

// Get a fresh connection using the function
require_once __DIR__ . "/connection.php";
$conn = getConnection();

// Debug
error_log("Processing password reset for token hash: " . $token_hash);

$sql = "SELECT * FROM users WHERE reset_token_hash = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    error_log("Prepare failed: " . $conn->error);
    die("SQL Error: " . $conn->error);
}

$stmt->bind_param("s", $token_hash);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user === null) {
    error_log("Token not found");
    die("Token not found");
}

error_log("Found user with ID: " . $user["id"]);

if (strtotime($user["reset_token_expires_at"]) <= time()) {
    error_log("Token expired");
    die("Token has expired");
}

if (strlen($_POST["password"]) < 8) {
    die("Password must be at least 8 characters");
}

if (!preg_match("/[a-z]/i", $_POST["password"])) {
    die("Password must contain at least one letter");
}

if (!preg_match("/[0-9]/", $_POST["password"])) {
    die("Password must contain at least one number");
}

if ($_POST["password"] !== $_POST["password_confirmation"]) {
    die("Passwords must match");
}

$password_hash = password_hash($_POST["password"], PASSWORD_DEFAULT);

error_log("Updating password for user: " . $user["id"]);

$sql = "UPDATE users 
        SET password = ?, 
            reset_token_hash = NULL, 
            reset_token_expires_at = NULL 
        WHERE id = ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    error_log("Update prepare failed: " . $conn->error);
    die("SQL Error. Check logs.");
}

$stmt->bind_param("si", $password_hash, $user["id"]);
$result = $stmt->execute();

if ($result) {
    error_log("Password updated successfully");
    // Show success message and link to login page
    echo "Password updated successfully. <a href='login.php'>Click here to login</a>";
} else {
    error_log("Password update failed: " . $conn->error);
    die("Failed to update password: " . $conn->error);
}