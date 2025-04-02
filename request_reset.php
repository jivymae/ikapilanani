<?php
session_start();
include 'db_config.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load Composer's autoloader
require 'vendor/autoload.php';

// Set the default timezone to the Philippines
date_default_timezone_set('Asia/Manila');

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);

    // Validate input
    if (empty($username)) {
        $message = "Username is required.";
    } else {
        // Check if the username exists
        $stmt = $conn->prepare("SELECT user_id, email FROM users WHERE username = ? AND role != 'admin'");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($user_id, $user_email);
            $stmt->fetch();

            // Generate a 6-character alphanumeric reset token
            function generateResetToken($length = 6) {
                $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
                $charactersLength = strlen($characters);
                $resetToken = '';
                for ($i = 0; $i < $length; $i++) {
                    $resetToken .= $characters[random_int(0, $charactersLength - 1)];
                }
                return $resetToken;
            }

            $reset_token = generateResetToken();
            $hashed_token = password_hash($reset_token, PASSWORD_BCRYPT); // Hash the reset token
            $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token expires in 1 hour

            // Insert the hashed reset token into the password_resets table
            $stmt = $conn->prepare("INSERT INTO password_resets (user_id, reset_token, expires_at) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $user_id, $hashed_token, $expires_at);
            $stmt->execute();

            // Send reset token via email using PHPMailer
            $mail = new PHPMailer(true); // Passing `true` enables exceptions

            try {
                // Server settings
                $mail->isSMTP();                                            // Send using SMTP
                $mail->Host       = 'smtp.gmail.com';                     // Set the SMTP server to send through
                $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
                $mail->Username   = 'dcams.official@gmail.com';            // SMTP username
                $mail->Password   = 'kjdxoxczcbojyhjk';                    // SMTP password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;       // Enable TLS encryption
                $mail->Port       = 587;                                    // TCP port to connect to

                // Recipients
                $mail->setFrom('admin@example.com', 'Dental Clinic Admin');
                $mail->addAddress($user_email);                              // Add a recipient

                // Content
                $mail->isHTML(false);                                      // Set email format to HTML
                $mail->Subject = 'Password Reset Token';
                $mail->Body    = "Hello,\n\nHere is your password reset token:\n\n $reset_token\n\nThis token will expire in 1 hour.\n\nThank you.";

                $mail->send();
                $message = "Reset token generated and sent to the user's email successfully.";
            } catch (Exception $e) {
                $message = "Failed to send email. Mailer Error: {$mail->ErrorInfo}";
            }
        } else {
            $message = "Username not found.";
        }

        $stmt->close();
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Request Password Reset</title>
    <style>
        /* General reset and box-sizing */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f7fc;
            color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .container {
            background-color: #fff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        h1 {
            text-align: center;
            color: #27C5F5;
            margin-bottom: 20px;
            font-size: 24px;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        label {
            font-size: 14px;
            color: #555;
            margin-bottom: 5px;
        }

        input {
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px;
            background-color: #f9f9f9;
            transition: all 0.3s ease;
        }

        input:focus {
            outline: none;
            border-color: #27C5F5;
            background-color: #e9f7e8;
        }

        button {
            padding: 12px;
            background-color: #27C5F5;
            color: #fff;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        button:hover {
            background-color: #0D9BC6;
        }

        a {
            display: block;
            margin-top: 15px;
            text-align: center;
            font-size: 14px;
            color: #27C5F5;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        /* Success message styling */
        p {
            color: green;
            font-size: 14px;
            margin-top: 10px;
        }

        /* Responsive styling */
        @media (max-width: 1024px) {  /* Tablets and smaller laptops */
            .container {
                padding: 30px;
                max-width: 90%;
            }

            h1 {
                font-size: 22px;
            }

            input, button {
                font-size: 14px;
                padding: 10px;
            }

            label {
                font-size: 13px;
            }
        }

        @media (max-width: 768px) {  /* Small tablets and below */
            body {
                padding: 10px;
            }

            .container {
                padding: 20px;
                max-width: 95%;
            }

            h1 {
                font-size: 20px;
            }

            input, button {
                font-size: 14px;
                padding: 10px;
            }

            label {
                font-size: 12px;
            }
        }

        @media (max-width: 480px) {  /* Mobile phones */
            .container {
                padding: 15px;
                max-width: 100%;
            }

            h1 {
                font-size: 18px;
            }

            input, button {
                font-size: 14px;
                padding: 12px;
            }

            label {
                font-size: 12px;
            }

            a {
                font-size: 12px;
            }
        }

    </style>
</head>
<body>
    <div class="container">
        <h1>Request Password Reset</h1>
        <form method="POST" action="">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>
            <button type="submit">Request Reset Token</button>
        </form>

        <!-- Display success or error message -->
        <?php if (!empty($message)) { 
            echo '<p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>'; 
        } ?>

        <a href="login.php">Back to Login</a>
    </div>
</body>
</html>
