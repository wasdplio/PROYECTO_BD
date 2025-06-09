<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('Operación no permitida');
}

require 'conexion.php';

try {
    // Primero eliminamos los salones y computadoras asociadas (si es necesario)
    $stmt = $conn->prepare("DELETE FROM salones WHERE sede_id = :sede_id");
    $stmt->bindParam(':sede_id', $_POST['id']);
    $stmt->execute();
    
    // Luego eliminamos la sede
    $stmt = $conn->prepare("DELETE FROM sedes WHERE id = :id");
    $stmt->bindParam(':id', $_POST['id']);
    $stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Sede eliminada correctamente']);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error al eliminar la sede: ' . $e->getMessage()]);
}
?>