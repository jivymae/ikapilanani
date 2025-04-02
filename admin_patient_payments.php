<?php
include 'login_check.php'; // Ensure user is logged in
include 'db_config.php';   // Database connection
include 'admin_check.php'; // Ensure the user is an admin
require_once __DIR__ . '/vendor/autoload.php';  // Include mPDF library

// Fetch all patients with a remaining balance greater than 0
$balance_filter = isset($_GET['filter_balance']) ? $_GET['filter_balance'] : 'all'; // Get balance filter option
$due_date_filter = isset($_GET['filter_due_date']) ? $_GET['filter_due_date'] : ''; // Get due_date filter option
// Get the search query for the patient name
$search_patient = isset($_GET['search_patient']) ? $_GET['search_patient'] : ''; // Get the search query

// Modify SQL query based on filter option
$sql = "
    SELECT 
        appointments.appointment_id, 
        appointments.patient_id, 
        patients.First_Name AS patient_first_name, 
        patients.Last_Name AS patient_last_name,
        p.payment_id, 
        p.total_amount AS payment_amount, 
        IFNULL(SUM(t.transaction_amount), 0) AS total_transactions,
        (p.total_amount - IFNULL(SUM(t.transaction_amount), 0)) AS remaining_balance,
        latest_transaction.due_date AS latest_due_date,
        t.transaction_id, t.transaction_amount, t.due_date AS transaction_due_date, t.created_at AS transaction_created_at, t.receipts
    FROM appointments
    JOIN patients ON appointments.patient_id = patients.Patient_ID
    LEFT JOIN payments p ON appointments.appointment_id = p.appointment_id
    LEFT JOIN transaction t ON p.payment_id = t.payment_id
    LEFT JOIN (
        SELECT 
            payment_id, 
            due_date,
            created_at,
            ROW_NUMBER() OVER (PARTITION BY payment_id ORDER BY created_at DESC) AS rn
        FROM transaction
    ) latest_transaction ON t.payment_id = latest_transaction.payment_id AND latest_transaction.rn = 1
    GROUP BY appointments.appointment_id, appointments.patient_id, patients.First_Name, patients.Last_Name, p.payment_id, p.total_amount
";

// Apply the balance filter
if ($balance_filter === 'zero') {
    $sql .= " HAVING remaining_balance = 0";
} elseif ($balance_filter === 'greater_than_zero') {
    $sql .= " HAVING remaining_balance > 0";
} else {
    $sql .= " HAVING remaining_balance >= 0";
}

// Apply due date filter if present
if (!empty($due_date_filter)) {
    $sql .= " AND t.due_date = '$due_date_filter'";
}

// Apply the search filter if present (Search by patient name)
if (!empty($search_patient)) {
    $search_patient = $conn->real_escape_string($search_patient); // Escape special characters
    $sql .= " AND (patients.First_Name LIKE '%$search_patient%' OR patients.Last_Name LIKE '%$search_patient%')";
}

$result = $conn->query($sql);
$patients_with_balance = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $patients_with_balance[] = $row;
    }
} else {
    $no_patients_message = "No patients with outstanding balance found."; // Set the message
}

// Handle payment form submission
// (Remaining code stays the same)





// Handle payment form submission
// Handle payment form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $transaction_amount = $_POST['transaction_amount'];
  $pstat_id = $_POST['pstat_id'];
  $patient_id = $_POST['patient_id'];
  $payment_id = $_POST['payment_id'];  // Get payment_id from form
  $due_date = $_POST['due_date']; // Get due_date from form

  // Check if due_date is empty, and set it to NULL
  if (empty($due_date)) {
      $due_date = NULL;  // Set due_date to NULL if not provided
  }

  // Validate and sanitize inputs
  if (empty($transaction_amount) || empty($pstat_id) || empty($payment_id)) {
      echo "Transaction amount, payment status, and payment_id are required.";
      exit();
  }

  // Prepare the SQL query to insert a transaction
  $insert_sql = "INSERT INTO transaction (payment_id, pstat_id, due_date, transaction_amount)
                 VALUES (?, ?, ?, ?)";

  // Prepare the statement
  $stmt = $conn->prepare($insert_sql);

  // Bind parameters
  $stmt->bind_param("iiss", $payment_id, $pstat_id, $due_date, $transaction_amount);

  // Execute the statement
  if ($stmt->execute()) {
      // Fetch the transaction ID after inserting
      $transaction_id = $stmt->insert_id;

      // Generate receipt PDF using mPDF
      generateReceiptPDF($transaction_id, $patient_id, $payment_id, $transaction_amount, $pstat_id, $due_date);

      echo "Transaction recorded successfully.";
  } else {
      echo "Error recording transaction: " . $stmt->error;
  }

  $stmt->close();
}


