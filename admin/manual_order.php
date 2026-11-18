<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}
require __DIR__ . '/../config.php';
$active_tables = [];
$result = mysqli_query($conn, "SELECT table_number, COUNT(*) AS order_count, SUM(total_price) AS total_price, SUM(LOWER(payment_status) <> 'paid') AS unpaid_count FROM `order` WHERE NOT (LOWER(order_status) = 'completed' AND LOWER(payment_status) = 'paid') GROUP BY table_number");
while ($row = mysqli_fetch_assoc($result)) {
    $active_tables[(int)$row['table_number']] = $row;
}
?>
<!DOCTYPE html>
<html lang="en" class="page-manual_order">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Submission</title>
    <link href='https://unpkg.com/boxicons@2.1.1/css/boxicons.min.css' rel='stylesheet'>
    
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime(__DIR__ . '/style.css'); ?>">
        
    </head>

   <body> 

 
   <?php include 'header_admin.php'; ?>

    <main class="table-dashboard">
        <div class="container">
            <?php for ($number = 1; $number <= 12; $number++): ?>
                <?php if (isset($active_tables[$number])): ?>
                    <a class="box is-active" href="table_summary.php?table=<?php echo $number; ?>">
                        <strong><?php echo sprintf('%02d', $number); ?></strong>
                        <span><?php echo (int)$active_tables[$number]['order_count']; ?> order(s) · <?php echo (int)$active_tables[$number]['unpaid_count']; ?> unpaid</span>
                    </a>
                <?php else: ?>
                    <button type="button" class="box" data-table="<?php echo $number; ?>" onclick="selectTable(this)">
                        <strong><?php echo sprintf('%02d', $number); ?></strong>
                        <span>Empty · Manual Order</span>
                    </button>
                <?php endif; ?>
            <?php endfor; ?>
        </div>
    </main>
    


    <div class="popup-outer" id="confirmationPopup">
        <div class="popup-box" id="popupContent">
            <i class='bx bx-x' onclick="closePopup()"></i>
            <p id="selectedTableText"></p>
            <div class="button">
                <a id="manualOrderLink" href="order.php">Manual Order</a>
            </div>
        </div>
    </div>

    <script>
        let selectedTable;

        function selectTable(table) {
            selectedTable = table.getAttribute("data-table");
            
            if (selectedTable) {
                document.getElementById("selectedTableText").innerText = "Table " + selectedTable;
                document.getElementById("manualOrderLink").href = "order.php?table=" + encodeURIComponent(selectedTable);
                showPopup("confirmationPopup");
            } 
        }

        function showPopup(popupId) {
            const popup = document.getElementById(popupId);
            if (popup) {
                popup.style.display = "flex";
            }
        }

        function closePopup() {
            const popups = document.querySelectorAll(".popup-outer");
            popups.forEach(popup => {
                popup.style.display = "none";
            });
        }

     
       


    </script>

</body>
</html>
