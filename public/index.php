<?php
require_once __DIR__ . '/../app/init.php';

$user = current_user();
if ($user) {
    redirect(($user['role'] ?? '') === 'guru' ? 'teacher/dashboard.php' : 'student/dashboard.php');
}

// Render halaman login langsung di root agar Vercel preview dan tombol Visit tidak berhenti di redirect 302.
require __DIR__ . '/login.php';
