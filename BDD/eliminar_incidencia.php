<?php
session_start();
require 'conexion.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
    exit;
}

if (empty($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de incidencia no proporcionado']);
    exit;
}

try {
    $stmt = $conn->prepare("DELETE FROM incidencias WHERE id = :id");
    $stmt->bindParam(':id', $_POST['id']);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Incidencia eliminada correctamente'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar la incidencia']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
}
?>