# ACLC College — Technical Equipment Inventory System

A ready-to-run **PHP + MySQL** app for managing equipment borrow/return.
Drop into XAMPP's `htdocs`, import the SQL, and go.

## 📁 Project Structure

```
Technical_Inventory/
├── config/database.php
├── classes/
│   ├── Equipment.php
│   ├── BorrowRequest.php
│   ├── User.php
│   └── Notification.php
├── admin/
│   ├── admin_dashboard.php
│   ├── manage_equipment.php
│   ├── history.php
│   ├── actions.php
│   └── includes/{sidebar.php, header.php}
├── student/
│   ├── student_dashboard.php
│   ├── history.php
│   └── actions.php
├── auth/
│   ├── admin_login.php   / admin_logout.php
│   └── student_login.php / student_logout.php
├── assets/
│   ├── upload/           (equipment images)
│   ├── images/logo.svg
│   ├── css/  (admin_dashboard, manage_equipment, history, student_dashboard, admin_login, student_login, landing)
│   └── js/   (admin.js, student.js, charts.js)
├── database/technical_inventory.sql
└── index.php
```

## 🚀 Setup (XAMPP, 4 steps)

1. **Copy** the entire `Technical_Inventory/` folder into `C:\xampp\htdocs\`.
2. **Start** *Apache* and *MySQL* in the XAMPP control panel.
3. **Import the database**
   - Open `http://localhost/phpmyadmin`
   - Click **Import** → select `database/technical_inventory.sql` → Go.
   - (Or CLI: `mysql -u root < database/technical_inventory.sql`)
4. **Open** `http://localhost/Technical_Inventory/`

If your MySQL user/password differ from XAMPP defaults, edit `config/database.php`.

## 🔑 Demo accounts (password for all: `password123`)

| Role    | ID                   |
|---------|----------------------|
| Admin   | `ADMIN-001`          |
| Student | `C24-01-1524-MAN121` |
| Student | `C24-01-9654-MAN121` |

Student IDs must match `C##-##-####-MAN###` (strict format validation).

## ✨ Features

**Student**
- Login (strict ID format)
- Browse catalog with search
- Submit borrow request (Purpose / Classroom / Teacher)
- **Cancel own pending requests** (from *My Borrow History*)
- Notifications (approved / rejected / return)
- **Email alerts** on approve / reject (see mail setup below)
- Personal borrow history

**Admin**
- Login
- Dashboard: **Total, Available, Pending, Borrowing, Maintenance**
- Monthly Equipment Activity chart (Borrowed vs Returned) — pure canvas, no libs
- Active Checkouts with **Mark as Return** → *Good / Broken / Lost*
- Approve / Reject pending requests
- Manage Catalog: add name + image → **auto-generated `SN-XXXXXXXX` serial**
- History Log with search + **Excel export**
- Hover-expand sidebar
- Notification bell with unread dot

## 🔧 Notes / Conventions

- Passwords stored via `password_hash()` (bcrypt).
- Serial numbers auto-generated with `bin2hex(random_bytes(4))`.
- Uploads land in `assets/upload/` (ensure it's writable).
- Excel export uses HTML-table trick (opens directly in Excel).
- No JS frameworks; only vanilla JS + CSS animations/hovers.

## ✉️ Email alerts (optional, XAMPP)

Emails are OFF by default. To enable:

1. Edit `xampp/sendmail/sendmail.ini`:
   ```
   [sendmail]
   smtp_server=smtp.gmail.com
   smtp_port=587
   smtp_ssl=tls
   auth_username=your.email@gmail.com
   auth_password=YOUR_16_CHAR_APP_PASSWORD
   ```
2. Edit `xampp/php/php.ini` → `[mail function]`:
   ```
   SMTP=smtp.gmail.com
   smtp_port=587
   sendmail_from=your.email@gmail.com
   sendmail_path="\"C:\xampp\sendmail\sendmail.exe\" -t"
   ```
3. Restart Apache.
4. Open `config/mail.php` and set `'enabled' => true` (also update `from`).

Every send is logged to `assets/upload/mail.log` (even when disabled) — handy for debugging.

## 🧪 Sanity checks

- After login, `http://localhost/Technical_Inventory/admin/admin_dashboard.php` must load with 3 seeded equipment items.
- Log in as student, submit a request → admin sees it in **Pending Requests** and receives a notification.
- Approve it → equipment becomes *Borrowed*, student is notified.
- **Mark as Return** → choose condition → equipment status updates:
  - Good → Available
  - Broken → Maintenance
  - Lost → Lost

Enjoy!
