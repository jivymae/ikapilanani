<?php
include 'db_config.php';

// Get the payment_id from the query parameter
$payment_id = intval($_GET['payment_id']); 

// Fetch the transaction details
$transaction_query = "
    SELECT 
        transaction_id,
        transaction_amount,
        receipts,
        created_at
    FROM transaction
    WHERE payment_id = ?
";
$transaction_stmt = $conn->prepare($transaction_query);
$transaction_stmt->bind_param("i", $payment_id);
$transaction_stmt->execute();
$transaction_result = $transaction_stmt->get_result();

// Check if the transaction exists
if ($transaction_result->num_rows > 0) {
    $transaction = $transaction_result->fetch_assoc();
    echo json_encode($transaction);
} else {
    echo json_encode(['error' => 'Transaction not found']);
}

// Close the database connection
$transaction_stmt->close();
$conn->close();
?>
