<?php
session_start();
include 'db_config.php'; // Ensure this file initializes $conn

// Redirect non-dentists to login page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'dentist') {
    header('Location: login.php');
    exit();
}

$dentist_id = $_SESSION['user_id'];

// Fetch pending reschedule requests
$reschedule_requests = [];
$reschedule_stmt = $conn->prepare("
    SELECT r.request_id, r.patient_id, r.new_date, r.new_time, r.status 
    FROM appointment_reschedule_requests r
    JOIN appointments a ON r.appointment_id = a.appointment_id
    WHERE a.dentist_id = ? AND r.status = 'pending'
");
$reschedule_stmt->bind_param("i", $dentist_id);
$reschedule_stmt->execute();
$reschedule_stmt->bind_result($request_id, $patient_id, $new_date, $new_time, $status);

while ($reschedule_stmt->fetch()) {
    $reschedule_requests[] = [
        'request_id' => $request_id,
        'patient_id' => $patient_id,
        'new_date' => $new_date,
        'new_time' => $new_time,
        'status' => $status,
    ];
}
$reschedule_stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pending Reschedule Requests</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; }
        .container { padding: 20px; }
        h2 { margin-bottom: 10px; }
        ul { list-style-type: none; padding: 0; }
        li { margin-bottom: 10px; }
    </style>
</head>
<body>
<div class="container">
    <h2>Pending Reschedule Requests</h2>
    <ul>
        <?php foreach ($reschedule_requests as $request): ?>
            <li>
                Patient ID: <?php echo htmlspecialchars($request['patient_id']); ?>,
                New Date: <?php echo htmlspecialchars($request['new_date']); ?>,
                New Time: <?php echo htmlspecialchars($request['new_time']); ?>
                <a href="dentist_view_reschedule.php?request_id=<?php echo $request['request_id']; ?>">View</a>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
</body>
</html>
