<?php

$token = $_GET["token"];

$token_hash = hash("sha256", $token);

// Include the connection file
include_once __DIR__ . "/connection.php";

// Debug information
error_log("Accessing reset password page with token hash: " . $token_hash);

$sql = "SELECT * FROM users
        WHERE reset_token_hash = ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    error_log("SQL Error in prepare: " . $conn->error);
    die("Error preparing statement: " . $conn->error);
}

$stmt->bind_param("s", $token_hash);
$stmt->execute();

$result = $stmt->get_result();

$user = $result->fetch_assoc();

if ($user === null) {
    error_log("Token not found: " . $token_hash);
    die("token not found");
}

error_log("User found: " . $user["id"]);

if (strtotime($user["reset_token_expires_at"]) <= time()) {
    error_log("Token expired for user: " . $user["id"]);
    die("token has expired");
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/water.css@2/out/water.css">
    <style>
        body {
            background-color: #000;
            color: #fff;
        }
        input, button {
            background-color: #222;
            color: #fff;
            border: 1px solid #444;
        }
        button {
            background-color: #333;
        }
        button:hover {
            background-color: #444;
        }
        a {
            color: #ccc;
        }
        h1 {
            color: #fff;
        }
    </style>
</head>
<body>

    <h1>Reset Password</h1>

    <form method="post" action="process-reset-password.php">

        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

        <label for="password">New password</label>
        <input type="password" id="password" name="password">

        <label for="password_confirmation">Repeat password</label>
        <input type="password" id="password_confirmation"
               name="password_confirmation">

        <button>Send</button>
    </form>

</body>
</html>