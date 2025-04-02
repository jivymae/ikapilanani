<?php
include 'login_check.php';  // Ensure user is logged in
include 'db_config.php';

// Ensure user is logged in as admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: admin_unauthorized.php');
    exit();
}


// Fetch patient details if patient_id is passed via URL
$patient_id = isset($_GET['patient_id']) ? $_GET['patient_id'] : (isset($_POST['patient_id']) ? $_POST['patient_id'] : null);

$patient = [];
$age = 0; // Initialize age variable

if ($patient_id) {
    // Fetch patient details including Date_Of_Birth
    $stmt = $conn->prepare("SELECT Patient_ID, CONCAT(First_Name, ' ', Last_Name) AS patient_name, Date_Of_Birth FROM patients WHERE Patient_ID = ?");
    $stmt->bind_param('i', $patient_id);
    $stmt->execute();
    $stmt->bind_result($id, $patient_name, $birth_date);
    if ($stmt->fetch()) {
        $patient = ['id' => $id, 'name' => $patient_name, 'birth_date' => $birth_date];

        // Calculate age
        if ($birth_date) {
            $today = new DateTime();
            $birthdate = new DateTime($birth_date);
            $age = $today->diff($birthdate)->y; // Calculate the difference in years
        }
    }
    $stmt->close();
}

