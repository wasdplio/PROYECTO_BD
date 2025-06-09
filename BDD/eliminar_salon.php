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
    $salon_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    
    if ($salon_id) {
        try {
            // Iniciar transacción
            $conn->beginTransaction();
            
            // Primero eliminamos las computadoras asociadas al salón
            $stmt = $conn->prepare("DELETE FROM computadores WHERE salon_id = :salon_id");
            $stmt->bindParam(':salon_id', $salon_id);
            $stmt->execute();
            
            // Luego eliminamos el salón
            $stmt = $conn->prepare("DELETE FROM salones WHERE id = :id");
            $stmt->bindParam(':id', $salon_id);
            $stmt->execute();
            
            // Confirmar transacción
            $conn->commit();
            
            echo json_encode(['success' => true]);
        } catch(PDOException $e) {
            // Revertir transacción en caso de error
            $conn->rollBack();
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