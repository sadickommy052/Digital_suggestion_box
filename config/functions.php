<?php
// =====================
// ACTIVITY LOG FUNCTION
// =====================
function logActivity($user_id, $username, $action, $details = null){
    global $conn;
    
    // Check if connection exists
    if(!isset($conn) || !$conn){
        error_log("Database connection not available for logging");
        return false;
    }
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    try {
        $stmt = $conn->prepare("
            INSERT INTO activity_logs (user_id, username, action, details, ip_address, user_agent, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("isssss", $user_id, $username, $action, $details, $ip, $user_agent);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    } catch (Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
        return false;
    }
}
?>