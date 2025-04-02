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

// Fetch upcoming paid appointments excluding cancelled and completed ones
try {
    $stmt = $conn->prepare("
        SELECT 
            a.appointment_date, 
            a.appointment_time, 
            u.first_name AS dentist_first_name, 
            u.last_name AS dentist_last_name
        FROM appointments a
        JOIN users u ON a.dentist_id = u.user_id
        LEFT JOIN payments p ON a.appointment_id = p.appointment_id
        LEFT JOIN cancelled_appointments ca ON a.appointment_id = ca.appointment_id
        WHERE a.patient_id = ? 
        AND a.appointment_date >= CURDATE()
        AND p.payment_status = 'paid' -- Filter for paid appointments only
        AND ca.appointment_id IS NULL -- Exclude cancelled appointments (those in the cancelled_appointments table)
        AND a.appointment_status <> 'completed' -- Exclude completed appointments
        AND a.appointment_status <> 'cancelled' -- Exclude cancelled appointments by status
        ORDER BY a.appointment_date, a.appointment_time
    ");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $appointments_result = $stmt->get_result();
    $stmt->close();
} catch (mysqli_sql_exception $e) {
    $error_message = "Error fetching appointments: " . $e->getMessage();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard - Dental Clinic Management System</title>
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
        /* Main Content */
.main-content {
    padding: 2rem 1rem;
    max-width: 1200px;
    margin: 0 auto;
}

.dashboard-container {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.profile-info {
    background-color: #fff;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.profile-info h1 {
    margin-bottom: 1rem;
    font-size: 1.8rem;
    color: #007bff;
}

.profile-info p {
    margin-bottom: 0.5rem;
    font-size: 1rem;
}

.appointments {
    background-color: #fff;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.appointments h2 {
    margin-bottom: 1rem;
    font-size: 1.5rem;
    color: #007bff;
}

.appointments table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 1rem;
}

.appointments table th, .appointments table td {
    border: 1px solid #ddd;
    padding: 0.8rem;
    text-align: left;
}

.appointments table th {
    background-color: #007bff;
    color: #fff;
}

.appointments table tr:nth-child(even) {
    background-color: #f9f9f9;
}

.error {
    color: red;
    font-weight: bold;
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
</head>
<body>
    <!-- Navigation Bar -->
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

    <!-- Main Content -->
    <main class="main-content">
        <div class="dashboard-container">
            <section class="profile-info">
                <h1>Welcome, <?php echo htmlspecialchars($first_name) . ' ' . htmlspecialchars($last_name); ?></h1>
                <p><strong>Username:</strong> <?php echo htmlspecialchars($username); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></p>
            </section>

            <section class="appointments">
                <h2>Upcoming Paid Appointments</h2>
                <?php if (isset($error_message)): ?>
                    <p class="error"><?php echo htmlspecialchars($error_message); ?></p>
                <?php elseif ($appointments_result->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Dentist</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $appointments_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['appointment_date']); ?></td>
                                    <td><?php echo htmlspecialchars($row['appointment_time']); ?></td>
                                    <td><?php echo htmlspecialchars($row['dentist_first_name'] . ' ' . $row['dentist_last_name']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No upcoming paid appointments.</p>
                <?php endif; ?>
            </section>
        </div>
    </main>
    <script>
        function toggleMenu() {
            const navLinks = document.querySelector('.nav-links');
            navLinks.classList.toggle('show');
        }
    </script>
</body>
</html>
