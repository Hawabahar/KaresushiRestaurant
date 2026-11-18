<!DOCTYPE html>
<html lang="en" class="page-header_history">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Icons+Outlined&display=swap" />
  <link rel="stylesheet" href="style.css">
  <title>Karesushi Restaurant</title>
</head>

<body>
  <header>
    <h1>KARESUSHI RESTAURANT</h1>
  </header>

  <nav>
    <a href="home.php" class="nav-link">Home</a>
    <a href="order_history.php" class="nav-link">Order History</a>

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
