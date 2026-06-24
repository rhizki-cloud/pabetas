<?php
require_once __DIR__ . '/../app/init.php';
if (current_user()) {
    redirect($_SESSION['role'] === 'guru' ? 'teacher/dashboard.php' : 'student/dashboard.php');
}
redirect('login.php');