// Fetch list of dentists with first and last names, and profile images
$dentists = [];
$stmt = $conn->prepare("SELECT u.user_id AS dentist_id, u.first_name, u.last_name, u.username, u.profile_image 
                        FROM users u 
                        WHERE u.role = 'dentist' 
                        AND u.user_id NOT IN (SELECT user_id FROM dentists WHERE archived = 1)");
$stmt->execute();
$stmt->bind_result($dentist_id, $dentist_first_name, $dentist_last_name, $dentist_username, $profile_image);
while ($stmt->fetch()) {
    $dentists[] = [
        'id' => $dentist_id,
        'first_name' => $dentist_first_name,
        'last_name' => $dentist_last_name,
        'username' => $dentist_username,
        'profile_image' => $profile_image ?: 'images/unknown.png'  // Default to 'unknown.png' if no profile image
    ];
}
$stmt->close();

// Fetch available services
$services = [];
$stmt = $conn->prepare("SELECT service_id, service_name, price FROM services");
$stmt->execute();
$stmt->bind_result($service_id, $service_name, $price);
while ($stmt->fetch()) {
    $services[] = [
        'service_id' => $service_id,
        'service_name' => $service_name,
        'price' => $price
    ];
}
$stmt->close();

// Fetch payment methods
$payment_methods = [];
$stmt = $conn->prepare("SELECT method_id, method_name FROM payment_methods");
$stmt->execute();
$stmt->bind_result($method_id, $method_name);
while ($stmt->fetch()) {
    $payment_methods[] = ['id' => $method_id, 'name' => $method_name];
}
$stmt->close();

// Fetch patient details if patient_id is passed via URL
$patient_id = isset($_GET['patient_id']) ? $_GET['patient_id'] : (isset($_POST['patient_id']) ? $_POST['patient_id'] : null);

if ($patient_id) {
    $stmt = $conn->prepare("SELECT Patient_ID, CONCAT(First_Name, ' ', Last_Name) AS patient_name, Date_Of_Birth FROM patients WHERE Patient_ID = ?");
    $stmt->bind_param('i', $patient_id);
    $stmt->execute();
    $stmt->bind_result($id, $patient_name, $birth_date);
    if ($stmt->fetch()) {
        $patient = ['id' => $id, 'name' => $patient_name, 'birth_date' => $birth_date];
    }
    $stmt->close();
}

// Handle appointment creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_appointment'])) {
    $patient_id = $_POST['patient_id'];
    $dentist_id = $_POST['dentist_id'];
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    $selected_services = $_POST['services'] ?? [];
    $payment_method_id = $_POST['payment_method']; // Payment Method ID
    $reason = $_POST['reason']; // Get the reason for the visit
    $is_emergency = $_POST['is_emergency']; // Get the emergency status (string '1' or '0')

    // Calculate the patient's age
    $today = new DateTime();
$birthdate = new DateTime($patient['birth_date']);
$age = $today->diff($birthdate)->y;




    // Insert appointment into the database, including the is_emergency field
    $stmt = $conn->prepare("INSERT INTO appointments (patient_id, dentist_id, appointment_date, appointment_time, reason, is_emergency) 
    VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('iissss', $patient_id, $dentist_id, $appointment_date, $appointment_time, $reason, $is_emergency);
    $stmt->execute();

    $appointment_id = $stmt->insert_id; // Get the last inserted appointment ID
    $stmt->close();

    // Insert services into the `appointment_services` table
    foreach ($selected_services as $service_id) {
        $stmt = $conn->prepare("INSERT INTO appointment_services (appointment_id, service_id) VALUES (?, ?)");
        $stmt->bind_param('ii', $appointment_id, $service_id);
        $stmt->execute();
    }
    $stmt->close();

    // Calculate the total amount based on selected services
    $total_amount = array_reduce($selected_services, function($carry, $service_id) use ($conn) {
        $stmt = $conn->prepare("SELECT price FROM services WHERE service_id = ?");
        $stmt->bind_param('i', $service_id);
        $stmt->execute();
        $stmt->bind_result($price);
        $stmt->fetch();
        $stmt->close();
        return $carry + $price;
    }, 0);


    
    // Apply 20% discount if the patient is 60 or older
    if ($age >= 60) {
        $total_amount = $total_amount * 0.80;  // Apply 20% discount
    }

    // Insert payment details into the payments table
    $transaction_number = ''; // Unique Transaction ID
    $payment_status = 'pending'; // Payment status set to 'pending'

    $stmt = $conn->prepare("INSERT INTO payments (appointment_id, patient_id, total_amount, transaction_number, method_id) 
                            VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('iiisi', $appointment_id, $patient_id, $total_amount, $transaction_number, $payment_method_id);
    $stmt->execute();
    $stmt->close();

    // Redirect to success page or display success message
    header('Location: appointments.php');
    exit();
}

// Fetch closure dates (optional)
$closures = [];
$stmt = $conn->prepare("SELECT closure_date, cause FROM clinic_closures");
$stmt->execute();
$stmt->bind_result($closure_date, $cause);
while ($stmt->fetch()) {
    $closures[] = ['date' => $closure_date, 'cause' => $cause];
}
$stmt->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="css/style.css">
    <title>Create Appointment - Admin Panel</title>
    <script>

function updateTimeSlots() {
    const appointmentDate = document.getElementById('appointment_date').value;
    if (appointmentDate) {
        // Check if the selected date is a clinic closure
        const closureDates = <?php echo json_encode($closures); ?>;
        const isClosed = closureDates.some(function(closure) {
            return closure.date === appointmentDate;
        });

        if (isClosed) {
            alert('The clinic is closed on the selected date. Please choose another date.');
            document.getElementById('appointment_date').value = '';
            return;
        }

        const date = new Date(appointmentDate);
        const options = { weekday: 'long' };
        const dayOfWeek = new Intl.DateTimeFormat('en-US', options).format(date);

        const xhr = new XMLHttpRequest();
        xhr.open('GET', `fetch_time_slots.php?day_of_week=${dayOfWeek}`, true);
        xhr.onload = function() {
            if (xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                const timeSelect = document.getElementById('appointment_time');
                timeSelect.innerHTML = '<option value="">-- Select Time --</option>';

                const currentDateTime = new Date();  // Get the current date and time
                const selectedDateTime = new Date(appointmentDate);  // Convert the selected date to a Date object
                selectedDateTime.setHours(0, 0, 0, 0);  // Remove time part for comparison

                response.forEach(function(time) {
                    // Create the full date-time for comparison (with the selected time)
                    const timeParts = time.split(':');
                    const timeDateTime = new Date(selectedDateTime);
                    timeDateTime.setHours(timeParts[0], timeParts[1]);

                    // Compare the selected time against the current time
                    if (timeDateTime >= currentDateTime) {
                        const option = document.createElement('option');
                        option.value = time;
                        option.textContent = time;
                        timeSelect.appendChild(option);
                    }
                });
            }
        };
        xhr.send();
    }
}

        // Function to update the dentist profile image when a dentist is selected
        function updateDentistProfile(dentistId, imageUrl) {
            // Highlight selected dentist card
            const dentistCards = document.querySelectorAll('.dentist-card');
            dentistCards.forEach(card => card.classList.remove('selected'));

            const selectedCard = document.querySelector(`#dentist-${dentistId}`);
            if (selectedCard) {
                selectedCard.classList.add('selected');
            }

            // Update the dentist image
            document.getElementById('dentist-image').src = imageUrl;
            document.getElementById('dentist-id').value = dentistId;
        }

        // Function to calculate the total amount based on selected services
        function calculateTotal() {
        const checkboxes = document.querySelectorAll('input[name="services[]"]:checked');
        let totalAmount = 0;

        // Get the patient's age from the hidden input
        const patientAge = parseInt(document.getElementById('patient-age').value);

        // Calculate the total based on selected services
        checkboxes.forEach(function(checkbox) {
            const price = parseFloat(checkbox.getAttribute('data-price'));
            if (!isNaN(price)) {
                totalAmount += price;
            }
        });

        // Apply 20% discount if patient is 60 or older
        if (patientAge >= 60) {
            totalAmount = totalAmount * 0.80;  // Apply a 20% discount
        }

        // Update the display and hidden input with the total amount
        document.getElementById('total-display').textContent = `Total Amount: PHP ${totalAmount.toFixed(2)}`;
        document.getElementById('total-amount').value = totalAmount.toFixed(2);

        // Optionally, display the discounted amount if applicable
        if (patientAge >= 60) {
            document.getElementById('discounted-display').style.display = 'block';
            document.getElementById('discounted-amount').textContent = `Discounted Total: PHP ${totalAmount.toFixed(2)}`;
        } else {
            document.getElementById('discounted-display').style.display = 'none';
        }
    }

    </script>
</head>
<style>
    .dentists-list {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-around;
    gap: 20px;
}

.dentist-card {
    width: 150px;
    text-align: center;
    cursor: pointer;
    transition: transform 0.3s;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 8px;
}

.dentist-card:hover {
    transform: scale(1.05);
}

.dentist-card.selected {
    border-color: #007bff;
    background-color: #f0f8ff;
}

.dentist-image {
    width: 100px;
    height: 100px;
    object-fit: cover;
    border-radius: 8px;
}

.dentist-name {
    margin-top: 10px;
    font-size: 14px;
    color: #333;
}

    </style>

<body>

<aside class="sidebar">
    <h2 class="logo">
        <img src="images/lads.png" alt="Dental Clinic Logo">
        <h1>Dental Clinic</h1>
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

<div class="main-content">
    <h1>Create Appointment</h1>
    <form method="POST" action="admin_add_appointments.php">
        <input type="hidden" name="patient_id" value="<?php echo $patient['id'] ?? ''; ?>">
       <!-- Hidden field to pass patient age to JavaScript -->
<input type="hidden" id="patient-age" value="<?php echo isset($age) ? $age : 0; ?>">

<?php if ($age >= 60): ?>
    <p style="color: green; font-weight: bold;">This patient is a senior citizen.</p>
<?php endif; ?>
<?php if ($patient): ?>
    <p><strong>Patient:</strong> <?php echo htmlspecialchars($patient['name']); ?></p>
    <p><strong>Age:</strong> <?php echo $age; ?></p>

    <input type="hidden" name="patient_id" value="<?php echo $patient['id']; ?>">
<?php else: ?>
    <p>No patient selected or patient not found.</p>
<?php endif; ?>
        
        <label for="dentist_id">Select Dentist:</label>
<div id="dentists-list" class="dentists-list" required>
    <?php foreach ($dentists as $dentist): ?>
        <div class="dentist-card" id="dentist-<?= $dentist['id'] ?>" onclick="updateDentistProfile(<?= $dentist['id'] ?>, '<?= htmlspecialchars($dentist['profile_image']) ?>')">
            <img src="<?= htmlspecialchars($dentist['profile_image']) ?>" alt="<?= htmlspecialchars($dentist['first_name']) ?>" class="dentist-image">
            <p class="dentist-name"><?= htmlspecialchars($dentist['first_name'] . ' ' . $dentist['last_name']) ?></p>
        </div>
    <?php endforeach; ?>
</div>
<!-- Dentist profile image display -->
<div id="dentist-profile">
    <img id="dentist-image" alt="Dentist Profile Image" style="width: 100px; height: 100px; object-fit: cover;">
    <input type="hidden" name="dentist_id" id="dentist-id" required>
</div>

<!-- Select Services -->
<label>Select Services:</label>
<div id="service-list" required>
    <p>Please select a dentist to view available services.</p>
</div>

<!-- Add a hidden field to enforce service selection in JavaScript -->
<input type="hidden" name="services_selected" id="services_selected" value="">


        <script>



 // Add validation to check if at least one service is selected
 function validateForm() {
        const selectedDentist = document.getElementById('dentist-id').value;
        const selectedServices = document.querySelectorAll('input[name="services[]"]:checked');
        
        if (!selectedDentist) {
            alert("Please select a dentist.");
            return false;
        }
        
        if (selectedServices.length === 0) {
            alert("Please select at least one service.");
            return false;
        }
        
        // All validations passed
        return true;
    }

    // Update the form submission to include service validation
    document.querySelector('form').addEventListener('submit', function(event) {
        if (!validateForm()) {
            event.preventDefault(); // Prevent form submission if validation fails
        }
    });
    
    function updateDentistProfile(dentistId, imageUrl) {
        // Highlight selected dentist card
        const dentistCards = document.querySelectorAll('.dentist-card');
        dentistCards.forEach(card => card.classList.remove('selected'));

        const selectedCard = document.querySelector(`#dentist-${dentistId}`);
        if (selectedCard) {
            selectedCard.classList.add('selected');
        }

        // Update the dentist image
        document.getElementById('dentist-image').src = imageUrl;
        document.getElementById('dentist-id').value = dentistId;

        // Fetch and display services for the selected dentist
        fetch(`fetch_services.php?dentist_id=${dentistId}`)
            .then(response => response.json())
            .then(services => {
                const serviceList = document.getElementById('service-list');
                serviceList.innerHTML = '';  // Clear existing services

                if (services.length === 0) {
                    serviceList.innerHTML = '<p>No services available for this dentist.</p>';
                    return;
                }

                services.forEach(service => {
                    const serviceLabel = document.createElement('label');
                    serviceLabel.innerHTML = `
                        <input type="checkbox" name="services[]" value="${service.service_id}" data-price="${service.price}" class="service-checkbox">
                        ${service.service_name} - PHP ${parseFloat(service.price).toFixed(2)}
                    `;
                    serviceList.appendChild(serviceLabel);
                    serviceList.appendChild(document.createElement('br'));
                });

                // Re-bind the calculateTotal function to new checkboxes
                const serviceCheckboxes = document.querySelectorAll('input[name="services[]"]');
                serviceCheckboxes.forEach(checkbox => checkbox.addEventListener('change', calculateTotal));
            })
            .catch(error => console.error('Error fetching services:', error));
    }

    function calculateTotal() {
        const checkboxes = document.querySelectorAll('input[name="services[]"]:checked');
        let totalAmount = 0;

        checkboxes.forEach(checkbox => {
            const price = parseFloat(checkbox.getAttribute('data-price'));
            if (!isNaN(price)) {
                totalAmount += price;
            }
        });

        document.getElementById('total-display').textContent = `Total Amount: PHP ${totalAmount.toFixed(2)}`;
        document.getElementById('total-amount').value = totalAmount.toFixed(2);
    }
</script>

<label for="is_emergency">Is this an emergency?</label>
<select name="is_emergency" id="is_emergency" required>
    <option value="">-- Select --</option>
    <option value="1">Yes</option>
    <option value="0">No</option>
</select>


        
        <label for="appointment_date">Date:</label>
        <input type="date" name="appointment_date" id="appointment_date" required onchange="updateTimeSlots()">

        <label for="appointment_time">Time:</label>
        <select name="appointment_time" id="appointment_time" required>
            <option value="">-- Select Time --</option>
        </select>

        <label for="payment_method">Payment Method:</label>
        <select name="payment_method" required>
            <option value="">-- Select Payment Method --</option>
            <?php foreach ($payment_methods as $method): ?>
                <option value="<?= $method['id'] ?>"><?= htmlspecialchars($method['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <label for="reason">Reason for Visit:</label>
<textarea name="reason" id="reason" rows="4" required placeholder="Enter the reason for the visit"></textarea>

      

        <input type="hidden" name="total_amount" id="total-amount" value="0.00">
        
        <p id="total-display">Total Amount: PHP 0.00</p>

        <input type="submit" name="create_appointment" value="Create Appointment">
    </form>
</div>

</body>
</html>
