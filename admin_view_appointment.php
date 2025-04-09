<?php
include 'login_check.php'; // Ensure user is logged in
include 'db_config.php';   // Database connection
include 'admin_check.php'; // Ensure the user is an admin

// Include mPDF library (using Composer autoloader)
require_once __DIR__ . '/vendor/autoload.php';

// Initialize an array to store messages
$messages = [];

// Get the appointment_id from the URL
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $appointment_id = $_GET['id'];

    // Query to get the appointment details, including payment info and other related data
    $sql = "
    SELECT appointments.appointment_id, appointments.appointment_date, appointments.appointment_time, appointments.appointment_status,
           appointments.appointment_created_at, -- Add this line
           patients.Patient_ID AS patient_id, patients.First_Name AS patient_first_name, patients.Last_Name AS patient_last_name,
           GROUP_CONCAT(s.service_name) AS services,
           p.total_amount AS payment_amount, tr.diagnosis, tr.medication_prescribed, 
           tr.upper_teeth_left, tr.lower_teeth_left, tr.teeth_part, tr.follow_up_date, pr.pres_file,
           p.payment_id  -- Fetch the payment_id from the payments table
    FROM appointments
    JOIN patients ON appointments.patient_id = patients.Patient_ID
    LEFT JOIN appointment_services aps ON appointments.appointment_id = aps.appointment_id
    LEFT JOIN services s ON aps.service_id = s.service_id
    LEFT JOIN treatment_records tr ON appointments.appointment_id = tr.appointment_id
    LEFT JOIN prescriptions pr ON appointments.appointment_id = pr.appointment_id
    LEFT JOIN payments p ON appointments.appointment_id = p.appointment_id
    WHERE appointments.appointment_id = ?
    GROUP BY appointments.appointment_id
 ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $appointment_id); // Bind the appointment_id to the query

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $appointment = $result->fetch_assoc();
        } else {
            echo "Appointment not found.";
            exit();
        }
    } else {
        echo "Error fetching appointment details: " . $stmt->error;
        exit();
    }

    $stmt->close();

    // Fetch the total transaction amount for the appointment
    $transaction_sql = "
        SELECT SUM(transaction_amount) AS total_transactions
        FROM transaction
        WHERE payment_id = ?";
    
    $stmt = $conn->prepare($transaction_sql);
    $stmt->bind_param("i", $appointment['payment_id']); // Use payment_id here
    
    if ($stmt->execute()) {
        $transaction_result = $stmt->get_result();
        if ($transaction_result->num_rows > 0) {
            $transaction_data = $transaction_result->fetch_assoc();
            $total_transactions = $transaction_data['total_transactions'] ?? 0;
        } else {
            $total_transactions = 0;
        }
    } else {
        echo "Error fetching transaction data: " . $stmt->error;
        exit();
    }

    $stmt->close();

    // Calculate the remaining balance
    $remaining_balance = $appointment['payment_amount'] - $total_transactions;

} else {
    echo "Appointment ID is missing.";
    exit();
}

