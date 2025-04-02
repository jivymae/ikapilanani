<?php
require_once __DIR__ . '/vendor/autoload.php'; // Include mPDF
include 'db_config.php';

// Ensure appointment_id and patient_id are provided
if (!isset($_GET['appointment_id']) || empty($_GET['appointment_id']) || !isset($_GET['patient_id']) || empty($_GET['patient_id'])) {
    echo "Appointment ID or Patient ID is missing.";
    exit;
}

$appointment_id = intval($_GET['appointment_id']); // Sanitize the input
$patient_id = intval($_GET['patient_id']); // Sanitize the input

// Fetch patient details from the database
$patient_stmt = $conn->prepare("SELECT * FROM patients WHERE Patient_ID = ?");
$patient_stmt->bind_param("i", $patient_id);
$patient_stmt->execute();
$patient_result = $patient_stmt->get_result();
if ($patient_result->num_rows > 0) {
    $patient = $patient_result->fetch_assoc();
} else {
    die("Patient data not found.");
}

// Fetch the appointment details
$appointment_stmt = $conn->prepare("SELECT * FROM appointments WHERE appointment_id = ?");
$appointment_stmt->bind_param("i", $appointment_id);
$appointment_stmt->execute();
$appointment_result = $appointment_stmt->get_result();
if ($appointment_result->num_rows > 0) {
    $appointment = $appointment_result->fetch_assoc();
} else {
    die("Appointment data not found.");
}

date_default_timezone_set('Asia/Manila');

// Fetch the dentist details using dentist_id from appointments table
$dentist_stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$dentist_stmt->bind_param("i", $appointment['dentist_id']); // Ensure dentist_id is correctly used
$dentist_stmt->execute();
$dentist_result = $dentist_stmt->get_result();
if ($dentist_result->num_rows > 0) {
    $dentist = $dentist_result->fetch_assoc();
} else {
    die("Dentist data not found.");
}

// Fetch service details (from appointment_services and services table)
$services_stmt = $conn->prepare("
    SELECT s.service_name, s.price
    FROM appointment_services AS asv
    JOIN services AS s ON asv.service_id = s.service_id
    WHERE asv.appointment_id = ?
");
$services_stmt->bind_param("i", $appointment_id);
$services_stmt->execute();
$services_result = $services_stmt->get_result();
if ($services_result->num_rows == 0) {
    die("No services found for this appointment.");
}

// Generate a unique certificate ID using appointment ID and current timestamp
$certificate_id = "CERT-" . $appointment_id . "-" . date('YmdHis');

// Start output buffering
ob_start();

// Create the mPDF instance
$mpdf = new \Mpdf\Mpdf([
    'format' => 'A4',  // Set page format to A4
    'margin_top' => 20,
    'margin_bottom' => 20,
    'margin_left' => 20,
    'margin_right' => 20,
]);

// Write HTML content for the certificate
$html = "
    <div style='text-align: center;'>
        <h2 style='font-family: Arial, sans-serif;'>LAD DENTAL CLINIC</h2>
        <p style='font-family: Arial, sans-serif;'>Vamenta Blvd Carmen, Cagayan de Oro City</p>
        <p style='font-family: Arial, sans-serif;'>Misamis Oriental, 9000, Philippines</p>
        <h3 style='font-family: Arial, sans-serif;'>CERTIFICATE OF APPEARANCE</h3>
        <p style='font-family: Arial, sans-serif;'><strong>Certificate ID:</strong> {$certificate_id}</p>
        <p style='font-family: Arial, sans-serif;'><strong>___________________________________________________________________________________</strong></p>
        <p style='font-family: Arial, sans-serif;'>This is to certify that:</p>
        <p style='font-family: Arial, sans-serif;'><strong>Name:</strong> {$patient['First_Name']} {$patient['Last_Name']}</p>
        <p style='font-family: Arial, sans-serif;'><strong>Date of Birth:</strong> " . date('F j, Y', strtotime($patient['Date_of_Birth'])) . "</p>
        <p style='font-family: Arial, sans-serif;'>Appeared at LAD Dental Clinic on " . date('F j, Y', strtotime($appointment['appointment_date'])) . " for a consultation and/or dental treatment.</p>
        <p style='font-family: Arial, sans-serif;'>The examination/treatment was carried out by Dr. {$dentist['first_name']} {$dentist['last_name']}, Dentist.</p>
        <h4 style='font-family: Arial, sans-serif;'>Details of Service Provided:</h4>
    </div>";

$html .= "<table style='width: 100%; font-family: Arial, sans-serif; border-collapse: collapse;'>";
$html .= "<thead><tr><th style='border: 1px solid #ddd; padding: 8px;'>Service Name</th></thead>";
$html .= "<tbody>";

foreach ($services_result as $service) {
    // Displaying the service name and price
    $html .= "<tr><td style='border: 1px solid #ddd; padding: 8px;'>{$service['service_name']}</td></tr>";
}

$html .= "</tbody></table>";

$html .= "
    <p style='font-family: Arial, sans-serif;'><strong>Date of Issue:</strong> " . date('F j, Y H:i:s') . "</p>
    <p style='font-family: Arial, sans-serif;'><strong>Signature:</strong> _____________________</p>
    <p style='font-family: Arial, sans-serif;'><strong>Dentist:</strong> Dr. {$dentist['first_name']} {$dentist['last_name']}</p>
";

// Write to PDF
$mpdf->WriteHTML($html);

// Output the PDF in the browser (instead of downloading)
$mpdf->Output('certificate_of_appearance.pdf', 'I'); // 'I' will display the PDF in the browser

// End output buffering and flush it
ob_end_flush();
exit;
?>
