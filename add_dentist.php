<?php
include 'login_check.php'; // Ensure user is logged in
include 'db_config.php'; // Include your database configuration
include 'admin_check.php'; // Include the admin check helper
require 'vendor/autoload.php'; // Load Composer's autoloader

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Check if the page is allowed
$page = basename($_SERVER['PHP_SELF']);
if (!isAdminPage($page)) {
    header('Location: unauthorized.php');
    exit();
}

function generateRandomPassword($length = 6) {
    $upper = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $lower = 'abcdefghijklmnopqrstuvwxyz';
    $numbers = '0123456789';
    $special = '@'; // Only including @ for special character

    $password = '';
    $password .= $upper[random_int(0, strlen($upper) - 1)];
    $password .= $lower[random_int(0, strlen($lower) - 1)];
    $password .= $numbers[random_int(0, strlen($numbers) - 1)];
    $password .= $special;

    $allCharacters = $upper . $lower . $numbers . $special;
    for ($i = 4; $i < $length; $i++) {
        $password .= $allCharacters[random_int(0, strlen($allCharacters) - 1)];
    }

    return str_shuffle($password);
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $license_number = trim($_POST['license_number']);
    $emergency_contact = trim($_POST['emergency_contact']);
    $gender = trim($_POST['gender']);
    $availability = isset($_POST['availability']) ? $_POST['availability'] : [];

    if (empty($username) || empty($email) || empty($first_name) || empty($last_name) || empty($license_number) || empty($emergency_contact) || empty($gender)) {
        $message = "Error: All fields are required.";
    } else {
        $specialization_ids = $_POST['specialization'] ?? [];
        $service_ids = $_POST['services'] ?? [];

        $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $stmt->bind_result($user_exists);
        $stmt->fetch();
        $stmt->close();

        if ($user_exists > 0) {
            $message = "Error: The username '$username' already exists. Please choose a different username.";
        } else {
            $password = generateRandomPassword();
            $password_hash = password_hash($password, PASSWORD_BCRYPT);

            $stmt = $conn->prepare("INSERT INTO users (username, password_hash, role, email, first_name, last_name, gender, created_at) VALUES (?, ?, 'dentist', ?, ?, ?, ?, NOW())");
            $stmt->bind_param('ssssss', $username, $password_hash, $email, $first_name, $last_name, $gender);

            if ($stmt->execute()) {
                $user_id = $stmt->insert_id;

                $stmt = $conn->prepare("INSERT INTO dentists (license_number, emergency_contact, updated_at, user_id) VALUES (?, ?, NOW(), ?)");
                $stmt->bind_param('sss', $license_number, $emergency_contact, $user_id);

                if ($stmt->execute()) {
                    $conn->begin_transaction();

                    try {
                        // Insert specializations
                        foreach ($specialization_ids as $spec_id) {
                            if (!empty($spec_id)) {
                                $stmt = $conn->prepare("INSERT INTO dentist_specializations (user_id, spec_id, created_at) VALUES (?, ?, NOW())");
                                $stmt->bind_param('ii', $user_id, $spec_id); // Use user_id instead of dentist_id
                                $stmt->execute();
                            }
                        }

                        // Insert services
                        foreach ($service_ids as $service_id) {
                            if (!empty($service_id)) {
                                $stmt = $conn->prepare("INSERT INTO dentist_services (user_id, service_id) VALUES (?, ?)");
                                $stmt->bind_param('ii', $user_id, $service_id); // Use user_id instead of dentist_id
                                $stmt->execute();
                            }
                        }

                        // Insert availability
                        foreach ($availability as $day) {
                            $stmt = $conn->prepare("INSERT INTO dentist_availability (user_id, day) VALUES (?, ?)");
                            $stmt->bind_param('is', $user_id, $day); // Use user_id instead of dentist_id
                            $stmt->execute();
                        }

                        $conn->commit();

                        $mail = new PHPMailer(true);
                        try {
                            $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'dcams.official@gmail.com';
        $mail->Password   = 'kjdxoxczcbojyhjk';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

                            $mail->setFrom('no-reply@yourclinic.com', 'Dental Clinic');
                            $mail->addAddress($email, $first_name);
                            $mail->isHTML(false);
                            $mail->Subject = 'Your New Account Password';
                            $mail->Body = "Hello $first_name,\n\nYour account has been created. Here are your login details:\n\nUsername: $username\nPassword: $password\n\nPlease change your password after your first login.";

                            $mail->send();
                            $message = "Dentist added successfully! An email with the password has been sent.";
                        } catch (Exception $e) {
                            $message = "Dentist added successfully, but the email could not be sent. Mailer Error: {$mail->ErrorInfo}";
                        }
                    } catch (Exception $e) {
                        $conn->rollback();
                        $message = "Error occurred while adding specializations or services: " . $e->getMessage();
                    }
                } else {
                    $message = "Error inserting dentist: " . $stmt->error;
                }
            } else {
                $message = "Error: " . $stmt->error;
            }
        }
    }
}

// Fetch specializations
$specializations = [];
$stmt = $conn->prepare("SELECT spec_id, spec_name FROM specializations");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $specializations[] = $row;
}
$stmt->close();

// Fetch services
$services = [];
$stmt = $conn->prepare("SELECT s.service_id, s.service_name, ss.spec_id FROM services s JOIN service_specialization ss ON s.service_id = ss.service_id");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $services[] = $row;
}
$stmt->close();

$conn->close();
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Dentist - Dental Clinic Management System</title>
    <link rel="stylesheet" type="text/css" href="css/style.css">
    <style>
/* General Styles */






/* Main Content Styles */


