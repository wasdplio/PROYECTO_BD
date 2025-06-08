<?php
session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

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
        die("Error al obtener computadoras: " . $e->getMessage());
    }
}

// Obtener usuarios técnicos para asignación de incidencias
$tecnicos = [];
try {
    $stmt = $conn->query("SELECT id, nombre, apellido FROM usuarios WHERE rol = 'tecnico'");
    $tecnicos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    // No detenemos la ejecución si hay error al obtener técnicos
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token']; ?>">
    <title>PeopleHub | Sistema de Gestión de Computadoras</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="estilos.css">
    <link rel="stylesheet" href="estilos2.css">
 <style>
    /* Estilos para el modal de detalles */
    .pc-detail-popup {
        text-align: center;
    }
    
    .pc-detail-container {
        max-width: 100%;
        text-align: center;
    }
    
    .pc-header {
        display: flex;
        flex-direction: column;
        align-items: center;
        margin-bottom: 20px;
    }
    
    .pc-icon {
        font-size: 3rem;
        color: #6c5ce7;
        margin-bottom: 10px;
    }
    
    .pc-title h3 {
        margin: 0;
        color: #2d3436;
        font-size: 1.5rem;
    }
    
    .pc-subtitle {
        margin: 5px 0 0;
        color: #636e72;
        font-size: 1rem;
    }
    
    .pc-detail-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 20px;
    }
    
    .pc-detail-section {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        text-align: left;
        margin-top:20px;
    }
    
    .section-title {
        color: #6c5ce7;
        font-size: 1rem;
        margin-top: 0;
        margin-bottom: 15px;
        padding-bottom: 5px;
        border-bottom: 1px solid #dfe6e9;
    }
    
    .detail-item {
        margin-bottom: 10px;
        display: flex;
        justify-content: space-between;
    }
    
    .detail-label {
        font-weight: 500;
        color: #2d3436;
    }
    
    .detail-value {
        color: #636e72;
        text-align: right;
    }
    
    .pc-observations {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        text-align: left;
    }
    
    .observations-content {
        color: #636e72;
        line-height: 1.5;
    }
    
    /* Estilos para los badges */
    .badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 0.8rem;
        font-weight: 500;
        gap: 5px;
    }
    
    .badge i {
        font-size: 0.8rem;
    }
    
    .badge-success {
        background-color: #d4edda;
        color: #155724;
    }
    
    .badge-warning {
        background-color: #fff3cd;
        color: #856404;
    }
    
    .badge-danger {
        background-color: #f8d7da;
        color: #721c24;
    }
</style>
</head>
<body>
    <!-- 🎀 Menú Lateral -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-laptop-code"></i>
            <span>PeopleHub</span>
        </div>

        <div class="sidebar-menu">
            <div class="menu-item active" data-section="inicio">
                <i class="fas fa-home"></i>
                <span>Inicio</span>
            </div>
            <div class="menu-item" data-section="incidencias">
                <i class="fas fa-exclamation-triangle"></i>
                <span>Incidencias</span>
            </div>
            
        </div>
    </aside>

    <!-- Contenido Principal -->
    <div class="main-content">
        <!-- 🎀 Header -->
        <header class="header">
            <button class="menu-toggle" id="menuToggle">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="logo">
                <i class="fas fa-laptop-code"></i>
                <span>PeopleHub</span>
            </div>
            
            <div class="search-bar">
                <input type="text" placeholder="Buscar salones, computadoras...">
                <button><i class="fas fa-search"></i></button>
            </div>
            
            <div class="user-menu ripple" onclick="confirmLogout()">
                <span><?php echo htmlspecialchars($nombreUsuario); ?></span>
                <div class="user-avatar" data-name="<?php echo htmlspecialchars($nombreUsuario); ?>"></div>
            </div>
        </header>

        <!-- Sección de Inicio -->
        <section id="inicio-section">
            <!-- 📊 Tarjetas Dashboard con las sedes -->
            <section class="dashboard">
                <?php if (!empty($sedes)): ?>
                    <?php foreach ($sedes as $sede): ?>
                    <div class="card floating" onclick="window.location.href='dashboard.php?sede_id=<?php echo $sede['id']; ?>'">
                        <div class="card-header">
                            <div>
                                <h3><?php echo htmlspecialchars($sede['nombre']); ?></h3>
                                <p><?php echo htmlspecialchars($sede['direccion']); ?></p>
                            </div>
                            <i class="fas fa-building"></i>
                        </div>
                        
                        <div class="progress-container">
                            <div class="progress-info">
                                <span>Disponibilidad</span>
                                
                            </div>
                            <div class="progress-bar">
                                <div class="progress"></div>
                            </div>
                        </div>
                        
                        <div class="card-footer">
                            <i class="fas fa-user-tie"></i>
                            <span><?php echo htmlspecialchars($sede['responsable']); ?></span>
                            <button class="delete-btn ripple" onclick="eliminarSede(<?php echo $sede['id']; ?>, event)" 
                                    style="margin-left: auto; padding: 0.5rem 1rem;">
                                <i class="fas fa-trash"></i> Eliminar
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <!-- Tarjeta para agregar nueva sede -->
                    <div class="card" onclick="mostrarModalNuevaSede()" style="background: rgba(108, 92, 231, 0.05); border: 2px dashed rgba(108, 92, 231, 0.3);">
                        <div class="card-header">
                            <div>
                                <h3 style="color: var(--color-primary);">Agregar Nueva Sede</h3>
                                <p style="color: var(--color-primary-light);">Haz clic para registrar una nueva sede</p>
                            </div>
                            <i class="fas fa-plus" style="color: var(--color-primary); opacity: 0.5;"></i>
                        </div>
                        <div style="text-align: center; margin: 2rem 0;">
                            <i class="fas fa-plus-circle" style="font-size: 3rem; color: var(--color-primary-light);"></i>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="no-data-message">
                        <i class="fas fa-building"></i>
                        <h3>No hay sedes registradas</h3>
                        <p>Para comenzar, agrega una nueva sede haciendo clic en el botón inferior</p>
                        <button class="btn-primary ripple" onclick="mostrarModalNuevaSede()" 
        style="margin-top: 1.5rem; 
               cursor: pointer;
               transition: all 0.3s ease;
               transform: scale(1);
               box-shadow: 0 2px 5px rgba(0,0,0,0.1);"
        onmouseover="this.style.transform='scale(1.03)'; this.style.boxShadow='0 4px 8px rgba(0,0,0,0.15)'"
        onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 2px 5px rgba(0,0,0,0.1)'"> Agregar Primera Sede
