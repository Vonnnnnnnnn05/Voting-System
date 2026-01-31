# Online Voting System

A secure, feature-rich online voting platform built with PHP and MySQL using PDO for database interactions.

## 🚀 Features

### Security
- ✅ PDO prepared statements with named parameters
- ✅ Password hashing using `password_hash()` and `password_verify()`
- ✅ Email verification with token-based system
- ✅ CSRF protection on all forms
- ✅ SQL injection prevention
- ✅ Input validation and sanitization
- ✅ Session-based authentication
- ✅ Comprehensive audit logging

### User Roles
- **Admin**: Full system management
  - Create, update, delete elections
  - Add, update, delete candidates
  - View real-time results
  - Access dashboard with statistics
  
- **Voter**: Participate in elections
  - Browse available elections
  - Vote for candidates
  - View results after voting
  - One vote per election (enforced at database level)

### Election Management
- Time-based election control (start/end dates)
- Support for multiple concurrent elections
- Candidate management with photos
- Real-time vote counting
- Interactive results visualization with Chart.js

### Audit & Compliance
- Complete action logging in `audit_logs` table
- IP address tracking
- Vote timestamp recording
- User activity monitoring

## 📋 Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache web server with mod_rewrite
- XAMPP/WAMP/LAMP stack

## 🔧 Installation

### 1. Clone/Download Files
Place the project in your web server directory:
```
c:\xampp\htdocs\voting system\
```

### 2. Create Database
1. Open phpMyAdmin (http://localhost/phpmyadmin)
2. Create a new database named `voting_system`
3. Import the schema:
   - Navigate to the database
   - Click "Import"
   - Select `database/schema.sql`
   - Click "Go"

### 3. Configure Database Connection
Edit `config/database.php` if needed:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'voting_system');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### 4. Configure Application Settings
Edit `config/config.php`:
- Update `APP_URL` to match your local environment
- Configure email settings for production use
- Adjust security settings as needed

### 5. Set Permissions
Ensure the upload directory is writable:
```bash
chmod 755 assets/images/candidates/
```

### 6. Access the Application
Open your browser and navigate to:
```
http://localhost/voting%20system/
```

## 👤 Default Admin Account

**Email:** admin@votingsystem.com  
**Password:** admin123

⚠️ **Important:** Change the default admin password immediately after first login!

## 📚 Usage Guide

### For Voters

1. **Register**
   - Go to Register page
   - Fill in your details
   - Submit the form

2. **Verify Email**
   - Check your email for verification link
   - Click the link to activate your account

3. **Login**
   - Use your email and password
   - You'll be redirected to Elections page

4. **Vote**
   - Browse available elections
   - Click "Vote Now" on active elections
   - Select your candidate
   - Confirm your vote

5. **View Results**
   - Results are visible after voting or when election ends
   - Interactive charts show vote distribution

### For Administrators

1. **Login**
   - Use admin credentials
   - Access Admin Dashboard

2. **Create Election**
   - Go to Elections page
   - Fill in election details
   - Set start and end dates
   - Submit

3. **Add Candidates**
   - Click "Manage Candidates" for an election
   - Add candidate name, description, and photo
   - Submit

4. **Monitor Results**
   - Click "Results" for any election
   - View real-time vote counts
   - See interactive charts and statistics

## 🏗️ Project Structure

```
voting system/
├── config/
│   ├── database.php       # PDO connection
│   └── config.php         # App configuration
├── core/
│   ├── auth.php           # Authentication functions
│   ├── csrf.php           # CSRF protection
│   └── helpers.php        # Utility functions
├── auth/
│   ├── register.php       # User registration
│   ├── login.php          # User login
│   ├── logout.php         # User logout
│   └── verify.php         # Email verification
├── admin/
│   ├── dashboard.php      # Admin dashboard
│   ├── elections.php      # Election management
│   ├── candidates.php     # Candidate management
│   └── results.php        # Results viewing
├── voter/
│   ├── elections.php      # Browse elections
│   └── vote.php           # Cast vote
├── api/
│   └── results.php        # JSON API for charts
├── assets/
│   ├── css/               # Custom stylesheets
│   ├── js/                # Custom scripts
│   └── images/
│       └── candidates/    # Candidate photos
├── database/
│   └── schema.sql         # Database schema
├── views/
│   ├── header.php         # Common header
│   └── footer.php         # Common footer
├── index.php              # Landing page
├── .htaccess              # Security rules
└── README.md              # This file
```

## 🗄️ Database Schema

### Tables

1. **users** - User accounts
2. **elections** - Election records
3. **candidates** - Election candidates
4. **votes** - Cast votes (with unique constraint)
5. **audit_logs** - System activity logs

### Key Relationships
- Elections created by users
- Candidates belong to elections
- Votes link users to candidates in elections
- Unique constraint prevents duplicate voting

## 🔒 Security Features

1. **Authentication**
   - Password hashing with bcrypt
   - Session-based authentication
   - Email verification required

2. **Input Protection**
   - All inputs sanitized
   - HTML special characters escaped
   - PDO prepared statements

3. **CSRF Protection**
   - Token generation and validation
   - Time-based token expiration
   - Secure token comparison

4. **Database Security**
   - PDO with exception mode
   - Named parameters
   - No inline SQL

5. **File Upload Security**
   - Type validation
   - Size limits
   - Unique filenames

6. **Audit Trail**
   - All actions logged
   - IP addresses recorded
   - Timestamps maintained

## 🎨 Customization

### Changing Colors
Edit the Bootstrap classes in view files or add custom CSS in `assets/css/`

### Email Configuration
For production, update SMTP settings in `config/config.php` or integrate PHPMailer

### Extending Features
- Add password reset functionality
- Implement voter statistics
- Add candidate profiles
- Enable ranked-choice voting

## 🐛 Troubleshooting

### Database Connection Issues
- Verify MySQL is running
- Check database credentials in `config/database.php`
- Ensure database exists and schema is imported

### Email Not Sending
- Configure SMTP settings in `config/config.php`
- For development, check PHP mail configuration
- Consider using PHPMailer for production

### Upload Directory Errors
- Ensure `assets/images/candidates/` directory exists
- Check directory permissions (755 or 777)
- Verify PHP upload settings in php.ini

### CSRF Token Errors
- Clear browser cookies
- Check session configuration
- Ensure session_start() is called

## 📄 License

This project is open-source and available for educational purposes.

## 👨‍💻 Development

Built with:
- PHP 8.x
- MySQL 8.x
- Bootstrap 5.1.3
- Font Awesome 6.0
- Chart.js 3.9.1

## 🤝 Contributing

Contributions are welcome! Feel free to:
- Report bugs
- Suggest features
- Submit pull requests

## ⚠️ Important Notes

1. **Change default admin password** immediately after installation
2. **Enable HTTPS** in production environments
3. **Configure proper email** settings for verification
4. **Set appropriate file permissions** for security
5. **Regular backups** of database recommended
6. **Update security headers** in .htaccess as needed

## 📞 Support

For issues or questions:
- Check the troubleshooting section
- Review code comments
- Check database logs
- Review PHP error logs

---

**Version:** 1.0.0  
**Last Updated:** January 2026
#   V o t i n g - S y s t e m  
 