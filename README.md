
# Sistem Informasi Gudang Peralatan

# Sistem Informasi Gudang Peralatan laravel 11 dengan template dashboard

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


