<?php
// =============================================
// CLASS DEMONSTRATION 4: Logout Functionality
// =============================================

// Start session
session_start();

// Destroy all session data (Demonstration 4)
$_SESSION = array();

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: login.php?message=Logged out successfully');
exit;
?>