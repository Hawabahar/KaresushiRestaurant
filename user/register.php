<?php
session_start();
include __DIR__ . '/../config.php';

$error = $success = $email = $Fname = $Lname = $phone = $pass = $cpass =  "";

function validate($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function hasSpecialChar($str) {
    
    return preg_match('/[\'^£$%&*()}{@#~?><>,|=_+¬-]/', $str);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $Fname = $_POST['first_name'];
    $Lname = $_POST['last_name'];
    $pass = $_POST['password'];
    $cpass = $_POST['cpassword'];
    $email = $_POST['email'];
    $phone = $_POST['mobile_phone'];

    // Validate the input fields
    $Fname = validate($Fname);
    $Lname = validate($Lname);
    $pass = validate($pass);
    $cpass = validate($cpass);
    $email = validate($email);
    $phone = validate($phone);

    if (empty($Fname) || empty($Lname) || empty($pass) || empty($cpass) || empty($email) || empty($phone)) {
        $error = "All fields are required";
    } else {
        
        if ($pass !== $cpass) {
            $error = "The confirmation password does not match";
        } else {
            
            $sql = "SELECT * FROM users WHERE email='" . mysqli_real_escape_string($conn, $email) . "'";
            $result = mysqli_query($conn, $sql);

            if (mysqli_num_rows($result) > 0) {
                $error = "The email is already registered. Please try another.";
            } else {
               
                if (strlen($phone) > 10) {
                    $error = "Phone number should not exceed 10 characters";
                } else {
                    
                    if (!preg_match('/[A-Z]/', $pass) || !preg_match('/[0-9]/', $pass) || !hasSpecialChar($pass)) {
                        $error = "Password should contain at least one capital letter, one number, and one special character.";
                    } else {
                        
                        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $error = "Invalid email format";
                        } else {
                           
                            $sql2 = "INSERT INTO users (first_name, last_name, email, password, mobile_phone) VALUES ('$Fname', '$Lname', '$email', '$pass', '$phone')";
                            $result2 = mysqli_query($conn, $sql2);

                            if ($result2) {
                                $success = "Account Successfully Created!";
                            } else {
                                $error = "Unknown error occurred";
                            }
                        }
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="page-register">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   
   <title>Register</title>

   <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="form-container">
   <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
      <h3>REGISTER NOW</h3>
      <?php if (!empty($error)) { ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
      <?php } ?>

      <?php if (!empty($success)) { ?>
        <p class="success"><?php echo htmlspecialchars($success); ?></p>
      <?php } ?>

      <input type="text" name="first_name" placeholder="Enter your first name" class="box" value="<?php echo htmlspecialchars($Fname); ?>">
      <input type="text" name="last_name" placeholder="Enter your last name" class="box" value="<?php echo htmlspecialchars($Lname); ?>">
      <input type="text" name="email" placeholder="Enter your email" class="box" value="<?php echo htmlspecialchars($email); ?>">
      <input type="password" name="password" placeholder="Enter your password" class="box">
      <input type="password" name="cpassword" placeholder="Confirm your password" class="box">
      <input type="tel" name="mobile_phone" placeholder="Enter your mobile phone" class="box" value="<?php echo htmlspecialchars($phone); ?>">
      <input type="submit" name="submit" value="Register" class="btn">
      <p>Already have an account? <a href="login.php">Login Now</a></p>
   </form>
</div>
</body>
</html>
