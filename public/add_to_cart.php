<?php
session_start();
require_once '../config/db.php';
require_once '../src/Database.php';

if (!isset($_SESSION['user']['id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user']['id'];
$product_id = $_POST['product_id'] ?? null;
if (!$product_id) {
    die('Некорректный запрос.');
}

$db = new Database($dbConfig);
$connection = $db->getConnection();

// Проверяем, существует ли запись в корзине
$query = "SELECT * FROM carts WHERE user_id = :user_id AND product_id = :product_id";
$stmt = $connection->prepare($query);
$stmt->execute([':user_id' => $user_id, ':product_id' => $product_id]);
$cartItem = $stmt->fetch(PDO::FETCH_ASSOC);

if ($cartItem) {
    // Если запись существует, увеличиваем количество
    $updateQuery = "UPDATE carts SET quantity = quantity + 1 WHERE id = :id";
    $updateStmt = $connection->prepare($updateQuery);
    $updateStmt->execute([':id' => $cartItem['id']]);
} else {
    // Если записи нет, добавляем новую
    $insertQuery = "INSERT INTO carts (user_id, product_id) VALUES (:user_id, :product_id)";
    $insertStmt = $connection->prepare($insertQuery);
    $insertStmt->execute([':user_id' => $user_id, ':product_id' => $product_id]);
}

// Перенаправляем обратно на главную страницу
header('Location: index.php');
exit;
?>
