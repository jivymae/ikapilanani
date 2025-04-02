<?php
include 'db_config.php';

// Get the filters from the URL
$search = isset($_GET['search']) ? $_GET['search'] : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$dateFilter = isset($_GET['date']) ? $_GET['date'] : '';

// Query to fetch the filtered appointments
$sql = "SELECT appointments.appointment_id, appointments.appointment_date, appointments.appointment_time, 
                appointments.appointment_status, patients.First_Name AS patient_first_name, 
                patients.Last_Name AS patient_last_name, appointments.is_emergency, payments.total_amount 
        FROM appointments 
        JOIN patients ON appointments.patient_id = patients.Patient_ID
        LEFT JOIN payments ON appointments.appointment_id = payments.appointment_id";

// Apply filters to the query
$conditions = [];
if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $conditions[] = "(appointments.appointment_id LIKE '%$search%' 
                    OR CONCAT(patients.First_Name, ' ', patients.Last_Name) LIKE '%$search%')";
}
if (!empty($statusFilter)) {
    $statusFilter = $conn->real_escape_string($statusFilter);
    $conditions[] = "appointments.appointment_status = '$statusFilter'";
}
if (!empty($dateFilter)) {
    $dateFilter = $conn->real_escape_string($dateFilter);
    $conditions[] = "appointments.appointment_date = '$dateFilter'";
}

// If there are any conditions, append them to the SQL query
if (count($conditions) > 0) {
    $sql .= " WHERE " . implode(' AND ', $conditions);
}

$result = $conn->query($sql);

// Prepare CSV file output
header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename=appointments_report.csv');

// Open output stream
$output = fopen('php://output', 'w');

// Write clinic information merged across 7 columns
fputcsv($output, ['LAD Dental Clinic', '', '', '', '', '', '']);
fputcsv($output, ['Vamenta Blvd. Carmen, Cagayan de Oro City', '', '', '', '', '', '']);
fputcsv($output, ['Date: ' . date('F j, Y'), '', '', '', '', '', '']);
fputcsv($output, []); // Blank line for spacing

// Write the header row to CSV
fputcsv($output, [
    'Appointment Date', 'Appointment Time', 'Patient Name', 'Emergency', 
    'Appointment Status', 'Services', 'Total Amount'
]);

// Fetch and write the data to CSV
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Fetch services for the appointment
        $servicesSql = "SELECT services.service_name 
                        FROM appointment_services 
                        JOIN services ON appointment_services.service_id = services.service_id 
                        WHERE appointment_services.appointment_id = " . $row['appointment_id'];
        $servicesResult = $conn->query($servicesSql);
        $services = [];
        while ($service = $servicesResult->fetch_assoc()) {
            $services[] = $service['service_name'];
        }

        // Write the row to CSV
        fputcsv($output, [
            $row['appointment_date'], 
            $row['appointment_time'], 
            $row['patient_first_name'] . ' ' . $row['patient_last_name'], 
            $row['is_emergency'] ? 'Yes' : 'No', 
            $row['appointment_status'], 
            implode(', ', $services), 
            $row['total_amount']
        ]);
    }
}

fclose($output);
$conn->close();
exit();
?>
