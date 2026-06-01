# MinIO Storage Demo - Setup & Usage Guide

## Overview

Demo aplikasi untuk mengupload foto pet ke MinIO (S3-compatible object storage). Ini mensimulasikan AWS S3 service dengan menggunakan MinIO yang berjalan di Docker.

## Prerequisites

- Docker & Docker Compose installed
- Laravel 11+ dengan Livewire
- PHP 8.1+

## Setup Instructions

### 1. **Start MinIO Container**

```bash
docker-compose up -d
```

MinIO akan berjalan di:

- **API Endpoint**: `http://localhost:9000`
- **Web Console**: `http://localhost:8900`
- **Credentials**:
    - Username: `cloudpet_user`
    - Password: `cloudpet_password`

### 2. **Configure Environment**

Update `.env` file:

```env
# Ubah dari 'local' ke 's3'
FILESYSTEM_DISK=s3

# MinIO Credentials (sesuai dengan docker-compose.yaml)
AWS_ACCESS_KEY_ID=cloudpet_user
AWS_SECRET_ACCESS_KEY=cloudpet_password
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=cloudpet-bucket

# MinIO Endpoint
AWS_ENDPOINT=http://localhost:9000
AWS_USE_PATH_STYLE_ENDPOINT=true
AWS_URL=http://localhost:9000/cloudpet-bucket
```

### 3. **Verify Configuration**

```bash
php artisan config:clear
php artisan config:cache
```

### 4. **Access the Feature**

1. Login ke aplikasi
2. Buka **User Dashboard**
3. Scroll ke bagian **"Demo Upload Foto Pet (S3 / MinIO)"**
4. Upload file gambar (max 1MB)

## Files Modified/Created

### Livewire Component

- **File**: `app/Livewire/UploadPetPhoto.php`
- **Features**:
    - File upload validation (required, image, max 1MB)
    - S3/MinIO connectivity check
    - Error logging
    - User-friendly session messages

### View Template

- **File**: `resources/views/livewire/upload-pet-photo.blade.php`
- **Features**:
    - File input dengan accept="image/\*"
    - Live preview menggunakan `temporaryUrl()`
    - Success/error messages
    - URL display dan image preview dari MinIO
    - Fallback image jika gagal load

### Dashboard Integration

- **File**: `resources/views/user-dashboard.blade.php`
- **Change**: Ditambahkan `@livewire('UploadPetPhoto')` di akhir

### Configuration Files

- **`.env.example`**: Ditambahkan contoh konfigurasi MinIO

## Troubleshooting

### Error: "MinIO S3 disk tidak terhubung"

**Solution**:

```bash
# Pastikan MinIO container berjalan
docker-compose ps

# Jika tidak, start ulang
docker-compose down
docker-compose up -d

# Clear cache
php artisan config:clear
```

### Image tidak tampil setelah upload

**Possible causes**:

1. MinIO endpoint tidak accessible dari browser (CORS issue)
    - **Fix**: Ubah `AWS_URL` ke URL yang accessible dari browser
2. Bucket/file permissions
    - **Check**: `docker-compose.yaml` sudah set public access

### Upload timeout

**Solution**:

```php
// Di bootstrap/app.php atau middleware
// Tingkatkan timeout jika diperlukan
```

## Testing Upload

### Using cURL:

```bash
curl -X POST http://localhost/api/upload \
  -F "photo=@/path/to/image.jpg" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Manual Test:

1. Login dengan test user
2. Upload gambar dari user dashboard
3. Verifikasi di MinIO Console: `http://localhost:8900`
4. Check uploaded file di `cloudpet-bucket/pet-photos/`

## Architecture

```
┌─────────────────────────────────────────┐
│     Laravel Application (App)           │
│  ┌───────────────────────────────────┐  │
│  │  UploadPetPhoto.php (Livewire)   │  │
│  │  - Validate file                 │  │
│  │  - Store to S3                   │  │
│  │  - Generate URL                  │  │
│  └───────────────────────────────────┘  │
└─────────────────────────────────────────┘
                    ↓
         ┌──────────────────────────┐
         │  AWS SDK for PHP         │
         │  (Configured for MinIO)  │
         └──────────────────────────┘
                    ↓
         ┌──────────────────────────┐
         │   MinIO Container        │
         │  - API: localhost:9000   │
         │  - Web: localhost:8900   │
         │  - Storage: minio_data   │
         └──────────────────────────┘
```

## Docker Compose Services

### minio

- Main MinIO service yang menjalankan object storage
- API accessible di port 9000
- Web console di port 8900

### minio-setup

- Automatically membuat bucket dan set permissions
- Runs only once saat first startup
- Creates `cloudpet-bucket` dan set public access

## Security Notes ⚠️

**For Development ONLY**:

- Credentials hardcoded di `.env` untuk demo
- Public bucket access untuk testing

**For Production**:

- Use environment variables via Docker secrets
- Implement proper IAM policies
- Use HTTPS endpoint
- Enable encryption
- Restrict bucket access dengan policies

## Next Steps

1. **Integration dengan User Model**: Link uploaded photos ke pet records
2. **Storage Quota**: Track storage usage per user
3. **Cleanup**: Implement garbage collection untuk unused files
4. **CDN**: Integrate dengan CDN untuk faster delivery
5. **Backup**: Setup backup strategy untuk S3 data

---

**Status**: ✅ Demo siap digunakan
**Last Updated**: June 2, 2026
