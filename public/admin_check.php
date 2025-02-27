<?php
session_start();

// Проверяем, что пользователь авторизован и его роль — администратор
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}
?>
