<?php
$host = 'localhost';
$dbname = 'avaliacoes_google';
$user = 'root';
$password = '';

try {
    // charset para evitar problemas com caracteres
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Registra o erro no log e exibe uma mensagem
    error_log("Erro ao conectar ao banco de dados: " . $e->getMessage());
    die("Erro ao conectar ao banco de dados. Por favor, tente novamente mais tarde.");
}