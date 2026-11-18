<?php
$header_cart_count = 0;
if (isset($conn, $_SESSION['user_id'])) {
  $header_user_id = (int)$_SESSION['user_id'];
  $header_cart_stmt = mysqli_prepare($conn, 'SELECT COUNT(*) AS item_count FROM cart WHERE user_id = ?');
  mysqli_stmt_bind_param($header_cart_stmt, 'i', $header_user_id);
  mysqli_stmt_execute($header_cart_stmt);
  $header_cart_count = (int)mysqli_fetch_assoc(mysqli_stmt_get_result($header_cart_stmt))['item_count'];
}
?>
<!DOCTYPE html>
<html lang="en" class="<?php echo htmlspecialchars($user_page_class ?? 'page-header', ENT_QUOTES, 'UTF-8'); ?>">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Icons+Outlined&display=swap" />
  <link rel="stylesheet" href="style.css?v=<?php echo filemtime(__DIR__ . '/style.css'); ?>">
  <title>Karesushi Restaurant</title>
</head>

<body>

  <header>
    <h1>KARESUSHI RESTAURANT</h1>
  </header>

  <nav>
    <a href="home.php" class="nav-link">Home</a>
    <a href="category.php" class="nav-link">Menu</a>
    <a href="cart.php" class="nav-link">Cart (<?php echo $header_cart_count; ?>)</a>
    <a href="order_status.php" class="nav-link">Order</a>

    <div class="nav-actions">
      <form class="search-form" action="search.php" method="get">
        <input type="search" name="query" class="search-input" placeholder="Search menu" value="<?php echo htmlspecialchars($_GET['query'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
        <button type="submit" class="search-button">Go</button>
      </form>
      <a href="logout.php" class="logout-link" aria-label="Log out" title="Log out"><span class="material-icons-outlined" aria-hidden="true">logout</span></a>
    </div>
  </nav>

</body>

</html>
