<?php
include 'login_check.php'; // Ensure user is logged in
include 'db_config.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use \Mpdf\Mpdf; // Import the mPDF class


require 'vendor/autoload.php'; // Ensure PHPMailer and mPDF are autoloaded

// Check if the appointment data is available in the session
if (!isset($_SESSION['appointment_data'])) {
    header('Location: patient_appointments.php');
    exit();
}

// Retrieve appointment data from session
$appointment_data = $_SESSION['appointment_data'];

// Fetch the dentist details (First and Last Name)
$dentist_id = $appointment_data['dentist_id'];
$stmt = $conn->prepare("SELECT first_name, last_name, email FROM users WHERE user_id = ? AND role = 'dentist'");
$stmt->bind_param('i', $dentist_id);
$stmt->execute();
$stmt->bind_result($dentist_first_name, $dentist_last_name, $dentist_email);
$stmt->fetch();
$stmt->close();

// Concatenate dentist's first and last name
$dentist_name = $dentist_first_name . ' ' . $dentist_last_name;

// Fetch selected services and calculate total amount
$total_payment_amount = 0.00;
$services_details = [];
foreach ($appointment_data['services'] as $service_id) {
    $stmt_service = $conn->prepare("SELECT service_name, price FROM services WHERE service_id = ?");
    $stmt_service->bind_param('i', $service_id);
    $stmt_service->execute();
    $stmt_service->bind_result($service_name, $service_price);
    if ($stmt_service->fetch()) {
        $total_payment_amount += $service_price;
        $services_details[] = [
            'name' => $service_name,
            'price' => $service_price
        ];
    }
    $stmt_service->close();
}

// Fetch the payment method details
$payment_method_id = $appointment_data['payment_method'];
$payment_method_name = 'Not specified'; 
$payment_method_description = 'No description available'; 

if ($payment_method_id) {
    $stmt_payment_method = $conn->prepare("SELECT method_name, description FROM payment_methods WHERE method_id = ?");
    $stmt_payment_method->bind_param('i', $payment_method_id);
    $stmt_payment_method->execute();
    $stmt_payment_method->bind_result($payment_method_name, $payment_method_description);
    $stmt_payment_method->fetch();
    $stmt_payment_method->close();
}

