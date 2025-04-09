<?php
session_start();
include 'db_config.php';

// Redirect non-dentists to login page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'dentist') {
    header('Location: login.php');
    exit();
}

// Get the appointment_id and patient_id from the URL
$appointment_id = isset($_GET['appointment_id']) ? intval($_GET['appointment_id']) : 0;
$patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : 0;

// Fetch appointment details, including the reason
$stmt = $conn->prepare("
    SELECT a.appointment_id, a.appointment_date, a.appointment_time, a.appointment_status, 
           a.is_emergency, a.appointment_created_at, p.first_name, p.last_name, a.reason 
    FROM appointments a
    JOIN patients p ON a.patient_id = p.patient_id
    WHERE a.appointment_id = ? AND a.patient_id = ?
");
$stmt->bind_param("ii", $appointment_id, $patient_id);
$stmt->execute();
$stmt->bind_result($appointment_id, $appointment_date, $appointment_time, $appointment_status, 
                   $is_emergency, $appointment_created_at, $first_name, $last_name, $reason);
$stmt->fetch();
$stmt->close();

// Fetch dental history
$dental_history = [];
$stmt = $conn->prepare("
    SELECT Dental_History_ID, Previous_Procedures, Last_Dental_Visit, Reason_for_Visit, Complications, created_at
    FROM dental_history
    WHERE Patient_ID = ?
");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $dental_history[] = $row;
}
$stmt->close();

// Fetch medical history
$medical_history = [];
$stmt = $conn->prepare("
    SELECT MedicalHistory_ID, Current_Medical_Conditions, Allergies, Medications, Previous_Surgeries, created_at
    FROM medical_history
    WHERE Patient_ID = ?
");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $medical_history[] = $row;
}
$stmt->close();

// Treatment record insertion
// Insert new appointment record for follow-up treatment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and process form data
    $diagnosis = $_POST['diagnosis'];
    $treatment_performed = $_POST['treatment_performed'];
    $medication_prescribed = $_POST['medication_prescribed'];
    $upper_teeth_left = !empty($_POST['upper_teeth_left']) ? $_POST['upper_teeth_left'] : 'N/A';
    $lower_teeth_left = !empty($_POST['lower_teeth_left']) ? $_POST['lower_teeth_left'] : 'N/A';
    $teeth_part = $_POST['teeth_part'] ?? 'N/A'; // Default to N/A if no option is selected
    $follow_up_date = !empty($_POST['follow_up_date']) ? $_POST['follow_up_date'] : 'N/A';
    $appointment_time = $_POST['appointment_time'];  // Capture the appointment time
    $dentist_id = $_SESSION['user_id']; // Replace 'user_id' with the correct session variable

    // Handle file upload for image
    $image = 'N/A';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $image = 'uploads/' . basename($_FILES['image']['name']);
        move_uploaded_file($_FILES['image']['tmp_name'], $image);
    }

    // Insert the treatment record into the treatment_records table
    $stmt = $conn->prepare("
        INSERT INTO treatment_records (appointment_id, patient_id, diagnosis, image, treatment_performed, 
            medication_prescribed, upper_teeth_left, lower_teeth_left, teeth_part, follow_up_date, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    $stmt->bind_param("iissssssss", $appointment_id, $patient_id, $diagnosis, $image, $treatment_performed, 
                     $medication_prescribed, $upper_teeth_left, $lower_teeth_left, $teeth_part, $follow_up_date);
    $stmt->execute();
    $stmt->close();

    // Insert new follow-up appointment into the appointments table
    $appointment_date = $follow_up_date; // Set the follow-up date as the new appointment date
    $appointment_status = 'pending';  // Set the status to pending
    $reason = 'follow up';  // Set the reason as "follow up"
    $is_emergency = 0;  // Set is_emergency to 0 for non-emergency follow-up

    $stmt = $conn->prepare("
    INSERT INTO appointments (patient_id, dentist_id, appointment_date, appointment_time, appointment_status, 
        reason, is_emergency, appointment_created_at) 
    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
");
$stmt->bind_param("iisssss", $patient_id, $dentist_id, $appointment_date, $appointment_time, $appointment_status, 
                 $reason, $is_emergency);
$stmt->execute();
$stmt->close();



    // Redirect back to the same page (or show a success message)
    header("Location: {$_SERVER['REQUEST_URI']}");
    exit();
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Appointment Details</title>
    <link rel="stylesheet" type="text/css" href="css/dentist.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
</head>
<style>
    /* General Styles */
/* General Styles */


/* General Styles */
body {
    font-family: 'Arial', sans-serif;
    background-color: #f4f4f9;
    color: #333;
    margin: 0;
    padding: 0;
}

.main-content {
    max-width: 750px;
    height: 400px;
    margin: 20px auto;
    padding: 30px;
    background-color: #fff;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    border-radius: 12px;
}

h1, h2 {
    color: #2c3e50;
    margin-bottom: 20px;
}

h1 {
    font-size: 2.5em;
    border-bottom: 2px solid #3498db;
    padding-bottom: 10px;
}

h2 {
    font-size: 2em;
    margin-top: 30px;
    color: #3498db;
}

/* Appointment Information Section */
.appointment-info {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-bottom: 20px;
}

.appointment-info p {
    margin: 0;
    font-size: 1.1em;
    line-height: 1.6;
}

.appointment-info strong {
    color: #2c3e50;
    display: inline-block;
    width: 150px; /* Fixed width for labels */
}

/* Button Styles */
.button {
    background-color: #3498db;
    color: #fff;
    padding: 12px 24px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 1em;
    transition: background-color 0.3s ease;
    display: inline-block;
    margin-bottom: 20px;
}

.button:hover {
    background-color: #2980b9;
}

/* Modal Overlay */
#dentalHistoryModalOverlay,
#medicalHistoryModalOverlay {
    display: none; /* Hidden by default */
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5); /* Semi-transparent black background */
    z-index: 1000; /* Ensure it's on top of other content */
    justify-content: center;
    align-items: center;
}

/* Modal Content */
.modal-content {
    background-color: #fff;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    width: 90%;
    max-width: 800px; /* Slightly wider for tables */
    max-height: 90vh; /* Limit modal height */
    overflow-y: auto; /* Enable vertical scrolling */
    position: relative;
}

/* Close Button */
.close-modal {
    position: absolute;
    top: 10px;
    right: 15px;
    font-size: 24px;
    cursor: pointer;
    color: #333;
    transition: color 0.3s ease;
}

.close-modal:hover {
    color: #000;
}

/* Table Styling */
table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}