// Handle payment form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $transaction_amount = $_POST['transaction_amount'];
    $pstat_id = $_POST['pstat_id'];
    $due_date = $_POST['due_date'] ?? null; // Optional due date for downpayment
    $payment_id = $appointment['payment_id']; // Use the payment_id from the appointment details

    // Validate and sanitize inputs
    if (empty($transaction_amount)) {
        $messages[] = "Transaction amount is required.";
    } elseif ($pstat_id == 1 && empty($due_date)) {
        $messages[] = "Due date is required for downpayment.";
    } else {
        // Set due_date to NULL if not provided and not required
        if ($pstat_id != 1) {
            $due_date = null;
        }

        // Prepare query to insert into transaction table
        $insert_sql = "INSERT INTO transaction (payment_id, pstat_id, due_date, transaction_amount, receipts)
                       VALUES (?, ?, ?, ?, ?)";

        if ($stmt = $conn->prepare($insert_sql)) {
            $stmt->bind_param("iisss", $payment_id, $pstat_id, $due_date, $transaction_amount, $receipt_path);

            if ($stmt->execute()) {
                $messages[] = "Transaction recorded successfully.";

                // Get the transaction_id of the inserted transaction record
                $transaction_id = $stmt->insert_id;

                // Recalculate remaining balance after the transaction
                $remaining_balance -= $transaction_amount;

                // If the remaining balance is 0, update the payment status to "Paid"
                if ($remaining_balance == 0) {
                    // Also update the payment status in the payments table to "Paid"
                    $update_payment_status_sql = "UPDATE payments SET payment_status = 'Paid' WHERE payment_id = ?";
                    if ($stmt = $conn->prepare($update_payment_status_sql)) {
                        $stmt->bind_param("i", $payment_id);
                        if ($stmt->execute()) {
                            $messages[] = "Payment status updated to 'Paid'.";
                        } else {
                            $messages[] = "Error updating payment status: " . $stmt->error;
                        }
                        $stmt->close();
                    } else {
                        $messages[] = "Error preparing statement for updating payment status: " . $conn->error;
                    }
                }

                // Generate receipt PDF using mPDF
                generateReceiptPDF($transaction_id, $appointment['patient_id'], $payment_id, $transaction_amount, $pstat_id, $due_date);
            } else {
                $messages[] = "Error recording transaction: " . $stmt->error;
            }
        } else {
            $messages[] = "Error preparing statement: " . $conn->error;
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Appointment - Dental Clinic Management System</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<style>
    /* General Styles */
body {
    font-family: Arial, sans-serif;
    background-color: #f4f4f4;
    margin: 0;
    padding: 0;
    color: #333;
}

h1, h2, h3 {
    color: #2c3e50;
}

.main-content {
    max-width: 800px;
    margin: 20px auto;
    padding: 20px;
    background: #fff;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    border-radius: 8px;
}

/* Notification Styles */
.notification {
    padding: 10px;
    margin-bottom: 20px;
    border-radius: 5px;
    background-color: #e9f5ff;
    border: 1px solid #b6d8ff;
    color: #2c3e50;
}

.notification p {
    margin: 0;
}

/* Appointment Details Styles */
.appointment-details {
    margin-bottom: 30px;
}

.appointment-details p {
    margin: 10px 0;
    font-size: 16px;
}

.appointment-details strong {
    color: #2c3e50;
}

/* Payment Form Styles */
/* Payment Form Styles */
.payment-form {
    background-color: #f9f9f9;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #ddd;
    margin-top: 20px;
}

.payment-form h3 {
    margin-top: 0;
    color: #2c3e50;
}

.payment-form label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #2c3e50;
}

.payment-form input[type="number"],
.payment-form input[type="date"],
.payment-form select {
    width: 100%;
    padding: 10px;
    margin-bottom: 15px;
    border: 1px solid #ccc;
    border-radius: 5px;
    font-size: 14px;
    color: #333;
    background-color: #fff;
    transition: border-color 0.3s ease;
    box-sizing: border-box; /* Ensure padding and border are included in the width */
}

.payment-form input[type="number"]:focus,
.payment-form input[type="date"]:focus,
.payment-form select:focus {
    border-color: #3498db;
    outline: none;
}

.payment-form select {
    appearance: none; /* Remove default arrow */
    background-image: url('data:image/svg+xml;utf8,<svg fill="%232c3e50" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M7 10l5 5 5-5z"/></svg>');
    background-repeat: no-repeat;
    background-position: right 10px center;
    background-size: 12px;
}

.payment-form button {
    width: 100%;
    padding: 12px;
    background-color: #3498db;
    color: #fff;
    border: none;
    border-radius: 5px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.payment-form button:hover {
    background-color: #2980b9;
}

/* Back Button Styles */
.back-button {
    display: inline-block;
    margin-bottom: 20px;
    padding: 10px 15px;
    background-color: #3498db;
    color: #fff;
    text-decoration: none;
    border-radius: 5px;
    font-size: 14px;
    transition: background-color 0.3s ease;
}

.back-button:hover {
    background-color: #2980b9;
}

.back-button i {
    margin-right: 5px;
}
/* Responsive Design */
@media (max-width: 768px) {
    .main-content {
        padding: 15px;
    }

    .appointment-details p {
        font-size: 14px;
    }

    .payment-form {
        padding: 15px;
    }

    .payment-form input[type="number"],
    .payment-form input[type="date"],
    .payment-form select {
        font-size: 14px;
        padding: 8px;
    }

    .payment-form button {
        font-size: 14px;
        padding: 10px;
    }
}
    </style>
<body>
    

        <main class="main-content">
        <a href="javascript:history.back()" class="back-button">
            <i class="fas fa-arrow-left"></i> Back
        </a>
            <h1>Appointment Details</h1>

            <?php if (!empty($messages)): ?>
                <div class="notification">
                    <?php foreach ($messages as $message): ?>
                        <p><?php echo $message; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="appointment-details">
                <p><strong>Patient Name:</strong> <?php echo $appointment['patient_first_name'] . ' ' . $appointment['patient_last_name']; ?></p>
                <p><strong>Appointment Date:</strong> <?php echo $appointment['appointment_date']; ?></p>
                <p><strong>Appointment Status:</strong> <?php echo $appointment['appointment_status']; ?></p>
                <p><strong>Appointment Created At:</strong> <?php echo date('F d, Y H:i:s', strtotime($appointment['appointment_created_at'])); ?></p> 
                <p><strong>Services:</strong> <?php echo $appointment['services']; ?></p>
                <p><strong>Total Amount:</strong> PHP <?php echo number_format($appointment['payment_amount'], 2); ?></p>
                <p><strong>Remaining Balance:</strong> PHP <?php echo number_format($remaining_balance, 2); ?></p>
                    </div>

                    <div class="payment-form">
                <h3>Make a Payment</h3>
                <form method="POST" action="">
                    <label for="transaction_amount">Transaction Amount:</label>
                    <input type="number" name="transaction_amount" id="transaction_amount" required>

                    <label for="pstat_id">Payment Status:</label>
                    <select name="pstat_id" id="pstat_id" required>
                        <option value="1">Downpayment</option>
                        <option value="2">Full Payment</option>
                    </select>

                    <label for="due_date">Due Date (for Downpayment):</label>
                    <input type="date" name="due_date" id="due_date">

                    <button type="submit">Submit Payment</button>
                </form>
            </div>
        </main>
    </div>
</body>
</html>

<?php
// Function to generate receipt PDF and store it under the transaction
function generateReceiptPDF($transaction_id, $patient_id, $payment_id, $transaction_amount, $pstat_id, $due_date) {
    global $conn;

    // Fetch patient details
    $patient_sql = "SELECT First_Name, Last_Name FROM patients WHERE Patient_ID = ?";
    $stmt = $conn->prepare($patient_sql);
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $patient_result = $stmt->get_result();
    $patient = $patient_result->fetch_assoc();
    $patient_name = $patient['First_Name'] . ' ' . $patient['Last_Name']; // Full name of patient

    // Fetch payment details (total amount and payment status)
    $payment_sql = "SELECT total_amount, payment_status FROM payments WHERE payment_id = ?";
    $stmt = $conn->prepare($payment_sql);
    $stmt->bind_param("i", $payment_id);
    $stmt->execute();
    $payment_result = $stmt->get_result();
    $payment = $payment_result->fetch_assoc();
    $total_amount = number_format($payment['total_amount'], 2); // Total amount (formatted)
    $payment_status = $payment['payment_status']; // Payment status (e.g., 'Paid', 'Unpaid', etc.)

    // Fetch transaction details (amount and due date)
    $transaction_sql = "SELECT transaction_amount, due_date FROM transaction WHERE transaction_id = ?";
    $stmt = $conn->prepare($transaction_sql);
    $stmt->bind_param("i", $transaction_id);
    $stmt->execute();
    $transaction_result = $stmt->get_result();
    $transaction = $transaction_result->fetch_assoc();
    $transaction_amount = number_format($transaction['transaction_amount'], 2); // Transaction amount (formatted)
    $due_date = $transaction['due_date'] ? date('F d, Y', strtotime($transaction['due_date'])) : 'N/A'; // Format due date if it exists

    // Current Date for Receipt
    $current_date = date('F d, Y');

    // HTML content for the receipt
    $html = "
    <div style='text-align:center;'>
        <img src='images/lads.png' alt='LAD Dental Clinic' style='width: 70px;'>
        <h1>LAD DENTAL CLINIC</h1>
        <p>Vamenta Blvd. Carmen, Cagayan de Oro City</p>
        <p><strong>Date:</strong> $current_date</p>
        <p><strong>Transaction ID:</strong> $transaction_id</p>
        <hr style='border:1px solid #000;' />
        <h2 style='text-align:center;'>Dental Clinic Receipt</h2>
        <p><strong>Patient:</strong> $patient_name</p>
        <p><strong>Payment Status:</strong> $payment_status</p>
        <p><strong>Total Payment Amount:</strong> PHP $total_amount</p>
        <p><strong>Transaction Amount:</strong> PHP $transaction_amount</p>
        <p><strong>Due Date:</strong> $due_date</p>
    </div>
    ";

    // Generate the PDF with mPDF
    $mpdf = new \Mpdf\Mpdf();
    $mpdf->WriteHTML($html);

    // Define the file path to save the PDF in the receipts folder
    $receipt_dir = 'receipts/';
    if (!is_dir($receipt_dir)) {
        mkdir($receipt_dir, 0777, true); // Create the folder if it doesn't exist
    }

    $file_name = "receipt_" . $transaction_id . ".pdf";
    $file_path = $receipt_dir . $file_name;

    // Save the PDF to the receipts folder
    $mpdf->Output($file_path, 'F'); // Save the file locally

    // Store the file path in the database
    $update_sql = "UPDATE transaction SET receipts = ? WHERE transaction_id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("si", $file_path, $transaction_id);
    $stmt->execute();
    $stmt->close();
}

?>
