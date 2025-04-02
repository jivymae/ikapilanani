<?php
require_once __DIR__ . '/vendor/autoload.php';  // Include mPDF
include 'db_config.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);
ini_set('memory_limit', '256M'); 
set_time_limit(60);

// Headers (Ensures browser recognizes PDF)
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="appointments_report.pdf"');
header('Cache-Control: private, must-revalidate, post-check=0, pre-check=0, max-age=0');
header('Pragma: public');
header('Expires: 0');

// Get filters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$dateFilter = isset($_GET['date']) ? $_GET['date'] : '';

// Capture the date range filter (from_date and to_date)
$fromDate = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$toDate = isset($_GET['to_date']) ? $_GET['to_date'] : '';

// Query
$sql = "SELECT appointments.appointment_id, appointments.appointment_date, appointments.appointment_time, 
                appointments.appointment_status, patients.First_Name AS patient_first_name, 
                patients.Last_Name AS patient_last_name, appointments.is_emergency, payments.total_amount 
        FROM appointments 
        JOIN patients ON appointments.patient_id = patients.Patient_ID
        LEFT JOIN payments ON appointments.appointment_id = payments.appointment_id";

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

// Add date range condition if both from_date and to_date are provided
if (!empty($fromDate) && !empty($toDate)) {
    $fromDate = $conn->real_escape_string($fromDate);
    $toDate = $conn->real_escape_string($toDate);
    $conditions[] = "appointments.appointment_date BETWEEN '$fromDate' AND '$toDate'";
}

// If there are any conditions, add them to the SQL query
if (count($conditions) > 0) {
    $sql .= " WHERE " . implode(' AND ', $conditions);
}

$result = $conn->query($sql);

// Start HTML
$html = '
    <h2>LAD Dental Clinic</h2>
    <p>Vamenta Blvd. Carmen, Cagayan de Oro City<br>
    Misamis Oriental, 9000, Philippines</p>
    <hr>
    <h3>Appointments Report</h3>
    <p>Date: ' . date("F j, Y") . '</p>
    <p>';
    
// Display the selected date range
if (!empty($fromDate) && !empty($toDate)) {
    $html .= 'Date Range: ' . htmlspecialchars($fromDate) . ' to ' . htmlspecialchars($toDate) . '<br>';
} elseif (!empty($fromDate)) {
    $html .= 'Date From: ' . htmlspecialchars($fromDate) . '<br>';
} elseif (!empty($toDate)) {
    $html .= 'Date To: ' . htmlspecialchars($toDate) . '<br>';
}

$html .= '</p>
    <table border="1" cellpadding="5" cellspacing="0" style="width:100%; margin-top:20px;">
        <thead>
            <tr>
                <th>Appointment Date</th>
                <th>Appointment Time</th>
                <th>Patient Name</th>
                <th>Emergency</th>
                <th>Status</th>
                <th>Services</th>
                <th>Total Amount</th>
            </tr>
        </thead>
        <tbody>';

// Process results
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $servicesSql = "SELECT services.service_name 
                        FROM appointment_services 
                        JOIN services ON appointment_services.service_id = services.service_id 
                        WHERE appointment_services.appointment_id = " . (int)$row['appointment_id'];
        $servicesResult = $conn->query($servicesSql);
        $services = [];
        while ($service = $servicesResult->fetch_assoc()) {
            $services[] = $service['service_name'];
        }

        // Row data
        $html .= '<tr>
            <td>' . htmlspecialchars($row['appointment_date']) . '</td>
            <td>' . htmlspecialchars($row['appointment_time']) . '</td>
            <td>' . htmlspecialchars($row['patient_first_name']) . ' ' . htmlspecialchars($row['patient_last_name']) . '</td>
            <td>' . ($row['is_emergency'] ? 'Yes' : 'No') . '</td>
            <td>' . htmlspecialchars($row['appointment_status']) . '</td>
            <td>' . implode(', ', $services) . '</td>
            <td>' . htmlspecialchars($row['total_amount']) . '</td>
        </tr>';
    }
} else {
    $html .= '<tr><td colspan="7">No appointments found.</td></tr>';
}

$html .= '</tbody></table>';

// Generate PDF
$mpdf = new \Mpdf\Mpdf();
$mpdf->WriteHTML($html);

// Debug: Save file first
$pdfFilePath = __DIR__ . '/appointments_report.pdf';
$mpdf->Output($pdfFilePath, 'F'); 

// Check if file exists
if (file_exists($pdfFilePath)) {
    readfile($pdfFilePath); // Send file to browser
} else {
    die("Error: PDF generation failed.");
}

$conn->close();
exit();
?>
