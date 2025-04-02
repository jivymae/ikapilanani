<?php
session_start();
include 'db_config.php'; // Ensure this file initializes $conn

// Redirect non-dentists to login page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'dentist') {
    header('Location: login.php');
    exit();
}

$dentist_id = $_SESSION['user_id'];

// Default search term is empty
$search_term = '';

// If the search form is submitted, assign the value to search_term
if (isset($_POST['search']) && !empty($_POST['search'])) {
    $search_term = '%' . $_POST['search'] . '%'; // Add percent signs for LIKE query
}

// Fetch patient names based on search term (or all records if no search term)
$patients = [];
if ($search_term) {
    // Fetch only the patients matching the search term
    $stmt = $conn->prepare("
        SELECT DISTINCT p.patient_id, p.first_name, p.last_name 
        FROM appointments a 
        JOIN patients p ON a.patient_id = p.patient_id 
        WHERE a.dentist_id = ? AND (p.first_name LIKE ? OR p.last_name LIKE ? OR CONCAT(p.first_name, ' ', p.last_name) LIKE ?)
    ");
    $stmt->bind_param("isss", $dentist_id, $search_term, $search_term, $search_term);
} else {
    // Fetch all patients
    $stmt = $conn->prepare("
        SELECT DISTINCT p.patient_id, p.first_name, p.last_name 
        FROM appointments a 
        JOIN patients p ON a.patient_id = p.patient_id 
        WHERE a.dentist_id = ?
    ");
    $stmt->bind_param("i", $dentist_id);
}

$stmt->execute();
$stmt->bind_result($patient_id, $first_name, $last_name);

while ($stmt->fetch()) {
    $patients[] = [
        'patient_id' => $patient_id,
        'first_name' => $first_name,
        'last_name' => $last_name,
    ];
}
$stmt->close();

// Fetch dentist details
$stmt = $conn->prepare("SELECT username, email FROM users WHERE user_id = ? AND role = 'dentist'");
$stmt->bind_param("i", $dentist_id);
$stmt->execute();
$stmt->bind_result($username, $email);
$stmt->fetch();
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dentist Patient Records</title>
    <link rel="stylesheet" type="text/css" href="css/dentist.css">
    <style>
        /* Main content styling */
        .main-content {
            margin-left: 20px; /* Set left margin to 20px */
            padding: 20px;
            background-color: #f4f4f4; /* Light background to ensure readability */
            min-height: 100vh; /* Ensure content takes full height */
            box-sizing: border-box; /* Include padding in width/height calculations */
        }

        /* Styling for patient records link */
        .patient-link {
            display: block;
            margin-bottom: 15px;
            padding: 10px;
            font-size: 18px;
            color: #58427c;
            text-decoration: none;
            border-radius: 8px; /* Rounded corners */
            background-color: #d6cadd;
            transition: all 0.3s ease;
        }

        .patient-link .patient-name {
            font-weight: bold;
        }

        .patient-link:hover {
            background-color: #f0e6ff;
            color: #3a1f6b;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .patient-link:active {
            transform: scale(0.98);
        }

        /* Style for the search form */
        .search-form {
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .search-form input[type="text"] {
            padding: 10px;
            font-size: 16px;
            border: 2px solid #fff;
            border-radius: 8px;
            width: 100%;
            outline: none;
            transition: all 0.3s ease;
        }

        .search-form button {
            padding: 10px 20px;
            font-size: 16px;
            background-color: #4c2882;
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .search-form button:hover {
            background-color: #3a1f6b;
            transform: scale(1.05);
        }

        .search-form button:active {
            transform: scale(0.98);
        }

        /* Hide patient list by default, show when search is performed */
        .record-list {
            display: <?php echo ($search_term) ? 'block' : 'none'; ?>;
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
        <a href="dentist_dashboard.php">Dashboard</a>
        <a href="dentist_profile.php">Profile</a>
        <a href="dentist_patient_appointments.php">Appointments</a>
        <a href="dentist_patient_records.php" class="active">Patient Records</a>
       
        <a href="logout.php">Logout</a>
    </nav>
</div>
<div class="main-content">
    <h1>Patients Who Booked Appointments</h1>
    <form method="POST" action="dentist_patient_records.php" class="search-form">
        <input type="text" name="search" placeholder="Search by First Name, Last Name, or Full Name" value="<?php echo isset($_POST['search']) ? htmlspecialchars($_POST['search']) : ''; ?>" />
        <button type="submit">Search</button>
    </form>
    
    <!-- The patient list will only be shown if a search term is provided -->
    <div class="record-list">
        <?php if (empty($patients)): ?>
            <p>No records found.</p>
        <?php else: ?>
            <?php foreach ($patients as $patient): ?>
                <a href="dentist_patient_details.php?patient_id=<?php echo htmlspecialchars($patient['patient_id']); ?>" class="patient-link">
                    <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
