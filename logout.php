<?php
session_start();

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    // Store user info for logging (optional)
    $user_id = $_SESSION['user_id'];
    $role_id = $_SESSION['role_id'] ?? null;
    
    // Unset all session variables
    $_SESSION = array();
    
    // Destroy the session cookie if it exists
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
    
    // Set logout success message
    session_start();
    $_SESSION['login_messages'] = [
        'type' => 'success',
        'message' => 'You have been successfully logged out.'
    ];
    
    // Optional: Log the logout activity (you can implement this later)
    // logActivity($user_id, 'logout', 'User logged out successfully');
}

// Redirect to login page
header("Location: index.php");
exit();
?>
