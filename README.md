# SmartClock PLN

Aplikasi SmartClock PLN adalah sistem absensi yang dirancang untuk mempermudah pencatatan kehadiran, izin, dan pengelolaan data karyawan secara digital.  Dilengkapi dengan tampilan modern, dashboard interaktif, dan fitur login terpisah antara HRD (Admin) dan User (Karyawan).

## Dua Versi Aplikasi

Aplikasi ini terdiri dari dua versi utama yang saling terhubung dengan satu database, yaitu smartclock.

### 1. Versi User (Karyawan)
Digunakan oleh seluruh karyawan untuk:
- Melakukan absen masuk dan pulang
- Mengajukan izin/cuti
- Melihat riwayat absensi pribadi
- Diakses melalui:  
http://localhost/smartclock-pln/user/

### 2. Versi HRD (Admin)
Digunakan oleh HRD untuk:
- Melihat rekap absensi seluruh karyawan
- Melihat data karyawan
- Melihat daftar izin/cuti karyawan
- Melihat Executive Summary Dashboard (grafik, statistik, dan tren absensi)
- Diakses melalui:  
http://localhost/smartclock-pln/hrd/

## Struktur Folder

smartclock-pln/
│
├── database/
│ └── smartclock.sql # File database MySQL
│
├── hrd/ # Halaman dan fitur untuk HRD/Admin
│
├── user/ # Halaman dan fitur untuk Karyawan/User
│
└── README.md # Dokumentasi project

## Cara Instalasi di Localhost (XAMPP)

1. Jalankan XAMPP, lalu aktifkan Apache dan MySQL.  
2. Salin folder `smartclock-pln` ke direktori: C:\xampp\htdocs\
3. Buka browser, lalu masuk ke:
http://localhost/phpmyadmin
4. Buat database baru dengan nama: smartclock
5. Klik menu Import, lalu pilih file: database/smartclock.sql
6. Setelah proses import selesai, buka aplikasi melalui browser:
- Versi Karyawan (User):  
  http://localhost/smartclock-pln/user/
- Versi HRD (Admin):  
  http://localhost/smartclock-pln/hrd/login.php

## Teknologi yang Digunakan

- PHP
- MySQL (phpMyAdmin)
- HTML / CSS / JavaScript
- Visual Studio Code

## Cara Update ke GitHub

Setiap kali ada perubahan pada project lokal di VS Code:

```bash
git add .
git commit -m "Update project files"
git push