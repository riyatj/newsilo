<?php
include('connection.php');

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Check if the token exists
    $sql = "SELECT * FROM users WHERE reset_token='$token'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) > 0) {
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $new_password = password_hash($_POST["password"], PASSWORD_DEFAULT);

            // Update password
            $sql = "UPDATE users SET password='$new_password', reset_token=NULL WHERE reset_token='$token'";
            mysqli_query($conn, $sql);

            echo "Your password has been reset. <a href='login.php'>Login here</a>";
            exit();
        }
    } else {
        echo "Invalid or expired token.";
        exit();
    }
} else {
    echo "No token provided.";
    exit();
}
?>

<form action="" method="post">
    <label for="password">New Password:</label>
    <input type="password" name="password" required>
    <input type="submit" value="Reset Password">
</form>
