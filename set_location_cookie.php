<?php
// Check if the location ID is provided
if (isset($_POST['locationId'])) {
    $locationId = $_POST['locationId'];
    
    if (!empty($locationId)) {
        // Set the cookie to expire in 30 days
        setcookie("current_location", $locationId, time() + (30 * 24 * 60 * 60), "/");
        
        // Return a success response
        echo json_encode(['success' => true, 'message' => 'Location cookie set successfully']);
    } else {
        // Delete the cookie by setting it to expire in the past
        setcookie("current_location", "", time() - 3600, "/");
        
        // Return a success response
        echo json_encode(['success' => true, 'message' => 'Location cookie deleted successfully']);
    }
} else {
    // Return an error response
    echo json_encode(['success' => false, 'message' => 'Location ID not provided']);
}
?>