# Panduan Deploy ke Hostinger

## Persiapan Sebelum Upload

1. **Backup Database Lokal**
   - Pastikan database SQLite (`rental_baju.sqlite`) terbaru sudah ada di folder `database/`

2. **Persiapan File untuk Upload**
   - Pastikan semua file up-to-date dan aplikasi berjalan dengan baik di server lokal

## Langkah-langkah Upload ke Hostinger

1. **Login ke Panel Hostinger**
   - Buka website [Hostinger](https://www.hostinger.co.id/) dan login ke akun Anda
   - Dari dashboard utama, pilih hosting Anda

2. **Akses File Manager**
   - Pada panel Hostinger, pilih "File Manager" atau "hPanel > File Manager"
   - Anda juga bisa menggunakan FTP (FileZilla) dengan kredensial dari Hostinger

3. **Upload File**
   - Jika ini adalah pertama kali upload, buatlah folder baru (misal: `rental-baju`) di folder `public_html`
   - Jika sudah ada folder sebelumnya, Anda bisa menggunakan folder yang sudah ada
   - Upload semua file dan folder dari aplikasi Anda ke folder tersebut

4. **Pengaturan Database**
   - Aplikasi ini menggunakan SQLite yang tersimpan dalam file, pastikan folder `database` memiliki izin tulis (chmod 755)
   - Jika Anda ingin menggunakan MySQL, Anda perlu mengubah konfigurasi di `includes/db.php`

5. **Pengaturan Domain**
   - Di panel Hostinger, pastikan domain/subdomain Anda mengarah ke folder yang benar

## Penyesuaian Setelah Upload

1. **Konfigurasi URL**
   - Config di `includes/config.php` sudah disesuaikan untuk mendeteksi lingkungan produksi
   - Jika aplikasi diakses dengan URL prefix tertentu (misalnya: yourdomain.com/rental-baju), Anda perlu menyesuaikan `BASE_URL` di file `includes/config.php`

2. **Izin File dan Folder**
   - Pastikan folder `assets/images` memiliki izin tulis (chmod 755)
   - Pastikan file database `database/rental_baju.sqlite` memiliki izin tulis (chmod 644)

3. **Uji Aplikasi**
   - Akses URL aplikasi Anda dan pastikan semua fitur berfungsi dengan baik
   - Periksa halaman frontend dan admin

## Pemecahan Masalah

1. **Tampilan Kosong atau Error 500**
   - Periksa error log di Hostinger panel (hPanel > Advanced > Error Log)
   - Pastikan semua konstanta di `config.php` sudah diatur dengan benar
   - Periksa path file dan izin akses

2. **Masalah Database**
   - Pastikan folder `database` dan file `rental_baju.sqlite` memiliki izin yang tepat
   - Cek apakah SQLite didukung pada paket hosting Anda

3. **Masalah Upload Gambar**
   - Pastikan folder `assets/images` memiliki izin tulis
   - Periksa batas ukuran upload di pengaturan PHP hosting Anda

## Backup Rutin

- Lakukan backup rutin pada database dan file-file penting 
- Hostinger biasanya menyediakan fitur backup otomatis, aktifkan jika tersedia

## Keamanan

- Pertimbangkan untuk mengubah nama folder admin atau menambahkan autentikasi tambahan
- Aktifkan HTTPS di panel Hostinger untuk keamanan data
