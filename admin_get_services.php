<?php
include 'db_config.php';

if (isset($_GET['specializations'])) {
    $specializations = explode(',', $_GET['specializations']);
    
    // Prepare the query to fetch services associated with the selected specializations
    $placeholders = implode(',', array_fill(0, count($specializations), '?'));
    $stmt = $conn->prepare("
        SELECT s.service_id, s.service_name
        FROM services s
        JOIN service_specialization ss ON s.service_id = ss.service_id
        WHERE ss.spec_id IN ($placeholders)
    ");
    
    // Bind the specialization values dynamically
    $types = str_repeat('i', count($specializations));
    $stmt->bind_param($types, ...$specializations);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $services = $result->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode($services);
    $stmt->close();
}
?>
