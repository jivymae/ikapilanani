<?php
// get_appointments.php

include 'db_config.php'; // Database connection

// Get year and month from the query string
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');
$month = isset($_GET['month']) ? $_GET['month'] : date('m');

// Prepare the query to get appointments for the specified month
$query = "SELECT appointment_id, DATE_FORMAT(time, '%Y-%m-%d %H:%i:%s') as time FROM appointments WHERE YEAR(time) = ? AND MONTH(time) = ? ORDER BY time";
$stmt = $pdo->prepare($query);
$stmt->execute([$year, $month]);

// Fetch the results
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Return the data as JSON
echo json_encode($appointments);
?>
