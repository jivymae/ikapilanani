<?php
require 'vendor/autoload.php'; // Load Composer's autoloader

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start(); // Start the session

// Check if user is logged in (ensure the session has user_id)
if (!isset($_SESSION['user_id'])) {
    echo 'User is not logged in.';
    exit; // Stop further processing if user is not logged in
}

$user_id = $_SESSION['user_id']; // Get the logged-in user's ID

// Database connection (adjust to your own db config)
include 'db_config.php'; // Include your database connection

// Query to get the user's email from the 'users' table
$stmt = $conn->prepare("SELECT email FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    echo 'User not found in database.';
    exit; // Stop further processing if user is not found in the database
}

$email = $user['email']; // Patient's email from the database
$username = "Patient"; // You can change this to the user's name if stored in the DB

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['emergency_request'])) {
        // Send an emergency email

        // Email settings
        $mail = new PHPMailer(true);
        try {
            // Server settings
            $mail->isSMTP();                                           // Send using SMTP
            $mail->Host       = 'smtp.gmail.com';                       // Set the SMTP server to send through
            $mail->SMTPAuth   = true;                                    // Enable SMTP authentication
            $mail->Username   = 'dcams.official@gmail.com';              // SMTP username
            $mail->Password   = 'kjdxoxczcbojyhjk';                      // SMTP password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;          // Enable TLS encryption
            $mail->Port       = 587;                                    // TCP port to connect to (587 for TLS)

            // Recipients
            $mail->setFrom('no-reply@yourclinic.com', 'Dental Clinic');
            $mail->addAddress($email, $username);                        // Patient's email address

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Emergency Appointment Request';
            $mail->Body    = 'Dear ' . htmlspecialchars($username) . ',<br><br>' . 
                             'We have received your emergency appointment request. Please <a href="http://localhost/dcams/patient_emergency_case">click here</a> to complete your request and schedule your appointment.<br><br>' . 
                             'Best regards,<br>Dental Clinic';

            // Send the email
            if ($mail->send()) {
                echo 'Emergency request has been sent!';
            } else {
                echo 'There was an error sending the request.';
            }
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    }
}
?>
