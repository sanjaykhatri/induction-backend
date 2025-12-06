# MySQL Database Setup Guide

## Step 1: Create MySQL Database

You need to create a MySQL database for the HSE Induction Platform. You can do this using one of the following methods:

### Option A: Using MySQL Command Line

```bash
mysql -u root -p
```

Then run:
```sql
CREATE DATABASE hse_induction CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```

### Option B: Using phpMyAdmin or MySQL Workbench

1. Open phpMyAdmin or MySQL Workbench
2. Create a new database named `hse_induction`
3. Set collation to `utf8mb4_unicode_ci`

## Step 2: Configure Laravel .env File

Update your `.env` file in the `laravel-api` directory with your MySQL credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hse_induction
DB_USERNAME=root
DB_PASSWORD=your_mysql_password
```

**Important:** Replace `your_mysql_password` with your actual MySQL root password (or create a dedicated MySQL user).

### Creating a Dedicated MySQL User (Recommended)

For better security, create a dedicated MySQL user:

```sql
CREATE USER 'hse_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON hse_induction.* TO 'hse_user'@'localhost';
FLUSH PRIVILEGES;
```

Then use these credentials in your `.env`:
```env
DB_USERNAME=hse_user
DB_PASSWORD=your_secure_password
```

## Step 3: Test Database Connection

Run this command to test your database connection:

```bash
cd laravel-api
php artisan db:show
```

If you see database information, the connection is working!

## Step 4: Run Migrations

Once your database is configured, run the migrations:

```bash
php artisan migrate
```

This will create all the necessary tables:
- users
- inductions
- chapters
- questions
- submissions
- answers
- personal_access_tokens (for Sanctum)
- migrations
- cache
- cache_locks
- jobs
- job_batches
- sessions

## Step 5: Verify Tables

You can verify that all tables were created:

```bash
php artisan db:table
```

Or check directly in MySQL:

```sql
USE hse_induction;
SHOW TABLES;
```

## Troubleshooting

### Connection Refused
- Make sure MySQL server is running: `sudo service mysql start` (Linux) or check MySQL service status
- Verify the host and port in `.env` match your MySQL configuration

### Access Denied
- Check your MySQL username and password
- Ensure the user has proper permissions on the database

### Database Doesn't Exist
- Make sure you created the database before running migrations
- Check the database name in `.env` matches the created database

### Migration Errors
- Make sure all previous migrations are run: `php artisan migrate:fresh` (WARNING: This will delete all data)
- Check MySQL version compatibility (Laravel requires MySQL 5.7+ or MariaDB 10.3+)

## Quick Setup Script

If you want to automate the database creation, you can use this SQL script:

```sql
-- Create database
CREATE DATABASE IF NOT EXISTS hse_induction CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create user (optional, for production)
-- CREATE USER IF NOT EXISTS 'hse_user'@'localhost' IDENTIFIED BY 'change_this_password';
-- GRANT ALL PRIVILEGES ON hse_induction.* TO 'hse_user'@'localhost';
-- FLUSH PRIVILEGES;
```

Save this as `setup_database.sql` and run:
```bash
mysql -u root -p < setup_database.sql
```

