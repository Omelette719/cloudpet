# CloudPet - Project Overview

## Project Introduction

CloudPet adalah sebuah platform berbasis web yang mensimulasikan fungsionalitas dari sebuah Cloud Service Provider (CSP). Platform ini memungkinkan pengguna untuk menyewa, mengelola, dan memonitor infrastruktur cloud dalam skala yang lebih kecil dan fungsional.

Sistem ini menggunakan Laravel sebagai kerangka kerja aplikasi backend utama. Antarmuka pengguna dibangun secara interaktif menggunakan Livewire dan Tailwind CSS. Untuk simulasi infrastruktur, CloudPet memanfaatkan Ministack sebagai lapisan orkestrasi _cloud_ dan MinIO untuk kebutuhan penyimpanan objek.

## Core Services

CloudPet mengelola tiga pilar layanan utama yang masing-masing digerakkan oleh _engine_ spesifik:

| Service           | Engine    | Description                                                               |
| :---------------- | :-------- | :------------------------------------------------------------------------ |
| Compute Instances | Ministack | Layanan peluncuran dan manajemen peladen virtual (Virtual Machines).      |
| Managed Databases | Ministack | Penyediaan instans database mandiri yang diatur sepenuhnya oleh sistem.   |
| Storage Buckets   | MinIO     | Wadah penyimpanan objek berskala besar yang kompatibel dengan standar S3. |

## Cloud Provisioning Model

Model penyediaan sumber daya pada CloudPet mengikuti alur otomasi terpusat. Pengguna mengirimkan permintaan melalui antarmuka web. Backend Laravel menerima dan memvalidasi permintaan tersebut. Backend kemudian berkomunikasi dengan Ministack atau MinIO melalui lapisan _Service/Adapter_. Sumber daya berhasil dibuat pada infrastruktur simulasi. Metadata dan kredensial sumber daya disimpan ke dalam database relasional. Sistem terus memantau status sumber daya secara berkala.

## Background Automation

Proyek ini dirancang dengan fitur otomatisasi dan pemantauan bawaan. Elemen-elemen berikut beroperasi di latar belakang:

- **Activity Logging**: Pencatatan riwayat tindakan pengguna pada sistem.
- **Resource State Tracking**: Pemantauan perubahan kondisi infrastruktur secara _real-time_.
- **System Error Logging**: Perekaman galat sistem untuk keperluan isolasi bug oleh Administrator.
- **Billing Transactions**: Pencatatan otomatis transaksi berdasarkan spesifikasi _Plan_ yang dipilih.

## Non-Functional Goals

Pengembangan sistem CloudPet berpedoman pada kualitas perangkat lunak berikut:

- **Scalability**: Kemampuan backend menangani banyak proses _provisioning_ secara asinkron menggunakan _Queues_.
- **Maintainability**: Penerapan arsitektur modular yang memisahkan logika antarmuka, bisnis, dan infrastruktur.
- **Reliability**: Jaminan ketersediaan layanan melalui mekanisme _retry_ dan toleransi kesalahan pada integrasi API.
- **Observability**: Transparansi operasional penuh melalui _logging_ terpusat.
- **Security**: Perlindungan akses dengan autentikasi, enkripsi kredensial, dan manajemen kebijakan otorisasi (IAM/Policy) yang ketat.
