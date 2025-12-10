# HSE Induction Training Platform MVP

A remote Health, Safety, and Environment (HSE) induction training platform built with Laravel (backend) and Next.js (frontend).

## Project Structure

- `laravel-api/` - Laravel backend API
- `next-app/` - Next.js frontend application

## Features

### User Features
- Secure sign-in using name, company, email, and Vantage Card number
- View and start active induction trainings
- Watch video chapters with controls disabled
- Answer questions (single choice, multi-choice, or text)
- Complete inductions and receive email notifications

### Admin Features
- Dashboard with submission statistics
- Manage inductions (create, edit, delete, reorder)
- Manage chapters within inductions
- Manage questions within chapters
- View and review submissions
- Manage admin users

## Setup Instructions

### Backend (Laravel)

1. Navigate to the Laravel directory:
```bash
cd laravel-api
```

2. Install dependencies:
```bash
composer install
```

3. Copy environment file:
```bash
cp .env.example .env
```

4. Generate application key:
```bash
php artisan key:generate
```

5. **Set up MySQL Database:**

   **Option A: Use the setup script (Recommended)**
   ```bash
   ./setup-mysql.sh
   ```
   This script will guide you through creating the database and configuring `.env`.

   **Option B: Manual Setup**
   
   First, create the MySQL database:
   ```bash
   mysql -u root -p < setup-database.sql
   ```
   Or manually:
   ```sql
   CREATE DATABASE hse_induction CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

   Then update your `.env` file with MySQL credentials:
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=hse_induction
   DB_USERNAME=root
   DB_PASSWORD=your_mysql_password
   ```

   **Note:** See `DATABASE_SETUP.md` for detailed MySQL setup instructions.

6. Configure email settings in `.env`:
```
MAIL_MAILER=smtp
MAIL_HOST=your_smtp_host
MAIL_PORT=your_smtp_port
MAIL_USERNAME=your_smtp_username
MAIL_PASSWORD=your_smtp_password
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="HSE Induction Platform"

SUBMISSION_NOTIFICATION_EMAIL=admin@example.com
```

7. Test database connection (optional):
```bash
php artisan db:show
```

8. Run migrations:
```bash
php artisan migrate
```

9. Start the development server:
```bash
php artisan serve
```

The API will be available at `http://localhost:8000`

### Frontend (Next.js)

1. Navigate to the Next.js directory:
```bash
cd next-app
```

2. Install dependencies:
```bash
npm install
```

3. Create `.env.local` file:
```bash
cp .env.local.example .env.local
```

4. Update `.env.local` with your API URL:
```
NEXT_PUBLIC_API_URL=http://localhost:8000/api
```

5. Start the development server:
```bash
npm run dev
```

The frontend will be available at `http://localhost:3000`

## Creating Your First Admin User

The easiest way to create admin users is using the database seeder:

```bash
cd laravel-api
php artisan db:seed --class=AdminUserSeeder
```

This creates:
- **Admin**: admin@example.com / password: admin123
- **Super Admin**: superadmin@example.com / password: superadmin123

**Alternative: Using Tinker**

If you prefer to use Tinker:

```bash
php artisan tinker
```

Then run:
```php
$user = new App\Models\User();
$user->name = 'Admin User';
$user->email = 'admin@example.com';
$user->password = Hash::make('your_password');
$user->role = 'admin';
$user->save();
exit
```

## Branding & Theming

The platform is designed with flexibility in mind for branding:

- **Fonts**: All font settings are defined as CSS variables in `next-app/app/globals.css`. Update `--font-body` and `--font-heading` variables.

- **Colors**: Theme colors are defined as CSS variables. Update `--color-primary`, `--color-secondary`, `--color-accent`, etc.

- **Logo**: Replace the logo placeholder in the header components with your actual logo image.

## API Endpoints

### Authentication
- `POST /api/auth/login` - User login/signup
- `GET /api/auth/me` - Get current user

### User Endpoints
- `GET /api/inductions/active` - Get active inductions
- `POST /api/inductions/{id}/start` - Start an induction
- `GET /api/submissions/{id}` - Get submission details
- `POST /api/submissions/{id}/answers` - Submit answers
- `POST /api/submissions/{id}/complete` - Complete submission

### Admin Endpoints
- `GET /api/admin/inductions` - List all inductions
- `POST /api/admin/inductions` - Create induction
- `PUT /api/admin/inductions/{id}` - Update induction
- `DELETE /api/admin/inductions/{id}` - Delete induction
- `GET /api/admin/inductions/{id}/chapters` - List chapters
- `POST /api/admin/inductions/{id}/chapters` - Create chapter
- `PUT /api/admin/chapters/{id}` - Update chapter
- `DELETE /api/admin/chapters/{id}` - Delete chapter
- `GET /api/admin/chapters/{id}/questions` - List questions
- `POST /api/admin/chapters/{id}/questions` - Create question
- `PUT /api/admin/questions/{id}` - Update question
- `DELETE /api/admin/questions/{id}` - Delete question
- `GET /api/admin/submissions` - List submissions
- `GET /api/admin/submissions/{id}` - Get submission details
- `GET /api/admin/admins` - List admins
- `POST /api/admin/admins` - Create admin
- `PUT /api/admin/admins/{id}` - Update admin
- `DELETE /api/admin/admins/{id}` - Remove admin

## Database Schema

- `users` - User accounts (users and admins)
- `inductions` - Training modules
- `chapters` - Video chapters within inductions
- `questions` - Questions for each chapter
- `submissions` - User submissions for inductions
- `answers` - Answers to questions

## Security Features

- Laravel Sanctum for API authentication
- Admin middleware to protect admin routes
- Video controls disabled for end users
- Secure password hashing
- CORS configuration for frontend

## Email Notifications

When a user completes an induction, an email notification is sent to the address specified in `SUBMISSION_NOTIFICATION_EMAIL` environment variable.

## Development Notes

- The platform stores a snapshot of the induction structure when a user starts it, ensuring historical accuracy even if the induction is later modified.
- Video URLs can be local file paths or external URLs.
- Questions support single choice, multi-choice, and text input types.
- The admin panel provides full CRUD operations for all content.

## License

This is a proprietary MVP project.
