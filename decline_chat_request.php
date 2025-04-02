<?php
session_start();
include 'db_config.php'; // Ensure this file initializes $conn

// Redirect non-dentists to login page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'dentist') {
    header('Location: login.php');
    exit();
}

if (isset($_GET['message_id'])) {
    $message_id = $_GET['message_id'];

    // Option 1: Mark message as deleted (set is_deleted = 1)
    $stmt = $conn->prepare("UPDATE messages SET is_deleted = 1 WHERE message_id = ? AND recipient_id = ?");
    $stmt->bind_param("ii", $message_id, $_SESSION['user_id']);
    $stmt->execute();

    // Option 2: Alternatively, you could set chat_request = 0 or any other flag for declined requests:
    // $stmt = $conn->prepare("UPDATE messages SET chat_request = 0 WHERE message_id = ? AND recipient_id = ?");
    // $stmt->bind_param("ii", $message_id, $_SESSION['user_id']);
    // $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $_SESSION['success'] = "Chat request declined successfully.";
    } else {
        $_SESSION['error'] = "Failed to decline the chat request.";
    }

    $stmt->close();
    header('Location: chat_requests.php'); // Redirect back to chat requests page
    exit();
} else {
    $_SESSION['error'] = "Invalid request.";
    header('Location: view_chat_requests.php');
    exit();
}
?>