</button>
                    </div>
                <?php endif; ?>
            </section>

            <!-- 📜 Tabla de Salones o Computadoras -->
            <section class="data-section">
                <div class="table-container">
                    <div class="table-header">
                        <?php if (isset($_GET['sede_id'])): ?>
                        <h2 class="table-title">Salones de la Sede</h2>
                        <div class="table-actions">
                            <button class="btn-primary ripple" id="btnAgregarSalon">
                                <i class="fas fa-plus"></i> Agregar Salón
                            </button>
                        </div>
                        <?php elseif (isset($_GET['salon_id'])): ?>
                        <h2 class="table-title">Computadoras del Salón</h2>
                        <div class="table-actions">
                            <button class="btn-primary ripple" id="btnAgregarComputadora">
                                <i class="fas fa-plus"></i> Agregar Computadora
                            </button>
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
                                <td>
                                    <div class="pc-info">
                                        <i class="fas fa-door-open" style="font-size: 1.8rem; color: var(--color-primary);"></i>
                                        <div class="pc-details">
                                            <strong><?php echo htmlspecialchars($salon['codigo_salon']); ?></strong>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($salon['descripcion']); ?></td>
                                <td><?php echo htmlspecialchars($salon['piso']); ?></td>
                                <td><?php echo htmlspecialchars($salon['capacidad']); ?></td>
                                <td>
                                    <div class="badge" style="background: rgba(108, 92, 231, 0.1); color: var(--color-primary);">
                                        <i class="fas fa-laptop"></i>
                                        <?php echo htmlspecialchars($salon['numero_computadores']); ?>
                                    </div>
                                </td>
                                <td class="actions">
                                    <button class="view-btn ripple" onclick="event.stopPropagation(); verSalon(<?php echo $salon['id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="edit-btn ripple" onclick="event.stopPropagation(); editarSalon(<?php echo $salon['id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="delete-btn ripple" onclick="event.stopPropagation(); eliminarSalon(<?php echo $salon['id']; ?>)">
                                        <i class="fas fa-trash" style="color:#f93b3b;"></i>
                                    </button>
                                </td>
                                
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php elseif (isset($_GET['sede_id']) && empty($salones)): ?>
                    <div style="text-align: center; padding: 3rem 0;">
                        <i class="fas fa-door-open" style="font-size: 4rem; color: rgba(108, 92, 231, 0.2); margin-bottom: 1.5rem;"></i>
                        <h3 style="color: var(--color-dark-light); margin-bottom: 1rem;">No hay salones registrados</h3>
                        <p style="color: var(--color-dark-light); margin-bottom: 2rem;">Agrega un nuevo salón haciendo clic en el botón superior</p>
                        <button class="btn-primary ripple" id="btnAgregarPrimerSalon" style="padding: 0.8rem 1.8rem; cursor: pointer;">
                            <i class="fas fa-plus"></i> Agregar Primer Salón
                        </button>
                    </div>
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

                        </thead>
                        <tbody>
                            <?php foreach ($computadoras as $pc): ?>
                            <tr>
                                <td>
                                    <div class="pc-info">
                                        <i class="fas fa-laptop" style="font-size: 2rem; color: var(--color-primary);"></i>
                                        <div class="pc-details">
                                            <strong><?php echo htmlspecialchars($pc['codigo_patrimonio']); ?></strong>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="pc-info">
                                        <div class="pc-details">
                                            <strong><?php echo htmlspecialchars($pc['marca']); ?></strong>
                                            <p><?php echo htmlspecialchars($pc['modelo']); ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="pc-details">
                                        <p><strong>SO:</strong> <?php echo htmlspecialchars($pc['sistema_operativo']); ?></p>
                                        <p><strong>RAM:</strong> <?php echo htmlspecialchars($pc['ram_gb']); ?>GB</p>
                                        <p><strong>Alm.:</strong> <?php echo htmlspecialchars($pc['almacenamiento_gb']); ?>GB <?php echo htmlspecialchars($pc['tipo_almacenamiento']); ?></p>
                                    </div>
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
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <i class="fas fa-<?php echo $pc['estado'] == 'operativo' ? 'check-circle' : ($pc['estado'] == 'mantenimiento' ? 'tools' : 'exclamation-triangle'); ?>"></i>
                                        <?php echo ucfirst($pc['estado']); ?>
                                    </span>
                                </td>
                               <td><?php echo $pc['ultimo_mantenimiento'] ? htmlspecialchars($pc['ultimo_mantenimiento']) : 'Nunca'; ?></td>
                                <td class="actions">
                                    <button class="view-btn ripple" onclick="verComputadora(<?php echo $pc['id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="edit-btn ripple" onclick="editarComputadora(<?php echo $pc['id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="delete-btn ripple" onclick="eliminarComputadora(<?php echo $pc['id']; ?>)">
                                        <i class="fas fa-trash" style="color:#f93b3b;"></i>
                                    </button>
                                </td>
                        


                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php elseif (isset($_GET['salon_id']) && empty($computadoras)): ?>
                    <div style="text-align: center; padding: 3rem 0;">
                        <i class="fas fa-laptop" style="font-size: 4rem; color: rgba(108, 92, 231, 0.2); margin-bottom: 1.5rem;"></i>
                        <h3 style="color: var(--color-dark-light); margin-bottom: 1rem;">No hay computadoras registradas</h3>
                        <p style="color: var(--color-dark-light); margin-bottom: 2rem;">Agrega una nueva computadora haciendo clic en el botón superior</p>
                        <button class="btn-primary ripple" id="btnAgregarPrimeraComputadora" style="padding: 0.8rem 1.8rem; cursor: pointer;">
                            <i class="fas fa-plus"></i> Agregar Primera Computadora
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </section>
        </section>

        <section class="data-section" id="incidencias-section">
            <div class="table-container">
                    <div class="table-header">
                        <h2 class="table-title">Incidencias Reportadas</h2>
                        <div class="table-actions">
                            <button class="btn-primary ripple" id="btnReportarIncidencia">
                                <i class="fas fa-plus"></i> Reportar Incidencia
                            </button>
                        </div>
                    </div>
                    
                    <table id="tablaIncidencias">
                        <thead>
                            <tr>
                                
                                <th>Computadora</th>
                                <th>Título</th>
                                <th>Reportado por</th>
                                <th>Asignado a</th>
                                <th>Estado</th>
                                <th>Prioridad</th>
                                <th>Fecha Reporte</th>
                                <th>Fecha Asignación</th>
                                <th>Fecha Resolución</th>
                                <th>Acciones</th>

                        </thead>
                        <tbody>
                            <!-- Las incidencias se cargarán aquí mediante JavaScript -->
                        </tbody>
                    </table>
                </div>
        </section>
    
    <!-- Modal para agregar salones -->
    <div class="modal" id="modalSalon">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-laptop-house"></i> Agregar Nuevo Salón</h3>
                <button class="close-modal" id="closeModalSalon">&times;</button>
            </div>
            
            <form id="formSalon" action="guardar_salon.php" method="POST" class="needs-validation" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="sede_id" value="<?php echo isset($_GET['sede_id']) ? $_GET['sede_id'] : ''; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <div class="form-group">
                        <label for="codigo_salon">Código del Salón</label>
                        <input type="text" id="codigo_salon" name="codigo_salon" placeholder="Ej: A101" required>
                        <div class="invalid-feedback">Por favor ingrese el código del salón</div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="piso">Piso</label>
                            <input type="number" id="piso" name="piso" min="1" required>
                            <div class="invalid-feedback">Por favor ingrese el piso</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="capacidad">Capacidad</label>
                            <input type="number" id="capacidad" name="capacidad" min="1" required>
                            <div class="invalid-feedback">Por favor ingrese la capacidad</div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="numero_computadores">Número de Computadoras</label>
                        <input type="number" id="numero_computadores" name="numero_computadores" min="0" required>
                        <div class="invalid-feedback">Por favor ingrese el número de computadoras</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="descripcion">Descripción</label>
                        <textarea id="descripcion" name="descripcion" rows="3" placeholder="Descripción del salón..."></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn-cancel ripple" id="cancelarFormSalon">Cancelar</button>
                    <button type="submit" class="btn-submit ripple">
                        <span class="btn-text">Guardar Salón</span>
                    </button>
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
            
            <form id="formComputadora" action="guardar_computadora.php" method="POST" class="needs-validation" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="salon_id" value="<?php echo isset($_GET['salon_id']) ? $_GET['salon_id'] : ''; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <div class="form-group">
                        <label for="codigo_patrimonio">Código Patrimonial</label>
                        <input type="text" id="codigo_patrimonio" name="codigo_patrimonio" placeholder="Ej: PAT-001" required>
                        <div class="invalid-feedback">Por favor ingrese el código patrimonial</div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="marca">Marca</label>
                            <input type="text" id="marca" name="marca" placeholder="Ej: Dell, HP" required>
                            <div class="invalid-feedback">Por favor ingrese la marca</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="modelo">Modelo</label>
                            <input type="text" id="modelo" name="modelo" placeholder="Ej: OptiPlex 7080" required>
                            <div class="invalid-feedback">Por favor ingrese el modelo</div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="sistema_operativo">Sistema Operativo</label>
                        <input type="text" id="sistema_operativo" name="sistema_operativo" placeholder="Ej: Windows 10 Pro">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="ram_gb">RAM (GB)</label>
                            <input type="number" id="ram_gb" name="ram_gb" min="1" step="1" required>
                            <div class="invalid-feedback">Por favor ingrese la cantidad de RAM</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="almacenamiento_gb">Almacenamiento (GB)</label>
                            <input type="number" id="almacenamiento_gb" name="almacenamiento_gb" min="1" step="1" required>
                            <div class="invalid-feedback">Por favor ingrese el almacenamiento</div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="tipo_almacenamiento">Tipo de Almacenamiento</label>
                        <select id="tipo_almacenamiento" name="tipo_almacenamiento" required>
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
                    <button type="button" class="btn-cancel ripple" id="cancelarFormComputadora">Cancelar</button>
                    <button type="submit" class="btn-submit ripple">
                        <span class="btn-text">Guardar Computadora</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal para agregar nueva sede -->
    <div class="modal" id="modalNuevaSede">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-building"></i> Agregar Nueva Sede</h3>
                <button class="close-modal" id="closeModalNuevaSede">&times;</button>
            </div>
            
            <form id="formNuevaSede" action="guardar_sede.php" method="POST" class="needs-validation" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <div class="form-group">
                        <label for="nombre_sede">Nombre de la Sede</label>
                        <input type="text" id="nombre_sede" name="nombre_sede" placeholder="Ej: Sede Principal" required>
                        <div class="invalid-feedback">Por favor ingrese el nombre de la sede</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="direccion_sede">Dirección</label>
                        <input type="text" id="direccion_sede" name="direccion_sede" placeholder="Ej: Av. Principal 123" required>
                        <div class="invalid-feedback">Por favor ingrese la dirección</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="responsable_sede">Responsable</label>
                        <input type="text" id="responsable_sede" name="responsable_sede" placeholder="Ej: Juan Pérez" required>
                        <div class="invalid-feedback">Por favor ingrese el responsable</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="telefono_sede">Teléfono</label>
                        <input type="text" id="telefono_sede" name="telefono_sede" placeholder="Ej: +51 987654321">
                    </div>
                    
                    <div class="form-group">
                        <label for="descripcion_sede">Descripción</label>
                        <textarea id="descripcion_sede" name="descripcion_sede" rows="3" placeholder="Información adicional sobre la sede..."></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn-cancel ripple" id="cancelarFormNuevaSede">Cancelar</button>
                    <button type="submit" class="btn-submit ripple">
                        <span class="btn-text">Guardar Sede</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
<!-- Modal para reportar/editar incidencias -->
<div class="modal" id="modalIncidencia">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title"><i class="fas fa-exclamation-triangle"></i> <span id="modalIncidenciaTitulo">Reportar Incidencia</span></h3>
            <button class="close-modal" id="closeModalIncidencia">&times;</button>
        </div>
        
        <form id="formIncidencia" action="guardar_incidencia.php" method="POST" class="needs-validation" novalidate>
            <input type="hidden" name="id" id="incidencia_id">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="modal-body">
                <div class="form-group">
                    <label for="sede_incidencia">Sede</label>
                    <select id="sede_incidencia" name="sede_id" required>
                        <option value="">Seleccione una sede</option>
                        <?php foreach ($sedes as $sede): ?>
                            <option value="<?php echo $sede['id']; ?>"><?php echo htmlspecialchars($sede['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="salon_incidencia">Salón</label>
                    <select id="salon_incidencia" name="salon_id" required disabled>
                        <option value="">Seleccione un salón</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="computadora_incidencia">Computadora</label>
                    <select id="computadora_incidencia" name="computador_id" required disabled>
                        <option value="">Seleccione una computadora</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="titulo_incidencia">Título</label>
                    <input type="text" id="titulo_incidencia" name="titulo" placeholder="Ej: No enciende, Problema de red..." required>
                </div>
                
                <div class="form-group">
                    <label for="descripcion_incidencia">Descripción detallada</label>
                    <textarea id="descripcion_incidencia" name="descripcion" rows="4" required></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="prioridad_incidencia">Prioridad</label>
                        <select id="prioridad_incidencia" name="prioridad" required>
                            <option value="baja">Baja</option>
                            <option value="media" selected>Media</option>
                            <option value="alta">Alta</option>
                            <option value="critica">Crítica</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="estado_incidencia">Estado</label>
                        <select id="estado_incidencia" name="estado" required>
                            <option value="pendiente">Pendiente</option>
                            <option value="asignado">Asignado</option>
                            <option value="en_proceso">En Proceso</option>
                            <option value="resuelto">Resuelto</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="asignado_nombre_incidencia">Nombre de la persona asignada</label>
                    <input type="text" id="asignado_nombre_incidencia" name="asignado_nombre" placeholder="Nombre del técnico">
                </div>
                
                <div class="form-group">
                    <label for="solucion_incidencia">Solución (si aplica)</label>
                    <textarea id="solucion_incidencia" name="solucion" rows="3" placeholder="Describa la solución aplicada..."></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="fecha_reporte_incidencia">Fecha de Reporte</label>
                        <input type="date" id="fecha_reporte_incidencia" name="fecha_reporte">
                    </div>
                    
                    <div class="form-group">
                        <label for="fecha_asignacion_incidencia">Fecha de Asignación</label>
                        <input type="date" id="fecha_asignacion_incidencia" name="fecha_asignacion">
                    </div>
                    
                    <div class="form-group">
                        <label for="fecha_resolucion_incidencia">Fecha de Resolución</label>
                        <input type="date" id="fecha_resolucion_incidencia" name="fecha_resolucion">
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn-cancel ripple" id="cancelarFormIncidencia">Cancelar</button>
                <button type="submit" class="btn-submit ripple">
                    <span class="btn-text" id="btnSubmitIncidencia">Reportar Incidencia</span>
                </button>
            </div>
        </form>
    </div>
</div>
    
   <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Función para generar avatares con iniciales
    function generarAvatares() {
        document.querySelectorAll('.user-avatar').forEach(avatar => {
            const nombre = avatar.getAttribute('data-name') || '';
            const partesNombre = nombre.trim().split(/\s+/);
            
            let iniciales = '';
            if (partesNombre.length >= 2) {
                iniciales = partesNombre[0].charAt(0).toUpperCase() + partesNombre[1].charAt(0).toUpperCase();
            } else if (partesNombre.length === 1) {
                iniciales = partesNombre[0].charAt(0).toUpperCase();
            }
            
            avatar.textContent = iniciales;
            avatar.setAttribute('data-initial', iniciales.charAt(0));
        });
    }

    // Efecto ripple para botones
    function setupRippleEffects() {
        document.querySelectorAll('.ripple').forEach(button => {
            button.addEventListener('click', function(e) {
                const x = e.clientX - e.target.getBoundingClientRect().left;
                const y = e.clientY - e.target.getBoundingClientRect().top;
                
                const ripple = document.createElement('span');
                ripple.classList.add('ripple-effect');
                ripple.style.left = `${x}px`;
                ripple.style.top = `${y}px`;
                
                this.appendChild(ripple);
                
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        });
    }

    // Animaciones cuando los elementos son visibles
    function setupAnimations() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate__animated', 'animate__fadeInUp');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });
        
        document.querySelectorAll('.card, .table-container').forEach(el => {
            observer.observe(el);
        });
        
        // Animación para las tarjetas de sedes
        document.querySelectorAll('.card').forEach((card, index) => {
            card.style.animationDelay = `${index * 0.1}s`;
        });
    }

    // Validación de formularios
    function setupFormValidation() {
        document.querySelectorAll('.needs-validation').forEach(form => {
            form.addEventListener('submit', function(event) {
                if (!this.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                    this.classList.add('was-validated');
                    
                    // Enfocar el primer campo inválido
                    const invalidField = this.querySelector(':invalid');
                    if (invalidField) {
                        invalidField.focus();
                    }
                    
                    return false;
                }
                
                const formData = new FormData(this);
                const action = this.getAttribute('action');
                
                Swal.fire({
                    title: 'Guardando...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                        
                        fetch(action, {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Error en la respuesta del servidor');
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    title: '¡Éxito!',
                                    text: data.message,
                                    icon: 'success',
                                    confirmButtonText: 'Aceptar'
                                }).then(() => {
                                    // Cerrar el modal si existe
                                    const modal = this.closest('.modal');
                                    if (modal) {
                                        modal.classList.remove('show');
                                        document.body.style.overflow = 'auto';
                                    }
                                    window.location.reload();
                                });
                            } else {
                                throw new Error(data.message || 'Error al guardar los datos');
                            }
                        })
                        .catch(error => {
                            Swal.fire({
                                title: 'Error',
                                text: error.message,
                                icon: 'error',
                                confirmButtonText: 'Entendido'
                            });
                        });
                    }
                });
                
                event.preventDefault();
            }, false);
        });
    }

    // Toggle del menú en móvil
    function setupMobileMenu() {
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });
    }

    // Función de búsqueda
    function setupSearch() {
        document.querySelector('.search-bar button').addEventListener('click', buscar);
        document.querySelector('.search-bar input').addEventListener('keyup', function(e) {
            if (e.key === 'Enter') buscar();
        });

        function buscar() {
            const termino = document.querySelector('.search-bar input').value.toLowerCase();
            const filas = document.querySelectorAll('tbody tr');
            
            filas.forEach(fila => {
                const textoFila = fila.textContent.toLowerCase();
                fila.style.display = textoFila.includes(termino) ? '' : 'none';
            });
        }
    }

    // Cambiar ítem activo del menú y mostrar sección correspondiente
    function setupMenuNavigation() {
        const menuItems = document.querySelectorAll('.menu-item');
        
        menuItems.forEach(item => {
            item.addEventListener('click', function() {
                // Quitar clase active de todos los items
                menuItems.forEach(i => i.classList.remove('active'));
                
                // Añadir clase active al item clickeado
                this.classList.add('active');
                
                // Obtener la sección a mostrar
                const section = this.getAttribute('data-section');
                
                // Ocultar todas las secciones
                document.querySelectorAll('#inicio-section, #incidencias-section').forEach(sec => {
                    sec.style.display = 'none';
                });
                
                // Mostrar la sección correspondiente
                if (section === 'inicio') {
                    document.getElementById('inicio-section').style.display = 'block';
                } else if (section === 'incidencias') {
                    document.getElementById('incidencias-section').style.display = 'block';
                    cargarIncidencias();
                }
            });
        });
    }

    // Función para cerrar sesión
    function setupLogout() {
        window.confirmLogout = function() {
            Swal.fire({
                title: 'Cerrar Sesión',
                html: '¿Estás seguro de que deseas salir de tu cuenta?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-sign-out-alt"></i> Cerrar Sesión',
                cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
                showClass: {
                    popup: 'animate__animated animate__fadeInDown animate__faster'
                },
                hideClass: {
                    popup: 'animate__animated animate__fadeOutUp animate__faster'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Cerrando sesión...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    setTimeout(() => {
                        window.location.href = 'logout.php';
                    }, 800);
                }
            });
        }
    }

    // Manejo de modales
    function setupModals() {
        // Modal para salones
        const modalSalon = document.getElementById('modalSalon');
        const btnAgregarSalon = document.getElementById('btnAgregarSalon');
        const btnAgregarPrimerSalon = document.getElementById('btnAgregarPrimerSalon');
        const closeModalSalon = document.getElementById('closeModalSalon');
        const cancelarFormSalon = document.getElementById('cancelarFormSalon');

        window.mostrarModalSalon = function() {
            modalSalon.classList.add('show');
            document.body.style.overflow = 'hidden';
            
            // Resetear el formulario
            const form = document.getElementById('formSalon');
            form.reset();
            form.classList.remove('was-validated');
        }

        if (btnAgregarSalon) {
            btnAgregarSalon.addEventListener('click', mostrarModalSalon);
        }

        if (btnAgregarPrimerSalon) {
            btnAgregarPrimerSalon.addEventListener('click', mostrarModalSalon);
        }

        closeModalSalon.addEventListener('click', () => {
            modalSalon.classList.remove('show');
            document.body.style.overflow = 'auto';
        });

        cancelarFormSalon.addEventListener('click', () => {
            modalSalon.classList.remove('show');
            document.body.style.overflow = 'auto';
        });

        modalSalon.addEventListener('click', (e) => {
            if (e.target === modalSalon) {
                modalSalon.classList.remove('show');
                document.body.style.overflow = 'auto';
            }
        });

        // Modal para computadoras
        const modalComputadora = document.getElementById('modalComputadora');
        const btnAgregarComputadora = document.getElementById('btnAgregarComputadora');
        const btnAgregarPrimeraComputadora = document.getElementById('btnAgregarPrimeraComputadora');
        const closeModalComputadora = document.getElementById('closeModalComputadora');
        const cancelarFormComputadora = document.getElementById('cancelarFormComputadora');

        window.mostrarModalComputadora = function() {
            modalComputadora.classList.add('show');
            document.body.style.overflow = 'hidden';
            
            // Resetear el formulario
            const form = document.getElementById('formComputadora');
            form.reset();
            form.classList.remove('was-validated');
        }

        if (btnAgregarComputadora) {
            btnAgregarComputadora.addEventListener('click', mostrarModalComputadora);
        }

        if (btnAgregarPrimeraComputadora) {
            btnAgregarPrimeraComputadora.addEventListener('click', mostrarModalComputadora);
        }

        closeModalComputadora.addEventListener('click', () => {
            modalComputadora.classList.remove('show');
            document.body.style.overflow = 'auto';
        });

        cancelarFormComputadora.addEventListener('click', () => {
            modalComputadora.classList.remove('show');
            document.body.style.overflow = 'auto';
        });

        modalComputadora.addEventListener('click', (e) => {
            if (e.target === modalComputadora) {
                modalComputadora.classList.remove('show');
                document.body.style.overflow = 'auto';
            }
        });

        // Modal para nueva sede
        const modalNuevaSede = document.getElementById('modalNuevaSede');
        const closeModalNuevaSede = document.getElementById('closeModalNuevaSede');
        const cancelarFormNuevaSede = document.getElementById('cancelarFormNuevaSede');

        window.mostrarModalNuevaSede = function() {
            modalNuevaSede.classList.add('show');
            document.body.style.overflow = 'hidden';
            
            // Resetear el formulario
            const form = document.getElementById('formNuevaSede');
            form.reset();
            form.classList.remove('was-validated');
        }

        closeModalNuevaSede.addEventListener('click', () => {
            modalNuevaSede.classList.remove('show');
            document.body.style.overflow = 'auto';
        });

        cancelarFormNuevaSede.addEventListener('click', () => {
            modalNuevaSede.classList.remove('show');
            document.body.style.overflow = 'auto';
        });

        modalNuevaSede.addEventListener('click', (e) => {
            if (e.target === modalNuevaSede) {
                modalNuevaSede.classList.remove('show');
                document.body.style.overflow = 'auto';
            }
        });

        // Modal para incidencias
        const modalIncidencia = document.getElementById('modalIncidencia');
        const btnReportarIncidencia = document.getElementById('btnReportarIncidencia');
        const closeModalIncidencia = document.getElementById('closeModalIncidencia');
        const cancelarFormIncidencia = document.getElementById('cancelarFormIncidencia');

        window.mostrarModalIncidencia = function() {
            modalIncidencia.classList.add('show');
            document.body.style.overflow = 'hidden';
            
            // Resetear el formulario
            const form = document.getElementById('formIncidencia');
            form.reset();
            form.classList.remove('was-validated');
        };

        if (btnReportarIncidencia) {
            btnReportarIncidencia.addEventListener('click', mostrarModalIncidencia);
        }

        closeModalIncidencia.addEventListener('click', () => {
            modalIncidencia.classList.remove('show');
            document.body.style.overflow = 'auto';
        });

        cancelarFormIncidencia.addEventListener('click', () => {
            modalIncidencia.classList.remove('show');
            document.body.style.overflow = 'auto';
        });

        modalIncidencia.addEventListener('click', (e) => {
            if (e.target === modalIncidencia) {
                modalIncidencia.classList.remove('show');
                document.body.style.overflow = 'auto';
            }
        });
    }

    // Funciones para CRUD de salones
    function setupSalonesFunctions() {
       window.verSalon = function(id) {
    fetch(`obtener_salon.php?id=${id}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Error al obtener los datos del salón');
            }
            return response.json();
        })
        .then(salon => {
            // Crear el HTML con diseño centrado y mejorado
            let htmlContent = `
                <div class="pc-detail-container">
                    <div class="pc-header">
                        <i class="fas fa-door-open pc-icon"></i>
                        <div class="pc-title">
                            <h3>${salon.codigo_salon}</h3>
                            <p class="pc-subtitle">Piso ${salon.piso} - ${salon.capacidad} personas</p>
                        </div>
                    </div>
                    
                    <div class="pc-detail-grid">
                        <div class="pc-detail-section">
                            <h4 class="section-title"><i class="fas fa-info-circle"></i> Información Básica</h4>
                            <div class="detail-item">
                                <span class="detail-label">Código:</span>
                                <span class="detail-value">${salon.codigo_salon}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Piso:</span>
                                <span class="detail-value">${salon.piso}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Capacidad:</span>
                                <span class="detail-value">${salon.capacidad} personas</span>
                            </div>
                        </div>
                        
                        <div class="pc-detail-section">
                            <h4 class="section-title"><i class="fas fa-laptop"></i> Equipamiento</h4>
                            <div class="detail-item">
                                <span class="detail-label">Computadoras:</span>
                                <span class="detail-value">
                                    <span class="badge" style="background: rgba(108, 92, 231, 0.1); color: var(--color-primary);">
                                        <i class="fas fa-laptop"></i> 
                                        ${salon.numero_computadores}
                                    </span>
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Estado:</span>
                                <span class="detail-value">
                                    <span class="badge badge-success">
                                        <i class="fas fa-check-circle"></i> 
                                        Activo
                                    </span>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="pc-observations">
                        <h4 class="section-title"><i class="fas fa-clipboard"></i> Descripción</h4>
                        <div class="observations-content">${salon.descripcion || 'No hay descripción disponible'}</div>
                    </div>
                </div>
            `;

            Swal.fire({
                title: 'Detalles del Salón',
                html: htmlContent,
                icon: 'info',
                confirmButtonText: 'Cerrar',
                width: '800px',
                customClass: {
                    popup: 'pc-detail-popup',
                    container: 'pc-detail-container'
                }
            });
        })
        .catch(error => {
            Swal.fire({
                title: 'Error',
                text: error.message,
                icon: 'error',
                confirmButtonText: 'Entendido'
            });
        });
};

        window.editarSalon = function(id) {
            fetch(`obtener_salon.php?id=${id}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Error al obtener los datos del salón');
                    }
                    return response.json();
                })
                .then(salon => {
                    // Clonar el formulario de agregar
                    const modal = document.getElementById('modalSalon');
                    const clone = modal.cloneNode(true);
                    clone.id = 'modalEditarSalon';
                    
                    // Cambiar título
                    clone.querySelector('.modal-title').innerHTML = '<i class="fas fa-edit"></i> Editar Salón';
                    
                    // Llenar formulario con datos
                    const form = clone.querySelector('form');
                    form.action = 'actualizar_salon.php';
                    form.querySelector('[name="codigo_salon"]').value = salon.codigo_salon;
                    form.querySelector('[name="piso"]').value = salon.piso;
                    form.querySelector('[name="capacidad"]').value = salon.capacidad;
                    form.querySelector('[name="numero_computadores"]').value = salon.numero_computadores;
                    form.querySelector('[name="descripcion"]').value = salon.descripcion || '';
                    
                    // Agregar campo oculto con el ID
                    const inputId = document.createElement('input');
                    inputId.type = 'hidden';
                    inputId.name = 'id';
                    inputId.value = id;
                    form.appendChild(inputId);
                    
                    // Mostrar modal
                    document.body.appendChild(clone);
                    clone.classList.add('show');
                    document.body.style.overflow = 'hidden';
                    
                    // Manejar cierre del modal
                    clone.querySelector('.close-modal').addEventListener('click', () => {
                        clone.classList.remove('show');
                        document.body.style.overflow = 'auto';
                        setTimeout(() => document.body.removeChild(clone), 300);
                    });
                    
                    clone.querySelector('.btn-cancel').addEventListener('click', () => {
                        clone.classList.remove('show');
                        document.body.style.overflow = 'auto';
                        setTimeout(() => document.body.removeChild(clone), 300);
                    });
                    
                    clone.addEventListener('click', (e) => {
                        if (e.target === clone) {
                            clone.classList.remove('show');
                            document.body.style.overflow = 'auto';
                            setTimeout(() => document.body.removeChild(clone), 300);
                        }
                    });
                    
                    // Validación del formulario
                    form.classList.add('needs-validation');
                    form.addEventListener('submit', function(e) {
                        if (!this.checkValidity()) {
                            e.preventDefault();
                            e.stopPropagation();
                            this.classList.add('was-validated');
                            return false;
                        }
                        
                        const formData = new FormData(this);
                        
                        Swal.fire({
                            title: 'Actualizando salón...',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                                
                                fetch(this.action, {
                                    method: 'POST',
                                    body: formData
                                })
                                .then(response => {
                                    if (!response.ok) {
                                        throw new Error('Error en la respuesta del servidor');
                                    }
                                    return response.json();
                                })
                                .then(data => {
                                    if (data.success) {
                                        Swal.fire({
                                            title: '¡Éxito!',
                                            text: data.message,
                                            icon: 'success'
                                        }).then(() => {
                                            window.location.reload();
                                        });
                                    } else {
                                        throw new Error(data.message || 'Error al actualizar el salón');
                                    }
                                })
                                .catch(error => {
                                    Swal.fire('Error', error.message, 'error');
                                });
                            }
                        });
                        
                        e.preventDefault();
                    }, false);
                })
                .catch(error => {
                    Swal.fire('Error', error.message, 'error');
                });
        };

        window.eliminarSalon = function(id) {
            Swal.fire({
                title: '¿Eliminar Salón?',
                html: '¿Estás seguro de que deseas eliminar este salón?<br><b>Esta acción también eliminará todas las computadoras asociadas.</b>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#d63031'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Eliminando...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                            
                            fetch('eliminar_salon.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: `id=${id}&csrf_token=<?php echo $_SESSION['csrf_token']; ?>`
                            })
                            .then(response => {
                                if (!response.ok) {
                                    throw new Error('Error en la respuesta del servidor');
                                }
                                return response.json();
                            })
                            .then(data => {
                                if (data.success) {
                                    Swal.fire({
                                        title: '¡Eliminado!',
                                        text: data.message,
                                        icon: 'success'
                                    }).then(() => {
                                        window.location.reload();
                                    });
                                } else {
                                    throw new Error(data.message || 'Error al eliminar el salón');
                                }
                            })
                            .catch(error => {
                                Swal.fire('Error', error.message, 'error');
                            });
                        }
                    });
                }
            });
        };
    }

    // Funciones para CRUD de sedes
    function setupSedesFunctions() {
        window.eliminarSede = function(id, event) {
            event.stopPropagation();
            
            Swal.fire({
                title: '¿Eliminar Sede?',
                html: '¿Estás seguro de que deseas eliminar esta sede?<br><b>Esta acción también eliminará todos los salones y computadoras asociadas.</b>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#d63031'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Eliminando...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                            
                            fetch('eliminar_sede.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: `id=${id}&csrf_token=<?php echo $_SESSION['csrf_token']; ?>`
                            })
                            .then(response => {
                                if (!response.ok) {
                                    throw new Error('Error en la respuesta del servidor');
                                }
                                return response.json();
                            })
                            .then(data => {
                                if (data.success) {
                                    Swal.fire({
                                        title: '¡Eliminada!',
                                        text: data.message,
                                        icon: 'success'
                                    }).then(() => {
                                        window.location.reload();
                                    });
                                } else {
                                    throw new Error(data.message || 'Error al eliminar la sede');
                                }
                            })
                            .catch(error => {
                                Swal.fire('Error', error.message, 'error');
                            });
                        }
                    });
                }
            });
        };
    }

    // Funciones para CRUD de computadoras
    function setupComputadorasFunctions() {
     window.verComputadora = function(id) {
    fetch(`obtener_computadora.php?id=${id}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Error al obtener los datos de la computadora');
            }
            return response.json();
        })
        .then(pcData => {
            // Determinar el color del badge según el estado
            let badgeClass, badgeIcon;
            switch(pcData.estado) {
                case 'operativo':
                    badgeClass = 'badge-success';
                    badgeIcon = 'fa-check-circle';
                    break;
                case 'mantenimiento':
                    badgeClass = 'badge-warning';
                    badgeIcon = 'fa-tools';
                    break;
                default:
                    badgeClass = 'badge-danger';
                    badgeIcon = 'fa-exclamation-triangle';
            }

            // Formatear las fechas
            const formatDate = (dateStr) => {
                return dateStr ? new Date(dateStr).toLocaleDateString('es-ES') : 'N/A';
            };

            // Crear el HTML con diseño centrado y mejorado
            let htmlContent = `
                <div class="pc-detail-container">
                    <div class="pc-header">
                        <i class="fas fa-laptop pc-icon"></i>
                        <div class="pc-title">
                            <h3>${pcData.codigo_patrimonio}</h3>
                            <p class="pc-subtitle">${pcData.marca} ${pcData.modelo}</p>
                        </div>
                    </div>
                    
                    <div class="pc-detail-grid">
                        <div class="pc-detail-section">
                            <h4 class="section-title"><i class="fas fa-microchip"></i> Especificaciones</h4>
                            <div class="detail-item">
                                <span class="detail-label">Sistema Operativo:</span>
                                <span class="detail-value">${pcData.sistema_operativo || 'N/A'}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">RAM:</span>
                                <span class="detail-value">${pcData.ram_gb} GB</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Almacenamiento:</span>
                                <span class="detail-value">${pcData.almacenamiento_gb} GB ${pcData.tipo_almacenamiento}</span>
                            </div>
                        </div>
                        
                        <div class="pc-detail-section">
                            <h4 class="section-title"><i class="fas fa-info-circle"></i> Estado</h4>
                            <div class="detail-item">
                                <span class="detail-label">Estado:</span>
                                <span class="detail-value">
                                    <span class="badge ${badgeClass}">
                                        <i class="fas ${badgeIcon}"></i> 
                                        ${pcData.estado.charAt(0).toUpperCase() + pcData.estado.slice(1)}
                                    </span>
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Instalación:</span>
                                <span class="detail-value">${formatDate(pcData.fecha_instalacion)}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Últ. Mantenimiento:</span>
                                <span class="detail-value">${formatDate(pcData.ultimo_mantenimiento)}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="pc-observations">
                        <h4 class="section-title"><i class="fas fa-clipboard"></i> Observaciones</h4>
                        <div class="observations-content">${pcData.observaciones || 'No hay observaciones registradas'}</div>
                    </div>
                </div>
            `;

            Swal.fire({
                title: 'Detalles de la Computadora',
                html: htmlContent,
                icon: 'info',
                confirmButtonText: 'Cerrar',
                width: '800px',
                customClass: {
                    popup: 'pc-detail-popup',
                    container: 'pc-detail-container'
                }
            });
        })
        .catch(error => {
            Swal.fire({
                title: 'Error',
                text: error.message,
                icon: 'error',
                confirmButtonText: 'Entendido'
            });
        });
};

        window.editarComputadora = function(id) {
            fetch(`obtener_computadora.php?id=${id}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Error al obtener los datos de la computadora');
                    }
                    return response.json();
                })
                .then(pcData => {
                    // Clonar el formulario de agregar
                    const modal = document.getElementById('modalComputadora');
                    const clone = modal.cloneNode(true);
                    clone.id = 'modalEditarComputadora';
                    
                    // Cambiar título
                    clone.querySelector('.modal-title').innerHTML = '<i class="fas fa-edit"></i> Editar Computadora';
                    
                    // Llenar formulario con datos reales
                    const form = clone.querySelector('form');
                    form.action = 'actualizar_computadora.php';
                    form.querySelector('[name="codigo_patrimonio"]').value = pcData.codigo_patrimonio;
                    form.querySelector('[name="marca"]').value = pcData.marca;
                    form.querySelector('[name="modelo"]').value = pcData.modelo;
                    form.querySelector('[name="sistema_operativo"]').value = pcData.sistema_operativo;
                    form.querySelector('[name="ram_gb"]').value = pcData.ram_gb;
                    form.querySelector('[name="almacenamiento_gb"]').value = pcData.almacenamiento_gb;
                    form.querySelector('[name="tipo_almacenamiento"]').value = pcData.tipo_almacenamiento;
                    form.querySelector('[name="estado"]').value = pcData.estado;
                    form.querySelector('[name="fecha_instalacion"]').value = pcData.fecha_instalacion || '';
                    form.querySelector('[name="ultimo_mantenimiento"]').value = pcData.ultimo_mantenimiento || '';
                    form.querySelector('[name="observaciones"]').value = pcData.observaciones || '';
                    
                    // Agregar campo oculto con el ID
                    const inputId = document.createElement('input');
                    inputId.type = 'hidden';
                    inputId.name = 'id';
                    inputId.value = id;
                    form.appendChild(inputId);
                    
                    // Mostrar modal
                    document.body.appendChild(clone);
                    clone.classList.add('show');
                    document.body.style.overflow = 'hidden';
                    
                    // Manejar cierre del modal
                    clone.querySelector('.close-modal').addEventListener('click', () => {
                        clone.classList.remove('show');
                        document.body.style.overflow = 'auto';
                        setTimeout(() => document.body.removeChild(clone), 300);
                    });
                    
                    clone.querySelector('.btn-cancel').addEventListener('click', () => {
                        clone.classList.remove('show');
                        document.body.style.overflow = 'auto';
                        setTimeout(() => document.body.removeChild(clone), 300);
                    });
                    
                    clone.addEventListener('click', (e) => {
                        if (e.target === clone) {
                            clone.classList.remove('show');
                            document.body.style.overflow = 'auto';
                            setTimeout(() => document.body.removeChild(clone), 300);
                        }
                    });
                    
                    // Manejar envío del formulario
                    form.addEventListener('submit', function(e) {
                        e.preventDefault();
                        
                        const formData = new FormData(this);
                        
                        Swal.fire({
                            title: 'Actualizando computadora...',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                                
                                fetch(this.action, {
                                    method: 'POST',
                                    body: formData
                                })
                                .then(response => {
                                    if (!response.ok) {
                                        throw new Error('Error en la respuesta del servidor');
                                    }
                                    return response.json();
                                })
                                .then(data => {
                                    if (data.success) {
                                        Swal.fire({
                                            title: '¡Actualizado!',
                                            text: data.message,
                                            icon: 'success'
                                        }).then(() => {
                                            window.location.reload();
                                        });
                                    } else {
                                        throw new Error(data.message || 'Error al actualizar la computadora');
                                    }
                                })
                                .catch(error => {
                                    Swal.fire('Error', error.message, 'error');
                                });
                            }
                        });
                    });
                })
                .catch(error => {
                    Swal.fire('Error', error.message, 'error');
                });
        };

        window.eliminarComputadora = function(id) {
            Swal.fire({
                title: '¿Eliminar Computadora?',
                text: '¿Estás seguro de que deseas eliminar esta computadora?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#d63031'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Eliminando...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                            
                            fetch('eliminar_computadora.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: `id=${id}&csrf_token=<?php echo $_SESSION['csrf_token']; ?>`
                            })
                            .then(response => {
                                if (!response.ok) {
                                    throw new Error('Error en la respuesta del servidor');
                                }
                                return response.json();
                            })
                            .then(data => {
                                if (data.success) {
                                    Swal.fire({
                                        title: '¡Eliminada!',
                                        text: data.message,
                                        icon: 'success'
                                    }).then(() => {
                                        window.location.reload();
                                    });
                                } else {
                                    throw new Error(data.message || 'Error al eliminar la computadora');
                                }
                            })
                            .catch(error => {
                                Swal.fire('Error', error.message, 'error');
                            });
                        }
                    });
                }
            });
        };
    }

    // Funciones para manejar incidencias
