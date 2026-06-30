# Patch koneksi Aiven untuk PABETAS di Vercel

## 1. Environment variables di Vercel

Isi di Vercel Project Settings > Environment Variables:

```text
APP_ENV=production
APP_DEBUG=false
APP_URL=https://pabetas.vercel.app

DB_HOST=<host Aiven, contoh mysql-xxx.aivencloud.com>
DB_PORT=<port Aiven, contoh 12345>
DB_NAME=defaultdb
DB_USER=avnadmin
DB_PASS=<password Aiven>
DB_CHARSET=utf8mb4
DB_SSL_MODE=verify-ca
DB_SSL_CA_PATH=app/ca.pem
```

Boleh juga memakai `DATABASE_URL` / `MYSQL_URI` dari Aiven, tetapi `DB_SSL_MODE` dan `DB_SSL_CA_PATH` tetap disarankan.

## 2. CA certificate

Download CA certificate dari Aiven Console > service MySQL > Overview > Connection information > CA Certificate.

Simpan sebagai:

```text
app/ca.pem
```

File `ca.pem` bukan password database, tetapi tetap jangan ubah isinya.

## 3. Import database

Import file ini ke database Aiven:

```text
database/pabetas_advanced.sql
```

Kalau memakai database Aiven bawaan, gunakan `defaultdb` sebagai `DB_NAME`.

## 4. Redeploy

Setelah env dan file CA benar:

1. Commit perubahan ke GitHub.
2. Redeploy project di Vercel.
3. Buka `https://pabetas.vercel.app/login.php`.

Untuk debugging sementara, ubah `APP_DEBUG=true`, redeploy, baca pesan error, lalu kembalikan lagi ke `false`.
