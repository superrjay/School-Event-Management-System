<?php
require_once 'config.php';

function updateEventStatuses() {
    $conn = getDBConnection();
    $current_datetime = date('Y-m-d H:i:s');
    
    // Update ongoing events
    $conn->query("UPDATE events SET status = 'ongoing' 
                  WHERE start_datetime <= '$current_datetime' 
                  AND end_datetime >= '$current_datetime' 
                  AND status = 'upcoming'");
    
    // Update completed events
    $conn->query("UPDATE events SET status = 'completed' 
                  WHERE end_datetime < '$current_datetime' 
                  AND status IN ('upcoming', 'ongoing')");
    
    $updated = $conn->affected_rows;
    $conn->close();
    
    return $updated;
}

// Run if called directly (for testing)
if (php_sapi_name() === 'cli') {
    $updated = updateEventStatuses();
    echo "Updated $updated event statuses\n";
} else {
    // If called via web, require authentication
    requireLogin();
    if (hasRole(['Admin', 'Staff'])) {
        $updated = updateEventStatuses();
        echo json_encode(['success' => true, 'message' => "Updated $updated event statuses"]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
    }
}
?>