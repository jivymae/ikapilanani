<?php
include 'login_check.php'; // Ensure user is logged in
include 'db_config.php';

// Check if patient_id is passed in URL
if (!isset($_GET['patient_id']) || empty($_GET['patient_id'])) {
    die("Patient ID is missing.");
}

$patient_id = intval($_GET['patient_id']); // Get patient ID from URL and sanitize it

// Fetch patient details (to display patient name, etc., in the form)
$stmt = $conn->prepare("SELECT * FROM patients WHERE Patient_ID = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
$patient = $result->fetch_assoc();
$stmt->close();

// If the patient doesn't exist, show an error message
if (!$patient) {
    die("Patient not found.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Collect form data and sanitize inputs
    $current_medical_conditions = $_POST['current_medical_conditions'] ?? '';
    $allergies = $_POST['allergies'] ?? '';
    $medications = $_POST['medications'] ?? '';
    $previous_surgeries = $_POST['previous_surgeries'] ?? '';

    // Insert the new medical history into the database
    $insert_stmt = $conn->prepare("
        INSERT INTO medical_history (Patient_ID, Current_Medical_Conditions, Allergies, Medications, Previous_Surgeries, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $insert_stmt->bind_param("issss", $patient_id, $current_medical_conditions, $allergies, $medications, $previous_surgeries);

    if ($insert_stmt->execute()) {
        // Redirect back to patient details page after success
        header("Location: patient_detail.php?patient_id=$patient_id");
        exit();
    } else {
        $error_message = "Error adding medical history. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Medical History - Dental Clinic Management System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
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
      
        <h1>Add Medical History for <?php echo htmlspecialchars($patient['First_Name'] . ' ' . $patient['Last_Name']); ?></h1>
        
        <?php if (!empty($error_message)): ?>
            <p class="error"><?php echo $error_message; ?></p>
        <?php endif; ?>

        <form action="admin_add_medical_history.php?patient_id=<?php echo $patient_id; ?>" method="POST">
            <div class="form-group">
                <label for="current_medical_conditions">Current Medical Conditions:</label>
                <textarea id="current_medical_conditions" name="current_medical_conditions" rows="4" required></textarea>
            </div>
            <div class="form-group">
                <label for="allergies">Allergies:</label>
                <textarea id="allergies" name="allergies" rows="4"></textarea>
            </div>
            <div class="form-group">
                <label for="medications">Medications:</label>
                <textarea id="medications" name="medications" rows="4"></textarea>
            </div>
            <div class="form-group">
                <label for="previous_surgeries">Previous Surgeries:</label>
                <textarea id="previous_surgeries" name="previous_surgeries" rows="4"></textarea>
            </div>
            <div class="form-group">
                <button type="submit" class="btn">Add Medical History</button>
            </div>
        </form>
        <p><a href="patient_detail.php?patient_id=<?php echo $patient_id; ?>" class="btn">Back to Patient Details</a></p>

    </main>
</div>
</body>
</html>
