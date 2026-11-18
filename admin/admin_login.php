<?php
session_start();
include __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $Ucode = $_POST['Ucode'];

    if (empty($Ucode)) {
        $error = "All fields are required";
    } else {
        function validate($data)
        {
            $data = trim($data);
            $data = stripslashes($data);
            $data = htmlspecialchars($data);
            return $data;
        }

        $Ucode = validate($Ucode);

       
        $sql = "SELECT * FROM admin WHERE Ucode='$Ucode'";
        $result = mysqli_query($conn, $sql);

        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);

            $_SESSION['Ucode'] = $Ucode;
            $_SESSION['admin_id'] = $row['id'];
            header("Location: manual_order.php");
            exit(); 
        } else {
            $error = 'Incorrect Ucode';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="page-admin_login">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="form-container">
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <h3>KARESUSHI RESTAURANT</h3>
        <br><br><br><br>
      
        <div class="input-box">
            <label for="LOGIN">LOGIN</label>
            <br>
            <input type="text" name="Ucode" placeholder="Use code" class="box">
        </div>
        <?php if (!empty($error)) { ?>
    <p class="error"><i class='bx bx-x-circle'></i> <?php echo htmlspecialchars($error); ?></p>
<?php } ?>

        <br><br><br><br><br>
        <input type="submit" name="submit" value="Enter" class="btn">
    </form>
</div>

</body>
</html>
