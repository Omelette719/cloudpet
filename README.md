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

---

## Cloud Compute & Migrations
This repository includes a lightweight Cloud Compute demo (POC) with:

- ECS-style provisioning against a local MiniStack emulator (`ministack` service).
- Interactive runtimes: Jupyter Notebook and code-server (IDE) for quick developer testing.
- Server-side metering (records `started_at`/`stopped_at`), authoritative price calculation, and CSV export for billing.

Quick steps to run compute locally

1. Run services (MinIO, MiniStack, Jupyter, code-server):

```bash
docker compose up -d
```

2. Run migrations (adds metering columns):

```bash
php artisan migrate --force
```

3. Open the compute UI (sign in as a user):

```
GET /cloud/computing
```

4. Create an instance via the UI (choose runtime, vRAM, vCPU). Interactive runtimes can be exposed on the host in two ways:

- Run the demo runtimes directly via `docker compose` (recommended for local dev):

	- Jupyter: http://localhost:28171/?token=... (when `docker compose up -d jupyter`)
	- code-server: http://localhost:29670/ (when `docker compose up -d code-server`, password shown in UI / metadata)

- Or let the compute provisioning create a runtime via MiniStack/ECS. In that case the runtime may be started inside MiniStack's network and the host port will be recorded in instance metadata (e.g. `jupyter_host_port`). When running via MiniStack the host port may not always be directly reachable from your host — for local developer convenience the service now attempts to start interactive runtimes with `docker run` on the host (see below).

Smoke tests and usage export

- Create and exercise an instance via command line (creates → stop → start → terminate):

```bash
php scripts/compute_smoke.php [plan]
# e.g. php scripts/compute_smoke.php jupyter
```

- Export usage CSV (no auth required, writes file to `storage/exports`):

```bash
php scripts/export_usage.php
```

- Admin web CSV export (requires admin):

```
GET /cloud/api/usage/export
```

Configuration & pricing

- Rates are server-authoritative and configurable in `config/compute.php` or via env:

	- `COMPUTE_CPU_RATE` (Rp per CPU unit)
	- `COMPUTE_VRAM_RATE` (Rp per GB)

- The UI calculates an estimate client-side; the server computes and stores authoritative `price_per_hour`, `usage_hours`, and `cost` when instances stop/terminate.

Interactive runtimes & auto-open (developer convenience)

- By default, for interactive runtimes (`jupyter` / `code-server`) in local development the compute service will attempt to run the runtime on the host via `docker run` so the container's port is actually bound to `localhost` and the browser can open the link directly. This behavior is controlled with the env var `COMPUTE_FORCE_LOCAL_RUNTIME` (default: `true`).

- The UI includes an "Auto-open runtime saat RUNNING" checkbox. When enabled the browser will automatically open the runtime link once (per browser session) after the instance becomes `RUNNING`. The preference is stored in `localStorage` under the key `cloudpet_auto_open` (`'1'` = enabled, `'0'` = disabled).

- If you prefer to run the runtimes via `docker compose` yourself, start them with:

```bash
docker compose up -d jupyter code-server
```

Then create an instance (or use the pre-launched services) and open the shown URL in the instance card.

Stripe (optional)

- Stripe Usage Billing integration was included as a POC but has been removed per project preference.

Security & notes

- The compute/demo flows are a POC for local development. In production you should:
	- Use proper authentication and permission checks for actions and exports.
	- Proxy and secure interactive runtimes (TLS, auth) instead of exposing raw host ports.
	- Review cost/pricing logic and currency handling before billing real customers.

If you want, I can add an admin UI to manage `stripe_subscription_item_id` per user, or commit and push these changes to the `compute` branch.

## 🔬 Local verification checklist (Compute)

Use these quick steps to validate interactive runtimes and auto-open behavior on your development machine:

- Start required services (MiniStack + runtimes):

```bash
# start all services (ministack, minio, jupyter, code-server)
docker compose up -d
```

- Run migrations and start dev server:

```bash
php artisan migrate --force
npm run dev   # (Vite watches assets; open http://localhost:5173 if needed)
```

- Create and exercise a Jupyter instance (smoke test):

```bash
php scripts/compute_smoke.php jupyter
# or create via UI: /cloud/computing
```

- Verify the instance metadata and host access:

```bash
# check instance metadata in DB (jupyter_host_port, token)
php artisan tinker --execute="\App\Models\ComputeInstance::latest()->first()->metadata"

# verify host port is listening and respond
curl -I http://localhost:<jupyter_host_port>/lab
```

- Notes & toggles:
	- `COMPUTE_FORCE_LOCAL_RUNTIME=true` (env) forces `docker run` fallback so runtimes bind to `localhost` for easy access.
	- Auto-open preference stored in browser `localStorage` key `cloudpet_auto_open` (`'1'` = enabled).
	- Default compose host ports: Jupyter `28171`, code-server `29670` (if you start them with `docker compose up -d jupyter code-server`).

If you want, I can also add a short troubleshooting section to this README that lists common failure modes (port collisions, MiniStack networking, or missing tokens).
