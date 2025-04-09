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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<style>
 /* General Styles */
body {
    font-family: Arial, sans-serif;
    background-color: #f4f4f4;
    margin: 0;
    padding: 0;
}





/* Main Content Styles */
.main-content {
    flex: 1;
    padding: 20px;
    background-color: #fff;
}

.main-content h1 {
    color: #2c3e50;
    margin-bottom: 20px;
    font-size: 2rem;
    display: flex;
    align-items: center;
    gap: 10px; /* Space between icon and text */
}

.error {
    color: #e74c3c;
    background-color: #f8d7da;
    padding: 10px;
    border-radius: 4px;
    margin-bottom: 20px;
    border: 1px solid #f5c6cb;
}

/* Form Styles */
form {
    max-width: 500px;
    margin: 0 auto;
    padding: 10px;
    background-color: #f9f9f9;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-weight: bold;
    margin-bottom: 5px;
    color: #333;
}

.form-group textarea {
    width: 480px;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 1rem;
    resize: vertical; /* Allow vertical resizing */
    min-height: 30px;
}

.form-group textarea:focus {
    border-color: #3498db;
    outline: none;
    box-shadow: 0 0 5px rgba(52, 152, 219, 0.5);
}

/* Button Styles */
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 10px 20px;
    background-color: #3498db;
    color: #fff;
    text-decoration: none;
    border-radius: 4px;
    font-size: 1rem;
    cursor: pointer;
    border: none;
    transition: background-color 0.3s ease;
}

.btn:hover {
    background-color: #2874a6;
}

.btn i {
    margin-right: 8px; /* Add spacing between icon and text (if any) */
}

/* Back Button Styles */
.back-btn {
    
    text-decoration: none;
    padding: 10px 20px;
    border-radius: 4px;
    display: inline-flex;
    align-items: center;
    gap: 8px; /* Space between icon and text */
}

.back-btn:hover {
    background-color: blue;
    color: black;
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
        padding: 15px;
    }

    form {
        padding: 15px;
    }

    .main-content h1 {
        flex-direction: column;
        align-items: flex-start;
    }
}
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
        <!-- Back Button and Heading -->
        <h1>
            <a href="patient_detail.php?patient_id=<?php echo $patient_id; ?>" class="back-btn">
                <i class="fas fa-arrow-left"></i> <!-- Back button icon -->
            </a>
            Add Medical History for <?php echo htmlspecialchars($patient['First_Name'] . ' ' . $patient['Last_Name']); ?>
        </h1>
        
        <?php if (!empty($error_message)): ?>
            <p class="error"><?php echo $error_message; ?></p>
        <?php endif; ?>

        <!-- Form -->
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
    </main>
</div>
</body>
</html>