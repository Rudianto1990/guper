
# Sistem Informasi Gudang Peralatan

# Sistem Informasi Gudang Peralatan laravel 11 dengan template dashboard

## Live CCTV RTSP dengan MediaMTX

Browser tidak memutar RTSP secara langsung. Untuk kamera RTSP, Live CCTV memakai MediaMTX untuk mengambil RTSP dari kamera dan menyediakan HLS ke pemutar browser.

1. Salin konfigurasi `MEDIAMTX_*` dari `.env.example` ke `.env`, lalu isi `MEDIAMTX_API_PASSWORD` dengan password acak yang kuat.
2. Pastikan `MEDIAMTX_API_URL` dan `MEDIAMTX_HLS_URL` menunjuk ke alamat yang dapat diakses masing-masing oleh server Laravel dan browser.
3. Jalankan gateway dari folder project:

   ```powershell
   docker compose -f docker-compose.mediamtx.yml up -d
   ```

4. Simpan alamat RTSP kamera di form CCTV. Saat tombol **Live** diklik, aplikasi mendaftarkan source RTSP ke MediaMTX dan memutar output HLS.

API MediaMTX pada konfigurasi Compose hanya dipublikasikan ke `127.0.0.1`; jangan membuka port API `9997` ke jaringan publik. Untuk akses aplikasi dari komputer lain atau HTTPS, atur `MEDIAMTX_HLS_URL` ke host gateway yang dapat dicapai browser, dan sediakan konfigurasi HTTPS/CORS serta firewall yang sesuai. Host/container MediaMTX harus dapat menjangkau kamera RTSP.

# Berikut cara upload project dari folder baru ke GitHub menggunakan Windows + Git Bash, dari awal sampai berhasil.
1. Buat repository baru di GitHub
1. Buka https://github.com/new
2. Isi Repository name, misalnya project-laravel-baru.
3. Pilih Public atau Private.
4. Untuk memudahkan, jangan centang README, .gitignore, atau License terlebih dahulu.
5. Klik Create repository.
2. Buka folder project baru
# Misalnya project Anda berada di:
D:\xampp8.2.12\htdocs\projectbaru
Buka Git Bash, lalu jalankan:
cd /d/xampp8.2.12/htdocs/projectbaru


# Sesuaikan lokasi tersebut dengan folder project Anda.
3. Inisialisasi Git
git init
git branch -M main


4. Pastikan file sensitif tidak ikut ter-upload
# Untuk Laravel, pastikan file .gitignore tersedia dan mengecualikan setidaknya:
.env
/vendor/
/node_modules/
/public/build/


# Catatan: public/build/ boleh tidak dikecualikan jika memang hasil build harus ikut disimpan. Pastikan .env tidak berisi kredensial yang akan ter-upload.
5. Tambahkan dan commit project
git add .
git status
git commit -m "Initial commit"


# Periksa hasil git status sebelum commit untuk memastikan tidak ada file rahasia.
6. Hubungkan dengan repository GitHub
Salin URL repository yang baru dibuat. Contohnya:
 https://github.com/Rudianto1990/guper.git
# Kemudian jalankan:
git remote add origin https://github.com/Rudianto1990/guper.git
git push -u origin main


atau

git add .
git commit -m "Upload project baru"
git branch -M main
git push -u origin main


# Ganti USERNAME dan nama repository sesuai milik Anda.
7. Selesai

