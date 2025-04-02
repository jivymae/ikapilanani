<?php
include 'login_check.php'; // Ensure user is logged in
include 'db_config.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';

// Check if the user is logged in and has the correct role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'patient') {
    header('Location: pat_unauthorized.php');
    exit();
}

$appointment_id = $_GET['appointment_id'] ?? null;
$appointment_details = [];

// Fetch appointment details including services, cost, and payment method
if ($appointment_id) {
    $patient_id = $_SESSION['user_id']; // Store user ID in a variable
    $stmt = $conn->prepare("
        SELECT a.appointment_id, a.dentist_id, a.appointment_date, a.appointment_time, 
               u.username AS dentist_name, 
               GROUP_CONCAT(s.service_name ORDER BY s.service_name) AS services,
               SUM(s.price) AS total_cost, 
               IFNULL(p.payment_status, 'Not Paid') AS payment_status,
               pm.method_name AS payment_method,
               u.email AS patient_email
        FROM appointments a
        LEFT JOIN users u ON a.dentist_id = u.user_id
        LEFT JOIN appointment_services asr ON a.appointment_id = asr.appointment_id
        LEFT JOIN services s ON asr.service_id = s.service_id
        LEFT JOIN payments p ON a.appointment_id = p.appointment_id
        LEFT JOIN payment_methods pm ON p.method_id = pm.method_id
        WHERE a.appointment_id = ? AND a.patient_id = ? 
        GROUP BY a.appointment_id
    ");
  
    $stmt->bind_param("ii", $appointment_id, $patient_id);  // Use the variable here
    $stmt->execute();
    $stmt->bind_result($appointment_id, $dentist_id, $appointment_date, $appointment_time, $dentist_name, $services, $total_cost, $payment_status, $payment_method, $patient_email);
  
    if ($stmt->fetch()) {
        $appointment_details = [
            'id' => $appointment_id,
            'dentist_name' => $dentist_name,
            'date' => $appointment_date,
            'time' => $appointment_time,
            'services' => $services,
            'total_cost' => $total_cost,
            'payment_status' => $payment_status,
            'payment_method' => $payment_method,
            'patient_email' => $patient_email
        ];
    }
    $stmt->close();

    // Send Email Reminder for Paid Appointments
    if ($payment_status === 'paid') {
        // Calculate the appointment time and check if it's 1 hour away
        $appointment_datetime = new DateTime($appointment_details['date'] . ' ' . $appointment_details['time']);
        $current_datetime = new DateTime();

        // Check if the current time is 1 hour before the appointment
        $interval = $current_datetime->diff($appointment_datetime);
        if ($interval->h == 1 && $interval->days == 0) {
            // Send Email using PHPMailer

            // Initialize PHPMailer
            $mail = new PHPMailer(true);
            try {
                // Server settings
                $mail->isSMTP();
                        $mail->Host       = 'smtp.gmail.com'; // Set the SMTP server to send through
                        $mail->SMTPAuth   = true; // Enable SMTP authentication
                        $mail->Username   = 'dcams.official@gmail.com'; // SMTP username
                        $mail->Password   = 'kjdxoxczcbojyhjk'; // SMTP password
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Enable TLS encryption
                        $mail->Port       = 587; // Use the appropriate port

                // Recipients
                $mail->setFrom('no-reply@example.com', 'Dental Clinic');
                $mail->addAddress($patient_email); // Send to the patient's email

                // Content
                $message = "Reminder: Your appointment with Dr. {$dentist_name} is in 1 hour on {$appointment_date} at {$appointment_time}.\n\nPlease arrive 15 minutes before your scheduled time to complete any necessary paperwork.";

                $mail->isHTML(true);
                $mail->Subject = 'Appointment Reminder';
                $mail->Body    = nl2br($message); // Convert newlines to <br> tags for HTML emails

                // Send the email
                $mail->send();
                $success_message = "Reminder email sent to the patient.";
            } catch (Exception $e) {
                $error_message = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
            }
        }
    }

    // Check if the appointment has already been cancelled by fetching from cancelled_appointments table
    $stmt_check_cancelled = $conn->prepare("SELECT cancel_id FROM cancelled_appointments WHERE appointment_id = ?");
    $stmt_check_cancelled->bind_param("i", $appointment_id);
    $stmt_check_cancelled->execute();
    $stmt_check_cancelled->store_result();
    $is_cancelled = $stmt_check_cancelled->num_rows > 0; // True if the appointment is cancelled
    $stmt_check_cancelled->close();
} else {
    echo "Appointment not found.";
    exit();
}

// Handle appointment cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_appointment'])) {
    // Your cancellation handling code here
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/patient.css">
    <title>Appointment Details</title>
    <script>
        // JavaScript function to toggle cancellation form visibility
        function showCancelForm() {
            document.getElementById("cancel_form").style.display = "block";
            document.getElementById("cancel_button").style.display = "none";
        }
    </script>
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
            gap: 0.5rem;
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

/* Appointment Details Section */
.appointment-details {
    padding: 2rem;
    max-width: 900px;
    margin: 0 auto;
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.appointment-details h2 {
    font-size: 2rem;
    margin-bottom: 1rem;
}

.appointment-details p {
    font-size: 1.1rem;
    margin-bottom: 0.8rem;
}

.error, .success {
    font-size: 1.2rem;
    margin: 1rem 0;
    padding: 1rem;
    border-radius: 5px;
}

.error {
    color: red;
    background-color: #f8d7da;
}

.success {
    color: green;
    background-color: #d4edda;
}

/* Cancel Appointment Section */
.cancel-appointment {
    display: flex;
    justify-content: center; /* Centers content horizontally */
    align-items: center; /* Centers content vertically */
    height: 300px; /* This height is adjustable based on your design */
    margin-top: 0.2rem;
}

#cancel_button {
    padding: 0.8rem 1.5rem;
    background-color: #00bfff;
    color: white;
    border: none;
    font-size: 1.2rem;
    cursor: pointer;
    border-radius: 5px;
    text-align: center;
}

#cancel_form {
    margin-top: 1rem;
    display: none;
    width: 100%;
    text-align: center;
}

