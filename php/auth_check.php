
<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
include __DIR__ . '/db_connect.php';

/**
 * Check if user has the required role
 * @param string $required_role - The role required ('parent' or 'child')
 */
function requireRole($required_role) {
    if (!isset($_SESSION['user_id'])) {
        // User not logged in
        if ($required_role === 'parent') {
            header("Location: /smart-home-system/php/login.php");
        } else {
            header("Location: /smart-home-system/child_login.php");
        }
        exit;
    }

    // For parent role
    if ($required_role === 'parent') {
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'parent') {
            header("Location: /smart-home-system/php/login.php");
            exit;
        }
    }

    // For child role
    if ($required_role === 'child') {
        if (!isset($_SESSION['child_id']) || !isset($_SESSION['child_name'])) {
            header("Location: /smart-home-system/child_login.php");
            exit;
        }
    }
}

/**
 * Check if user is logged in (any role)
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) || isset($_SESSION['child_id']);
}

/**
 * Get current user ID
 */
function getCurrentUserId() {
    if (isset($_SESSION['user_id'])) {
        return $_SESSION['user_id'];
    }
    if (isset($_SESSION['child_id'])) {
        return $_SESSION['child_id'];
    }
    return null;
}

/**
 * Get current user role
 */
function getCurrentRole() {
    if (isset($_SESSION['role'])) {
        return $_SESSION['role'];
    }
    if (isset($_SESSION['child_id'])) {
        return 'child';
    }
    return null;
}
?>