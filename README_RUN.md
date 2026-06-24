# PABETAS Academic V8.1 Ladder Control Fix

Versi ini memperbaiki animasi Tangga Satuan agar benar-benar mengikuti input pada panel kontrol.

## Perbaikan

- Karakter selalu mulai dari satuan pada field **Dari**.
- Karakter bergerak menuju satuan pada field **Ke**.
- Jalur aktif disorot dari satuan asal ke satuan tujuan.
- Kartu satuan asal diberi label **DARI**.
- Kartu satuan tujuan diberi label **KE**.
- Angka perubahan tampil bertahap: contoh 3000 → 300 → 30 → 3.
- Demo 3 km ke m dan 3000 mm ke m sudah mengikuti arah kontrol.

## Cara menjalankan

```bash
php -S localhost:8000 -t public
```

Buka:

```text
http://localhost:8000
```

## Database

Tidak perlu update database karena patch ini hanya memperbaiki JavaScript animasi.

## Update V9 - Musik Menyeluruh dan Daftar Guru/Murid

Versi ini memperbaiki layout login dan pendaftaran, menambahkan daftar akun guru, serta memperluas suara web.

Fitur suara:
- musik latar berjalan terus per menu
- tombol suara untuk stop/play
- efek tombol
- efek pilih jawaban
- efek input
- efek benar/salah/ranking/countdown/power-up

Catatan: browser bisa memblokir autoplay sampai ada klik pertama. Setelah pengguna klik halaman atau tombol suara, musik berjalan normal.

Tidak perlu menjalankan SQL tambahan untuk update V9.
