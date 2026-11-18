<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}
require __DIR__ . '/../config.php';

$kl = new DateTimeZone('Asia/Kuala_Lumpur');
$utc = new DateTimeZone('UTC');
$today = new DateTimeImmutable('today', $kl);
$month_start = $today->modify('first day of this month');
$year_start = $today->setDate((int)$today->format('Y'), 1, 1);

function report_utc(DateTimeImmutable $date, DateTimeZone $utc): string
{
    return $date->setTimezone($utc)->format('Y-m-d H:i:s');
}

$day_start_utc = report_utc($today, $utc);
$day_end_utc = report_utc($today->modify('+1 day'), $utc);
$month_start_utc = report_utc($month_start, $utc);
$month_end_utc = report_utc($month_start->modify('+1 month'), $utc);
$year_start_utc = report_utc($year_start, $utc);
$year_end_utc = report_utc($year_start->modify('+1 year'), $utc);

$paid_at_column = mysqli_query($conn, "SHOW COLUMNS FROM `order` LIKE 'paid_at'");
$sales_date = mysqli_num_rows($paid_at_column) > 0
    ? 'COALESCE(paid_at, CAST(placed_on AS DATETIME))'
    : 'CAST(placed_on AS DATETIME)';

$sql = "SELECT
    COALESCE(SUM(CASE WHEN $sales_date >= ? AND $sales_date < ? THEN total_price ELSE 0 END), 0) AS daily_total,
    COALESCE(SUM(CASE WHEN $sales_date >= ? AND $sales_date < ? THEN total_price ELSE 0 END), 0) AS monthly_total,
    COALESCE(SUM(CASE WHEN $sales_date >= ? AND $sales_date < ? THEN total_price ELSE 0 END), 0) AS annual_total
    FROM `order`
    WHERE LOWER(payment_status) = 'paid'";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'ssssss', $day_start_utc, $day_end_utc, $month_start_utc, $month_end_utc, $year_start_utc, $year_end_utc);
mysqli_stmt_execute($stmt);
$totals = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
?>
<!DOCTYPE html>
<html lang="en" class="page-sales_report">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sales Report</title>
    <link rel="stylesheet" href="style.css?v=<?= filemtime(__DIR__ . '/style.css') ?>">
</head>
<body>
<?php include __DIR__ . '/header_admin.php'; ?>
<main class="sales report">
    <h1>Sales Report</h1>
    <p class="report-note"></p>
    <div class="box-container">
        <section class="box">
            <h2>Sales Today</h2>
            <p class="report-period"><?= htmlspecialchars($today->format('d M Y'), ENT_QUOTES, 'UTF-8') ?></p>
            <p class="report-amount">RM<?= number_format((float)$totals['daily_total'], 2) ?></p>
        </section>
        <section class="box">
            <h2>Earnings This Month</h2>
            <p class="report-period"><?= htmlspecialchars($today->format('F Y'), ENT_QUOTES, 'UTF-8') ?></p>
            <p class="report-amount">RM<?= number_format((float)$totals['monthly_total'], 2) ?></p>
        </section>
        <section class="box">
            <h2>Earnings This Year</h2>
            <p class="report-period"><?= htmlspecialchars($today->format('Y'), ENT_QUOTES, 'UTF-8') ?></p>
            <p class="report-amount">RM<?= number_format((float)$totals['annual_total'], 2) ?></p>
        </section>
    </div>
</main>
</body>
</html>
