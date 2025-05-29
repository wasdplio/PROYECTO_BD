<?php
require 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $contraseña = $_POST['contraseña'];
    

    if (empty($nombre) || empty($email) || empty($contraseña)) {
        echo "Todos los campos son obligatorios";
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "El correo electrónico no es válido";
        exit;
    }
    
    if (strlen($contraseña) < 6) {
        echo "La contraseña debe tener al menos 6 caracteres";
        exit;
    }
    
    try {
        
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            echo "El correo electrónico ya está registrado";
            exit;
        }
        
       
        $contraseña_hash = hash('sha256', $contraseña);
        
       
        $insert = $conn->prepare("INSERT INTO usuarios (nombre, email, contraseña, fecha_registro) 
                                VALUES (:nombre, :email, :contrasena, NOW())");  
        
        $insert->bindParam(':nombre', $nombre);
        $insert->bindParam(':email', $email);
        $insert->bindParam(':contrasena', $contraseña_hash);  
        
        if ($insert->execute()) {
            echo "Registro exitoso. Bienvenido, " . htmlspecialchars($nombre) . "! Ahora puedes iniciar sesión.";
        } else {
            echo "Error al registrar el usuario";
        }
    } catch(PDOException $e) {
        echo "Error al registrar el usuario: " . $e->getMessage();
    }
}
?>