# Employee Login Setup Guide

This guide explains how to create user accounts for employees so they can log in to the system.

## Steps to Enable Employee Login

### Step 1: Add Employee Record

1. **Login as Admin**
   - Go to: `http://localhost/payrolll/login.php`
   - Username: `admin`
   - Password: `admin123`

2. **Add Employee**
   - Navigate to: **Employees > Add New Employee**
   - Fill in all employee information
   - Save the employee record

### Step 2: Create User Account for Employee

1. **Go to System Users**
   - Navigate to: **Settings > System Users**
   - Or directly: `http://localhost/payrolll/settings/users.php`

2. **Create Account**
   - In the "Create Employee Account" card:
     - **Select Employee**: Choose the employee from the dropdown (only shows employees without accounts)
     - **Username**: Enter a unique username (e.g., `john.doe` or `emp001`)
     - **Password**: Enter a secure password (minimum 8 characters)
     - **Role**: Select the role (usually "Employee")
     - Click **"Create Account"**

3. **Account Created**
   - The system will:
     - Create a user account in the `users` table
     - Link it to the employee record
     - Employee can now log in!

### Step 3: Employee Login

The employee can now log in using:
- **Username**: The username you created
- **Password**: The password you set

**Login URL**: `http://localhost/payrolll/login.php`

## Employee Account Features

Once logged in, employees can:
- View their own profile
- View their attendance records
- View their payroll information
- Submit leave requests
- View their payslips

## Quick Setup Script

If you want to quickly create a test employee account, you can run this SQL in phpMyAdmin:

```sql
-- First, add an employee (replace with actual values)
INSERT INTO employees (employee_id, first_name, last_name, email, position, department, hire_date, base_salary, employment_status) 
VALUES ('EMP001', 'John', 'Doe', 'john.doe@company.com', 'Software Developer', 'IT', CURDATE(), 50000.00, 'active');

-- Get the employee ID (replace EMP001 with your employee_id)
SET @emp_id = (SELECT id FROM employees WHERE employee_id = 'EMP001');

-- Create user account (password: employee123)
INSERT INTO users (username, email, password_hash, role, first_name, last_name) 
VALUES ('john.doe', 'john.doe@company.com', '$2y$10$D82LoPDigUHqvxCIhcLrj.qZ99Jecge8Bx0L2hk.KeKLh2LevvqEm', 'employee', 'John', 'Doe');

-- Link user to employee
UPDATE employees SET user_id = (SELECT id FROM users WHERE username = 'john.doe') WHERE id = @emp_id;
```

**Note**: The password hash above is for `employee123`. Change it if needed.

## Troubleshooting

### Employee Cannot Login

1. **Check if account exists**
   - Go to Settings > System Users
   - Verify the employee appears in the users list

2. **Check account status**
   - Ensure the account is "Active" (not "Inactive")

3. **Verify username/password**
   - Username is case-sensitive
   - Password must match exactly

4. **Check employee status**
   - Employee must have `employment_status = 'active'`

### Employee Not Showing in Dropdown

- Only employees with `user_id IS NULL` appear in the dropdown
- If employee already has an account, they won't appear
- Check the "All System Users" table to see if account exists

### Reset Employee Password

To reset an employee's password, you can:

1. **Via SQL** (in phpMyAdmin):
```sql
UPDATE users 
SET password_hash = '$2y$10$D82LoPDigUHqvxCIhcLrj.qZ99Jecge8Bx0L2hk.KeKLh2LevvqEm' 
WHERE username = 'employee_username';
```
(Replace `employee_username` and the hash with new password)

2. **Via Profile Page** (if employee is logged in):
   - Employee can change their own password from Profile page

## Security Notes

- Always use strong passwords (minimum 8 characters)
- Employees should change their password on first login
- Consider implementing password complexity requirements
- Regularly review active user accounts

## Roles Available

- **Employee**: Basic access (view own records)
- **HR Manager**: Can manage employees and attendance
- **Accountant**: Can compute payroll and view reports
- **Administrator**: Full system access

---

**Need Help?** Contact your system administrator.

