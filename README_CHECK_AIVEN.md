# Check koneksi Aiven di Vercel

Upload/deploy project ini, lalu buka:

```text
https://domain-kamu.vercel.app/check-aiven.php
```

Urutan diagnosis:

1. `DNS_RESULT: FAILED` berarti DB_HOST salah, masih private/VPC host, atau environment Vercel belum redeploy.
2. `TCP_RESULT: FAILED` berarti port salah atau public access Aiven belum aktif.
3. `PDO_RESULT: FAILED` berarti credential, DB_NAME, SSL CA, atau import SQL bermasalah.
4. `STATUS: KONEKSI DATABASE BERHASIL` berarti masalah utama selesai.

Hapus `public/check-aiven.php` setelah selesai agar informasi konfigurasi database tidak terlihat publik.
