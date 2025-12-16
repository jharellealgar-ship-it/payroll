# Installation Guide
## Employee Payroll Management System

### Quick Start Guide

#### Step 1: Database Setup

1. **Start XAMPP/WAMP**
   - Start Apache and MySQL services

2. **Create Database**
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Click "New" to create a database
   - Name it: `payroll_management`
   - Or import the SQL file directly:
     - Click "Import" tab
     - Choose file: `database/schema.sql`
     - Click "Go"

#### Step 2: Configure Database Connection

1. **Edit Database Configuration**
   - Open `config/database.php`
   - Update if needed:
     ```php
     private $host = 'localhost';
     private $db_name = 'payroll_management';
     private $username = 'root';      // Your MySQL username
     private $password = '';          // Your MySQL password
     ```

#### Step 3: Configure Base URL

1. **Edit System Configuration**
   - Open `config/config.php`
   - Update BASE_URL if your project is in a subdirectory:
     ```php
     define('BASE_URL', 'http://localhost/payrolll/');
     ```

#### Step 4: Set File Permissions

1. **Create Uploads Directory** (if needed)
   - Create folder: `uploads/`
   - Set permissions: 755 (or writable by web server)

#### Step 5: Access the System

1. **Open Browser**
   - Navigate to: `http://localhost/payrolll/`
   - Or: `http://localhost/payrolll/login.php`

2. **Login with Default Credentials**
   - **Username:** `admin`
   - **Password:** `admin123`
   
   ⚠️ **IMPORTANT:** Change the password immediately after first login!

#### Step 6: Initial Setup

1. **Update System Settings**
   - Go to: Settings > System Settings
   - Configure company information
   - Set payroll rates (tax, SSS, PhilHealth, Pag-IBIG)
   - Adjust working hours and overtime rates

2. **Add Employees**
   - Go to: Employees > Add New Employee
   - Fill in employee information
   - Set base salary and employment details

3. **Create Payroll Period**
   - Go to: Payroll > Payroll Periods
   - Create a new payroll period
   - Set start date, end date, and pay date

### Troubleshooting

#### Database Connection Error
- **Problem:** "Database connection failed"
- **Solution:** 
  - Check MySQL service is running
  - Verify database credentials in `config/database.php`
  - Ensure database `payroll_management` exists

#### Page Not Found / 404 Error
- **Problem:** Pages not loading
- **Solution:**
  - Check BASE_URL in `config/config.php`
  - Ensure .htaccess file exists
  - Check Apache mod_rewrite is enabled

#### Session Errors
- **Problem:** "Session already started" or session issues
- **Solution:**
  - Check PHP session configuration
  - Ensure no output before `session_start()`
  - Clear browser cookies

#### Permission Denied
- **Problem:** Cannot write files
- **Solution:**
  - Check file/folder permissions
  - Ensure web server has write access
  - Check PHP error logs

### System Requirements Checklist

- [ ] PHP 7.4 or higher
- [ ] MySQL 5.7+ or MariaDB 10.3+
- [ ] Apache/Nginx with mod_rewrite
- [ ] PDO extension enabled
- [ ] mbstring extension enabled
- [ ] GD extension (for future image features)

### Post-Installation Checklist

- [ ] Database imported successfully
- [ ] Default admin user can login
- [ ] Changed default admin password
- [ ] System settings configured
- [ ] Test employee added
- [ ] Test attendance recorded
- [ ] Test payroll period created
- [ ] Test payroll computation works

### Security Recommendations

1. **Change Default Password**
   - Immediately change admin password
   - Use strong passwords (min 8 characters)

2. **Database Security**
   - Use strong MySQL root password
   - Create dedicated database user with limited privileges
   - Don't expose database credentials

3. **File Permissions**
   - Set proper file permissions (644 for files, 755 for directories)
   - Protect config files

4. **HTTPS (Production)**
   - Use HTTPS in production
   - Update `config/config.php`:
     ```php
     ini_set('session.cookie_secure', 1);
     ```

5. **Error Reporting**
   - Disable error display in production:
     ```php
     error_reporting(0);
     ini_set('display_errors', 0);
     ```

### Support

For issues or questions:
1. Check the README.md file
2. Review error logs
3. Check PHP and MySQL error logs
4. Contact system administrator

---

**Installation Complete!** 🎉

You can now start using the Employee Payroll Management System.

