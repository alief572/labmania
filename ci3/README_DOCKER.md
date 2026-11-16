# Panduan Menjalankan Docker (Apache, PHP 8.5 & MySQL 8)

File konfigurasi Docker telah disiapkan untuk project CodeIgniter ini dengan spesifikasi:
- **Web Server:** Apache
- **PHP:** 8.5 (dengan ekstensi: `gd`, `mysqli`, `pdo`, `pdo_mysql`, `zip`, `intl`, `bcmath`, `exif`, `opcache`, dan Composer)
- **Database:** MySQL 8.0
- **Database GUI (Opsional):** phpMyAdmin

---

## 1. Port yang Digunakan
| Service | Container Name | Port di Host (Browser / Client) | Keterangan |
| :--- | :--- | :--- | :--- |
| **Web (Apache + PHP 8.5)** | `labmania_web` | `http://localhost:8080` | Aplikasi web CodeIgniter |
| **MySQL 8.0** | `labmania_db` | `localhost:3307` | Database MySQL (internal port 3306) |
| **phpMyAdmin** | `labmania_pma` | `http://localhost:8081` | Web GUI untuk import/kelola database |

---

## 2. Kredensial Database Default
- **Host (dari dalam container web):** `db`
- **Host (dari luar / Navicat / DBeaver / HeidiSQL):** `127.0.0.1` port `3307`
- **Database Name:** `db_consultant_new`
- **Root Password:** `root`
- **User:** `alief`
- **Password:** `alief@ssc`

---

## 3. Cara Menjalankan

### A. Build dan Jalankan Container
Buka terminal / PowerShell di root project, lalu jalankan:
```bash
docker compose up -d --build
```

### B. Melihat Status Container
```bash
docker compose ps
```

### C. Melihat Log Container
```bash
docker compose logs -f web
```

### D. Menghentikan Container
```bash
docker compose down
```

---

## 4. Import Database

Anda dapat mengimpor database dengan salah satu cara berikut:
1. **Lewat phpMyAdmin:**
   Buka `http://localhost:8081`, login dengan User: `root` dan Password: `root` (atau `alief` / `alief@ssc`), lalu import file SQL ke database `db_consultant_new`.
2. **Lewat folder `docker/mysql/init/`:**
   Taruh file `.sql` Anda ke dalam direktori `docker/mysql/init/` sebelum container MySQL dibuat pertama kali. MySQL akan otomatis mengeksekusinya saat inisialisasi volume awal.
3. **Lewat Command Line:**
   ```bash
   docker exec -i labmania_db mysql -u root -proot db_consultant_new < path/to/database.sql
   ```

---

## 5. Menjalankan Perintah Composer di Dalam Container
Jika butuh install package baru:
```bash
docker compose exec web composer install
```
