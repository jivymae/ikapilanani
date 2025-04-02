<?php
include 'db_config.php';

// Check if payment_id is provided
if (isset($_GET['payment_id'])) {
    $payment_id = $_GET['payment_id'];

    // Fetch the transaction details from the database ordered by created_at in descending order
    $sql = "SELECT 
                transaction_id, 
                transaction_amount, 
                due_date, 
                receipts, 
                created_at
            FROM transaction
            WHERE payment_id = ?
            ORDER BY created_at DESC"; // Order by the created_at field in descending order

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $payment_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Prepare an array to hold the transaction data
    $transactions = [];

    // Fetch the results
    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }

    // Return the transactions as JSON
    echo json_encode(['transactions' => $transactions]);
} else {
    echo json_encode(['error' => 'Payment ID not provided']);
}
?>
