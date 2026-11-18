<?php
require __DIR__ . '/../config.php';
session_start();

if (empty($_SESSION['kitchen_csrf'])) {
    $_SESSION['kitchen_csrf'] = bin2hex(random_bytes(32));
}

$notice = $_SESSION['kitchen_notice'] ?? null;
unset($_SESSION['kitchen_notice']);

$result = mysqli_query($conn, "
    SELECT o.Order_id, o.table_number, o.total_price, o.placed_on,
           o.payment_status, o.order_status,
           d.product_name, d.quantity, d.price
    FROM `order` o
    LEFT JOIN order_detail d ON d.order_id = o.Order_id
    WHERE o.order_status IN ('received', 'Preparing', 'Ready')
    ORDER BY o.Order_id ASC, d.Order_detail_id ASC
");

$orders = [];
while ($row = mysqli_fetch_assoc($result)) {
    $id = (int)$row['Order_id'];
    if (!isset($orders[$id])) {
        $orders[$id] = [
            'table_number' => $row['table_number'],
            'total_price' => $row['total_price'],
            'placed_on' => $row['placed_on'],
            'payment_status' => $row['payment_status'],
            'order_status' => $row['order_status'],
            'items' => [],
        ];
    }
    if ($row['product_name'] !== null) {
        $orders[$id]['items'][] = [
            'name' => $row['product_name'],
            'quantity' => (int)$row['quantity'],
            'price' => $row['price'],
        ];
    }
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function local_order_time(string $utc_time): string
{
    try {
        return (new DateTimeImmutable($utc_time, new DateTimeZone('UTC')))
            ->setTimezone(new DateTimeZone('Asia/Kuala_Lumpur'))
            ->format('d M Y, h:i A');
    } catch (Throwable $exception) {
        return $utc_time;
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="page-index">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="refresh" content="15">
    <title>Kitchen Orders</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<main>
    <h1>Kitchen Orders</h1>
    <?php if ($notice): ?><p class="notice"><?= h($notice) ?></p><?php endif; ?>
    <p class="refresh-note">Updated <?= h(gmdate('h:i A', time() + 8 * 3600)) ?> · Refreshes every 15 seconds · <a href="index.php">Refresh now</a></p>
    <?php if (!$orders): ?>
        <p class="empty">No active orders yet.</p>
    <?php else: ?>
        <div class="orders">
            <?php foreach ($orders as $id => $order): ?>
                <section class="order">
                    <div class="order-header">
                        <h2>Table <?= (int)$order['table_number'] ?></h2>
                        <span class="badge"><?= h(ucfirst($order['order_status'])) ?></span>
                    </div>
                    <p class="details">Order #<?= $id ?><br>
                        Placed <?= h(local_order_time($order['placed_on'])) ?><br>
                        Payment: <?= h(ucfirst($order['payment_status'])) ?></p>
                    <ul class="items">
                        <?php foreach ($order['items'] as $item): ?>
                            <li><span class="quantity"><?= $item['quantity'] ?> ×</span> <?= h($item['name']) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <p class="total">Total: RM<?= number_format((float)$order['total_price'], 2) ?></p>
                    <?php
                    $next = [
                        'received' => ['Preparing', 'Start preparing'],
                        'Preparing' => ['Ready', 'Mark ready'],
                        'Ready' => ['Completed', 'Complete order'],
                    ][$order['order_status']] ?? null;
                    ?>
                    <?php if ($next): ?>
                        <form class="stage-form" action="update_order.php" method="post">
                            <input type="hidden" name="order_id" value="<?= $id ?>">
                            <input type="hidden" name="next_status" value="<?= h($next[0]) ?>">
                            <input type="hidden" name="csrf_token" value="<?= h($_SESSION['kitchen_csrf']) ?>">
                            <button class="stage-button" type="submit"><?= h($next[1]) ?></button>
                        </form>
                    <?php endif; ?>
                </section>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
</body>
</html>
