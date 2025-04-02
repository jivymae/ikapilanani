<?php
session_start();
include 'db_config.php';
require 'vendor/autoload.php'; // Include Composer's autoloader

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Redirect non-dentists to login page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'dentist') {
    header('Location: login.php');
    exit();
}

// Fetch dentist's full name and signature from the database
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT first_name, last_name, signature FROM users WHERE user_id = ? AND role = 'dentist'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $dentist_name = $row['first_name'] . ' ' . $row['last_name'];
        $dentist_signature = $row['signature']; // Assuming signature is stored as a path or base64 string
    } else {
        $dentist_name = 'Dentist Name';
        $dentist_signature = ''; // Default value if no signature found
    }

    $stmt->close();
} else {
    $dentist_name = 'Dentist Name';
    $dentist_signature = '';
}

$appointment_id = $_GET['appointment_id'] ?? null;
if (!$appointment_id || !is_numeric($appointment_id)) {
    header('Location: dentist_patient_appointments.php');
    exit();
}

// Check if prescription already exists
$stmt = $conn->prepare("SELECT pres_file FROM prescriptions WHERE appointment_id = ?");
$stmt->bind_param("i", $appointment_id);
$stmt->execute();
$stmt->bind_result($existing_prescription);
$stmt->fetch();
$stmt->close();

$prescription_exists = !empty($existing_prescription);

// Handle prescription submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_prescription']) && !$prescription_exists) {
    $prescription_notes = $_POST['prescription_notes'];

// Fetch patient's name
$stmt = $conn->prepare("
    SELECT users.first_name, users.last_name 
    FROM appointments 
    INNER JOIN users ON appointments.patient_id = users.user_id 
    WHERE appointments.appointment_id = ?");
$stmt->bind_param("i", $appointment_id);
$stmt->execute();
$stmt->bind_result($patient_first_name, $patient_last_name);
$stmt->fetch();
$stmt->close();

// Combine first and last name
$patient_name = htmlspecialchars($patient_first_name . ' ' . $patient_last_name);

// Generate PDF with mPDF
$mpdf = new \Mpdf\Mpdf();
$mpdf->SetTitle('Prescription');

// Create the HTML structure
$html = '<div style="width: 100%; text-align: center;">';
$html .= '<h1 style="margin: 0;">Dental Clinic Prescription</h1>';
$html .= '<p style="margin: 0;">Max Suniel St. Carmen, Cagayan de Oro City</p>';
$html .= '<hr style="border: 1px solid #000; margin: 20px 0;">';
$html .= '<h2 style="margin: 0;">Prescription</h2>';
$html .= '<p style="font-size: 14px;">Prescription ID: ' . time() . '</p>';
$html .= '<p style="font-size: 14px;">Patient Name: ' . $patient_name . '</p>';
$html .= '<div style="text-align: left; margin-top: 20px;">';
$html .= '<p>' . $prescription_notes . '</p>';
$html .= '</div>';
$html .= '<div style="text-align: right; margin-top: 50px;">';

if ($dentist_signature) {
    $html .= '<img src="' . htmlspecialchars($dentist_signature) . '" style="width:200px; margin-top: 20px;"/>';
}
$html .= '<p>Prescribed by: ' . htmlspecialchars($dentist_name) . '</p>';
$html .= '</div>';
$html .= '</div>';

// Write the HTML to the PDF
$mpdf->WriteHTML($html);

// Save the PDF to the server
$file_path = 'prescriptions/prescription_' . time() . '.pdf';
$mpdf->Output($file_path, \Mpdf\Output\Destination::FILE);


    // Prepare to insert prescription into the database
    if (isset($appointment_id)) {
        $stmt = $conn->prepare("INSERT INTO prescriptions (appointment_id, pres_file) VALUES (?, ?)");
        $stmt->bind_param("is", $appointment_id, $file_path);
        if ($stmt->execute()) {
            $_SESSION['prescription_file'] = $file_path; // Set the session variable for download
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    }

    // Redirect to the same page to clear POST data
    header('Location: ' . $_SERVER['PHP_SELF'] . '?appointment_id=' . $appointment_id);
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Prescription</title>
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            text-align: center;
        }
        .form-container {
            max-width: 600px;
            margin: auto;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            background-color: #f9f9f9;
            position: relative;
        }
        button {
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
        .signature-container {
            margin-top: 30px;
            text-align: right;
        }
        .signature {
            border-top: 1px solid #ccc;
            width: 200px;
            margin-top: 10px;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h1>Dental Clinic Appointment Management System</h1>
        <p>Max Suniel St. Carmen, Cagayan de Oro City</p>
        <h2>Create Prescription</h2>

        <?php if ($prescription_exists): ?>
            <p>You have already saved a prescription for this appointment.</p>
            <a href="<?php echo $existing_prescription; ?>" download>
                <button>Download Prescription</button>
            </a>
        <?php else: ?>
            <form method="POST">
                <div id="editor" style="height: 300px;"></div>
                <input type="hidden" name="prescription_notes" id="prescriptionNotes">
                <button type="submit" name="submit_prescription" onclick="return saveContent()">Save Prescription</button>
            </form>
        <?php endif; ?>

        <a href="dentist_patient_appointments.php">Back to Appointments</a>

        <div class="signature-container">
            <p>Signature:</p>
            <div class="signature"></div>
            <p>(<?php echo htmlspecialchars($dentist_name); ?>)</p>
        </div>
    </div>

    <script>
        // Initialize Quill editor
        const quill = new Quill('#editor', {
            theme: 'snow',
            modules: {
                toolbar: [
                    ['bold', 'italic', 'underline'],
                    [{ 'header': [1, 2, false] }],
                    ['clean']
                ]
            }
        });

        function saveContent() {
            const content = quill.root.innerHTML; 
            document.getElementById('prescriptionNotes').value = content; 
            return true; 
        }
    </script>
</body>
</html>
