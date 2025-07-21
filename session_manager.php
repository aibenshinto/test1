<?php
/**
 * Session Management Utility
 * Handles multiple user sessions for staff and customers
 * Updated for separate customers and staff tables
 * Supports simultaneous login for different user types
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in as customer
 * @return bool
 */
function isCustomerLoggedIn() {
    return isset($_SESSION['customer_id']) && isset($_SESSION['customer_name']);
}

/**
 * Check if user is logged in as staff
 * @return bool
 */
function isStaffLoggedIn() {
    return isset($_SESSION['staff_id']) && isset($_SESSION['staff_name']);
}

/**
 * Check if user is logged in (either customer or staff)
 * @return bool
 */
function isLoggedIn() {
    return isCustomerLoggedIn() || isStaffLoggedIn();
}

/**
 * Check if user is a customer (for backward compatibility)
 * @return bool
 */
function isCustomer() {
    return isCustomerLoggedIn();
}

/**
 * Check if user is staff (for backward compatibility)
 * @return bool
 */
function isStaff() {
    return isStaffLoggedIn();
}

/**
 * Check if user is delivery staff
 * @return bool
 */
function isDeliveryStaff() {
    return isStaffLoggedIn() && $_SESSION['staff_role'] === 'delivery';
}

/**
 * Check if user is product manager staff
 * @return bool
 */
function isProductManager() {
    return isStaffLoggedIn() && $_SESSION['staff_role'] === 'product_manager';
}

/**
 * Get current user ID (customer or staff)
 * @return int|null
 */
function getCurrentUserId() {
    if (isCustomerLoggedIn()) {
        return $_SESSION['customer_id'];
    }
    if (isStaffLoggedIn()) {
        return $_SESSION['staff_id'];
    }
    return null;
}

/**
 * Get current user name (customer or staff)
 * @return string|null
 */
function getCurrentUsername() {
    if (isCustomerLoggedIn()) {
        return $_SESSION['customer_name'];
    }
    if (isStaffLoggedIn()) {
        return $_SESSION['staff_name'];
    }
    return null;
}

/**
 * Get current user email (customer or staff)
 * @return string|null
 */
function getCurrentUserEmail() {
    if (isCustomerLoggedIn()) {
        return $_SESSION['customer_email'];
    }
    if (isStaffLoggedIn()) {
        return $_SESSION['staff_email'];
    }
    return null;
}

/**
 * Get current user type (customer or staff)
 * @return string|null
 */
function getCurrentUserType() {
    if (isCustomerLoggedIn()) {
        return 'customer';
    }
    if (isStaffLoggedIn()) {
        return 'staff';
    }
    return null;
}

/**
 * Get current user role (for staff: delivery or product_manager)
 * @return string|null
 */
function getCurrentUserRole() {
    if (isStaffLoggedIn()) {
        return $_SESSION['staff_role'];
    }
    return null;
}

/**
 * Redirect user based on their type and role
 */
function redirectByUserType() {
    if (isCustomerLoggedIn()) {
        header("Location: ../customer/customer_dashboard.php");
        exit;
    }
    if (isStaffLoggedIn()) {
        if ($_SESSION['staff_role'] === 'delivery') {
            header("Location: ../staff/delivery_dashboard.php");
        } else {
            header("Location: ../staff/staff_dashboard.php");
        }
        exit;
    }
}

/**
 * Require customer to access page
 */
function requireCustomer() {
    if (!isCustomerLoggedIn()) {
        header("Location: ../customer/login_customer.php");
        exit;
    }
}

/**
 * Require staff to access page
 */
function requireStaff($role = null) {
    if (!isStaffLoggedIn()) {
        header("Location: ../authentication/login.php");
        exit;
    }
    
    if ($role && $_SESSION['staff_role'] !== $role) {
        header("Location: ../authentication/login.php");
        exit;
    }
}

/**
 * Require delivery staff to access page
 */
function requireDeliveryStaff() {
    requireStaff('delivery');
}

/**
 * Require product manager to access page
 */
function requireProductManager() {
    requireStaff('product_manager');
}

/**
 * Logout current user type
 * @param string $userType - 'customer' or 'staff' or 'all'
 */
function logout($userType = 'all') {
    if ($userType === 'customer' || $userType === 'all') {
        unset($_SESSION['customer_id']);
        unset($_SESSION['customer_name']);
        unset($_SESSION['customer_email']);
        unset($_SESSION['customer_login_time']);
    }
    
    if ($userType === 'staff' || $userType === 'all') {
        unset($_SESSION['staff_id']);
        unset($_SESSION['staff_name']);
        unset($_SESSION['staff_email']);
        unset($_SESSION['staff_role']);
        unset($_SESSION['staff_login_time']);
    }
    
    // If no sessions left, destroy the session completely
    if (empty($_SESSION)) {
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
    }
}

/**
 * Check session timeout for specific user type
 * @param string $userType - 'customer' or 'staff'
 * @param int $timeoutMinutes - session timeout in minutes
 * @return bool - true if session is still valid
 */
function checkSessionTimeout($userType = 'all', $timeoutMinutes = 30) {
    $timeout = $timeoutMinutes * 60;
    
    if ($userType === 'customer' || $userType === 'all') {
        if (isset($_SESSION['customer_login_time']) && (time() - $_SESSION['customer_login_time']) > $timeout) {
            logout('customer');
            return false;
        }
    }
    
    if ($userType === 'staff' || $userType === 'all') {
        if (isset($_SESSION['staff_login_time']) && (time() - $_SESSION['staff_login_time']) > $timeout) {
            logout('staff');
            return false;
        }
    }
    
    return true;
}

/**
 * Get staff name specifically (for staff pages)
 * @return string|null
 */
function getStaffName() {
    if (isStaffLoggedIn()) {
        return $_SESSION['staff_name'];
    }
    return null;
}

/**
 * Get customer name specifically (for customer pages)
 * @return string|null
 */
function getCustomerName() {
    if (isCustomerLoggedIn()) {
        return $_SESSION['customer_name'];
    }
    return null;
}
?> 