.main-content h1 {
    color: #2c3e50;
    margin-bottom: 30px;
    padding-bottom: 10px;
    border-bottom: 2px solid #1abc9c;
}

.alert {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 4px;
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

/* Form Styles */
form {
    max-width: 800px;
    margin: 0 auto;
    background: white;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 0 20px rgba(0,0,0,0.05);
}

label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #2c3e50;
}

input[type="text"],
input[type="email"],
input[type="password"],
select {
    width: 100%;
    padding: 12px;
    margin-bottom: 20px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 16px;
    transition: border 0.3s;
}

input[type="text"]:focus,
input[type="email"]:focus,
input[type="password"]:focus,
select:focus {
    border-color: #1abc9c;
    outline: none;
}

/* Checkbox Styles */
#specialization,
#services {
    margin-bottom: 20px;
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
}

#specialization label,
#services label {
    display: flex;
    align-items: center;
    padding: 8px 15px;
    background-color: #f8f9fa;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.3s;
    font-weight: normal;
}

#specialization label:hover,
#services label:hover {
    background-color: #e9ecef;
}

#specialization input[type="checkbox"],
#services input[type="checkbox"] {
    margin-right: 8px;
}

/* Availability Checkboxes */
label[for="availability"] {
    margin-bottom: 15px;
}

label > input[type="checkbox"] {
    margin-right: 5px;
}

/* Button Styles */
button[type="submit"] {
    background-color: #1abc9c;
    color: white;
    border: none;
    padding: 12px 25px;
    font-size: 16px;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.3s;
    margin-top: 20px;
    width: 100%;
}

button[type="submit"]:hover {
    background-color: #16a085;
}

/* Responsive Design */
@media (max-width: 768px) {
    .sidebar {
        width: 100%;
        height: auto;
        position: relative;
    }
    
    .main-content {
        margin-left: 0;
    }
    
    form {
        padding: 20px;
    }
}
</style>
</head>
<body>
    
<aside class="sidebar">
        <h2 class="logo">
            <img src="images/lads.png" alt="Dental Clinic Logo">
            <h1>LAD DCAMS</h1>
        </h2>
        <ul>
            <li><a href="admin_dashboard.php">Dashboard</a></li>
            <li><a href="patients.php">Patients</a></li>
            <li><a href="dentists.php">Dentists</a></li>
            <li><a href="appointments.php">Appointments</a></li>
            <li><a href="admin_add_services.php">Add Service</a></li>
            <li><a href="reports.php">Reports</a></li>
            <li><a href="admin_settings.php">Settings</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </aside>
</div>
        <main class="main-content">
            <h1>Add Dentist</h1>
            <?php if ($message): ?>
                <div class="alert"><?php echo $message; ?></div>
            <?php endif; ?>
            <form method="POST" enctype="multipart/form-data">
                <label for="username">Username:</label>
                <input type="text" name="username" required>

                <label for="email">Email:</label>
                <input type="email" name="email" required>

                <label for="first_name">First Name:</label>
                <input type="text" name="first_name" required>

                <label for="last_name">Last Name:</label>
                <input type="text" name="last_name" required>

                <label for="license_number">License Number:</label>
                <input type="text" name="license_number" required>

                <label for="emergency_contact">Emergency Contact:</label>
                <input type="text" name="emergency_contact" required>

              

                <label for="gender">Gender:</label>
                <select name="gender" required>
                    <option value="">Select Gender</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                </select>

              
                <label for="specialization">Specializations:</label>
                <div id="specialization">
                    <?php foreach ($specializations as $spec): ?>
                        <label>
                            <input type="checkbox" name="specialization[]" value="<?php echo $spec['spec_id']; ?>">
                            <?php echo $spec['spec_name']; ?>
                        </label>
                    <?php endforeach; ?>
                </div>

                <label for="services">Services:</label>
                <div id="services">
                    <p>Select specializations to load services.</p>
                </div>

                <label>Availability:</label><br>
                <label><input type="checkbox" name="availability[]" value="M"> Monday</label>
                <label><input type="checkbox" name="availability[]" value="T"> Tuesday</label>
                <label><input type="checkbox" name="availability[]" value="W"> Wednesday</label>
                <label><input type="checkbox" name="availability[]" value="Th"> Thursday</label>
                <label><input type="checkbox" name="availability[]" value="F"> Friday</label>
                <label><input type="checkbox" name="availability[]" value="Sat"> Saturday</label>
                <label><input type="checkbox" name="availability[]" value="Sun"> Sunday</label>
                <button type="submit">Add Dentist</button>
            </form>
        </main>
    </div>

    <script>
 

// Dynamic loading of services based on specialization selection
const specializationCheckboxes = document.querySelectorAll('input[name="specialization[]"]');
specializationCheckboxes.forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const specIds = Array.from(specializationCheckboxes)
            .filter(i => i.checked)
            .map(i => i.value);
        fetchServices(specIds);
    });
});

function fetchServices(specIds) {
    const servicesDiv = document.getElementById('services');
    servicesDiv.innerHTML = '<p>Loading services...</p>'; // Show loading message

    fetch('admin_fetch_services.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ specialization_ids: specIds })
    })
    .then(response => response.json())
    .then(data => {
        servicesDiv.innerHTML = ''; // Clear previous options
        data.forEach(service => {
            const label = document.createElement('label');
            label.innerHTML = `
                <input type="checkbox" name="services[]" value="${service.service_id}">
                ${service.service_name}
            `;
            servicesDiv.appendChild(label);
        });
    })
    .catch(error => {
        console.error('Error fetching services:', error);
        servicesDiv.innerHTML = '<p>Error loading services</p>';
    });
}

    </script>
</body>
</html>