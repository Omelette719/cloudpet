# CloudPet - Cloud Service Platform 🐾☁️

Simple cloud platform untuk demonstrasi upload foto pet menggunakan MinIO S3-compatible storage.

## 🚀 Quick Start

### 1. Install Dependencies

```bash
# PHP dependencies
composer install

# JavaScript dependencies
npm install
```

### 2. Setup Environment

```bash
# Copy environment file
cp .env.example .env

# Generate app key
php artisan key:generate

# Migrate database
php artisan migrate:refresh --force

# Create storage link
php artisan storage:link
```

### 3. Start MinIO Storage

```bash
# Run MinIO container
docker-compose up -d

# Verify running
docker-compose ps
```

Bucket `cloudpet-bucket` akan dibuat **otomatis** saat MinIO start.

### 4. Run Application

```bash
# Terminal 1 - PHP Server
php artisan serve

# Terminal 2 - Vite Dev Server (optional)
npm run dev
```

Akses aplikasi di: **http://localhost:8000**

---

## 📝 Login Credentials

| Role  | Email             | Password |
| ----- | ----------------- | -------- |
| User  | user@example.com  | password |
| Admin | admin@example.com | password |

---

## ✨ Features

### 📸 Upload Foto Pet

- Upload ke **MinIO S3** (Primary)
- Fallback ke **Local Storage** jika MinIO unavailable
- Max file: 1MB
- Format: Image only

**Access:** Dashboard → "Demo Upload Foto Pet"

### 🔐 Authentication

- Register/Login
- Logout dengan confirmation
- Two-factor authentication ready

### ☁️ MinIO Console

- **URL:** http://localhost:8900
- **Username:** cloudpet_user
- **Password:** cloudpet_password

---

## 🛠️ Development

### Clear Cache

```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

### Run Tests

```bash
php artisan test
```

### Stop Services

```bash
# Stop MinIO
docker-compose down

# Stop PHP server
Ctrl + C
```

---

## 📚 Documentation

- [MinIO Setup Guide](./MINIO_SETUP.md) - Detailed MinIO configuration
- [Bucket Status](./BUCKET_STATUS.md) - Bucket information & troubleshooting

---

## 🔍 Troubleshooting

### Upload gagal "Photo field is required"

- Pastikan file sudah dipilih sebelum click upload
- Browser console check untuk error messages

### MinIO not accessible

```bash
# Check container status
docker-compose ps

# Check logs
docker-compose logs minio

# Restart
docker-compose restart minio
```

### Storage link error

```bash
php artisan storage:link --force
```

---

## 📋 Tech Stack

- **Backend:** Laravel 13 + Livewire
- **Frontend:** Blade + Tailwind CSS + Vite
- **Storage:** MinIO (S3-compatible)
- **Database:** SQLite
- **Auth:** Laravel Fortify

---

## 📦 Project Structure

```
cloudpet/
├── app/
│   ├── Livewire/
│   │   ├── UploadPetPhoto.php      # Upload component
│   │   ├── Auth/Logout.php         # Logout component
│   │   └── Dashboard/
│   └── Models/
├── resources/
│   ├── views/
│   │   ├── livewire/upload-pet-photo.blade.php
│   │   ├── user-dashboard.blade.php
│   │   └── layouts/app.blade.php
│   └── css/
├── routes/web.php                   # Routes dengan explicit logout
├── docker-compose.yaml              # MinIO setup
└── README.md                         # This file
```

---

## ✅ Status Fitur

- ✅ Upload Foto Pet ke MinIO
- ✅ Logout dengan Confirmation
- ✅ Local Storage Fallback
- ✅ Database Migration
- ✅ Authentication
- ✅ Responsive UI

---

## 🤝 Support

Untuk issues atau questions, check dokumentasi atau logs:

```bash
tail -f storage/logs/laravel.log
```

---

**Created:** June 2, 2026  
**Version:** 1.0.0  
**License:** MIT
