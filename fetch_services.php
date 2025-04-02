<?php
include 'db_config.php';  // Include database configuration

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['dentist_id'])) {
    $dentist_id = intval($_GET['dentist_id']);
    
    // Fetch services associated with the dentist
    $stmt = $conn->prepare("
        SELECT s.service_id, s.service_name, s.price 
        FROM dentist_services ds 
        JOIN services s ON ds.service_id = s.service_id 
        WHERE ds.user_id = ?
    ");
    $stmt->bind_param('i', $dentist_id);
    $stmt->execute();
    $stmt->bind_result($service_id, $service_name, $price);

    $services = [];
    while ($stmt->fetch()) {
        $services[] = [
            'service_id' => $service_id,
            'service_name' => $service_name,
            'price' => $price
        ];
    }
    $stmt->close();
    
    header('Content-Type: application/json');
    echo json_encode($services);
    exit();
}

http_response_code(400);
echo json_encode(['error' => 'Invalid request']);
?>
