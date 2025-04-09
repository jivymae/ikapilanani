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
    <!-- Include Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* General Styles */
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
            color: #333;
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        /* Main Content Styles */
        .main-content {
            flex: 1;
            padding: 20px;
            background-color: #fff;
        }

        .main-content h1 {
            font-size: 2rem;
            color: #2c3e50;
            margin-bottom: 20px;
        }

        .main-content h2 {
            font-size: 1.5rem;
            color: #2c3e50;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px; /* Space between icon and text */
        }

        .back-btn {
            cursor: pointer;
            color: #2c3e50;
            font-size: 1.2rem;
            transition: color 0.3s ease;
        }

        .back-btn:hover {
            color: #1abc9c;
        }

        /* Patient Details Section */
        #patient-detail {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        #patient-detail .patient-info {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        #patient-detail .patient-info p {
            margin: 0;
            font-size: 1rem;
            color: #555;
        }

        #patient-detail .patient-info strong {
            color: #2c3e50;
        }

        /* Pending Payments Table */
        #payment-details {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        #payment-details table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        #payment-details table th,
        #payment-details table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        #payment-details table th {
            background-color: #2c3e50;
            color: #fff;
            font-weight: bold;
        }

        #payment-details table tr:hover {
            background-color: #f1f1f1;
        }

        #payment-details table td {
            color: #555;
        }

        /* Button Styles */
        .view-transaction-btn {
            background-color: #1abc9c;
            color: #fff;
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background-color 0.3s ease;
        }

        .view-transaction-btn:hover {
            background-color: #16a085;
        }

        /* Modal Styles */
        .modal {
            display: none; /* Hidden by default */
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5); /* Semi-transparent background */
            justify-content: center; /* Center horizontally */
            align-items: center; /* Center vertically */
        }

        .modal-content {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 500px; /* Max width for the modal */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            position: relative;
        }

        .close-btn {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 24px;
            color: #aaa;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .close-btn:hover {
            color: #333;
        }

        #transactionModal h2 {
            font-size: 1.5rem;
            color: #2c3e50;
            margin-bottom: 15px;
        }

        #transactionModal p {
            font-size: 1rem;
            color: #555;
            margin: 10px 0;
        }

        #transactionModal span {
            color: #333;
            font-weight: bold;
        }

        .receipts-link {
            color: #1abc9c;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .receipts-link:hover {
            color: #16a085;
        }

        /* Date Search Styles */
        .date-search {
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .date-search label {
            font-weight: bold;
            color: #2c3e50;
        }

        .date-search input[type="date"] {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }

        .date-search button {
            background-color: #1abc9c;
            color: #fff;
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background-color 0.3s ease;
        }

        .date-search button:hover {
            background-color: #16a085;
        }
    </style>
</head>
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
        <section id="payment-details">
            <!-- Back Button Icon -->
            <h2>
                <i class="fas fa-arrow-left back-btn" onclick="history.back()"></i>
                Pending Payments
            </h2>

            <!-- Date Search Input -->
            <div class="date-search">
                <label for="dateFilter">Filter by Date:</label>
                <input type="date" id="dateFilter" onchange="filterTableByDate()">
                <button onclick="clearDateFilter()">Clear Filter</button>
            </div>

            <?php if ($payments_result->num_rows > 0): ?>
                <table id="paymentsTable">
                    <thead>
                        <tr>
                            <th>Patient Name</th>
                            <th>Amount</th>
                            <th>Transaction Amount</th>
                            <th>Remaining Balance</th>
                            <th>Status</th>
                            <th>Created At</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($payment = $payments_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($patient['First_Name'] . ' ' . $patient['Last_Name']); ?></td>
                                <td><?php echo htmlspecialchars($payment['total_amount'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($payment['transaction_amount'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($payment['remaining_balance'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($payment['payment_status'] ?? 'N/A'); ?></td>
                                <td class="created-at"><?php echo date('Y-m-d', strtotime($payment['created_at']) ?: 'N/A'); ?></td>
                                <td><button class="view-transaction-btn" data-payment-id="<?php echo htmlspecialchars($payment['payment_id']); ?>">View Transaction</button></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No pending payments found for this patient.</p>
            <?php endif; ?>
        </section>
    </main>
</div>

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

// Function to filter the table by date
function filterTableByDate() {
    var dateFilter = document.getElementById('dateFilter').value; // Get the selected date
    var table = document.getElementById('paymentsTable');
    var rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

    for (var i = 0; i < rows.length; i++) {
        var row = rows[i];
        var dateCell = row.getElementsByClassName('created-at')[0].textContent; // Get the date from the "Created At" column

        if (dateFilter === '' || dateCell === dateFilter) {
            row.style.display = ''; // Show the row if the date matches or no filter is applied
        } else {
            row.style.display = 'none'; // Hide the row if the date does not match
        }
    }
}

// Function to clear the date filter
function clearDateFilter() {
    document.getElementById('dateFilter').value = ''; // Clear the date input
    filterTableByDate(); // Reset the table to show all rows
}
</script>
</body>
</html>