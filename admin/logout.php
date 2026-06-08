<?php
require __DIR__ . '/../app/bootstrap.php';
logout_user();
session_start();
flash('success', 'Logged out of admin.');
redirect('admin/login.php');
