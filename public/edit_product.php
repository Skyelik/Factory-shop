<?php
require_once 'admin_check.php';
require_once '../config/db.php';
require_once '../src/Database.php';

$db = new Database($dbConfig);
$connection = $db->getConnection();

// Получение данных продукта
$productId = $_GET['id'] ?? null;

if (!$productId) {
    header('Location: manage_products.php');
    exit;
}

$query = "SELECT * FROM products WHERE id = :id";
$stmt = $connection->prepare($query);
$stmt->bindValue(':id', $productId, PDO::PARAM_INT);
$stmt->execute();
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: manage_products.php');
    exit;
}

$error = '';
$success = '';

// Обновление данных продукта
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $price = $_POST['price'] ?? 0;
    $stock = $_POST['stock'] ?? 0;
    $category = $_POST['category'] ?? '';
    $country = $_POST['country'] ?? '';
    $dimensions = $_POST['dimensions'] ?? '';
    $weight = $_POST['weight'] ?? '';
    $fullDescription = $_POST['full_description'] ?? '';
    $imagePath = $product['image_path']; // Текущее изображение по умолчанию

    // Обработка загрузки изображения
    if (!empty($_FILES['image']['name'])) {
        $imageFile = $_FILES['image'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $uploadDir = __DIR__ . '/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        if (in_array($imageFile['type'], $allowedTypes) && $imageFile['size'] <= 2 * 1024 * 1024) {
            $fileName = uniqid() . '_' . basename($imageFile['name']);
            $filePath = $uploadDir . $fileName;

            if (move_uploaded_file($imageFile['tmp_name'], $filePath)) {
                $imagePath = 'uploads/' . $fileName;
            } else {
                $error = 'Ошибка загрузки изображения.';
            }
        } else {
            $error = 'Допустимые форматы: JPEG, PNG, GIF. Максимальный размер — 2 МБ.';
        }
    }

    if (empty($error)) {
        $query = "UPDATE products 
                  SET name = :name, description = :description, price = :price, stock = :stock, 
                      category = :category, country_of_origin = :country, dimensions = :dimensions, 
                      weight = :weight, full_description = :full_description, image_path = :image_path 
                  WHERE id = :id";
        $stmt = $connection->prepare($query);
        $stmt->bindValue(':name', $name, PDO::PARAM_STR);
        $stmt->bindValue(':description', $description, PDO::PARAM_STR);
        $stmt->bindValue(':price', $price, PDO::PARAM_STR);
        $stmt->bindValue(':stock', $stock, PDO::PARAM_INT);
        $stmt->bindValue(':category', $category, PDO::PARAM_STR);
        $stmt->bindValue(':country', $country, PDO::PARAM_STR);
        $stmt->bindValue(':dimensions', $dimensions, PDO::PARAM_STR);
        $stmt->bindValue(':weight', $weight, PDO::PARAM_STR);
        $stmt->bindValue(':full_description', $fullDescription, PDO::PARAM_STR);
        $stmt->bindValue(':image_path', $imagePath, PDO::PARAM_STR);
        $stmt->bindValue(':id', $productId, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $success = 'Продукт успешно обновлен.';
            $product['image_path'] = $imagePath;
        } else {
            $error = 'Ошибка при обновлении продукта.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактировать продукт</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container py-5">
        <h1 class="text-center">Редактировать продукт</h1>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php elseif (!empty($success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="name" class="form-label">Название</label>
                <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($product['name']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Краткое описание</label>
                <textarea id="description" name="description" class="form-control" required><?php echo htmlspecialchars($product['description']); ?></textarea>
            </div>
            <div class="mb-3">
                <label for="price" class="form-label">Цена</label>
                <input type="number" id="price" name="price" class="form-control" value="<?php echo htmlspecialchars($product['price']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="stock" class="form-label">Количество на складе</label>
                <input type="number" id="stock" name="stock" class="form-control" value="<?php echo htmlspecialchars($product['stock']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="category" class="form-label">Категория</label>
                <input type="text" id="category" name="category" class="form-control" value="<?php echo htmlspecialchars($product['category']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="country" class="form-label">Страна производства</label>
                <input type="text" id="country" name="country" class="form-control" value="<?php echo htmlspecialchars($product['country_of_origin']); ?>">
            </div>
            <div class="mb-3">
                <label for="dimensions" class="form-label">Размеры</label>
                <input type="text" id="dimensions" name="dimensions" class="form-control" value="<?php echo htmlspecialchars($product['dimensions']); ?>">
            </div>
            <div class="mb-3">
                <label for="weight" class="form-label">Вес</label>
                <input type="text" id="weight" name="weight" class="form-control" value="<?php echo htmlspecialchars($product['weight']); ?>">
            </div>
            <div class="mb-3">
                <label for="full_description" class="form-label">Полное описание</label>
                <textarea id="full_description" name="full_description" class="form-control"><?php echo htmlspecialchars($product['full_description']); ?></textarea>
            </div>
            <div class="mb-3">
                <label for="image" class="form-label">Изображение</label>
                <input type="file" id="image" name="image" class="form-control">
                <small class="form-text text-muted">Текущее изображение:</small>
                <?php if (!empty($product['image_path'])): ?>
                    <img src="<?php echo htmlspecialchars($product['image_path']); ?>" alt="Изображение продукта" class="img-thumbnail mt-2" style="width: 150px; height: 150px;">
                <?php endif; ?>
            </div>
            <button type="submit" class="btn btn-primary">Сохранить изменения</button>
        </form>
    </div>
</body>
</html>
