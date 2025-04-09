<?php
include 'login_check.php'; // Ensure user is logged in
include 'db_config.php'; // Include your database configuration
include 'admin_check.php'; // Include the admin check helper

$message = '';
$dentist_id = $_GET['id'] ?? null;

if (!$dentist_id) {
    $message = "Error: Invalid dentist ID.";
} else {
    // Fetch dentist details
    $stmt = $conn->prepare("
        SELECT u.*, d.license_number, d.emergency_contact 
        FROM users u 
        JOIN dentists d ON u.user_id = d.user_id 
        WHERE d.user_id = ?
    ");
    $stmt->bind_param('i', $dentist_id);
    $stmt->execute();
    $dentist = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Store the original data for comparison
    $original_data = [
        'username' => $dentist['username'],
        'email' => $dentist['email'],
        'first_name' => $dentist['first_name'],
        'last_name' => $dentist['last_name'],
        'license_number' => $dentist['license_number'],
        'emergency_contact' => $dentist['emergency_contact'],
        'gender' => $dentist['gender']
    ];

    // Fetch existing specializations
    $stmt = $conn->prepare("SELECT spec_id FROM dentist_specializations WHERE user_id = ?");
    $stmt->bind_param('i', $dentist_id);
    $stmt->execute();
    $specializations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $dentist_specializations = array_column($specializations, 'spec_id');

    // Fetch dentist availability
    $stmt = $conn->prepare("SELECT day FROM dentist_availability WHERE user_id = ?");
    $stmt->bind_param('i', $dentist_id);
    $stmt->execute();
    $availability = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $dentist_availability = array_column($availability, 'day');

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $license_number = trim($_POST['license_number']);
        $emergency_contact = trim($_POST['emergency_contact']);
        $gender = trim($_POST['gender']);
        $specialization_ids = $_POST['specialization'] ?? [];
        $service_ids = $_POST['services'] ?? [];
        $availability_days = $_POST['availability'] ?? [];

        if (empty($username) || empty($email) || empty($first_name) || empty($last_name) || empty($license_number) || empty($emergency_contact) || empty($gender)) {
            $message = "Error: All fields are required.";
        } else {
            $conn->begin_transaction();

            try {
                // Update users table
                $stmt = $conn->prepare("
                    UPDATE users 
                    SET username = ?, email = ?, first_name = ?, last_name = ?, gender = ? 
                    WHERE user_id = ?
                ");
                $stmt->bind_param('sssssi', $username, $email, $first_name, $last_name, $gender, $dentist_id);
                $stmt->execute();

                // Update dentists table
                $stmt = $conn->prepare("
                    UPDATE dentists 
                    SET license_number = ?, emergency_contact = ?, updated_at = NOW() 
                    WHERE user_id = ?
                ");
                $stmt->bind_param('ssi', $license_number, $emergency_contact, $dentist_id);
                $stmt->execute();

                // Update specializations
                $stmt = $conn->prepare("DELETE FROM dentist_specializations WHERE user_id = ?");
                $stmt->bind_param('i', $dentist_id);
                $stmt->execute();

                foreach ($specialization_ids as $spec_id) {
                    $stmt = $conn->prepare("INSERT INTO dentist_specializations (user_id, spec_id, created_at) VALUES (?, ?, NOW())");
                    $stmt->bind_param('ii', $dentist_id, $spec_id);
                    $stmt->execute();
                }

                // Update availability
                $stmt = $conn->prepare("DELETE FROM dentist_availability WHERE user_id = ?");
                $stmt->bind_param('i', $dentist_id);
                $stmt->execute();

                foreach ($availability_days as $day) {
                    $stmt = $conn->prepare("INSERT INTO dentist_availability (user_id, day) VALUES (?, ?)");
                    $stmt->bind_param('is', $dentist_id, $day);
                    $stmt->execute();
                }

                // Commit the transaction
                $conn->commit();
                $message = "Dentist details updated successfully!";

                // Prepare email notification if there are changes
                $changes = [];
                foreach ($original_data as $field => $old_value) {
                    $new_value = $$field;
                    if ($new_value != $old_value) {
                        $changes[] = ucfirst(str_replace('_', ' ', $field)) . ": " . $old_value . " → " . $new_value;
                    }
                }

                if ($changes) {
                    // Send the email only if there were changes
                    $to = $dentist['email'];
                    $email_subject = "Your Profile Information Has Been Updated";
                    $email_message = "Dear " . $dentist['first_name'] . " " . $dentist['last_name'] . ",\n\n";
                    $email_message .= "The following changes have been made to your profile:\n\n";
                    $email_message .= implode("\n", $changes); // List changes

                    $email_message .= "\n\nIf you did not request these changes, please contact us immediately.\n\nBest regards,\nThe Dental Clinic Management Team";

                    $headers = "From: no-reply@dentalclinic.com" . "\r\n" .
                               "Reply-To: no-reply@dentalclinic.com" . "\r\n" .
                               "Content-Type: text/plain; charset=UTF-8" . "\r\n";

                    // Send the email
                    if (mail($to, $email_subject, $email_message, $headers)) {
                        $message .= " A notification has been sent to the dentist about the changes.";
                    } else {
                        $message .= " Error: Failed to send email notification.";
                    }
                }

            } catch (Exception $e) {
                $conn->rollback();
                $message = "Error updating dentist details: " . $e->getMessage();
            }
        }
    }
}

// Fetch all specializations
$stmt = $conn->prepare("SELECT spec_id, spec_name FROM specializations");
$stmt->execute();
$all_specializations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch all services
$stmt = $conn->prepare("
    SELECT s.service_id, s.service_name, ss.spec_id 
    FROM services s 
    JOIN service_specialization ss ON s.service_id = ss.service_id
");
$stmt->execute();
$all_services = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Dentist - Dental Clinic Management System</title>
    <link rel="stylesheet" type="text/css" href="css/style.css">
</head>
<style>

/* Main Content Styles */
.main-content {
    flex: 1;
    padding: 30px;
    background-color: white;
}

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
#services-container,
form > label[for="availability"] + label {
    margin-bottom: 20px;
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
}

form > label[for="specialization"] + label,
#services-container label {
    display: flex;
    align-items: center;
    padding: 8px 15px;
    background-color: #f8f9fa;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.3s;
    font-weight: normal;
}

form > label[for="specialization"] + label:hover,
#services-container label:hover {
    background-color: #e9ecef;
}

input[type="checkbox"] {
    margin-right: 8px;
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
    .container {
        flex-direction: column;
    }
    
    .main-content {
        padding: 20px;
    }
    
    form {
        padding: 20px;
    }
}
</style>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const specializationCheckboxes = document.querySelectorAll('.specialization-checkbox');
        const servicesContainer = document.getElementById('services-container');

        // Function to fetch services based on selected specialization
        function fetchServices() {
            const selectedSpecializations = [];

            specializationCheckboxes.forEach(function (checkbox) {
                if (checkbox.checked) {
                    selectedSpecializations.push(checkbox.value);
                }
            });

            if (selectedSpecializations.length > 0) {
                // Send an AJAX request to fetch services associated with selected specializations
                const xhr = new XMLHttpRequest();
                xhr.open('GET', 'admin_get_services.php?specializations=' + selectedSpecializations.join(','), true);
                xhr.onload = function () {
                    if (xhr.status === 200) {
                        const services = JSON.parse(xhr.responseText);
                        // Clear existing services
                        servicesContainer.innerHTML = '<label>Services:</label>';

                        // Display new services
                        services.forEach(function (service) {
                            const serviceLabel = document.createElement('label');
                            const serviceInput = document.createElement('input');
                            serviceInput.type = 'checkbox';
                            serviceInput.name = 'services[]';
                            serviceInput.value = service.service_id;
                            serviceLabel.appendChild(serviceInput);
                            serviceLabel.appendChild(document.createTextNode(service.service_name));
                            servicesContainer.appendChild(serviceLabel);
                        });
                    }
                };
                xhr.send();
            } else {
                // Clear services if no specialization is selected
                servicesContainer.innerHTML = '<label>Services:</label>';
            }
        }

        // Listen to specialization checkboxes change event
        specializationCheckboxes.forEach(function (checkbox) {
            checkbox.addEventListener('change', fetchServices);
        });
    });
    </script>
