<?php
include 'login_check.php'; // Ensure user is logged in
include 'db_config.php'; // Include your database configuration
include 'admin_check.php'; // Include the admin check helper

// Check if request is made via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve the specialization IDs from the request
    $data = json_decode(file_get_contents('php://input'), true);
    $specialization_ids = $data['specialization_ids'] ?? [];

    // Sanitize the IDs to avoid SQL injection
    $sanitized_ids = array_filter($specialization_ids, 'is_numeric');

    // Prepare an array for the results
    $services = [];

    if (!empty($sanitized_ids)) {
        // Create a string of placeholders for the prepared statement
        $placeholders = implode(',', array_fill(0, count($sanitized_ids), '?'));
        
        // Prepare the SQL statement
        $stmt = $conn->prepare("SELECT s.service_id, s.service_name FROM services s 
                                  JOIN service_specialization ss ON s.service_id = ss.service_id 
                                  WHERE ss.spec_id IN ($placeholders)");

        // Check if the statement was prepared successfully
        if ($stmt) {
            // Bind the parameters dynamically
            $stmt->bind_param(str_repeat('i', count($sanitized_ids)), ...$sanitized_ids);
            $stmt->execute();
            $result = $stmt->get_result();

            // Fetch the services
            while ($row = $result->fetch_assoc()) {
                $services[] = $row;
            }
            $stmt->close();
        } else {
            // Handle SQL preparation error
            http_response_code(500);
            echo json_encode(['error' => 'Failed to prepare the SQL statement.']);
            exit();
        }
    }

    // Return the services as JSON
    header('Content-Type: application/json');
    echo json_encode($services);
} else {
    // Handle invalid requests
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}

// Close the database connection
$conn->close();
?>
