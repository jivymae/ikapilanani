<?php
include 'login_check.php'; // Ensure user is logged in
include 'db_config.php';
include 'admin_check.php'; // Include the admin check helper

// Check if the page is allowed
$page = basename($_SERVER['PHP_SELF']);
if (!isAdminPage($page)) {
    header('Location: unauthorized.php');
    exit();
}

// Get the service ID from the query string
$service_id = intval($_GET['id']);

// Delete the service from the database
$stmt = $conn->prepare("DELETE FROM services WHERE service_id = ?");
$stmt->bind_param('i', $service_id);
if ($stmt->execute()) {
    header('Location: admin_show_services.php?message=Service+deleted+successfully');
} else {
    echo "Error deleting service: " . $stmt->error;
}
$stmt->close();
$conn->close();
?>
