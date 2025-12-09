---
description: How to run the FRUSH Admin Panel locally
---

# Running the FRUSH Admin Panel

## Prerequisites
- PHP 8.0+ installed
- MySQL (XAMPP recommended)

## Steps to Run

// turbo-all

### 1. Start MySQL
Open XAMPP Control Panel and click **Start** next to MySQL, OR run:
```
Start-Process "C:\xampp\mysql\bin\mysqld.exe" -ArgumentList '--defaults-file="C:\xampp\mysql\bin\my.ini"' -WindowStyle Hidden
```

### 2. Start the Laravel Development Server
```bash
cd d:\FRUSH\frush
php artisan serve --host=127.0.0.1 --port=8000
```

### 3. Access the Admin Panel
Open your browser and go to:
- **Admin Login:** http://127.0.0.1:8000/admin/auth/login
- **Homepage:** http://127.0.0.1:8000

### Default Admin Credentials
- **Email:** admin@admin.com
- **Password:** 12345678

## Quick One-Liner (PowerShell)
```powershell
cd d:\FRUSH\frush; php artisan serve --host=127.0.0.1 --port=8000
```

## Troubleshooting

### If MySQL is not running
```powershell
Start-Process "C:\xampp\mysql\bin\mysqld.exe" -ArgumentList '--defaults-file="C:\xampp\mysql\bin\my.ini"' -WindowStyle Hidden
```

### If you get database errors
```bash
php artisan migrate --force
```

### Clear cache if things look wrong
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```
