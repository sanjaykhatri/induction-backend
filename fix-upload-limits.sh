#!/bin/bash

# Script to fix PHP upload limits for MAMP PRO
# This script updates the PHP configuration file used by MAMP PRO

PHP_INI="/Library/Application Support/appsolute/MAMP PRO/conf/php8.2.26.ini"

if [ ! -f "$PHP_INI" ]; then
    echo "Error: PHP configuration file not found at: $PHP_INI"
    echo "Please update the path in this script to match your MAMP PRO PHP version."
    exit 1
fi

echo "Backing up PHP configuration..."
cp "$PHP_INI" "${PHP_INI}.backup.$(date +%Y%m%d_%H%M%S)"

echo "Updating PHP upload limits..."

# Update or add upload_max_filesize
if grep -q "^upload_max_filesize" "$PHP_INI"; then
    sed -i '' 's/^upload_max_filesize.*/upload_max_filesize = 200M/' "$PHP_INI"
else
    echo "upload_max_filesize = 200M" >> "$PHP_INI"
fi

# Update or add post_max_size
if grep -q "^post_max_size" "$PHP_INI"; then
    sed -i '' 's/^post_max_size.*/post_max_size = 200M/' "$PHP_INI"
else
    echo "post_max_size = 200M" >> "$PHP_INI"
fi

# Update or add max_execution_time
if grep -q "^max_execution_time" "$PHP_INI"; then
    sed -i '' 's/^max_execution_time.*/max_execution_time = 300/' "$PHP_INI"
else
    echo "max_execution_time = 300" >> "$PHP_INI"
fi

# Update or add max_input_time
if grep -q "^max_input_time" "$PHP_INI"; then
    sed -i '' 's/^max_input_time.*/max_input_time = 300/' "$PHP_INI"
else
    echo "max_input_time = 300" >> "$PHP_INI"
fi

echo ""
echo "✅ PHP configuration updated successfully!"
echo ""
echo "Updated settings:"
echo "  - upload_max_filesize: 200M"
echo "  - post_max_size: 200M"
echo "  - max_execution_time: 300"
echo "  - max_input_time: 300"
echo ""
echo "⚠️  IMPORTANT: You need to restart MAMP PRO for changes to take effect!"
echo ""
echo "To verify the changes, run:"
echo "  php -i | grep -E 'upload_max_filesize|post_max_size'"

