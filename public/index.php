<?php
require_once '../config/db.php';
require_once '../src/Database.php';

$db = new Database($dbConfig);
$connection = $db->getConnection();

// Получение продуктов из базы данных
$query = "SELECT * FROM products WHERE 1=1";

if (!empty($_GET['price_from'])) {
    $query .= " AND price >= :price_from";
}
if (!empty($_GET['price_to'])) {
    $query .= " AND price <= :price_to";
}
if (!empty($_GET['category'])) {
    $query .= " AND category = :category";
}
if (!empty($_GET['search'])) {
    $query .= " AND name LIKE :search";
}

$statement = $connection->prepare($query);

if (!empty($_GET['price_from'])) {
    $statement->bindValue(':price_from', $_GET['price_from'], PDO::PARAM_INT);
}
if (!empty($_GET['price_to'])) {
    $statement->bindValue(':price_to', $_GET['price_to'], PDO::PARAM_INT);
}
if (!empty($_GET['category'])) {
    $statement->bindValue(':category', $_GET['category'], PDO::PARAM_STR);
}
if (!empty($_GET['search'])) {
    $statement->bindValue(':search', '%' . $_GET['search'] . '%', PDO::PARAM_STR);
}

$statement->execute();
$products = $statement->fetchAll(PDO::FETCH_ASSOC);




?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>МАПИД Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <?php 
    include 'includes/header.php'
    ?>

    <?php if (isset($_SESSION['order_success'])): ?>
    <div class="alert alert-success">
        <?php 
        echo $_SESSION['order_success'];
        unset($_SESSION['order_success']);
        ?>
    </div>

    
    <?php endif; ?>

    <main class="container py-5">


    

    <h1 class="text-center mb-4">Наши продукты</h1>

    
    <!-- Форма фильтрации -->
    <form method="GET" class="mb-4">
        <div class="row">
            <div class="col-md-3">
                <label for="price_from" class="form-label">Цена от:</label>
                <input type="number" id="price_from" name="price_from" class="form-control" placeholder="Минимальная цена" value="<?php echo htmlspecialchars($_GET['price_from'] ?? ''); ?>">
            </div>
            <div class="col-md-3">
                <label for="price_to" class="form-label">Цена до:</label>
                <input type="number" id="price_to" name="price_to" class="form-control" placeholder="Максимальная цена" value="<?php echo htmlspecialchars($_GET['price_to'] ?? ''); ?>">
            </div>
            <div class="col-md-3">
                <label for="category" class="form-label">Категория:</label>
                <select id="category" name="category" class="form-select">
                    <option value="">Все категории</option>
                    <option value="Стены" <?php echo (isset($_GET['category']) && $_GET['category'] === 'Стены') ? 'selected' : ''; ?>>Стены</option>
                    <option value="Панели" <?php echo (isset($_GET['category']) && $_GET['category'] === 'Панели') ? 'selected' : ''; ?>>Панели</option>
                    <option value="Плиты" <?php echo (isset($_GET['category']) && $_GET['category'] === 'Плиты') ? 'selected' : ''; ?>>Плиты</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="search" class="form-label">Поиск:</label>
                <input type="text" id="search" name="search" class="form-control" placeholder="Введите название" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
            </div>
            
            <div class="col-md-3 d-flex align-items-end" style="margin-top: 20px;">
                <button type="submit" class="btn btn-primary w-100">Применить</button> 
            </div>
        </div>
    </form>


    

    
    <!-- Вывод товаров -->
    <div class="row">
        <?php foreach ($products as $product): ?>
            <div class="col-md-4 mb-4">
                <div class="card">
                    <img src="<?php echo htmlspecialchars($product['image_path']); ?>" class="card-img-top" alt="Product Image">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                        <p class="card-text"><?php echo htmlspecialchars($product['description']); ?></p>
                        <p class="text-primary fw-bold">Цена: <?php echo number_format($product['price'], 2, ',', ' '); ?> руб.</p>
                        <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-primary">Подробнее</a>
                        
                        <form method="POST" action="add_to_cart.php" class="d-inline">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <button type="submit" class="btn btn-primary">Добавить в корзину</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</main>
</body>
</html>

<?php 
    include 'includes/footer.php'
?>
