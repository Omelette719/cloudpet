# CloudPet — Platform IaaS (Infrastructure as a Service)

CloudPet adalah platform cloud computing berbasis web yang memungkinkan pengguna membuat dan mengelola virtual machine, cloud IDE, Jupyter notebook, managed database, block storage, dan object storage — semuanya berjalan di atas Docker container dengan billing per jam.

## Daftar Isi

- [Arsitektur Sistem](#arsitektur-sistem)
- [Prasyarat](#prasyarat)
- [Instalasi dan Menjalankan](#instalasi-dan-menjalankan)
- [Konfigurasi Environment](#konfigurasi-environment)
- [Fitur Platform](#fitur-platform)
- [Infrastruktur MiniStack](#infrastruktur-ministack)
- [Sistem Billing](#sistem-billing)
- [Sistem Membership](#sistem-membership)
- [Rancangan Database](#rancangan-database)
- [Perintah Artisan](#perintah-artisan)
- [API Endpoints](#api-endpoints)
- [Akun Default](#akun-default)

---

## Arsitektur Sistem

```
+-----------------------------------------------------+
|                   Browser (User)                     |
+------------------------+----------------------------+
                         |
+------------------------v----------------------------+
|              Laravel Application                      |
|  +----------+ +----------+ +----------+             |
|  | Livewire | |  Blade   | | REST API |             |
|  +----------+ +----------+ +----------+             |
|  +------------------------------------------+       |
|  |            Services Layer                 |       |
|  |  ComputeService  |  DatabaseService       |       |
|  |  VolumeService   |  BillingService        |       |
|  +------------------------------------------+       |
+-------+-------------+-------------+-----------------+
        |              |              |
+-------v------+ +----v------+ +-----v------+
|   Docker     | | MiniStack | |   MySQL    |
|  Containers  | | (port     | |  Database  |
|  VM/IDE/NB   | |  4566)    | |            |
|  Database    | |  EC2+S3   | |            |
+--------------+ +-----------+ +------------+
```

### Komponen Utama

| Komponen | Fungsi |
|----------|--------|
| **Laravel** | Web framework PHP |
| **Docker Desktop** | Menjalankan container untuk VM, IDE, Notebook, Database |
| **MiniStack (LocalStack)** | Emulator AWS di port 4566: API EC2 (control plane volume) dan S3 (object storage) |
| **MySQL** | Database aplikasi (users, billing, instances, dll) |

### Alur Kerja

1. User buka browser, login ke CloudPet
2. Buat block volume (Docker named volume + EC2 API ke MiniStack)
3. Buat compute instance (Docker container) dengan volume terpasang
4. Instance berjalan, user akses via web terminal / VS Code / Jupyter
5. Billing dipotong per jam dari saldo user
6. Admin memantau semua resource dan server dari dashboard

---

## Prasyarat

| Software | Versi | Keterangan |
|----------|-------|------------|
| **PHP** | >= 8.3 | Termasuk extension: pdo_mysql, pdo_pgsql, curl |
| **MySQL** | >= 8.0 | Database server (bisa pakai XAMPP, Laragon, atau Herd built-in) |
| **Composer** | >= 2.x | PHP dependency manager |
| **Node.js** | >= 18 | Untuk build frontend (Vite + Tailwind) |
| **Docker Desktop** | Latest | Harus running sebelum menggunakan fitur cloud |
| **Git** | Latest | Version control |

---

## Instalasi dan Menjalankan

### 1. Clone dan Install Dependencies

```bash
git clone <repository-url> cloudpet
cd cloudpet
composer install
npm install
```

### 2. Setup Environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` sesuai kebutuhan (lihat [Konfigurasi Environment](#konfigurasi-environment)).

Buat database MySQL:
```sql
CREATE DATABASE cloudpet;
```

### 3. Setup Database

```bash
php artisan migrate
php artisan db:seed
```

Ini membuat:
- Akun admin default
- Akun user demo
- Database plans (db-micro, db-small, db-medium)

### 4. Build Frontend

```bash
npm run build
```

Untuk development dengan hot-reload:
```bash
npm run dev
```

### 5. Jalankan Docker Desktop

Buka Docker Desktop, pastikan sudah running. Lalu jalankan infrastruktur:

```bash
docker compose up -d
```

Ini menjalankan:
- **MiniStack** (port 4566) — Emulator AWS (EC2 + S3)
- **Jupyter** (port 28171) — Base notebook image
- **Code Server** (port 29670) — Base IDE image

### 6. Jalankan Aplikasi

```bash
php artisan serve
```
Buka `http://localhost:8000`

### 7. Jalankan Scheduler (opsional)

Untuk billing otomatis per jam:
```bash
php artisan schedule:work
```

---

## Konfigurasi Environment

File `.env` yang perlu disesuaikan:

```env
APP_NAME=CloudPet
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cloudpet
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=database

AWS_ACCESS_KEY_ID=test
AWS_SECRET_ACCESS_KEY=test
MINISTACK_URL=http://localhost:4566
MINISTACK_API_KEY=ministack_test_key_7f3a9b2c4d6e8f1a

COMPUTE_FORCE_LOCAL_RUNTIME=true
```

---

## Fitur Platform

### User

| Fitur | Deskripsi |
|-------|-----------|
| **Cloud Computing** | Buat VM (SSH + web terminal), Cloud IDE (VS Code), atau Jupyter Notebook. Pilih vCPU dan RAM bebas sesuai membership. |
| **Block Storage** | Volume disk virtual. Pasang ke instance sebagai primary disk. Data persisten meski instance di-terminate. |
| **Managed Database** | PostgreSQL 14/15, MySQL 5.7/8, MariaDB 10. Buat tabel, kelola data, dan jalankan SQL query langsung dari browser. |
| **Object Storage** | Bucket S3-compatible. Upload dan kelola file via browser. |
| **Membership** | 4 tier (Free/Starter/Pro/Business) menentukan batas vCPU, RAM, volume, bucket, dan database. |
| **Billing** | Pay-as-you-go per jam. Top-up saldo, riwayat transaksi. Auto-stop saat saldo habis. |

### Admin

| Fitur | Deskripsi |
|-------|-----------|
| **Server Monitor** | Real-time CPU, RAM, disk, Docker container — langsung dari dashboard. |
| **Kelola Plans** | CRUD paket layanan database (harga, spec, resource). |
| **Users & Billing** | Kelola user: adjust saldo, set status, ubah membership, lihat transaksi. |
| **Logs & Monitoring** | Activity logs, resource state logs (PROVISIONING -> RUNNING dll), system error logs dari Laravel. |

---

## Infrastruktur MiniStack

MiniStack (berbasis LocalStack) adalah emulator AWS lokal yang berjalan di Docker port 4566. Semua layanan CloudPet menggunakan MiniStack sebagai **control plane** melalui AWS SDK PHP.

### EC2 API — Compute Instance + Block Storage

```
Laravel --Ec2Client--> MiniStack:4566
                         |
    Compute:  RunInstances / StopInstances / StartInstances / TerminateInstances
    Volume:   CreateVolume / DeleteVolume / AttachVolume / DetachVolume
```

- **Compute**: EC2 API mencatat lifecycle instance. Data plane menggunakan Docker container (VM/IDE/Notebook).
- **Block Storage**: EC2 API mencatat lifecycle volume. Data plane menggunakan Docker named volume (`cp_vol_{id}`).

### RDS API — Managed Database

```
Laravel --RdsClient--> MiniStack:4566
                         |
    CreateDBInstance / DeleteDBInstance / StopDBInstance / StartDBInstance
```

RDS API mencatat lifecycle database. Data plane menggunakan Docker container (PostgreSQL/MySQL/MariaDB).

### S3 API — Object Storage

```
Laravel --S3Client--> MiniStack:4566
                         |
    CreateBucket / PutObject / GetObject / DeleteObject
```

Bucket S3 menyimpan file user secara nyata. Setiap user mendapat bucket dengan access key dan secret key unik.

### Konfigurasi di config/services.php

```php
'ministack' => [
    'url'        => env('MINISTACK_URL', 'http://127.0.0.1:4566'),
    'region'     => env('MINISTACK_REGION', 'id-1'),
    'aws_key'    => env('AWS_ACCESS_KEY_ID', 'test'),
    'aws_secret' => env('AWS_SECRET_ACCESS_KEY', 'test'),
],
```

---

## Sistem Billing

### Tarif

| Resource | Harga | Kapan Ditagih |
|----------|-------|---------------|
| **Compute Instance** | vCPU x Rp 500 + RAM_GB x Rp 500 per jam | Selama status RUNNING |
| **Block Storage** | size_GB x Rp 15 per jam | Selama volume ada (AVAILABLE/ATTACHED) |
| **Managed Database** | Rp 1.500 - 6.000 per jam (sesuai plan) | Selama status RUNNING |
| **Object Storage** | usage_GB x Rp 150 per jam | Berdasarkan ukuran objek di bucket |

### Contoh Perhitungan

```
Instance: 2 vCPU + 2 GB RAM  = (2 x 500) + (2 x 500) = Rp 2.000/jam
Volume:   20 GB               = 20 x 15               = Rp   300/jam
Database: db-small            =                          Rp 3.000/jam
                                                 Total:  Rp 5.300/jam
```

### Alur

1. User top-up saldo (minimal Rp 1.000)
2. `billing:tick` berjalan setiap jam, potong saldo per resource aktif
3. Saldo habis -> akun di-SUSPEND, semua instance dan database di-stop otomatis
4. User top-up lagi -> akun kembali ACTIVE

---

## Sistem Membership

| Tier | Compute Max | Block Storage | Bucket | Database | Harga |
|------|-------------|---------------|--------|----------|-------|
| **Free** | 1 vCPU, 2 GB RAM | 30 GB total | 1 | 1 (db-micro) | Gratis |
| **Starter** | 2 vCPU, 4 GB RAM | 100 GB total | 3 | 3 (db-micro, db-small) | Rp 15.000/bulan |
| **Pro** | 4 vCPU, 8 GB RAM | 512 GB total | 10 | 5 (semua plan) | Rp 50.000/bulan |
| **Business** | 8 vCPU, 16 GB RAM | 2 TB total | 50 | 20 (semua plan) | Rp 150.000/bulan |

Membership menentukan batas maksimal resource yang bisa digunakan. Biaya membership dipotong dari saldo per bulan.

---

## Rancangan Database

### Entity Relationship

```
users
 |-- compute_instances
 |      |-- block_volumes (FK: compute_instance_id)
 |
 |-- managed_databases --> plans (FK: plan_id)
 |
 |-- storage_buckets
 |
 |-- billing_transactions
 |
 |-- activity_logs

resource_state_logs    (standalone audit trail)
system_error_logs      (standalone error log)
sessions, cache        (framework tables)
```

### Tabel: users

Menyimpan data pengguna, saldo, dan membership.

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | bigint PK | Auto-increment |
| name, email, password | string | Kredensial |
| role | enum | `admin` atau `user` |
| animal_avatar | string | Emoji avatar |
| balance | decimal(15,2) | Saldo prepaid (Rp) |
| account_status | enum | `ACTIVE`, `SUSPENDED`, `VERIFYING` |
| storage_plan | string | Tier: `free`, `starter`, `pro`, `business` |
| storage_quota_gb | integer | Volume limit dari tier |
| storage_plan_expires_at | timestamp | Tanggal berakhir membership |

### Tabel: compute_instances

Instance VM, Cloud IDE, atau Jupyter Notebook.

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | bigint PK | Auto-increment |
| user_id | FK -> users | Pemilik |
| name | string | Nama instance (e.g., `vm-aB3xK2pQ`) |
| plan | string | Deskripsi resource (e.g., `2c-2GB`) |
| os | string | OS key (e.g., `ubuntu-22.04`) |
| status | enum | `PROVISIONING`, `RUNNING`, `STOPPED`, `TERMINATED` |
| metadata | json | Container ID, port, password, volume info |
| provision_log | text | Log proses pembuatan |
| price_per_hour | decimal | Tarif per jam |
| usage_hours, cost | decimal | Akumulasi penggunaan |
| deleted_at | timestamp | Soft delete |

### Tabel: block_volumes

Block storage berbasis Docker named volume.

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | bigint PK | Auto-increment |
| user_id | FK -> users | Pemilik |
| compute_instance_id | FK -> compute_instances | Instance terpasang (nullable) |
| volume_name | string | Nama volume |
| size_gb | integer | Ukuran (GB) |
| status | string | `PROVISIONING`, `AVAILABLE`, `ATTACHED`, `ERROR` |
| provider_volume_id | string | EC2 Volume ID dari MiniStack |
| provision_log | text | Log pembuatan |

### Tabel: managed_databases

Managed database berbasis Docker container.

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | uuid PK | - |
| user_id | FK -> users | Pemilik |
| plan_id | FK -> plans | Paket dipilih |
| engine | string | `postgres-15`, `mysql-8`, `mariadb-10`, dll |
| db_name, db_user, db_password | string | Kredensial |
| host, port | string/int | Endpoint koneksi |
| status | string | `PROVISIONING`, `RUNNING`, `STOPPED`, `TERMINATED`, `ERROR` |
| metadata | json | Container ID, driver info |
| price_per_hour | decimal | Tarif per jam |

### Tabel: plans

Katalog paket layanan database (dikelola admin).

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | uuid PK | - |
| service_type | enum | `DATABASE`, `STORAGE` |
| name | string | Nama plan (e.g., `db-micro`) |
| vcpu, ram, storage | integer | Spec resource |
| price | decimal | Harga per jam (Rp) |

### Tabel: storage_buckets

Bucket S3-compatible.

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | uuid PK | - |
| user_id | FK -> users | Pemilik |
| bucket_name | string (unique) | Nama bucket |
| access_key, secret_key | string | Kredensial S3 |

### Tabel: billing_transactions

Log semua transaksi keuangan.

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | uuid PK | - |
| user_id | FK -> users | User terkait |
| amount | decimal(15,2) | Positif = top-up, negatif = debit |
| transaction_type | enum | `TOPUP`, `HOURLY_USAGE`, `MONTHLY_BILLING`, `REFUND` |
| description | string | Keterangan |

### Tabel: activity_logs

Audit trail aktivitas pengguna.

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | uuid PK | - |
| user_id | FK -> users | Pelaku |
| action | string | Nama aksi |
| resource_type, resource_id | string | Resource yang diakses |
| ip_address | string | IP user |

### Tabel: resource_state_logs

Audit trail perubahan status resource.

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | uuid PK | - |
| resource_type | string | `compute_instance`, `block_volume`, `managed_database` |
| resource_id | uuid | ID resource |
| old_state, new_state | string | Status sebelum dan sesudah |
| message | text | Pesan deskriptif |

---

## Perintah Artisan

### Provisioning (dipanggil otomatis)

```bash
php artisan compute:provision {instance_id}
php artisan database:provision {database_id}
```

### Billing dan Monitoring

```bash
php artisan billing:tick          # Potong saldo resource aktif (tiap jam)
php artisan cloud:meter-storage   # Hitung dan tagih object storage
php artisan storage:sync          # Ringkasan block storage per user
php artisan disk:enforce          # Cek kuota disk, stop jika melebihi limit
```

### Database

```bash
php artisan migrate               # Jalankan migration
php artisan db:seed               # Seed admin + demo user + plans
```

### Scheduler (jalankan untuk billing otomatis)

```bash
php artisan schedule:work
```

Menjalankan: `billing:tick` (jam), `cloud:meter-storage` (jam), `storage:sync` (15 menit)

---

## API Endpoints

### User API

| Method | Endpoint | Keterangan |
|--------|----------|------------|
| GET | `/cloud/api/plans` | Resource options + volume tersedia |
| POST | `/cloud/api/instances` | Buat instance (cpu, ram, type, os, volume_id) |
| POST | `/cloud/api/instances/{id}/action` | Start/stop/restart/terminate |
| GET | `/cloud/api/instances/{id}/stats` | CPU, RAM, disk real-time |
| POST | `/cloud/api/databases` | Buat database (plan_id, engine) |
| POST | `/cloud/api/databases/{id}/action` | Start/stop/terminate |
| GET | `/cloud/api/databases/{id}/tables` | Daftar tabel |
| POST | `/cloud/api/databases/{id}/tables/create` | Buat tabel (GUI) |
| POST | `/cloud/api/databases/{id}/tables/{t}/rows` | Insert baris |
| POST | `/cloud/api/databases/{id}/query` | Jalankan SQL |
| POST | `/cloud/api/billing/topup` | Top-up saldo |
| POST | `/cloud/api/billing/storage-subscribe` | Langganan membership |

### Admin API

| Method | Endpoint | Keterangan |
|--------|----------|------------|
| GET | `/admin/api/stats` | Platform statistics |
| GET | `/admin/api/server` | Server monitor (CPU, RAM, disk, Docker) |
| CRUD | `/admin/api/plans` | Kelola database plans |
| GET/PUT | `/admin/api/users/{id}` | Kelola user + adjust saldo |
| GET | `/admin/api/logs/activity` | Activity logs |
| GET | `/admin/api/logs/resource-state` | Resource state logs |
| GET | `/admin/api/logs/errors` | System error logs |

---

## Akun Default

Setelah `php artisan db:seed`:

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@cloudpet.id | Admin@12345 |
| User | budi@cloudpet.id | User@12345 |

---

## Tech Stack

| Layer | Teknologi |
|-------|-----------|
| Backend | Laravel 13, PHP 8.3, Livewire 4 |
| Frontend | Blade, Tailwind CSS 4, Vite 8 |
| Database | MySQL 8 |
| Infrastructure | Docker Desktop, MiniStack (LocalStack), AWS SDK PHP (EC2 + RDS + S3) |
| Authentication | Laravel Fortify + 2FA |
