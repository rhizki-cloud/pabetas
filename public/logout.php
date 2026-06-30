<?php
require_once __DIR__ . '/../app/init.php';
logout_user();
redirect('login.php');
