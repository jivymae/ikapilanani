<?php
include 'login_check.php'; // Ensure user is logged in
include 'db_config.php';
include 'admin_check.php';

// Check if request ID is provided
if (!isset($_GET['request_id'])) {
    die('Request ID not provided.');
}

$request_id = intval($_GET['request_id']);

// Fetch reschedule request details
$sql = "
    SELECT arr.*, 
           a.appointment_date, 
           a.appointment_time, 
           u.first_name AS patient_first_name, 
           u.last_name AS patient_last_name
    FROM appointment_reschedule_requests arr
    JOIN appointments a ON arr.appointment_id = a.appointment_id
    JOIN users u ON a.patient_id = u.user_id
    WHERE arr.request_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $request_id);
$stmt->execute();
$result = $stmt->get_result();
$request = $result->fetch_assoc();
$stmt->close();

// Handle confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_reschedule'])) {
    $new_date = $request['new_date'];
    $new_time = $request['new_time'];

    // Update the appointment
    $update_stmt = $conn->prepare("UPDATE appointments SET appointment_date = ?, appointment_time = ? WHERE appointment_id = ?");
    $update_stmt->bind_param("ssi", $new_date, $new_time, $request['appointment_id']);
    
    if ($update_stmt->execute()) {
        // Delete the reschedule request
        $delete_request_stmt = $conn->prepare("DELETE FROM appointment_reschedule_requests WHERE request_id = ?");
        $delete_request_stmt->bind_param("i", $request_id);
        $delete_request_stmt->execute();

        echo '<p>Reschedule confirmed and request deleted.</p>';
    } else {
        echo '<p>Failed to confirm reschedule.</p>';
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Reschedule Request</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            padding: 20px;
            background-color: #f4f4f4;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #333;
        }
        p {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <h1>Confirm Reschedule Request</h1>
    <p><strong>Current Appointment Date:</strong> <?php echo htmlspecialchars($request['appointment_date']); ?></p>
    <p><strong>Current Appointment Time:</strong> <?php echo htmlspecialchars($request['appointment_time']); ?></p>
    <p><strong>Patient:</strong> <?php echo htmlspecialchars($request['patient_first_name'] . ' ' . $request['patient_last_name']); ?></p>
    <p><strong>Requested New Date:</strong> <?php echo htmlspecialchars($request['new_date']); ?></p>
    <p><strong>Requested New Time:</strong> <?php echo htmlspecialchars($request['new_time']); ?></p>
    
    <form method="POST">
        <button type="submit" name="confirm_reschedule">Confirm Reschedule</button>
    </form>
    
    <a href="admin_dashboard.php">Back to Admin Dashboard</a>
</body>
</html>
