<?php
include 'login_check.php'; // Ensure user is logged in
include 'db_config.php';

// Check if the user is logged in and has the correct role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'patient') {
    header('Location: pat_unauthorized.php'); // Redirect to an unauthorized page
    exit();
}

// Fetch patient information
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT username, email, first_name, last_name FROM users WHERE user_id = ? AND role = 'patient'");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($username, $email, $first_name, $last_name);
$stmt->fetch();
$stmt->close();

// Fetch completed appointments
try {
    $stmt = $conn->prepare("
        SELECT 
            a.appointment_id, 
            a.appointment_date, 
            a.appointment_time, 
            u.first_name AS dentist_first_name, 
            u.last_name AS dentist_last_name,
            tr.treatment_notes,
            p.pres_file,
            r.review_id
        FROM appointments a
        JOIN users u ON a.dentist_id = u.user_id
        LEFT JOIN treatment_records tr ON tr.appointment_id = a.appointment_id
        LEFT JOIN prescriptions p ON p.appointment_id = a.appointment_id
        LEFT JOIN reviews r ON r.appointment_id = a.appointment_id AND r.patient_id = ?
        WHERE a.patient_id = ? 
        AND a.appointment_status = 'completed' 
        ORDER BY a.appointment_date DESC, a.appointment_time DESC
    ");
    $stmt->bind_param('ii', $user_id, $user_id);
    $stmt->execute();
    $completed_appointments_result = $stmt->get_result();
    $stmt->close();
} catch (mysqli_sql_exception $e) {
    $error_message = "Error fetching completed appointments: " . $e->getMessage();
}



$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Completed Appointments - Dental Clinic Management System</title>
    <link rel="stylesheet" type="text/css" href="css/patient.css">
    <style>
          /* General Reset */
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

        /* Mobile Responsiveness */
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
                padding: 8px;
            }

            .appointment-list {
                padding: 10px;
            }

            .appointment-list h2 {
                font-size: 1.25rem;
            }

            
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar">
    <div class="logo">
        <img src="images/lads.png" alt="Dental Clinic Logo">
        <h1>LAD DCAMS</h1>
    </div>
    <div class="hamburger" onclick="toggleMenu()">
        <span></span>
        <span></span>
        <span></span>
    </div>
    <ul class="nav-links">
        <li><a href="patient_dashboard.php" class="active">Dashboard</a></li>
        <li><a href="patient_appointments.php">Appointments</a></li>
        <li><a href="patient_message.php">Messages</a></li>
        <li><a href="patient_profile.php">Profile</a></li>
        <li><a href="logout.php">Logout</a></li>
    </ul>
</nav>

<script>
    function toggleMenu() {
        const navLinks = document.querySelector('.nav-links');
        navLinks.classList.toggle('show');
    }
</script>

<!-- Main Content -->
<main class="main-content">
    <div class="dashboard-container">
        <section class="appointment-list">
            <h2>Completed Appointments</h2>
            <?php if (isset($error_message)): ?>
                <p class="error"><?php echo htmlspecialchars($error_message); ?></p>
            <?php elseif ($completed_appointments_result->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Dentist</th>
                            <th>Notes</th>
                            <th>Review</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $completed_appointments_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['appointment_date']); ?></td>
                                <td><?php echo htmlspecialchars($row['appointment_time']); ?></td>
                                <td><?php echo htmlspecialchars($row['dentist_first_name'] . ' ' . $row['dentist_last_name']); ?></td>
                                <td class="treatment-notes"><?php echo nl2br(htmlspecialchars($row['treatment_notes'] ?: 'N/A')); ?></td>
                                <td>
    <?php if ($row['review_id']): ?>
        <p>Reviewed</p>
    <?php else: ?>
        <a href="patient_reviews.php?appointment_id=<?php echo $row['appointment_id']; ?>">
            <button type="button">Review</button>
        </a>
    <?php endif; ?>
</td>

                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No completed appointments.</p>
            <?php endif; ?>
        </section>
    </div>
</main>

</body>
</html>
