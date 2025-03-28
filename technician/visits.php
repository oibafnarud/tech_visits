<?php
require_once '../includes/session_check.php';
require_once '../config/database.php';

$database = new Database();
$db = $database->connect();

// Procesar POST requests
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    header('Content-Type: application/json');
    
    // Validar que sea un técnico
    if ($_SESSION['role'] !== 'technician') {
        http_response_code(403);
        die(json_encode(['error' => 'Acceso no autorizado']));
    }

    try {
        $database = new Database();
        $db = $database->connect();
        
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        
        switch ($data['action']) {
            case 'update_status':
                // Validar que la visita pertenezca al técnico
                $stmt = $db->prepare("
                    SELECT id 
                    FROM visits 
                    WHERE id = :visit_id 
                    AND technician_id = :technician_id
                ");
                $stmt->execute([
                    ':visit_id' => $data['visit_id'],
                    ':technician_id' => $_SESSION['user_id']
                ]);
                
                if (!$stmt->fetch()) {
                    throw new Exception('Visita no encontrada');
                }

                $stmt = $db->prepare("
                    UPDATE visits 
                    SET status = :status,
                        updated_at = NOW()
                        " . ($data['status'] === 'completed' ? ", completion_time = NOW()" : "") . "
                    WHERE id = :visit_id 
                    AND technician_id = :technician_id
                ");

                $result = $stmt->execute([
                    ':status' => $data['status'],
                    ':visit_id' => $data['visit_id'],
                    ':technician_id' => $_SESSION['user_id']
                ]);

                if ($result) {
                    // Registrar en historial
                    $stmt = $db->prepare("
                        INSERT INTO visit_history (
                            visit_id, action, action_by, 
                            action_at, details
                        ) VALUES (
                            :visit_id, 'status_change', :user_id,
                            NOW(), :details
                        )
                    ");

                    $stmt->execute([
                        ':visit_id' => $data['visit_id'],
                        ':user_id' => $_SESSION['user_id'],
                        ':details' => "Estado cambiado a: " . $data['status']
                    ]);

                    echo json_encode(['success' => true]);
                } else {
                    throw new Exception('Error al actualizar el estado');
                }
                break;

            case 'save_exception':
                $stmt = $db->prepare("
                    INSERT INTO availability_exceptions (
                        technician_id, exception_date, 
                        start_time, end_time,
                        is_available, reason
                    ) VALUES (
                        :technician_id, :exception_date,
                        :start_time, :end_time,
                        :is_available, :reason
                    )
                ");

                $result = $stmt->execute([
                    ':technician_id' => $_SESSION['user_id'],
                    ':exception_date' => $data['exception_date'],
                    ':start_time' => $data['is_available'] ? $data['start_time'] : null,
                    ':end_time' => $data['is_available'] ? $data['end_time'] : null,
                    ':is_available' => isset($data['is_available']) ? 1 : 0,
                    ':reason' => $data['reason']
                ]);

                if ($result) {
                    echo json_encode(['success' => true]);
                } else {
                    throw new Exception('Error al guardar la excepción');
                }
                break;

            case 'delete_exception':
                $stmt = $db->prepare("
                    DELETE FROM availability_exceptions 
                    WHERE id = :exception_id 
                    AND technician_id = :technician_id
                ");

                $result = $stmt->execute([
                    ':exception_id' => $data['exception_id'],
                    ':technician_id' => $_SESSION['user_id']
                ]);

                if ($result) {
                    echo json_encode(['success' => true]);
                } else {
                    throw new Exception('Error al eliminar la excepción');
                }
                break;

            default:
                throw new Exception('Acción no válida');
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// Obtener parámetros de vista
$view = isset($_GET['view']) ? $_GET['view'] : 'today';
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Determinar rango de fechas según la vista
$date_condition = "";
switch($view) {
    case 'week':
        $start_of_week = date('Y-m-d', strtotime('monday this week', strtotime($selected_date)));
        $end_of_week = date('Y-m-d', strtotime('sunday this week', strtotime($selected_date)));
        $date_condition = "AND visit_date BETWEEN '$start_of_week' AND '$end_of_week'";
        break;
    case 'month':
        $start_of_month = date('Y-m-01', strtotime($selected_date));
        $end_of_month = date('Y-m-t', strtotime($selected_date));
        $date_condition = "AND visit_date BETWEEN '$start_of_month' AND '$end_of_month'";
        break;
    default: // today
        $date_condition = "AND visit_date = '$selected_date'";
}

// Obtener visitas
$stmt = $db->prepare("
    SELECT * FROM visits 
    WHERE technician_id = :technician_id 
    $date_condition 
    ORDER BY visit_date, visit_time ASC
");
$stmt->execute([':technician_id' => $_SESSION['user_id']]);
$visits = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Agrupar visitas por día para vistas semana/mes
$visitsByDay = [];
foreach ($visits as $visit) {
    $day = date('j', strtotime($visit['visit_date']));
    if (!isset($visitsByDay[$day])) {
        $visitsByDay[$day] = [];
    }
    $visitsByDay[$day][] = $visit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>App Técnicos</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .bottom-nav-item.active {
            color: #2563eb;
            border-top: 2px solid #2563eb;
        }
        .visit-card {
            transition: all 0.2s ease;
        }
        .visit-card:active {
            transform: scale(0.98);
        }
        /* Para modales en móviles */
        @media (max-width: 640px) {
            .modal-content {
                max-height: 90vh;
                overflow-y: auto;
            }
        }
            .notification-badge {
                padding: 0 6px;
                min-width: 20px;
                height: 20px;
                font-size: 12px;
                font-weight: bold;
                transform: translate(25%, -25%);
            }
    </style>
    
    <audio id="notificationSound" preload="auto">
        <source src="../assets/sounds/notification.mp3" type="audio/mpeg">
    </audio>

<script>
// Definir todas las funciones en el scope global
window.isLoadingNotifications = false;
window.lastNotificationTime = new Date();
window.notificationCheckInterval = null;

window.lastCheck = new Date();
window.pollingInterval = null;

window.showTechnicianReport = async function() {
    const modal = document.getElementById('reportModal');
    const content = document.getElementById('reportContent');
    
    // Mostrar modal con loading
    modal.classList.remove('hidden');
    content.innerHTML = `
        <div class="animate-pulse">
            <div class="h-8 bg-gray-200 rounded w-3/4 mb-4"></div>
            <div class="space-y-3">
                <div class="h-4 bg-gray-200 rounded"></div>
                <div class="h-4 bg-gray-200 rounded w-5/6"></div>
                <div class="h-4 bg-gray-200 rounded w-4/6"></div>
            </div>
        </div>
    `;

    // Ocultar menú más
    window.toggleMoreMenu();

    try {
        // Cargar datos
        const response = await fetch('./actions/get_report.php');
        const data = await response.json();
        
        if (!data.success) throw new Error(data.error);
        
        const stats = data.data.stats;
        const completionRate = stats.total_visits > 0 ? 
            ((stats.completed_visits / stats.total_visits) * 100).toFixed(1) : 0;
        
        content.innerHTML = `
            <div class="space-y-6">
                <!-- Resumen del mes -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <div class="text-3xl font-bold text-blue-600">${stats.total_visits}</div>
                        <div class="text-sm text-blue-600">Total Visitas</div>
                    </div>
                    <div class="bg-green-50 p-4 rounded-lg">
                        <div class="text-3xl font-bold text-green-600">${completionRate}%</div>
                        <div class="text-sm text-green-600">Completadas</div>
                    </div>
                </div>

                <!-- Estadísticas detalladas -->
                <div class="bg-white rounded-lg shadow-sm p-4 space-y-4">
                    <h4 class="font-medium">Estadísticas del Mes</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <div class="text-sm text-gray-500">Tiempo promedio</div>
                            <div class="font-medium">${stats.avg_completion_time ? Math.round(stats.avg_completion_time / 60) : 0}h</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">Días activos</div>
                            <div class="font-medium">${stats.active_days} días</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">En ruta</div>
                            <div class="font-medium">${stats.in_route_visits}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">Pendientes</div>
                            <div class="font-medium">${stats.pending_visits}</div>
                        </div>
                    </div>
                </div>

                <!-- Estadísticas por tipo de servicio -->
                <div class="bg-white rounded-lg shadow-sm p-4">
                    <h4 class="font-medium mb-4">Por Tipo de Servicio</h4>
                    <div class="space-y-3">
                        ${data.data.serviceStats.map(service => `
                            <div>
                                <div class="flex justify-between mb-1">
                                    <span class="text-sm font-medium">${service.service_type || 'Sin especificar'}</span>
                                    <span class="text-sm text-gray-500">${service.total} visitas</span>
                                </div>
                                <div class="h-2 bg-gray-200 rounded">
                                    <div class="h-2 bg-blue-600 rounded" style="width: ${(service.total / stats.total_visits * 100)}%"></div>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            </div>
        `;
    } catch (error) {
        console.error('Error al cargar reporte:', error);
        content.innerHTML = `
            <div class="text-center text-red-600 py-8">
                Error al cargar el reporte
                <button onclick="window.showTechnicianReport()" class="block mt-4 text-blue-600">
                    Reintentar
                </button>
            </div>
        `;
    }
};

window.hideReportModal = function() {
    document.getElementById('reportModal').classList.add('hidden');
};


// Función para cargar notificaciones
window.loadNotifications = async function() {
    if (window.isLoadingNotifications) return;
    
    const loadingDiv = document.getElementById('notificationsLoading');
    const contentDiv = document.getElementById('notificationsContent');
    
    try {
        window.isLoadingNotifications = true;
        if (loadingDiv) loadingDiv.classList.remove('hidden');
        if (contentDiv) contentDiv.classList.add('opacity-50');

        const response = await fetch('./actions/get_notifications.php');
        const data = await response.json();
        
        if (contentDiv) {
            if (data.success) {
                contentDiv.innerHTML = data.notifications.length > 0 ? 
                    data.notifications.map(notif => `
                        <div class="p-4 hover:bg-gray-50 cursor-pointer ${notif.status === 'unread' ? 'bg-blue-50' : ''}"
                             onclick="window.markAsRead(${notif.id}, this)">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <h4 class="font-medium text-gray-900">${window.escapeHtml(notif.title)}</h4>
                                    <p class="text-sm text-gray-600 mt-1 whitespace-pre-line">${window.escapeHtml(notif.message)}</p>
                                    <p class="text-xs text-gray-500 mt-2">${window.formatDate(notif.created_at)}</p>
                                </div>
                                ${notif.status === 'unread' ? '<span class="w-2 h-2 bg-blue-600 rounded-full"></span>' : ''}
                            </div>
                        </div>
                    `).join('') 
                    : '<div class="p-4 text-center text-gray-500">No hay notificaciones</div>';
                
                // Actualizar el contador de notificaciones
                const unreadCount = data.notifications.filter(n => n.status === 'unread').length;
                window.updateNotificationBadge(unreadCount);
            } else {
                throw new Error(data.error || 'Error al cargar notificaciones');
            }
        }
    } catch (error) {
        console.error('Error:', error);
        if (contentDiv) {
            contentDiv.innerHTML = `
                <div class="p-4 text-center text-red-500">
                    Error al cargar las notificaciones
                    <button onclick="window.loadNotifications()" class="mt-2 text-blue-600 hover:underline">
                        Reintentar
                    </button>
                </div>
            `;
        }
    } finally {
        if (loadingDiv) loadingDiv.classList.add('hidden');
        if (contentDiv) contentDiv.classList.remove('opacity-50');
        window.isLoadingNotifications = false;
    }
};

window.markAsRead = async function(notificationId, element) {
    try {
        const response = await fetch('./actions/mark_notification_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ notification_id: notificationId })
        });

        const data = await response.json();
        if (data.success) {
            // Remover la clase de no leído
            element.classList.remove('bg-blue-50');
            // Remover el punto azul
            element.querySelector('.bg-blue-600')?.remove();
            
            // Actualizar el contador
            const currentCount = parseInt(document.querySelector('.notification-badge')?.textContent || '0');
            if (currentCount > 0) {
                window.updateNotificationBadge(currentCount - 1);
            }
        }
    } catch (error) {
        console.error('Error al marcar como leída:', error);
    }
};

// Función para alternar el panel de notificaciones
window.toggleNotifications = function() {
    const panel = document.getElementById('notificationsPanel');
    const isHidden = panel.classList.contains('translate-x-full');
    
    if (isHidden) {
        window.loadNotifications();
    }
    
    panel.classList.toggle('translate-x-full');
};

// Funciones auxiliares
window.escapeHtml = function(unsafe) {
    if (!unsafe) return '';
    return unsafe
        .toString()
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
};

window.formatDate = function(dateStr) {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleDateString('es-ES', { 
        day: '2-digit', 
        month: '2-digit', 
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

window.checkNotifications = async function() {
    try {
        const response = await fetch('./actions/check_notifications.php', {
            headers: {
                'Cache-Control': 'no-cache',
                'Pragma': 'no-cache'
            }
        });
        const data = await response.json();
        
        if (data.success) {
            // Actualizar contador
            window.updateNotificationBadge(data.unread_count);
            
            if (data.new_notifications?.length > 0) {
                // Reproducir sonido
                playNotificationSound();
                // Actualizar última verificación
                window.lastCheck = new Date();
            }
        }
    } catch (error) {
        console.error('Error checking notifications:', error);
    }
};

// Función para el sonido
function playNotificationSound() {
    const audio = new Audio('../assets/sounds/notification.mp3');
    audio.volume = 0.5; // Volumen al 50%
    
    const playPromise = audio.play();
    if (playPromise !== undefined) {
        playPromise.catch(error => {
            console.log('Auto-play prevented:', error);
        });
    }
}

// Iniciar polling al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    window.checkNotifications();
    // Verificar cada 30 segundos
    window.pollingInterval = setInterval(window.checkNotifications, 30000);
});

window.updateNotificationBadge = function(count) {
    const badges = document.querySelectorAll('.notification-badge');
    badges.forEach(badge => {
        if (count > 0) {
            badge.textContent = count;
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }
    });
};

// Inicialización cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    // Iniciar verificación periódica
    window.checkNotifications();
    setInterval(window.checkNotifications, 60000);
});
</script>

</head>
<body class="bg-gray-50 pb-16">
    <!-- Cabecera fija -->
<header class="fixed top-0 left-0 right-0 bg-white shadow z-50">
    <div class="flex justify-between items-center px-4 h-16">
        <div class="flex items-center">
            <img src="../assets/images/logo.png" alt="Logo" class="h-8 w-auto mr-2">
            <h1 class="text-xl font-semibold">App Técnicos</h1>
        </div>
        <div class="flex items-center">
            <!-- Solo una campana de notificaciones -->
            <button onclick="toggleNotifications()" class="p-2 text-gray-600 hover:text-gray-800 relative">
                <i class="fas fa-bell text-xl"></i>
                <span class="notification-badge absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full min-w-[20px] h-5 flex items-center justify-center hidden">
                    0
                </span>
            </button>
        </div>
    </div>
</header>

    <!-- Contenido Principal -->
    <main class="mt-16 px-4 pb-4">
        <?php if ($view === 'today'): ?>
            <!-- Vista de Hoy -->
            <div class="text-center py-4">
                <h2 class="text-2xl font-bold">
                    <?php 
                    if ($selected_date === date('Y-m-d')) {
                        echo 'Hoy';
                    } else {
                        echo date('d/m/Y', strtotime($selected_date));
                    }
                    ?>
                </h2>
            </div>

            <!-- Lista de Visitas -->
            <div class="space-y-4">
                <?php foreach ($visits as $visit): ?>
                    <div class="visit-card bg-white rounded-lg shadow-sm" data-visit-id="<?php echo $visit['id']; ?>">
                        <!-- Cabecera de la visita -->
                        <div class="p-4 border-b">
                            <div class="flex justify-between items-start">
                                <div>
                                    <div class="text-xl font-bold">
                                        <?php echo date('h:i A', strtotime($visit['visit_time'])); ?>
                                    </div>
                                    <div class="text-gray-600">
                                        <?php echo htmlspecialchars($visit['client_name']); ?>
                                    </div>
                                </div>
                                <span class="px-3 py-1 rounded-full text-sm 
                                    <?php echo $visit['status'] === 'completed' ? 'bg-green-100 text-green-800' : 
                                        ($visit['status'] === 'in_route' ? 'bg-yellow-100 text-yellow-800' : 
                                         'bg-blue-100 text-blue-800'); ?>">
                                    <?php 
                                    echo $visit['status'] === 'completed' ? 'Completada' : 
                                         ($visit['status'] === 'in_route' ? 'En Camino' : 'Pendiente');
                                    ?>
                                </span>
                            </div>
                        </div>
                        
                        <?php if ($visit['client_name'] === 'Personal'): ?>
    <div class="visit-card bg-white rounded-lg shadow-sm" data-visit-id="<?php echo $visit['id']; ?>">
        <div class="p-4 border-b">
            <div class="flex justify-between items-start">
                <div>
                    <div class="text-xl font-bold">
                        <?php echo date('h:i A', strtotime($visit['visit_time'])); ?>
                    </div>
                    <div class="flex items-center">
                        <span class="px-3 py-1 rounded-full text-sm bg-red-100 text-red-800">
                            No Disponible
                        </span>
                        <span class="ml-2 text-gray-600">
                            <?php echo htmlspecialchars($visit['service_type']); ?>
                        </span>
                    </div>
                </div>
                <button onclick="deleteBlock(<?php echo $visit['id']; ?>)" 
                        class="text-red-600 hover:bg-red-50 p-2 rounded">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
            <?php if ($visit['notes']): ?>
                <div class="mt-2 text-sm text-gray-600">
                    <?php echo htmlspecialchars($visit['notes']); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

                        <!-- Detalles y Acciones -->
                        <div class="p-4">
                            <div class="space-y-2 mb-4">
                                <div class="flex items-center text-gray-600">
                                    <i class="fas fa-map-marker-alt w-5"></i>
                                    <span><?php echo htmlspecialchars($visit['address']); ?></span>
                                </div>
                                <?php if ($visit['contact_phone']): ?>
                                    <a href="tel:<?php echo htmlspecialchars($visit['contact_phone']); ?>" 
                                       class="flex items-center text-blue-600">
                                        <i class="fas fa-phone w-5"></i>
                                        <span><?php echo htmlspecialchars($visit['contact_phone']); ?></span>
                                    </a>
                                <?php endif; ?>
                            </div>

                            <?php if ($visit['status'] !== 'completed' && $visit['client_name'] !== 'Personal'): ?>
                                <div class="grid grid-cols-2 gap-2">
                                    <?php if ($visit['location_url']): ?>
                                        <a href="<?php echo htmlspecialchars($visit['location_url']); ?>" 
                                           target="_blank"
                                           class="col-span-2 flex items-center justify-center bg-gray-100 text-gray-700 p-2 rounded">
                                            <i class="fas fa-map-marked-alt mr-2"></i>
                                            Ver en Mapa
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($visit['status'] === 'pending'): ?>
                                        <button onclick="updateStatus(<?php echo $visit['id']; ?>, 'in_route')"
                                                class="flex items-center justify-center bg-yellow-500 text-white p-2 rounded">
                                            <i class="fas fa-truck mr-2"></i>
                                            En Camino
                                        </button>
                                    <?php endif; ?>

                                    <button onclick="updateStatus(<?php echo $visit['id']; ?>, 'completed')"
                                            class="flex items-center justify-center bg-green-500 text-white p-2 rounded">
                                        <i class="fas fa-check mr-2"></i>
                                        Completar
                                    </button>

                                    <?php 
                                    $visit_timestamp = strtotime($visit['visit_date'] . ' ' . $visit['visit_time']);
                                    if ($visit_timestamp > strtotime('+24 hours')): 
                                    ?>
                                        <button onclick="showRescheduleModal(<?php echo $visit['id']; ?>)"
                                                class="col-span-2 flex items-center justify-center bg-blue-100 text-blue-700 p-2 rounded">
                                            <i class="fas fa-calendar-alt mr-2"></i>
                                            Reprogramar
                                        </button>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if (empty($visits)): ?>
                    <div class="text-center py-8">
                        <div class="text-gray-400 text-5xl mb-4">
                            <i class="far fa-calendar-check"></i>
                        </div>
                        <p class="text-gray-500">No hay visitas programadas</p>
                    </div>
                <?php endif; ?>
            </div>

        <?php elseif ($view === 'month'): ?>
            <!-- Vista de Mes -->
            <?php include 'month_view.php'; ?>

        <?php elseif ($view === 'week'): ?>
            <!-- Vista de Semana -->
            <?php include 'week_view.php'; ?>
            
        <?php endif; ?>
    </main>

    <!-- Barra de navegación inferior -->
<nav class="fixed bottom-0 left-0 right-0 bg-white border-t">
    <div class="grid grid-cols-5 h-16">
        <a href="?view=today" 
           class="bottom-nav-item flex flex-col items-center justify-center <?php echo $view === 'today' ? 'active' : ''; ?>">
            <i class="fas fa-calendar-day text-xl mb-1"></i>
            <span class="text-xs">Hoy</span>
        </a>
        <a href="?view=week" 
           class="bottom-nav-item flex flex-col items-center justify-center <?php echo $view === 'week' ? 'active' : ''; ?>">
            <i class="fas fa-calendar-week text-xl mb-1"></i>
            <span class="text-xs">Semana</span>
        </a>
        <a href="?view=month" 
           class="bottom-nav-item flex flex-col items-center justify-center <?php echo $view === 'month' ? 'active' : ''; ?>">
            <i class="fas fa-calendar-alt text-xl mb-1"></i>
            <span class="text-xs">Mes</span>
        </a>
        <button onclick="showBlockTimeModal()" 
                class="bottom-nav-item flex flex-col items-center justify-center">
            <i class="fas fa-ban text-xl mb-1"></i>
            <span class="text-xs">Bloquear</span>
        </button>
        
        <button onclick="toggleMoreMenu()" 
                class="bottom-nav-item flex flex-col items-center justify-center">
            <i class="fas fa-bars text-xl mb-1"></i>
            <span class="text-xs">Más</span>
        </button>
    </div>
</nav>
        
<div id="moreMenu" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="fixed bottom-0 left-0 right-0 bg-white rounded-t-xl">
        <div class="p-4">
            <div class="mb-4">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-bold">Menú</h3>
                    <button onclick="toggleMoreMenu()" class="text-gray-500">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <!-- Perfil -->
                <div class="flex items-center p-4 bg-gray-50 rounded-lg mb-4">
                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center text-blue-600">
                        <i class="fas fa-user text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <div class="font-medium"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
                        <div class="text-sm text-gray-500">Técnico</div>
                    </div>
                </div>

                <!-- Opciones del menú -->
                <div class="space-y-2">
                    <!-- Agregar opción de Reportes -->
                <button onclick="window.showTechnicianReport()" class="w-full flex items-center p-3 hover:bg-gray-50 rounded-lg">
                    <i class="fas fa-chart-bar w-8 text-blue-600"></i>
                    <span>Mi Rendimiento</span>
                </button>

                    <a href="#" onclick="toggleBlockTimeModal(); return false;" class="flex items-center p-3 hover:bg-gray-50 rounded-lg">
                        <i class="fas fa-calendar-times w-8 text-blue-600"></i>
                        <span>Bloquear Horario</span>
                    </a>

                    <button onclick="showPasswordModal()" class="w-full flex items-center p-3 hover:bg-gray-50 rounded-lg">
                        <i class="fas fa-key w-8 text-blue-600"></i>
                        <span>Cambiar Contraseña</span>
                    </button>

                    <div class="border-t my-2"></div>

                    <a href="../logout.php" class="flex items-center p-3 hover:bg-red-50 text-red-600 rounded-lg">
                        <i class="fas fa-sign-out-alt w-8"></i>
                        <span>Cerrar Sesión</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Reporte -->
<div id="reportModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="fixed inset-4 bg-white rounded-xl overflow-hidden flex flex-col">
        <div class="p-4 border-b flex justify-between items-center sticky top-0 bg-white">
            <h3 class="text-lg font-bold">Mi Rendimiento</h3>
            <button onclick="hideReportModal()" class="text-gray-500">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto p-4">
            <div id="reportContent" class="space-y-6">
                <!-- El contenido se cargará dinámicamente -->
                <div class="animate-pulse">
                    <div class="h-8 bg-gray-200 rounded w-3/4 mb-4"></div>
                    <div class="space-y-3">
                        <div class="h-4 bg-gray-200 rounded"></div>
                        <div class="h-4 bg-gray-200 rounded w-5/6"></div>
                        <div class="h-4 bg-gray-200 rounded w-4/6"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="notificationsPanel" class="fixed inset-y-0 right-0 w-full md:w-96 bg-white shadow-lg transform translate-x-full transition-transform duration-300 z-50">
    <div class="h-full flex flex-col">
        <div class="p-4 border-b flex justify-between items-center">
            <h3 class="text-lg font-bold">Notificaciones</h3>
            <button onclick="toggleNotifications()" class="text-gray-500">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div id="notificationsLoading" class="flex-1 flex items-center justify-center hidden">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        </div>

        <div id="notificationsContent" class="flex-1 overflow-y-auto divide-y">
            <!-- El contenido se cargará dinámicamente -->
        </div>
    </div>
</div>

    <!-- Modales -->
    <?php include 'modals.php'; ?>

    <!-- Scripts -->
    <script>
    // Funciones para modales
    function showBlockTimeModal() {
        document.getElementById('blockTimeModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function hideBlockTimeModal() {
        document.getElementById('blockTimeModal').classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    function showVisitDetailModal(visitId) {
        const modal = document.getElementById('visitDetailModal');
        const content = document.getElementById('visitDetailContent');
        const visitCard = document.querySelector(`[data-visit-id="${visitId}"]`).cloneNode(true);
        
        content.innerHTML = '';
        content.appendChild(visitCard);
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function hideVisitDetailModal() {
        document.getElementById('visitDetailModal').classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    // Función para actualizar estado
    function updateStatus(visitId, status) {
        if (!confirm('¿Seguro que quieres cambiar el estado?')) return;

        const formData = new FormData();
        formData.append('action', 'update_status');
        formData.append('visit_id', visitId);
        formData.append('status', status);

        fetch(window.location.href, {
            method: 'POST',
            body: formData,
            headers: {'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert('Error al actualizar el estado');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al actualizar el estado');
        });
    }
    
    function deleteBlock(blockId) {
        if (!confirm('¿Seguro que quieres eliminar este bloqueo?')) return;

        const formData = new FormData();
        formData.append('action', 'delete_block');
        formData.append('visit_id', blockId);

        fetch(window.location.href, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Eliminar la tarjeta del DOM o recargar la página
                const card = document.querySelector(`[data-visit-id="${blockId}"]`);
                if (card) {
                    card.remove();
                } else {
                    window.location.reload();
                }
            } else {
                alert('Error al eliminar el bloqueo');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al eliminar el bloqueo');
        });
    }

    // Cerrar modales al hacer clic fuera
    document.addEventListener('click', function(event) {
        const modals = ['blockTimeModal', 'visitDetailModal', 'rescheduleModal'];
        modals.forEach(modalId => {
            const modal = document.getElementById(modalId);
            if (event.target === modal) {
                modal.classList.add('hidden');
                document.body.style.overflow = 'auto';
            }
        });
    });
    </script>
    
<script>
async function updateStatus(visitId, status) {
    if (!confirm(`¿Seguro que quieres marcar esta visita como ${
        status === 'completed' ? 'completada' : 'en camino'
    }?`)) return;

    try {
        const response = await fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'update_status',
                visit_id: visitId,
                status: status
            })
        });

        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.error || 'Error al actualizar estado');
        }

        if (data.success) {
            window.location.reload();
        }
    } catch (error) {
        alert(error.message);
    }
}

async function saveException(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    
    try {
        // Añadir el ID del técnico al FormData
        formData.append('technician_id', '<?php echo $_SESSION['user_id']; ?>');

        const response = await fetch('actions/save_exception.php', {
            method: 'POST',
            body: formData
        });

        // Verificar si la respuesta es JSON
        const contentType = response.headers.get("content-type");
        if (!contentType || !contentType.includes("application/json")) {
            throw new Error("Error en el servidor");
        }

        const data = await response.json();
        if (data.success) {
            hideBlockTimeModal();
            showNotification('Excepción guardada correctamente', 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            throw new Error(data.error || 'Error al guardar la excepción');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification(error.message, 'error');
    }
}

async function deleteException(exceptionId) {
    if (!confirm('¿Seguro que quieres eliminar esta excepción?')) return;

    try {
        const response = await fetch('../admin/actions/delete_exception.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ exception_id: exceptionId })
        });

        const data = await response.json();
        if (data.success) {
            window.location.reload();
        } else {
            throw new Error(data.error || 'Error al eliminar la excepción');
        }
    } catch (error) {
        alert(error.message);
    }
}

function toggleTimeInputs(checkbox) {
    const timeInputs = document.getElementById('timeInputs');
    const startTime = document.querySelector('input[name="start_time"]');
    const endTime = document.querySelector('input[name="end_time"]');
    
    timeInputs.style.display = checkbox.checked ? 'block' : 'none';

    if (checkbox.checked) {
        startTime.required = true;
        endTime.required = true;
    } else {
        startTime.required = false;
        endTime.required = false;
        startTime.value = '';
        endTime.value = '';
    }
}

function showVisitDetail(visitId) {
    // Mostrar un loading
    document.getElementById('visitDetailContent').innerHTML = `
        <div class="flex justify-center items-center p-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        </div>
    `;
    
    // Mostrar el modal
    document.getElementById('visitDetailModal').classList.remove('hidden');
    
    // Hacer la petición AJAX
    fetch(`get_visit_details.php?id=${visitId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const visit = data.visit;
                // Crear el contenido HTML con los detalles
                const content = `
                    <div class="space-y-4">
                        <div class="flex justify-between items-start">
                            <div>
                                <div class="text-xl font-bold">
                                    ${visit.visit_time}
                                </div>
                                <div class="text-gray-600">
                                    ${visit.client_name}
                                </div>
                            </div>
                            <span class="px-3 py-1 rounded-full text-sm ${
                                visit.status === 'completed' ? 'bg-green-100 text-green-800' : 
                                visit.status === 'in_route' ? 'bg-yellow-100 text-yellow-800' : 
                                'bg-blue-100 text-blue-800'
                            }">
                                ${visit.status === 'completed' ? 'Completada' : 
                                  visit.status === 'in_route' ? 'En Camino' : 'Pendiente'}
                            </span>
                        </div>

                        <div class="space-y-3">
                            <div class="flex items-center text-gray-600">
                                <i class="fas fa-map-marker-alt w-5"></i>
                                <span class="ml-2">${visit.address}</span>
                            </div>
                            ${visit.contact_phone ? `
                                <a href="tel:${visit.contact_phone}" 
                                   class="flex items-center text-blue-600">
                                    <i class="fas fa-phone w-5"></i>
                                    <span class="ml-2">${visit.contact_phone}</span>
                                </a>
                            ` : ''}
                        </div>

                        ${visit.status !== 'completed' ? `
                            <div class="grid grid-cols-2 gap-2 mt-4">
                                ${visit.location_url ? `
                                    <a href="${visit.location_url}" 
                                       target="_blank"
                                       class="col-span-2 flex items-center justify-center bg-gray-100 text-gray-700 p-2 rounded">
                                        <i class="fas fa-map-marked-alt mr-2"></i>
                                        Ver en Mapa
                                    </a>
                                ` : ''}

                                ${visit.status === 'pending' ? `
                                    <button onclick="updateStatus(${visit.id}, 'in_route')"
                                            class="flex items-center justify-center bg-yellow-500 text-white p-2 rounded">
                                        <i class="fas fa-truck mr-2"></i>
                                        En Camino
                                    </button>
                                ` : ''}

                                <button onclick="updateStatus(${visit.id}, 'completed')"
                                        class="flex items-center justify-center bg-green-500 text-white p-2 rounded">
                                    <i class="fas fa-check mr-2"></i>
                                    Completar
                                </button>
                            </div>
                        ` : ''}
                    </div>
                `;
                
                document.getElementById('visitDetailContent').innerHTML = content;
            } else {
                throw new Error(data.error || 'Error al cargar los detalles');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('visitDetailContent').innerHTML = `
                <div class="text-center text-red-600 p-4">
                    Error al cargar los detalles. Intente nuevamente.
                </div>
            `;
        });
}

function hideVisitDetailModal() {
    document.getElementById('visitDetailModal').classList.add('hidden');
}

// Asignar event handler al formulario de excepciones
document.getElementById('blockTimeModal')?.querySelector('form')
    .addEventListener('submit', saveException);
</script>

<script>


// Funciones para manejar los menús
function toggleMoreMenu() {
    const menu = document.getElementById('moreMenu');
    menu.classList.toggle('hidden');
}

async function toggleNotifications() {
    const panel = document.getElementById('notificationsPanel');
    const isHidden = panel.classList.contains('translate-x-full');
    
    panel.classList.toggle('translate-x-full');
    
    if (isHidden) {
        // Si estamos abriendo el panel, cargar las notificaciones
        await loadNotifications();
    }
}

// Cerrar los menús al hacer click fuera
document.addEventListener('click', (event) => {
    const moreMenu = document.getElementById('moreMenu');
    const moreButton = event.target.closest('[onclick*="toggleMoreMenu"]');
    const notifPanel = document.getElementById('notificationsPanel');
    const notifButton = event.target.closest('[onclick*="toggleNotifications"]');

    if (!moreButton && !moreMenu.contains(event.target) && !moreMenu.classList.contains('hidden')) {
        toggleMoreMenu();
    }

    if (!notifButton && !notifPanel.contains(event.target) && !notifPanel.classList.contains('translate-x-full')) {
        toggleNotifications();
    }
});
</script>

<script>
// Funciones para el modal de contraseña
function showPasswordModal() {
    document.getElementById('passwordModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    toggleMoreMenu(); // Cerrar el menú más
}

function hidePasswordModal() {
    document.getElementById('passwordModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
    document.getElementById('passwordForm').reset();
}

async function changePassword(event) {
    event.preventDefault();
    const form = event.target;
    const submitBtn = form.querySelector('button[type="submit"]');
    const formData = new FormData(form);

    // Validar que las contraseñas coincidan
    if (formData.get('new_password') !== formData.get('confirm_password')) {
        showNotification('Las contraseñas no coinciden', 'error');
        return;
    }

    try {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Cambiando...';

        const response = await fetch('actions/password_actions.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        
        if (data.success) {
            showNotification('Contraseña actualizada exitosamente', 'success');
            hidePasswordModal();
        } else {
            throw new Error(data.error || 'Error al cambiar la contraseña');
        }
    } catch (error) {
        showNotification(error.message, 'error');
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Cambiar Contraseña';
    }
}

</script>

<script>
// Funciones de notificaciones
function toggleNotifications() {
    const panel = document.getElementById('notificationsPanel');
    const isHidden = panel.classList.contains('translate-x-full');
    
    if (isHidden) {
        loadNotifications();
    }
    
    panel.classList.toggle('translate-x-full');
}

async function loadNotifications() {
    if (window.isLoadingNotifications) return;
    
    const loadingDiv = document.getElementById('notificationsLoading');
    const contentDiv = document.getElementById('notificationsContent');
    
    try {
        window.isLoadingNotifications = true;
        loadingDiv?.classList.remove('hidden');
        contentDiv?.classList.add('opacity-50');

        const response = await fetch('./actions/get_notifications.php');
        const data = await response.json();

        if (!data.success) {
            throw new Error(data.error || 'Error al cargar notificaciones');
        }

        // Actualizar contenido
        if (contentDiv) {
            contentDiv.innerHTML = data.notifications.length > 0 ? 
                data.notifications.map(notif => `
                    <div class="p-4 hover:bg-gray-50 cursor-pointer ${notif.status === 'unread' ? 'bg-blue-50' : ''}"
                         onclick="markAsRead(${notif.id}, this)">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <h4 class="font-medium text-gray-900">${escapeHtml(notif.title)}</h4>
                                <p class="text-sm text-gray-600 mt-1 whitespace-pre-line">${escapeHtml(notif.message)}</p>
                                <p class="text-xs text-gray-500 mt-2">${formatDate(notif.created_at)}</p>
                            </div>
                            ${notif.status === 'unread' ? '<span class="w-2 h-2 bg-blue-600 rounded-full"></span>' : ''}
                        </div>
                    </div>
                `).join('') 
                : '<div class="p-4 text-center text-gray-500">No hay notificaciones</div>';
        }

    } catch (error) {
        console.error('Error al cargar notificaciones:', error);
        if (contentDiv) {
            contentDiv.innerHTML = `
                <div class="p-4 text-center text-red-500">
                    Error al cargar las notificaciones
                    <button onclick="loadNotifications()" class="mt-2 text-blue-600 hover:underline">
                        Reintentar
                    </button>
                </div>
            `;
        }
    } finally {
        loadingDiv?.classList.add('hidden');
        contentDiv?.classList.remove('opacity-50');
        window.isLoadingNotifications = false;
    }
}

// Resto de funciones auxiliares...
{código anterior de las funciones auxiliares}

// Inicializar al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    if (!window.notificationCheckInterval) {
        checkNotifications();
        window.notificationCheckInterval = setInterval(checkNotifications, 60000);
    }
    
    if ('Notification' in window) {
        Notification.requestPermission();
    }
});


function hideReportModal() {
    document.getElementById('reportModal').classList.add('hidden');
}
</script>



</body>
</html>