table th, table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

table th {
    background-color: #3498db;
    color: #fff;
}

table tr:hover {
    background-color: #f1f1f1;
}

/* Responsive Design */
@media (max-width: 768px) {
    .modal-content {
        padding: 20px;
    }

    table, thead, tbody, th, td, tr {
        display: block;
    }

    table th {
        position: absolute;
        top: -9999px;
        left: -9999px;
    }

    table tr {
        border: 1px solid #ddd;
        margin-bottom: 10px;
    }

    table td {
        border: none;
        position: relative;
        padding-left: 50%;
    }

    table td:before {
        position: absolute;
        top: 12px;
        left: 6px;
        width: 45%;
        padding-right: 10px;
        white-space: nowrap;
        content: attr(data-label);
        font-weight: bold;
        color: #2c3e50;
    }

}

/* Link Styles */
a {
    color: #3498db;
    text-decoration: none;
    font-weight: bold;
    display: inline-block;
    margin-top: 20px;
}

a:hover {
    text-decoration: underline;
}

/* Back Button Styles */
.back-button {
    display: inline-block;
    margin-bottom: 20px; /* Space below the button */
    font-size: 1.2em; /* Icon size */
    color: #3498db; /* Icon color */
    text-decoration: none; /* Remove underline */
    transition: color 0.3s ease; /* Smooth color transition */
}

.back-button:hover {
    color: #2980b9; /* Darker color on hover */
}

/* Adjust the h1 heading to align with the back button */
h1 {
    display: inline-block;
    margin-left: 10px; /* Space between the icon and the heading */
    font-size: 2.5em;
    border-bottom: 2px solid #3498db;
    padding-bottom: 10px;
}


