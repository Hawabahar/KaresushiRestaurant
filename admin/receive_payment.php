<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: manual_order.php');
    exit;
}

$table_number = filter_input(INPUT_POST, 'table_number', FILTER_VALIDATE_INT);
$token = (string)($_POST['csrf_token'] ?? '');
if (!$table_number || $table_number < 1 || $table_number > 12 || empty($_SESSION['manual_payment_csrf']) || !hash_equals($_SESSION['manual_payment_csrf'], $token)) {
    http_response_code(400);
    exit('Invalid payment request.');
}

require __DIR__ . '/../config.php';
$paid_at_column = mysqli_query($conn, "SHOW COLUMNS FROM `order` LIKE 'paid_at'");
if (mysqli_num_rows($paid_at_column) === 0) {
    mysqli_query($conn, 'ALTER TABLE `order` ADD COLUMN paid_at DATETIME NULL DEFAULT NULL AFTER payment_status');
}
$stmt = mysqli_prepare($conn, "UPDATE `order` SET payment_status = 'paid', paid_at = UTC_TIMESTAMP() WHERE table_number = ? AND LOWER(payment_status) <> 'paid'");
mysqli_stmt_bind_param($stmt, 'i', $table_number);
mysqli_stmt_execute($stmt);
header('Location: table_summary.php?table=' . $table_number);
exit;
