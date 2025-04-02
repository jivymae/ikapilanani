<?php
session_start();
include 'db_config.php'; // Ensure this file initializes $conn

// Redirect non-dentists to login page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'dentist') {
    header('Location: login.php');
    exit();
}

$dentist_id = $_SESSION['user_id'];
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Fetch messages and their counts
$patients = [];
$stmt = $conn->prepare("
    SELECT m.user_id AS sender_id,
           m.recipient_id AS patient_id,
           u.first_name,
           u.last_name,
           COUNT(CASE WHEN m.status = 'unread' THEN 1 END) AS unread_count
    FROM messages m
    JOIN users u ON m.recipient_id = u.user_id
    WHERE m.user_id = ? 
      AND m.is_deleted = 0
      AND (u.first_name LIKE ? OR u.last_name LIKE ?)
    GROUP BY m.recipient_id
");

$like_query = '%' . $search_query . '%';
$stmt->bind_param("iss", $dentist_id, $like_query, $like_query);
$stmt->execute();
$stmt->bind_result($sender_id, $patient_id, $first_name, $last_name, $unread_count);

while ($stmt->fetch()) {
    $patients[] = [
        'sender_id' => $sender_id,
        'patient_id' => $patient_id,
        'patient_name' => htmlspecialchars($first_name . ' ' . $last_name),
        'unread_count' => $unread_count,
    ];
}

// Fetch chat request notifications
$chat_request_count = 0;
$chat_request_stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM messages 
    WHERE recipient_id = ? AND chat_request = 1 AND is_deleted = 0
");
$chat_request_stmt->bind_param("i", $dentist_id);
$chat_request_stmt->execute();
$chat_request_stmt->bind_result($chat_request_count);
$chat_request_stmt->fetch();
$chat_request_stmt->close();

// Fetch reschedule requests
$reschedule_requests = [];
$reschedule_stmt = $conn->prepare("
    SELECT r.request_id, r.patient_id, r.new_date, r.new_time, r.status 
    FROM appointment_reschedule_requests r
    JOIN appointments a ON r.appointment_id = a.appointment_id
    WHERE a.dentist_id = ? AND r.status = 'pending'
");
$reschedule_stmt->bind_param("i", $dentist_id);
$reschedule_stmt->execute();
$reschedule_stmt->bind_result($request_id, $patient_id, $new_date, $new_time, $status);

while ($reschedule_stmt->fetch()) {
    $reschedule_requests[] = [
        'request_id' => $request_id,
        'patient_id' => $patient_id,
        'new_date' => $new_date,
        'new_time' => $new_time,
        'status' => $status,
    ];
}
$reschedule_stmt->close();

// Fetch dentist details
$stmt = $conn->prepare("SELECT username, email FROM users WHERE user_id = ? AND role = 'dentist'");
$stmt->bind_param("i", $dentist_id);
$stmt->execute();
$stmt->bind_result($username, $email);
$stmt->fetch();
$stmt->close();
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dentist Inbox</title>
    <link rel="stylesheet" type="text/css" href="css/dentist.css">
    <style>

/* Main Content */
.main-content {
    flex: 1;
    padding: 20px;
    margin-left: 20px; /* Offset for fixed sidebar */
}

.main-content h1 {
    color: #354649; /* Dark Green */
    font-size: 24px;
    margin-bottom: 20px;
}

/* Create Message Link */
a[href="create_message.php"] {
    display: inline-block;
    padding: 10px;
    background-color: #3498db; /* Blue */
    color: white;
    text-decoration: none;
    border-radius: 4px;
    margin-bottom: 20px;
    transition: background-color 0.3s;
}

a[href="create_message.php"]:hover {
    background-color: #2980b9; /* Darker blue */
}

/* Notification Bell */
.notification-bell {
    display: inline-block;
    position: relative;
    margin-bottom: 20px;
    color: #3498db;
    text-decoration: none;
}

.notification-bell i {
    font-size: 24px; /* Adjust size for the bell icon */
}

.notification-bell .count {
    position: absolute;
    top: -5px;
    right: -5px;
    background-color: red;
    color: white;
    font-size: 12px;
    padding: 3px 6px;
    border-radius: 50%;
}

/* Search Form */
form {
    margin-top: 20px;
}

form input[type="text"] {
    padding: 8px;
    margin-right: 5px;
    border-radius: 4px;
    border: 1px solid #ccc;
    width: 200px;
}

form button {
    padding: 8px 15px;
    background-color: #354649; /* Dark Green */
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.3s;
}

form button:hover {
    background-color: #6a8a82; /* Muted Teal */
}

/* Patient List */
.patient-list {
    margin-top: 20px;
}

.patient-list a {
    display: block;
    padding: 12px 20px;
    margin: 5px 0;
    text-decoration: none;
    color: #3498db; /* Blue color */
    border: 1px solid #ddd;
    border-radius: 5px;
    transition:  0.3s ease;
    background-color: #e6e8fa;
    position: relative;
}

.patient-list a:hover {
    background-color: #f1f1f1; /* Light gray background on hover */
}

.patient-list a.unread {
    background-color: #d6cadd; /* Light red background for unread messages */
    color: #58427c; /* Dark red text for unread messages */
}

/* Unread Indicator Dot */
.unread-dot {
    position: absolute;
    top: 10px;
    right: 10px;
    width: 8px;
    height: 8px;
    background-color: red;
    border-radius: 50%;
    display: none; /* Hidden by default */
}

.patient-list a.unread .unread-dot {
    display: block; /* Show the dot if unread */
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
        <a href="dentist_patient_appointments.php">Appointments</a>
        <a href="dentist_patient_records.php">Patient Records</a>
        <a href="dentist_message.php" class="active">Messages</a>
        <a href="logout.php">Logout</a>
    </div>


<div class="main-content">

<!-- Notification Bell -->
<?php if ($chat_request_count > 0): ?>
        <a href="view_chat_requests.php" class="notification-bell">
            Chat requests<i class="fas fa-bell"></i>
            <span class="count"><?php echo $chat_request_count; ?></span>
        </a>
    <?php endif; ?><br>
    <a href="create_message.php">Create Message</a>
    <h1>Inbox</h1>
    <form method="GET" action="">
        <input type="text" name="search" placeholder="Search by name" required>
        <button type="submit">Search</button>
    </form>
    
    <div class="patient-list">
        <?php foreach ($patients as $patient): ?>
            <a href="view_patient_messages.php?patient_id=<?php echo $patient['patient_id']; ?>" class="<?php echo $patient['unread_count'] > 0 ? 'unread' : ''; ?>">
                <?php echo $patient['patient_name']; ?>
                <span class="unread-dot"></span>
            </a>
        <?php endforeach; ?>
    </div>
    

</div>

</body>
</html>
