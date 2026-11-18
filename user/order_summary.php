<?php
include __DIR__ . '/../config.php';

session_start();

$user_id = (int)($_SESSION['user_id'] ?? 0);
$error = '';

if (!$user_id) {
    header('Location: login.php');
    exit;
}

$stmt = mysqli_prepare($conn, "SELECT table_number FROM `order` WHERE user_id = ? AND NOT (LOWER(order_status) = 'completed' AND LOWER(payment_status) = 'paid') ORDER BY Order_id DESC LIMIT 1");
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$active_order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
$default_table = (int)($active_order['table_number'] ?? 0);

if (isset($_POST['order_btn'])) {
    $table_input = $default_table > 0 ? (string)$default_table : trim((string)($_POST['table_number'] ?? ''));
    if (!ctype_digit($table_input) || (int)$table_input < 1 || (int)$table_input > 12) {
        $error = 'Enter a table number from 1 to 12.';
    } else {
        $table_number = (int)$table_input;
        $stmt = mysqli_prepare($conn, "SELECT user_id FROM `order` WHERE table_number = ? AND NOT (LOWER(order_status) = 'completed' AND LOWER(payment_status) = 'paid')");
        mysqli_stmt_bind_param($stmt, 'i', $table_number);
        mysqli_stmt_execute($stmt);
        $table_orders = mysqli_stmt_get_result($stmt);
        $occupied_by_another_user = false;
        while ($table_order = mysqli_fetch_assoc($table_orders)) {
            if ($table_order['user_id'] !== null && (int)$table_order['user_id'] !== $user_id) {
                $occupied_by_another_user = true;
                break;
            }
            if ($table_order['user_id'] === null && $default_table !== $table_number) {
                $occupied_by_another_user = true;
                break;
            }
        }
        if ($occupied_by_another_user) {
            $error = 'This table is already in use by another customer.';
        } else {
            $stmt = mysqli_prepare($conn, 'SELECT name, price, image, quantity FROM cart WHERE user_id = ?');
            mysqli_stmt_bind_param($stmt, 'i', $user_id);
            mysqli_stmt_execute($stmt);
            $cart_items = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
            if (!$cart_items) {
                $error = 'Your cart is empty.';
            } else {
                $stmt = mysqli_prepare($conn, 'SELECT email FROM users WHERE id = ?');
                mysqli_stmt_bind_param($stmt, 'i', $user_id);
                mysqli_stmt_execute($stmt);
                $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
                if (!$user) {
                    $error = 'User account not found.';
                } else {
                    $total_price = 0;
                    foreach ($cart_items as $cart_item) {
                        $total_price += (float)$cart_item['price'] * (int)$cart_item['quantity'];
                    }
                    mysqli_begin_transaction($conn);
                    try {
                        $stmt = mysqli_prepare($conn, 'INSERT INTO `order` (user_id, email, table_number, total_price, placed_on) VALUES (?, ?, ?, ?, NOW())');
                        mysqli_stmt_bind_param($stmt, 'isid', $user_id, $user['email'], $table_number, $total_price);
                        mysqli_stmt_execute($stmt);
                        $order_id = mysqli_insert_id($conn);

                        $stmt = mysqli_prepare($conn, 'INSERT INTO order_detail (order_id, product_name, price, image, quantity) VALUES (?, ?, ?, ?, ?)');
                        foreach ($cart_items as $cart_item) {
                            $name = $cart_item['name'];
                            $price = (float)$cart_item['price'];
                            $image = $cart_item['image'];
                            $quantity = (int)$cart_item['quantity'];
                            mysqli_stmt_bind_param($stmt, 'isdsi', $order_id, $name, $price, $image, $quantity);
                            mysqli_stmt_execute($stmt);
                        }

                        $stmt = mysqli_prepare($conn, 'DELETE FROM cart WHERE user_id = ?');
                        mysqli_stmt_bind_param($stmt, 'i', $user_id);
                        mysqli_stmt_execute($stmt);
                        mysqli_commit($conn);
                        $order_placed = true;
                    } catch (Throwable $exception) {
                        mysqli_rollback($conn);
                        error_log('Order placement failed: ' . $exception->getMessage());
                        $error = 'Could not place the order. Please try again.';
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="page-order_summary">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>checkout</title>

   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
   <link href='https://unpkg.com/boxicons@2.1.1/css/boxicons.min.css' rel='stylesheet'>
   <link rel="stylesheet" href="style.css?v=<?php echo filemtime(__DIR__ . '/style.css'); ?>">


</head>
<body>
<?php include 'header.php'; ?>
<br>
<br>
<br>


<section class="checkout">
    <form action="" method="post">
        <h3><?php echo $default_table ? 'Place an additional order' : 'Place your order'; ?></h3>
        <?php if (!empty($error)) { ?>
            <p class="error-message"> <?php echo htmlspecialchars($error); ?></p>
        <?php } ?>
        <br> <br>
        <div class="inputBox">
            <span>Table number</span>
            <?php if ($default_table): ?>
                <strong class="locked-table">Table <?php echo $default_table; ?></strong>
                <p class="table-hint">Your additional items will be sent to the kitchen for this table.</p>
            <?php else: ?>
                <input type="number" name="table_number" min="1" max="12" required placeholder="Enter table number (1-12)">
            <?php endif; ?>
        </div>
        <br>

       

        <div class="flex">
            <section class="display-order">
                <div class="order">
                    <?php  
                    $grand_total = 0;
                    $select_cart = mysqli_query($conn, "SELECT * FROM `cart` WHERE user_id = '$user_id'") or die('query failed');
                    if(mysqli_num_rows($select_cart) > 0){
                        while($fetch_cart = mysqli_fetch_assoc($select_cart)){
                            $total_price = ($fetch_cart['price'] * $fetch_cart['quantity']);
                            $grand_total += $total_price;
                            ?>
                            <p><?php echo htmlspecialchars($fetch_cart['name'], ENT_QUOTES, 'UTF-8'); ?> <span><?php echo '× ' . (int)$fetch_cart['quantity'] . ' · RM' . number_format((float)$fetch_cart['price'], 2); ?></span></p>
                            <?php
                        }
                    } else {
                        echo '<p class="empty">Your cart is empty</p>';
                    }
                    ?>
                </div>
                <div class="grand-total">Grand total <span>RM<?php echo number_format((float)$grand_total, 2); ?></span></div>
            </section>
        </div>
        <input type="submit" value="Place Order" class="btn <?php echo (mysqli_num_rows($select_cart) > 0) ? '' : 'disabled'; ?>" name="order_btn" <?php echo (mysqli_num_rows($select_cart) > 0) ? '' : 'disabled'; ?>>

    </form>
    
</section>


<div class="popup-outer<?php echo !empty($order_placed) ? ' is-visible' : ''; ?>" id="confirmationPopup">
    <div class="popup-box">
        <p>Order Received!</p>
        <div class="button">
            <button onclick="redirectToUpdatePayment()">OK</button>
        </div>
    </div>
</div>

<script>
    function redirectToUpdatePayment() {
        window.location.href = 'status.php';
    }
</script>


</body>
</html>
