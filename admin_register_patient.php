<?php
// Include the database configuration file
include 'db_config.php';
session_start();

// Initialize variables
$lastName = $firstName = $dob = $contactInfo = $emergencyContactName = "";
$relationship = $emergencyContactPhone = "";
$medicalConditions = $allergies = $medications = $previousSurgeries = "";
$previousProcedures = $lastDentalVisit = $reasonForVisit = $complications = "";
$successMessage = "";  
$errorMessage = "";   
$email = ""; // Initialize $email 
$gender = "";  // Initialize gender variable
$seniorCitizenID = ""; // Initialize Senior Citizen ID

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize form inputs
    $lastName = $conn->real_escape_string(trim($_POST['last_name']));
    $firstName = $conn->real_escape_string(trim($_POST['first_name']));
    $dob = $conn->real_escape_string(trim($_POST['dob']));
    $contactInfo = $conn->real_escape_string(trim($_POST['contact_info']));
    $email = isset($_POST['email']) ? $conn->real_escape_string(trim($_POST['email'])) : '';
    $seniorCitizenID = isset($_POST['senior_citizen_id']) ? $conn->real_escape_string(trim($_POST['senior_citizen_id'])) : ''; // Add Senior Citizen ID
    $emergencyContactName = $conn->real_escape_string(trim($_POST['emergency_contact_name']));
    $relationship = $conn->real_escape_string(trim($_POST['relationship']));
    $emergencyContactPhone = $conn->real_escape_string(trim($_POST['emergency_contact_phone']));

    // Get gender value
    $gender = isset($_POST['gender']) ? $conn->real_escape_string(trim($_POST['gender'])) : '';

    $medicalConditions = isset($_POST['medical_conditions']) ? implode(", ", $_POST['medical_conditions']) : '';
    if (!empty($_POST['other_condition'])) {
        $medicalConditions .= (!empty($medicalConditions) ? ", " : "") . $conn->real_escape_string(trim($_POST['other_condition']));
    }

    $allergies = isset($_POST['allergies']) ? implode(", ", $_POST['allergies']) : '';
    if (!empty($_POST['other_allergy'])) {
        $allergies .= (!empty($allergies) ? ", " : "") . $conn->real_escape_string(trim($_POST['other_allergy']));
    }

    $medications = $conn->real_escape_string(trim($_POST['medications']));
    $previousSurgeries = $conn->real_escape_string(trim($_POST['previous_surgeries']));
    $previousProcedures = $conn->real_escape_string(trim($_POST['previous_procedures']));
    $lastDentalVisit = $conn->real_escape_string(trim($_POST['last_dental_visit']));
    $reasonForVisit = $conn->real_escape_string(trim($_POST['reason_for_visit']));
    $complications = $conn->real_escape_string(trim($_POST['complications']));

    // Attempt to insert into database
    $conn->begin_transaction(); // Start transaction
    try {
        $sql = "INSERT INTO Patients (Last_Name, First_Name, Date_of_Birth, Contact_Information, Emergency_Contact_Name, Relationship_to_Patient, Emergency_Contact_Phone, Email, Gender, Senior_Citizen_ID)
                VALUES ('$lastName', '$firstName', '$dob', '$contactInfo', '$emergencyContactName', '$relationship', '$emergencyContactPhone', '$email', '$gender', '$seniorCitizenID')";
        $conn->query($sql);

        $patientID = $conn->insert_id;

        $sqlMedicalHistory = "INSERT INTO medical_history (patient_id, current_medical_conditions, allergies, medications, previous_surgeries)
                              VALUES ('$patientID', '$medicalConditions', '$allergies', '$medications', '$previousSurgeries')";
        $conn->query($sqlMedicalHistory);

        $sqlDentalHistory = "INSERT INTO dental_history (patient_id, Previous_Procedures, Last_Dental_Visit, Reason_for_Visit, Complications)
                             VALUES ('$patientID', '$previousProcedures', '$lastDentalVisit', '$reasonForVisit', '$complications')";
        $conn->query($sqlDentalHistory);

        $conn->commit(); // Commit transaction
        $_SESSION['successMessage'] = "New patient, medical history, and dental history registered successfully!";
        header("Location: admin_register_patient.php"); // Redirect to avoid resubmission
        exit();
    } catch (Exception $e) {
        $conn->rollback(); // Rollback transaction
        $errorMessage = "Error: " . $e->getMessage();
    }
}


