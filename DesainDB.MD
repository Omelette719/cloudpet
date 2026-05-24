markdown_content = """# Dokumentasi Desain Database & Sistem Logging

## Mini Cloud Provider Infrastructure

Dokumen ini berisi rancangan basis data (_database design_) dan arsitektur pelaporan (_logging_) untuk platform penyedia layanan cloud mini yang mencakup tiga layanan utama: **Compute Service (VPS/CaaS)**, **Object Storage Service**, dan **Managed Database Service (DBaaS)**.

---

## 1. Ringkasan Arsitektur Tabel

Sistem ini dirancang menggunakan relasi data yang ketat untuk menjamin keamanan, auditabilitas, dan akurasi keuangan (_billing_). Seluruh tabel menggunakan **PostgreSQL** dengan pendekatan ID berbasis **UUID** untuk mencegah eksploitasi keamanan seperti _Insecure Direct Object Reference (IDOR)_.

Sistem dibagi menjadi dua kelompok tabel utama:

1. **Core Service Tables**: Mengelola data pengguna, paket harga, dan _resource_ infrastruktur yang aktif.
2. **Logging & Billing Tables**: Mengelola jejak audit aktivitas pengguna, siklus hidup mesin, rekaman kegagalan sistem, dan arus keuangan.

---

## 2. Detail Spesifikasi Tabel

### A. Kelompok Layanan Utama (Core Services)

#### 1. Tabel `users`

Menyimpan profil akun pelanggan beserta saldo berjalan untuk memfasilitasi sistem berbayar (_pay-as-you-go_ atau _prepaid_).

| Nama Kolom      | Tipe Data      | Atribut / Constraint                       | Keterangan                                        |
| :-------------- | :------------- | :----------------------------------------- | :------------------------------------------------ |
| `id`            | UUID           | PRIMARY KEY, Default: `uuid_generate_v4()` | Identifikasi unik pengguna.                       |
| `email`         | VARCHAR(255)   | UNIQUE, NOT NULL                           | Alamat email untuk login dan notifikasi.          |
| `password_hash` | VARCHAR(255)   | NOT NULL                                   | Hasil hash sandi aman (Bcrypt/Argon2).            |
| `balance`       | DECIMAL(15, 2) | DEFAULT: `0.00`                            | Saldo keuangan pengguna yang tersisa.             |
| `status`        | VARCHAR(20)    | DEFAULT: `'ACTIVE'`                        | Status akun (`ACTIVE`, `SUSPENDED`, `VERIFYING`). |
| `created_at`    | TIMESTAMP      | DEFAULT: `CURRENT_TIMESTAMP`               | Waktu pendaftaran akun.                           |

#### 2. Tabel `plans`

Mendefinisikan katalog paket spesifikasi komputasi, penyimpanan, atau database beserta tarif harganya.

| Nama Kolom     | Tipe Data      | Atribut / Constraint                       | Keterangan                                              |
| :------------- | :------------- | :----------------------------------------- | :------------------------------------------------------ |
| `id`           | UUID           | PRIMARY KEY, Default: `uuid_generate_v4()` | Identifikasi unik paket.                                |
| `service_type` | VARCHAR(50)    | NOT NULL                                   | Jenis layanan (`COMPUTE`, `STORAGE`, `DATABASE`).       |
| `name`         | VARCHAR(100)   | NOT NULL                                   | Nama pemasaran paket (misal: `Standard-1`, `DB-Large`). |
| `vcpu`         | INT            | NULLABLE                                   | Jumlah core CPU (khusus Compute/DB).                    |
| `ram`          | INT            | NULLABLE                                   | Kapasitas RAM dalam Satuan Megabytes (MB).              |
| `storage`      | INT            | NULLABLE                                   | Kapasitas penyimpanan dalam Gigabytes (GB).             |
| `price`        | DECIMAL(10, 2) | NOT NULL                                   | Nilai harga sewa (per jam atau per bulan).              |
| `created_at`   | TIMESTAMP      | DEFAULT: `CURRENT_TIMESTAMP`               | Waktu paket ini ditambahkan ke sistem.                  |

#### 3. Tabel `compute_instances`

Mencatat informasi virtual mesin atau kontainer (LXD/Docker) yang dideploy oleh pengguna.

| Nama Kolom   | Tipe Data    | Atribut / Constraint                        | Keterangan                                                |
| :----------- | :----------- | :------------------------------------------ | :-------------------------------------------------------- |
| `id`         | UUID         | PRIMARY KEY, Default: `uuid_generate_v4()`  | ID unik instance virtual.                                 |
| `user_id`    | UUID         | FOREIGN KEY (`users.id`) ON DELETE CASCADE  | Pemilik instance cloud.                                   |
| `plan_id`    | UUID         | FOREIGN KEY (`plans.id`) ON DELETE SET NULL | Paket spesifikasi yang digunakan.                         |
| `name`       | VARCHAR(100) | NOT NULL                                    | Nama VM yang ditentukan oleh pengguna.                    |
| `os`         | VARCHAR(50)  | NOT NULL                                    | Distribusi OS (contoh: `ubuntu-22.04`, `debian-12`).      |
| `ip_address` | VARCHAR(45)  | NULLABLE                                    | IP Address publik/privat yang dialokasikan.               |
| `status`     | VARCHAR(20)  | DEFAULT: `'PROVISIONING'`                   | State operasional (`PROVISIONING`, `RUNNING`, `STOPPED`). |
| `created_at` | TIMESTAMP    | DEFAULT: `CURRENT_TIMESTAMP`                | Waktu pembuatan instansiasi mesin.                        |

#### 4. Tabel `storage_buckets`

Menyimpan konfigurasi nama dan kredensial hak akses API untuk penyimpanan berbasis objek (S3-compatible via MinIO).

| Nama Kolom    | Tipe Data    | Atribut / Constraint                       | Keterangan                                      |
| :------------ | :----------- | :----------------------------------------- | :---------------------------------------------- |
| `id`          | UUID         | PRIMARY KEY, Default: `uuid_generate_v4()` | ID unik entri bucket.                           |
| `user_id`     | UUID         | FOREIGN KEY (`users.id`) ON DELETE CASCADE | Pemilik ruang penyimpanan objek.                |
| `bucket_name` | VARCHAR(63)  | UNIQUE, NOT NULL                           | Nama bucket unik global (standar penamaan S3).  |
| `access_key`  | VARCHAR(100) | NOT NULL                                   | Kunci akses API publik untuk autentikasi MinIO. |
| `secret_key`  | VARCHAR(255) | NOT NULL                                   | Kunci rahasia API (harus disimpan terenkripsi). |
| `created_at`  | TIMESTAMP    | DEFAULT: `CURRENT_TIMESTAMP`               | Waktu pembuatan bucket.                         |

#### 5. Tabel `managed_databases`

Menampung kredensial akses dan status provisioning klaster database instan (DBaaS) MySQL/PostgreSQL.

| Nama Kolom    | Tipe Data    | Atribut / Constraint                        | Keterangan                                       |
| :------------ | :----------- | :------------------------------------------ | :----------------------------------------------- |
| `id`          | UUID         | PRIMARY KEY, Default: `uuid_generate_v4()`  | ID unik database server.                         |
| `user_id`     | UUID         | FOREIGN KEY (`users.id`) ON DELETE CASCADE  | Pemilik resource database.                       |
| `plan_id`     | UUID         | FOREIGN KEY (`plans.id`) ON DELETE SET NULL | Paket kapasitas database server.                 |
| `engine`      | VARCHAR(50)  | NOT NULL                                    | Mesin database (`mysql-8.0`, `postgres-15`).     |
| `db_name`     | VARCHAR(100) | NOT NULL                                    | Nama database default yang otomatis dibuat.      |
| `db_user`     | VARCHAR(100) | NOT NULL                                    | Username utama akses database.                   |
| `db_password` | VARCHAR(255) | NOT NULL                                    | Password user database (Terenkripsi).            |
| `host`        | VARCHAR(255) | NULLABLE                                    | Domain/IP Address endpoint koneksi database.     |
| `port`        | INT          | NULLABLE                                    | Port jaringan koneksi (contoh: 3306, 5432).      |
| `status`      | VARCHAR(20)  | DEFAULT: `'CREATING'`                       | State DBaaS (`CREATING`, `ACTIVE`, `SUSPENDED`). |
| `created_at`  | TIMESTAMP    | DEFAULT: `CURRENT_TIMESTAMP`                | Waktu pembuatan database.                        |

---

### B. Kelompok Logging & Finansial

#### 6. Tabel `activity_logs` (Audit Trail)

Merekam semua operasi krusial yang dilakukan oleh pengguna melalui dashboard ataupun pemicu REST API untuk kebutuhan forensik dan pembuktian komplain.

| Nama Kolom      | Tipe Data    | Atribut / Constraint                        | Keterangan                                               |
| :-------------- | :----------- | :------------------------------------------ | :------------------------------------------------------- |
| `id`            | UUID         | PRIMARY KEY, Default: `uuid_generate_v4()`  | ID log aktivitas.                                        |
| `user_id`       | UUID         | FOREIGN KEY (`users.id`) ON DELETE SET NULL | Aktor pengubah data (tetap ada walau user dihapus).      |
| `action`        | VARCHAR(100) | NOT NULL                                    | Operasi kerja (`CREATE_VM`, `DELETE_BUCKET`, `STOP_DB`). |
| `resource_type` | VARCHAR(50)  | NOT NULL                                    | Modul target (`compute`, `storage`, `database`).         |
| `resource_id`   | UUID         | NOT NULL                                    | UUID dari resource terkait yang dimanipulasi.            |
| `ip_address`    | VARCHAR(45)  | NULLABLE                                    | Alamat IP client pengirim request API.                   |
| `created_at`    | TIMESTAMP    | DEFAULT: `CURRENT_TIMESTAMP`                | Waktu presisi kejadian aksi.                             |

#### 7. Tabel `resource_state_logs` (State Machine History)

Mencatat riwayat perubahan status operasional dari infrastruktur. Log ini penting untuk verifikasi metrik keandalan sistem (_SLA Calculation_) dan perhitungan tagihan berdasar durasi aktif mesin.

| Nama Kolom      | Tipe Data   | Atribut / Constraint                       | Keterangan                                                    |
| :-------------- | :---------- | :----------------------------------------- | :------------------------------------------------------------ |
| `id`            | UUID        | PRIMARY KEY, Default: `uuid_generate_v4()` | ID log siklus hidup mesin.                                    |
| `resource_type` | VARCHAR(50) | NOT NULL                                   | Klasifikasi entitas (`compute_instance`, `managed_database`). |
| `resource_id`   | UUID        | NOT NULL                                   | UUID penunjuk ke tabel resource asal.                         |
| `old_state`     | VARCHAR(50) | NULLABLE                                   | Status lama mesin sebelum berubah (misal: `RUNNING`).         |
| `new_state`     | VARCHAR(50) | NOT NULL                                   | Status baru mesin (misal: `STOPPED`, `TERMINATED`).           |
| `triggered_by`  | VARCHAR(50) | NOT NULL                                   | Pemicu perubahan (`USER_API`, `SYSTEM_CRON`, `ADMIN`).        |
| `created_at`    | TIMESTAMP   | DEFAULT: `CURRENT_TIMESTAMP`               | Waktu perubahan state terjadi.                                |

#### 8. Tabel `system_error_logs` (Error & Debugging Central)

Mencatat kegagalan operasional di sisi internal backend (LXD daemon down, MinIO timeout, Docker error) tanpa memaparkan informasi teknis sensitif ke sisi pengguna.

| Nama Kolom       | Tipe Data    | Atribut / Constraint                       | Keterangan                                                       |
| :--------------- | :----------- | :----------------------------------------- | :--------------------------------------------------------------- |
| `id`             | UUID         | PRIMARY KEY, Default: `uuid_generate_v4()` | ID log galat.                                                    |
| `service_module` | VARCHAR(100) | NOT NULL                                   | Komponen yang bermasalah (`lxd_worker`, `minio_api`, `billing`). |
| `error_level`    | VARCHAR(20)  | NOT NULL                                   | Tingkat kegawatan (`WARNING`, `ERROR`, `CRITICAL`).              |
| `message`        | TEXT         | NOT NULL                                   | Pesan kesalahan singkat terformat.                               |
| `stack_trace`    | JSONB        | NULLABLE                                   | Detail baris kode/objek error lengkap untuk debugging backend.   |
| `resolved`       | BOOLEAN      | DEFAULT: `FALSE`                           | Penanda penanganan isu oleh tim internal DevOps.                 |
| `created_at`     | TIMESTAMP    | DEFAULT: `CURRENT_TIMESTAMP`               | Waktu sistem mengalami kegagalan.                                |

#### 9. Tabel `billing_transactions` (Buku Besar Keuangan)

Buku besar (_ledger_) transaksi yang merekam riwayat modifikasi mutasi saldo pengguna demi transparansi audit keuangan.

| Nama Kolom         | Tipe Data      | Atribut / Constraint                       | Keterangan                                                     |
| :----------------- | :------------- | :----------------------------------------- | :------------------------------------------------------------- |
| `id`               | UUID           | PRIMARY KEY, Default: `uuid_generate_v4()` | ID transaksi finansial.                                        |
| `user_id`          | UUID           | FOREIGN KEY (`users.id`) ON DELETE CASCADE | Pengguna yang mengalami mutasi saldo.                          |
| `amount`           | DECIMAL(15, 2) | NOT NULL                                   | Nominal keuangan (`+` nilai top-up, `-` nilai potongan biaya). |
| `transaction_type` | VARCHAR(50)    | NOT NULL                                   | Kategori mutasi (`TOPUP`, `HOURLY_USAGE`, `REFUND`).           |
| `description`      | VARCHAR(255)   | NULLABLE                                   | Catatan narasi pendukung (contoh: "Potongan VM-Linux Jan").    |
| `created_at`       | TIMESTAMP      | DEFAULT: `CURRENT_TIMESTAMP`               | Waktu pemotongan atau penambahan saldo.                        |

---

## 3. Skrip SQL Data Definition Language (DDL)

Berikut adalah skrip DDL SQL lengkap untuk diimplementasikan langsung pada engine database **PostgreSQL**:

```sql
-- Mengaktifkan ekstensi untuk manajemen UUID v4
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- ==========================================
-- KELOMPOK 1: CORE SERVICE TABLES
-- ==========================================

-- Tabel Pengguna / Akun Pelanggan
CREATE TABLE users (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    balance DECIMAL(15, 2) DEFAULT 0.00,
    status VARCHAR(20) DEFAULT 'ACTIVE', -- ACTIVE, SUSPENDED
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Paket Spesifikasi & Kuota Layanan
CREATE TABLE plans (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    service_type VARCHAR(50) NOT NULL, -- COMPUTE, STORAGE, DATABASE
    name VARCHAR(100) NOT NULL,
    vcpu INT,
    ram INT, -- dalam MB
    storage INT, -- dalam GB
    price DECIMAL(10, 2) NOT NULL, -- Harga
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Layanan Virtual Mesin (Compute VPS/CaaS)
CREATE TABLE compute_instances (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id UUID REFERENCES users(id) ON DELETE CASCADE,
    plan_id UUID REFERENCES plans(id) ON DELETE SET NULL,
    name VARCHAR(100) NOT NULL,
    os VARCHAR(50) NOT NULL,
    ip_address VARCHAR(45),
    status VARCHAR(20) DEFAULT 'PROVISIONING',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Layanan Penyimpanan Objek (Object Storage MinIO)
CREATE TABLE storage_buckets (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id UUID REFERENCES users(id) ON DELETE CASCADE,
    bucket_name VARCHAR(63) UNIQUE NOT NULL,
    access_key VARCHAR(100) NOT NULL,
    secret_key VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Layanan Basis Data Terkelola (DBaaS)
CREATE TABLE managed_databases (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id UUID REFERENCES users(id) ON DELETE CASCADE,
    plan_id UUID REFERENCES plans(id) ON DELETE SET NULL,
    engine VARCHAR(50) NOT NULL,
    db_name VARCHAR(100) NOT NULL,
    db_user VARCHAR(100) NOT NULL,
    db_password VARCHAR(255) NOT NULL,
    host VARCHAR(255),
    port INT,
    status VARCHAR(20) DEFAULT 'CREATING',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==========================================
-- KELOMPOK 2: LOGGING & BILLING TABLES
-- ==========================================

-- Tabel Log Jejak Audit Aktivitas User
CREATE TABLE activity_logs (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id UUID REFERENCES users(id) ON DELETE SET NULL,
    action VARCHAR(100) NOT NULL,
    resource_type VARCHAR(50) NOT NULL,
    resource_id UUID NOT NULL,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Log Perubahan Status Infrastruktur (VM/DB)
CREATE TABLE resource_state_logs (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    resource_type VARCHAR(50) NOT NULL,
    resource_id UUID NOT NULL,
    old_state VARCHAR(50),
    new_state VARCHAR(50) NOT NULL,
    triggered_by VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Log Internal Kerusakan Sistem (System Error)
CREATE TABLE system_error_logs (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    service_module VARCHAR(100) NOT NULL,
    error_level VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    stack_trace JSONB,
    resolved BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Ringkasan Log Transaksi Keuangan Saldo User
CREATE TABLE billing_transactions (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id UUID REFERENCES users(id) ON DELETE CASCADE,
    amount DECIMAL(15, 2) NOT NULL,
    transaction_type VARCHAR(50) NOT NULL,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==========================================
-- OPTIMASI PENINGKATAN PERFORMA (INDEXING)
-- ==========================================

CREATE INDEX idx_activity_logs_user ON activity_logs(user_id);
CREATE INDEX idx_billing_transactions_user ON billing_transactions(user_id);
CREATE INDEX idx_compute_status ON compute_instances(status);
CREATE INDEX idx_db_status ON managed_databases(status);
CREATE INDEX idx_resource_state_lookup ON resource_state_logs(resource_id, resource_type);
CREATE INDEX idx_system_error_unresolved ON system_error_logs(resolved) WHERE resolved = FALSE;
```
