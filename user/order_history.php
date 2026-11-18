<?php
require __DIR__ . '/../config.php';
session_start();

$user_id = (int)($_SESSION['user_id'] ?? 0);
if ($user_id < 1) {
    header('Location: login.php');
    exit;
}

$statement = mysqli_prepare($conn, "
    SELECT o.Order_id, o.table_number, o.total_price, o.placed_on,
           o.payment_status, o.order_status,
           d.product_name, d.quantity, d.price
    FROM `order` o
    LEFT JOIN order_detail d ON d.order_id = o.Order_id
    WHERE o.user_id = ?
      AND LOWER(o.order_status) = 'completed'
      AND LOWER(o.payment_status) = 'paid'
    ORDER BY o.Order_id DESC, d.Order_detail_id ASC
");
mysqli_stmt_bind_param($statement, 'i', $user_id);
mysqli_stmt_execute($statement);
$result = mysqli_stmt_get_result($statement);

$orders = [];
while ($row = mysqli_fetch_assoc($result)) {
    $id = (int)$row['Order_id'];
    if (!isset($orders[$id])) {
        $status = strtolower((string)$row['order_status']);
        $orders[$id] = [
            'table_number' => $row['table_number'],
            'total_price' => $row['total_price'],
            'placed_on' => $row['placed_on'],
            'payment_status' => $row['payment_status'],
            'status' => $status,
            'items' => [],
        ];
    }
    if ($row['product_name'] !== null) {
        $orders[$id]['items'][] = [
            'name' => $row['product_name'],
            'quantity' => (int)$row['quantity'],
            'price' => (float)$row['price'],
        ];
    }
}

function order_history_escape($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function order_history_time($utc_time): string
{
    try {
        return (new DateTimeImmutable((string)$utc_time, new DateTimeZone('UTC')))
            ->setTimezone(new DateTimeZone('Asia/Kuala_Lumpur'))
            ->format('d M Y, h:i A');
    } catch (Throwable $exception) {
        return (string)$utc_time;
    }
}

$stages = ['received' => 'Received', 'preparing' => 'Preparing', 'ready' => 'Ready', 'completed' => 'Completed'];
?>
<?php $user_page_class = 'page-orders page-order_history'; include __DIR__ . '/header.php'; ?>
<main class="history-main">
    <h2>Order History</h2>
    <p class="history-note">
        Completed and paid orders. <a href="order_status.php">Track current orders</a>
    </p>

    <?php if (!$orders): ?>
        <div class="history-empty">No completed and paid orders yet. <a href="order_status.php">Track current orders</a>.</div>
    <?php else: ?>
        <?php foreach ($orders as $id => $order): ?>
            <section class="history-card">
                <div class="history-top">
                    <div>
                        <h3>Order #<?= $id ?></h3>
                        <p class="history-meta">
                            Table <?= order_history_escape($order['table_number'] ?? '-') ?><br>
                            Placed <?= order_history_escape(order_history_time($order['placed_on'])) ?><br>
                            Payment: <?= order_history_escape(ucfirst((string)$order['payment_status'])) ?>
                        </p>
                    </div>
                    <span class="history-badge"><?= order_history_escape($stages[$order['status']] ?? ucfirst($order['status'])) ?></span>
                </div>

                <ul class="history-items">
                    <?php foreach ($order['items'] as $item): ?>
                        <li><span><strong><?= $item['quantity'] ?> &times;</strong> <?= order_history_escape($item['name']) ?></span><span>RM<?= number_format($item['quantity'] * $item['price'], 2) ?></span></li>
                    <?php endforeach; ?>
                </ul>
                <p class="history-total">Total: RM<?= number_format((float)$order['total_price'], 2) ?></p>
            </section>
        <?php endforeach; ?>
    <?php endif; ?>
</main>
<?php include __DIR__ . '/footer.php'; ?>
