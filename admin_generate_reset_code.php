<?php
session_start();
include 'db_config.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Set the default timezone to the Philippines
date_default_timezone_set('Asia/Manila');

// Load Composer's autoloader
require 'vendor/autoload.php';

// Ensure that only admins can access this page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$message = '';
$username = isset($_GET['username']) ? trim($_GET['username']) : '';
$temp_email = ''; // Temporary email for sending reset code

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $temp_email = trim($_POST['temp_email']); // Get the temporary email

    // Check if the username exists in the users table (make sure it's a patient or dentist, not an admin)
    $stmt = $conn->prepare("SELECT user_id, username, role FROM users WHERE username = ? AND (role = 'patient' OR role = 'dentist')");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // Generate a 6-digit alphanumeric reset code
        function generateResetCode($length = 6) {
            $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
            $charactersLength = strlen($characters);
            $resetCode = '';
            for ($i = 0; $i < $length; $i++) {
                $resetCode .= $characters[random_int(0, $charactersLength - 1)];
            }
            return $resetCode;
        }

        $reset_code = generateResetCode();
        
        // Calculate expiry time (1 hour from now)
        $reset_expiry = date('Y-m-d H:i:s', strtotime('+1 hour')); // Code expires in 1 hour
        $status = 'pending'; // Initial status of the reset request
        $email_sent_time = date('Y-m-d H:i:s'); // Current time for email sent time
        $request_time = date('Y-m-d H:i:s'); // Current time for the request

        // Get the user details (user_id, username, and role)
        $stmt->bind_result($user_id, $username_from_db, $role);
        $stmt->fetch();
        $stmt->close();

        // Insert the reset request into the password_reset_requests table (without 'role' field)
        $stmt_insert = $conn->prepare("INSERT INTO password_reset_requests (username, request_time, status, reset_code, reset_code_expiry, email_sent_time, user_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt_insert->bind_param("sssssss", $username_from_db, $request_time, $status, $reset_code, $reset_expiry, $email_sent_time, $user_id);
        $stmt_insert->execute();
        $stmt_insert->close();

        // Send reset code via email using PHPMailer
        $mail = new PHPMailer(true);

            try {
                // Server settings
                $mail->isSMTP();                                            // Send using SMTP
                 $mail->Host       = 'smtp.gmail.com';                     // Set the SMTP server to send through
                 $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
                 $mail->Username   = 'dcams.official@gmail.com';            // SMTP username
                 $mail->Password   = 'kjdxoxczcbojyhjk';                    // SMTP password
                 $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;       // Enable TLS encryption
                 $mail->Port       = 587;                                    // TCP port to connect to (587 for TLS)
            
                // Recipients
                $mail->setFrom('admin@example.com', 'Admin');
                $mail->addAddress($temp_email);  // Add the recipient's temporary email

            // Content
            $mail->isHTML(false);  // Set email format to plain text
            $mail->Subject = 'Password Reset Code';
            $mail->Body = "Hello, $username \n\nHere is your password reset code: $reset_code\n\nTo reset your password. \n\nThis code will expire in 1 hour.\n\nThank you.";

            // Send the email
            $mail->send();
            $message = "Reset code successfully generated and sent to the temporary email.";
        } catch (Exception $e) {
            $message = "Failed to send email. Mailer Error: {$mail->ErrorInfo}";
        }
    } else {
        $message = "Username not found or the user is not a valid patient or dentist.";
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Generate Password Reset Code</title>
</head>
<body>
    <h1>Generate Password Reset Code</h1>
    <form method="POST" action="">
        <label for="username">Username (Patient or Dentist):</label>
        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>" required>
        <br>
        <label for="temp_email">Temporary Email (To Send Code):</label>
        <input type="email" id="temp_email" name="temp_email" value="<?php echo htmlspecialchars($temp_email, ENT_QUOTES, 'UTF-8'); ?>" required>
        <br>
        <button type="submit">Generate Code</button>
    </form>
    <?php if (!empty($message)) { echo '<p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>'; } ?>
    <a href="password_reset_requests.php">Back to Requests</a>
</body>
</html>