// Function to generate receipt PDF and store it under the transaction
function generateReceiptPDF($transaction_id, $patient_id, $payment_id, $transaction_amount, $pstat_id, $due_date) {
  global $conn;

  // Fetch patient and payment details
  $patient_sql = "SELECT First_Name, Last_Name FROM patients WHERE Patient_ID = ?";
  $stmt = $conn->prepare($patient_sql);
  $stmt->bind_param("i", $patient_id);
  $stmt->execute();
  $patient_result = $stmt->get_result();
  $patient = $patient_result->fetch_assoc();
  
  $payment_sql = "SELECT total_amount FROM payments WHERE payment_id = ?";
  $stmt = $conn->prepare($payment_sql);
  $stmt->bind_param("i", $payment_id);
  $stmt->execute();
  $payment_result = $stmt->get_result();
  $payment = $payment_result->fetch_assoc();

  // Prepare data for the receipt
  $patient_name = $patient['First_Name'] . ' ' . $patient['Last_Name'];
  $total_amount = number_format($payment['total_amount'], 2);
  $balance_due = number_format($transaction_amount, 2);
  $payment_status = $pstat_id == 1 ? "Downpayment" : "Full Payment";
  $due_date_display = $due_date ? date('F d, Y', strtotime($due_date)) : 'N/A';

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
      <p><strong>Transaction Amount:</strong> PHP $balance_due</p>
      <p><strong>Due Date:</strong> $due_date_display</p>
      
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




<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patients with Outstanding Balances - Dental Clinic Management System</title>
    <link rel="stylesheet" type="text/css" href="css/style.css">
    <style>

      /* General Styles */
body {
    font-family: Arial, sans-serif;
    background-color: #f4f7f9;
    margin: 0;
    padding: 0;
    color: #333;
}

.container {
    display: flex;
    height: 100vh;
}

.main-content {
    margin-left: 300px;
    padding: 20px;
    flex: 1;
}

h1 {
    color: #2c3e50;
    font-size: 28px;
    margin-bottom: 20px;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    background-color: #fff;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

table th, table td {
    padding: 12px 15px;
    border: 1px solid #ddd;
    text-align: left;
}

table th {
    background-color: #3498db;
    color: white;
}

table tr:nth-child(even) {
    background-color: #f9f9f9;
}

table tr:hover {
    background-color: #f1f1f1;
}

table td a {
    color: #3498db;
    text-decoration: none;
    font-weight: bold;
}

table td a:hover {
    text-decoration: underline;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0, 0, 0, 0.4);
    padding-top: 60px;
}

.modal-content {
    background-color: #fff;
    margin: 5% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 80%;
    max-width: 500px;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.modal h2 {
    color: #2c3e50;
    font-size: 22px;
    margin-bottom: 20px;
}

.modal form {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.modal form div {
    display: flex;
    flex-direction: column;
}

.modal form label {
    font-size: 16px;
    color: #333;
    margin-bottom: 5px;
}

.modal form input,
.modal form select {
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 16px;
    width: 100%;
}

.modal form button {
    background-color: #3498db;
    color: white;
    padding: 12px;
    border: none;
    border-radius: 5px;
    font-size: 16px;
    cursor: pointer;
}

.modal form button:hover {
    background-color: #2980b9;
}

/* Close button */
.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.close:hover,
.close:focus {
    color: black;
    text-decoration: none;
    cursor: pointer;
}

/* Responsive Styles */
@media screen and (max-width: 768px) {
    .container {
        flex-direction: column;
    }

    .sidebar {
        width: 100%;
        height: auto;
        padding-top: 15px;
    }

    .main-content {
        margin-left: 0;
        padding: 15px;
    }

    table {
        font-size: 14px;
    }
}


        /* Modal styles */
        .modal {
            display: none; /* Hidden by default */
            position: fixed;
            z-index: 1; /* Sit on top */
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto; /* Enable scroll if needed */
            background-color: rgb(0,0,0); /* Fallback color */
            background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
            padding-top: 60px;
        }

        /* Modal content */
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
        }

        /* Close button */
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <h2 class="logo">
                <img src="images/lads.png" alt="Dental Clinic Logo">
                <h1>LAD DCAMS</h1>
            </h2>
            <ul>
                <li><a href="admin_dashboard.php">Dashboard</a></li>
                <li><a href="appointments.php">Appointments</a></li>
                <li><a href="patients.php">Patients</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
        <?php
        // If no patients found, display the message as a prompt inside the main content
        if (!empty($no_patients_message)) {
            echo "<script>alert('$no_patients_message');</script>"; // Show alert as prompt
       
        }
        ?>
            <section id="patients-balance">
                <h1>Patients with Outstanding Balances</h1>
<!-- Filter Section -->
<div class="filter-section">
    <form method="GET" action="">
        <label for="balance_filter">Filter by Balance:</label>
        <select name="filter_balance" id="balance_filter">
            <option value="all" <?php echo ($balance_filter == 'all' ? 'selected' : ''); ?>>All</option>
            <option value="zero" <?php echo ($balance_filter == 'zero' ? 'selected' : ''); ?>>Zero Balance</option>
            <option value="greater_than_zero" <?php echo ($balance_filter == 'greater_than_zero' ? 'selected' : ''); ?>>Greater Than Zero</option>
        </select>

        <label for="due_date_filter">Filter by Due Date:</label>
        <input type="date" name="filter_due_date" id="due_date_filter" value="<?php echo htmlspecialchars($due_date_filter); ?>">

        <button type="submit">Apply Filter</button>
    </form>
</div>


<table>
    <thead>
        <tr>
            <th>Patient Name</th>
            <th>Remaining Balance</th>
            <th>Latest Due Date</th>
            <th>Action</th>
            <th>View Transactions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($patients_with_balance as $patient): ?>
            <tr>
                <td><?php echo htmlspecialchars($patient['patient_first_name'] . ' ' . $patient['patient_last_name']); ?></td>
                <td><?php echo number_format($patient['remaining_balance'], 2); ?></td>
                <td>
                    <?php echo $patient['latest_due_date'] ? date('F d, Y', strtotime($patient['latest_due_date'])) : 'N/A'; ?>
                </td>
                <td><a href="#" class="record-payment-link" data-patient-id="<?php echo $patient['patient_id']; ?>" data-appointment-id="<?php echo $patient['appointment_id']; ?>" data-payment-id="<?php echo $patient['payment_id']; ?>">Record Payment</a></td>
                <td><button class="view-transaction-btn" data-payment-id="<?php echo $patient['payment_id']; ?>" data-patient-id="<?php echo $patient['patient_id']; ?>" data-appointment-id="<?php echo $patient['appointment_id']; ?>">View Transactions</button></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>



            </section>

            <!-- Modal Payment Form Section -->
            <?php foreach ($patients_with_balance as $patient): ?>
                <div id="payment-modal-<?php echo $patient['appointment_id']; ?>" class="modal">
                    <div class="modal-content">
                        <span class="close" data-modal-id="payment-modal-<?php echo $patient['appointment_id']; ?>">&times;</span>
                        <h2>Record Payment for <?php echo htmlspecialchars($patient['patient_first_name'] . ' ' . $patient['patient_last_name']); ?> (Appointment ID: <?php echo $patient['appointment_id']; ?>)</h2>

                        <form method="POST" action="">
                            <input type="hidden" name="patient_id" value="<?php echo $patient['patient_id']; ?>">
                            <input type="hidden" name="payment_id" value="<?php echo $patient['payment_id']; ?>">  <!-- Pass payment_id here -->
                            <input type="hidden" name="appointment_id" value="<?php echo $patient['appointment_id']; ?>"> <!-- Pass appointment_id here -->

                            <div>
                                <label for="transaction_amount">Transaction Amount</label>
                                <input type="number" id="transaction_amount" name="transaction_amount" required>
                            </div>
                            <div>
                                <label for="pstat_id">Payment Status</label>
                                <select id="pstat_id" name="pstat_id" required>
                                    <option value="1">Downpayment</option>
                                    <option value="2">Full Payment</option>
                                </select>
                            </div>
                            <div id="due-date-container">
                                <label for="due_date">Due Date (If Downpayment)</label>
                                <input type="date" id="due_date" name="due_date">
                            </div>
                            <button type="submit">Submit Payment</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </main>
    </div>

    <script>
        // JavaScript to handle modal behavior
        const links = document.querySelectorAll('.record-payment-link');
        const modals = document.querySelectorAll('.modal');
        const closeButtons = document.querySelectorAll('.close');

        // Show modal when link is clicked
        links.forEach(link => {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                const appointmentId = this.dataset.appointmentId;  // Get appointment_id from data attribute
                const modal = document.getElementById('payment-modal-' + appointmentId);  // Show the corresponding modal
                modal.style.display = 'block';
            });
        });

        // Close modal when the close button is clicked
        closeButtons.forEach(button => {
            button.addEventListener('click', function () {
                const modalId = this.dataset.modalId;
                const modal = document.getElementById(modalId);
                modal.style.display = 'none';
            });
        });

        // Close modal if the user clicks outside of the modal content
        window.onclick = function(event) {
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        };
    </script>


