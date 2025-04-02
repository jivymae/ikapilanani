<?php
session_start();
include 'db_config.php'; // Ensure this file initializes $conn

// Redirect non-dentists to login page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'dentist') {
    header('Location: login.php');
    exit();
}

$dentist_id = $_SESSION['user_id'];
$patient_id = $_GET['patient_id'] ?? null;

// Fetch patient's username
$stmt = $conn->prepare("SELECT username FROM users WHERE user_id = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$stmt->bind_result($patient_username);
$stmt->fetch();
$stmt->close();

// Fetch messages between the dentist and the patient, along with their usernames
$messages = [];
$stmt = $conn->prepare("
    SELECT m.message_id, m.created_at, m.body, m.attachments, u1.username AS sender_username, u2.username AS recipient_username 
    FROM messages m
    JOIN users u1 ON m.user_id = u1.user_id
    JOIN users u2 ON m.recipient_id = u2.user_id
    WHERE (m.user_id = ? AND m.recipient_id = ?) OR (m.user_id = ? AND m.recipient_id = ?)
    ORDER BY m.created_at ASC
");
$stmt->bind_param("iiii", $dentist_id, $patient_id, $patient_id, $dentist_id);
$stmt->execute();
$stmt->bind_result($message_id, $created_at, $body, $attachments, $sender_username, $recipient_username);

while ($stmt->fetch()) {
    $messages[] = [
        'message_id' => $message_id,
        'created_at' => $created_at,
        'body' => $body,
        'attachments' => $attachments,
        'sender_username' => $sender_username,
        'recipient_username' => $recipient_username,
    ];
}
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message_content = $_POST['message_content'];
    $attachments = null;

    // Handle file upload
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/'; // Ensure this directory is writable
        $file_name = basename($_FILES['attachment']['name']);
        $target_file = $upload_dir . $file_name;

        // Move the uploaded file to the designated folder
        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $target_file)) {
            $attachments = $target_file; // Store the file path
        } else {
            echo "Error uploading file.";
        }
    }

    // Insert the message into the messages table
    $stmt = $conn->prepare("INSERT INTO messages (user_id, recipient_id, subject, body, created_at, status, attachments) VALUES (?, ?, ?, ?, NOW(), 'sent', ?)");
    $subject = ''; // Optional subject field
    $stmt->bind_param("iisss", $dentist_id, $patient_id, $subject, $message_content, $attachments);
    
    if ($stmt->execute()) {
        header("Location: view_patient_messages.php?patient_id=" . $patient_id); // Redirect to the same page to see the new message
        exit();
    } else {
        echo "Error sending message: " . $stmt->error;
    }
}
// Mark messages as read
$stmt = $conn->prepare("UPDATE messages SET status = 'read' WHERE recipient_id = ? AND user_id = ? AND status = 'unread'");
$stmt->bind_param("ii", $patient_id, $dentist_id);
$stmt->execute();
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Messages with <?php echo htmlspecialchars($patient_username); ?></title>
    <link rel="stylesheet" type="text/css" href="css/dentist.css">
    <style>
     
    /* General styling remains the same... */
/* General styling remains the same... */

/* Main Content Section */
.main-content {
    margin-left: 250px; /* Ensure the content doesn't overlap with the sidebar */
    padding: 20px;
    width: 100%;
    overflow: hidden; /* Prevents the body from being too wide */
}

/* Message Section Styling */
h1 {
    font-size: 24px;
    font-family: Arial, sans-serif;
    margin-bottom: 20px;
    text-align: center;
}

.messages {
    max-width: 800px;
    margin-top:  ;
    padding: 50px;
    background-color: #dcd0ff;
   
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    max-height: 100%; /* Limit the height for the scrolling area */
    overflow-y: auto; /* Make messages scrollable */
}

.message {
    padding: 15px;
    margin-bottom: 15px;
    border-radius: 8px;
    border: 2px solid #ddd;
}

.dentist {
    background-color: #e7f3fe; /* Light blue background for dentist messages */
    text-align: right;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}

.patient {
    background-color: #f9f9f9; /* Light gray background for patient messages */
    text-align: left;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}

strong {
    font-weight: bold;
    color: #333;
}

p {
    font-size: 16px;
    color: #333;
    line-height: 1.5;
    word-wrap: break-word;
}

.attachment a {
    color: #3498db;
    text-decoration: none;
    font-weight: bold;
}

.attachment a:hover {
    text-decoration: underline;
}

