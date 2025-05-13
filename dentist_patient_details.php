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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
      /* Main Content Styles - Scrollable Area */
      .main-content {
          
            padding: 30px;
            background-color: #f8f9fa;
            min-height: 100vh;
            overflow-y: auto;
        }

        /* Patient Card Styles */
        .patient-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
            padding: 30px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }

        .patient-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 8px;
            height: 100%;
            background: linear-gradient(to bottom, #8a63d2, #4c2882);
        }

        .patient-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .patient-header h1 {
            color: #4c2882;
            font-size: 28px;
            margin: 0;
            display: flex;
            align-items: center;
        }

        .patient-header h1 i {
            margin-right: 15px;
            color: #8a63d2;
        }

        .back-btn {
            background-color: #4c2882;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
        }

        .back-btn:hover {
            background-color: #3a1d66;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .back-btn i {
            margin-right: 8px;
        }

        /* Patient Info Grid */
        .patient-info {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .info-item {
            background: #f9f5ff;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #8a63d2;
            transition: transform 0.3s ease;
        }

        .info-item:hover {
            transform: translateY(-3px);
        }

        .info-item strong {
            color: #4c2882;
            display: block;
            margin-bottom: 5px;
            font-size: 14px;
        }

        .info-item p {
            color: #555;
            margin: 0;
            font-size: 16px;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 15px;
            margin: 30px 0;
            flex-wrap: wrap;
        }

        .action-btn {
            background: linear-gradient(135deg, #8a63d2, #4c2882);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 15px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
            background: linear-gradient(135deg, #7a53c2, #3a1d66);
        }

        .action-btn i {
            margin-right: 10px;
        }

        /* Section Headers */
        .section-header {
            color: #4c2882;
            font-size: 22px;
            margin: 30px 0 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
            display: flex;
            align-items: center;
        }

        .section-header i {
            margin-right: 15px;
        }

        /* Table Styles */
        .table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
            padding: 20px;
            overflow-x: auto;
            margin-bottom: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th {
            background-color: #4c2882;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 500;
            position: sticky;
            top: 0;
        }

        td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            color: #555;
        }

        tr:nth-child(even) {
            background-color: #fafafa;
        }

        tr:hover {
            background-color: #f5f0ff;
        }

        .no-record {
            text-align: center;
            padding: 30px;
            color: #e74c3c;
            font-size: 16px;
            background: #fff5f5;
            border-radius: 8px;
            margin: 20px 0;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            animation: fadeIn 0.3s;
            overflow-y: auto;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background-color: #fff;
            margin: 5% auto;
            padding: 30px;
            border-radius: 12px;
            width: 85%;
            max-width: 1000px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            position: relative;
            animation: slideDown 0.4s;
        }

        @keyframes slideDown {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .close {
            position: absolute;
            top: 20px;
            right: 25px;
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s;
        }

        .close:hover {
            color: #4c2882;
        }

        .modal-header {
            color: #4c2882;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
        }

        .modal-header i {
            margin-right: 15px;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .main-content {
                padding: 20px;
            }
            
            .patient-info {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .modal-content {
                width: 95%;
                padding: 20px;
            }
            
            .patient-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <!-- Your existing sidebar content -->
        <nav class="navbar">
            <div class="logo">
                <img src="images/lads.png" alt="Dental Clinic Logo">
                <h1>LAD DCAMS</h1>
            </div>
            <nav>
                <a href="dentist_dashboard.php">Dashboard</a>
                <a href="dentist_profile.php">Profile</a>
                <a href="dentist_patient_appointments.php">Appointments</a>
                <a href="dentist_patient_records.php">Patient Records</a>
                <a href="dentist_message.php">Messages</a>
                <a href="logout.php">Logout</a>
            </nav>
        </nav>
    </div>

    <div class="main-content">
        <div class="patient-card">
            <div class="patient-header">
                <h1><i class="fas fa-user-injured"></i> Patient Details</h1>
                <button class="back-btn" onclick="window.location.href='dentist_patient_records.php'">
                    <i class="fas fa-arrow-left"></i> Back to Records
                </button>
            </div>

            <div class="patient-info">
                <div class="info-item">
                    <strong>First Name</strong>
                    <p><?php echo htmlspecialchars($First_Name ?: 'N/A'); ?></p>
                </div>
                <div class="info-item">
                    <strong>Last Name</strong>
                    <p><?php echo htmlspecialchars($Last_Name ?: 'N/A'); ?></p>
                </div>
                <div class="info-item">
                    <strong>Date of Birth</strong>
                    <p><?php echo htmlspecialchars($Date_of_Birth ?: 'N/A'); ?></p>
                </div>
                <div class="info-item">
                    <strong>Gender</strong>
                    <p><?php echo htmlspecialchars($Gender ?: 'N/A'); ?></p>
                </div>
                <div class="info-item">
                    <strong>Contact Information</strong>
                    <p><?php echo htmlspecialchars($Contact_Information ?: 'N/A'); ?></p>
                </div>
                <div class="info-item">
                    <strong>Email</strong>
                    <p><?php echo htmlspecialchars($Email ?: 'N/A'); ?></p>
                </div>
                <div class="info-item">
                    <strong>Emergency Contact</strong>
                    <p><?php echo htmlspecialchars($Emergency_Contact_Name ?: 'N/A'); ?></p>
                </div>
                <div class="info-item">
                    <strong>Relationship</strong>
                    <p><?php echo htmlspecialchars($Relationship_to_Patient ?: 'N/A'); ?></p>
                </div>
                <div class="info-item">
                    <strong>Emergency Phone</strong>
                    <p><?php echo htmlspecialchars($Emergency_Contact_Phone ?: 'N/A'); ?></p>
                </div>
                <div class="info-item">
                    <strong>Member Since</strong>
                    <p><?php echo htmlspecialchars($Created_At ?: 'N/A'); ?></p>
                </div>
            </div>

            <div class="action-buttons">
                <button id="viewMedicalHistoryBtn" class="action-btn">
                    <i class="fas fa-heartbeat"></i> Medical History
                </button>
                <button id="viewDentalHistoryBtn" class="action-btn">
                    <i class="fas fa-tooth"></i> Dental History
                </button>
                <button id="viewCompletedAppointmentsBtn" class="action-btn">
                    <i class="fas fa-calendar-check"></i> Completed Appointments
                </button>
            </div>
        </div>

        <h2 class="section-header"><i class="fas fa-notes-medical"></i> Treatment Records</h2>
        <div class="table-container">
            <?php if (empty($treatment_records)): ?>
                <div class="no-record">
                    <i class="fas fa-exclamation-circle" style="font-size: 24px; margin-bottom: 10px;"></i>
                    <p>No treatment records found for this patient.</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Treatment ID</th>
                            <th>Appointment ID</th>
                            <th>Diagnosis</th>
                            <th>Treatment</th>
                            <th>Medication</th>
                            <th>Follow Up</th>
                            <th>Created</th>
                            <th>Updated</th>
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
    </div>

    <!-- Medical History Modal -->
    <div id="medicalHistoryModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <div class="modal-header">
                <i class="fas fa-heartbeat"></i>
                <h2>Medical History</h2>
            </div>
            <?php if (empty($medical_history)): ?>
                <div class="no-record">
                    <i class="fas fa-exclamation-circle" style="font-size: 24px; margin-bottom: 10px;"></i>
                    <p>No medical history found for this patient.</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Medical Conditions</th>
                                <th>Allergies</th>
                                <th>Medications</th>
                                <th>Previous Surgeries</th>
                                <th>Recorded</th>
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
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Dental History Modal -->
    <div id="dentalHistoryModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <div class="modal-header">
                <i class="fas fa-tooth"></i>
                <h2>Dental History</h2>
            </div>
            <?php if (empty($dental_history)): ?>
                <div class="no-record">
                    <i class="fas fa-exclamation-circle" style="font-size: 24px; margin-bottom: 10px;"></i>
                    <p>No dental history found for this patient.</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Previous Procedures</th>
                                <th>Last Dental Visit</th>
                                <th>Reason for Visit</th>
                                <th>Complications</th>
                                <th>Recorded</th>
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
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Completed Appointments Modal -->
    <div id="completedAppointmentsModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <div class="modal-header">
                <i class="fas fa-calendar-check"></i>
                <h2>Completed Appointments</h2>
            </div>
            <?php if (empty($completed_appointments)): ?>
                <div class="no-record">
                    <i class="fas fa-exclamation-circle" style="font-size: 24px; margin-bottom: 10px;"></i>
                    <p>No completed appointments found for this patient.</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Reason</th>
                                <th>Emergency</th>
                                <th>Scheduled</th>
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
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Modal functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Get modals and buttons
            const modals = {
                medical: document.getElementById("medicalHistoryModal"),
                dental: document.getElementById("dentalHistoryModal"),
                appointments: document.getElementById("completedAppointmentsModal")
            };
            
            const buttons = {
                medical: document.getElementById("viewMedicalHistoryBtn"),
                dental: document.getElementById("viewDentalHistoryBtn"),
                appointments: document.getElementById("viewCompletedAppointmentsBtn")
            };
            
            const closeBtns = document.querySelectorAll(".close");
            
            // Open modals
            Object.keys(buttons).forEach(key => {
                buttons[key].addEventListener('click', () => {
                    modals[key].style.display = "block";
                });
            });
            
            // Close modals
            closeBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    Object.values(modals).forEach(modal => {
                        modal.style.display = "none";
                    });
                });
            });
            
            // Close when clicking outside
            window.addEventListener('click', (event) => {
                Object.values(modals).forEach(modal => {
                    if (event.target == modal) {
                        modal.style.display = "none";
                    }
                });
            });
        });
    </script>
</body>
</html>