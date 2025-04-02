<?php
session_start();
include 'db_config.php'; // Ensure this file initializes $conn

// Redirect non-dentists to login page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'dentist') {
    header('Location: login.php');
    exit();
}

$dentist_id = $_SESSION['user_id'];
$patients = [];

// Fetch all patients for the dropdown
$stmt = $conn->prepare("SELECT user_id, username FROM users WHERE role = 'patient'");
$stmt->execute();
$stmt->bind_result($patient_id, $patient_username);

while ($stmt->fetch()) {
    $patients[] = [
        'patient_id' => $patient_id,
        'patient_username' => $patient_username,
    ];
}

$stmt->close();

// Check if the form has been submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipient_id = $_POST['recipient_id'];
    $subject = $_POST['subject'];
    $body = $_POST['body'];
    $status = 'unread'; // Set default status
    $created_at = date('Y-m-d H:i:s'); // Current timestamp
    $priority = 'medium'; // Set priority to medium

    // Handle file upload
    $attachments = null;
    if (isset($_FILES['attachments']) && $_FILES['attachments']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/';
        $uploadFile = $uploadDir . basename($_FILES['attachments']['name']);

        // Ensure the uploads directory exists
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        if (move_uploaded_file($_FILES['attachments']['tmp_name'], $uploadFile)) {
            $attachments = $uploadFile; // Store the path of the uploaded file
        }
    }

    // Insert message into the database with or without attachment
    if ($attachments) {
        // If there is an attachment, use the full query with all parameters
        $stmt = $conn->prepare("INSERT INTO messages (user_id, recipient_id, subject, body, created_at, status, priority, attachments) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissssss", $dentist_id, $recipient_id, $subject, $body, $created_at, $status, $priority, $attachments);
    } else {
        // If no attachment, use the query without the attachment column
        $stmt = $conn->prepare("INSERT INTO messages (user_id, recipient_id, subject, body, created_at, status, priority) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iisssss", $dentist_id, $recipient_id, $subject, $body, $created_at, $status, $priority);
    }

    // Execute and close statement
    if ($stmt) {
        $stmt->execute();
        $stmt->close();
    }

    // Redirect back to messages
    header('Location: dentist_message.php');
    exit();
}

// Close the connection after handling form submission
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Message</title>
</head>
<style>
    /* General Styles */
body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
    background-color: #f4f4f4;
    color: #333;
}

/* Sidebar Styles */
.sidebar {
    width: 200px;
    background-color: #354649; /* Dark Green */
    padding: 15px;
    box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
    color: #f4efe9; /* Light Cream */
    position: fixed;
    top: 0;
    left: 0;
    height: 100%;
}

.sidebar h2 {
    font-size: 18px;
    margin-bottom: 20px;
    color: #d2a679; /* Sand */
}

.sidebar a {
    display: block;
    padding: 10px;
    text-decoration: none;
    color: #f4efe9; /* Light Cream */
    margin-bottom: 5px;
    border-radius: 4px;
    transition: background-color 0.3s;
}

.sidebar a:hover {
    background-color: #6a8a82; /* Muted Teal */
}

.sidebar a.active {
    background-color: #d2a679; /* Sand */
    color: #333333;
    font-weight: bold;
}

/* Main Content */
.main-content {
    flex: 1;
    padding: 20px;
    margin-left: 220px; /* Offset for fixed sidebar */
}

.main-content h1 {
    color: #354649; /* Dark Green */
    font-size: 24px;
    margin-bottom: 20px;
}

/* Form Styles */
form {
    background-color: white;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #ddd;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

form label {
    font-weight: bold;
    color: #354649; /* Dark Green */
}

form input[type="text"], form select, form textarea, form input[type="file"] {
    width: 100%;
    padding: 10px;
    margin: 8px 0;
    border-radius: 4px;
    border: 1px solid #ccc;
}

form textarea {
    resize: vertical;
    height: 150px;
}

form input[type="submit"] {
    padding: 10px 15px;
    background-color: #354649; /* Dark Green */
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.3s;
}

form input[type="submit"]:hover {
    background-color: #6a8a82; /* Muted Teal */
}

/* Back Link */
a[href="dentist_message.php"] {
    display: inline-block;
    margin-top: 20px;
    padding: 10px;
    background-color: #3498db; /* Blue */
    color: white;
    text-decoration: none;
    border-radius: 4px;
    transition: background-color 0.3s;
}

a[href="dentist_message.php"]:hover {
    background-color: #2980b9; /* Darker blue */
}

/* Responsive Design */
@media screen and (max-width: 768px) {
    body {
        flex-direction: column;
    }

    .sidebar {
        width: 100%;
        height: auto;
        position: static;
    }

    .main-content {
        margin-left: 0;
    }

    form {
        width: 100%;
        padding: 15px;
    }
}

    </style>
<body>
    <h1>Create Message</h1>
    <form action="create_message.php" method="POST" enctype="multipart/form-data">
        <label for="recipient_id">Select Patient:</label>
        <select name="recipient_id" id="recipient_id" required>
            <option value="">--Select Patient--</option>
            <?php foreach ($patients as $patient): ?>
                <option value="<?php echo $patient['patient_id']; ?>"><?php echo htmlspecialchars($patient['patient_username']); ?></option>
            <?php endforeach; ?>
        </select>
        <br><br>
        
        <label for="subject">Subject:</label>
        <input type="text" name="subject" id="subject" required>
        <br><br>

        <label for="body">Message:</label><br>
        <textarea name="body" id="body" rows="4" cols="50" required></textarea>
        <br><br>

        <label for="attachments">attachments:</label>
        <input type="file" name="attachments" id="attachments" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
        <br><br>

        <input type="submit" value="Send Message">
    </form>
    <br>
    <a href="dentist_message.php">Back to Inbox</a>
</body>
</html>
