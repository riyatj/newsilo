<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
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

    <h1>Forgot Password</h1>

    <form method="post" action="send-password-reset.php">

        <label for="email">email</label>
        <input type="email" name="email" id="email">

        <button>Send</button>

    </form>

</body>
</html>