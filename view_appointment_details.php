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


.main-content {
    width: 80%;
    margin: 30px auto;
    padding: 25px;
    background-color: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    border: 1px solid #ddd;
}

button.button {
    background-color: #7a4fd1; /* Smooth Violet */
    color: #fff;
    padding: 12px 18px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    margin-top: 20px;
    font-size: 16px;
    transition: background-color 0.3s ease, transform 0.3s ease;
}

button.button:hover {
    background-color: #5c2f99; /* Darker Violet on hover */
    transform: scale(1.05); /* Slight enlarge effect */
}

/* Styling for the "What Part of the Teeth" section */
form label {
    font-weight: 600;
    color: #5c3c91;
    font-size: 16px;
}

form .teeth-part-group {
    display: flex;
    gap: 20px; /* Space between radio buttons */
    align-items: center; /* Vertically center the items */
}

form input[type="radio"] {
    margin-right: 8px;
    transform: scale(1.2); /* Increase size of radio buttons */
}

form label {
    font-size: 16px;
    color: #5c3c91; /* Color for the labels */
    cursor: pointer;
}

/* When radio is selected */
form input[type="radio"]:checked + label {
    font-weight: bold;
    color: #7a4fd1;
}


form {
    margin-top: 30px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

form label {
    font-weight: 600;
    color: #5c3c91; /* Slightly muted purple for labels */
    font-size: 16px;
}

form input[type="text"],
form input[type="date"],
form textarea,
form input[type="file"],
form input[type="time"] {
    padding: 10px;
    border: 1px solid #e0c7f7; /* Light purple border */
    border-radius: 6px;
    background-color: #f7f4ff;
    font-size: 14px;
    transition: border 0.3s ease;
}

form input[type="text"]:focus,
form input[type="date"]:focus,
form textarea:focus,
form input[type="file"]:focus,
form input[type="time"]:focus {
    border: 1px solid #7a4fd1; /* Violet border on focus */
    outline: none;
}

form textarea {
    resize: vertical;
    min-height: 120px;
}

form input[type="radio"] {
    margin-right: 8px;
}

form input[type="radio"]:checked + label {
    font-weight: bold;
    color: #7a4fd1;
}

table {
    width: 100%;
    margin-top: 30px;
    border-collapse: collapse;
    background-color: #fff;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    border-radius: 8px;
}

table th, table td {
    padding: 12px;
    text-align: left;
    border: 1px solid #e0c7f7;
}

table th {
    background-color: #7a4fd1;
    color: white;
    font-weight: bold;
    text-transform: uppercase;
}

table td {
    background-color: #f9f5ff;
    color: #333;
    font-size: 14px;
}

table tr:nth-child(even) {
    background-color: #f2e9ff; /* Light lilac for even rows */
}

table tr:hover {
    background-color: #e0d2f5; /* Subtle hover effect */
}

table td, table th {
    border-radius: 6px;
}

/* Responsive Styles */
@media (max-width: 768px) {
    .main-content {
        width: 90%;
    }

    table, th, td {
        font-size: 14px;
    }

    button.button {
        width: 100%;
        padding: 12px 20px;
    }
}

/* Treatment Form Styling */
#treatmentForm {
    margin-top: 30px;
    background-color: #f4f4f9;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

#showFormBtn {
    background-color: #8e44ad;
    color: white;
    padding: 10px 15px;
    border-radius: 8px;
    transition: background-color 0.3s ease;
}

#showFormBtn:hover {
    background-color: #6c2a8b;
}

h2 {
    color: #7a4fd1; /* Soft violet */
}

input[type="radio"] {
    margin-right: 5px;
}

form input[type="radio"] + label {
    font-weight: normal;
    margin-right: 20px;
}

form input[type="radio"]:checked + label {
    color: #7a4fd1; /* Highlight checked radio buttons */
}

/* Form Section Styling */
form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}


/* Buttons Styling */
button.button {
    background-color: #9b7fd3;
    border-radius: 8px;
    color: white;
    padding: 12px 16px;
    font-size: 16px;
    border: none;
    transition: all 0.3s ease;
}

button.button:hover {
    background-color: #7a4fd1;
    transform: scale(1.05);
}

form input[type="date"],
form input[type="text"],
form input[type="file"],
form textarea {
    padding: 12px;
    background-color: #f8f6ff;
    border-radius: 6px;
    border: 1px solid #e0c7f7;
    transition: border 0.3s ease;
}

form input[type="date"]:focus,
form input[type="text"]:focus,
form textarea:focus,
form input[type="file"]:focus {
    border: 1px solid #7a4fd1;
}

/* Customizing Back link */
a {
    display: inline-block;
    margin-top: 20px;
    padding: 10px 20px;
    background-color: #8e44ad;
    color: white;
    text-decoration: none;
    border-radius: 6px;
    font-size: 16px;
    transition: all 0.3s ease;
}

a:hover {
    background-color: #6c2a8b;
    transform: scale(1.05);
}

/* Enhancing table responsiveness */
@media (max-width: 768px) {
    .main-content {
        width: 95%;
    }

    table, th, td {
        font-size: 14px;
    }

    .main-content h1 {
        font-size: 24px;
    }

    button.button {
        width: 100%;
    }
}

    </style>
<body>
    <div class="main-content">
        <h1>Appointment Details</h1>
        <h2>Appointment Information</h2>
        <p><strong>Appointment ID:</strong> <?php echo htmlspecialchars($appointment_id); ?></p>
        <p><strong>Patient Name:</strong> <?php echo htmlspecialchars($first_name . ' ' . $last_name); ?></p>
        <p><strong>Date:</strong> <?php echo htmlspecialchars($appointment_date); ?></p>
        <p><strong>Time:</strong> <?php echo htmlspecialchars($appointment_time); ?></p>
        <p><strong>Is Emergency:</strong> <?php echo htmlspecialchars($is_emergency ? 'Yes' : 'No'); ?></p>
        <p><strong>Created At:</strong> <?php echo htmlspecialchars($appointment_created_at); ?></p>
        <p><strong>Reason:</strong> <?php echo htmlspecialchars($reason); ?></p>

        <!-- Button to show treatment documentation form -->
        <button id="showFormBtn" class="button">Add Treatment Documentation</button>

        <!-- Treatment Documentation Form (Initially Hidden) -->
        <div id="treatmentForm" style="display:none;">
            <h2>Treatment Documentation</h2>
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

        <!-- Back button -->
        <a href="dentist_patient_appointments.php">Back to Appointments</a>
    </div>

    <!-- JavaScript to toggle the form visibility -->
    <script>
        // Toggle the treatment form visibility
        var showFormBtn = document.getElementById("showFormBtn");
        var treatmentForm = document.getElementById("treatmentForm");

        showFormBtn.onclick = function() {
            // Toggle form visibility
            if (treatmentForm.style.display === "none") {
                treatmentForm.style.display = "block";
                showFormBtn.textContent = "Hide Treatment Documentation Form";
            } else {
                treatmentForm.style.display = "none";
                showFormBtn.textContent = "Add Treatment Documentation";
            }
        }
    </script>
</body>
</html>
