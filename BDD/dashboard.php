<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

require 'conexion.php';

// Obtener el nombre del usuario para mostrar en el header
$nombreUsuario = $_SESSION['nombre'];

// Obtener las sedes de la base de datos
$sedes = [];
try {
    $stmt = $conn->query("SELECT * FROM sedes WHERE activa = TRUE");
    $sedes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    // Manejar error
    die("Error al obtener sedes: " . $e->getMessage());
}

// Obtener los salones para mostrar en la tabla
$salones = [];
if (isset($_GET['sede_id'])) {
    $sede_id = $_GET['sede_id'];
    try {
        $stmt = $conn->prepare("SELECT * FROM salones WHERE sede_id = :sede_id");
        $stmt->bindParam(':sede_id', $sede_id);
        $stmt->execute();
        $salones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        // Manejar error
        die("Error al obtener salones: " . $e->getMessage());
    }
}

// Obtener las computadoras si se seleccionó un salón
$computadoras = [];
if (isset($_GET['salon_id'])) {
    $salon_id = $_GET['salon_id'];
    try {
        $stmt = $conn->prepare("SELECT * FROM computadores WHERE salon_id = :salon_id");
        $stmt->bindParam(':salon_id', $salon_id);
        $stmt->execute();
        $computadoras = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        // Manejar error
        die("Error al obtener computadoras: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PeopleHub | Sistema de Gestión de Computadoras</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <style>
        :root {
            --color-primary: #6c5ce7;      /* Morado */
            --color-secondary: #0984e3;    /* Azul */
            --color-accent: #00cec9;       /* Turquesa */
            --color-orange: #e67e22;       /* Naranja */
            --color-green: #2ecc71;        /* Verde */
            --color-light: #f8f9fa;
            --color-dark: #2d3436;
            --color-success: #00b894;
            --color-warning: #fdcb6e;
            --color-danger: #d63031;
            --color-dark-blue: #1d00ff;    /* Azul oscuro */
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f6fa;
            color: var(--color-dark);
            display: flex;
            min-height: 100vh;
        }

        /* 🎀 Menú Lateral */
        .sidebar {
            width: 250px;
            background: white;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
        }

        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 30px;
        }

        .sidebar-header i {
            font-size: 1.5rem;
            color: var(--color-primary);
        }

        .sidebar-menu {
            padding: 1rem 0;
        }

        .menu-item {
            padding: 0.8rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            transition: all 0.3s;
            border-left: 4px solid transparent;
        }

        .menu-item:hover {
            background: #f8f9fa;
            border-left: 4px solid var(--color-primary);
        }

        .menu-item.active {
            background: #f0f2ff;
            border-left: 4px solid var(--color-primary);
            color: var(--color-primary);
        }

        .menu-item i {
            font-size: 1.1rem;
            width: 24px;
            text-align: center;
        }

        .menu-item span {
            font-weight: 500;
        }

        /* 🎀 Header Estilo Moderno */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .header {
            background: linear-gradient(135deg, var(--color-dark-blue), var(--color-primary));
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.5rem;
            font-weight: bold;
        }

        .logo i {
            font-size: 1.8rem;
            color: var(--color-accent);
        }

        .search-bar {
            display: flex;
            width: 280px;
            position: relative;
            margin-right: 15px;
        }

        .search-bar input {
            width: 100%;
            padding: 0.6rem 1rem 0.6rem 2.5rem;
            border: none;
            border-radius: 30px;
            font-size: 0.9rem;
            outline: none;
            background: #f1f3f5;
            transition: all 0.3s;
            height: 42px;
        }

        .search-bar button {
            background: none;
            color: #7f8c8d;
            border: none;
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            transition: all 0.3s;
            z-index: 1;
            font-size: 0.9rem;
        }

        .search-bar button:hover {
            color: var(--color-dark);
        }
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 15px;
            cursor: pointer;
        }

        .user-menu img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid white;
            object-fit: cover;
        }

        /* 📊 Tarjetas Dashboard con nuevos colores */
        .dashboard {
            padding: 2rem;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s, box-shadow 0.3s;
            border-top: 4px solid;
            cursor: pointer;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
        }

        /* Asignación de colores específicos a cada tarjeta */
        .card:nth-child(1) { border-color: var(--color-orange); }  /* Naranja */
        .card:nth-child(2) { border-color: var(--color-green); }   /* Verde */
        .card:nth-child(3) { border-color: var(--color-primary); } /* Morado */
        .card:nth-child(4) { border-color: var(--color-secondary); } /* Azul */

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .card-header i {
            font-size: 1.8rem;
        }

        /* Iconos con el color de cada tarjeta */
        .card:nth-child(1) .card-header i { color: var(--color-orange); }
        .card:nth-child(2) .card-header i { color: var(--color-green); }
        .card:nth-child(3) .card-header i { color: var(--color-primary); }
        .card:nth-child(4) .card-header i { color: var(--color-secondary); }

        .card h3 {
            font-size: 1.2rem;
            color: var(--color-dark);
            margin-bottom: 0.5rem;
        }

        .card p {
            color: #7f8c8d;
            font-size: 0.9rem;
        }

        .progress-bar {
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            margin: 1rem 0;
            overflow: hidden;
        }

        .progress {
            height: 100%;
            border-radius: 4px;
        }

        /* Barras de progreso con colores de tarjeta */
        .card:nth-child(1) .progress { background: var(--color-orange); width: 75%; }
        .card:nth-child(2) .progress { background: var(--color-green); width: 45%; }
        .card:nth-child(3) .progress { background: var(--color-primary); width: 90%; }
        .card:nth-child(4) .progress { background: var(--color-secondary); width: 30%; }

        /* 📜 Tabla de Salones/Computadoras */
        .data-section {
            padding: 0 2rem 2rem;
        }

        .table-container {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.05);
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .table-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--color-dark);
        }

        .table-actions button {
            padding: 0.6rem 1.2rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
        }

        .btn-primary {
            background: var(--color-primary);
            color: white;
        }

        .btn-primary:hover {
            background: #5a4abf;
        }

        .btn-secondary {
            background: var(--color-light);
            color: var(--color-dark);
            margin-left: 0.5rem;
        }

        .btn-secondary:hover {
            background: #e0e0e0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f8f9fa;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: var(--color-dark);
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }

        tr:hover {
            background: #f5f6fa;
        }

        .pc-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .pc-info img {
            width: 40px;
            height: 40px;
            border-radius: 4px;
            object-fit: cover;
        }

        .badge {
            padding: 0.3rem 0.6rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .badge-success {
            background: #e8f7f0;
            color: var(--color-green);
        }

        .badge-warning {
            background: #fff8e6;
            color: var(--color-orange);
        }

        .badge-danger {
            background: #ffebee;
            color: var(--color-danger);
        }

        .actions button {
            background: none;
            border: none;
            cursor: pointer !important;
            font-size: 1rem;
            margin-right: 0.5rem;
            transition: all 0.3s;
        }

        .actions button:hover {
            opacity: 0.8;
        }

        .view-btn { color: var(--color-primary); }
        .edit-btn { color: var(--color-secondary); }
        .delete-btn { color: var(--color-danger); }

        /* Modal para agregar salones */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            overflow-y: auto;
            padding: 20px;
        }

        .modal-content {
            background-color: white;
            padding: 2rem;
            border-radius: 12px;
            width: auto;
            min-width: 500px;
            max-width: 90%;
            max-height: 90vh;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            animation: modalFadeIn 0.3s ease-out;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }

        .modal-title {
            font-size: 1.5rem;
            color: var(--color-primary);
            font-weight: 600;
        }

        .modal-body {
            max-height: 60vh;
            overflow-y: auto;
            padding-right: 10px;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #eee;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--color-dark);
            font-size: 0.9rem;
        }

        .form-group input, 
        .form-group select, 
        .form-group textarea {
            width: 100%;
            padding: 0.7rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 0.9rem;
            transition: all 0.3s;
        }

        .form-group textarea {
            min-height: 80px;
        }

        .form-group input:focus, 
        .form-group select:focus, 
        .form-group textarea:focus {
            border-color: var(--color-primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(108, 92, 231, 0.1);
        }

        .btn-cancel {
            background: #f1f1f1;
            color: var(--color-dark);
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
        }

        .btn-cancel:hover {
            background: #e0e0e0;
        }

        .btn-submit {
            background: var(--color-primary);
            color: white;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
        }

        .btn-submit:hover {
            background: #5a4abf;
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 1.8rem;
            cursor: pointer;
            color: #7f8c8d;
            transition: all 0.3s;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            line-height: 1;
            padding: 0;
        }

        .close-modal:hover {
            background-color: #f5f5f5;
            color: var(--color-danger);
            transform: rotate(90deg);
        }

        /* 📱 Responsive */
        @media (max-width: 1200px) {
            .dashboard {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
                overflow: hidden;
            }

            .sidebar-header span, .menu-item span {
                display: none;
            }

            .menu-item {
                justify-content: center;
            }

            .dashboard {
                grid-template-columns: 1fr;
            }
            
            .header {
                flex-direction: column;
                gap: 1rem;
                padding: 1rem;
            }
            
            .search-bar {
                width: 100%;
            }
            
            .modal-content {
                min-width: 90%;
            }
        }
        
        /* Estilos para SweetAlert2 */
        .swal2-container {
            background: rgba(0, 0, 0, 0.4) !important;
        }
        
        .swal2-popup {
            border: none !important;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15) !important;
            border-radius: 12px !important;
            overflow: hidden !important;
            padding: 2rem !important;
        }
        
        .swal2-confirm {
            background: linear-gradient(135deg, var(--color-primary), var(--color-secondary)) !important;
            color: white !important;
            border: none !important;
            border-radius: 8px !important;
            padding: 12px 24px !important;
            font-weight: 500 !important;
            font-size: 15px !important;
            transition: all 0.3s ease !important;
            box-shadow: 0 4px 8px rgba(67, 97, 238, 0.3) !important;
            margin: 0 8px !important;
            outline: none !important;
        }
        
        .swal2-confirm:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 6px 12px rgba(67, 97, 238, 0.4) !important;
        }
        
        .swal2-cancel {
            background: white !important;
            color: var(--color-dark) !important;
            border: 2px solid #e0e0e0 !important;
            border-radius: 8px !important;
            padding: 12px 24px !important;
            font-weight: 500 !important;
            font-size: 15px !important;
            transition: all 0.3s ease !important;
            margin: 0 8px !important;
            outline: none !important;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1) !important;
        }
        
        .swal2-cancel:hover {
            background: #f5f5f5 !important;
            border-color: #d0d0d0 !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15) !important;
        }
        
        .swal2-icon.swal2-question {
            border-color: var(--color-primary) !important;
            color: var(--color-primary) !important;
        }
        /* Agrega esto al final de tu sección de estilos CSS */
