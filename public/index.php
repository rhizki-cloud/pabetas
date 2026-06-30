<?php
require_once __DIR__ . '/../app/init.php';

$user = current_user();

if ($user) {
    redirect($user['role'] === 'guru' ? 'teacher/dashboard.php' : 'student/dashboard.php');
}

redirect('login.php');