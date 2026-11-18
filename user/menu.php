<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
require __DIR__ . '/../config.php';

$category_id = filter_input(INPUT_GET, 'category', FILTER_VALIDATE_INT);
if (!$category_id) {
    header('Location: category.php');
    exit;
}
$stmt = mysqli_prepare($conn, 'SELECT id, name FROM categories WHERE id = ?');
mysqli_stmt_bind_param($stmt, 'i', $category_id);
mysqli_stmt_execute($stmt);
$category = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
if (!$category) {
    header('Location: category.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $menu_id = filter_input(INPUT_POST, 'menu_id', FILTER_VALIDATE_INT);
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
    if (!$menu_id || !$quantity || $quantity < 1 || $quantity > 99) {
        $error = 'Choose a quantity between 1 and 99.';
    } else {
        $stmt = mysqli_prepare($conn, 'SELECT name, price, image FROM menu WHERE Menu_id = ? AND category = ?');
        mysqli_stmt_bind_param($stmt, 'is', $menu_id, $category['name']);
        mysqli_stmt_execute($stmt);
        $item = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if (!$item) {
            $error = 'Menu item not found.';
        } else {
            $user_id = (int)$_SESSION['user_id'];
            $stmt = mysqli_prepare($conn, 'SELECT id FROM cart WHERE user_id = ? AND name = ? LIMIT 1');
            mysqli_stmt_bind_param($stmt, 'is', $user_id, $item['name']);
            mysqli_stmt_execute($stmt);
            $existing = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            if ($existing) {
                $stmt = mysqli_prepare($conn, 'UPDATE cart SET quantity = quantity + ? WHERE id = ? AND user_id = ?');
                mysqli_stmt_bind_param($stmt, 'iii', $quantity, $existing['id'], $user_id);
            } else {
                $stmt = mysqli_prepare($conn, 'INSERT INTO cart (user_id, name, price, quantity, image) VALUES (?, ?, ?, ?, ?)');
                mysqli_stmt_bind_param($stmt, 'isdis', $user_id, $item['name'], $item['price'], $quantity, $item['image']);
            }
            mysqli_stmt_execute($stmt);
            header('Location: cart.php');
            exit;
        }
    }
}

$stmt = mysqli_prepare($conn, 'SELECT Menu_id, name, price, image, description FROM menu WHERE category = ? ORDER BY Menu_id');
mysqli_stmt_bind_param($stmt, 's', $category['name']);
mysqli_stmt_execute($stmt);
$items = mysqli_stmt_get_result($stmt);
function h(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en" class="page-menu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($category['name']) ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include __DIR__ . '/header.php'; ?>
<main>
    <p><a class="back" href="category.php">← All categories</a></p>
    <h1><?= h($category['name']) ?></h1>
    <?php if ($error): ?><p class="error"><?= h($error) ?></p><?php endif; ?>
    <div class="grid">
    <?php if (mysqli_num_rows($items) === 0): ?>
        <p class="error">No menu items in this category yet.</p>
    <?php endif; ?>
    <?php while ($item = mysqli_fetch_assoc($items)): ?>
        <form class="card" method="post">
            <?php if ($item['image']): ?>
                <img src="../uploaded_img/<?= rawurlencode($item['image']) ?>" alt="<?= h($item['name']) ?>">
            <?php else: ?>
                <div class="placeholder"></div>
            <?php endif; ?>
            <div class="card-content">
                <h2 title="<?= h($item['name']) ?>"><?= h($item['name']) ?></h2>
                <p class="price">RM<?= number_format((float)$item['price'], 2) ?></p>
                <p title="<?= h($item['description']) ?>"><?= h($item['description']) ?></p>
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
<?php include __DIR__ . '/footer.php'; ?>
</body></html>
