<?php
session_start();
require 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $salon_id = $_POST['salon_id'];
    $codigo_patrimonio = $_POST['codigo_patrimonio'];
    $marca = $_POST['marca'];
    $modelo = $_POST['modelo'];
    $sistema_operativo = $_POST['sistema_operativo'];
    $ram_gb = $_POST['ram_gb'];
    $almacenamiento_gb = $_POST['almacenamiento_gb'];
    $tipo_almacenamiento = $_POST['tipo_almacenamiento'];
    $estado = $_POST['estado'];
    $fecha_instalacion = $_POST['fecha_instalacion'];
    $ultimo_mantenimiento = $_POST['ultimo_mantenimiento'];
    $observaciones = $_POST['observaciones'];
    
    try {
        $stmt = $conn->prepare("INSERT INTO computadores 
                               (salon_id, codigo_patrimonio, marca, modelo, sistema_operativo, ram_gb, 
                               almacenamiento_gb, tipo_almacenamiento, estado, fecha_instalacion, 
                               ultimo_mantenimiento, observaciones) 
                               VALUES 
                               (:salon_id, :codigo_patrimonio, :marca, :modelo, :sistema_operativo, :ram_gb, 
                               :almacenamiento_gb, :tipo_almacenamiento, :estado, :fecha_instalacion, 
                               :ultimo_mantenimiento, :observaciones)");
        
        $stmt->bindParam(':salon_id', $salon_id);
        $stmt->bindParam(':codigo_patrimonio', $codigo_patrimonio);
        $stmt->bindParam(':marca', $marca);
        $stmt->bindParam(':modelo', $modelo);
        $stmt->bindParam(':sistema_operativo', $sistema_operativo);
        $stmt->bindParam(':ram_gb', $ram_gb);
        $stmt->bindParam(':almacenamiento_gb', $almacenamiento_gb);
        $stmt->bindParam(':tipo_almacenamiento', $tipo_almacenamiento);
        $stmt->bindParam(':estado', $estado);
        $stmt->bindParam(':fecha_instalacion', $fecha_instalacion);
        $stmt->bindParam(':ultimo_mantenimiento', $ultimo_mantenimiento);
        $stmt->bindParam(':observaciones', $observaciones);
        
        $stmt->execute();
        
        echo "success";
    } catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>