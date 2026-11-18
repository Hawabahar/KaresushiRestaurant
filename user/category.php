<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
require __DIR__ . '/../config.php';
$categories = mysqli_query($conn, 'SELECT id, name, image FROM categories ORDER BY id');
function h(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en" class="page-category">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Menu Categories</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include __DIR__ . '/header.php'; ?>
<main>
    <h1>Menu Categories</h1>
    <div class="grid">
    <?php while ($category = mysqli_fetch_assoc($categories)): ?>
        <a class="card" href="menu.php?category=<?= (int)$category['id'] ?>">
            <?php if ($category['image']): ?>
                <img src="../<?= h($category['image']) ?>" alt="">
            <?php else: ?>
                <div class="placeholder">No image</div>
            <?php endif; ?>
            <strong><?= h($category['name']) ?></strong>
        </a>
    <?php endwhile; ?>
    </div>
</main>
<?php include __DIR__ . '/footer.php'; ?>
</body></html>