function setupIncidenciasFunctions() {
    // Cargar incidencias desde la base de datos
window.cargarIncidencias = function() {
    const tbody = document.querySelector('#tablaIncidencias tbody');
    tbody.innerHTML = '<tr><td colspan="10" style="text-align: center;"><div class="loader"></div></td></tr>';

    fetch('obtener_incidencias.php')
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            tbody.innerHTML = '';
            
            if (!data || data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="10" style="text-align: center;">No hay incidencias registradas</td></tr>';
                return;
            }

            const formatDate = (dateString) => {
                if (!dateString) return 'N/A';
                const date = new Date(dateString + 'T00:00:00');
                const day = String(date.getDate()).padStart(2, '0');
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const year = date.getFullYear();
                return `${day}/${month}/${year}`;
            };

            const translateEstado = (estado) => {
                const estados = {
                    'pendiente': 'Pendiente',
                    'asignado': 'Asignado',
                    'en_proceso': 'En Proceso',
                    'resuelto': 'Resuelto'
                };
                return estados[estado] || estado;
            };

            const translatePrioridad = (prioridad) => {
                const prioridades = {
                    'baja': 'Baja',
                    'media': 'Media',
                    'alta': 'Alta',
                    'critica': 'Crítica'
                };
                return prioridades[prioridad] || prioridad;
            };

            data.forEach(incidencia => {
                const tr = document.createElement('tr');
                
                let estadoClass = '';
                switch(incidencia.estado) {
                    case 'pendiente': estadoClass = 'badge-danger'; break;
                    case 'asignado': estadoClass = 'badge-info'; break;
                    case 'en_proceso': estadoClass = 'badge-warning'; break;
                    case 'resuelto': estadoClass = 'badge-success'; break;
                    default: estadoClass = 'badge-secondary';
                }
                
                let prioridadClass = '';
                switch(incidencia.prioridad) {
                    case 'baja': prioridadClass = 'badge-success'; break;
                    case 'media': prioridadClass = 'badge-info'; break;
                    case 'alta': prioridadClass = 'badge-warning'; break;
                    case 'critica': prioridadClass = 'badge-danger'; break;
                    default: prioridadClass = 'badge-secondary';
                }

                tr.innerHTML = `
                    <td>${incidencia.computadora_codigo || 'N/A'}</td>
                    <td>${incidencia.titulo}</td>
                    <td>${incidencia.reportador_nombre || 'N/A'}</td>
                    <td>${incidencia.asignado_nombre || 'N/A'}</td>
                    <td><span class="badge ${estadoClass}">${translateEstado(incidencia.estado)}</span></td>
                    <td><span class="badge ${prioridadClass}">${translatePrioridad(incidencia.prioridad)}</span></td>
                    <td>${formatDate(incidencia.fecha_reporte)}</td>
                    <td>${formatDate(incidencia.fecha_asignacion)}</td>
                    <td>${formatDate(incidencia.fecha_resolucion)}</td>
                    <td class="actions">
                        <button class="view-btn ripple" onclick="verIncidencia(${incidencia.id})" title="Ver detalles">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="edit-btn ripple" onclick="editarIncidencia(${incidencia.id})" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="delete-btn ripple" onclick="eliminarIncidencia(${incidencia.id}, event)" title="Eliminar">
                            <i class="fas fa-trash" style="color:#f93b3b;"></i>
                        </button>
                    </td>
                `;
                
                tbody.appendChild(tr);
            });
        })
        .catch(error => {
            console.error('Error al cargar incidencias:', error);
            tbody.innerHTML = `
                <tr>
                    <td colspan="10" style="text-align: center; color: red;">
                        Error al cargar incidencias: ${error.message}
                        <br><br>
                        <button onclick="cargarIncidencias()" class="btn-retry ripple">
                            <i class="fas fa-sync-alt"></i> Reintentar
                        </button>
                    </td>
                </tr>
            `;
        });
};

    
   window.verIncidencia = function(id) {
    fetch(`obtener_incidencia.php?id=${id}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Error al obtener los datos de la incidencia');
            }
            return response.json();
        })
        .then(incidencia => {
            // Badge para estado
            let estadoClass, estadoIcon;
            switch (incidencia.estado) {
                case 'pendiente':
                    estadoClass = 'badge-danger';
                    estadoIcon = 'fa-hourglass-start';
                    break;
                case 'asignado':
                    estadoClass = 'badge-info';
                    estadoIcon = 'fa-user-check';
                    break;
                case 'en_proceso':
                    estadoClass = 'badge-warning';
                    estadoIcon = 'fa-spinner';
                    break;
                case 'resuelto':
                    estadoClass = 'badge-success';
                    estadoIcon = 'fa-check-circle';
                    break;
                default:
                    estadoClass = 'badge-secondary';
                    estadoIcon = 'fa-question-circle';
            }

            // Badge para prioridad
            let prioridadClass, prioridadIcon;
            switch (incidencia.prioridad) {
                case 'baja':
                    prioridadClass = 'badge-success';
                    prioridadIcon = 'fa-arrow-down';
                    break;
                case 'media':
                    prioridadClass = 'badge-info';
                    prioridadIcon = 'fa-arrow-right';
                    break;
                case 'alta':
                    prioridadClass = 'badge-warning';
                    prioridadIcon = 'fa-arrow-up';
                    break;
                case 'critica':
                    prioridadClass = 'badge-danger';
                    prioridadIcon = 'fa-exclamation-triangle';
                    break;
                default:
                    prioridadClass = 'badge-secondary';
                    prioridadIcon = 'fa-question-circle';
            }

            // Formatear fechas
            const formatDate = (dateStr) => {
                if (!dateStr) return 'N/A';
                
                const dateObj = new Date(dateStr.includes(' ') ? dateStr : dateStr + 'T00:00:00');
                const hasTime = dateStr.includes(' ') 
                    ? dateStr.split(' ')[1] !== '00:00:00'
                    : false;
                
                return dateObj.toLocaleDateString('es-ES', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric',
                    ...(hasTime && {
                        hour: '2-digit',
                        minute: '2-digit'
                    })
                });
            };

            // HTML con diseño similar a verComputadora
            let htmlContent = `
                <div class="pc-detail-container">
                    <div class="pc-header">
                        <i class="fas fa-exclamation-triangle pc-icon" style="color: #e74c3c;"></i>
                        <div class="pc-title">
                            <h3>Incidencia #${incidencia.id}</h3>
                            <p class="pc-subtitle">${incidencia.titulo}</p>
                        </div>
                    </div>
                    
                    <div class="pc-detail-grid">
                        <div class="pc-detail-section">
                            <h4 class="section-title"><i class="fas fa-info-circle"></i> Información Básica</h4>
                            <div class="detail-item">
                                <span class="detail-label">Computadora:</span>
                                <span class="detail-value">${incidencia.computadora_codigo || 'N/A'}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Reportado por:</span>
                                <span class="detail-value">${incidencia.reportador_nombre || 'N/A'}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Asignado a:</span>
                                <span class="detail-value">${incidencia.asignado_nombre || 'No asignado'}</span>
                            </div>
                        </div>
                        
                        <div class="pc-detail-section">
                            <h4 class="section-title"><i class="fas fa-chart-line"></i> Estado y Prioridad</h4>
                            <div class="detail-item">
                                <span class="detail-label">Estado:</span>
                                <span class="detail-value">
                                    <span class="badge ${estadoClass}">
                                        <i class="fas ${estadoIcon}"></i> 
                                        ${incidencia.estado.charAt(0).toUpperCase() + incidencia.estado.slice(1).replace('_', ' ')}
                                    </span>
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Prioridad:</span>
                                <span class="detail-value">
                                    <span class="badge ${prioridadClass}">
                                        <i class="fas ${prioridadIcon}"></i> 
                                        ${incidencia.prioridad.charAt(0).toUpperCase() + incidencia.prioridad.slice(1)}
                                    </span>
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Fecha Reporte:</span>
                                <span class="detail-value">${formatDate(incidencia.fecha_reporte)}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="pc-observations">
                        <h4 class="section-title"><i class="fas fa-align-left"></i> Descripción</h4>
                        <div class="observations-content">${incidencia.descripcion || 'No hay descripción disponible'}</div>
                    </div>
                    
                    ${incidencia.solucion ? `
                    <div class="pc-observations">
                        <h4 class="section-title"><i class="fas fa-check-circle"></i> Solución</h4>
                        <div class="observations-content">${incidencia.solucion}</div>
                    </div>
                    ` : ''}
                    
                    <div class="pc-detail-grid">
                        <div class="pc-detail-section">
                            <h4 class="section-title"><i class="fas fa-calendar-alt"></i> Fechas</h4>
                            <div class="detail-item">
                                <span class="detail-label">Asignación:</span>
                                <span class="detail-value">${formatDate(incidencia.fecha_asignacion)}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Resolución:</span>
                                <span class="detail-value">${formatDate(incidencia.fecha_resolucion)}</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            Swal.fire({
                title: 'Detalles de la Incidencia',
                html: htmlContent,
                icon: 'info',
                confirmButtonText: 'Cerrar',
                width: '800px',
                customClass: {
                    popup: 'pc-detail-popup',
                    container: 'pc-detail-container'
                }
            });
        })
        .catch(error => {
            Swal.fire({
                title: 'Error',
                text: error.message,
                icon: 'error',
                confirmButtonText: 'Entendido'
            });
        });
};

    // Editar incidencia
   window.editarIncidencia = function(id) {
    fetch(`obtener_incidencia.php?id=${id}`)
        .then(response => response.json())
        .then(incidencia => {
            document.getElementById('modalIncidenciaTitulo').textContent = 'Editar Incidencia';
            document.getElementById('btnSubmitIncidencia').textContent = 'Actualizar Incidencia';
            document.getElementById('formIncidencia').action = 'actualizar_incidencia.php';
            document.getElementById('incidencia_id').value = incidencia.id;
            
            // Configurar selects bloqueados
            document.getElementById('sede_incidencia').innerHTML = `
                <option value="${incidencia.sede_id}" selected>${incidencia.sede_nombre}</option>
            `;
            document.getElementById('sede_incidencia').disabled = true;
            
            document.getElementById('salon_incidencia').innerHTML = `
                <option value="${incidencia.salon_id}" selected>${incidencia.salon_codigo}</option>
            `;
            document.getElementById('salon_incidencia').disabled = true;
            
            document.getElementById('computadora_incidencia').innerHTML = `
                <option value="${incidencia.computador_id}" selected>${incidencia.computadora_codigo}</option>
            `;
            document.getElementById('computadora_incidencia').disabled = true;
            
            // Llenar campos editables
            document.getElementById('titulo_incidencia').value = incidencia.titulo;
            document.getElementById('descripcion_incidencia').value = incidencia.descripcion;
            document.getElementById('prioridad_incidencia').value = incidencia.prioridad;
            document.getElementById('estado_incidencia').value = incidencia.estado;
            document.getElementById('asignado_nombre_incidencia').value = incidencia.asignado_nombre || '';
            document.getElementById('solucion_incidencia').value = incidencia.solucion || '';
            
            // Configurar fechas
            if (incidencia.fecha_reporte) {
                document.getElementById('fecha_reporte_incidencia').value = incidencia.fecha_reporte.split(' ')[0];
            }
            
            if (incidencia.fecha_asignacion) {
                document.getElementById('fecha_asignacion_incidencia').value = incidencia.fecha_asignacion.split(' ')[0];
            }
            
            if (incidencia.fecha_resolucion) {
                document.getElementById('fecha_resolucion_incidencia').value = incidencia.fecha_resolucion.split(' ')[0];
            }
            
            // Mostrar modal
            document.getElementById('modalIncidencia').classList.add('show');
            document.body.style.overflow = 'hidden';
        });
};

