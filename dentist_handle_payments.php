<?php
session_start();
include 'db_config.php'; // Assuming you have a file for DB connection

// Redirect non-dentists to login page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'dentist') {
    header('Location: login.php');
    exit();
}

// Fetch dentist's ID from session
$dentist_id = $_SESSION['user_id']; // Replace with your actual session key for user_id or dentist_id

// Check if the form is submitted to update payment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointment_id = $_POST['appointment_id'];
    $amount_to_be_paid = $_POST['amount_to_be_paid'];
    $payment_status = $_POST['payment_status'];
    $transaction_number = $_POST['transaction_number'] ?? null;
    $method_id = $_POST['method_id']; // Assuming you have predefined payment methods
    $receipt_path = $_POST['receipt_path'] ?? null; // Path to the receipt file if uploaded
    $updated_amount = $_POST['updated_amount']; // The updated amount_to_be_paid value

    // Validate the input amount
    if (empty($updated_amount) || !is_numeric($updated_amount) || $updated_amount <= 0) {
        echo "Invalid amount to be paid.";
        exit;
    }

    // Get the current payment details
    $stmt = $conn->prepare("SELECT total_amount, amount_to_be_paid FROM payments WHERE appointment_id = ?");
    $stmt->bind_param("i", $appointment_id);
    $stmt->execute();
    $stmt->bind_result($total_amount, $amount_to_be_paid);
    $stmt->fetch();
    $stmt->close();

    // Update the amount_to_be_paid value and adjust payment status
    $new_amount_to_be_paid = $amount_to_be_paid + $updated_amount;  // Add the new amount to the existing amount

    // Calculate remaining balance
    $remaining_balance = $total_amount - $new_amount_to_be_paid;

    // If the remaining balance is zero or less, set payment status to 'paid'
    if ($payment_status === 'paid' || $remaining_balance <= 0) {
        $payment_status = 'paid';
    } elseif ($new_amount_to_be_paid < $total_amount) {
        $payment_status = 'partial_payment';
    } elseif ($new_amount_to_be_paid > 0) {
        $payment_status = 'downpayment';
    }

    // Update the payment record in the database
    $update_sql = "
        UPDATE payments SET 
            amount_to_be_paid = ?, 
            payment_status = ?, 
            transaction_number = ?, 
            method_id = ?, 
            receipt_path = ?,
            payment_date = NOW() 
        WHERE appointment_id = ?
    ";

    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("dsssssi", $new_amount_to_be_paid, $payment_status, $transaction_number, $method_id, $receipt_path, $appointment_id);
    $stmt->execute();
    $stmt->close();

    echo "Payment updated successfully!";
    exit; // Exit after the update
}

// Fetch all appointments with pending, partial payment, or downpayment status for the logged-in dentist
$sql = "
    SELECT a.appointment_id, a.patient_id, a.appointment_date, p.total_amount, p.amount_to_be_paid, 
           p.payment_status, u.first_name, u.last_name, u.email AS patient_email
    FROM appointments a
    JOIN users u ON a.patient_id = u.user_id
    LEFT JOIN payments p ON a.appointment_id = p.appointment_id
    WHERE a.dentist_id = ? AND (p.payment_status = 'pending' OR p.payment_status = 'partial_payment' OR p.payment_status = 'downpayment')
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $dentist_id);
$stmt->execute();
$stmt->bind_result($appointment_id, $patient_id, $appointment_date, $total_amount, $amount_to_be_paid, 
                   $payment_status, $first_name, $last_name, $patient_email);

// Store the result in an array
$appointments = [];
while ($stmt->fetch()) {
    // Calculate the remaining balance
    $remaining_balance = $total_amount - $amount_to_be_paid;

    // Only add appointments where amount_to_be_paid is less than total_amount (not fully paid)
    if ($amount_to_be_paid < $total_amount) {
        $appointments[] = [
            'appointment_id' => $appointment_id,
            'patient_id' => $patient_id,
            'appointment_date' => $appointment_date,
            'total_amount' => $total_amount,
            'amount_to_be_paid' => $amount_to_be_paid,
            'remaining_balance' => $remaining_balance, // Store the remaining balance here
            'payment_status' => $payment_status,
            'patient_name' => $first_name . ' ' . $last_name,
            'patient_email' => $patient_email,
        ];
    }
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitor Pending Payments</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .container {
            width: 80%;
            margin: 0 auto;
            padding-top: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        .button {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            cursor: pointer;
            text-decoration: none;
        }
        .button:hover {
            background-color: #0056b3;
        }
        input[type="number"] {
            width: 120px;
            padding: 5px;
        }
        select {
            padding: 5px;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Pending Payments</h1>

    <table>
        <thead>
            <tr>
                <th>Appointment ID</th>
                <th>Patient Name</th>
                <th>Email</th>
                <th>Appointment Date</th>
                <th>Total Amount</th>
                <th>Amount to be Paid</th>
                <th>Remaining Balance</th>
                <th>Payment Status</th>
                <th>Update Payment</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($appointments as $appointment): ?>
                <tr>
                    <td><?= htmlspecialchars($appointment['appointment_id']) ?></td>
                    <td><?= htmlspecialchars($appointment['patient_name']) ?></td>
                    <td><?= htmlspecialchars($appointment['patient_email']) ?></td>
                    <td><?= htmlspecialchars($appointment['appointment_date']) ?></td>
                    <td><?= number_format($appointment['total_amount'], 2) ?></td>
                    <td><?= number_format($appointment['amount_to_be_paid'], 2) ?></td>
                    <td><?= number_format($appointment['remaining_balance'], 2) ?></td>
                    <td><?= htmlspecialchars($appointment['payment_status']) ?></td>
                    <td>
                        <!-- Form to input amount to be paid and select payment status -->
                        <form action="dentist_handle_payments.php" method="POST">
                            <input type="number" name="updated_amount" min="0" step="0.01" required>
                            <select name="payment_status" required>
                                <option value="downpayment" <?= $appointment['payment_status'] === 'downpayment' ? 'selected' : '' ?>>Downpayment</option>
                                <option value="partial_payment" <?= $appointment['payment_status'] === 'partial_payment' ? 'selected' : '' ?>>Partial Payment</option>
                                <option value="paid" <?= $appointment['payment_status'] === 'paid' ? 'selected' : '' ?>>Paid</option>
                                <option value="pending" <?= $appointment['payment_status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                            </select>
                            <input type="hidden" name="appointment_id" value="<?= htmlspecialchars($appointment['appointment_id']) ?>">
                            <button type="submit" class="button">Update Payment</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

</body>
</html>