table tbody tr {
    cursor: pointer;
}

/* Para evitar que los botones dentro de las celdas cambien el cursor */
table tbody tr .actions button {
    cursor: default;
}

/* Para las computadoras (si es necesario) */
table tbody tr[onclick] {
    cursor: pointer;
}

    </style>
</head>
<body>
    <!-- 🎀 Menú Lateral -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-laptop"></i>
            <span>PeopleHub</span>
        </div>

        <div class="sidebar-menu">
            <div class="menu-item active">
                <i class="fas fa-home"></i>
                <span>Inicio</span>
            </div>
            <div class="menu-item">
                <i class="fas fa-building"></i>
                <span>Sedes</span>
            </div>
            <div class="menu-item">
                <i class="fas fa-laptop-house"></i>
                <span>Salones</span>
            </div>
            <div class="menu-item">
                <i class="fas fa-laptop"></i>
                <span>Computadoras</span>
            </div>
            <div class="menu-item">
                <i class="fas fa-cog"></i>
                <span>Configuración</span>
            </div>
        </div>
    </aside>

    <!-- Contenido Principal -->
    <div class="main-content">
        <!-- 🎀 Header -->
        <header class="header">
            <div class="logo">
                <i class="fas fa-laptop"></i>
                <span>PeopleHub</span>
            </div>
            
            <div class="search-bar">
                <input type="text" placeholder="Buscar salones o computadoras...">
                <button><i class="fas fa-search"></i></button>
            </div>
            
            <div class="user-menu" onclick="confirmLogout()">
                <span><?php echo htmlspecialchars($nombreUsuario); ?></span>
                <img src="https://randomuser.me/api/portraits/women/65.jpg" alt="Usuario">
            </div>
        </header>

        <!-- 📊 Tarjetas Dashboard con las sedes -->
        <section class="dashboard">
            <?php foreach ($sedes as $sede): ?>
            <div class="card" onclick="window.location.href='dashboard.php?sede_id=<?php echo $sede['id']; ?>'">
                <div class="card-header">
                    <h3><?php echo htmlspecialchars($sede['nombre']); ?></h3>
                    <i class="fas fa-building"></i>
                </div>
                <p><?php echo htmlspecialchars($sede['direccion']); ?></p>
                <div class="progress-bar">
                    <div class="progress"></div>
                </div>
                <p>Responsable: <?php echo htmlspecialchars($sede['responsable']); ?></p>
            </div>
            <?php endforeach; ?>
        </section>

        <!-- 📜 Tabla de Salones o Computadoras -->
        <section class="data-section">
            <div class="table-container">
                <div class="table-header">
                    <?php if (isset($_GET['sede_id'])): ?>
                    <h2 class="table-title">Salones de la Sede</h2>
                    <div class="table-actions">
                        <button class="btn-primary" id="btnAgregarSalon"><i class="fas fa-plus"></i> Agregar Salón</button>
                    </div>
                    <?php elseif (isset($_GET['salon_id'])): ?>
                    <h2 class="table-title">Computadoras del Salón</h2>
                    <div class="table-actions">
                        <button class="btn-primary" id="btnAgregarComputadora"><i class="fas fa-plus"></i> Agregar Computadora</button>
                    </div>
                    <?php else: ?>
                    <h2 class="table-title">Seleccione una sede para ver los salones</h2>
                    <?php endif; ?>
                </div>
                
                <?php if (isset($_GET['sede_id']) && !empty($salones)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Descripción</th>
                            <th>Piso</th>
                            <th>Capacidad</th>
                            <th>Computadoras</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($salones as $salon): ?>
                        <tr onclick="window.location.href='dashboard.php?salon_id=<?php echo $salon['id']; ?>'">
                            <td><?php echo htmlspecialchars($salon['codigo_salon']); ?></td>
                            <td><?php echo htmlspecialchars($salon['descripcion']); ?></td>
                            <td><?php echo htmlspecialchars($salon['piso']); ?></td>
                            <td><?php echo htmlspecialchars($salon['capacidad']); ?></td>
                            <td><?php echo htmlspecialchars($salon['numero_computadores']); ?></td>
                            <td class="actions">
                                <button class="view-btn" onclick="event.stopPropagation(); verSalon(<?php echo $salon['id']; ?>)"><i class="fas fa-eye"></i></button>
                                <button class="edit-btn" onclick="event.stopPropagation(); editarSalon(<?php echo $salon['id']; ?>)"><i class="fas fa-edit"></i></button>
                                <button class="delete-btn" onclick="event.stopPropagation(); eliminarSalon(<?php echo $salon['id']; ?>)"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php elseif (isset($_GET['salon_id']) && !empty($computadoras)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Marca/Modelo</th>
                            <th>Especificaciones</th>
                            <th>Estado</th>
                            <th>Últ. Mantenimiento</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($computadoras as $pc): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($pc['codigo_patrimonio']); ?></td>
                            <td>
                                <div class="pc-info">
                                    <i class="fas fa-laptop" style="font-size: 2rem; color: #6c5ce7;"></i>
                                    <div>
                                        <strong><?php echo htmlspecialchars($pc['marca']); ?></strong>
                                        <p><?php echo htmlspecialchars($pc['modelo']); ?></p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <strong>SO:</strong> <?php echo htmlspecialchars($pc['sistema_operativo']); ?><br>
                                <strong>RAM:</strong> <?php echo htmlspecialchars($pc['ram_gb']); ?>GB<br>
                                <strong>Alm.:</strong> <?php echo htmlspecialchars($pc['almacenamiento_gb']); ?>GB <?php echo htmlspecialchars($pc['tipo_almacenamiento']); ?>
                            </td>
                            <td>
                                <?php 
                                $badge_class = '';
                                if ($pc['estado'] == 'operativo') {
                                    $badge_class = 'badge-success';
                                } elseif ($pc['estado'] == 'mantenimiento') {
                                    $badge_class = 'badge-warning';
                                } else {
                                    $badge_class = 'badge-danger';
                                }
                                ?>
                                <span class="badge <?php echo $badge_class; ?>"><?php echo ucfirst($pc['estado']); ?></span>
                            </td>
                            <td><?php echo $pc['ultimo_mantenimiento'] ? date('d/m/Y', strtotime($pc['ultimo_mantenimiento'])) : 'Nunca'; ?></td>
                            <td class="actions">
                                <button class="view-btn" onclick="verComputadora(<?php echo $pc['id']; ?>)"><i class="fas fa-eye"></i></button>
                                <button class="edit-btn" onclick="editarComputadora(<?php echo $pc['id']; ?>)"><i class="fas fa-edit"></i></button>
                                <button class="delete-btn" onclick="eliminarComputadora(<?php echo $pc['id']; ?>)"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php elseif (isset($_GET['sede_id']) && empty($salones)): ?>
                <p>No hay salones registrados en esta sede.</p>
                <?php elseif (isset($_GET['salon_id']) && empty($computadoras)): ?>
                <p>No hay computadoras registradas en este salón.</p>
                <?php endif; ?>
            </div>
        </section>
    </div>
    
    <!-- Modal para agregar salones -->
    <div class="modal" id="modalSalon">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-laptop-house"></i> Agregar Nuevo Salón</h3>
                <button class="close-modal" id="closeModalSalon">&times;</button>
            </div>
            
            <form id="formSalon" action="guardar_salon.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="sede_id" value="<?php echo isset($_GET['sede_id']) ? $_GET['sede_id'] : ''; ?>">
                    
                    <div class="form-group">
                        <label for="codigo_salon">Código del Salón</label>
                        <input type="text" id="codigo_salon" name="codigo_salon" placeholder="Ej: A101" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="piso">Piso</label>
                            <input type="number" id="piso" name="piso" min="1" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="capacidad">Capacidad</label>
                            <input type="number" id="capacidad" name="capacidad" min="1" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="numero_computadores">Número de Computadoras</label>
                        <input type="number" id="numero_computadores" name="numero_computadores" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="descripcion">Descripción</label>
                        <textarea id="descripcion" name="descripcion" rows="3" placeholder="Descripción del salón..."></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" id="cancelarFormSalon">Cancelar</button>
                    <button type="submit" class="btn-submit">Guardar Salón</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal para agregar computadoras -->
    <div class="modal" id="modalComputadora">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-laptop"></i> Agregar Nueva Computadora</h3>
                <button class="close-modal" id="closeModalComputadora">&times;</button>
            </div>
            
            <form id="formComputadora" action="guardar_computadora.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="salon_id" value="<?php echo isset($_GET['salon_id']) ? $_GET['salon_id'] : ''; ?>">
                    
                    <div class="form-group">
                        <label for="codigo_patrimonio">Código Patrimonial</label>
                        <input type="text" id="codigo_patrimonio" name="codigo_patrimonio" placeholder="Ej: PAT-001" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="marca">Marca</label>
                            <input type="text" id="marca" name="marca" placeholder="Ej: Dell, HP" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="modelo">Modelo</label>
                            <input type="text" id="modelo" name="modelo" placeholder="Ej: OptiPlex 7080" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="sistema_operativo">Sistema Operativo</label>
                        <input type="text" id="sistema_operativo" name="sistema_operativo" placeholder="Ej: Windows 10 Pro">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="ram_gb">RAM (GB)</label>
                            <input type="number" id="ram_gb" name="ram_gb" min="1" step="1">
                        </div>
                        
                        <div class="form-group">
                            <label for="almacenamiento_gb">Almacenamiento (GB)</label>
                            <input type="number" id="almacenamiento_gb" name="almacenamiento_gb" min="1" step="1">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="tipo_almacenamiento">Tipo de Almacenamiento</label>
                        <select id="tipo_almacenamiento" name="tipo_almacenamiento">
                            <option value="HDD">HDD</option>
                            <option value="SSD">SSD</option>
                            <option value="NVMe">NVMe</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="estado">Estado</label>
                        <select id="estado" name="estado" required>
                            <option value="operativo">Operativo</option>
                            <option value="mantenimiento">En Mantenimiento</option>
                            <option value="dañado">Dañado</option>
                        </select>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="fecha_instalacion">Fecha de Instalación</label>
                            <input type="date" id="fecha_instalacion" name="fecha_instalacion">
                        </div>
                        
                        <div class="form-group">
                            <label for="ultimo_mantenimiento">Último Mantenimiento</label>
                            <input type="date" id="ultimo_mantenimiento" name="ultimo_mantenimiento">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="observaciones">Observaciones</label>
                        <textarea id="observaciones" name="observaciones" rows="3" placeholder="Notas adicionales..."></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" id="cancelarFormComputadora">Cancelar</button>
                    <button type="submit" class="btn-submit">Guardar Computadora</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // 🔍 Función de búsqueda
        document.querySelector('.search-bar button').addEventListener('click', buscar);
        document.querySelector('.search-bar input').addEventListener('keyup', function(e) {
            if (e.key === 'Enter') buscar();
        });

        function buscar() {
            const termino = document.querySelector('.search-bar input').value.toLowerCase();
            const filas = document.querySelectorAll('tbody tr');
            
            filas.forEach(fila => {
                const textoFila = fila.textContent.toLowerCase();
                if (textoFila.includes(termino)) {
                    fila.style.display = '';
                } else {
                    fila.style.display = 'none';
                }
            });
        }

        // 🖱️ Cambiar ítem activo del menú
        const menuItems = document.querySelectorAll('.menu-item');
        menuItems.forEach(item => {
            item.addEventListener('click', function() {
                menuItems.forEach(i => i.classList.remove('active'));
                this.classList.add('active');
            });
        });

        // 🔒 Función para cerrar sesión con SweetAlert2
        function confirmLogout() {
            Swal.fire({
                title: 'Cerrar Sesión',
                html: '¿Estás seguro de que deseas salir de tu cuenta?',
                icon: 'question',
                iconColor: 'var(--color-secondary)',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-sign-out-alt" style="margin-right: 8px;"></i> Cerrar Sesión',
                cancelButtonText: '<i class="fas fa-times" style="margin-right: 8px;"></i> Cancelar',
                customClass: {
                    confirmButton: 'swal2-confirm',
                    cancelButton: 'swal2-cancel',
                    popup: 'custom-swal-popup'
                },
                buttonsStyling: false,
                showClass: {
                    popup: 'animate__animated animate__fadeInDown animate__faster'
                },
                hideClass: {
                    popup: 'animate__animated animate__fadeOutUp animate__faster'
                },
                focusConfirm: false,
                focusCancel: false,
                backdrop: 'rgba(0,0,0,0.4)'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Cerrando sesión...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        },
                        background: 'white',
                        backdrop: 'rgba(0,0,0,0.4)'
                    });
                    
                    setTimeout(() => {
                        window.location.href = 'logout.php';
                    }, 800);
                }
            });
        }
        
        // Manejo del modal para agregar salones
        const modalSalon = document.getElementById('modalSalon');
        const btnAgregarSalon = document.getElementById('btnAgregarSalon');
        const closeModalSalon = document.getElementById('closeModalSalon');
        const cancelarFormSalon = document.getElementById('cancelarFormSalon');
        const formSalon = document.getElementById('formSalon');
        
        if (btnAgregarSalon) {
            btnAgregarSalon.addEventListener('click', () => {
                modalSalon.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            });
        }
        
        closeModalSalon.addEventListener('click', () => {
            modalSalon.style.display = 'none';
            document.body.style.overflow = 'auto';
        });
        
        cancelarFormSalon.addEventListener('click', () => {
            modalSalon.style.display = 'none';
            document.body.style.overflow = 'auto';
        });
        
        modalSalon.addEventListener('click', (e) => {
            if (e.target === modalSalon) {
                modalSalon.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });
        
        // Manejo del modal para agregar computadoras
        const modalComputadora = document.getElementById('modalComputadora');
        const btnAgregarComputadora = document.getElementById('btnAgregarComputadora');
        const closeModalComputadora = document.getElementById('closeModalComputadora');
        const cancelarFormComputadora = document.getElementById('cancelarFormComputadora');
        const formComputadora = document.getElementById('formComputadora');
        
        if (btnAgregarComputadora) {
            btnAgregarComputadora.addEventListener('click', () => {
                modalComputadora.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            });
        }
        
        closeModalComputadora.addEventListener('click', () => {
            modalComputadora.style.display = 'none';
            document.body.style.overflow = 'auto';
        });
        
        cancelarFormComputadora.addEventListener('click', () => {
            modalComputadora.style.display = 'none';
            document.body.style.overflow = 'auto';
        });
        
        modalComputadora.addEventListener('click', (e) => {
            if (e.target === modalComputadora) {
                modalComputadora.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });
        
        // Funciones para los botones de acción
        function verSalon(id) {
            // Implementar lógica para ver detalles del salón
            Swal.fire({
                title: 'Detalles del Salón',
                text: 'Mostrando detalles del salón con ID: ' + id,
                icon: 'info',
                confirmButtonText: 'Cerrar'
            });
        }
        
        function editarSalon(id) {
            // Implementar lógica para editar salón
            Swal.fire({
                title: 'Editar Salón',
                text: 'Editando salón con ID: ' + id,
                icon: 'info',
                confirmButtonText: 'Cerrar'
            });
        }
        
        function eliminarSalon(id) {
            Swal.fire({
                title: '¿Eliminar Salón?',
                text: '¿Estás seguro de que deseas eliminar este salón?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Aquí iría la llamada AJAX para eliminar el salón
                    Swal.fire(
                        'Eliminado',
                        'El salón ha sido eliminado.',
                        'success'
                    ).then(() => {
                        // Recargar la página para actualizar la tabla
                        window.location.reload();
                    });
                }
            });
        }
        
        function verComputadora(id) {
            // Implementar lógica para ver detalles de la computadora
            Swal.fire({
                title: 'Detalles de la Computadora',
                text: 'Mostrando detalles de la computadora con ID: ' + id,
                icon: 'info',
                confirmButtonText: 'Cerrar'
            });
        }
        
        function editarComputadora(id) {
            // Implementar lógica para editar computadora
            Swal.fire({
                title: 'Editar Computadora',
                text: 'Editando computadora con ID: ' + id,
                icon: 'info',
                confirmButtonText: 'Cerrar'
            });
        }
        
        function eliminarComputadora(id) {
            Swal.fire({
                title: '¿Eliminar Computadora?',
                text: '¿Estás seguro de que deseas eliminar esta computadora?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Aquí iría la llamada AJAX para eliminar la computadora
                    Swal.fire(
                        'Eliminada',
                        'La computadora ha sido eliminada.',
                        'success'
                    ).then(() => {
                        // Recargar la página para actualizar la tabla
                        window.location.reload();
                    });
                }
            });
        }
        
        // Manejar el envío del formulario de salón
        formSalon.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(formSalon);
            
            fetch('guardar_salon.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                Swal.fire({
                    title: '¡Salón guardado!',
                    text: 'El salón ha sido registrado exitosamente.',
                    icon: 'success',
                    confirmButtonText: 'Aceptar'
                }).then(() => {
                    window.location.reload();
                });
            })
            .catch(error => {
                Swal.fire({
                    title: 'Error',
                    text: 'Ocurrió un error al guardar el salón.',
                    icon: 'error',
                    confirmButtonText: 'Aceptar'
                });
            });
        });
        
        // Manejar el envío del formulario de computadora
        formComputadora.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(formComputadora);
            
            fetch('guardar_computadora.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                Swal.fire({
                    title: '¡Computadora guardada!',
                    text: 'La computadora ha sido registrada exitosamente.',
                    icon: 'success',
                    confirmButtonText: 'Aceptar'
                }).then(() => {
                    window.location.reload();
                });
            })
            .catch(error => {
                Swal.fire({
                    title: 'Error',
                    text: 'Ocurrió un error al guardar la computadora.',
                    icon: 'error',
                    confirmButtonText: 'Aceptar'
                });
            });
        });
    </script>
</body>
</html>