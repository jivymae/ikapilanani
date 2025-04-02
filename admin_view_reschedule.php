<?php
include 'login_check.php'; // Ensure user is logged in
include 'db_config.php';
include 'admin_check.php';

// Fetch all reschedule requests
$sql = "
    SELECT arr.request_id, 
           u.first_name AS patient_first_name, 
           u.last_name AS patient_last_name, 
           a.appointment_date, 
           a.appointment_time, 
           arr.new_date, 
           arr.new_time
    FROM appointment_reschedule_requests arr
    JOIN appointments a ON arr.appointment_id = a.appointment_id
    JOIN users u ON a.patient_id = u.user_id
    ORDER BY arr.request_id DESC"; // Order by request ID for the latest first

$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reschedule Requests</title>
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ccc;
        }
        th {
            background-color: #e2e2e2;
        }
        a {
            color: #007BFF;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <h1>All Reschedule Requests</h1>
    <?php if ($result->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Request ID</th>
                    <th>Patient Name</th>
                    <th>Current Appointment Date</th>
                    <th>Current Appointment Time</th>
                    <th>Requested New Date</th>
                    <th>Requested New Time</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($request = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($request['request_id']); ?></td>
                    <td><?php echo htmlspecialchars($request['patient_first_name'] . ' ' . $request['patient_last_name']); ?></td>
                    <td><?php echo htmlspecialchars($request['appointment_date']); ?></td>
                    <td><?php echo htmlspecialchars($request['appointment_time']); ?></td>
                    <td><?php echo htmlspecialchars($request['new_date']); ?></td>
                    <td><?php echo htmlspecialchars($request['new_time']); ?></td>
                    <td>
                        <a href="admin_confirm_reschedule.php?request_id=<?php echo htmlspecialchars($request['request_id']); ?>">Confirm</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No reschedule requests found.</p>
    <?php endif; ?>
    <a href="admin_dashboard.php">Back to Admin Dashboard</a>
</body>
</html>
