<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}
include __DIR__ . '/../config.php';
$categories = mysqli_fetch_all(mysqli_query($conn, 'SELECT name FROM categories ORDER BY id'), MYSQLI_ASSOC);

if (isset($_POST['add_menu'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $price = $_POST['price'];
    $description = $_POST['description'];
    $category = $_POST['category'];
    $image = str_replace(' ', '_', basename($_FILES['image']['name']));
    $image_size = $_FILES['image']['size'];
    $image_tmp_name = $_FILES['image']['tmp_name'];
    $image_folder = __DIR__ . '/../uploaded_img/' . $image;

    $select_menu_name = mysqli_query($conn, "SELECT name FROM menu WHERE name = '$name'") or die('query failed');

    if (mysqli_num_rows($select_menu_name) > 0) {
        $message[] = 'menu name already added';
    } else {
        $add_menu_query = mysqli_query($conn, "INSERT INTO menu(name, price, image, description, category) VALUES('$name', '$price', '$image', '$description', '$category')") or die('query failed');

        if ($add_menu_query) {
            if ($image_size > 2000000) {
                $message[] = 'image size is too large';
            } else {
                move_uploaded_file($image_tmp_name, $image_folder);
                $message[] = 'menu added successfully!';
            }
        } else {
            $message[] = 'menu could not be added';
        }
    }
}

if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    $delete_image_query = mysqli_query($conn, "SELECT image FROM menu WHERE menu_id = '$delete_id'") or die('query failed');
    $fetch_delete_image = mysqli_fetch_assoc($delete_image_query);
    unlink(__DIR__ . '/../uploaded_img/' . str_replace(' ', '_', $fetch_delete_image['image']));
    mysqli_query($conn, "DELETE FROM menu WHERE menu_id = '$delete_id'") or die('query failed');
    header('location:admin_menu.php');
}

if (isset($_POST['update_menu'])) {
    $update_m_id = $_POST['update_m_id'];
    $update_name = $_POST['update_name'];
    $update_price = $_POST['update_price'];
    $update_description = $_POST['update_description'];
    $update_category = $_POST['update_category'];

    mysqli_query($conn, "UPDATE menu SET name = '$update_name', price = '$update_price', description= '$update_description', category= '$update_category' WHERE Menu_id = '$update_m_id'") or die('query failed');

    $update_image = str_replace(' ', '_', basename($_FILES['update_image']['name']));
    $update_image_tmp_name = $_FILES['update_image']['tmp_name'];
    $update_image_size = $_FILES['update_image']['size'];
    $update_folder = __DIR__ . '/../uploaded_img/' . $update_image;
    $update_old_image = $_POST['update_old_image'];

    if (!empty($update_image)) {
        if ($update_image_size > 2000000) {
            $message[] = 'image file size is too large';
        } else {
            mysqli_query($conn, "UPDATE menu SET image = '$update_image' WHERE menu_id = '$update_m_id'") or die('query failed');
            move_uploaded_file($update_image_tmp_name, $update_folder);
            unlink(__DIR__ . '/../uploaded_img/' . str_replace(' ', '_', $update_old_image));
        }
    }

    header('location:admin_menu.php');
}

?>

<!DOCTYPE html>
<html lang="en" class="page-admin_menu">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime(__DIR__ . '/style.css'); ?>">
</head>

<body>
    <?php include 'header_admin.php'; ?>
    <section class="messages">
        <?php
        if (isset($message) && is_array($message) && count($message) > 0) {
            foreach ($message as $msg) {
                echo '<p>' . htmlspecialchars($msg) . '</p>';
            }
        }
        ?>
    </section>

    <section class="add-menu">
        <h1 class="title">ADD MENU</h1>
        <form action="" method="post" enctype="multipart/form-data">
            <br>
            <input type="file" name="image" accept="image/jpg, image/jpeg, image/png" class="box" required>
            <input type="text" name="name" class="box" placeholder="Enter menu name" required>
            <input type="number" min="0" name="price" class="box" placeholder="Enter menu price" required>
            <input type="text" name="description" class="box" placeholder="Enter menu description" required>
            <select name="category" class="dropdown" required>
                <option value="" disabled selected>--Select category--</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8'); ?>">
                        <?php echo htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <br>
            <br>
            <input type="submit" value="Add Menu" name="add_menu" class="btn">
        </form>
    </section>
    <section class="show-menu">
        <div class="box-container">
            <?php
            $select_menu = mysqli_query($conn, "SELECT * FROM menu") or die('query failed');
            if (mysqli_num_rows($select_menu) > 0) {
                while ($fetch_menu = mysqli_fetch_assoc($select_menu)) {
                    $image_path = '../uploaded_img/' . rawurlencode($fetch_menu['image']);
                    ?>
                    <div class="box-show">
                        <img src="<?php echo $image_path; ?>" alt="">
                        <div class="box-show-content">
                            <div class="name"><?php echo htmlspecialchars($fetch_menu['name'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="price">RM<?php echo htmlspecialchars($fetch_menu['price'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="description"><?php echo htmlspecialchars($fetch_menu['description'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="category"><?php echo htmlspecialchars($fetch_menu['category'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="box-show-actions">
                                <a href="admin_menu.php?delete=<?php echo $fetch_menu['Menu_id']; ?>" class="delete-btn"
                                    onclick="return confirm('Delete this menu?');">Delete</a>
                                <a href="admin_menu.php?update=<?php echo $fetch_menu['Menu_id']; ?>" class="option-btn">Update</a>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            } else {
                echo '<p class="empty">No menu added yet</p>';
            }
            ?>
        </div>
    </section>

    <section class="edit-menu-form">
        <?php
        if (isset($_GET['update'])) {
            $update_id = $_GET['update'];
            $update_query = mysqli_query($conn, "SELECT * FROM menu WHERE menu_id = '$update_id'") or die('query failed');
            if (mysqli_num_rows($update_query) > 0) {
                while ($fetch_update = mysqli_fetch_assoc($update_query)) {
                    ?>
                    <form action="" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="update_m_id" value="<?php echo $fetch_update['Menu_id']; ?>">
                        <input type="hidden" name="update_old_image" value="<?php echo $fetch_update['image']; ?>">
                        <input type="file" class="box" name="update_image" accept="image/jpg, image/jpeg, image/png">
                        <p class="existing-image">Current image:
                            <strong><?php echo htmlspecialchars($fetch_update['image'], ENT_QUOTES, 'UTF-8'); ?></strong></p>
                        <input type="text" name="update_name" value="<?php echo $fetch_update['name']; ?>" class="box" required
                            placeholder="Enter menu name">
                        <input type="number" name="update_price" value="<?php echo $fetch_update['price']; ?>" min="0" class="box"
                            required placeholder="Enter menu price">
                        <input type="text" name="update_description" value="<?php echo $fetch_update['description']; ?>" class="box"
                            placeholder="Enter menu description">

                       
                        <select name="update_category" class="dropdown" required>
                            <option value="" disabled>--Select category--</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $fetch_update['category'] === $category['name'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                      

                        <input type="submit" value="Update" name="update_menu" class="btn">
                        <input type="button" value="Cancel" class="option-btn" onclick="closeUpdateForm()">
                    </form>
                    <?php
                }
            }
        } else {
            echo '<script>document.querySelector(".edit-menu-form").style.display = "none";</script>';
        }
        ?>
    </section>

    <script>
        function closeUpdateForm() {
            document.querySelector(".edit-menu-form").style.display = "none";
        }
    </script>
</body>

</html>
