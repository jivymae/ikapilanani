<?php
session_start();
include 'db_config.php'; // Ensure this file initializes $conn

// Redirect non-dentists to login page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'dentist') {
    header('Location: login.php');
    exit();
}

// Get the patient ID from the URL
if (!isset($_GET['patient_id']) || !is_numeric($_GET['patient_id'])) {
    header('Location: dentist_patient_records.php'); // Redirect if no valid patient ID
    exit();
}

$patient_id = $_GET['patient_id'];

// Initialize variables
$patient_details = [];
$medical_history = [];
$dental_history = [];
$completed_appointments = [];

// Fetch patient details from the 'patients' table (excluding Patient_ID)
$stmt = $conn->prepare("SELECT Last_Name, First_Name, Date_of_Birth, Contact_Information, Email, 
                               Emergency_Contact_Name, Relationship_to_Patient, Emergency_Contact_Phone, Created_At, Gender 
                        FROM patients WHERE Patient_ID = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$stmt->bind_result($Last_Name, $First_Name, $Date_of_Birth, $Contact_Information, $Email, 
                   $Emergency_Contact_Name, $Relationship_to_Patient, $Emergency_Contact_Phone, $Created_At, $Gender);
$stmt->fetch();
$stmt->close();

// Fetch medical history from the 'medical_history' table
$stmt = $conn->prepare("
    SELECT Current_Medical_Conditions, Allergies, Medications, Previous_Surgeries, created_at 
    FROM medical_history 
    WHERE Patient_ID = ?
");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$stmt->bind_result($Current_Medical_Conditions, $Allergies, $Medications, $Previous_Surgeries, $history_created_at);

while ($stmt->fetch()) {
    $medical_history[] = [
        'current_medical_conditions' => $Current_Medical_Conditions,
        'allergies' => $Allergies,
        'medications' => $Medications,
        'previous_surgeries' => $Previous_Surgeries,
        'created_at' => $history_created_at,
    ];
}
$stmt->close();

// Fetch dental history from the 'dental_history' table
$stmt = $conn->prepare("
    SELECT Previous_Procedures, Last_Dental_Visit, Reason_for_Visit, Complications, created_at 
    FROM dental_history 
    WHERE Patient_ID = ?
");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$stmt->bind_result($Previous_Procedures, $Last_Dental_Visit, $Reason_for_Visit, $Complications, $dental_created_at);

while ($stmt->fetch()) {
    $dental_history[] = [
        'previous_procedures' => $Previous_Procedures,
        'last_dental_visit' => $Last_Dental_Visit,
        'reason_for_visit' => $Reason_for_Visit,
        'complications' => $Complications,
        'created_at' => $dental_created_at,
    ];
}
$stmt->close();

// Fetch completed appointments from the 'appointments' table
// Fetch treatment records from the 'treatment_records' table
$stmt = $conn->prepare("
    SELECT treatment_id, appointment_id, patient_id, diagnosis, image, treatment_performed, medication_prescribed, 
           upper_teeth_left, lower_teeth_left, teeth_part, follow_up_date, created_at, updated_at 
    FROM treatment_records 
    WHERE patient_id = ?
");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$stmt->bind_result($treatment_id, $appointment_id, $patient_id, $diagnosis, $image, $treatment_performed, 
                   $medication_prescribed, $upper_teeth_left, $lower_teeth_left, $teeth_part, $follow_up_date, 
                   $created_at, $updated_at);

$treatment_records = [];

while ($stmt->fetch()) {
    $treatment_records[] = [
        'treatment_id' => $treatment_id,
        'appointment_id' => $appointment_id,
        'diagnosis' => $diagnosis,
        'image' => $image,
        'treatment_performed' => $treatment_performed,
        'medication_prescribed' => $medication_prescribed,
        'upper_teeth_left' => $upper_teeth_left,
        'lower_teeth_left' => $lower_teeth_left,
        'teeth_part' => $teeth_part,
        'follow_up_date' => $follow_up_date,
        'created_at' => $created_at,
        'updated_at' => $updated_at,
    ];
}

$stmt = $conn->prepare("
    SELECT appointment_date, appointment_time, reason, is_emergency, appointment_created_at 
    FROM appointments 
    WHERE patient_id = ? AND appointment_status = 'completed'
");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$stmt->bind_result($appointment_date, $appointment_time, $reason, $is_emergency, $appointment_created_at);

while ($stmt->fetch()) {
    $completed_appointments[] = [
        'appointment_date' => $appointment_date,
        'appointment_time' => $appointment_time,
        'reason' => $reason,
        'is_emergency' => $is_emergency,
        'appointment_created_at' => $appointment_created_at,
    ];
}
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Patient Details - Dental Clinic Management System</title>
    <link rel="stylesheet" type="text/css" href="css/dentist.css">
    <style>
      /* Basic reset */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: Arial, sans-serif;
    background-color: #f4f4f4;
    color: #333;
    display: flex;
    height: 100vh;
}

/* Sidebar styling */
.sidebar {
    width: 250px; /* Sidebar width */
    background-color: #b19cd9;
    color: white;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding-top: 20px;
    position: fixed; /* Fix sidebar to the left */
    height: 100%; /* Make sidebar full height */
}

/* Sidebar logo styling */
.sidebar .logo {
    text-align: center;
    margin-bottom: 30px;
}

.sidebar .logo img {
    width: 80px;
    height: auto;
}

.sidebar .logo h1 {
    font-size: 24px;
    font-weight: bold;
    margin-top: 10px;
}

/* Navbar links in the sidebar */
.navbar {
    width: 100%;
    text-align: center;
    margin-top: 20px;
}

.navbar h2 {
    font-size: 18px;
    margin-bottom: 20px;
}

.navbar a {
    display: block;
    color: white;
    text-decoration: none;
    padding: 12px;
    margin: 5px 0;
    font-size: 16px;
    transition: background-color 0.3s ease;
}

.navbar a:hover,
.navbar a.active {
    background-color: #4c2882;
}

.navbar a.active {
    font-weight: bold;
}

        .container {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            max-width: 800px;
            margin: auto;
        }

        .container h1 {
            margin-bottom: 20px;
            font-size: 28px;
            color: #4c2882;
        }

        p {
            font-size: 16px;
            margin: 8px 0;
        }

        strong {
            color: #555;
        }

        a {
            text-decoration: none;
            color: #4c2882;
            font-size: 16px;
            margin-top: 20px;
            display: inline-block;
        }

        /* Modal Styles */
        .modal {
            display: none; /* Hidden by default */
            position: fixed;
            z-index: 1; /* Sit on top */
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
            padding-top: 60px;
        }

        .modal-content {
            background-color: #fff;
            margin: 5% auto;
            padding: 20px;
            border-radius: 8px;
            width: 80%;
            max-width: 800px;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        /* Table Styles */
        .medical-history table, .dental-history table, .appointments table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 12px;
            text-align: left;
            font-size: 14px;
        }

        th {
            background-color: #4c2882;
            color: white;
            border: 1px solid #ddd;
        }

        td {
            border: 1px solid #ddd;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        tr:hover {
            background-color: #f1f1f1;
        }

        .no-record {
            font-size: 18px;
            font-weight: bold;
            color: #e74c3c;
        }
        /* Treatment Records Table Styles */
.treatment-records table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

.treatment-records th, .treatment-records td {
    padding: 12px;
    text-align: left;
    font-size: 14px;
    border: 1px solid #ddd;
}

.treatment-records th {
    background-color: #4c2882;
    color: white;
}

.treatment-records td {
    background-color: #f9f9f9;
}

.treatment-records tr:nth-child(even) td {
    background-color: #f1f1f1;
}

.treatment-records tr:hover {
    background-color: #e8e8e8;
}

/* Optional: Make the table more responsive */
@media (max-width: 768px) {
    .treatment-records table, .treatment-records th, .treatment-records td {
        display: block;
        width: 100%;
    }
    
    .treatment-records th {
        background-color: #4c2882;
    }

    .treatment-records td {
        text-align: right;
        padding-left: 50%;
        position: relative;
    }

    .treatment-records td::before {
        content: attr(data-label);
        position: absolute;
        left: 10px;
        font-weight: bold;
        color: #4c2882;
    }
}

    </style>
</head>
<body>
<div class="sidebar">
        <nav class="navbar">
        <div class="logo">
            <img src="images/lads.png" alt="Dental Clinic Logo">
            <h1>LAD DCAMS</h1>
        </div>
        
        <nav>
        
        <a href="dentist_dashboard.php">Dashboard</a>
        <a href="dentist_profile.php">Profile</a>
        <a href="dentist_patient_appointments.php" >Appointments</a>
        <a href="dentist_patient_records.php">Patient Records</a>
        <a href="dentist_message.php">Messages</a>
        <a href="logout.php">Logout</a>

    </div>
    <div class="container">
        <h1>Patient Details</h1>
        <p><strong>First Name:</strong> <?php echo htmlspecialchars($First_Name ?: 'N/A'); ?></p>
        <p><strong>Last Name:</strong> <?php echo htmlspecialchars($Last_Name ?: 'N/A'); ?></p>
        <p><strong>Date of Birth:</strong> <?php echo htmlspecialchars($Date_of_Birth ?: 'N/A'); ?></p>
        <p><strong>Contact Information:</strong> <?php echo htmlspecialchars($Contact_Information ?: 'N/A'); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($Email ?: 'N/A'); ?></p>
        <p><strong>Emergency Contact Name:</strong> <?php echo htmlspecialchars($Emergency_Contact_Name ?: 'N/A'); ?></p>
        <p><strong>Relationship to Patient:</strong> <?php echo htmlspecialchars($Relationship_to_Patient ?: 'N/A'); ?></p>
        <p><strong>Emergency Contact Phone:</strong> <?php echo htmlspecialchars($Emergency_Contact_Phone ?: 'N/A'); ?></p>
        <p><strong>Created At:</strong> <?php echo htmlspecialchars($Created_At ?: 'N/A'); ?></p>
        <p><strong>Gender:</strong> <?php echo htmlspecialchars($Gender ?: 'N/A'); ?></p>

        <a href="dentist_patient_records.php">Back to Patient Records</a>
<div>

    
        <!-- Buttons to Open Modals -->
        <button id="viewMedicalHistoryBtn">View Medical History</button>
        <button id="viewDentalHistoryBtn">View Dental History</button>
        <button id="viewCompletedAppointmentsBtn">View Completed Appointments</button>
    </div>

    
    <!-- Medical History Modal -->
    <div id="medicalHistoryModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Medical History</h2>
            <?php if (empty($medical_history)): ?>
                <p class="no-record">No medical history found for this patient.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Current Medical Conditions</th>
                            <th>Allergies</th>
                            <th>Medications</th>
                            <th>Previous Surgeries</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($medical_history as $history): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($history['current_medical_conditions'] ?: 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($history['allergies'] ?: 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($history['medications'] ?: 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($history['previous_surgeries'] ?: 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($history['created_at'] ?: 'N/A'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Dental History Modal -->
    <div id="dentalHistoryModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Dental History</h2>
            <?php if (empty($dental_history)): ?>
                <p class="no-record">No dental history found for this patient.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Previous Procedures</th>
                            <th>Last Dental Visit</th>
                            <th>Reason for Visit</th>
                            <th>Complications</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dental_history as $history): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($history['previous_procedures'] ?: 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($history['last_dental_visit'] ?: 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($history['reason_for_visit'] ?: 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($history['complications'] ?: 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($history['created_at'] ?: 'N/A'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
<div>
    
    <!-- Completed Appointments Modal -->
    <div id="completedAppointmentsModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Completed Appointments</h2>
            <?php if (empty($completed_appointments)): ?>
                <p class="no-record">No completed appointments found for this patient.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Appointment Date</th>
                            <th>Appointment Time</th>
                            <th>Reason</th>
                            <th>Emergency</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($completed_appointments as $appointment): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($appointment['appointment_date'] ?: 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($appointment['appointment_time'] ?: 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($appointment['reason'] ?: 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($appointment['is_emergency'] ? 'Yes' : 'No'); ?></td>
                                <td><?php echo htmlspecialchars($appointment['appointment_created_at'] ?: 'N/A'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
    </div>

    <script>
        // Get the modals
        var medicalModal = document.getElementById("medicalHistoryModal");
        var dentalModal = document.getElementById("dentalHistoryModal");
        var appointmentsModal = document.getElementById("completedAppointmentsModal");

        // Get the buttons that open the modals
        var medicalBtn = document.getElementById("viewMedicalHistoryBtn");
        var dentalBtn = document.getElementById("viewDentalHistoryBtn");
        var appointmentsBtn = document.getElementById("viewCompletedAppointmentsBtn");

        // Get the <span> elements that close the modals
        var closeBtns = document.getElementsByClassName("close");

        // Open modals
        medicalBtn.onclick = function() {
            medicalModal.style.display = "block";
        }
        dentalBtn.onclick = function() {
            dentalModal.style.display = "block";
        }
        appointmentsBtn.onclick = function() {
            appointmentsModal.style.display = "block";
        }

        // Close modals
        for (var i = 0; i < closeBtns.length; i++) {
            closeBtns[i].onclick = function() {
                medicalModal.style.display = "none";
                dentalModal.style.display = "none";
                appointmentsModal.style.display = "none";
            }
        }

        // Close modals if clicked outside
        window.onclick = function(event) {
            if (event.target == medicalModal || event.target == dentalModal || event.target == appointmentsModal) {
                medicalModal.style.display = "none";
                dentalModal.style.display = "none";
                appointmentsModal.style.display = "none";
            }
        }
    </script>
   
<!-- Treatment Records Table -->
<div class="treatment_records">
    <h2>Treatment Records</h2>
    <?php if (empty($treatment_records)): ?>
        <p class="no-record">No treatment records found for this patient.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Treatment ID</th>
                    <th>Appointment ID</th>
                    <th>Diagnosis</th>
                    <th>Treatment Performed</th>
                    <th>Medication Prescribed</th>
                    <th>Follow Up Date</th>
                    <th>Created At</th>
                    <th>Updated At</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($treatment_records as $record): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($record['treatment_id'] ?: 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($record['appointment_id'] ?: 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($record['diagnosis'] ?: 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($record['treatment_performed'] ?: 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($record['medication_prescribed'] ?: 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($record['follow_up_date'] ?: 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($record['created_at'] ?: 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($record['updated_at'] ?: 'N/A'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

</body>
</html>
