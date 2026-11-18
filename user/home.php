<?php


include __DIR__ . '/../config.php';

session_start();

$user_id = $_SESSION['user_id'] ?? null;

if(!isset($user_id)){
   header('location:login.php');
   exit;
}
?>
<!DOCTYPE html>
<html lang="en" class="page-home">

<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <link rel="stylesheet" href="style.css">
   <title>Home</title>
</head>

<body>
<div class="menu">
   <a href="category.php">
    <button type="button">MENU</button>
   </a>
  </div>
  <div class="history">
   <a href="order_history.php">
    <button type="button">HISTORY</button>
   </a>
  </div>

</body>

</html>
