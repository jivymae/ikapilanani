<?php
include 'db_config.php';

// Ensure 'patient_id' is passed in the query string
if (!isset($_GET['patient_id']) || empty($_GET['patient_id'])) {
    echo json_encode(['success' => false, 'message' => 'Patient ID is missing.']);
    exit;
}

$patient_id = intval($_GET['patient_id']); // Sanitize the input

// Calculate the total amount owed (sum of the total_amount from payments table)
$stmt = $conn->prepare("SELECT SUM(total_amount) AS total_amount FROM payments WHERE patient_id = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$stmt->bind_result($total_amount);
$stmt->fetch();
$stmt->close();

// Calculate the total amount paid (sum of the transaction_amount from transactions table)
$stmt = $conn->prepare("SELECT SUM(transaction_amount) AS total_paid FROM transactions WHERE pstat_id = ? AND payment_id IN (SELECT payment_id FROM payments WHERE patient_id = ?)");
$stmt->bind_param("ii", $patient_id, $patient_id);
$stmt->execute();
$stmt->bind_result($total_paid);
$stmt->fetch();
$stmt->close();

// Remaining balance calculation
$remaining_balance = $total_amount - $total_paid;

// Fetch receipts from transactions table
$receipts = [];
$stmt = $conn->prepare("SELECT transaction_id, transaction_amount, due_date, receipts FROM transactions WHERE pstat_id = ? AND payment_id IN (SELECT payment_id FROM payments WHERE patient_id = ?)");
$stmt->bind_param("ii", $patient_id, $patient_id);
$stmt->execute();
$stmt->bind_result($transaction_id, $transaction_amount, $due_date, $receipt_path);

while ($stmt->fetch()) {
    $receipts[] = [
        'transaction_id' => $transaction_id,
        'amount' => $transaction_amount,
        'due_date' => $due_date,
        'receipt_path' => $receipt_path
    ];
}
$stmt->close();

// Return the response as JSON
echo json_encode([
    'success' => true,
    'balance' => number_format($remaining_balance, 2),
    'receipts' => $receipts
]);

// Close the connection
$conn->close();
?>
