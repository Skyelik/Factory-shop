<?php
require_once 'admin_check.php';
require_once '../config/db.php';
require_once '../src/Database.php';

$db = new Database($dbConfig);
$connection = $db->getConnection();

$productId = $_GET['id'] ?? null;

if ($productId) {
    $query = "DELETE FROM products WHERE id = :id";
    $stmt = $connection->prepare($query);
    $stmt->bindValue(':id', $productId, PDO::PARAM_INT);
    $stmt->execute();
}

header('Location: manage_products.php');
exit;
?>
