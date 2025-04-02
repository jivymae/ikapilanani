<?php
include 'db_config.php';

// Get the patient_id from the URL
$patient_id = intval($_GET['patient_id']); 

// Fetch patient details
$patient_query = "SELECT First_Name, Last_Name, Patient_ID FROM patients WHERE Patient_ID = ?";
$patient_stmt = $conn->prepare($patient_query);
$patient_stmt->bind_param("i", $patient_id);
$patient_stmt->execute();
$patient_result = $patient_stmt->get_result();

// Check if patient exists
if ($patient_result->num_rows > 0) {
    $patient = $patient_result->fetch_assoc();
} else {
    echo "No patient found with ID: $patient_id";
    exit;
}

// Fetch payments details, including created_at and due_date
$payments_query = "
    SELECT 
        payments.payment_id,
        payments.appointment_id,
        payments.patient_id,
        payments.total_amount,
        payments.transaction_number,
        payments.payment_status,
        payments.method_id,
        payments.created_at,  -- Added created_at field
        transaction.transaction_id,
        transaction.transaction_amount,  -- Added transaction_amount field
        transaction.receipts,
        transaction.due_date,
        (payments.total_amount - IFNULL(transaction.transaction_amount, 0)) AS remaining_balance  -- Calculate remaining balance
    FROM payments
    LEFT JOIN transaction ON payments.payment_id = transaction.payment_id
    WHERE payments.patient_id = ?
";

$payments_stmt = $conn->prepare($payments_query);
$payments_stmt->bind_param("i", $patient_id);
$payments_stmt->execute();
$payments_result = $payments_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Payments - Dental Clinic Management System</title>
    <link rel="stylesheet" type="text/css" href="css/style.css">
</head>
<style>
    /* Modal Styles */
.modal {
    display: none; /* Hidden by default */
    position: fixed; /* Stay in place */
    z-index: 1; /* Sit on top */
    left: 0;
    top: 0;
    width: 100%; /* Full width */
    height: 100%; /* Full height */
    background-color: rgba(0, 0, 0, 0.4); /* Black background with opacity */
}

/* Modal Content */
.modal-content {
    background-color: #fff;
    margin: 15% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 80%;
    max-width: 600px; /* Max width for the modal */
    border-radius: 8px; /* Rounded corners */
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
}

/* Close button (X) */
.close-btn {
    color: #aaa;
    font-size: 28px;
    font-weight: bold;
    position: absolute;
    top: 10px;
    right: 20px;
    cursor: pointer;
}

.close-btn:hover,
.close-btn:focus {
    color: black;
    text-decoration: none;
    cursor: pointer;
}

/* Style the modal content */
#transactionModal h2 {
    font-size: 24px;
    margin-bottom: 15px;
}

#transactionModal p {
    font-size: 16px;
    line-height: 1.5;
}

#transactionModal span {
    font-weight: bold;
    color: #333;
}

/* Button styling */
.view-transaction-btn {
    background-color: #4CAF50;
    color: white;
    padding: 10px 20px;
    border: none;
    cursor: pointer;
    border-radius: 5px;
    font-size: 16px;
}

.view-transaction-btn:hover {
    background-color: #45a049;
}

    </style>
<body>
<div class="container">
    <!-- Sidebar Menu -->
    <aside class="sidebar">
        <h2 class="logo">
            <img src="images/lads.png" alt="Dental Clinic Logo">
            <h1>LAD DCAMS</h1>
        </h2>
        <ul>
            <li><a href="admin_dashboard.php">Dashboard</a></li>
            <li><a href="patients.php">Patients</a></li>
            <li><a href="dentists.php">Dentists</a></li>
            <li><a href="appointments.php">Appointments</a></li>
            <li><a href="admin_add_services.php">Add Services</a></li>
            <li><a href="reports.php">Reports</a></li>
            <li><a href="admin_settings.php">Settings</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <section id="patient-info">
            <h1>Patient: <?php echo htmlspecialchars($patient['First_Name'] . ' ' . $patient['Last_Name']); ?></h1>
            <p><strong>Patient ID:</strong> <?php echo htmlspecialchars($patient['Patient_ID']); ?></p>
        </section>

        <section id="payment-details">
            <h2>Pending Payments</h2>
            <?php if ($payments_result->num_rows > 0): ?>
                <table>
                <thead>
    <tr>
        <th>Payment ID</th>
        <th>Patient Name</th>
        <th>Amount</th>
        <th>Transaction Amount</th>
        <th>Remaining Balance</th> <!-- Added Remaining Balance column -->
        <th>Status</th>
        <th>Created At</th>
    </tr>
