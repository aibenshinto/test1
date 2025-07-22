@echo off
rem This batch file creates a fresh database for the e-commerce project.

echo ==========================================================
echo This will create a new database called 'ecommerce_jul21' with:
echo 1. All necessary tables
echo 2. Sample data for products, staff, and customers
echo - Separate customers and staff tables
echo - All product, order, and cart tables
echo - Sample data for testing
echo - Proper foreign key relationships
echo - Delivery system with distance calculation
echo.

echo Press any key to continue...
pause

echo.
echo Creating database and tables...
C:\xampp\mysql\bin\mysql.exe -u root < create_fresh_database.sql

echo.
echo Database creation completed!
echo.
echo Test credentials:
echo - Customer: alice@example.com / password123
echo - Product Manager: manager@example.com / password123
echo - Delivery Staff: delivery@example.com / password123
echo.
echo Next steps:
echo 1. Update your db_connect.php to use 'ecommerce_jul21' database
echo 2. Test the application with the sample users
echo 3. Test delivery system: http://localhost/test1/test_delivery_system.php
echo.
pause 