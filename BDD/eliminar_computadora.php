<?php
session_start();
require 'conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header("HTTP/1.1 401 Unauthorized");
    exit;
}

// Verificar token CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'error' => 'Token CSRF inválido']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $computadora_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    
    if ($computadora_id) {
        try {
            $stmt = $conn->prepare("DELETE FROM computadores WHERE id = :id");
            $stmt->bindParam(':id', $computadora_id);
            $stmt->execute();
            
            echo json_encode(['success' => true]);
        } catch(PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'ID inválido']);
    }
} else {
    header("HTTP/1.1 405 Method Not Allowed");
    exit;
}
?>