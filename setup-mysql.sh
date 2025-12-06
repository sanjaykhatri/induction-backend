#!/bin/bash

# MySQL Database Setup Script for HSE Induction Platform

echo "=========================================="
echo "HSE Induction Platform - MySQL Setup"
echo "=========================================="
echo ""

# Check if .env file exists
if [ ! -f .env ]; then
    echo "Creating .env file from .env.example..."
    cp .env.example .env
    php artisan key:generate
fi

# Prompt for MySQL credentials
echo "Please provide your MySQL database credentials:"
echo ""

read -p "MySQL Host [127.0.0.1]: " DB_HOST
DB_HOST=${DB_HOST:-127.0.0.1}

read -p "MySQL Port [3306]: " DB_PORT
DB_PORT=${DB_PORT:-3306}

read -p "Database Name [hse_induction]: " DB_DATABASE
DB_DATABASE=${DB_DATABASE:-hse_induction}

read -p "MySQL Username [root]: " DB_USERNAME
DB_USERNAME=${DB_USERNAME:-root}

read -sp "MySQL Password: " DB_PASSWORD
echo ""

# Update .env file
echo ""
echo "Updating .env file..."

# Use sed to update the .env file (works on macOS and Linux)
if [[ "$OSTYPE" == "darwin"* ]]; then
    # macOS
    sed -i '' "s/^DB_CONNECTION=.*/DB_CONNECTION=mysql/" .env
    sed -i '' "s/^DB_HOST=.*/DB_HOST=${DB_HOST}/" .env
    sed -i '' "s/^DB_PORT=.*/DB_PORT=${DB_PORT}/" .env
    sed -i '' "s/^DB_DATABASE=.*/DB_DATABASE=${DB_DATABASE}/" .env
    sed -i '' "s/^DB_USERNAME=.*/DB_USERNAME=${DB_USERNAME}/" .env
    sed -i '' "s/^DB_PASSWORD=.*/DB_PASSWORD=${DB_PASSWORD}/" .env
else
    # Linux
    sed -i "s/^DB_CONNECTION=.*/DB_CONNECTION=mysql/" .env
    sed -i "s/^DB_HOST=.*/DB_HOST=${DB_HOST}/" .env
    sed -i "s/^DB_PORT=.*/DB_PORT=${DB_PORT}/" .env
    sed -i "s/^DB_DATABASE=.*/DB_DATABASE=${DB_DATABASE}/" .env
    sed -i "s/^DB_USERNAME=.*/DB_USERNAME=${DB_USERNAME}/" .env
    sed -i "s/^DB_PASSWORD=.*/DB_PASSWORD=${DB_PASSWORD}/" .env
fi

echo "✓ .env file updated"
echo ""

# Ask if user wants to create the database
read -p "Do you want to create the database now? (y/n): " CREATE_DB
if [[ $CREATE_DB == "y" || $CREATE_DB == "Y" ]]; then
    echo ""
    echo "Creating database..."
    mysql -u "$DB_USERNAME" -p"$DB_PASSWORD" -e "CREATE DATABASE IF NOT EXISTS ${DB_DATABASE} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    if [ $? -eq 0 ]; then
        echo "✓ Database created successfully"
    else
        echo "✗ Failed to create database. Please create it manually."
    fi
fi

echo ""
echo "=========================================="
echo "Setup Complete!"
echo "=========================================="
echo ""
echo "Next steps:"
echo "1. Run migrations: php artisan migrate"
echo "2. Create an admin user (see README.md)"
echo "3. Start the server: php artisan serve"
echo ""

