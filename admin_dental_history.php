<?php
include 'login_check.php'; // Ensure user is logged in
include 'db_config.php';

// Check if 'patient_id' is present in the URL
if (!isset($_GET['patient_id']) || empty($_GET['patient_id'])) {
    echo "Patient ID is missing.";
    exit;
}

$patient_id = intval($_GET['patient_id']); // Sanitize the input

// Fetch patient details from the 'patients' table in the database
$stmt = $conn->prepare("SELECT * FROM patients WHERE Patient_ID = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
$patient = $result->fetch_assoc();
$stmt->close();

if (!$patient) {
    echo "Patient not found.";
    exit;
}

// Fetch dental history for the patient
$dental_history_stmt = $conn->prepare("SELECT * FROM dental_history WHERE Patient_ID = ?");
$dental_history_stmt->bind_param("i", $patient_id);
$dental_history_stmt->execute();
$dental_history_result = $dental_history_stmt->get_result();

// Fetch completed appointments for the patient
$appointment_stmt = $conn->prepare("SELECT * FROM appointments WHERE Patient_ID = ? AND appointment_status = 'Completed'");
$appointment_stmt->bind_param("i", $patient_id);
$appointment_stmt->execute();
$appointment_result = $appointment_stmt->get_result();

// Close the connection when done
$conn->close();

// Default values to avoid null issues
$first_name = htmlspecialchars($patient['First_Name'] ?? '');
$last_name = htmlspecialchars($patient['Last_Name'] ?? '');
$date_of_birth = htmlspecialchars($patient['Date_of_Birth'] ?? 'N/A');
$contact_information = htmlspecialchars($patient['Contact_Information'] ?? 'N/A');
$email = htmlspecialchars($patient['Email'] ?? 'N/A');
$gender = htmlspecialchars($patient['Gender'] ?? 'N/A');
$date_of_last_visit = htmlspecialchars($patient['Date_of_Last_Visit'] ?? 'N/A');
$emergency_contact_name = htmlspecialchars($patient['Emergency_Contact_Name'] ?? 'N/A');
$relationship_to_patient = htmlspecialchars($patient['Relationship_to_Patient'] ?? 'N/A');
$emergency_contact_phone = htmlspecialchars($patient['Emergency_Contact_Phone'] ?? 'N/A');
$profile_image = htmlspecialchars($patient['Profile_Image'] ?? 'uploads/default.png'); // Use default image if profile image is missing
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dental History - Dental Clinic Management System</title>
    <link rel="stylesheet" type="text/css" href="css/style.css">
</head>
<style>
    /* General Styles */
    .main-content { flex: 1; padding: 20px; }
    .main-content h1, .main-content h2 { color: #2c3e50; margin-bottom: 20px; }
    .patient-info { background-color: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 8px; margin-bottom: 30px; }
    .patient-info img { border-radius: 8px; margin-top: 10px; }
    .main-content .btn { display: inline-block; padding: 10px 20px; background-color: #3498db; color: #fff; text-decoration: none; font-weight: bold; border-radius: 4px; transition: background-color 0.3s ease, transform 0.2s ease; }
    .main-content .btn:hover { background-color: #2874a6; transform: scale(1.05); }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    table th, table td { padding: 10px 15px; border: 1px solid #ddd; text-align: center; }
    table th { background-color: #3498db; color: #fff; }
    table tr:nth-child(even) { background-color: #f9f9f9; }
    table tr:hover { background-color: #f1f1f1; }
    table td a { color: #3498db; text-decoration: none; font-weight: bold; }
    table td a:hover { text-decoration: underline; }
    /* Modal Styles */
    .modal { display: none; position: fixed; z-index: 1; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0, 0, 0, 0.4); padding-top: 60px; }
    .modal-content { background-color: #fff; margin-left:25%; padding: 20px; border: 1px solid #888; width: 80%; max-width: 900px; border-radius: 8px; }
    .modal-header { padding: 10px 0; border-bottom: 1px solid #ddd; font-size: 20px; }
    .modal-body { padding: 20px 0; }
    .modal-footer { padding: 10px 0; text-align: right; }
    .close { color: #aaa; font-size: 28px; font-weight: bold; position: absolute; top: 10px; right: 25px; }
    .close:hover, .close:focus { color: black; text-decoration: none; cursor: pointer; }
</style>
<body>
<div class="container">
    <!-- Sidebar Menu -->
    <aside class="sidebar">
        <h2 class="logo">
            <img src="images/lads.png" alt="Dental Clinic Logo">
            <h1>LAD DCAMS</h1>
        </h2>
        <ul>
            <li><a href="admin_dashboard.php">Dashboard</a></li>
            <li><a href="patients.php">Patients</a></li>
            <li><a href="dentists.php">Dentists</a></li>
            <li><a href="appointments.php">Appointments</a></li>
            <li><a href="admin_add_services.php">Add Services</a></li>
            <li><a href="reports.php">Reports</a></li>
            <li><a href="admin_settings.php">Settings</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <section id="patient-detail">
            <h1>Patient Details</h1>
            <div class="patient-info">
                <p><strong>First Name:</strong> <?php echo $first_name; ?></p>
                <p><strong>Last Name:</strong> <?php echo $last_name; ?></p>
                <p><strong>Date of Birth:</strong> <?php echo $date_of_birth; ?></p>
                <p><strong>Contact Information:</strong> <?php echo $contact_information; ?></p>
                <p><strong>Email:</strong> <?php echo $email; ?></p>
                <p><strong>Gender:</strong> <?php echo $gender; ?></p>
                <p><strong>Emergency Contact Name:</strong> <?php echo $emergency_contact_name; ?></p>
                <p><strong>Relationship to Patient:</strong> <?php echo $relationship_to_patient; ?></p>
                <p><strong>Emergency Contact Phone:</strong> <?php echo $emergency_contact_phone; ?></p>
                <p><a href="admin_add_appointments.php?patient_id=<?php echo $patient_id; ?>" class="btn">Add Appointment</a></p>
                <!-- View Appointment History Button -->
               
            </div>
        </section>

        <p><a href="patient_detail.php?patient_id=<?php echo $patient_id; ?>" class="btn">Medical History</a>
        <a href="admin_dental_history.php?patient_id=<?php echo $patient_id; ?>" class="btn">View Dental History</a>
        <a href="admin_payment_details.php?patient_id=<?php echo $patient_id; ?>" class="btn">View Payment Details</a>
        <a id="viewAppointmentHistoryBtn" class="btn">View Appointment History</a></p>
        
        <section id="patient-dental-history">
            <h1>Dental History for <?php echo htmlspecialchars($patient['First_Name']) . ' ' . htmlspecialchars($patient['Last_Name']); ?></h1>
           
            <?php if ($dental_history_result->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Record Created At</th>
                            <th>Previous Procedures</th>
                            <th>Last Dental Visit</th>
                            <th>Reason for Visit</th>
                            <th>Complications</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($dental_history = $dental_history_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $dental_history['created_at'] ? date('F j, Y, g:i A', strtotime($dental_history['created_at'])) : 'N/A'; ?></td>
                                <td><?php echo htmlspecialchars($dental_history['Previous_Procedures'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($dental_history['Last_Dental_Visit'] ? date('F j, Y', strtotime($dental_history['Last_Dental_Visit'])) : 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($dental_history['Reason_for_Visit'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($dental_history['Complications'] ?? 'N/A'); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No dental history found for this patient.</p>
            <?php endif; ?>
        </section>

        <!-- Modal for Appointment History -->
    <!-- Modal for Appointment History -->
<div id="appointmentHistoryModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <span class="close">&times;</span>
            <h2>Completed Appointments</h2>
        </div>
        <div class="modal-body">
            <?php if ($appointment_result->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Appointment Date</th>
                            <th>Appointment Time</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Download</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($appointment = $appointment_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars(date('F j, Y', strtotime($appointment['appointment_date']))); ?></td>
                                <td><?php echo htmlspecialchars(date('g:i A', strtotime($appointment['appointment_time']))); ?></td>
                                <td><?php echo htmlspecialchars($appointment['reason']); ?></td>
                                <td><?php echo htmlspecialchars($appointment['appointment_status']); ?></td>
                                <td>
    <a href="generate_pdf.php?appointment_id=<?php echo $appointment['appointment_id']; ?>&patient_id=<?php echo $patient_id; ?>" class="btn" target="_blank">
        &#x21d3; Print
    </a>
</td>

                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No completed appointments found for this patient.</p>
            <?php endif; ?>
        </div>
        <div class="modal-footer">
            <button class="btn" id="closeModalBtn">Close</button>
        </div>
    </div>
</div>


<script>
    // Modal functionality
   // Modal functionality
var modal = document.getElementById("appointmentHistoryModal");
var btn = document.getElementById("viewAppointmentHistoryBtn");
var closeBtn = document.getElementsByClassName("close")[0];
var closeModalBtn = document.getElementById("closeModalBtn");

// Open the modal
btn.onclick = function() {
    modal.style.display = "block";
}

// Close the modal
closeBtn.onclick = function() {
    modal.style.display = "none";
}

closeModalBtn.onclick = function() {
    modal.style.display = "none";
}

// Close modal if clicked outside of it
window.onclick = function(event) {
    if (event.target == modal) {
        modal.style.display = "none";
    }
}

// Function to handle printing of appointment details
function printAppointment(appointment_id) {
    // You could generate a printable version of the appointment or the entire page.
    // Here, we just print the current page.
    window.print();
}


</script>
</body>
</html>
