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

```
acss-1
├─ backups
│  ├─ backup_acss1_2025-10-31_15-21-15.sql.zip
│  ├─ backup_acss1_2025-10-31_15-57-27.sql.zip
│  ├─ backup_acss1_2025-11-01_16-24-29.sql.zip
│  ├─ backup_acss1_2025-11-02_23-31-55.sql.zip
│  ├─ backup_acss1_2025-11-03_23-41-22.sql.zip
│  └─ backup_acss1_2025-11-20_23-23-36.sql.zip
├─ cli
│  └─ backup-database.php
├─ composer.json
├─ logs
├─ package-lock.json
├─ package.json
├─ PROJECTTREE.md
├─ public_html
│  ├─ .htaccess
│  ├─ assets
│  │  ├─ img
│  │  ├─ js
│  │  │  ├─ curriculum.js
│  │  │  ├─ generate_schedules.js
│  │  │  ├─ manual_schedules.js
│  │  │  └─ schedule.js
│  │  └─ logo
│  │     ├─ college_logo
│  │     │  ├─ college_5_1761146495.png
│  │     │  ├─ college_7_1759400446.png
│  │     │  ├─ college_7_1759451921.png
│  │     │  ├─ college_7_1759453222.png
│  │     │  ├─ college_7_1759632346.png
│  │     │  ├─ college_7_1759632357.png
│  │     │  └─ college_9_1762301447.png
│  │     └─ main_logo
│  │        ├─ campus.jpg
│  │        └─ PRMSUlogo.png
│  ├─ css
│  │  ├─ custom.css
│  │  ├─ output.css
│  │  ├─ schedule_management.css
│  │  └─ settings.css
│  ├─ index.php
│  └─ uploads
│     ├─ bg_1761923201_6904d08167af9.png
│     ├─ bg_1761923897_6904d3399e7ea.png
│     ├─ bg_1762050164_6906c074b1a4c.png
│     ├─ bg_1762050176_6906c0804159c.jpg
│     ├─ logo_1761923325_6904d0fd66a56.png
│     ├─ logo_1761923496_6904d1a86cbbf.png
│     ├─ logo_1762048286_6906b91e9ee74.png
│     ├─ logo_1762048360_6906b96823293.png
│     ├─ profiles
│     │  ├─ profile_1_1755697812.png
│     │  ├─ profile_1_1755697860.png
│     │  └─ profile_1_1755860919.png
│     ├─ profiles_picture
│     │  └─ profile_1_1755864417.png
│     └─ profile_pictures
│        ├─ profile_1_1755864533.png
│        ├─ profile_1_1755867785.png
│        ├─ profile_1_1756791244.png
│        ├─ profile_1_1762330259.jpg
│        ├─ profile_26_1759837327.png
│        ├─ profile_2_1756793168.png
│        ├─ profile_45_1758014886.png
│        ├─ profile_65_1755868685.png
│        ├─ user_1_1755864800.png
│        ├─ user_1_1755865986.png
│        ├─ user_49_1751092559.png
│        ├─ user_59_1755334211.png
│        ├─ user_59_1755334397.png
│        ├─ user_59_1755334635.png
│        ├─ user_59_1755335026.png
│        ├─ user_59_1755335130.png
│        └─ user_59_1755335861.png
├─ README.md
├─ src
│  ├─ api
│  │  └─ load_data.php
│  ├─ config
│  │  └─ Database.php
│  ├─ controllers
│  │  ├─ AdminController.php
│  │  ├─ ApiController.php
│  │  ├─ AuthController.php
│  │  ├─ BaseController.php
│  │  ├─ BaseProfileController.php
│  │  ├─ ChairController.php
│  │  ├─ DeanController.php
│  │  ├─ DirectorController.php
│  │  ├─ FacultyController.php
│  │  ├─ PdfController.php
│  │  └─ PublicController.php
│  ├─ helpers
│  │  └─ SystemSettings.php
│  ├─ input.css
│  ├─ middleware
│  │  └─ AuthMiddleware.php
│  ├─ models
│  │  ├─ ContentModel.php
│  │  ├─ ScheduleModel.php
│  │  └─ UserModel.php
│  ├─ repositories
│  │  ├─ CourseRepository.php
│  │  ├─ ProfileRepository.php
│  │  └─ SpecializationRepository.php
│  ├─ services
│  │  ├─ AuthService.php
│  │  ├─ BackupSchedulerService.php
│  │  ├─ EmailService.php
│  │  ├─ PdfService.php
│  │  ├─ ProfilePictureService.php
│  │  └─ SchedulingService.php
│  └─ views
│     ├─ admin
│     │  ├─ act_logs.php
│     │  ├─ classroom.php
│     │  ├─ colleges.php
│     │  ├─ colleges_departments.php
│     │  ├─ dashboard.php
│     │  ├─ database-backup.php
│     │  ├─ departments.php
│     │  ├─ edit_user.php
│     │  ├─ layout.php
│     │  ├─ profile.php
│     │  ├─ schedule-history.php
│     │  ├─ schedule.php
│     │  ├─ settings.php
│     │  └─ users.php
│     ├─ auth
│     │  ├─ forgot_password.php
│     │  ├─ login.php
│     │  ├─ register.php
│     │  ├─ reset_password.php
│     │  └─ terms_modal.php
│     ├─ chair
│     │  ├─ classroom.php
│     │  ├─ courses.php
│     │  ├─ curriculum.php
│     │  ├─ dashboard.php
│     │  ├─ faculty-teaching-load.php
│     │  ├─ faculty.php
│     │  ├─ layout.php
│     │  ├─ my_schedule.php
│     │  ├─ profile.php
│     │  ├─ schedule_history.php
│     │  ├─ schedule_management.php
│     │  ├─ sections.php
│     │  └─ settings.php
│     ├─ dean
│     │  ├─ activities.php
│     │  ├─ classroom.php
│     │  ├─ courses.php
│     │  ├─ curriculum.php
│     │  ├─ dashboard.php
│     │  ├─ faculty-teaching-load.php
│     │  ├─ faculty.php
│     │  ├─ layout.php
│     │  ├─ manage_departments.php
│     │  ├─ manage_schedules.php
│     │  ├─ profile.php
│     │  ├─ schedule.php
│     │  ├─ search.php
│     │  └─ settings.php
│     ├─ director
│     │  ├─ all-teaching-load.php
│     │  ├─ dashboard.php
│     │  ├─ layout.php
│     │  ├─ monitor.php
│     │  ├─ pending-approvals.php
│     │  ├─ profile.php
│     │  ├─ schedule.php
│     │  ├─ schedule_deadline.php
│     │  └─ settings.php
│     ├─ errors
│     │  └─ 403.php
│     ├─ faculty
│     │  ├─ dashboard.php
│     │  ├─ layout.php
│     │  ├─ my_schedule.php
│     │  ├─ profile.php
│     │  ├─ reports
│     │  │  ├─ specializations.php
│     │  │  └─ teaching_load.php
│     │  ├─ reports.php
│     │  └─ settings.php
│     ├─ public
│     │  └─ home.php
│     └─ vpaa
├─ storage
│  └─ scheduler.json
├─ tailwind.config.js
└─ test_db.php

```