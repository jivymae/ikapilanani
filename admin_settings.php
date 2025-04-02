<?php
include 'login_check.php';  // Ensure user is logged in
include 'db_config.php';
include 'admin_check.php';  // Admin check
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings - Dental Clinic Management System</title>
    <link rel="stylesheet" type="text/css" href="css/style.css">
</head>
<style>
    /* General Styles */
body {
    font-family: Arial, sans-serif;
    background-color: #f4f4f9;
    color: #333;
    margin: 0;
    padding: 0;
}

.container {
    display: flex;
}



/* Main Content Styles */
.main-content {
    flex: 1;
    padding: 30px;
}

#settings h1 {
    
    font-size: 32px;
    margin-bottom: 30px;
    color: #333;
    text-align: ;
}

#settings ul {
    
    list-style-type: none;
    padding: 0;
}

#settings ul li {
    margin-top: 5%;
    margin-bottom: 20px;
    text-align: ;
}

#settings ul li a {
    font-size: 18px;
    text-decoration: none;
    color: #0066cc;
    padding: 12px 20px;
    border-radius: 5px;
    border: 1px solid #0066cc;
    transition: background-color 0.3s ease;
}

#settings ul li a:hover {
    background-color: #0066cc;
    color: white;
}

/* Responsive Design */
@media (max-width: 768px) {
    .container {
        flex-direction: column;
    }

    .sidebar {
        width: 100%;
        padding: 15px;
    }

    .main-content {
        padding: 20px;
    }

    #settings ul li a {
        font-size: 16px;
        padding: 10px 15px;
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
                
                <li><a href="admin_settings.php" class="active">Settings</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <section id="settings">
                <h1>Admin Settings</h1>
                
                <ul>
                    <li><a href="admin_profile.php">Profile</a></li>
                    <li><a href="admin_change_password.php">Change Password</a></li>
                    <li><a href="admin_manage_time_slots.php">Manage Time Slots</a></li>
                    <li><a href="admin_clinic_closure.php">Set Clinic Closure Dates</a></li>
                    <li><a href="admin_payment_methods.php">Manage Payment Methods</a></li>
                    <li><a href="admin_patient_payments.php">Payments</a></li>
                   
                    
                </ul>
            </section>
        </main>
    </div>
</body>
</html>