// Handle dental history insertion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_dental_history'])) {
    $patient_id = $_POST['patient_id'];
    $previous_procedures = $_POST['previous_procedures'];
    $last_dental_visit = empty($_POST['last_dental_visit']) ? NULL : $_POST['last_dental_visit']; // Set NULL if empty
    $reason_for_visit = $_POST['reason_for_visit'];
    $complications = $_POST['complications'];

    // Insert into the dental history table
    $stmt = $conn->prepare("INSERT INTO dental_history (Patient_ID, Previous_Procedures, Last_Dental_Visit, Reason_for_Visit, Complications, created_at) 
                            VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param('issss', $patient_id, $previous_procedures, $last_dental_visit, $reason_for_visit, $complications);
    $stmt->execute();
    $stmt->close();

    // Redirect or display success message
    header('Location: dental_history.php');
    exit();
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Patient - Dental Clinic Management System</title>
    <link rel="stylesheet" type="text/css" href="css/style.css">

    <script>
        function toggleOtherCondition(checkboxId, inputId) {
            var checkbox = document.getElementById(checkboxId);
            var input = document.getElementById(inputId);
            if (checkbox.checked) {
                input.style.display = "block";
            } else {
                input.style.display = "none";
            }
        }
    </script>

    <style>
/* General Styles */


.left-column, .right-column {
    background-color: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    margin-bottom: 20px;
}

h2 {
    color: #2c3e50;
    margin-bottom: 20px;
}

h3 {
    color: #34495e;
    margin-top: 20px;
    margin-bottom: 10px;
}

label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
    color: #333;
}

input[type="text"],
input[type="date"],
input[type="email"],
input[type="password"],
select,
textarea {
    width: 100%;
    padding: 10px;
    margin-bottom: 15px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 1em;
    box-sizing: border-box;
}

input[type="text"]:focus,
input[type="date"]:focus,
input[type="email"]:focus,
input[type="password"]:focus,
select:focus,
textarea:focus {
    border-color: #3498db;
    outline: none;
}

.checkbox-group {
    margin-bottom: 15px;
}

.checkbox-group label {
    display: block;
    margin-bottom: 10px;
    font-weight: normal;
}

.checkbox-group input[type="checkbox"] {
    margin-right: 10px;
}

.gender-options {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
}

.gender-options .gender-label {
    display: flex;
    align-items: center;
    font-weight: normal;
}

.gender-options input[type="radio"] {
    margin-right: 5px;
}

button[type="submit"] {
    background-color: #3498db;
    color: #fff;
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    font-size: 1em;
    cursor: pointer;
    transition: background-color 0.3s;
}

button[type="submit"]:hover {
    background-color: #2980b9;
}

.success {
    background-color: #d4edda;
    color: #155724;
    padding: 10px;
    border-radius: 4px;
    margin-bottom: 20px;
    border: 1px solid #c3e6cb;
}

/* Responsive Design */
@media (max-width: 768px) {
    .container {
        flex-direction: column;
    }

    .sidebar {
        width: 100%;
        height: auto;
        padding: 10px;
    }

    .main-content {
        padding: 10px;
    }

    .left-column, .right-column {
        padding: 15px;
    }
}
    </style>
</head>

<body>
<div class="container">
    <aside class="sidebar">
        <h2 class="logo">
            <img src="images/lads.png" alt="Dental Clinic Logo">
            <h1>LAD DCAMS</h1>
        </h2>
        <ul>
            <li><a href="admin_dashboard.php">Dashboard</a></li>
            <li><a href="patients.php" class="active">Patients</a></li>
            <li><a href="dentists.php">Dentists</a></li>
            <li><a href="appointments.php">Appointments</a></li>
            <li><a href="admin_add_services.php">Add Services</a></li>
            <li><a href="reports.php">Reports</a></li>
            <li><a href="admin_settings.php">Settings</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </aside>

    <div class="main-content">
        <div class="left-column">
            <h2>Add New Patient</h2>

            <!-- Display success/error message -->
            <?php if (isset($_SESSION['successMessage'])) : ?>
                <div class="success"><?php echo $_SESSION['successMessage']; ?></div>
                <?php unset($_SESSION['successMessage']); ?>
            <?php endif; ?>

            <form action="admin_register_patient.php" method="POST">
                <h3>Personal Information</h3>
                <label for="last_name">Last Name</label>
                <input type="text" name="last_name" required value="<?php echo htmlspecialchars($lastName); ?>">

                <label for="first_name">First Name</label>
                <input type="text" name="first_name" required value="<?php echo htmlspecialchars($firstName); ?>">

                <label for="dob">Date of Birth</label>
                <input type="date" name="dob" required value="<?php echo htmlspecialchars($dob); ?>">

                <label for="gender">Gender</label>