form {
    margin-top:;/* Center form and reduce margins */
    background-color: #fff;
    padding: 15px;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    max-width: 800px; /* Ensure form has a max width */
}

textarea {
    width: 100%; /* Full width for all screen sizes */
    height: 120px;
    padding: 10px;
    font-size: 16px;
    border: 1px solid #ccc;
    border-radius: 5px;
    resize: none;
    background-color: #dcd0ff;
}

input[type="file"] {
    margin-top: 10px;
    padding: 8px;
    font-size: 16px;
    width: 100%; /* Full width for file input */
}

button {
    background-color: #967bb6;
    color: white;
    border: none;
    padding: 12px 20px;
    font-size: 16px;
    cursor: pointer;
    border-radius: 5px;
    margin-top: 10px;
    transition: background-color 0.3s;
    width: 100%; /* Make buttons full-width */
}

button:hover {
    background-color: #522d80;
}

button:focus {
    outline: none;
}

button:active {
    transform: scale(0.98);
}

/* Go to Inbox Button */
button.go-to-inbox {
    background-color: #3498db;
    margin-top: 20px;
}

button.go-to-inbox:hover {
    background-color: #2980b9;
}

/* Add responsiveness for tablets (600px to 1024px) */
@media (max-width: 1024px) {
    .main-content {
        margin-left: 0; /* Remove sidebar margin */
        padding: 10px;
    }

    .messages {
        padding: 50px;
        max-width: 100%; /* Full width for smaller screens */
    }

    .message {
        padding: 12px;
        margin-bottom: 10px;
    }

    form {
       
        padding: 15px;
        width: 70%; /* Make form content responsive */
    }

    textarea {
        width: 100%; /* Full width for tablet */
        height: 100px;
    }

    input[type="file"] {
        width: 100%; /* Make file input take full width */
    }

    button {
        width: 100%; /* Make buttons full-width on tablet */
    }

    /* Sidebar adjustment for smaller screens */
    .sidebar {
        position: absolute;
        left: -250px; /* Hide sidebar by default */
        transition: 0.3s;
    }

    .navbar {
        display: block;
    }

    .sidebar a {
        font-size: 18px;
    }

    /* Make sidebar visible when expanded (on click) */
    .sidebar.active {
        left: 0;
    }

    .sidebar .navbar {
        margin-top: 20px;
    }
}

/* Add responsiveness for small screens (less than 600px) */
@media (max-width: 600px) {
    h1 {
        font-size: 20px;
    }

    .messages {
        padding: 10px;
    }

    .message {
        padding: 10px;
        margin-bottom: 10px;
    }

    form {
        width: 100%;
        padding: 15px;
    }

    textarea {
        width: 100%;
        height: 80px;
    }

    button {
        width: 100%;
        font-size: 14px;
    }

    .attachment a {
        font-size: 14px;
    }
}

</style>

        
    
</head>
<body>

<div class="sidebar">
        <nav class="navbar">
        <div class="logo">
            <img src="images/lads.png" alt="Dental Clinic Logo">
            <h1>LAD DCAMS</h1>
        </div>
        
        <nav>
        
        <a href="dentist_dashboard.php">Dashboard</a>
        <a href="dentist_profile.php">Profile</a>
        <a href="dentist_patient_appointments.php" class="active">Appointments</a>
        <a href="dentist_patient_records.php">Patient Records</a>
        <a href="dentist_message.php">Messages</a>
        <a href="logout.php">Logout</a>

    </div>
<div class="messages">
    <p>Messages</p>
    
    <?php foreach ($messages as $message): ?>
        <div class="message <?php echo ($message['sender_username'] === $patient_username) ? 'patient' : 'dentist'; ?>">
            <strong><?php echo htmlspecialchars($message['created_at']); ?></strong>
            <p><strong><?php echo htmlspecialchars($message['sender_username']); ?>:</strong> <?php echo nl2br(htmlspecialchars($message['body'])); ?></p>
            <?php if ($message['attachments']): ?>
                <div class="attachment">
                    <a href="<?php echo htmlspecialchars($message['attachments']); ?>" target="_blank">View Attachment</a>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
    
            </div>
    <form method="POST" enctype="multipart/form-data">
        <h1>Messages with <?php echo htmlspecialchars($patient_username); ?></h1>
        <textarea name="message_content" required placeholder="Type your message here..."></textarea>
        <input type="file" name="attachment" accept="image/*,application/pdf" />
        <button type="submit">Send Reply</button>
        <button onclick="window.location.href='dentist_message.php';">Go to Inbox</button>
    </form>

    

</body>
</html>
