# Automated Classroom Scheduling System (ACSS)

## Overview

The Automated Classroom Scheduling System (ACSS) is designed to automate the process of creating schedules for all college departments across all sections. The primary goal is to ensure no overlapping schedules and to produce clean, conflict-free schedules efficiently.

## Features

- Automated schedule generation for multiple departments and sections
- Conflict detection and resolution to avoid overlapping schedules
- Clean and organized schedule outputs
- Role-based access for administrators, chairs, deans, directors, and faculty
- Curriculum management
- Faculty teaching load monitoring
- Schedule history and backups
- PDF generation for schedules
- Email notifications
- Database backup functionality

## Technologies Used

- PHP (Backend)
- JavaScript (Frontend)
- MySQL (Database)
- Tailwind CSS (Styling)
- Composer (PHP dependencies)
- NPM (Node dependencies)

## Installation

1. Clone the repository:
   ```
   git clone https://github.com/Mark-Louie27/acss-1.git
   cd acss-1
   ```

2. Install PHP dependencies:
   ```
   composer install
   ```

3. Install Node dependencies:
   ```
   npm install
   ```

4. Set up the database:
   - Import the database schema from `test_db.php` or create your own.
   - Configure database settings in `src/config/Database.php`.

5. Configure environment variables in `.env`.

6. Start the development server:
   ```
   php -S localhost:8000 -t public_html
   ```

## Usage

- Access the application through the web interface.
- Log in with appropriate credentials based on your role.
- Use the dashboard to manage schedules, curricula, and users.

## Contributing

Contributions are welcome. Please fork the repository and submit a pull request.