// If the form is submitted to confirm the appointment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_appointment'])) {
    $transaction_number = $_POST['transaction_number'];

    // Handle file upload for the receipt (only if the payment method is not 'cash')
    $receipt_path = '';  
    if ($payment_method_id != 1 && isset($_FILES['receipt']) && $_FILES['receipt']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = 'receipts/';
        $file_name = uniqid() . "_" . basename($_FILES['receipt']['name']);
        $receipt_path = $upload_dir . $file_name;

        // Move the uploaded file to the specified directory
        if (!move_uploaded_file($_FILES['receipt']['tmp_name'], $receipt_path)) {
            die('Error uploading the receipt file.');
        }
    }

    // Ensure $appointment_data['appointment_time'] is a string
    $appointment_time = is_array($appointment_data['appointment_time']) 
        ? $appointment_data['appointment_time'][0] 
        : $appointment_data['appointment_time']; 

    // Insert appointment data into the 'appointments' table
    $stmt_appointment = $conn->prepare("INSERT INTO appointments (patient_id, dentist_id, appointment_date, appointment_time) 
                                        VALUES (?, ?, ?, ?)");
    $stmt_appointment->bind_param('iiss', $user_id, $appointment_data['dentist_id'], $appointment_data['appointment_date'], $appointment_time);
    $stmt_appointment->execute();
    $appointment_id = $stmt_appointment->insert_id;
    $stmt_appointment->close();

    // Insert payment record into the 'payments' table
    $payment_status = 'pending'; 
    $pstat_id = 1; 

    $stmt_payment = $conn->prepare("INSERT INTO payments 
        (appointment_id, patient_id, total_amount, payment_status, method_id, transaction_number, receipt_path) 
        VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt_payment->bind_param('iidsiss', 
        $appointment_id, 
        $user_id, 
        $total_payment_amount, 
        $payment_status,  
        $payment_method_id, 
        $transaction_number, 
        $receipt_path
    );
    $stmt_payment->execute();
    $stmt_payment->close();

    // Insert the selected services into the 'appointment_services' table
    foreach ($appointment_data['services'] as $service_id) {
        $stmt_service_insert = $conn->prepare("INSERT INTO appointment_services (appointment_id, service_id) VALUES (?, ?)");
        $stmt_service_insert->bind_param('ii', $appointment_id, $service_id);
        $stmt_service_insert->execute();
        $stmt_service_insert->close();
    }

    // Clear session data
    unset($_SESSION['appointment_data']);

    // Set the success flag
    $_SESSION['appointment_success'] = true;  

    // Send email notifications with the PDF
    sendAppointmentEmails($user_id, $dentist_name, $dentist_email, $appointment_data, $total_payment_amount, $payment_method_name, $services_details);

    // Redirect to success page
    header('Location: patient_app_success.php');
    exit();
}

// Function to send emails to user and dentist
function sendAppointmentEmails($patient_id, $dentist_name, $dentist_email, $appointment_data, $total_payment_amount, $payment_method_name, $services_details) {
    global $conn;

    // Fetch patient's full name (First and Last Name)
    $stmt_patient = $conn->prepare("SELECT first_name, last_name FROM users WHERE user_id = ? AND role = 'patient'");
    $stmt_patient->bind_param('i', $patient_id);
    $stmt_patient->execute();
    $stmt_patient->bind_result($patient_first_name, $patient_last_name);
    $stmt_patient->fetch();
    $stmt_patient->close();

    $patient_name = htmlspecialchars($patient_first_name . ' ' . $patient_last_name);

    // Fetch patient's email
    $stmt_patient_email = $conn->prepare("SELECT email FROM users WHERE user_id = ?");
    $stmt_patient_email->bind_param('i', $patient_id);
    $stmt_patient_email->execute();
    $stmt_patient_email->bind_result($patient_email);
    $stmt_patient_email->fetch();
    $stmt_patient_email->close();

    // Prepare the email content
    $appointment_date = htmlspecialchars($appointment_data['appointment_date'] ?? 'N/A');
    $appointment_time = htmlspecialchars($appointment_data['appointment_time'][0] ?? 'N/A');
    $service_list = '';
    foreach ($services_details as $service) {
        $service_list .= $service['name'] . " - PHP " . number_format($service['price'], 2) . "\n";
    }

    $message = "
    <html>
    <head>
        <title>Appointment Confirmation</title>
    </head>
    <body>
        <p><strong>Dear $patient_name,</strong></p>
        <p>Your appointment has been successfully booked with Dr. $dentist_name.</p>
        <p><strong>Appointment Details:</strong></p>
        <p>Date: $appointment_date</p>
        <p>Time: $appointment_time</p>
        <p><strong>Services:</strong></p>
        <pre>$service_list</pre>
        <p><strong>Total Amount:</strong> PHP " . number_format($total_payment_amount, 2) . "</p>
        <p><strong>Payment Method:</strong> $payment_method_name</p>
        <p>Thank you for booking with us!</p>
    </body>
    </html>";

    // Initialize mPDF
    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'orientation' => 'P',
        'margin_left' => 15,
        'margin_right' => 15,
        'margin_top' => 20,
        'margin_bottom' => 20,
        'margin_header' => 10,
        'margin_footer' => 10
    ]);

    $logo_path = 'images/lads.png'; 
    // Define the PDF content
    $pdfContent = "
   
    <div style='text-align: center;'>
        <h1>Appointment Confirmation</h1>
        <h2>  <img src='$logo_path' alt='Clinic Logo' style='width: 60px; height: auto;'>LAD DENTAL CLINIC</h2>
        <p> Vamenta Blvd. Carmen, Cagayan de Oro City <br>
        Misamis Oriental, 9000, Philippines
        </p>

    </div>

    <hr>

    <h3>Patient Details</h3>
    <p><strong>Name:</strong> $patient_name</p>

    <h3>Appointment Details</h3>
    <p><strong>Dentist:</strong> $dentist_name</p>
    <p><strong>Appointment Date:</strong> $appointment_date</p>
    <p><strong>Appointment Time:</strong> $appointment_time</p>

    <h3>Selected Services</h3>
    <ul>";

    foreach ($services_details as $service) {
        $pdfContent .= "<li>" . htmlspecialchars($service['name']) . " - PHP " . number_format($service['price'], 2) . "</li>";
    }

    $pdfContent .= "</ul>";

    // Output PDF
    $mpdf->WriteHTML($pdfContent);
    $pdf_output = $mpdf->Output('', 'S'); 

    // Send email to patient
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Set your mail server
        $mail->SMTPAuth = true;
        $mail->Username   = 'dcams.official@gmail.com'; // SMTP username
        $mail->Password   = 'kjdxoxczcbojyhjk'; // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('clinic@example.com', 'Clinic Name');
        $mail->addAddress($patient_email, $patient_name);
        $mail->addReplyTo('clinic@example.com', 'Clinic Name');
        $mail->Subject = 'Appointment Confirmation';
        $mail->Body    = $message;
        $mail->AltBody = strip_tags($message);

        // Attach the PDF
        $mail->addStringAttachment($pdf_output, 'Appointment_Confirmation.pdf');

        $mail->send();
    } catch (Exception $e) {
        echo "Mailer Error: " . $mail->ErrorInfo;
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="css/patient.css">
    <title>Appointment Summary - Dental Clinic Management System</title>
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


/* Navbar */


.appointment-summary {
    background-color: #ffffff;
    padding: 30px;
    max-width: 700px;
    width: 100%;
    margin: 20px auto;
    border-radius: 15px;
    box-shadow: 0px 6px 15px rgba(0, 0, 0, 0.2);
    color: #333;
}

.appointment-summary h2 {
    text-align: center;
    color: #00bfff;
    font-size: 2rem;
    margin-bottom: 20px;
    border-bottom: 2px solid #00bfff;
    display: inline-block;
    padding-bottom: 10px;
}

.appointment-summary p,
.appointment-summary ul {
    font-size: 1rem;
    line-height: 1.6;
    margin: 15px 0;
}

.appointment-summary ul {
    list-style-type: none;
    padding: 0;
}

.appointment-summary ul li {
    background-color: #f9f9f9;
    border: 1px solid #ddd;
    padding: 10px;
    border-radius: 5px;
    margin-bottom: 10px;
    display: flex;
    justify-content: space-between;
    font-weight: bold;
}

.appointment-summary ul li span.price {
    color: #28a745;
    font-weight: normal;
}

.appointment-summary input[type="text"],
.appointment-summary input[type="file"] {
    width: 100%;
    padding: 12px;
    font-size: 1rem;
    border: 1px solid #ccc;
    border-radius: 5px;
    margin-bottom: 20px;
    box-sizing: border-box;
}

.appointment-summary input[type="submit"] {
    width: 100%;
    background-color: #00bfff;
    color: white;
    border: none;
    margin-bottom: 10px;
    cursor: pointer;
    font-size: 1.1rem;
    padding: 12px;
    border-radius: 5px;
    transition: all 0.3s ease;
}

.appointment-summary input[type="submit"]:hover {
    background-color: #0056b3;
    transform: scale(1.05);
}

.appointment-summary input[type="submit"]:active {
    background-color: #004085;
    transform: scale(1);
}

#transaction-section,
#receipt-section {
    margin-bottom: 20px;
}