<!-- Modal to View Transaction Details -->
<!-- Modal to View Transaction Details -->
<div id="transaction-modal" class="modal">
    <div class="modal-content">
        <span id="transaction-modal-close" class="close">&times;</span>  <!-- Added ID -->
        <h2>Transaction Details</h2>
        <div id="transaction-details">
            <!-- Transaction details will be inserted here dynamically -->
        </div>
    </div>
</div>

<script>
  // JavaScript to handle the transaction modal behavior
const transactionBtns = document.querySelectorAll('.view-transaction-btn');
const transactionModal = document.getElementById('transaction-modal');
const transactionDetails = document.getElementById('transaction-details');
const closeBtn = document.getElementById('transaction-modal-close');  // Select the specific close button

// Show the modal and fetch transaction details when "View Transactions" button is clicked
transactionBtns.forEach(button => {
    button.addEventListener('click', function (e) {
        e.preventDefault();
        
        const paymentId = this.dataset.paymentId;  // Get the payment_id from data attribute
        const patientId = this.dataset.patientId;
        const appointmentId = this.dataset.appointmentId;

        // Fetch transaction details via AJAX
        fetch(`get_transaction_details.php?payment_id=${paymentId}`)
            .then(response => response.json())
            .then(data => {
                // Display transaction details inside the modal
                let transactionHTML = '';
                data.transactions.forEach(transaction => {
                    transactionHTML += `
                       
                        <p><strong>Amount: PHP</strong> ${transaction.transaction_amount}</p>
                        <p><strong>Due Date:</strong> ${transaction.due_date ? transaction.due_date : 'N/A'}</p>
                        <p><strong>Receipt:</strong> <a href="${transaction.receipts}" target="_blank">View Receipt</a></p>
                        <p><strong>Created At:</strong> ${transaction.created_at}</p>
                        <hr>
                    `;
                });

                transactionDetails.innerHTML = transactionHTML;
                transactionModal.style.display = 'block';
            })
            .catch(error => {
                console.error('Error fetching transaction details:', error);
            });
    });
});

// Close modal when the close button is clicked
closeBtn.addEventListener('click', function () {
    transactionModal.style.display = 'none';
});

// Close modal if the user clicks outside of the modal content
window.onclick = function(event) {
    if (event.target === transactionModal) {
        transactionModal.style.display = 'none';
    }
};
</script>
</body>
</html>
