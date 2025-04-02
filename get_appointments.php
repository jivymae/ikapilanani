<?php
include 'db_config.php';

$start_date = $_GET['start_date'];
$end_date = $_GET['end_date'];

$sql = "
    SELECT a.appointment_id, a.appointment_date, a.appointment_time, p.first_name, p.last_name
    FROM appointments a
    JOIN patients p ON a.patient_id = p.patient_id
    WHERE a.appointment_date BETWEEN '$start_date' AND '$end_date' 
    AND a.appointment_status = 'pending'
";
$result = $conn->query($sql);

$appointments_for_month = [];
while ($row = $result->fetch_assoc()) {
    $date = $row['appointment_date'];
    $appointments_for_month[$date][] = [
        'appointment_id' => $row['appointment_id'],
        'appointment_time' => $row['appointment_time'],
        'patient_name' => $row['first_name'] . ' ' . $row['last_name']
    ];
}

echo json_encode($appointments_for_month);
$conn->close();
?>
