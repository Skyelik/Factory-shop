<?php
session_start(); // Стартуем сессию
session_unset(); // Очищаем все данные сессии
session_destroy(); // Завершаем сессию

// Перенаправляем пользователя на главную страницу
header('Location: index.php');
exit;
