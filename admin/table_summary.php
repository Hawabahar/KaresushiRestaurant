<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}
require __DIR__ . '/../config.php';

$table_number = filter_input(INPUT_GET, 'table', FILTER_VALIDATE_INT);
if (!$table_number || $table_number < 1 || $table_number > 12) {
    header('Location: manual_order.php');
    exit;
}
if (empty($_SESSION['manual_payment_csrf'])) {
    $_SESSION['manual_payment_csrf'] = bin2hex(random_bytes(32));
}

$stmt = mysqli_prepare($conn, "SELECT o.Order_id, o.user_id, o.total_price, o.placed_on, o.payment_status, o.order_status, d.product_name, d.quantity, d.price FROM `order` o LEFT JOIN order_detail d ON d.order_id = o.Order_id WHERE o.table_number = ? AND NOT (LOWER(o.order_status) = 'completed' AND LOWER(o.payment_status) = 'paid') ORDER BY o.Order_id, d.Order_detail_id");
mysqli_stmt_bind_param($stmt, 'i', $table_number);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$orders = [];
$bill_total = 0;
$amount_due = 0;
while ($row = mysqli_fetch_assoc($result)) {
    $id = (int)$row['Order_id'];
    if (!isset($orders[$id])) {
        $orders[$id] = [
            'user_id' => $row['user_id'],
            'total_price' => (float)$row['total_price'],
            'placed_on' => $row['placed_on'],
            'payment_status' => $row['payment_status'],
            'order_status' => $row['order_status'],
            'items' => [],
        ];
        $bill_total += (float)$row['total_price'];
        if (strtolower($row['payment_status']) !== 'paid') {
            $amount_due += (float)$row['total_price'];
        }
    }
    if ($row['product_name'] !== null) {
        $orders[$id]['items'][] = $row;
    }
}
if (!$orders) {
    header('Location: manual_order.php');
    exit;
}
function table_summary_escape($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en" class="page-table_summary">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="refresh" content="15">
    <title>Table <?= $table_number ?> Summary</title>
    <link rel="stylesheet" href="style.css?v=<?= filemtime(__DIR__ . '/style.css') ?>">
</head>
<body>
<?php include __DIR__ . '/header_admin.php'; ?>
<main class="table-summary">
    <a class="table-summary-back" href="manual_order.php">← All tables</a>
    <div class="table-summary-card">
        <h1>Table <?= $table_number ?></h1>
        <p class="table-summary-note">Current orders · Refreshes every 15 seconds</p>
        <?php foreach ($orders as $id => $order): ?>
            <section class="table-summary-order">
                <div class="table-summary-order-heading">
                    <strong>Order #<?= $id ?> · <?= $order['user_id'] === null ? 'Manual' : 'Customer' ?></strong>
                    <span><?= table_summary_escape(ucfirst($order['order_status'])) ?> · <?= table_summary_escape(ucfirst($order['payment_status'])) ?></span>
                </div>
                <?php foreach ($order['items'] as $item): ?>
                    <div class="table-summary-item">
                        <span><?= (int)$item['quantity'] ?> × <?= table_summary_escape($item['product_name']) ?></span>
                        <span>RM<?= number_format((float)$item['price'] * (int)$item['quantity'], 2) ?></span>
                    </div>
                <?php endforeach; ?>
                <p class="table-summary-order-total">Order total: RM<?= number_format($order['total_price'], 2) ?></p>
            </section>
        <?php endforeach; ?>
        <div class="table-summary-totals">
            <p>Current table total <strong>RM<?= number_format($bill_total, 2) ?></strong></p>
            <p>Amount due <strong>RM<?= number_format($amount_due, 2) ?></strong></p>
        </div>
        <?php if ($amount_due > 0): ?>
            <form action="receive_payment.php" method="post" onsubmit="return confirm('Confirm payment received for all unpaid orders at Table <?= $table_number ?>?');">
                <input type="hidden" name="table_number" value="<?= $table_number ?>">
                <input type="hidden" name="csrf_token" value="<?= table_summary_escape($_SESSION['manual_payment_csrf']) ?>">
                <button class="table-summary-button" type="submit">Mark Paid · RM<?= number_format($amount_due, 2) ?></button>
            </form>
        <?php endif; ?>
        <a class="table-summary-add" href="order.php?table=<?= $table_number ?>">Add items to this table</a>
    </div>
</main>
</body>
</html>
