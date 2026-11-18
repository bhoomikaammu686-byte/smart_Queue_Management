# Smart Queue Management System

A web-based queue management system designed to simplify customer handling, reduce waiting time, and improve service efficiency through digital token generation and real-time queue tracking.

## 📌 Project Overview

The **Smart Queue Management System** is a web application developed to manage customer queues efficiently. Instead of customers waiting in an unorganized physical queue, the system allows administrators to generate digital tokens, monitor waiting customers, call the next token, and update service status.

The system provides a centralized dashboard to track queue statistics and manage multiple service categories such as Document Verification, Customer Support, Payment Service, and General Enquiry.

## 🎯 Objectives

- Reduce customer waiting time.
- Organize customer queues systematically.
- Generate digital queue tokens automatically.
- Simplify queue handling for administrators.
- Track customer service status in real time.
- Improve service efficiency and customer experience.

## ✨ Features

- **User Registration** – Create an administrator account.
- **Secure Login** – Authentication system for accessing the application.
- **Dashboard** – View queue statistics and service information.
- **Token Generation** – Generate unique queue tokens for customers.
- **Queue Management** – View and manage waiting customers.
- **Call Next Token** – Call the next customer in the queue.
- **Service Status Tracking** – Track Waiting, Serving, and Completed tokens.
- **Service Categories** – Manage different customer service types.
- **Customer Records** – Store customer details and queue information.
- **Dynamic Dashboard** – Automatically retrieve queue information from the database.

## 🛠️ Technologies Used

| Technology | Purpose |
|---|---|
| HTML5 | Web page structure |
| CSS3 | Styling and responsive interface |
| JavaScript | Interactive frontend functionality |
| PHP | Server-side processing and API |
| MySQL | Database management |
| MySQLi | PHP–MySQL connectivity |

## 📂 Project Structure

```text
smart_Queue_Management/
│
├── api.php
├── db.php
├── dashboard.html
├── index.html
├── login.html
├── login_api.php
├── register.html
├── register_api.php
├── script.js
├── style.css
└── README.md
```

## 🗄️ Database

**Database Name:** `smart_queue_db`

The application uses MySQL to store administrator accounts and customer queue details.

### Queue Token Information

The queue system stores information such as:

- Token ID
- Token Number
- Customer Name
- Service Type
- Queue Status
- Token Creation Time
- Called Time
- Completion Time

### Queue Status

| Status | Description |
|---|---|
| Waiting | Customer is waiting for service |
| Serving | Customer is currently being served |
| Completed | Customer service has been completed |

## 💻 Installation and Setup

### Prerequisites

Install the following software:

- PHP 8 or above
- MySQL Server
- MySQL Workbench (optional)
- Web browser

### Step 1: Clone the Repository

```bash
git clone https://github.com/bhoomikaammu686-byte/smart_Queue_Management.git
```

Navigate to the project folder:

```bash
cd smart_Queue_Management
```

### Step 2: Configure MySQL

Create the database in MySQL:

```sql
CREATE DATABASE smart_queue_db;
```

Select the database:

```sql
USE smart_queue_db;
```

Create the queue token table:

```sql
CREATE TABLE queue_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token_no VARCHAR(20) NOT NULL UNIQUE,
    customer_name VARCHAR(100) NOT NULL,
    service VARCHAR(100) NOT NULL,
    status ENUM('Waiting', 'Serving', 'Completed')
        DEFAULT 'Waiting',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    called_at DATETIME NULL,
    completed_at DATETIME NULL
);
```

Create the `users` table according to the registration and login backend structure.

### Step 3: Configure Database Connection

Open `db.php` and update the database connection settings according to your MySQL configuration.

```php
$host = "localhost";
$username = "root";
$password = "YOUR_MYSQL_PASSWORD";
$database = "smart_queue_db";
$port = 3307;
```

Change the port and password if your MySQL installation uses different settings.

### Step 4: Start the PHP Server

Open PowerShell inside the project folder and run:

```powershell
php -S localhost:8000
```

If PHP is not added to PATH, run it using your PHP installation path:

```powershell
& "C:\php\php.exe" -S localhost:8000
```

### Step 5: Open the Application

Open your browser and navigate to:

**http://localhost:8000**

Register an account, log in, and start managing the queue.

## 🔄 System Workflow

1. Administrator registers in the application.
2. Administrator logs in to the system.
3. Dashboard displays queue statistics.
4. A new customer token is generated.
5. Customer is added to the waiting queue.
6. Administrator calls the next waiting token.
7. Token status changes to Serving.
8. After service completion, status changes to Completed.
9. Dashboard statistics are updated according to queue activity.

## 📊 Modules

### 1. Authentication Module
Provides user registration and login functionality.

### 2. Dashboard Module
Displays total tokens, waiting customers, currently serving customers, completed tokens, and service-wise information.

### 3. Token Generation Module
Creates a unique token number and stores customer and service details.

### 4. Queue Management Module
Displays customer queue information and allows administrators to call and complete tokens.

### 5. Service Management Module
Organizes customers based on service categories.

### 6. Database Module
Stores and retrieves user and queue data using MySQL.

## 🚀 Future Enhancements

- Customer display screen for currently serving tokens.
- SMS notifications for customers.
- Estimated waiting-time calculation.
- QR-code-based token access.
- Multi-counter queue management.
- Online token booking.
- Queue analytics and reporting.

## 🎓 Project Information

**Project Title:** Smart Queue Management System  
**Project Type:** Academic / Web Application Project  
**Frontend:** HTML, CSS, JavaScript  
**Backend:** PHP  
**Database:** MySQL

## 👩‍💻 Developer

Developed as an academic project to demonstrate web development, database connectivity, authentication, and digital queue management.

## 📄 License

This project is intended for educational and academic purposes.

---

⭐ If you find this project useful, consider giving the repository a star.
