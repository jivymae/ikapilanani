<?php
include 'db_config.php'; // Include your database configuration

// Get the specialization IDs from the AJAX request
$data = json_decode(file_get_contents('php://input'), true);
$specialization_ids = $data['specialization_ids'] ?? [];

// Prepare the SQL query
if (!empty($specialization_ids)) {
    $placeholders = implode(',', array_fill(0, count($specialization_ids), '?'));
    $stmt = $conn->prepare("SELECT s.service_id, s.service_name FROM services s JOIN service_specialization ss ON s.service_id = ss.service_id WHERE ss.spec_id IN ($placeholders)");
    $stmt->bind_param(str_repeat('i', count($specialization_ids)), ...$specialization_ids);
    $stmt->execute();

    $result = $stmt->get_result();
    $services = [];
    while ($row = $result->fetch_assoc()) {
        $services[] = $row;
    }

    $stmt->close();
} else {
    $services = [];
}

// Close the connection
$conn->close();

// Return the services as a JSON response
header('Content-Type: application/json');
echo json_encode($services);
?>