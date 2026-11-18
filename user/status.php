<?php
require __DIR__ . '/../config.php';
session_start();

$user_id = (int)($_SESSION['user_id'] ?? 0);
if ($user_id < 1) {
    header('Location: login.php');
    exit;
}

$statement = mysqli_prepare($conn, 'SELECT Order_id, table_number, total_price, placed_on, payment_status, order_status FROM `order` WHERE user_id = ? ORDER BY Order_id DESC LIMIT 1');
mysqli_stmt_bind_param($statement, 'i', $user_id);
mysqli_stmt_execute($statement);
$order = mysqli_fetch_assoc(mysqli_stmt_get_result($statement));

$items = [];
if ($order) {
    $order_id = (int)$order['Order_id'];
    $statement = mysqli_prepare($conn, 'SELECT product_name, quantity, price FROM order_detail WHERE order_id = ? ORDER BY Order_detail_id ASC');
    mysqli_stmt_bind_param($statement, 'i', $order_id);
    mysqli_stmt_execute($statement);
    $items = mysqli_fetch_all(mysqli_stmt_get_result($statement), MYSQLI_ASSOC);
}

function summary_escape($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$placed_time = $order ? (string)$order['placed_on'] : '';
if ($placed_time !== '') {
    try {
        $placed_time = (new DateTimeImmutable($placed_time, new DateTimeZone('UTC')))
            ->setTimezone(new DateTimeZone('Asia/Kuala_Lumpur'))
            ->format('j M Y, g:i A');
    } catch (Throwable $exception) {
        $placed_time = (string)$order['placed_on'];
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="page-status">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Summary</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime(__DIR__ . '/style.css'); ?>">
</head>
<body>
<?php include __DIR__ . '/header.php'; ?>

<main class="summary-main">
    <section class="summary-card">
        <?php if ($order): ?>
            <div class="summary-heading">
                <p class="summary-brand">Karesushi Restaurant</p>
                <h1>Order placed</h1>
                <p>Your order has been sent to the kitchen.</p>
            </div>

            <div class="summary-meta">
                <div class="summary-table-number">
                    <span>Table</span>
                    <strong><?= summary_escape($order['table_number'] ?? '-') ?></strong>
                </div>
                <div class="summary-details">
                    <p><span>Order</span><strong>#<?= $order_id ?></strong></p>
                    <p><span>Placed</span><strong><?= summary_escape($placed_time) ?></strong></p>
                    <p><span>Stage</span><strong><?= summary_escape(ucfirst((string)$order['order_status'])) ?></strong></p>
                    <p><span>Payment</span><strong><?= summary_escape(ucfirst((string)$order['payment_status'])) ?></strong></p>
                </div>
            </div>

            <h2>Items ordered</h2>
            <div class="summary-table-wrap">
                <table class="summary-items">
                    <thead>
                        <tr><th>Item</th><th>Qty</th><th>Amount</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td>
                                <strong><?= summary_escape($item['product_name']) ?></strong>
                                <small>RM<?= number_format((float)$item['price'], 2) ?> each</small>
                            </td>
                            <td><?= (int)$item['quantity'] ?></td>
                            <td>RM<?= number_format((float)$item['price'] * (int)$item['quantity'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="summary-total"><span>Total</span><strong>RM<?= number_format((float)$order['total_price'], 2) ?></strong></div>
            <div class="summary-actions">
                <a class="summary-primary" href="order_status.php">Track order</a>
                <a class="summary-secondary" href="category.php">Back to menu</a>
            </div>
        <?php else: ?>
            <div class="summary-heading">
                <h1>No orders yet</h1>
                <p>Your order summary will appear here after you place an order.</p>
            </div>
            <div class="summary-actions"><a class="summary-primary" href="category.php">Browse menu</a></div>
        <?php endif; ?>
    </section>
</main>

<?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