<div class="gender-options">
    <label for="male" class="gender-label">
        <input type="radio" id="male" name="gender" value="Male" <?php echo ($gender == 'Male') ? 'checked' : ''; ?>> Male
    </label>
    <label for="female" class="gender-label">
        <input type="radio" id="female" name="gender" value="Female" <?php echo ($gender == 'Female') ? 'checked' : ''; ?>> Female
    </label>
    <label for="other" class="gender-label">
        <input type="radio" id="other" name="gender" value="Other" <?php echo ($gender == 'Other') ? 'checked' : ''; ?>> Other
    </label>
</div>


                <label for="contact_info">Contact Information</label>
                <input type="text" name="contact_info" required value="<?php echo htmlspecialchars($contactInfo); ?>">

                <label for="email">Email Address</label>
<input type="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>">

<label for="senior_citizen_id">Senior Citizen ID</label>
<input type="text" name="senior_citizen_id" value="<?php echo htmlspecialchars($seniorCitizenID ?? ''); ?>">

<label for="emergency_contact_name">Emergency Contact Name</label>
<input type="text" name="emergency_contact_name" required value="<?php echo htmlspecialchars($emergencyContactName); ?>">

                <label for="relationship">Relationship to Patient</label>
                <input type="text" name="relationship" required value="<?php echo htmlspecialchars($relationship); ?>">

                <label for="emergency_contact_phone">Emergency Contact Phone</label>
                <input type="text" name="emergency_contact_phone" required value="<?php echo htmlspecialchars($emergencyContactPhone); ?>">

                <h3>Medical History</h3>
                <label for="medical_conditions">Current Medical Conditions</label>
                <div class="checkbox-group">
                    <label><input type="checkbox" name="medical_conditions[]" value="Diabetes"> Diabetes</label>
                    <label><input type="checkbox" name="medical_conditions[]" value="Hypertension"> Hypertension</label>
                    <label><input type="checkbox" name="medical_conditions[]" value="Heart Disease"> Heart Disease</label>
                    <label><input type="checkbox" name="medical_conditions[]" value="Asthma"> Asthma</label>
                    <label><input type="checkbox" name="medical_conditions[]" value="Other" id="other_condition" onclick="toggleOtherCondition('other_condition', 'other_condition_input')"> Other</label>
                    <label><input type="checkbox" name="medical_conditions[]" value="None"> None</label>
                </div>
                
                <div id="other_condition_input">
                    <label for="other_condition">Other Condition (if any)</label>
                    <input type="text" name="other_condition" value="">
                </div>
            </div>

            <div class="right-column">
                <!-- Medical History Section -->
              

                <!-- Allergies Section -->
                <h3>Allergies</h3>
                <div class="checkbox-group">
                    <label><input type="checkbox" name="allergies[]" value="Penicillin"> Penicillin</label>
                    <label><input type="checkbox" name="allergies[]" value="Aspirin"> Aspirin</label>
                    <label><input type="checkbox" name="allergies[]" value="Latex"> Latex</label>
                    <label><input type="checkbox" name="allergies[]" value="Other" id="other_allergy" onclick="toggleOtherCondition('other_allergy', 'other_allergy_input')"> Other</label>
                </div>

                <div id="other_allergy_input">
                    <label for="other_allergy">Please specify allergy:</label>
                    <input type="text" name="other_allergy">
                </div>

                <!-- Medications Section -->
                <h3>Medications</h3>
                <label for="medications">Current Medications</label>
                <input type="text" name="medications" value="<?php echo $medications; ?>">

                <!-- Previous Surgeries Section -->
                <h3>Previous Surgeries</h3>
                <label for="previous_surgeries">Previous Surgeries</label>
                <input type="text" name="previous_surgeries" value="<?php echo $previousSurgeries; ?>">

                <h3>Dental History</h3>

                <div class="form-group">
                    <label for="previous_procedures">Previous Procedures (e.g., fillings, crowns, root canals)</label>
                    <input type="text" name="previous_procedures" value="<?php echo $previousProcedures; ?>">
                </div>

                <div class="form-group">
                    <label for="last_dental_visit">Last Dental Visit</label>
                    <input type="date" name="last_dental_visit" value="<?php echo $lastDentalVisit; ?>">
                </div>

                <div class="form-group">
                    <label for="reason_for_visit">Reason for Last Visit</label>
                    <input type="text" name="reason_for_visit" value="<?php echo $reasonForVisit; ?>">
                </div>

                <div class="form-group">
                    <label for="complications">Complications (if any)</label>
                    <input type="text" name="complications" value="<?php echo $complications; ?>">
                </div>

                <button type="submit">Submit</button>
            </form>
        </div>
    </div>
</div>

</body>
</html>
