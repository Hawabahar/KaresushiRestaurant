<?php

session_start();
if (!isset($_SESSION['admin_id'])) {
   header('Location: admin_login.php');
   exit;
}
include __DIR__ . '/../config.php';
$admin_id = (int)$_SESSION['admin_id'];
$manual_table = (int)($_SESSION['manual_order_table'] ?? 0);



if(isset($_POST['update_cart'])){
   $cart_id = filter_input(INPUT_POST, 'cart_id', FILTER_VALIDATE_INT);
   $cart_quantity = filter_input(INPUT_POST, 'cart_quantity', FILTER_VALIDATE_INT);
   if ($cart_id && $cart_quantity && $cart_quantity >= 1 && $cart_quantity <= 99) {
      $stmt = mysqli_prepare($conn, 'UPDATE cart SET quantity = ? WHERE id = ? AND admin_id = ?');
      mysqli_stmt_bind_param($stmt, 'iii', $cart_quantity, $cart_id, $admin_id);
      mysqli_stmt_execute($stmt);
      header('Location: cart.php');
      exit;
   }
}

if(isset($_GET['delete'])){
   $delete_id = filter_input(INPUT_GET, 'delete', FILTER_VALIDATE_INT);
   if ($delete_id) {
      $stmt = mysqli_prepare($conn, 'DELETE FROM cart WHERE id = ? AND admin_id = ?');
      mysqli_stmt_bind_param($stmt, 'ii', $delete_id, $admin_id);
      mysqli_stmt_execute($stmt);
   }
   header('Location: cart.php');
   exit;
}

if(isset($_GET['delete_all'])){
   $stmt = mysqli_prepare($conn, 'DELETE FROM cart WHERE admin_id = ?');
   mysqli_stmt_bind_param($stmt, 'i', $admin_id);
   mysqli_stmt_execute($stmt);
   header('Location: cart.php');
   exit;
}

?>

<!DOCTYPE html>
<html lang="en" class="page-cart">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>cart</title>

 
   <link rel="stylesheet" href="style.css?v=<?php echo filemtime(__DIR__ . '/style.css'); ?>">

</head>
<body>
   
<?php include 'header_admin.php'; ?>



<section class="shopping-cart">

   <h1 class="title">Cart</h1>
   <?php if ($manual_table >= 1 && $manual_table <= 12): ?>
      <p class="cart-table">Manual order for Table <?php echo $manual_table; ?></p>
   <?php endif; ?>

   <div class="box-container">
      <?php
         $grand_total = 0;
         $stmt = mysqli_prepare($conn, 'SELECT * FROM cart WHERE admin_id = ?');
         mysqli_stmt_bind_param($stmt, 'i', $admin_id);
         mysqli_stmt_execute($stmt);
         $select_cart = mysqli_stmt_get_result($stmt);
         if(mysqli_num_rows($select_cart) > 0){
            while($fetch_cart = mysqli_fetch_assoc($select_cart)){   
      ?>
      <div class="box">
         <a href="cart.php?delete=<?php echo $fetch_cart['id']; ?>" class="remove-item" aria-label="Remove item" onclick="return confirm('delete this from cart?');">&times;</a>
         <img class="cart-image" src="../uploaded_img/<?php echo rawurlencode($fetch_cart['image']); ?>" alt="">
         <div class="name"><?php echo htmlspecialchars($fetch_cart['name'], ENT_QUOTES, 'UTF-8'); ?></div>
         <div class="price">RM<?php echo $fetch_cart['price']; ?></div>
         <form action="" method="post">
            <input type="hidden" name="cart_id" value="<?php echo $fetch_cart['id']; ?>">
            <input type="number" min="1" name="cart_quantity" value="<?php echo $fetch_cart['quantity']; ?>">
            <input type="submit" name="update_cart" value="Update" class="option-btn">
         </form>
         <div class="sub-total">Sub total: <span>RM<?php echo number_format($sub_total = ($fetch_cart['quantity'] * $fetch_cart['price']), 2); ?></span></div>
      </div>
      <?php
      $grand_total += $sub_total;
         }
      }else{
         echo '<p class="empty">Your cart is empty</p>';
      }
      ?>
   </div>

   <div class="cart-actions">
      <?php if ($grand_total > 0): ?>
         <a href="cart.php?delete_all" class="delete-btn" onclick="return confirm('delete all from cart?');">Delete all</a>
      <?php endif; ?>
   </div>

   <div class="cart-total">
      <p>Grand total: <span>RM<?php echo number_format($grand_total, 2); ?></span></p>
      <div class="flex">
         <a href="order.php" class="option-btn">Continue Shopping</a>
         <?php if ($grand_total > 0): ?>
            <a href="order_summary.php" class="option-btn">Proceed to Order</a>
         <?php endif; ?>
      </div>
   </div>

</section>








<?php include 'footer.php'; ?>


<script src="js/script.js"></script>

</body>
</html>
