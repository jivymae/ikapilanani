<?php
session_start();
include 'db_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load Composer's autoloader
require 'vendor/autoload.php';


// Initialize message variable to show feedback to the user
$message = "";  

// PHPMailer function
function sendConfirmationEmail($toEmail, $firstName, $lastName, $username) {
    // Create a new PHPMailer instance
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();                                            // Send using SMTP
         $mail->Host       = 'smtp.gmail.com';                     // Set the SMTP server to send through
         $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
         $mail->Username   = 'dcams.official@gmail.com';            // SMTP username
         $mail->Password   = 'kjdxoxczcbojyhjk';                    // SMTP password
         $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;       // Enable TLS encryption
         $mail->Port       = 587;                                      // TCP port to connect to
    
            //Recipients
            $mail->setFrom('no-reply@yourdomain.com', 'DCAMS');  // Set the sender email and name
            $mail->addAddress($toEmail, "$firstName $lastName");          // Add a recipient
    
            // Content
            $mail->isHTML(true);                                     // Set email format to HTML
            $mail->Subject = 'Welcome to Our Platform!';
            $mail->Body    = "Dear $firstName $lastName,<br><br>"
                             . "Thank you for registering as a patient on our platform.<br>"
                             . "Your username is: <strong>$username</strong><br><br>"
                             . "Best regards,<br>The Team";
    
            // Send email
            $mail->send();
            return true;
        } catch (Exception $e) {
            return false;  // Return false if the email failed to send
        }
    }

function isStrongPassword($password) {
    $lengthValid = strlen($password) >= 8 && strlen($password) <= 12; // Length between 8 and 12
    $upperValid = preg_match('/[A-Z]/', $password);
    $lowerValid = preg_match('/[a-z]/', $password);
    $numberValid = preg_match('/[0-9]/', $password);
    $specialValid = preg_match('/[\W_]/', $password);

    return $lengthValid && $upperValid && $lowerValid && $numberValid && $specialValid;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];

    // Check if passwords match
    if ($password !== $confirm_password) {
        $message = 'Passwords do not match.';
    } else if (strlen($password) < 8) {
        $message = 'Password must be at least 8 characters long.';
    } else if (strlen($password) > 12) {
        $message = 'Password must not exceed 12 characters.';
    } else if (!isStrongPassword($password)) {
        $message = 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.';
    } else {
        // Check if username already exists
        $query = "SELECT username FROM users WHERE username = ?";
        $stmt = $conn->prepare($query);
        if ($stmt === false) {
            $message = 'Database error: ' . $conn->error;
        } else {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $message = 'Username already exists. Please choose a different username.';
            } else {
                // Hash the password before storing it
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Insert user into the database
                $query = "INSERT INTO users (username, password_hash, role, first_name, last_name, email) VALUES (?, ?, 'patient', ?, ?, ?)";
                $stmt = $conn->prepare($query);

                if ($stmt === false) {
                    $message = 'Database error: ' . $conn->error;
                } else {
                    $stmt->bind_param("sssss", $username, $hashed_password, $first_name, $last_name, $email);

                   // Inside your form handling logic, when sending the email
if ($stmt->execute()) {
    // Success message
    $message = "Patient registered successfully!";

    // Send confirmation email using PHPMailer
    if (sendConfirmationEmail($email, $first_name, $last_name, $username)) {
        $message .= " A confirmation email has been sent to $email.";
    } else {
        $message .= " There was an error sending the confirmation email.";
    }
} else {
    $message = "Error: " . $stmt->error;
}

                }
            }

            $stmt->close();
        }
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register Patient</title>
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
            max-width: 450px;
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
        }

        label {
            font-size: 14px;
            color: #555;
            margin-bottom: 5px;
            margin-top: 10px;
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

        .login-link {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: #27C5F5;
            font-size: 14px;
            text-decoration: none;
        }

        .login-link:hover {
            text-decoration: underline;
        }

        /* Error and success message styles */
        .error {
            color: red;
            font-size: 14px;
            text-align: center;
            margin-top: 10px;
        }

        .success {
            color: green;
            font-size: 14px;
            text-align: center;
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

            .login-link {
                font-size: 12px;
            }
        }

    </style>
</head>
<body>
    <div class="container">
        <h1>Register as a Patient</h1>
        <form method="POST" action="">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>
            
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
            
            <label for="confirm_password">Confirm Password:</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
            
            <label for="first_name">First Name:</label>
            <input type="text" id="first_name" name="first_name" required>
            
            <label for="last_name">Last Name:</label>
            <input type="text" id="last_name" name="last_name" required>
            
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
            
            <button type="submit">Register</button>
        </form>

        <!-- Display error or success messages -->
        <?php if (!empty($message)) { 
            echo '<p class="' . (strpos($message, 'successfully') !== false ? 'success' : 'error') . '">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>'; 
        } ?>

        <a href="login.php" class="login-link">Already have an account? Login</a>
    </div>
</body>
</html>