/* Modal Overlay */
#modalOverlay {
    display: none; /* Hidden by default */
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5); /* Semi-transparent black background */
    z-index: 1000; /* Ensure it's on top of other content */
    justify-content: center;
    align-items: center;
}

/* Modal Content */
.modal-content {
    background-color: #fff;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    width: 90%;
    max-width: 600px; /* Limit modal width */
    position: relative;
}

/* Close Button */
.close-modal {
    position: absolute;
    top: 10px;
    right: 15px;
    font-size: 24px;
    cursor: pointer;
    color: #333;
}

.close-modal:hover {
    color: #000;
}

/* Modal Overlay */
#modalOverlay {
    display: none; /* Hidden by default */
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5); /* Semi-transparent black background */
    z-index: 1000; /* Ensure it's on top of other content */
    justify-content: center;
    align-items: center;
    overflow: auto; /* Enable scrolling for the overlay if needed */
}

/* Modal Content */
.modal-content {
    background-color: #fff;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    width: 90%;
    max-width: 600px; /* Limit modal width */
    max-height: 90vh; /* Limit modal height to 90% of the viewport height */
    overflow-y: auto; /* Enable vertical scrolling */
    position: relative;
}

/* Close Button */
.close-modal {
    position: absolute;
    top: 10px;
    right: 15px;
    font-size: 24px;
    cursor: pointer;
    color: #333;
    transition: color 0.3s ease;
}

.close-modal:hover {
    color: #000;
}

/* Modal Title */
.modal-content h2 {
    font-size: 2em;
    color: #2c3e50;
    margin-bottom: 20px;
    border-bottom: 2px solid #3498db;
    padding-bottom: 10px;
}

/* Form Styles */
.modal-content form {
    display: flex;
    flex-direction: column;
}

.modal-content label {
    font-weight: bold;
    color: #2c3e50;
    margin-bottom: 8px;
}

.modal-content textarea,
.modal-content input[type="text"],
.modal-content input[type="date"],
.modal-content input[type="time"],
.modal-content input[type="file"] {
    width: 100%;
    padding: 10px;
    margin-bottom: 15px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 1em;
    box-sizing: border-box; /* Ensures padding doesn't affect width */
}

.modal-content textarea {
    resize: vertical;
    height: 100px;
}

/* Teeth Part Group */
.teeth-part-group {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
}

.teeth-part-group label {
    font-weight: normal;
    color: #333;
}

/* Submit Button */
.modal-content .button {
    background-color: #3498db;
    color: #fff;
    padding: 12px 24px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 1em;
    transition: background-color 0.3s ease;
    align-self: flex-start; /* Align button to the left */
}

.modal-content .button:hover {
    background-color: #2980b9;
}

/* Icon Button Styles */
.icon-button {
    background-color: #3498db; /* Blue background */
    color: #fff; /* White icon */
    border: none;
    border-radius: 50%; /* Circular button */
    width: 36px; /* Fixed width */
    height: 36px; /* Fixed height */
    font-size: 18px; /* Icon size */
    cursor: pointer;
    margin-left: 10px; /* Space between heading and button */
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: background-color 0.3s ease;
}

.icon-button:hover {
    background-color: #2980b9; /* Darker blue on hover */
}
/* Responsive Design */
@media (max-width: 768px) {
    .modal-content {
        padding: 20px;
    }

    .modal-content h2 {
        font-size: 1.5em;
    }

    .teeth-part-group {
        flex-direction: column; /* Stack radio buttons vertically on smaller screens */
    }
}
/* Responsive Design */
@media (max-width: 768px) {
    .main-content {
        padding: 20px;
    }

    h1 {
        font-size: 2em;
    }

    h2 {
        font-size: 1.5em;
    }

    .appointment-info {
        grid-template-columns: 1fr; /* Stack on smaller screens */
    }

    .button {
        width: 100%;
        padding: 15px;
    }

    table, thead, tbody, th, td, tr {
        display: block;
    }

    table th {
        position: absolute;
        top: -9999px;
        left: -9999px;
    }

    table tr {
        border: 1px solid #ddd;
        margin-bottom: 10px;
    }

    table td {
        border: none;
        position: relative;
        padding-left: 50%;
    }

    table td:before {
        position: absolute;
        top: 12px;
        left: 6px;
        width: 45%;
        padding-right: 10px;
        white-space: nowrap;
        content: attr(data-label);
        font-weight: bold;
        color: #2c3e50;
    }
}
    </style>
