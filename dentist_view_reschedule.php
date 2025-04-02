<?php
session_start();
include 'db_config.php'; // Ensure this file initializes $conn

// Redirect non-dentists to login page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'dentist') {
    header('Location: login.php');
    exit();
}

// Get the request ID from the URL
$request_id = isset($_GET['request_id']) ? intval($_GET['request_id']) : 0;

// Fetch reschedule request details along with the original appointment details
$request_details = null;
$stmt = $conn->prepare("
    SELECT r.request_id, r.patient_id, r.new_date, r.new_time, r.status, 
           u.first_name, u.last_name, a.appointment_date, a.appointment_time, a.appointment_status 
    FROM appointment_reschedule_requests r
    JOIN users u ON r.patient_id = u.user_id
    JOIN appointments a ON r.appointment_id = a.appointment_id
    WHERE r.request_id = ?
");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$stmt->bind_result($request_id, $patient_id, $new_date, $new_time, $status, $first_name, $last_name, $appointment_date, $appointment_time, $appointment_status);
if ($stmt->fetch()) {
    $request_details = [
        'request_id' => $request_id,
        'patient_id' => $patient_id,
        'new_date' => $new_date,
        'new_time' => $new_time,
        'status' => $status,
        'patient_name' => htmlspecialchars($first_name . ' ' . $last_name),
        'original_appointment_date' => $appointment_date,
        'original_appointment_time' => $appointment_time,
        'original_status' => $appointment_status,
    ];
}
$stmt->close();
$conn->close();

// Redirect if request details are not found
if (!$request_details) {
    header('Location: dentist_message.php'); // or some other page
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reschedule Request Details</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }
        .request-details, .appointment-details {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 20px;
        }
        .request-details h2, .appointment-details h2 {
            margin-top: 0;
        }
        .actions {
            margin-top: 20px;
        }
    </style>
</head>
<body>

<div class="request-details">
    <h2>Reschedule Request Details</h2>
    <p><strong>Patient Name:</strong> <?php echo $request_details['patient_name']; ?></p>
    <p><strong>New Appointment Date:</strong> <?php echo $request_details['new_date']; ?></p>
    <p><strong>New Appointment Time:</strong> <?php echo $request_details['new_time']; ?></p>
    <p><strong>Status:</strong> <?php echo $request_details['status']; ?></p>
</div>

<div class="appointment-details">
    <h2>Original Appointment Details</h2>
    <p><strong>Original Appointment Date:</strong> <?php echo $request_details['original_appointment_date']; ?></p>
    <p><strong>Original Appointment Time:</strong> <?php echo $request_details['original_appointment_time']; ?></p>
    <p><strong>Original Appointment Status:</strong> <?php echo $request_details['original_status']; ?></p>
</div>

<a href="dentist_message.php">Back</a>

</body>
</html>
