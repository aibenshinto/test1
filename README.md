# E-Commerce Application

A comprehensive e-commerce platform with customer and staff management, product catalog, shopping cart, order processing, and delivery management.

## Features

### Customer Features
- **User Registration & Authentication**: Secure customer registration with location tracking
- **Product Browsing**: View products with images, descriptions, and prices
- **Shopping Cart**: Add/remove items, update quantities
- **Order Management**: Place orders, view order history with delivery tracking
- **Product Q&A**: Ask questions about products
- **Delivery System**: Automatic distance calculation and delivery fee determination

### Staff Features
- **Product Management**: Add, edit, and manage product catalog
- **Order Processing**: View and manage customer orders
- **Customer Support**: Answer product questions
- **Delivery Management**: Track delivery orders, update status, assign delivery staff

### Delivery System
- **Distance Calculation**: Automatic calculation of distance from warehouse (Kochi)
- **Delivery Availability**: Delivery available within 5km of warehouse
- **Delivery Fee**: ₹50 for delivery within 5km, free pickup for distant customers
- **Status Tracking**: Real-time order status updates
- **Staff Assignment**: Delivery staff can assign orders to themselves

## User Roles

### Customer
- Browse products
- Add items to cart
- Place orders
- Track order status and delivery
- Ask product questions

### Product Manager Staff
- Manage product catalog
- Handle customer questions
- View order statistics

### Delivery Staff
- View delivery orders
- Assign orders to themselves
- Update order status
- Manage pickup orders

## Database Structure

### Core Tables
- `customers`: Customer information with location coordinates
- `staff`: Staff accounts with roles (delivery, product_manager)
- `products`: Product catalog with images and pricing
- `orders`: Order information with delivery details
- `order_items`: Individual items in each order
- `cart_items`: Shopping cart contents
- `product_questions`: Customer questions and staff answers

### Delivery System
- **Warehouse Location**: Kochi, Kerala (9.9312, 76.2673)
- **Delivery Radius**: 5km from warehouse
- **Delivery Fee**: ₹50 for delivery, free for pickup
- **Order Statuses**: Pending → Processing → Ready for Pickup/Out for Delivery → Delivered

## Installation & Setup

### Prerequisites
- XAMPP (Apache + MySQL + PHP)
- Web browser

### Setup Instructions

1. **Clone/Download** the project to your XAMPP htdocs folder
2. **Start XAMPP** and ensure Apache and MySQL are running
3. **Create Database**:
   ```bash
   # Option 1: Run the batch file
   create_fresh_db.bat
   
   # Option 2: Manual database creation
   mysql -u root -p < create_fresh_database.sql
   ```
4. **Configure Database Connection**:
   - Edit `db_connect.php` if needed
   - Default: localhost, root, no password, database: ecommerce_jun19
5. **Access the Application**:
   - Main URL: `http://localhost/test1/`
   - Customer registration: `http://localhost/test1/customer/register_customer.php`
   - Staff registration: `http://localhost/test1/staff/register_staff.php`

### Test the Delivery System
Run the delivery system test:
```
http://localhost/test1/test_delivery_system.php
```

## Sample Data

The database comes with sample data:
- **Products**: iPhone 15 Pro, Samsung Galaxy S24, MacBook Air, etc.
- **Staff**: 
  - Product Manager: manager@example.com (password: password123)
  - Delivery Staff: delivery@example.com, mike@example.com (password: password123)
- **Customers**: Sample customers with different locations
- **Orders**: Sample orders with delivery information

## Delivery System Details

### Distance Calculation
- Uses Haversine formula for accurate distance calculation
- Warehouse coordinates: 9.9312°N, 76.2673°E (Kochi, Kerala)
- Distance calculated in kilometers

### Delivery Rules
- **Within 5km**: Delivery available for ₹50
- **Beyond 5km**: Customer must pick up from warehouse
- **Pickup Orders**: Free, no delivery fee

### Order Status Flow
1. **Pending**: Order placed, awaiting processing
2. **Processing**: Order being prepared
3. **Ready for Pickup**: Order ready for customer pickup
4. **Out for Delivery**: Delivery staff en route
5. **Delivered**: Order successfully delivered
6. **Cancelled**: Order cancelled

### Staff Workflow
1. **Product Manager**: Processes orders, marks as "Ready for Pickup"
2. **Delivery Staff**: 
   - Views unassigned delivery orders
   - Assigns orders to themselves
   - Updates status: "Out for Delivery" → "Delivered"
   - Manages pickup orders

## File Structure

```
test1/
├── authentication/          # Login/logout pages
├── customer/               # Customer-facing pages
│   ├── customer_dashboard.php
│   ├── customer_cart.php
│   ├── checkout.php
│   ├── customer_orders.php
│   ├── register_customer.php
│   └── ...
├── staff/                  # Staff management pages
│   ├── staff_dashboard.php
│   ├── delivery_dashboard.php
│   ├── staff_products.php
│   ├── staff_qna.php
│   └── ...
├── uploads/               # Product images
├── db_connect.php         # Database connection
├── session_manager.php    # Session management
├── delivery_utils.php     # Delivery system utilities
├── create_fresh_database.sql
└── README.md
```

## Security Features

- **Password Hashing**: All passwords are securely hashed
- **Session Management**: Secure session handling with timeout
- **Role-Based Access**: Different access levels for customers and staff
- **SQL Injection Prevention**: Prepared statements throughout
- **XSS Prevention**: Output escaping for user data

## Testing

### Test Files
- `test_delivery_system.php`: Tests delivery calculations and database integration
- `test_sessions.php`: Tests session management
- `test_new_database.php`: Tests database connectivity

### Manual Testing
1. **Customer Flow**: Register → Browse → Add to Cart → Checkout → Track Order
2. **Staff Flow**: Login → Manage Products/Orders → Update Status
3. **Delivery Flow**: Assign Orders → Update Status → Complete Delivery

## Troubleshooting

### Common Issues
1. **Database Connection Error**: Check XAMPP MySQL service
2. **Image Upload Issues**: Ensure uploads/ directory is writable
3. **Session Issues**: Check PHP session configuration
4. **Delivery Calculation**: Verify customer coordinates are set

### Support
For issues or questions, check the test files or review the database structure in `create_fresh_database.sql`.

## Future Enhancements

- Google Maps integration for automatic coordinate detection
- Real-time delivery tracking
- SMS/Email notifications
- Payment gateway integration
- Advanced analytics and reporting
- Mobile app development 