<?php
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    include "db.php";
    


    if($_SERVER['REQUEST_METHOD']=='POST')
    {
    $firstName= $_POST['firstname'];
    $lastName= $_POST['lastname'];
    $email= $_POST['email'];
    $password= $_POST['password'];
    
    $check = $conn->prepare("SELECT email FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        echo "<script>alert('Email already registered!');
        window.location.href='register.php'; </script>";
        exit;
    }


    // Hash password securely
    $hash_password = password_hash($password, PASSWORD_DEFAULT);

    // Use prepared statement for secure insertion
    $stmt = $conn->prepare("INSERT INTO users (firstname, lastname, email, password) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $firstName, $lastName, $email, $hash_password);
    $result = $stmt->execute();

    if ($result)
    {
        echo "<script type='text/javascript'>
        alert('Registration successful!'); 
        window.location.href='login.php'; 
        </script>";
        
    }
     else 
    {
        echo "<script>alert('Registration failed! Please try again.');
        window.location.href='register.php'; 
        </script>";
    }
    }    
?>

<html>
<head>
    <link rel="stylesheet" href="css/register.css">
    <style>
        .error-message {
            color: red;
            font-size: 12px;
            margin-top: 4px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">Ai Mock Interview</div>
        <button class="home-button" onclick="location.href='index.html'">Home</button>
    </div>

    <div class="container">
        <form class="form" id="register-form" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
            <p class="title">Register</p>
            <p class="message">Signup now and get full access to our app. </p>

            <div class="flex">
                <label>
                    <input required type="text" class="input" name=firstname>
                    <span>Firstname</span>
                </label>

                <label>
                    <input required type="text" class="input" name=lastname>
                    <span>Lastname</span>
                </label>
            </div>

            <label>
                <input required id="email" type="email" class="input" name=email>
                <span>Email</span>
                <div id="email-error" class="error-message"></div>
            </label>

            <label>
                <input required id="password" type="password" class="input" name=password>
                <span>Password</span>
                <div id="password-error" class="error-message"></div>
            </label>

            <label>
                <input required id="confirm-password" type="password" class="input" name=confirmpassword>
                <span>Confirm password</span>
                <div id="confirm-error" class="error-message"></div>
            </label>

            <button class="submit" type="submit">Register</button>
            <p class="signin">Already have an account? <a href="login.php">Sign in</a> </p>
        </form>
    </div>

    <script>
        const emailInput = document.getElementById("email");
        const passwordInput = document.getElementById("password");
        const confirmPasswordInput = document.getElementById("confirm-password");

        const emailError = document.getElementById("email-error");
        const passwordError = document.getElementById("password-error");
        const confirmError = document.getElementById("confirm-error");

        emailInput.addEventListener("input", () => {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(emailInput.value.trim())) {
                emailError.textContent = "Please enter a valid email.";
            } else {
                emailError.textContent = "";
            }
        });

        passwordInput.addEventListener("input", () => {
            if (passwordInput.value.length < 6) {
                passwordError.textContent = "Password must be at least 6 characters.";
            } else {
                passwordError.textContent = "";
            }

            // Check match on password change
            if (confirmPasswordInput.value && passwordInput.value !== confirmPasswordInput.value) {
                confirmError.textContent = "Passwords do not match.";
            } else {
                confirmError.textContent = "";
            }
        });

        confirmPasswordInput.addEventListener("input", () => {
            if (passwordInput.value !== confirmPasswordInput.value) {
                confirmError.textContent = "Passwords do not match.";
            } else {
                confirmError.textContent = "";
            }
        });
    </script>
</body>
</html>
