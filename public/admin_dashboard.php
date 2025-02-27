<?php
session_start();
require_once 'admin_check.php'; // Проверка на роль администратора
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель администратора</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container py-5">
    <h1 class="text-center">Добро пожаловать в панель администратора</h1>
    <div class="row">
        <div class="col-md-6">
            <a href="manage_products.php" class="btn btn-primary w-100 mb-3">Управление товарами</a>
        </div>
        <div class="col-md-6">
            <a href="manage_orders.php" class="btn btn-primary w-100 mb-3">Управление заказами</a>
        </div>
        <div class="col-md-6">
            <a href="manage_comments.php" class="btn btn-primary w-100 mb-3">Управление комментариями</a>
        </div>
        <div class="col-md-6">
            <a href="manage_users.php" class="btn btn-primary w-100 mb-3">Управление пользователями</a>
        </div>
        <!-- Доделать потом категории и аналитику -->
        <!-- <div class="col-md-4">
            <a href="manage_categories.php" class="btn btn-primary w-100 mb-3">Управление категориями</a>
        </div>
        -->
        <div class="col-md-4">
            <a href="analytics.php" class="btn btn-primary w-100 mb-3">Аналитика</a>
        </div> 
    </div>
</div>
</body>
</html>
