# GuviHCL Internship Authentication System

A modern, secure authentication system built with HTML, CSS, JavaScript (jQuery), PHP, MySQL, MongoDB, and Redis.

![Project Status](https://img.shields.io/badge/status-ready-brightgreen)
![PHP Version](https://img.shields.io/badge/php-%3E%3D7.4-blue)
![License](https://img.shields.io/badge/license-MIT-green)

---

## 📋 Table of Contents

- [Features](#features)
- [Tech Stack](#tech-stack)
- [Prerequisites](#prerequisites)
- [Installation Guide](#installation-guide)
- [Project Structure](#project-structure)
- [Configuration](#configuration)
- [Usage](#usage)
- [API Documentation](#api-documentation)
- [Testing](#testing)
- [Troubleshooting](#troubleshooting)
- [Security Features](#security-features)
- [Screenshots](#screenshots)
- [Contributing](#contributing)

---

## ✨ Features

- **Modern UI/UX**: Glassmorphism design with smooth animations
- **Secure Authentication**: BCrypt password hashing, session management
- **Complete Flow**: Register → Login → Profile → Update → Logout
- **Responsive Design**: Works on all devices (mobile, tablet, desktop)
- **Real-time Validation**: Client-side and server-side form validation
- **Session Management**: Redis-based backend sessions, localStorage frontend
- **Profile Management**: Update age, DOB, contact, address, bio
- **Error Handling**: User-friendly error messages and alerts
- **Code Separation**: HTML, CSS, JS, PHP in separate files (as required)
- **jQuery AJAX**: All API calls without form submissions (as required)

---

## 🛠️ Tech Stack

### Frontend
- HTML5
- CSS3 (Glassmorphism, Gradients, Animations)
- JavaScript (ES6+)
- jQuery 3.7.1
- Bootstrap 5.3.2

### Backend
- PHP 7.4+
- MySQL 8.0+ (User authentication data)
- MongoDB 4.4+ (Profile data storage)
- Redis 6.0+ (Session management)

---

## 📦 Prerequisites

Before you begin, ensure you have the following installed:

### 1. XAMPP / WAMP / LAMP
- **Download**: [XAMPP Official Site](https://www.apachefriends.org/)
- Includes: Apache, PHP, MySQL
- **Start**: Apache and MySQL services

### 2. Redis
- **Windows**: [Redis for Windows](https://github.com/microsoftarchive/redis/releases)
  - Download, extract, and run `redis-server.exe`
- **Linux/Mac**: 
  ```bash
  sudo apt-get install redis-server
  redis-server
  ```

### 3. MongoDB
- **Download**: [MongoDB Community Server](https://www.mongodb.com/try/download/community)
- **Start Service**:
  - Windows: `net start MongoDB`
  - Linux: `sudo systemctl start mongod`

### 4. Composer
- **Download**: [Composer Official Site](https://getcomposer.org/download/)
- Required for MongoDB PHP library

### 5. PHP Extensions
Edit `php.ini` and enable:
```ini
extension=mysqli
extension=redis
extension=mongodb
```

---

## 🚀 Installation Guide

### Step 1: Download Project

1. Create project folder in `htdocs`:
   ```
   C:\xampp\htdocs\internship_app\
   ```

2. Copy all project files to this folder

### Step 2: Setup Database

1. Open phpMyAdmin: `http://localhost/phpmyadmin`

2. Import `database.sql` or run these queries:

```sql
CREATE DATABASE IF NOT EXISTS internship_auth
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE internship_auth;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL UNIQUE,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Step 3: Install MongoDB PHP Library

1. Open Command Prompt/Terminal
2. Navigate to project PHP folder:
   ```bash
   cd C:\xampp\htdocs\internship_app\assets\php
   ```
3. Run:
   ```bash
   composer require mongodb/mongodb
   ```

### Step 4: Start Services

1. **XAMPP**: Start Apache and MySQL
2. **Redis**: Run `redis-server.exe` (or `redis-server` on Linux)
3. **MongoDB**: Ensure MongoDB service is running

### Step 5: Configure (if needed)

Edit `assets/php/config.php` if your credentials differ:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'internship_auth');
```

### Step 6: Access Application

Open browser and navigate to:
```
http://localhost/internship_app/
```

---

## 📂 Project Structure

```
internship_app/
│
├── assets/
│   ├── css/
│   │   └── style.css              # All styling (glassmorphism, animations)
│   │
│   ├── js/
│   │   ├── register.js            # Registration form handler
│   │   ├── login.js               # Login form handler
│   │   └── profile.js             # Profile management handler
│   │
│   └── php/
│       ├── config.php             # Database connections & helper functions
│       ├── register.php           # Registration API endpoint
│       ├── login.php              # Login API endpoint
│       ├── profile.php            # Profile CRUD API endpoint
│       └── vendor/                # Composer dependencies (MongoDB)
│
├── index.html                     # Landing page
├── register.html                  # Registration page
├── login.html                     # Login page
├── profile.html                   # Profile page
├── database.sql                   # Database schema
└── README.md                      # This file
```

---

## ⚙️ Configuration

### Database Configuration

File: `assets/php/config.php`

```php
// MySQL
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'internship_auth');

// Redis
$redis->connect('127.0.0.1', 6379);

// MongoDB
$mongoClient = new MongoDB\Client("mongodb://127.0.0.1:27017");
```

### Session Configuration

- **Expiry**: 1 hour (3600 seconds)
- **Storage**: Redis backend, localStorage frontend
- **Token Length**: 64 characters (secure random)

---

## 📖 Usage

### 1. Register New Account

1. Go to: `http://localhost/internship_app/register.html`
2. Fill in:
   - Email address
   - Username (3+ characters, alphanumeric + underscore)
   - Password (6+ characters)
   - Confirm password
3. Click "Create Account"
4. Success → Redirects to Login

### 2. Login

1. Go to: `http://localhost/internship_app/login.html`
2. Enter email/username and password
3. Optional: Check "Remember me"
4. Click "Sign In"
5. Success → Redirects to Profile

### 3. Manage Profile

1. After login, automatically at profile page
2. View read-only: Username, Email, Member Since
3. Update editable fields:
   - Age (1-120)
   - Date of Birth
   - Contact Number
   - Address
   - Bio
4. Click "Save Changes"

### 4. Logout

1. Click "Logout" button in navbar
2. Confirms logout
3. Clears session → Redirects to Login

---

## 🔌 API Documentation

### Base URL
```
http://localhost/internship_app/assets/php/
```

---

### 1. Register

**Endpoint**: `POST /register.php`

**Request Body**:
```json
{
  "email": "user@example.com",
  "username": "johndoe",
  "password": "secure123"
}
```

**Success Response** (201):
```json
{
  "success": true,
  "message": "Registration successful! You can now login.",
  "user_id": 1
}
```

**Error Response** (400/409):
```json
{
  "success": false,
  "message": "Email is already registered"
}
```

---

### 2. Login

**Endpoint**: `POST /login.php`

**Request Body**:
```json
{
  "emailOrUsername": "johndoe",
  "password": "secure123"
}
```

**Success Response** (200):
```json
{
  "success": true,
  "message": "Login successful",
  "token": "a1b2c3d4...",
  "user": {
    "id": 1,
    "username": "johndoe",
    "email": "user@example.com"
  }
}
```

**Error Response** (401):
```json
{
  "success": false,
  "message": "Invalid email/username or password"
}
```

---

### 3. Get Profile

**Endpoint**: `POST /profile.php`

**Request Body**:
```json
{
  "action": "get",
  "token": "session_token_here"
}
```

**Success Response** (200):
```json
{
  "success": true,
  "message": "Profile data retrieved",
  "user": {
    "id": 1,
    "username": "johndoe",
    "email": "user@example.com",
    "age": 25,
    "dob": "1999-01-15",
    "contact": "1234567890",
    "address": "123 Main St",
    "bio": "Software developer",
    "created_at": "2024-01-01 10:00:00"
  }
}
```

---

### 4. Update Profile

**Endpoint**: `POST /profile.php`

**Request Body**:
```json
{
  "action": "update",
  "token": "session_token_here",
  "age": 26,
  "dob": "1999-01-15",
  "contact": "9876543210",
  "address": "456 Oak Ave",
  "bio": "Full-stack developer"
}
```

**Success Response** (200):
```json
{
  "success": true,
  "message": "Profile updated successfully"
}
```

---

### 5. Logout

**Endpoint**: `POST /profile.php`

**Request Body**:
```json
{
  "action": "logout",
  "token": "session_token_here"
}
```

**Success Response** (200):
```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

---

## 🧪 Testing

### Test Account

Default test account (if you ran the SQL with sample data):
- **Email**: test@example.com
- **Username**: testuser
- **Password**: test123

### Manual Testing Checklist

- [ ] Register with valid data
- [ ] Register with existing email (should fail)
- [ ] Register with invalid email format (should fail)
- [ ] Register with weak password (should fail)
- [ ] Login with correct credentials
- [ ] Login with wrong credentials (should fail)
- [ ] Access profile without login (should redirect)
- [ ] Update profile fields
- [ ] Logout and verify session cleared
- [ ] Test "Remember me" functionality
- [ ] Test responsive design on mobile
- [ ] Test all validation messages

---

## 🐛 Troubleshooting

### Issue: "Database connection failed"

**Solution**:
1. Ensure MySQL is running in XAMPP
2. Check credentials in `config.php`
3. Verify database `internship_auth` exists

---

### Issue: "Redis connection failed"

**Solution**:
1. Start Redis server: `redis-server.exe`
2. Test connection: `redis-cli ping` (should return PONG)
3. Check if port 6379 is open

---

### Issue: "MongoDB library not installed"

**Solution**:
```bash
cd assets/php
composer require mongodb/mongodb
```

---

### Issue: "Session expired" immediately after login

**Solution**:
1. Ensure Redis is running
2. Check Redis connection in `config.php`
3. Verify localStorage is enabled in browser

---

### Issue: White screen / No output

**Solution**:
1. Enable error display in `php.ini`:
   ```ini
   display_errors = On
   error_reporting = E_ALL
   ```
2. Check Apache error logs: `xampp/apache/logs/error.log`
3. Check browser console for JavaScript errors

---

## 🔒 Security Features

1. **Password Hashing**: BCrypt with cost factor 12
2. **Prepared Statements**: All MySQL queries use prepared statements
3. **Input Sanitization**: All user inputs are sanitized
4. **Session Security**: 
   - Secure random token generation
   - 1-hour session expiry
   - Redis-based backend storage
5. **CORS Headers**: Proper CORS configuration
6. **XSS Protection**: HTML special chars encoding
7. **SQL Injection Prevention**: Parameterized queries only

---

## 📸 Screenshots

*(Add screenshots of your application here)*

1. **Landing Page**: Modern glassmorphism design
2. **Register Page**: Clean registration form
3. **Login Page**: Professional login interface
4. **Profile Page**: User dashboard with editable fields

---

## ✅ Internship Requirements Checklist

- [x] Register → Login → Profile flow
- [x] HTML, CSS, JS, PHP in separate files
- [x] jQuery AJAX only (no form submissions)
- [x] Bootstrap for responsive design
- [x] MySQL with prepared statements
- [x] MongoDB for profile storage
- [x] Redis for session management
- [x] localStorage for client session (no PHP sessions)
- [x] Modern, professional UI
- [x] Complete documentation

---

## 🤝 Contributing

This is an internship project. For improvements:

1. Fork the repository
2. Create feature branch: `git checkout -b feature-name`
3. Commit changes: `git commit -m 'Add feature'`
4. Push to branch: `git push origin feature-name`
5. Submit pull request

---

## 📝 License

This project is open source and available under the [MIT License](LICENSE).

---

## 👨‍💻 Developer

**Your Name**
- Email: your.email@example.com
- GitHub: [@yourusername](https://github.com/yourusername)
- LinkedIn: [Your Profile](https://linkedin.com/in/yourprofile)

---

## 🙏 Acknowledgments

- GuviHCL for the internship opportunity
- Bootstrap team for the responsive framework
- MongoDB, Redis communities for excellent documentation

---

## 📞 Support

If you encounter any issues or have questions:

1. Check the [Troubleshooting](#troubleshooting) section
2. Review the [API Documentation](#api-documentation)
3. Contact: your.email@example.com

---

**Made with ❤️ for GuviHCL Internship**#   G U V I _ H C L _ I N T E R N S H I P  
 