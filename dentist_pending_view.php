<?php
session_start();
include 'db_config.php';

// Redirect non-dentists to login page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'dentist') {
    header('Location: login.php');
    exit();
}

// Check if the appointment_id is set in the URL
if (isset($_GET['appointment_id'])) {
    $appointment_id = $_GET['appointment_id'];

    // SQL query to fetch appointment details, patient info, payment info, and services
    $stmt = $conn->prepare("
        SELECT 
            a.appointment_id, a.appointment_date, a.appointment_time, 
            p.first_name, p.last_name, p.email AS patient_email, 
            pay.payment_status, pay.transaction_number,
            pay.total_amount, pm.method_name AS payment_method,
            GROUP_CONCAT(s.service_name SEPARATOR ', ') AS services
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        LEFT JOIN payments pay ON a.appointment_id = pay.appointment_id
        LEFT JOIN payment_methods pm ON pay.method_id = pm.method_id
        LEFT JOIN appointment_services aps ON a.appointment_id = aps.appointment_id
        LEFT JOIN services s ON aps.service_id = s.service_id
        WHERE a.appointment_id = ?
        GROUP BY a.appointment_id
    ");

    // Bind the appointment_id parameter
    $stmt->bind_param("i", $appointment_id);
    $stmt->execute();

    // Bind the results to variables
    $stmt->bind_result(
        $appointment_id, $appointment_date, $appointment_time, 
        $first_name, $last_name, $patient_email, $payment_status, 
        $transaction_number, 
        $total_amount, $payment_method, $services
    );
    
    // Fetch the data
    $stmt->fetch();
    $stmt->close();
} else {
    echo "Appointment ID is missing. Please provide a valid appointment ID.";
    exit(); // Stops further execution
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Appointment Details</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .container {
            width: 70%;
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
        .back-btn {
            margin-top: 20px;
            padding: 10px 15px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        .back-btn:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Appointment Details</h1>
    <table>
        <tr>
            <th>Appointment ID</th>
            <td><?php echo htmlspecialchars($appointment_id ?? ''); ?></td>
        </tr>
        <tr>
            <th>Patient Name</th>
            <td><?php echo htmlspecialchars($first_name ?? '') . ' ' . htmlspecialchars($last_name ?? ''); ?></td>
        </tr>
        <tr>
            <th>Patient Email</th>
            <td><?php echo htmlspecialchars($patient_email ?? ''); ?></td>
        </tr>
        <tr>
            <th>Appointment Date</th>
            <td><?php echo htmlspecialchars($appointment_date ?? ''); ?></td>
        </tr>
        <tr>
            <th>Appointment Time</th>
            <td><?php echo htmlspecialchars($appointment_time ?? ''); ?></td>
        </tr>
        <tr>
            <th>Payment Status</th>
            <td><?php echo htmlspecialchars($payment_status ?? ''); ?></td>
        </tr>
        <tr>
            <th>Payment Method</th>
            <td><?php echo htmlspecialchars($payment_method ?? ''); ?></td>
        </tr>
        
        <tr>
            <th>Total Amount</th>
            <td><?php echo htmlspecialchars($total_amount ?? ''); ?></td>
        </tr>
        <tr>
            <th>Services</th>
            <td><?php echo htmlspecialchars($services ?? 'No services assigned'); ?></td>
        </tr>
        <tr>
            <th>Receipt</th>
            <td>
                <?php if (!empty($receipt_path)): ?>
                    <img src="<?= htmlspecialchars($receipt_path ?? '') ?>" alt="Receipt" style="max-width: 200px;">
                <?php else: ?>
                    No Receipt Available
                <?php endif; ?>
            </td>
        </tr>
    </table>

    <a href="dentist_pending.php" class="back-btn">Back to Pending Appointments</a>
</div>

</body>
</html>
