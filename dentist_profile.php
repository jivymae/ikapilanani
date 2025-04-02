<?php
session_start();
include 'db_config.php';

// Redirect non-dentists to login page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'dentist') {
    header('Location: login.php');
    exit();
}

// Fetch dentist details
$dentist_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT username, email, phone, address FROM users WHERE user_id = ? AND role = 'dentist'");
$stmt->bind_param("i", $dentist_id);
$stmt->execute();
$stmt->bind_result($username, $email, $phone, $address);
$stmt->fetch();
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dentist Profile</title>
    <link rel="stylesheet" type="text/css" href="css/dentist.css">
    <style>


/* Main Content */
.main-content {
    flex: 1;
    padding: 20px;
    /* Offset for fixed sidebar */
}

.main-content h1 {
    color: #8878c3; /* Dark Green */
    font-size: 24px;
    margin-bottom: 20px;
}

/* Profile Information Section */
.profile-info {
    background-color: #ffffff; /* White Background */
    padding: 20px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Light Shadow */
    margin-top: 20px;
    border-radius: 8px; /* Rounded corners */
}

.profile-info a {
    display: inline-block;
    padding: 12px 20px;
    background-color: #8878c3; /* Sand Color */
    color: #ffffff;
    text-decoration: none;
    border-radius: 5px;
    font-size: 16px;
    transition: background-color 0.3s ease;
    margin-bottom: 20px;
}

.profile-info a:hover {
    background-color: #1565c0; /* Muted Teal */
}

.profile-info p {
    font-size: 18px;
    color: #333333; /* Dark Gray */
    margin-bottom: 10px;
}

.profile-info strong {
    color: #354649; /* Dark Green */
}

/* Responsive Design */
@media screen and (max-width: 768px) {
    body {
        flex-direction: column;
    }

    .sidebar {
        width: 100%;
        height: auto;
        position: static;
    }

    .main-content {
        margin-left: 0;
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
        <a href="dentist_profile.php" class="active">Profile</a>
        <a href="dentist_patient_appointments.php">Appointments</a>
        <a href="dentist_patient_records.php">Patient Records</a>
       
        <a href="logout.php">Logout</a>
    </div>
    
    <div class="main-content">
        <h1>Dentist Profile</h1>
        <div class="profile-info">
        <a href="update_dentist_profile.php">Update Profile</a>
            <p><strong>Username:</strong> <?php echo htmlspecialchars($username ?? ''); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($email ?? ''); ?></p>
            <p><strong>Phone:</strong> <?php echo htmlspecialchars($phone ?? ''); ?></p>
            <p><strong>Address:</strong> <?php echo htmlspecialchars($address ?? ''); ?></p>
        </div>
    </div>
    
</body>
</html>
