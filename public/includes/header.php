<?php
session_start(); // Убедимся, что сессия стартует
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factory Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css">
</head>
<body>
    <header>
    <nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">МАПИД Shop</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if (!empty($_SESSION['user'])): ?>
                        <!-- Кнопка "Панель администратора" только для админов -->
                        <?php if ($_SESSION['user']['role'] === 'admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="admin_dashboard.php">
                                    <i class="bi bi-speedometer2"></i> Панель администратора
                                </a>
                            </li>
                            
                        <?php else: ?>
                            <!-- Ссылка на корзину для пользователей -->
                            <li class="nav-item">
                                <a class="nav-link" href="cart.php">
                                    <i class="bi bi-cart"></i> Корзина
                                </a>
                            </li>
                        <?php endif; ?>

                        <!-- Dropdown для пользователя -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Привет, <?php echo htmlspecialchars($_SESSION['user']['name']); ?>!
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="settings.php"><i class="bi bi-gear"></i> Настройки</a></li>
                                <!-- История заказов только для обычных пользователей -->
                                <?php if ($_SESSION['user']['role'] !== 'admin'): ?>
                                    <li><a class="dropdown-item" href="order_history.php"><i class="bi bi-cart-check"></i> История заказов</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right"></i> Выйти</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <!-- Если пользователь не авторизован -->
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Войти</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">Регистрация</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    </header>
    <main class="container py-5" style="margin-top: 60px;">
