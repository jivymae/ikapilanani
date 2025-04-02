<?php
session_start();
include 'db_config.php'; // Ensure this file initializes $conn

// Redirect non-patients to login page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'patient') {
    header('Location: login.php');
    exit();
}

$patient_id = $_SESSION['user_id'];
$dentists = [];

// Fetch all dentists for the dropdown
$stmt = $conn->prepare("SELECT user_id, username FROM users WHERE role = 'dentist'");
$stmt->execute();
$stmt->bind_result($dentist_id, $dentist_username);

while ($stmt->fetch()) {
    $dentists[] = [
        'dentist_id' => $dentist_id,
        'dentist_username' => $dentist_username,
    ];
}

$stmt->close();

// Check if the form has been submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipient_id = $_POST['recipient_id']; // Dentist selected
    $subject = $_POST['subject']; // Subject of the message
    $body = $_POST['body']; // Body of the message
    $status = 'unread'; // Set default status
    $created_at = date('Y-m-d H:i:s'); // Current timestamp
    $priority = $_POST['priority']; // Priority from form

    // Handle file upload (attachments)
    $attachments = null;
    if (isset($_FILES['attachments']) && $_FILES['attachments']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/';
        $uploadFile = $uploadDir . basename($_FILES['attachments']['name']);

        // Ensure the uploads directory exists
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Move the uploaded file to the upload directory
        if (move_uploaded_file($_FILES['attachments']['tmp_name'], $uploadFile)) {
            $attachments = $uploadFile; // Store the path of the uploaded file
        }
    }

    // Insert message into the database with or without attachment
    if ($attachments) {
        // If there is an attachment, use the query with the attachment column
        $stmt = $conn->prepare("INSERT INTO messages (user_id, recipient_id, subject, body, created_at, status, priority, attachments) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissssss", $patient_id, $recipient_id, $subject, $body, $created_at, $status, $priority, $attachments);
    } else {
        // If no attachment, use the query without the attachment column
        $stmt = $conn->prepare("INSERT INTO messages (user_id, recipient_id, subject, body, created_at, status, priority) 
                               VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iisssss", $patient_id, $recipient_id, $subject, $body, $created_at, $status, $priority);
    }

    // Execute and close statement
    if ($stmt) {
        $stmt->execute();
        $stmt->close();
    }

    // Redirect back to messages page
    header('Location: patient_message.php');
    exit();
}

// Close the connection after handling form submission
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Message to Dentist</title>
</head>
<body>
    <h1>Send Message to Dentist</h1>

    <form action="patient_send_message.php" method="POST" enctype="multipart/form-data">
        <label for="recipient_id">Select Dentist:</label>
        <select name="recipient_id" id="recipient_id" required>
            <option value="">--Select Dentist--</option>
            <?php foreach ($dentists as $dentist): ?>
                <option value="<?php echo $dentist['dentist_id']; ?>"><?php echo htmlspecialchars($dentist['dentist_username']); ?></option>
            <?php endforeach; ?>
        </select>
        <br><br>

        <label for="subject">Subject:</label>
        <input type="text" name="subject" id="subject" required>
        <br><br>

        <label for="body">Message Body:</label><br>
        <textarea name="body" id="body" rows="4" cols="50" required></textarea>
        <br><br>

        <label for="attachments">Attachments:</label>
        <input type="file" name="attachments" id="attachments" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
        <br><br>

        <label for="priority">Priority:</label>
        <select name="priority" id="priority">
            <option value="low">Low</option>
            <option value="medium" selected>Medium</option>
            <option value="high">High</option>
        </select>
        <br><br>

        <input type="submit" value="Send Message">
    </form>

    <br>
    <a href="patient_message.php">Back to Inbox</a>
</body>
</html>
