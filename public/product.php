<?php
require_once '../config/db.php';
require_once '../src/Database.php';

session_start(); // Стартуем сессию для проверки авторизации

$db = new Database($dbConfig);
$connection = $db->getConnection();

// Получение ID товара из URL
$product_id = $_GET['id'] ?? null;

if ($product_id === null) {
    die('Товар не найден.');
}

// Получение данных о товаре
$query = "SELECT * FROM products WHERE id = :id";
$statement = $connection->prepare($query);
$statement->bindValue(':id', $product_id, PDO::PARAM_INT);
$statement->execute();
$product = $statement->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    die('Товар не найден.');
}

// Обработка отправки формы комментария
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user']['id'])) {
    $commentText = trim($_POST['comment']);
    $userId = $_SESSION['user']['id'];

    if (!empty($commentText)) {
        $insertCommentQuery = "
            INSERT INTO comments (text, created_at, product_id, user_id)
            VALUES (:text, NOW(), :product_id, :user_id)
        ";
        $insertStmt = $connection->prepare($insertCommentQuery);
        $insertStmt->bindValue(':text', $commentText, PDO::PARAM_STR);
        $insertStmt->bindValue(':product_id', $product_id, PDO::PARAM_INT);
        $insertStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);

        $insertStmt->execute();

        // Перенаправление, чтобы избежать повторной отправки формы
        header("Location: product.php?id=$product_id");
        exit;
    } else {
        $error = "Комментарий не может быть пустым.";
    }
}

// Получение комментариев к товару
$commentsQuery = "
    SELECT c.text, c.created_at, u.username AS name, u.avatar
    FROM comments c
    INNER JOIN users u ON c.user_id = u.id
    WHERE c.product_id = :product_id
    ORDER BY c.created_at DESC
";
$commentsStmt = $connection->prepare($commentsQuery);
$commentsStmt->bindValue(':product_id', $product_id, PDO::PARAM_INT);
$commentsStmt->execute();
$comments = $commentsStmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Factory Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="container py-5">
        <h1 class="text-center mb-4"><?php echo htmlspecialchars($product['name']); ?></h1>
        <div class="row">
            <!-- Блок с изображением -->
            <div class="col-md-6 text-center">
                <img src="<?php echo htmlspecialchars($product['image_path']); ?>" alt="Product Image" class="img-fluid rounded" style="max-height: 400px;">
            </div>
            <!-- Блок с таблицей -->
            <div class="col-md-6">
                <table class="table table-bordered">
                    <tr>
                        <th>Категория</th>
                        <td><?php echo htmlspecialchars($product['category']); ?></td>
                    </tr>
                    <tr>
                        <th>Сделано в</th>
                        <td><?php echo htmlspecialchars($product['country_of_origin']); ?></td>
                    </tr>
                    <tr>
                        <th>Размеры</th>
                        <td><?php echo htmlspecialchars($product['dimensions']); ?></td>
                    </tr>
                    <tr>
                        <th>Вес</th>
                        <td><?php echo htmlspecialchars($product['weight']); ?></td>
                    </tr>
                </table>
                <p><?php echo nl2br(htmlspecialchars($product['full_description'])); ?></p>
            </div>
        </div>

        <h3 class="mt-4">Комментарии</h3>
        <div class="list-group mb-4">
            <?php if (!empty($comments)): ?>
                <?php foreach ($comments as $comment): ?>
                    <div class="list-group-item">
                        <div class="d-flex align-items-start">
                            <img src="<?php echo htmlspecialchars($comment['avatar']); ?>" alt="Avatar" class="rounded-circle me-3" style="width: 50px; height: 50px;">
                            <div>
                                <h6 class="mb-1"><?php echo htmlspecialchars($comment['name']); ?></h6>
                                <small class="text-muted"><?php echo date('d.m.Y', strtotime($comment['created_at'])); ?></small>
                                <p class="mb-0 mt-2"><?php echo htmlspecialchars($comment['text']); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-muted">Комментариев пока нет.</p>
            <?php endif; ?>
        </div>

        <!-- Форма для добавления комментария -->
        <?php if (isset($_SESSION['user'])): ?>
            <form method="POST" class="mt-4">
                <div class="mb-3">
                    <label for="comment" class="form-label">Ваш комментарий:</label>
                    <textarea id="comment" name="comment" class="form-control" rows="3" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Отправить</button>
                <?php if (!empty($error)): ?>
                    <p class="text-danger mt-2"><?php echo $error; ?></p>
                <?php endif; ?>
            </form>
        <?php else: ?>
            <p class="text-muted">Вы должны <a href="login.php">войти</a>, чтобы оставить комментарий.</p>
        <?php endif; ?>
    </div>
</body>
</html>

<?php 
include 'includes/footer.php';
?>