<body>
    <div class="main-content">
    <a href="dentist_patient_appointments.php" class="back-button">
        <i class="fas fa-arrow-left"></i> <!-- Font Awesome back arrow icon -->
    </a>
        <h1>Appointment Details</h1>
        <h2>Appointment Information
    <button id="showFormBtn" class="icon-button">
        <i class="fas fa-plus"></i> <!-- Font Awesome plus icon -->
    </button>
</h2>
        <p><strong>Appointment ID:</strong> <?php echo htmlspecialchars($appointment_id); ?></p>
        <p><strong>Patient Name:</strong> <?php echo htmlspecialchars($first_name . ' ' . $last_name); ?></p>
        <p><strong>Date:</strong> <?php echo htmlspecialchars($appointment_date); ?></p>
        <p><strong>Time:</strong> <?php echo htmlspecialchars($appointment_time); ?></p>
        <p><strong>Is Emergency:</strong> <?php echo htmlspecialchars($is_emergency ? 'Yes' : 'No'); ?></p>
        <p><strong>Created At:</strong> <?php echo htmlspecialchars($appointment_created_at); ?></p>
        <p><strong>Reason:</strong> <?php echo htmlspecialchars($reason); ?></p>

        <!-- Button to show treatment documentation form -->
       
        <!-- Treatment Documentation Form (Initially Hidden) -->
       <!-- Button to show treatment documentation modal -->

<!-- Modal Overlay -->
<div id="modalOverlay" style="display: none;">
    <!-- Modal Content -->
    <div id="treatmentModal" class="modal-content">
        <!-- Close Button -->
        <span id="closeModalBtn" class="close-modal">&times;</span>

        <!-- Modal Title -->
        <h2>Treatment Documentation</h2>

        <!-- Treatment Documentation Form -->
        <form action="" method="POST" enctype="multipart/form-data">
            <label for="diagnosis">Diagnosis:</label>
            <textarea id="diagnosis" name="diagnosis" required></textarea><br><br>

            <label for="treatment_performed">Treatment Performed:</label>
            <textarea id="treatment_performed" name="treatment_performed" required></textarea><br><br>

            <label for="medication_prescribed">Medication Prescribed:</label>
            <textarea id="medication_prescribed" name="medication_prescribed" required></textarea><br><br>

            <label for="image">Images/X-rays:</label>
            <input type="file" id="image" name="image"><br><br>

            <label for="upper_teeth_left">Upper Teeth Left:</label>
            <input type="text" id="upper_teeth_left" name="upper_teeth_left"><br><br>

            <label for="lower_teeth_left">Lower Teeth Left:</label>
            <input type="text" id="lower_teeth_left" name="lower_teeth_left"><br><br>

            <label for="teeth_part">What Part of the Teeth:</label><br>
            <div class="teeth-part-group">
                <input type="radio" id="upper" name="teeth_part" value="upper">
                <label for="upper">Upper</label>

                <input type="radio" id="lower" name="teeth_part" value="lower">
                <label for="lower">Lower</label>

                <input type="radio" id="both" name="teeth_part" value="both">
                <label for="both">Upper and Lower</label>
            </div>
            <br><br>

            <label for="follow_up_date">Follow-up Treatment:</label>
            <input type="date" id="follow_up_date" name="follow_up_date" value="N/A"><br><br>

            <label for="appointment_time">Appointment Time:</label>
            <input type="time" id="appointment_time" name="appointment_time" required><br><br>

            <button type="submit" class="button">Save Treatment Record</button>
        </form>
    </div>
</div>
        
      <!-- Buttons to trigger modals -->
<button id="showDentalHistoryBtn" class="button">View Dental History</button>
<button id="showMedicalHistoryBtn" class="button">View Medical History</button>

