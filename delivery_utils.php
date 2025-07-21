<?php
/**
 * Delivery Utilities
 * Functions for handling delivery calculations and logistics
 */

// Warehouse coordinates (Kochi, Kerala)
define('WAREHOUSE_LAT', 9.9312);
define('WAREHOUSE_LON', 76.2673);
define('MAX_DELIVERY_DISTANCE', 5.0); // 5 km
define('DELIVERY_FEE', 50.00); // ₹50 for delivery within 5km

/**
 * Calculate distance between two points using Haversine formula
 * @param float $lat1 Latitude of point 1
 * @param float $lon1 Longitude of point 1
 * @param float $lat2 Latitude of point 2
 * @param float $lon2 Longitude of point 2
 * @return float Distance in kilometers
 */
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371; // Earth's radius in kilometers
    
    $latDelta = deg2rad($lat2 - $lat1);
    $lonDelta = deg2rad($lon2 - $lon1);
    
    $a = sin($latDelta / 2) * sin($latDelta / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($lonDelta / 2) * sin($lonDelta / 2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    
    return $earthRadius * $c;
}

/**
 * Calculate distance from warehouse to customer location
 * @param float $customerLat Customer latitude
 * @param float $customerLon Customer longitude
 * @return float Distance in kilometers
 */
function getDistanceFromWarehouse($customerLat, $customerLon) {
    return calculateDistance(WAREHOUSE_LAT, WAREHOUSE_LON, $customerLat, $customerLon);
}

/**
 * Determine if delivery is available based on distance
 * @param float $distance Distance in kilometers
 * @return bool True if delivery is available (within 5km)
 */
function isDeliveryAvailable($distance) {
    return $distance <= MAX_DELIVERY_DISTANCE;
}

/**
 * Calculate delivery fee based on distance
 * @param float $distance Distance in kilometers
 * @return float Delivery fee
 */
function calculateDeliveryFee($distance) {
    if (isDeliveryAvailable($distance)) {
        return DELIVERY_FEE;
    }
    return 0.00; // No delivery fee for pickup
}

/**
 * Get delivery type based on distance
 * @param float $distance Distance in kilometers
 * @return string 'delivery' or 'pickup'
 */
function getDeliveryType($distance) {
    return isDeliveryAvailable($distance) ? 'delivery' : 'pickup';
}

/**
 * Get delivery message for customer
 * @param float $distance Distance in kilometers
 * @return string Message explaining delivery/pickup
 */
function getDeliveryMessage($distance) {
    if (isDeliveryAvailable($distance)) {
        return "Delivery available! Your order will be delivered to your address for ₹" . DELIVERY_FEE;
    } else {
        return "Delivery not available. You'll need to pick up your order from our warehouse in Kochi.";
    }
}

/**
 * Get coordinates from address using Google Geocoding API (if available)
 * Note: This requires a Google Maps API key
 * @param string $address Address to geocode
 * @return array ['lat' => float, 'lng' => float] or null if failed
 */
function getCoordinatesFromAddress($address) {
    // For now, return null - coordinates should be set manually or via API
    // In a real implementation, you would use Google Geocoding API
    return null;
}

/**
 * Update customer coordinates in database
 * @param mysqli $conn Database connection
 * @param int $customerId Customer ID
 * @param float $latitude Latitude
 * @param float $longitude Longitude
 * @return bool Success status
 */
function updateCustomerCoordinates($conn, $customerId, $latitude, $longitude) {
    $stmt = $conn->prepare("UPDATE customers SET latitude = ?, longitude = ? WHERE id = ?");
    $stmt->bind_param("ddi", $latitude, $longitude, $customerId);
    return $stmt->execute();
}

/**
 * Get available delivery staff
 * @param mysqli $conn Database connection
 * @return array Array of delivery staff
 */
function getAvailableDeliveryStaff($conn) {
    $sql = "SELECT id, name, email FROM staff WHERE role = 'delivery' ORDER BY name";
    $result = $conn->query($sql);
    
    $staff = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $staff[] = $row;
        }
    }
    
    return $staff;
}

/**
 * Assign delivery staff to order
 * @param mysqli $conn Database connection
 * @param int $orderId Order ID
 * @param int $staffId Staff ID
 * @return bool Success status
 */
function assignDeliveryStaff($conn, $orderId, $staffId) {
    $stmt = $conn->prepare("UPDATE orders SET delivery_staff_id = ? WHERE id = ?");
    $stmt->bind_param("ii", $staffId, $orderId);
    return $stmt->execute();
}

/**
 * Update order status
 * @param mysqli $conn Database connection
 * @param int $orderId Order ID
 * @param string $status New status
 * @return bool Success status
 */
function updateOrderStatus($conn, $orderId, $status) {
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $orderId);
    return $stmt->execute();
}

/**
 * Get order details with customer and delivery information
 * @param mysqli $conn Database connection
 * @param int $orderId Order ID
 * @return array Order details or null if not found
 */
function getOrderDetails($conn, $orderId) {
    $sql = "SELECT o.*, c.name as customer_name, c.email as customer_email, 
                   c.location as customer_location, c.latitude, c.longitude,
                   s.name as delivery_staff_name
            FROM orders o
            JOIN customers c ON o.customer_id = c.id
            LEFT JOIN staff s ON o.delivery_staff_id = s.id
            WHERE o.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

/**
 * Get orders for delivery staff
 * @param mysqli $conn Database connection
 * @param int $staffId Staff ID (optional, if null gets all delivery orders)
 * @return array Array of orders
 */
function getDeliveryOrders($conn, $staffId = null) {
    $sql = "SELECT o.*, c.name as customer_name, c.location as customer_location,
                   c.latitude, c.longitude
            FROM orders o
            JOIN customers c ON o.customer_id = c.id
            WHERE o.delivery_type = 'delivery'";
    
    if ($staffId) {
        $sql .= " AND o.delivery_staff_id = ?";
    }
    
    $sql .= " ORDER BY o.order_date DESC";
    
    $stmt = $conn->prepare($sql);
    if ($staffId) {
        $stmt->bind_param("i", $staffId);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    
    return $orders;
}
?> 