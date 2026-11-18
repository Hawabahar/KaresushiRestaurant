<?php
session_start();
header('Location: ' . (isset($_SESSION['admin_id']) ? 'manual_order.php' : 'admin_login.php'));
exit;