</thead>
<tbody>
    <?php while ($payment = $payments_result->fetch_assoc()): ?>
        <tr>
            <td><?php echo htmlspecialchars($payment['payment_id'] ?? 'N/A'); ?></td>
            <td><?php echo htmlspecialchars($patient['First_Name'] . ' ' . $patient['Last_Name']); ?></td>
            <td><?php echo htmlspecialchars($payment['total_amount'] ?? 'N/A'); ?></td>
            <td><?php echo htmlspecialchars($payment['transaction_amount'] ?? 'N/A'); ?></td>
            <td><?php echo htmlspecialchars($payment['remaining_balance'] ?? 'N/A'); ?></td>
            <td><?php echo htmlspecialchars($payment['payment_status'] ?? 'N/A'); ?></td>
            <td><?php echo date('Y-m-d', strtotime($payment['created_at']) ?: 'N/A'); ?></td>
            <td><button class="view-transaction-btn" data-payment-id="<?php echo htmlspecialchars($payment['payment_id']); ?>">View Transaction</button></td> <!-- View Transaction Button -->
        </tr>
    <?php endwhile; ?>
</tbody>

<!-- Modal for displaying transaction details -->
<!-- Modal for displaying transaction details -->
<div id="transactionModal" class="modal">
    <div class="modal-content">
        <span class="close-btn">&times;</span>
        <h2>Transaction Details</h2>
        <p><strong>Transaction ID:</strong> <span id="modal-transaction-id"></span></p>
        <p><strong>Transaction Amount:</strong> <span id="modal-transaction-amount"></span></p>
        <p><strong>Receipts:</strong> <a href="#" id="modal-receipts" class="receipts-link" target="_blank">View Receipt</a></p>
        <p><strong>Created At:</strong> <span id="modal-created-at"></span></p>
    </div>
</div>

<script>
// Modal functionality
var modal = document.getElementById('transactionModal');
var closeBtn = document.getElementsByClassName('close-btn')[0];

// When the user clicks the "View Transaction" button
document.querySelectorAll('.view-transaction-btn').forEach(function(button) {
    button.addEventListener('click', function() {
        var paymentId = this.getAttribute('data-payment-id');
        fetchTransactionDetails(paymentId);
    });
});

// Close the modal when the close button is clicked
closeBtn.onclick = function() {
    modal.style.display = "none";
}

// Close the modal if the user clicks outside of the modal content
window.onclick = function(event) {
    if (event.target == modal) {
        modal.style.display = "none";
    }
}

// Function to fetch transaction details and display them in the modal
function fetchTransactionDetails(paymentId) {
    var xhr = new XMLHttpRequest();
    xhr.open('GET', 'get_transactions.php?payment_id=' + paymentId, true);
    xhr.onload = function() {
        if (xhr.status === 200) {
            var transaction = JSON.parse(xhr.responseText);

            // Populate the modal with the transaction data
            document.getElementById('modal-transaction-id').textContent = transaction.transaction_id;
            document.getElementById('modal-transaction-amount').textContent = transaction.transaction_amount;
            document.getElementById('modal-created-at').textContent = transaction.created_at;

            // Handle the receipts display
            var receiptLink = document.getElementById('modal-receipts');
            if (transaction.receipts) {
                var receiptPath = 'receipts/' + transaction.receipts.split('/').pop(); // Ensure we only get the filename from the full path
                receiptLink.setAttribute('href', receiptPath); // Set the correct file path
                receiptLink.textContent = 'View Receipt'; // Change the link text
            } else {
                receiptLink.textContent = 'No receipt available'; // In case no receipt is available
                receiptLink.removeAttribute('href'); // Remove the link if no receipt is found
            }

            // Display the modal
            modal.style.display = "block";
        } else {
            alert('Transaction details could not be loaded.');
        }
    };
    xhr.send();
}
</script>



                </table>
            <?php else: ?>
                <p>No pending payments found for this patient.</p>
            <?php endif; ?>
        </section>
    </main>
</div>
</body>
</html>

<?php
// Close the database connections
$patient_stmt->close();
$payments_stmt->close();
$conn->close();
?>
