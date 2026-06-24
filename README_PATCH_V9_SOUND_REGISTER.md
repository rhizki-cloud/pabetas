# PABETAS Academic V9 - Sound & Register UI Fix

Patch ini menambahkan:

1. Musik latar yang berjalan terus sesuai menu/kegiatan.
2. Tombol suara untuk menyalakan dan mematikan musik secara global.
3. Efek suara untuk tombol, link, pilihan jawaban, radio, checkbox, input, benar, salah, ranking, countdown, power-up, dan selesai.
4. Musik berbeda untuk konteks: materi, tangga satuan, kuis individu, mode tim, live game, refleksi, guru, dan beranda.
5. Layout login dibuat dua panel agar tidak berantakan.
6. Layout pendaftaran dibuat responsif agar tidak terpotong di layar kecil.
7. Pendaftaran akun guru dan murid dalam satu halaman.

## Catatan Browser

Sebagian browser memblokir autoplay sebelum pengguna melakukan klik pertama. Sistem tetap mencoba memulai musik otomatis. Jika belum bunyi, klik tombol **Musik Aktif** atau klik area halaman satu kali.

## Update Database

Tidak perlu update database.

## File utama yang berubah

- public/login.php
- public/register.php
- public/assets/js/sounds.js
- public/assets/css/style.css
- app/layout.php