<body>
    <div class="container">
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
                <li><a href="admin_add_services.php">Add Services</a></li>
                <li><a href="reports.php">Reports</a></li>
                <li><a href="admin_settings.php">Settings</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <h1>Edit Dentist</h1>
            <?php if ($message): ?>
                <div class="alert"><?php echo $message; ?></div>
            <?php endif; ?>
            <form method="POST">
                <label>Username:</label>
                <input type="text" name="username" value="<?php echo htmlspecialchars($dentist['username']); ?>" required>
                <label>Email:</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($dentist['email']); ?>" required>
                <label>First Name:</label>
                <input type="text" name="first_name" value="<?php echo htmlspecialchars($dentist['first_name']); ?>" required>
                <label>Last Name:</label>
                <input type="text" name="last_name" value="<?php echo htmlspecialchars($dentist['last_name']); ?>" required>
                <label>License Number:</label>
                <input type="text" name="license_number" value="<?php echo htmlspecialchars($dentist['license_number']); ?>" required>
                <label>Emergency Contact:</label>
                <input type="text" name="emergency_contact" value="<?php echo htmlspecialchars($dentist['emergency_contact']); ?>" required>
                <label>Gender:</label>
                <select name="gender">
                    <option value="Male" <?php echo ($dentist['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                    <option value="Female" <?php echo ($dentist['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                    <option value="Other" <?php echo ($dentist['gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                </select>

                <label>Specializations:</label>
                <?php foreach ($all_specializations as $spec): ?>
                    <label>
                        <input type="checkbox" name="specialization[]" value="<?php echo $spec['spec_id']; ?>"
                            <?php echo in_array($spec['spec_id'], $dentist_specializations) ? 'checked' : ''; ?>
                            class="specialization-checkbox">
                        <?php echo htmlspecialchars($spec['spec_name']); ?>
                    </label>
                <?php endforeach; ?>

                <div id="services-container">
                    <label>Services:</label>
                    <!-- Services will be displayed here dynamically -->
                </div>

                <label>Availability:</label>
                <label><input type="checkbox" name="availability[]" value="M" <?php echo in_array('M', $dentist_availability) ? 'checked' : ''; ?>> Monday</label>
                <label><input type="checkbox" name="availability[]" value="T" <?php echo in_array('T', $dentist_availability) ? 'checked' : ''; ?>> Tuesday</label>
                <label><input type="checkbox" name="availability[]" value="W" <?php echo in_array('W', $dentist_availability) ? 'checked' : ''; ?>> Wednesday</label>
                <label><input type="checkbox" name="availability[]" value="Th" <?php echo in_array('Th', $dentist_availability) ? 'checked' : ''; ?>> Thursday</label>
                <label><input type="checkbox" name="availability[]" value="F" <?php echo in_array('F', $dentist_availability) ? 'checked' : ''; ?>> Friday</label>
                <label><input type="checkbox" name="availability[]" value="S" <?php echo in_array('S', $dentist_availability) ? 'checked' : ''; ?>> Saturday</label>
                <label><input type="checkbox" name="availability[]" value="Su" <?php echo in_array('Su', $dentist_availability) ? 'checked' : ''; ?>> Sunday</label>

                <button type="submit">Update Dentist</button>
            </form>
        </main>
    </div>
</body>
</html>
