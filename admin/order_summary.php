<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}
require __DIR__ . '/../config.php';

$admin_id = (int)$_SESSION['admin_id'];
$table_number = (int)($_SESSION['manual_order_table'] ?? 0);
$error = '';

if (empty($_SESSION['manual_order_csrf'])) {
    $_SESSION['manual_order_csrf'] = bin2hex(random_bytes(32));
}

function manual_order_escape($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

if (isset($_GET['placed'])) {
    $placed_id = filter_input(INPUT_GET, 'placed', FILTER_VALIDATE_INT);
    if (!$placed_id || (int)($_SESSION['last_manual_order_id'] ?? 0) !== $placed_id) {
        header('Location: manual_order.php');
        exit;
    }
    $stmt = mysqli_prepare($conn, 'SELECT Order_id, table_number, total_price, placed_on, payment_status, order_status FROM `order` WHERE Order_id = ? AND user_id IS NULL');
    mysqli_stmt_bind_param($stmt, 'i', $placed_id);
    mysqli_stmt_execute($stmt);
    $placed_order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if (!$placed_order) {
        header('Location: manual_order.php');
        exit;
    }
    $stmt = mysqli_prepare($conn, 'SELECT product_name, price, quantity FROM order_detail WHERE order_id = ? ORDER BY Order_detail_id');
    mysqli_stmt_bind_param($stmt, 'i', $placed_id);
    mysqli_stmt_execute($stmt);
    $items = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
} else {
    if ($table_number < 1 || $table_number > 12) {
        header('Location: manual_order.php');
        exit;
    }
    $stmt = mysqli_prepare($conn, 'SELECT name, price, image, quantity FROM cart WHERE admin_id = ? ORDER BY id');
    mysqli_stmt_bind_param($stmt, 'i', $admin_id);
    mysqli_stmt_execute($stmt);
    $items = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    if (!$items) {
        header('Location: cart.php');
        exit;
    }
}

$grand_total = 0;
foreach ($items as $item) {
    $grand_total += (float)$item['price'] * (int)$item['quantity'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($placed_order)) {
    if (!hash_equals($_SESSION['manual_order_csrf'], (string)($_POST['csrf_token'] ?? ''))) {
        $error = 'The form expired. Refresh and try again.';
    } else {
        $transaction_started = false;
        try {
            $column = mysqli_fetch_assoc(mysqli_query($conn, "SHOW COLUMNS FROM `order` LIKE 'user_id'"));
            if (!$column || $column['Null'] !== 'YES') {
                mysqli_query($conn, 'ALTER TABLE `order` MODIFY `user_id` int(100) NULL');
            }

            mysqli_begin_transaction($conn);
            $transaction_started = true;
            $stmt = mysqli_prepare($conn, 'SELECT name, price, image, quantity FROM cart WHERE admin_id = ? FOR UPDATE');
            mysqli_stmt_bind_param($stmt, 'i', $admin_id);
            mysqli_stmt_execute($stmt);
            $items = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
            if (!$items) {
                throw new RuntimeException('The cart is empty.');
            }
            $grand_total = 0;
            foreach ($items as $item) {
                $grand_total += (float)$item['price'] * (int)$item['quantity'];
            }

            $stmt = mysqli_prepare($conn, "INSERT INTO `order` (user_id, email, table_number, total_price, placed_on) VALUES (NULL, '', ?, ?, UTC_TIMESTAMP())");
            mysqli_stmt_bind_param($stmt, 'id', $table_number, $grand_total);
            mysqli_stmt_execute($stmt);
            $order_id = mysqli_insert_id($conn);

            $stmt = mysqli_prepare($conn, 'INSERT INTO order_detail (order_id, product_name, price, image, quantity) VALUES (?, ?, ?, ?, ?)');
            foreach ($items as $item) {
                $name = $item['name'];
                $price = (float)$item['price'];
                $image = $item['image'];
                $quantity = (int)$item['quantity'];
                mysqli_stmt_bind_param($stmt, 'isdsi', $order_id, $name, $price, $image, $quantity);
                mysqli_stmt_execute($stmt);
            }

            $stmt = mysqli_prepare($conn, 'DELETE FROM cart WHERE admin_id = ?');
            mysqli_stmt_bind_param($stmt, 'i', $admin_id);
            mysqli_stmt_execute($stmt);
            mysqli_commit($conn);

            $_SESSION['last_manual_order_id'] = $order_id;
            unset($_SESSION['manual_order_table']);
            $_SESSION['manual_order_csrf'] = bin2hex(random_bytes(32));
            header('Location: order_summary.php?placed=' . $order_id);
            exit;
        } catch (Throwable $exception) {
            if ($transaction_started) {
                mysqli_rollback($conn);
            }
            error_log('Manual order failed: ' . $exception->getMessage());
            $error = 'Could not place the order. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="page-admin_order_summary">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= isset($placed_order) ? 'Order Placed' : 'Review Manual Order' ?></title>
    <link rel="stylesheet" href="style.css?v=<?= filemtime(__DIR__ . '/style.css') ?>">
</head>
<body>
<?php include __DIR__ . '/header_admin.php'; ?>
<main class="manual-checkout">
    <section class="manual-checkout-card">
        <?php if (isset($placed_order)): ?>
            <h1>Order placed</h1>
            <p class="manual-checkout-note">The order has been sent to the kitchen.</p>
            <div class="manual-checkout-meta">
                <strong>Table <?= (int)$placed_order['table_number'] ?></strong>
                <span>Order #<?= (int)$placed_order['Order_id'] ?></span>
                <span>Stage: <?= manual_order_escape(ucfirst($placed_order['order_status'])) ?></span>
                <span>Payment: <?= manual_order_escape(ucfirst($placed_order['payment_status'])) ?></span>
            </div>
        <?php else: ?>
            <h1>Review manual order</h1>
            <p class="manual-checkout-note">Table <?= $table_number ?> · Check the items before sending them to the kitchen.</p>
        <?php endif; ?>
        <?php if ($error): ?><p class="manual-checkout-error"><?= manual_order_escape($error) ?></p><?php endif; ?>
        <div class="manual-checkout-items">
            <?php foreach ($items as $item): ?>
                <div class="manual-checkout-item">
                    <span><?= manual_order_escape($item['product_name'] ?? $item['name']) ?> × <?= (int)$item['quantity'] ?></span>
                    <strong>RM<?= number_format((float)$item['price'] * (int)$item['quantity'], 2) ?></strong>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="manual-checkout-total"><span>Grand total</span><strong>RM<?= number_format($grand_total, 2) ?></strong></div>
        <?php if (isset($placed_order)): ?>
            <a class="manual-checkout-button" href="table_summary.php?table=<?= (int)$placed_order['table_number'] ?>">View table summary</a>
            <a class="manual-checkout-back" href="manual_order.php">All tables</a>
        <?php else: ?>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= manual_order_escape($_SESSION['manual_order_csrf']) ?>">
                <button class="manual-checkout-button" type="submit">Place Order</button>
            </form>
            <a class="manual-checkout-back" href="cart.php">Back to Cart</a>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
