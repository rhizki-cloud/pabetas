<?php
$message=''; $error='';
$configPath=__DIR__.'/../app/config.php';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $host=trim($_POST['db_host'] ?? '127.0.0.1'); $name=trim($_POST['db_name'] ?? 'pabetas'); $user=trim($_POST['db_user'] ?? 'root'); $pass=$_POST['db_pass'] ?? ''; $appUrl=trim($_POST['app_url'] ?? ''); $school=trim($_POST['school_name'] ?? 'SD Contoh Nusantara');
    try{
        $pdo=new PDO('mysql:host='.$host.';charset=utf8mb4',$user,$pass,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
        $pdo->exec('CREATE DATABASE IF NOT EXISTS `'.str_replace('`','',$name).'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $pdo->exec('USE `'.str_replace('`','',$name).'`');
        $sql=file_get_contents(__DIR__.'/../database/pabetas_advanced.sql');
        $pdo->exec($sql);
        $config="<?php\ndefine('APP_NAME','PABETAS');\ndefine('APP_ENV','production');\ndefine('APP_DEBUG',false);\ndefine('APP_URL','".addslashes($appUrl)."');\ndefine('DB_HOST','".addslashes($host)."');\ndefine('DB_NAME','".addslashes($name)."');\ndefine('DB_USER','".addslashes($user)."');\ndefine('DB_PASS','".addslashes($pass)."');\ndefine('DB_CHARSET','utf8mb4');\ndefine('SCHOOL_NAME','".addslashes($school)."');\ndefine('SCHOOL_LOGO','assets/img/logo-pabetas.svg');\ndefine('SESSION_TIMEOUT',2700);\n";
        file_put_contents($configPath,$config);
        $message='Instalasi berhasil. Hapus file public/install.php setelah selesai agar aman.';
    }catch(Throwable $e){ $error=$e->getMessage(); }
}
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Installer PABETAS</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="assets/css/style.css"></head><body><div class="container py-5"><div class="panel-card col-lg-7 mx-auto"><h1>🚀 Installer PABETAS</h1><p>Form ini membuat database, import tabel, dan menulis konfigurasi sistem.</p><?php if($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><a class="btn btn-primary" href="login.php">Ke Login</a><?php endif; ?><?php if($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?><form method="post"><div class="row"><div class="col-md-6"><label>DB Host</label><input class="form-control mb-2" name="db_host" value="127.0.0.1"></div><div class="col-md-6"><label>DB Name</label><input class="form-control mb-2" name="db_name" value="pabetas"></div><div class="col-md-6"><label>DB User</label><input class="form-control mb-2" name="db_user" value="root"></div><div class="col-md-6"><label>DB Password</label><input type="password" class="form-control mb-2" name="db_pass"></div></div><label>APP URL</label><input class="form-control mb-2" name="app_url" placeholder="Kosongkan jika document root langsung ke public"><label>Nama Sekolah</label><input class="form-control mb-3" name="school_name" value="SD Contoh Nusantara"><button class="btn btn-primary rounded-pill">Install Sekarang</button></form></div></div></body></html>
