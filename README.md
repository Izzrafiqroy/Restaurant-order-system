# Restaurant Order System

A comprehensive Restaurant Order System built with PHP and SQL database, supporting multiple ordering channels and an intuitive admin dashboard.

## Features

- **Online Ordering**: Customers can browse the menu and place orders through a web interface.
- **Walk-In Ordering**: Staff can quickly enter orders for dine-in or takeaway customers at the counter.
- **Table QR Scan Ordering**: Dine-in guests can scan a unique QR code at their table to order directly from their phones.
- **Admin Dashboard**: Manage menu items, view order reports, track sales, and oversee all restaurant operations.
- **Order Management**: Real-time updates and notifications for new orders, order status tracking, and kitchen display integration.
- **User Roles**: Support for admin, kitchen staff, waiters, and customers.
- **Responsive Design**: Mobile-friendly interfaces for customers and staff.

## Technologies Used

- **Backend:** PHP
- **Database:** MySQL (SQL)
- **Frontend:** HTML, CSS, JavaScript (with Bootstrap or similar framework)
- **QR Code:** PHP QR code library for table order system

## System Modules

- **Customer Module:**
  - Online menu browsing and ordering
  - Order status tracking
  - Table QR code scan for menu and order
- **Walk-In/Staff Module:**
  - Quick order entry for walk-in customers
  - Order assignment to tables or takeaway
- **Admin Module:**
  - Menu management (add/edit/delete items, categories)
  - Order management and reporting
  - User management
  - Sales analytics dashboard
- **Kitchen/Order Processing:**
  - View incoming orders in real time
  - Update order status (preparing, ready, completed)

## Installation

1. **Clone the repository:**
   ```bash
   git clone https://github.com/Izzrafiqroy/Restaurant-order-system.git
   ```

2. **Configure the Database:**
   - Import the provided `restaurant_db.sql` file into your MySQL server.
   - The admin username and password must be this $admin_username = 'admin';
      $admin_password = 'Admin19123';

3. **Set Up the Server:**
   - Make sure you have PHP and MySQL/MariaDB installed.
   - Place the project folder in your web server's root directory (e.g., `htdocs` for XAMPP).
   - Access the system via `http://localhost/restaurant-order-system/`

4. **QR Code Setup:**
   - Ensure the PHP QR code library is included (or install via Composer).
   - Each table should have its unique QR code pointing to the table order page with the table identifier.

## Usage

- **For Customers:**  
  - Visit the website for online ordering or scan the table QR code for dine-in.
- **For Staff:**  
  - Use the Walk-In Order page for counter service.
- **For Admins:**  
  - Log in to the Admin Dashboard to manage all aspects of the restaurant.

## License

This project is licensed under the MIT License.

## Contact

For questions or support, contact [izzrafiq12@gmail.com](mailto:izzrafiq12@gmail..com).
