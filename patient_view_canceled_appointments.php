<?php
include 'login_check.php'; // Ensure user is logged in
include 'db_config.php';

// Fetch patient ID
$user_id = $_SESSION['user_id'];

// Get the appointment ID from the GET request
if (isset($_GET['appointment_id'])) {
    $appointment_id = $_GET['appointment_id'];
} else {
    // Redirect if no appointment_id is provided
    header("Location: patient_cancelled_app.php");
    exit();
}

// Fetch details of the specific canceled appointment
$appointment_details = [];
$stmt = $conn->prepare("
    SELECT 
        a.appointment_id, 
        a.dentist_id, 
        a.appointment_date, 
        a.appointment_time, 
        a.appointment_status, 
        a.appointment_created_at,
        a.patient_id,
        u.first_name AS dentist_first_name, 
        u.last_name AS dentist_last_name,
        ca.canceled_date, 
        ca.cancel_reason
    FROM appointments a
    JOIN cancelled_appointments ca ON a.appointment_id = ca.appointment_id
    LEFT JOIN users u ON a.dentist_id = u.user_id
    WHERE a.patient_id = ? AND a.appointment_id = ?
");
$stmt->bind_param("ii", $user_id, $appointment_id);
$stmt->execute();
$stmt->bind_result($appointment_id, $dentist_id, $appointment_date, $appointment_time, $appointment_status, $appointment_created_at, $patient_id, $dentist_first_name, $dentist_last_name, $canceled_date, $cancel_reason);

if ($stmt->fetch()) {
    $appointment_details = [
        'appointment_id' => $appointment_id,
        'dentist_name' => $dentist_first_name . ' ' . $dentist_last_name,
        'appointment_date' => $appointment_date,
        'appointment_time' => $appointment_time,
        'status' => $appointment_status,
        'created_at' => $appointment_created_at,
        'canceled_date' => $canceled_date,
        'cancel_reason' => $cancel_reason
    ];
} else {
    // If no such canceled appointment exists for the patient, redirect
    header("Location: patient_cancelled_app.php");
    exit();
}

$stmt->close();

// Handle rebook request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rebook_appointment_id'])) {
    $rebook_appointment_id = $_POST['rebook_appointment_id'];

    // Step 1: Restore the appointment status to 'pending'
    $stmt_update = $conn->prepare("UPDATE appointments SET appointment_status = 'pending' WHERE appointment_id = ?");
    $stmt_update->bind_param('i', $rebook_appointment_id);
    
    if ($stmt_update->execute()) {
        // Step 2: Remove the appointment from the cancelled_appointments table
        $stmt_delete = $conn->prepare("DELETE FROM cancelled_appointments WHERE appointment_id = ?");
        $stmt_delete->bind_param('i', $rebook_appointment_id);

        if ($stmt_delete->execute()) {
            // Success: Appointment restored and canceled record removed
            header("Location: patient_view_canceled_appointments.php?appointment_id=$rebook_appointment_id&rebooked=1"); // Redirect with success message
            exit();
        }
    } else {
        $error_message = "Error: Could not rebook appointment.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Canceled Appointment</title>
</head>
<body>
    <h2>View Canceled Appointment</h2>

    <?php if (isset($_GET['rebooked'])): ?>
        <div class="success">Your appointment has been successfully restored and is now pending.</div>
    <?php endif; ?>

    <table>
        <tr>
            <th>Appointment ID</th>
            <td><?php echo htmlspecialchars($appointment_details['appointment_id']); ?></td>
        </tr>
        <tr>
            <th>Dentist</th>
            <td><?php echo htmlspecialchars($appointment_details['dentist_name']); ?></td>
        </tr>
        <tr>
            <th>Appointment Date</th>
            <td><?php echo htmlspecialchars($appointment_details['appointment_date']); ?></td>
        </tr>
        <tr>
            <th>Appointment Time</th>
            <td><?php echo htmlspecialchars($appointment_details['appointment_time']); ?></td>
        </tr>
        <tr>
            <th>Status</th>
            <td><?php echo htmlspecialchars($appointment_details['status']); ?></td>
        </tr>
        <tr>
            <th>Created At</th>
            <td><?php echo htmlspecialchars($appointment_details['created_at']); ?></td>
        </tr>
        <tr>
            <th>Cancelled Date</th>
            <td><?php echo htmlspecialchars($appointment_details['canceled_date']); ?></td>
        </tr>
        <tr>
            <th>Reason for Cancellation</th>
            <td><?php echo htmlspecialchars($appointment_details['cancel_reason'] ?? ''); ?></td>

        </tr>
    </table>

    <!-- Rebook Button -->
    <form method="POST">
        <input type="hidden" name="rebook_appointment_id" value="<?php echo htmlspecialchars($appointment_details['appointment_id']); ?>">
        <button type="submit">Rebook Appointment</button>
    </form>

</body>
</html>
