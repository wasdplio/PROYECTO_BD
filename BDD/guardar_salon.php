<?php
session_start();
require 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $sede_id = $_POST['sede_id'];
    $codigo_salon = $_POST['codigo_salon'];
    $piso = $_POST['piso'];
    $capacidad = $_POST['capacidad'];
    $numero_computadores = $_POST['numero_computadores'];
    $descripcion = $_POST['descripcion'];
    
    try {
        $stmt = $conn->prepare("INSERT INTO salones (sede_id, codigo_salon, piso, capacidad, numero_computadores, descripcion) 
                               VALUES (:sede_id, :codigo_salon, :piso, :capacidad, :numero_computadores, :descripcion)");
        $stmt->bindParam(':sede_id', $sede_id);
        $stmt->bindParam(':codigo_salon', $codigo_salon);
        $stmt->bindParam(':piso', $piso);
        $stmt->bindParam(':capacidad', $capacidad);
        $stmt->bindParam(':numero_computadores', $numero_computadores);
        $stmt->bindParam(':descripcion', $descripcion);
        $stmt->execute();
        
        echo "success";
    } catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>