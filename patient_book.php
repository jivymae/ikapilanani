<?php
include 'login_check.php'; // Ensure user is logged in
include 'db_config.php';

// Get the user ID from the session
$user_id = $_SESSION['user_id'];

// Updated query to fetch appointments with statuses that are not completed, cancelled, or no-show
$query = "
    SELECT a.appointment_id, a.appointment_date, a.appointment_time, p.payment_status, u.username AS dentist_name, a.appointment_status
    FROM appointments a
    JOIN users u ON a.dentist_id = u.user_id
    LEFT JOIN payments p ON a.appointment_id = p.appointment_id
    WHERE a.patient_id = ? AND a.appointment_status NOT IN ('completed', 'cancelled', 'no-show')
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$appointments = [];

while ($row = $result->fetch_assoc()) {
    $appointments[] = $row;
}

$stmt->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/patient.css">
    <title>Your Appointments</title>
</head>
<style>
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
                background-color: #00bfff;
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
        <li><a href="patient_dashboard.php">Dashboard</a></li>
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

    <section class="appointment-list">
    <h2>Your Appointments</h2>
    <table>
        <thead>
            <tr>
                <th>Appointment ID</th>
                <th>Date</th>
                <th>Time</th>
                <th>Payment Status</th>
                <th>Send Appointment</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($appointments)): ?>
                <tr>
                    <td colspan="6">No appointments booked.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($appointments as $appointment): ?>
                    <tr>
                    <td>
    <?php if ($appointment['appointment_status'] === 'completed'): ?>
        <!-- Hide the Appointment ID if status is completed -->
        <span style="color: #ccc;">Completed Appointment</span>
    <?php else: ?>
        <a href="patient_reschedule.php?appointment_id=<?php echo htmlspecialchars($appointment['appointment_id']); ?>">
            <?php echo htmlspecialchars($appointment['appointment_id']); ?>
        </a>
    <?php endif; ?>
</td>

                        <td><?php echo htmlspecialchars($appointment['appointment_date']); ?></td>
                        <td><?php echo htmlspecialchars($appointment['appointment_time']); ?></td>
                        <td><?php echo htmlspecialchars($appointment['payment_status']); ?></td>
                        
                        <td>
                            <form action="send_appointment.php" method="post">
                                <input type="hidden" name="appointment_id" value="<?php echo htmlspecialchars($appointment['appointment_id']); ?>">

                                <?php if ($appointment['payment_status'] === 'cash'): ?>
                                    <!-- Disable the button if payment method is 'cash' -->
                                    <button type="submit" name="send_appointment" disabled style="background-color: #ccc; cursor: not-allowed;">Send Appointment (Cash Payment)</button>
                                <?php else: ?>
                                    <!-- Enable the button for other payment methods -->
                                    <button type="submit" name="send_appointment">Send Payment Receipt</button>
                                <?php endif; ?>
                            </form>
                        </td>

                        <!-- Link to View Details -->
                        <td>
                            <a href="patient_view_appointment_details.php?appointment_id=<?php echo htmlspecialchars($appointment['appointment_id']); ?>">View Details</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</section>

</body>
</html>
