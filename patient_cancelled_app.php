<?php
include 'login_check.php'; // Ensure user is logged in
include 'db_config.php';

// Check if the user is logged in and has the correct role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'patient') {
    header('Location: pat_unauthorized.php');
    exit();
}

// Fetch patient ID
$user_id = $_SESSION['user_id'];

// Fetch cancelled appointments from the appointments table (where status is 'cancelled')
$cancelled_appointments = [];
$stmt = $conn->prepare("
    SELECT 
        a.appointment_id, 
        a.dentist_id, 
        a.appointment_date, 
        a.appointment_time, 
        a.appointment_status,
        a.appointment_created_at
    FROM appointments a
    WHERE a.patient_id = ? AND a.appointment_status = 'cancelled'
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($appointment_id, $dentist_id, $appointment_date, $appointment_time, $appointment_status, $appointment_created_at);
while ($stmt->fetch()) {
    $cancelled_appointments[] = [
        'id' => $appointment_id,
        'dentist_id' => $dentist_id,
        'date' => $appointment_date,
        'time' => $appointment_time,
        'status' => $appointment_status,
        'created_at' => $appointment_created_at
    ];
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="css/patient.css">
    <title>Cancelled Appointments - Dental Clinic Management System</title>
</head>
<style>
    /* General Styles */
    body, h1, h2, p, ul, li, table, th, td {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
  font-family: Arial, sans-serif;
  margin: 0;
  padding: 0;
  color: #333;
  background-color: #f4f4f4;
}


        /* Navigation Bar */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #00bfff;
            color: #fff;
            padding: 0.7rem 1rem;
            position: relative;
        }

        .navbar .logo {
            display: flex;
            align-items: center;
        }

        .navbar .logo img {
            height: 50px;
            margin-right: 10px;
        }

        .navbar .logo h1 {
            font-size: 1.5rem;
            font-weight: bold;
        }

        .navbar .nav-links {
            list-style: none;
            display: flex;
            gap: 1rem;
            transition: all 0.3s ease;
        }

        .navbar .nav-links a {
            text-decoration: none;
            color: #fff;
            font-weight: ;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        .navbar .nav-links a.active, .navbar .nav-links a:hover {
            background-color: #0056b3;
        }

        /* Hamburger Menu */
        .hamburger {
            display: none;
            flex-direction: column;
            justify-content: space-around;
            width: 30px;
            height: 25px;
            cursor: pointer;
        }

        .hamburger span {
            background-color: #fff;
            height: 3px;
            width: 100%;
            border-radius: 3px;
        }

/* Appointment List Section */
.appointment-list {
    margin: 20px;
    padding: 20px;
    background-color: white;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    border-radius: 8px;
}

.appointment-list h2 {
    margin-bottom: 20px;
    text-align: center;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

th, td {
    padding: 12px;
    text-align: center;
    border: 1px solid #ddd;
}

th {
    background-color: #f2f2f2;
}

a {
    text-decoration: none;
    color: #3498db;
}

a:hover {
    text-decoration: underline;
}

/* Mobile Responsiveness */
@media (max-width: 768px) {
    body {
        padding: 0;
    }


    .appointment-list {
        margin: 10px;
        padding: 15px;
    }

    table {
        font-size: 14px;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        display: block;
    }

    th, td {
        padding: 10px;
    }


}

@media (max-width: 480px) {

    table {
        font-size: 12px;
    }

    th, td {
        padding: 12px;
    }

    .appointment-list {
        padding: 10px;
    }

    .appointment-list h2 {
        font-size: 1.25rem;
    }
}

/* Responsive Design */
@media (max-width: 768px) {
            .navbar .nav-links {
                display: none;
                flex-direction: column;
                position: absolute;
                top: 100%;
                left: 0;
                width: 100%;
                background-color: #007bff;
                padding: 1rem 0;
                z-index: 10;
            }

            .navbar .nav-links.show {
                display: flex;
            }

            .hamburger {
                display: flex;
            }
        }
    
    </style>
<body>
    <nav class="navbar">
        <div class="logo">
            <img src="images/lads.png" alt="Dental Clinic Logo">
            <h1>Dental Clinic</h1>
        </div>
        <div class="hamburger" onclick="toggleMenu()">
            <span></span>
            <span></span>
            <span></span>
        </div>
        <ul class="nav-links">
            <li><a href="patient_dashboard.php">Dashboard</a></li>
            <li><a href="patient_appointments.php">Appointments</a></li>
           
            <li><a href="patient_message.php">Messages</a></li>
            <li><a href="patient_profile.php">Profile</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>

    <section class="appointment-list">
        <h2>Your Cancelled Appointments</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($cancelled_appointments)): ?>
                    <tr>
                        <td colspan="6">No cancelled appointments.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($cancelled_appointments as $appointment): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($appointment['id']); ?></td>
                            <td><?php echo htmlspecialchars($appointment['date']); ?></td>
                            <td><?php echo htmlspecialchars($appointment['time']); ?></td>
                            <td><?php echo htmlspecialchars($appointment['status']); ?></td>
                            <td><?php echo htmlspecialchars($appointment['created_at']); ?></td>
                            <td>
                                <a href="patient_view_canceled_appointments.php?appointment_id=<?php echo htmlspecialchars($appointment['id']); ?>">View Canceled Appointment</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </section>

    <script>
        function toggleMenu() {
            const navLinks = document.querySelector('.nav-links');
            navLinks.classList.toggle('show');
        }
    </script>
</body>
</html>
