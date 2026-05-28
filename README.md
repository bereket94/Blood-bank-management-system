# 🩸 Blood Bank Management System

A web-based system for managing blood donations, appointments, donors, nurses, hospitals, and blood inventory.

Developed by
<br>
Bereket Asfaw<br>
Filseta Taddese<br>
Mihret Getu<br>
Samrawit Lema<br>
Bersufikad Kalsa<br>
Mesud Nuredin<br>

Technologies

| Layer Technology

| Frontend: HTML5, CSS3, JavaScript
| Backend: PHP 8.0
| Database: MySQL
| Server: Apache (XAMPP)

User Roles

| Role | Responsibilities

| Donor : Register, book appointments, check eligibility
| Nurse : Verify donors, update donation status
| Hospital Admin : Manage nurses, view donation records
| System Admin : Full system control, manage all users

 Login Credentials

| Role | Email | Password
| System Admin : admin.bloodbank@gmail.com | admin123 |
| Donor : johndoe@gmail.com | donor123 |
| Nurse : sarah.nurse@gmail.com | nurse123 |
| Hospital Admin : wolkite.hospital@gmail.com | hospital123 |

Installation

1. Install :XAMPP
2. Copy folder to `C:\xampp\htdocs\`
3. Import `blood_bank.sql` to phpMyAdmin
4. Update `config/db_connect.php` with your DB credentials
5. Run: `http://localhost/Blood-Bank-system`

---

Security Features

- ✅ Prepared Statements (SQL Injection prevention)
- ✅ MD5 Password Hashing
- ✅ Session Management
- ✅ Role-Based Access Control

Project Structure

Blood-Bank-system/<br>
|
├── admin/ # Admin module<br>
├── donor/ # Donor module<br>
├── nurse/ # Nurse module<br>
├── hospital/ # Hospital module<br>
├── config/ # Database config<br>
├── css/ # Stylesheets<br>
├── database/ # SQL file<br>
└── index.php # Landing page<br>