window.eliminarIncidencia = function(id, event) {
    event.stopPropagation();
    
    Swal.fire({
        title: '¿Eliminar Incidencia?',
        text: '¿Estás seguro de que deseas eliminar esta incidencia permanentemente?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#d63031'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Eliminando...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                    
                    fetch('eliminar_incidencia.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `id=${id}&csrf_token=<?php echo $_SESSION['csrf_token']; ?>`
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Error en la respuesta del servidor');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                title: '¡Eliminada!',
                                text: data.message,
                                icon: 'success'
                            }).then(() => {
                                cargarIncidencias(); // Recargar la tabla
                            });
                        } else {
                            throw new Error(data.message || 'Error al eliminar la incidencia');
                        }
                    })
                    .catch(error => {
                        Swal.fire('Error', error.message, 'error');
                    });
                }
            });
        }
    });
};
    
    // Configurar eventos para el modal de incidencias
    const modalIncidencia = document.getElementById('modalIncidencia');
    const btnReportarIncidencia = document.getElementById('btnReportarIncidencia');
    const closeModalIncidencia = document.getElementById('closeModalIncidencia');
    const cancelarFormIncidencia = document.getElementById('cancelarFormIncidencia');
    
    // Función para resetear el modal
function resetearModalIncidencia() {
    document.getElementById('modalIncidenciaTitulo').textContent = 'Reportar Incidencia';
    document.getElementById('btnSubmitIncidencia').textContent = 'Reportar Incidencia';
    document.getElementById('formIncidencia').action = 'guardar_incidencia.php';
    document.getElementById('formIncidencia').reset();
    document.getElementById('incidencia_id').value = '';
    document.getElementById('formIncidencia').classList.remove('was-validated');
    
    // Reactivar selects y limpiar opciones
    document.getElementById('sede_incidencia').disabled = false;
    document.getElementById('sede_incidencia').innerHTML = `
        <option value="">Seleccione una sede</option>
        <?php foreach ($sedes as $sede): ?>
            <option value="<?php echo $sede['id']; ?>"><?php echo htmlspecialchars($sede['nombre']); ?></option>
        <?php endforeach; ?>
    `;
    
    // Restablecer valores por defecto
    document.getElementById('prioridad_incidencia').value = 'media';
    document.getElementById('estado_incidencia').value = 'reportado';
}
    
    // Mostrar modal para nueva incidencia
    btnReportarIncidencia.addEventListener('click', () => {
        resetearModalIncidencia();
        modalIncidencia.classList.add('show');
        document.body.style.overflow = 'hidden';
    });
    
    // Cerrar modal
    closeModalIncidencia.addEventListener('click', () => {
        modalIncidencia.classList.remove('show');
        document.body.style.overflow = 'auto';
    });
    
    cancelarFormIncidencia.addEventListener('click', () => {
        modalIncidencia.classList.remove('show');
        document.body.style.overflow = 'auto';
    });
    
    modalIncidencia.addEventListener('click', (e) => {
        if (e.target === modalIncidencia) {
            modalIncidencia.classList.remove('show');
            document.body.style.overflow = 'auto';
        }
    });
    
    // Cargar salones cuando se selecciona una sede
    document.getElementById('sede_incidencia').addEventListener('change', function() {
        const sedeId = this.value;
        const salonSelect = document.getElementById('salon_incidencia');
        
        if (sedeId) {
            fetch(`obtener_salones.php?sede_id=${sedeId}`)
                .then(response => response.json())
                .then(data => {
                    salonSelect.innerHTML = '<option value="">Seleccione un salón</option>';
                    salonSelect.disabled = false;
                    
                    data.forEach(salon => {
                        const option = document.createElement('option');
                        option.value = salon.id;
                        option.textContent = salon.codigo_salon;
                        salonSelect.appendChild(option);
                    });
                });
        } else {
            salonSelect.innerHTML = '<option value="">Seleccione un salón</option>';
            salonSelect.disabled = true;
            document.getElementById('computadora_incidencia').innerHTML = '<option value="">Seleccione una computadora</option>';
            document.getElementById('computadora_incidencia').disabled = true;
        }
    });
    
    // Cargar computadoras cuando se selecciona un salón
    document.getElementById('salon_incidencia').addEventListener('change', function() {
        const salonId = this.value;
        const pcSelect = document.getElementById('computadora_incidencia');
        
        if (salonId) {
            fetch(`obtener_computadoras.php?salon_id=${salonId}`)
                .then(response => response.json())
                .then(data => {
                    pcSelect.innerHTML = '<option value="">Seleccione una computadora</option>';
                    pcSelect.disabled = false;
                    
                    data.forEach(pc => {
                        const option = document.createElement('option');
                        option.value = pc.id;
                        option.textContent = `${pc.codigo_patrimonio} - ${pc.marca} ${pc.modelo}`;
                        pcSelect.appendChild(option);
                    });
                });
        } else {
            pcSelect.innerHTML = '<option value="">Seleccione una computadora</option>';
            pcSelect.disabled = true;
        }
    });
}

    // Inicialización cuando el DOM está listo
    document.addEventListener('DOMContentLoaded', function() {
        generarAvatares();
        setupRippleEffects();
        setupAnimations();
        setupFormValidation();
        setupMobileMenu();
        setupSearch();
        setupMenuNavigation();
        setupLogout();
        setupModals();
        setupSalonesFunctions();
        setupSedesFunctions();
        setupComputadorasFunctions();
        setupIncidenciasFunctions();
        
        // Mostrar sección de inicio por defecto
        document.getElementById('inicio-section').style.display = 'block';
    });
    
</script>
</body>
</html>