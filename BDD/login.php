<?php
require 'conexion.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $contraseña = $_POST['contraseña'];
    
    try {
        $stmt = $conn->prepare("SELECT * FROM usuarios WHERE email = :email AND activo = TRUE");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verificar la contraseña directamente (sin hash)
            if ($contraseña === $usuario['contraseña']) {
                // Actualizar último login
                $update = $conn->prepare("UPDATE usuarios SET ultimo_login = NOW() WHERE id = :id");
                $update->bindParam(':id', $usuario['id']);
                $update->execute();
                
                // Guardar datos en sesión
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['nombre'] = $usuario['nombre'];
                $_SESSION['email'] = $usuario['email'];
                $_SESSION['rol'] = $usuario['rol'];
                
                echo "success";
            } else {
                echo "Contraseña incorrecta";
            }
        } else {
            echo "Usuario no encontrado o inactivo";
        }
    } catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>