#cancel_reason {
    width: 50%;
    padding: 0.8rem;
    border: 1px solid #ccc;
    border-radius: 5px;
    font-size: 1rem;
}

input[type="submit"] {
    background-color: #f44336;
    color: white;
    border: none;
    padding: 0.8rem 1.5rem;
    cursor: pointer;
    font-size: 1rem;
    border-radius: 5px;
}

input[type="submit"]:hover {
    background-color: #e53935;
}

/* Responsive Design */
@media (max-width: 768px) {

 

    .appointment-details {
        padding: 1rem;
    }

    .appointment-details h2 {
        font-size: 1.6rem;
    }

    .appointment-details p {
        font-size: 1rem;
    }

    #cancel_button {
        width: 100%;
        font-size: 1.2rem;
    }

    #cancel_form {
        display: block;
    }
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
        }
    </style>
<body>
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

    <div class="appointment-details">
        <h2>Appointment Details</h2>
        <p><strong>Appointment ID:</strong> <?php echo htmlspecialchars($appointment_details['id']); ?></p>
        <p><strong>Dentist:</strong> <?php echo htmlspecialchars($appointment_details['dentist_name']); ?></p>
        <p><strong>Date:</strong> <?php echo htmlspecialchars($appointment_details['date']); ?></p>
        <p><strong>Time:</strong> <?php echo htmlspecialchars($appointment_details['time']); ?></p>
        <p><strong>Services:</strong> <?php echo htmlspecialchars($appointment_details['services']); ?></p>
        <p><strong>Total Cost:</strong> PHP <?php echo number_format($appointment_details['total_cost'], 2); ?></p>
        <p><strong>Payment Status:</strong> <?php echo htmlspecialchars($appointment_details['payment_status']); ?></p>
        <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($appointment_details['payment_method']); ?></p>

        <?php if ($is_cancelled): ?>
            <p>Your appointment has already been cancelled.</p>
        <?php else: ?>
            <!-- Only show cancel button if the appointment is not already cancelled -->
            <button id="cancel_button" onclick="showCancelForm()">Cancel Appointment</button>
        <?php endif; ?>
    </div>

    <?php if (isset($error_message)): ?>
        <div class="error"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>
    <?php if (isset($success_message)): ?>
        <div class="success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>

    <!-- Only show the cancel form if the payment is 'pending' and method is 'CASH' -->
    <?php if ($appointment_details['payment_status'] === 'pending' && $appointment_details['payment_method'] === 'CASH' && !$is_cancelled): ?>
    <div class="cancel-appointment">
        <div id="cancel_form" style="display: none;">
            <h3>Reason for cancellation</h3>
            <form action="patient_view_appointment_details.php?appointment_id=<?php echo htmlspecialchars($appointment_details['id']); ?>" method="POST">
                <textarea name="cancel_reason" id="cancel_reason" rows="4" required></textarea>
                <br><br>
                <input type="submit" name="cancel_appointment" value="Confirm Cancellation">
            </form>
        </div>
    </div>
    <?php endif; ?>
</body>
</html>
