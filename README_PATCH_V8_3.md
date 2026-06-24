# Patch V8.3 Decimal Ladder Fix

Perbaikan:

- Hasil konversi naik dari mm ke hm/km tidak lagi menjadi 0.
- Angka desimal kecil ditampilkan lengkap, contoh: `3 mm = 0,000003 km`.
- Proses bertahap menampilkan `3 → 0,3 → 0,03 → 0,003 → 0,0003 → 0,00003 → 0,000003`.
- Input angka mendukung koma desimal Indonesia.
- Cache JavaScript dinaikkan ke `?v=8.3`.

File utama yang berubah:

- `public/assets/js/ladder.js`
- `public/student/ladder.php`