<!-- Dental History Modal -->
<div id="dentalHistoryModalOverlay" style="display: none;">
    <div class="modal-content">
        <span id="closeDentalHistoryModalBtn" class="close-modal">&times;</span>
        <h2>Dental History</h2>
        <?php if (!empty($dental_history)): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Previous Procedures</th>
                        <th>Last Visit</th>
                        <th>Reason</th>
                        <th>Complications</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dental_history as $history): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($history['Dental_History_ID']); ?></td>
                            <td><?php echo htmlspecialchars($history['Previous_Procedures']); ?></td>
                            <td><?php echo htmlspecialchars($history['Last_Dental_Visit']); ?></td>
                            <td><?php echo htmlspecialchars($history['Reason_for_Visit']); ?></td>
                            <td><?php echo htmlspecialchars($history['Complications']); ?></td>
                            <td><?php echo htmlspecialchars($history['created_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No dental history found for this patient.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Medical History Modal -->
<div id="medicalHistoryModalOverlay" style="display: none;">
    <div class="modal-content">
        <span id="closeMedicalHistoryModalBtn" class="close-modal">&times;</span>
        <h2>Medical History</h2>
        <?php if (!empty($medical_history)): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Conditions</th>
                        <th>Allergies</th>
                        <th>Medications</th>
                        <th>Surgeries</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($medical_history as $history): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($history['MedicalHistory_ID']); ?></td>
                            <td><?php echo htmlspecialchars($history['Current_Medical_Conditions']); ?></td>
                            <td><?php echo htmlspecialchars($history['Allergies']); ?></td>
                            <td><?php echo htmlspecialchars($history['Medications']); ?></td>
                            <td><?php echo htmlspecialchars($history['Previous_Surgeries']); ?></td>
                            <td><?php echo htmlspecialchars($history['created_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No medical history found for this patient.</p>
        <?php endif; ?>
    </div>
</div>

        <!-- Back button -->
       
    <!-- JavaScript to toggle the form visibility -->
    <script>
        // JavaScript to handle modal functionality
        document.addEventListener('DOMContentLoaded', function () {
    var showFormBtn = document.getElementById('showFormBtn');
    var modalOverlay = document.getElementById('modalOverlay');
    var closeModalBtn = document.getElementById('closeModalBtn');

    // Show modal when "Add Treatment Documentation" button is clicked
    showFormBtn.onclick = function () {
        modalOverlay.style.display = 'flex'; // Show the modal overlay
    };

    // Hide modal when close button is clicked
    closeModalBtn.onclick = function () {
        modalOverlay.style.display = 'none'; // Hide the modal overlay
    };

    // Hide modal when clicking outside the modal content
    modalOverlay.onclick = function (event) {
        if (event.target === modalOverlay) {
            modalOverlay.style.display = 'none'; // Hide the modal overlay
        }
    };
});


document.addEventListener('DOMContentLoaded', function () {
    // Dental History Modal
    var showDentalHistoryBtn = document.getElementById('showDentalHistoryBtn');
    var dentalHistoryModalOverlay = document.getElementById('dentalHistoryModalOverlay');
    var closeDentalHistoryModalBtn = document.getElementById('closeDentalHistoryModalBtn');

    showDentalHistoryBtn.onclick = function () {
        dentalHistoryModalOverlay.style.display = 'flex';
    };

    closeDentalHistoryModalBtn.onclick = function () {
        dentalHistoryModalOverlay.style.display = 'none';
    };

    dentalHistoryModalOverlay.onclick = function (event) {
        if (event.target === dentalHistoryModalOverlay) {
            dentalHistoryModalOverlay.style.display = 'none';
        }
    };

    // Medical History Modal
    var showMedicalHistoryBtn = document.getElementById('showMedicalHistoryBtn');
    var medicalHistoryModalOverlay = document.getElementById('medicalHistoryModalOverlay');
    var closeMedicalHistoryModalBtn = document.getElementById('closeMedicalHistoryModalBtn');

    showMedicalHistoryBtn.onclick = function () {
        medicalHistoryModalOverlay.style.display = 'flex';
    };

    closeMedicalHistoryModalBtn.onclick = function () {
        medicalHistoryModalOverlay.style.display = 'none';
    };

    medicalHistoryModalOverlay.onclick = function (event) {
        if (event.target === medicalHistoryModalOverlay) {
            medicalHistoryModalOverlay.style.display = 'none';
        }
    };
});
    </script>
</body>
</html>
