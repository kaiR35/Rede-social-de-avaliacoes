<?php
require 'db.php';

// Configurar os cabeçalhos para permitir requisições de outros domínios
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST, GET, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Função para obter dados do corpo da requisição
function getRequestBody() {
    return json_decode(file_get_contents('php://input'), true);
}

// Rota: Cadastro de usuários
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_GET['route'] === 'register') {
    $data = getRequestBody();
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, type) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        $data['name'],
        $data['email'],
        password_hash($data['password'], PASSWORD_BCRYPT),
        $data['type']
    ]);
    echo json_encode(['message' => 'Usuário cadastrado com sucesso!']);
}

// Rota: Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_GET['route'] === 'login') {
    $data = getRequestBody();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$data['email']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($data['password'], $user['password'])) {
        echo json_encode(['message' => 'Login realizado com sucesso!', 'user' => $user]);
    } else {
        http_response_code(401);
        echo json_encode(['message' => 'E-mail ou senha inválidos.']);
    }
}

// Rota: Postagem de comentários (Empresa)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_GET['route'] === 'post_comment') {
    $data = getRequestBody();
    $stmt = $pdo->prepare("INSERT INTO comments (company_id, content) VALUES (?, ?)");
    $stmt->execute([$data['company_id'], $data['content']]);
    echo json_encode(['message' => 'Comentário postado com sucesso!']);
}

// Rota: Upload de prints (Avaliador) com controle de envios diários
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_GET['route'] === 'upload') {
    if (!empty($_FILES['screenshot']) && isset($_POST['user_id'], $_POST['comment_id'])) {
        $user_id = $_POST['user_id'];
        $comment_id = $_POST['comment_id'];
        $filename = uniqid() . '_' . $_FILES['screenshot']['name'];
        move_uploaded_file($_FILES['screenshot']['tmp_name'], 'uploads/' . $filename);

        // Verificar envios diários
        $stmt = $pdo->prepare("
            SELECT COUNT(*) AS total_envios 
            FROM reviews 
            WHERE user_id = :user_id 
              AND DATE(created_at) = CURDATE()
        ");
        $stmt->execute(['user_id' => $user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['total_envios'] >= 2) {
            echo json_encode(['status' => 'error', 'message' => 'Você já atingiu o limite diário de envios.']);
            exit;
        }

        // Inserir envio no banco
        $stmt = $pdo->prepare("INSERT INTO reviews (user_id, comment_id, screenshot_path) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $comment_id, $filename]);

        echo json_encode(['status' => 'success', 'message' => 'Print enviado com sucesso!', 'file' => $filename]);
    } else {
        http_response_code(400);
        echo json_encode(['message' => 'Erro ao enviar o print.']);
    }
}

// Rota: Histórico do Avaliador
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $_GET['route'] === 'history') {
    $user_id = $_GET['user_id'];
    $stmt = $pdo->prepare("
        SELECT r.id, c.content AS comment_content, r.screenshot_path, r.created_at, u.name AS reviewer_name 
        FROM reviews r 
        INNER JOIN comments c ON r.comment_id = c.id 
        LEFT JOIN users u ON r.user_id = u.id
        WHERE r.user_id = ?
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($reviews);
}

// Rota: Painel da Empresa
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $_GET['route'] === 'company_panel') {
    $company_id = $_GET['company_id'];
    $stmt = $pdo->prepare("
        SELECT c.content AS comment_content, 
               r.screenshot_path, 
               r.created_at AS review_date, 
               u.name AS reviewer_name 
        FROM comments c
        LEFT JOIN reviews r ON c.id = r.comment_id
        LEFT JOIN users u ON r.user_id = u.id
        WHERE c.company_id = ?
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$company_id]);
    $panel = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($panel);
}
?>
