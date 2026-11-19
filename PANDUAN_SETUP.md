# Panduan Setup & Menjalankan Proyek "Bloom&Books"

Proyek Toko Buku Online Modern **Bloom&Books** dibangun menggunakan **PHP Native / PDO**, **HTML5**, **CSS3 (Tailwind CSS)**, dan **JavaScript** dengan tema visual **Clean Minimalist Glassmorphic** (#3A0CA3 & #F72585).

---

## 📁 Struktur Direktori Proyek

```
ecommerce/
├── config/
│   └── database.php          # Koneksi Database PDO & Helper Global
├── includes/
│   ├── header.php            # Navbar Glassmorphic, Tailwind Config, Autentikasi State
│   └── footer.php            # Footer Minimalis & Hak Cipta
├── assets/
│   ├── css/
│   │   └── style.css         # Styling Glassmorphism, Palet Warna & Efek Cahaya
│   └── js/
│       └── main.js           # Interaktivitas Menu Mobile & Notifikasi
├── admin/
│   ├── auth_check.php        # Middleware Proteksi Session Role 'admin'
│   ├── layout/
│   │   ├── header.php        # Sidebar Back-Office & Top Navbar Admin
│   │   └── footer.php        # Script Penutup & Mobile Sidebar Toggle
│   ├── dashboard.php         # Dashboard Statistik, Grafik Penjualan & Stok Menipis
│   ├── books.php             # CRUD Lengkap Data Buku (Tambah, Edit, Hapus, Cari)
│   ├── categories.php        # CRUD Data Kategori Buku
│   ├── orders.php            # Manajemen Transaksi & Pembaruan Status Pesanan
│   ├── users.php             # Manajemen Pengguna (Admin & Customer, Ubah Role)
│   └── settings.php          # Pengaturan Toko & Profil Akun Admin
├── user/
│   └── dashboard.php         # Dashboard Pengguna/Member Pembaca
├── index.php                 # Halaman Landing Page & Katalog Buku Publik
├── login.php                 # Halaman Login Mandiri (URL Dedicated)
├── register.php              # Halaman Registrasi Pengguna Baru (URL Dedicated)
├── logout.php                # Pemroses Logout & Penghapusan Sesi
├── schema.sql                # Skrip SQL Database Lengkap & Dummy Data
└── PANDUAN_SETUP.md          # Panduan ini
```

---

## 🗄️ Langkah Import Database (XAMPP / phpMyAdmin)

1. Buka aplikasi **XAMPP Control Panel**.
2. Klik tombol **Start** pada modul **Apache** dan **MySQL**.
3. Buka browser dan akses **[http://localhost/phpmyadmin](http://localhost/phpmyadmin)**.
4. Klik tab **Import** pada menu atas phpMyAdmin.
5. Klik **Choose File** / **Pilih File**, lalu pilih file `schema.sql` yang ada di dalam folder proyek ini (`c:\Users\Nasya\ecommerce\schema.sql`).
6. Klik tombol **Import** (atau **Go**) di bagian bawah.
7. Database `db_ecommerce` beserta seluruh tabel (`users`, `categories`, `books`, `cart`, `orders`, `order_items`) dan 12 dummy buku terkurasi akan otomatis dibuat.

---

## 🚀 Cara Menjalankan Aplikasi

Aplikasi Bloom&Books sekarang mendukung **3 cara pengujian sekaligus**:

### Opsi A: Menjalankan Lewat `php artisan serve` (Sangat Praktis)
Jika Anda terbiasa menggunakan perintah bawaan workspace:
```bash
php artisan serve
```
Lalu buka browser di:
```
http://127.0.0.1:8000
```
*(Seluruh request akan langsung dijembatani ke aplikasi Bloom&Books).*

### Opsi B: Menjalankan Lewat PHP Built-in Server
Jalankan perintah berikut di root folder:
```bash
php -S localhost:8000
```
Lalu buka browser di:
```
http://localhost:8000
```

### Opsi C: Menjalankan Lewat XAMPP (Apache)
- Tempatkan folder proyek di dalam direktori `C:\xampp\htdocs\ecommerce`.
- Buka browser dan akses:
  ```
  http://localhost/ecommerce/index.php
  ```

---

## 🔑 Kredensial Akun Percobaan (Demo Accounts)

Untuk mempermudah pengujian autentikasi dan pengalihan hak akses (Role-based redirection), telah disiapkan 2 akun bawaan:

| Role | Alamat Email | Kata Sandi | Halaman Tujuan Setelah Login |
| :--- | :--- | :--- | :--- |
| **Admin** | `admin@bloombooks.com` | `admin123` | `/admin/dashboard.php` |
| **User** | `user@bloombooks.com` | `user123` | `/user/dashboard.php` |

*(Pada halaman `login.php`, terdapat tombol klik cepat untuk langsung mengisi kredensial demo di atas secara otomatis).*
