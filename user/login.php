<?php
session_start();
include __DIR__ . '/../config.php';

$error = $success = $email = $pass = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $pass = $_POST['password'];

    if (empty($email) || empty($pass)) {
        $error = "All fields are required";
    } else {
        function validate($data)
        {
            $data = trim($data);
            $data = stripslashes($data);
            $data = htmlspecialchars($data);
            return $data;
        }

       
        $email = validate($email);
        $pass = validate($pass);

        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format";
        } else {
           
            $sql = "SELECT * FROM users WHERE email='$email' AND password='$pass'";
            $result = mysqli_query($conn, $sql);

            if (mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_assoc($result);
            
                $_SESSION['email'] = $email;
                $_SESSION['user_id'] = $row['id'];
                header("Location:home.php");
                exit(); 
        

            } else {
                $error = 'User not found';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="page-login">

<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <link rel="stylesheet" href="style.css">

   <title>Login</title>
</head>

<body>
   <div class="form-container">
      <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
         <h3>LOGIN</h3>
         <?php if (!empty($error)) { ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
         <?php } ?>

         <input type="email" name="email" placeholder="Enter your email" class="box" value="<?php echo htmlspecialchars($email); ?>">
         <input type="password" name="password" placeholder="Enter your password" class="box">
         <input type="submit" name="submit" value="login" class="btn">
         <p>Don't have an account? <a href="register.php">Register Now</a></p>
      </form>
   </div>
</body>

</html>
