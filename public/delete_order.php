<?php
session_start();
require_once '../config/db.php';
require_once '../src/Database.php';

// Проверка роли администратора
if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// Проверяем, передан ли ID заказа
$orderId = $_GET['id'] ?? null;
if (!$orderId) {
    header('Location: manage_orders.php');
    exit;
}

$db = new Database($dbConfig);
$connection = $db->getConnection();

try {
    // Начинаем транзакцию
    $connection->beginTransaction();

    // Удаляем записи из таблицы order_items
    $deleteItemsQuery = "DELETE FROM order_items WHERE order_id = :order_id";
    $stmtDeleteItems = $connection->prepare($deleteItemsQuery);
    $stmtDeleteItems->bindValue(':order_id', $orderId, PDO::PARAM_INT);
    $stmtDeleteItems->execute();

    // Удаляем сам заказ
    $deleteOrderQuery = "DELETE FROM orders WHERE id = :id";
    $stmtDeleteOrder = $connection->prepare($deleteOrderQuery);
    $stmtDeleteOrder->bindValue(':id', $orderId, PDO::PARAM_INT);
    $stmtDeleteOrder->execute();

    // Фиксируем транзакцию
    $connection->commit();

    // Перенаправляем обратно на страницу управления заказами
    header('Location: manage_orders.php?message=success');
    exit;
} catch (Exception $e) {
    // Откатываем транзакцию в случае ошибки
    $connection->rollBack();
    header('Location: manage_orders.php?message=error');
    exit;
}
