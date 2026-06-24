# PABETAS Academic V8.2 Ladder Ghost Fix

Patch ini memperbaiki bug animasi Tangga Satuan yang terlihat bertumpuk/double di bagian belakang ketika karakter bergerak dari satuan kanan seperti `mm` menuju satuan kiri seperti `dam` atau `m`.

## Perubahan utama

1. File `public/assets/js/ladder.js`
   - Menambahkan penghentian animasi lama sebelum animasi baru berjalan.
   - Membersihkan timeout dan requestAnimationFrame lama.
   - Menambah area aman di sisi kanan canvas agar karakter tidak menabrak kartu `mm`.
   - Membatasi posisi badge `DARI` dan `KE` agar tidak keluar panel.
   - Membersihkan canvas penuh pada setiap frame.

2. File `public/student/ladder.php`
   - Menambahkan versi cache `?v=8.2` agar browser memakai file JavaScript terbaru.

## Cara update

Tidak perlu update database.

1. Ganti project lama dengan folder ini, atau minimal ganti file berikut:
   - `public/assets/js/ladder.js`
   - `public/student/ladder.php`
2. Jalankan ulang server.
3. Buka halaman Tangga Satuan.
4. Tekan Ctrl + F5 agar cache browser bersih.

## Tes cepat

- Angka: `3000`
- Dari: `mm`
- Ke: `dam`
- Klik: `Gerakkan Perlahan`

Karakter harus bergerak bersih dari `mm` ke `dam` tanpa gambar bertumpuk di belakang.
