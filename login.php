<?php
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    include "db.php";
    if($_SERVER['REQUEST_METHOD']=='POST')
    {
        $email= $_POST['email'];
        $password= $_POST['password'];
        $check = $conn->prepare("SELECT email,password FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();
        
        if ($check->num_rows === 0) {
            echo "<script>alert('User ID Not Found!');
            window.location.href='login.php'; </script>";
            exit;
        }
        $check->bind_result($db_email, $db_password);
        $check->fetch();

        if(password_verify($password, $db_password)){
            session_start();
            $_SESSION['userId'] = $db_email;
            echo "<script>alert('Login Successful');
            window.location.href='dashboard.php'; </script>";
            exit;
        } else {
            echo "<script>alert('Incorrect Password!');
            window.location.href='login.php'; </script>";
            exit;
        }
    }
?>
<html>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | AI Mock Interview</title>
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <div class="header">
        <div class="logo">Ai Mock Interview</div>
        <button class="home-button" onclick="location.href='index.html'">Home</button>
    </div>

    <div class="container">
        <form class="form" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']);?>">
            <p class="title">Login</p>
            <p class="message">Sign in to continue.</p>

            <label>
                <input required="" placeholder="" type="email" class="input" name=email>
                <span>Email</span>
            </label>

            <label>
                <input required="" placeholder="" type="password" class="input" name=password>
                <span>Password</span>
            </label>

            <button class="submit">Login</button>
            <p class="signin">Don't have an account? <a href="register.php">Sign up</a></p>
        </form>
    </div>
</body>
</html>
