<?php
require __DIR__ . '/../config.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$token = $_POST['csrf_token'] ?? '';
if (!is_string($token) || empty($_SESSION['kitchen_csrf']) || !hash_equals($_SESSION['kitchen_csrf'], $token)) {
    http_response_code(403);
    exit('Invalid request. Refresh the kitchen page and try again.');
}

$order_id = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
$next_status = $_POST['next_status'] ?? '';
if (!is_string($next_status)) {
    http_response_code(400);
    exit('Invalid order update.');
}
$previous_status = [
    'Preparing' => 'received',
    'Ready' => 'Preparing',
    'Completed' => 'Ready',
][$next_status] ?? null;

if (!$order_id || $order_id < 1 || !$previous_status) {
    http_response_code(400);
    exit('Invalid order update.');
}

$statement = mysqli_prepare($conn, 'UPDATE `order` SET order_status = ?, status_updated_at = CURRENT_TIMESTAMP WHERE Order_id = ? AND order_status = ?');
mysqli_stmt_bind_param($statement, 'sis', $next_status, $order_id, $previous_status);
mysqli_stmt_execute($statement);

if (mysqli_stmt_affected_rows($statement) !== 1) {
    $_SESSION['kitchen_notice'] = 'This order has already changed. The list has been refreshed.';
}

header('Location: index.php', true, 303);
exit;
