# Hostel Tracking, Allocation, and Management System

## Overview
A secure, responsive web application for students, hostel owners, and managers to manage hostel bookings, allocations, and payments. Built with procedural PHP, MySQLi, and Bootstrap.

## Features
- Student hostel/room search, booking, and payment
- Hostel/room management for owners and managers
- Role-based access (Super Admin, Owner, Manager, Student)
- Business and branch support for expansion
- Reviews, notifications, amenities, support tickets, and more

## Project Structure
- `config/` — Database configuration
- `assets/` — CSS, JS, images
- `sql/` — SQL schema
- `modules/` — Application modules (to be created)
- `includes/` — Common includes (to be created)
- `public/` — Public entry point (to be created)
- `templates/` — Bootstrap templates (to be created)

## Setup Instructions
1. Import the SQL schema from `sql/schema.sql` into your MySQL database.
2. Copy `config/db.php` and update with your database credentials.
3. Place your PHP files in the appropriate folders as you build modules.
4. Access the app via your web server (e.g., `public/index.php`).

## Database Configuration
Edit `config/db.php` and set:
- `$host` — your MySQL host (usually `localhost`)
- `$user` — your MySQL username
- `$password` — your MySQL password
- `$database` — your database name

---

For more details, see the code comments and module documentation as you build out the system. 