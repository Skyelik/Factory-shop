<?php
session_start();
require_once '../config/db.php';
require_once '../src/Database.php';

if (!isset($_SESSION['user']['id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user']['id'];
$cartId = $_POST['cart_id'] ?? null;
$quantity = $_POST['quantity'] ?? null;

if (!$cartId || !$quantity || $quantity < 1) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$db = new Database($dbConfig);
$connection = $db->getConnection();

$updateQuery = "UPDATE carts SET quantity = :quantity WHERE id = :cart_id AND user_id = :user_id";
$updateStmt = $connection->prepare($updateQuery);
$updateStmt->bindValue(':quantity', $quantity, PDO::PARAM_INT);
$updateStmt->bindValue(':cart_id', $cartId, PDO::PARAM_INT);
$updateStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);

if ($updateStmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update']);
}
exit;
?>