#transaction-section label,
#receipt-section label {
    display: block;
    font-weight: bold;
    margin-bottom: 5px;
    color: #333;
}

/* Responsive Design */
@media (max-width: 768px) {
    .appointment-summary {
        padding: 20px;
        width: 90%;
    }

    .appointment-summary h2 {
        font-size: 1.5rem;
    }

    .appointment-summary p,
    .appointment-summary ul li {
        font-size: 0.9rem;
    }
}

@media (max-width: 480px) {
    .appointment-summary {
        padding: 15px;
    }

    .appointment-summary h2 {
        font-size: 1.3rem;
    }

    .appointment-summary p,
    .appointment-summary ul li {
        font-size: 0.8rem;
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
            <img src="images/cometaicon.png" alt="Dental Clinic Logo">
            <h1>LAD DCAMS</h1>
        </div>
        <div class="hamburger" onclick="toggleMenu()">
            <span></span>
            <span></span>
            <span></span>
        </div>
        <ul class="nav-links">
            <li><a href="patient_dashboard.php">Dashboard</a></li>
            <li><a href="patient_appointments.php"  >Appointments</a></li>
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

    <div class="appointment-summary">
    <h2>Appointment Summary</h2>

    <p><strong>Dentist:</strong> <?php echo htmlspecialchars($dentist_name ?? 'Not specified'); ?></p>
    <p><strong>Appointment Date:</strong> <?php echo htmlspecialchars($appointment_data['appointment_date'] ?? 'N/A'); ?></p>
    <p><strong>Appointment Time:</strong> 
    <?php 
        // Handle the case where 'appointment_time' might be an array
        $appointment_time = is_array($appointment_data['appointment_time']) 
            ? $appointment_data['appointment_time'][0] 
            : $appointment_data['appointment_time'];
        
        echo htmlspecialchars($appointment_time ?? 'N/A');
    ?>
</p>

    <h3>Selected Services:</h3>
    <ul>
        <?php foreach ($services_details as $service): ?>
            <li><?php echo htmlspecialchars($service['name']) . " - PHP " . number_format($service['price'], 2); ?></li>
        <?php endforeach; ?>
    </ul>

    <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($payment_method_name ?? 'Not specified'); ?></p>
    <p><strong>Payment Method Description:</strong> <?php echo htmlspecialchars($payment_method_description ?? 'No description available'); ?></p>
    <p><strong>Total Amount:</strong> PHP <?php echo number_format($total_payment_amount, 2); ?></p>

    <!-- Hidden payment method value to use in JavaScript -->
    <input type="hidden" id="payment_method_id" value="<?php echo $payment_method_id; ?>">

    <!-- Form to confirm or modify appointment -->
    <form action="patient_appointment_summary.php" method="post" enctype="multipart/form-data">
        <!-- Hidden input to submit the book_appointment action -->
        <input type="hidden" name="book_appointment" value="1">

        <!-- Transaction Number (conditionally displayed) -->
        <div id="transaction-section">
            <label for="transaction_number">Transaction Number:</label>
            <input type="text" name="transaction_number" id="transaction_number" required>
        </div>

        <!-- Receipt Upload (conditionally displayed) -->
        <div id="receipt-section">
            <label for="receipt">Upload Receipt:</label>
            <input type="file" name="receipt" id="receipt" accept="image/*">
        </div>

        <br>
        <input type="submit" value="Confirm Appointment">
    </form>

    <script>
        // Wait for the DOM to be loaded before running the script
        document.addEventListener('DOMContentLoaded', function() {
            const paymentMethodId = document.getElementById('payment_method_id').value;
            const transactionSection = document.getElementById('transaction-section');
            const receiptSection = document.getElementById('receipt-section');
            const transactionNumberInput = document.getElementById('transaction_number');

            // If the payment method is 'Cash' (payment_method_id == 2), hide transaction number and receipt
            if (paymentMethodId == '2') {
                transactionSection.style.display = 'none';
                receiptSection.style.display = 'none';
                transactionNumberInput.removeAttribute('required'); // Remove required validation for cash
            } else {
                // Otherwise, show the transaction and receipt sections
                transactionSection.style.display = 'block';
                receiptSection.style.display = 'block';
                transactionNumberInput.setAttribute('required', 'required'); // Ensure it's required for non-cash
            }
        });
    </script>

    <!-- Go back to modify the appointment -->
    <form action="patient_appointments.php" method="post">
        <input type="submit" value="Go Back and Modify">
    </form>
</div>
</body>
</html>
