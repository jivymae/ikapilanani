<?php
include 'login_check.php';
include 'db_config.php';
include 'admin_check.php';

if (!isset($_GET['id'])) {
    header('Location: appointments.php');
    exit();
}

$appointment_id = $_GET['id'];

// Cancel the appointment
$sql = "UPDATE appointments SET appointment_status = 'canceled' WHERE appointment_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $appointment_id);
$stmt->execute();
$stmt->close();

$conn->close();

header('Location: appointments.php?msg=Appointment canceled successfully.');
exit();
?>