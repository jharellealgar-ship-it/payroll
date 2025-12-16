# Employee Payroll Management System

A professional, comprehensive payroll management system built with PHP and MySQL. This system automates and organizes all payroll-related processes within an organization.

## Features

### 1. Employee Information Management
- Complete employee profiles with personal and employment details
- Employment status tracking (active, on-leave, suspended, terminated)
- Position and department management
- Salary and rate configuration
- Bank and government information storage

### 2. Time and Attendance Tracking
- Record employee working hours (time in/out)
- Automatic calculation of total hours and overtime
- Late entry tracking with penalty calculation
- Absence and leave management
- Leave request system with approval workflow

### 3. Payroll Computation
- Automated salary calculation based on attendance
- Overtime pay computation
- Automatic deduction calculations (tax, SSS, PhilHealth, Pag-IBIG)
- Late and absence penalty deductions
- Incentive and bonus integration
- Net pay calculation

### 4. Incentives and Deductions Module
- Configurable deduction types (fixed, percentage, variable)
- Government-mandated deductions (SSS, PhilHealth, Pag-IBIG, Tax)
- Custom employee-specific deductions
- Incentive type management
- Employee-specific incentive assignment

### 5. Payroll Report Generation
- Comprehensive payroll summaries
- Period-based payroll reports
- Employee payslip generation
- Financial reports for management
- Print-ready reports

### 6. Secure Record Management
- Role-based access control (Admin, HR, Accountant, Employee)
- Secure authentication system
- Audit logging for all system activities
- Session management with timeout
- Data validation and sanitization

### 7. User-Friendly Interface
- Modern, responsive design
- Clean and intuitive navigation
- Dashboard with key statistics
- DataTables for easy data management
- Mobile-friendly layout

## System Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher / MariaDB 10.3 or higher
- Apache/Nginx web server
- XAMPP/WAMP/LAMP (for local development)

## Installation

### 1. Database Setup

1. Open phpMyAdmin or MySQL command line
2. Import the database schema:
   ```sql
   source database/schema.sql
   ```
   Or manually execute the SQL file located at `database/schema.sql`

### 2. Configuration

1. Edit `config/database.php` and update database credentials:
   ```php
   private $host = 'localhost';
   private $db_name = 'payroll_management';
   private $username = 'root';
   private $password = '';
   ```

2. Update `config/config.php` if needed:
   ```php
   define('BASE_URL', 'http://localhost/payrolll/');
   ```

### 3. File Permissions

Ensure the following directories are writable:
- `uploads/` (if file uploads are needed)

### 4. Default Login Credentials

**Username:** `admin`  
**Password:** `admin123`

**⚠️ IMPORTANT:** Change the default password immediately after first login!

## User Roles

### Administrator
- Full system access
- User management
- System settings configuration
- All HR and Payroll functions

### HR Manager
- Employee management
- Attendance management
- Leave request approval
- View payroll reports

### Accountant
- Payroll computation
- Payroll period management
- Financial reports
- View employee information

### Employee
- View own profile
- View own attendance records
- View own payslips
- Submit leave requests

## Directory Structure

```
payrolll/
├── assets/
│   ├── css/
│   │   └── style.css
│   └── js/
│       └── main.js
├── attendance/
│   ├── index.php
│   ├── manage.php
│   └── leave_requests.php
├── config/
│   ├── config.php
│   └── database.php
├── database/
│   └── schema.sql
├── employees/
│   ├── index.php
│   ├── add.php
│   ├── view.php
│   └── edit.php
├── includes/
│   ├── auth.php
│   ├── functions.php
│   ├── header.php
│   └── footer.php
├── payroll/
│   ├── periods.php
│   ├── compute.php
│   └── reports.php
├── settings/
│   ├── deductions.php
│   ├── incentives.php
│   ├── system.php
│   └── users.php
├── index.php
├── login.php
├── logout.php
└── README.md
```

## Key Features in Detail

### Payroll Computation Logic

1. **Basic Salary Calculation**
   - Monthly salary divided by pay periods
   - Configurable pay period (bi-weekly, monthly, etc.)

2. **Overtime Calculation**
   - Hours beyond regular working hours
   - Configurable overtime rate multiplier
   - Automatic calculation from attendance records

3. **Deductions**
   - **Tax:** Configurable percentage of gross salary
   - **SSS:** Social Security System contribution
   - **PhilHealth:** Health insurance contribution
   - **Pag-IBIG:** Home Development Mutual Fund
   - **Late Penalties:** Per-minute penalty for late arrivals
   - **Absence Deductions:** Daily rate deduction for absences

4. **Incentives**
   - Performance bonuses
   - Attendance bonuses
   - Allowances
   - Custom incentives per employee

### Security Features

- Password hashing using PHP `password_hash()`
- Prepared statements to prevent SQL injection
- Input sanitization and validation
- XSS protection with `htmlspecialchars()`
- Session timeout management
- Role-based access control
- Audit logging for sensitive operations

## Customization

### Adding New Deduction Types

1. Go to Settings > Deduction Types
2. Add new deduction type with code and default value
3. The system will automatically include it in payroll computation

### Modifying Payroll Rates

1. Go to Settings > System Settings
2. Update tax rates, contribution rates, etc.
3. Changes apply to new payroll computations

### Customizing Reports

Edit the report templates in `payroll/reports.php` to match your company's format.

## Troubleshooting

### Database Connection Error
- Check database credentials in `config/database.php`
- Ensure MySQL service is running
- Verify database name exists

### Session Issues
- Check PHP session configuration
- Ensure `session_start()` is called before any output
- Verify session directory permissions

### Permission Denied Errors
- Check file and directory permissions
- Ensure web server has read access to all files
- Check PHP error logs for detailed messages

## Support

For issues, questions, or feature requests, please refer to the system documentation or contact your system administrator.

## License

This system is proprietary software. All rights reserved.

## Version

Current Version: 1.0.0

---

**Note:** This is a professional payroll management system. Ensure compliance with local labor laws and tax regulations when using this system in production.

