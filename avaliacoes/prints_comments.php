<?php
require 'db.php';

try {
    // Consulta SQL para obter comentários e prints
    $stmt = $pdo->prepare("
        SELECT c.content AS comment_content, 
               r.screenshot_path, 
               r.created_at AS review_date, 
               u.name AS reviewer_name 
        FROM reviews r
        INNER JOIN comments c ON r.comment_id = c.id
        INNER JOIN users u ON r.user_id = u.id
        ORDER BY r.created_at DESC
    ");
    $stmt->execute();
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro ao buscar dados: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comentários e Prints</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Comentários e Prints</h1>
        <div class="reviews-container">
            <?php if (count($reviews) > 0): ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="review-item">
                        <p><strong>Comentário:</strong> <?= htmlspecialchars($review['comment_content']) ?></p>
                        <p><strong>Avaliador:</strong> <?= htmlspecialchars($review['reviewer_name']) ?></p>
                        <p><strong>Data:</strong> 
                            <?= date("d/m/Y H:i:s", strtotime($review['review_date'])) ?>
                        </p>
                        <?php if (!empty($review['screenshot_path'])): ?>
                            <img src="uploads/<?= htmlspecialchars($review['screenshot_path']) ?>" alt="Print" class="screenshot-img">
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Nenhum comentário ou print disponível.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>