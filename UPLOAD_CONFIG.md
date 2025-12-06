# Video Upload Configuration Guide

## Problem
When uploading large video files, you may encounter errors like:
- `413 Content Too Large`
- `POST Content-Length exceeds the limit`
- `The POST data is too large`

## Solution

### Option 1: Update PHP Configuration (Recommended)

If you're using `php artisan serve`, you need to update your PHP configuration:

1. **Find your PHP configuration file:**
   ```bash
   php --ini
   ```

2. **Edit the php.ini file** (usually located at `/etc/php.ini` or `/usr/local/etc/php/8.2/php.ini` on macOS):
   ```ini
   upload_max_filesize = 200M
   post_max_size = 200M
   max_execution_time = 300
   max_input_time = 300
   memory_limit = 256M
   ```

3. **Restart PHP-FPM or your web server** (if using Apache/Nginx):
   ```bash
   # For Apache
   sudo apachectl restart
   
   # For Nginx with PHP-FPM
   sudo service php-fpm restart
   ```

4. **Restart `php artisan serve`** if you're using it:
   ```bash
   # Stop the current server (Ctrl+C)
   # Then restart
   php artisan serve
   ```

### Option 2: Using .htaccess (Apache only)

The `.htaccess` file in `public/.htaccess` has been updated with upload limits. This works if you're using Apache, but **does NOT work with `php artisan serve`**.

### Option 3: Create a Custom PHP Configuration

1. Create a `php.ini` file in the `public/` directory (already created)
2. Start PHP with custom configuration:
   ```bash
   php -c public/php.ini artisan serve
   ```

### Option 4: Use Environment Variables (macOS/Linux)

You can also set PHP configuration via environment variables before starting the server:

```bash
export PHP_INI_SCAN_DIR="/path/to/custom/php.ini.d"
php artisan serve
```

## Verify Configuration

After making changes, verify your PHP settings:

```bash
php -i | grep -E "upload_max_filesize|post_max_size|max_execution_time"
```

You should see:
- `upload_max_filesize => 200M => 200M`
- `post_max_size => 200M => 200M`
- `max_execution_time => 300 => 300`

## Current Limits

The application is configured to accept video files up to **100MB** (as defined in the validation rules). The PHP configuration should be set higher to accommodate the upload process overhead.

## Troubleshooting

### Still getting errors after configuration?

1. **Check if changes took effect:**
   ```bash
   php -i | grep post_max_size
   ```

2. **Clear Laravel cache:**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

3. **Check web server limits** (if using Nginx/Apache):
   - **Nginx**: Check `client_max_body_size` in nginx.conf
   - **Apache**: Check `LimitRequestBody` in httpd.conf

4. **For `php artisan serve` specifically:**
   - The built-in PHP server may not respect all php.ini settings
   - Consider using a proper web server (Apache/Nginx) for production
   - Or use the custom php.ini method above

## Production Recommendations

For production environments:
- Use a proper web server (Nginx or Apache)
- Configure upload limits in the web server configuration
- Consider using cloud storage (S3) for large files instead of local storage
- Implement chunked uploads for very large files

