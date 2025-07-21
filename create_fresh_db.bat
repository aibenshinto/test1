@echo off
echo Creating Fresh E-commerce Database
echo ===================================
echo.

echo This will create a new database called 'ecommerce_jun19' with:
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
echo 1. Update your db_connect.php to use 'ecommerce_jun19' database
echo 2. Test the application with the sample users
echo 3. Test delivery system: http://localhost/test1/test_delivery_system.php
echo.
pause 