<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}
require __DIR__ . '/../config.php';

$admin_id = (int)$_SESSION['admin_id'];
$category_id = filter_input(INPUT_GET, 'category', FILTER_VALIDATE_INT);
$table = filter_input(INPUT_GET, 'table', FILTER_VALIDATE_INT);
$error = '';

if ($table !== null && $table !== false) {
    if ($table < 1 || $table > 12) {
        $error = 'Choose a table from 1 to 12.';
    } else {
        $stmt = mysqli_prepare($conn, 'SELECT COUNT(*) AS total FROM cart WHERE admin_id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $admin_id);
        mysqli_stmt_execute($stmt);
        $items_in_cart = (int)mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'];
        $current_table = (int)($_SESSION['manual_order_table'] ?? 0);
        if ($items_in_cart > 0 && $current_table > 0 && $current_table !== $table) {
            $error = 'Finish or clear the cart for Table ' . $current_table . ' before choosing another table.';
        } else {
            $_SESSION['manual_order_table'] = $table;
        }
    }
}
$table = (int)($_SESSION['manual_order_table'] ?? 0);
if ($table < 1 || $table > 12) {
    header('Location: manual_order.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $menu_id = filter_input(INPUT_POST, 'menu_id', FILTER_VALIDATE_INT);
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
    if (!$menu_id || !$quantity || $quantity < 1 || $quantity > 99) {
        $error = 'Choose a quantity between 1 and 99.';
    } else {
        $stmt = mysqli_prepare($conn, 'SELECT name, price, image FROM menu WHERE Menu_id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $menu_id);
        mysqli_stmt_execute($stmt);
        $item = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if (!$item) {
            $error = 'Menu item not found.';
        } else {
            $stmt = mysqli_prepare($conn, 'SELECT id FROM cart WHERE admin_id = ? AND name = ? LIMIT 1');
            mysqli_stmt_bind_param($stmt, 'is', $admin_id, $item['name']);
            mysqli_stmt_execute($stmt);
            $existing = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            if ($existing) {
                $stmt = mysqli_prepare($conn, 'UPDATE cart SET quantity = quantity + ? WHERE id = ? AND admin_id = ?');
                mysqli_stmt_bind_param($stmt, 'iii', $quantity, $existing['id'], $admin_id);
            } else {
                $stmt = mysqli_prepare($conn, 'INSERT INTO cart (admin_id, name, price, quantity, image) VALUES (?, ?, ?, ?, ?)');
                mysqli_stmt_bind_param($stmt, 'isdis', $admin_id, $item['name'], $item['price'], $quantity, $item['image']);
            }
            mysqli_stmt_execute($stmt);
            header('Location: cart.php');
            exit;
        }
    }
}

$categories = mysqli_query($conn, 'SELECT id, name FROM categories ORDER BY id');
if ($category_id) {
    $stmt = mysqli_prepare($conn, 'SELECT m.Menu_id, m.name, m.price, m.description, m.image, m.category FROM menu m JOIN categories c ON c.name = m.category WHERE c.id = ? ORDER BY m.Menu_id');
    mysqli_stmt_bind_param($stmt, 'i', $category_id);
    mysqli_stmt_execute($stmt);
    $items = mysqli_stmt_get_result($stmt);
} else {
    $items = mysqli_query($conn, 'SELECT Menu_id, name, price, description, image, category FROM menu ORDER BY Menu_id');
}
$stmt = mysqli_prepare($conn, 'SELECT COUNT(*) AS total FROM cart WHERE admin_id = ?');
mysqli_stmt_bind_param($stmt, 'i', $admin_id);
mysqli_stmt_execute($stmt);
$cart_count = (int)mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'];
function h(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en" class="page-order">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manual Order</title>
    <link rel="stylesheet" href="style.css?v=<?= filemtime(__DIR__ . '/style.css') ?>">
</head>
<body>
<?php include __DIR__ . '/header_admin.php'; ?>
<main>
    <a class="back-to-tables" href="manual_order.php">← Back to Tables</a>
    <h1>Manual Order - Table <?= $table ?></h1>
    <p><a href="cart.php">Cart (<?= $cart_count ?>)</a></p>
    <?php if ($error): ?><p class="error"><?= h($error) ?></p><?php endif; ?>
    <nav class="filters">
        <a class="<?= !$category_id ? 'active' : '' ?>" href="order.php<?= $table ? '?table=' . $table : '' ?>">All</a>
        <?php while ($category = mysqli_fetch_assoc($categories)): ?>
            <a class="<?= $category_id === (int)$category['id'] ? 'active' : '' ?>" href="order.php?category=<?= (int)$category['id'] ?><?= $table ? '&amp;table=' . $table : '' ?>"><?= h($category['name']) ?></a>
        <?php endwhile; ?>
    </nav>
    <div class="grid">
        <?php if (mysqli_num_rows($items) === 0): ?><p>No menu items yet.</p><?php endif; ?>
        <?php while ($item = mysqli_fetch_assoc($items)): ?>
            <form class="card" method="post">
                <?php if ($item['image']): ?>
                    <img src="../uploaded_img/<?= rawurlencode($item['image']) ?>" alt="<?= h($item['name']) ?>">
                <?php else: ?>
                    <div class="placeholder"></div>
                <?php endif; ?>
                <div class="card-content">
                    <h2><?= h($item['name']) ?></h2>
                    <p class="price">RM<?= number_format((float)$item['price'], 2) ?></p>
                    <p><?= h($item['description']) ?></p>
                    <input type="hidden" name="menu_id" value="<?= (int)$item['Menu_id'] ?>">
                    <div class="buy">
                        <label>Qty <input type="number" name="quantity" min="1" max="99" value="1" required></label>
                        <button type="submit">Add to Cart</button>
                    </div>
                </div>
            </form>
        <?php endwhile; ?>
    </div>
</main>
</body></html>
