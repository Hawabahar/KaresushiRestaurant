<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}
require __DIR__ . '/../config.php';

$_SESSION['category_csrf'] ??= bin2hex(random_bytes(16));
$message = (string)($_SESSION['category_message'] ?? '');
$error = (string)($_SESSION['category_error'] ?? '');
unset($_SESSION['category_message'], $_SESSION['category_error']);

function upload_category_image(array $file): ?string
{
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 2_000_000) {
        throw new RuntimeException('Image upload failed or exceeds 2 MB.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($extensions[$mime])) {
        throw new RuntimeException('Use a JPG, PNG, or WebP image.');
    }
    $relative = 'uploaded_img/category_' . bin2hex(random_bytes(8)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($file['tmp_name'], __DIR__ . '/../' . $relative)) {
        throw new RuntimeException('Could not save the image.');
    }
    return $relative;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!hash_equals($_SESSION['category_csrf'], $_POST['csrf'] ?? '')) {
            throw new RuntimeException('Form session expired. Refresh the page.');
        }
        $action = $_POST['action'] ?? '';
        $name = trim($_POST['name'] ?? '');
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if ($action !== 'delete' && ($name === '' || strlen($name) > 100)) {
            throw new RuntimeException('Enter a category name (up to 100 characters).');
        }

        if ($action === 'add') {
            $image = upload_category_image($_FILES['image'] ?? ['error' => UPLOAD_ERR_NO_FILE]) ?? '';
            $stmt = mysqli_prepare($conn, 'INSERT INTO categories (name, image) VALUES (?, ?)');
            mysqli_stmt_bind_param($stmt, 'ss', $name, $image);
            mysqli_stmt_execute($stmt);
            $message = 'Category added.';
        } elseif ($action === 'update' && $id) {
            $stmt = mysqli_prepare($conn, 'SELECT name, image FROM categories WHERE id = ?');
            mysqli_stmt_bind_param($stmt, 'i', $id);
            mysqli_stmt_execute($stmt);
            $old = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            if (!$old) {
                throw new RuntimeException('Category not found.');
            }
            $image = upload_category_image($_FILES['image'] ?? ['error' => UPLOAD_ERR_NO_FILE]) ?? $old['image'];
            mysqli_begin_transaction($conn);
            try {
                $stmt = mysqli_prepare($conn, 'UPDATE categories SET name = ?, image = ? WHERE id = ?');
                mysqli_stmt_bind_param($stmt, 'ssi', $name, $image, $id);
                mysqli_stmt_execute($stmt);
                $stmt = mysqli_prepare($conn, 'UPDATE menu SET category = ? WHERE category = ?');
                mysqli_stmt_bind_param($stmt, 'ss', $name, $old['name']);
                mysqli_stmt_execute($stmt);
                mysqli_commit($conn);
            } catch (Throwable $exception) {
                mysqli_rollback($conn);
                throw $exception;
            }
            $message = 'Category updated.';
        } elseif ($action === 'delete' && $id) {
            $stmt = mysqli_prepare($conn, 'SELECT name FROM categories WHERE id = ?');
            mysqli_stmt_bind_param($stmt, 'i', $id);
            mysqli_stmt_execute($stmt);
            $category = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            if (!$category) {
                throw new RuntimeException('Category not found.');
            }
            $stmt = mysqli_prepare($conn, 'SELECT COUNT(*) AS total FROM menu WHERE category = ?');
            mysqli_stmt_bind_param($stmt, 's', $category['name']);
            mysqli_stmt_execute($stmt);
            $count = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'];
            if ($count > 0) {
                throw new RuntimeException('Move or delete menu items in this category first.');
            }
            $stmt = mysqli_prepare($conn, 'DELETE FROM categories WHERE id = ?');
            mysqli_stmt_bind_param($stmt, 'i', $id);
            mysqli_stmt_execute($stmt);
            $message = 'Category deleted.';
        } else {
            throw new RuntimeException('Invalid action.');
        }
    } catch (Throwable $exception) {
        $error = $exception instanceof mysqli_sql_exception && $exception->getCode() === 1062
            ? 'Category name already exists.' : $exception->getMessage();
    }
    $_SESSION[$error !== '' ? 'category_error' : 'category_message'] = $error !== '' ? $error : $message;
    header('Location: categories.php');
    exit;
}

$categories = mysqli_query($conn, 'SELECT c.id, c.name, c.image, COUNT(m.Menu_id) AS product_count FROM categories c LEFT JOIN menu m ON m.category = c.name GROUP BY c.id, c.name, c.image ORDER BY c.id');
function h(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en" class="page-categories">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Categories</title>
    <link rel="stylesheet" href="style.css?v=<?= filemtime(__DIR__ . '/style.css') ?>">
</head>
<body>
<?php include __DIR__ . '/header_admin.php'; ?>
<main>
    <h1>Manage Categories</h1>
    <?php if ($error): ?><p class="error"><?= h($error) ?></p><?php endif; ?>
    <?php if ($message): ?><p class="success"><?= h($message) ?></p><?php endif; ?>
    <section class="card category-add-card">
        <h2>Add new category</h2>
        <form class="category-add-form" method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?= h($_SESSION['category_csrf']) ?>">
            <input type="hidden" name="action" value="add">
            <label class="category-field">Category name<input name="name" placeholder="New category name" maxlength="100" required></label>
            <label class="category-field">Image (optional)<input type="file" name="image" accept="image/jpeg,image/png,image/webp"></label>
            <button type="submit">Add Category</button>
        </form>
    </section>
    <?php while ($category = mysqli_fetch_assoc($categories)): ?>
        <section class="card category-card">
            <?php if ($category['image']): ?><img src="../<?= h($category['image']) ?>" alt=""><?php else: ?><div class="category-image-placeholder" aria-hidden="true"></div><?php endif; ?>
            <div class="category-details"><h2><?= h($category['name']) ?></h2><p><?= (int)$category['product_count'] ?> menu items</p></div>
            <form class="edit-category" method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf" value="<?= h($_SESSION['category_csrf']) ?>">
                <input type="hidden" name="id" value="<?= (int)$category['id'] ?>">
                <label class="category-field">Category name<input name="name" value="<?= h($category['name']) ?>" maxlength="100" required></label>
                <label class="category-field">Replace image<input type="file" name="image" accept="image/jpeg,image/png,image/webp"></label>
                <div class="category-buttons">
                    <button type="submit" name="action" value="update">Save</button>
                    <button type="submit" name="action" value="delete" formnovalidate onclick="return confirm('Delete this category?')">Delete</button>
                </div>
            </form>
        </section>
    <?php endwhile; ?>
</main></body></html>
