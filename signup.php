<?php
    session_start();
    if(isset($_SESSION['username'])){
        header("Location: welcome.php");
    }
?>
<?php
    include("connection.php");
    
    // Initialize variables to preserve form data
    $username = "";
    $email = "";
    $error_message = "";
    $error_field = "";
    
    if(isset($_POST['submit'])){
        $username = mysqli_real_escape_string($conn, $_POST['user']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $password = mysqli_real_escape_string($conn, $_POST['pass']);
        $cpassword = mysqli_real_escape_string($conn, $_POST['cpass']);
        
        $sql="select * from users where username='$username'";
        $result = mysqli_query($conn, $sql);
        $count_user = mysqli_num_rows($result);

        $sql="select * from users where email='$email'";
        $result = mysqli_query($conn, $sql);
        $count_email = mysqli_num_rows($result);

        if($count_user == 0 && $count_email==0){
            if($password==$cpassword){
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $sql = "INSERT INTO users(username, email, password) VALUES('$username', '$email', '$hash')";
                $result = mysqli_query($conn, $sql);
                if($result){
                    header("Location: login.php");
                }
            }
            else{
                // Passwords don't match - keep username and email
                $error_message = "Passwords do not match";
                $error_field = "pass";
            }
        }
        else{
            if($count_user>0){
                // Username exists - clear username, keep email
                $error_message = "Username already exists!!";
                $error_field = "user";
                $username = "";
            }
            if($count_email>0){
                // Email exists - clear email, keep username
                $error_message = "Email already exists!!";
                $error_field = "email";
                $email = "";
            }
        }
    }
?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SignUp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
  <body class="bg">
  <h1>SIGN UP TO NEWSILO</h1>
  <br>
    <div id="form">
        <h1 id="heading">SignUp Form</h1>
        
        <!-- Display error message if exists -->
        <?php if(!empty($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <form name="form" action="signup.php" method="POST">
            <label>Enter Username: </label>
            <input type="text" id="user" name="user" value="<?php echo htmlspecialchars($username); ?>" required><br><br>
            <label>Enter Email: </label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required><br><br>
            <label>Create Password: </label>
            <input type="password" id="pass" name="pass" required><br><br>
            <label>Retype Password: </label>
            <input type="password" id="cpass" name="cpass" required><br><br>
            <input type="submit" id="btn" value="SignUp" name = "submit"/>
            <p class="dot"><b>----------or----------</b></p>
                <div class="icons">
                    <i class="fab fa-google"></i>
                    <i class="fab fa-facebook"></i>
                    <i class="fab fa-instagram"></i>
                </div>
                <div class="links">
                    <p>Already exist an account?</p>
                    <a href="login.php" id="loginButton" class="btnSign">LogIn</a>
                </div>
        </form>
    </div> 
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
    <script>
        // Focus on the error field if specified
        <?php if(!empty($error_field)): ?>
            document.getElementById('<?php echo $error_field; ?>').focus();
            document.getElementById('<?php echo $error_field; ?>').value = '';
        <?php endif; ?>
    </script>
  </body>
</html>