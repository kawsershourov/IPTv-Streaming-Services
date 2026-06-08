<?php
require __DIR__ . '/app/bootstrap.php';

logout_user();
session_start(); // fresh session to carry the flash message
flash('success', 'You have been logged out.');
redirect('index.php');
