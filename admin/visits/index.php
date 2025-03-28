<?php
require_once '../../includes/session_check.php';
require_once '../../config/database.php';
require_once '../../includes/utils.php';

$page_title = 'Gestión de Visitas';
$current_page = 'visits';

try {
    $database = new Database();
    $db = $database->connect();

    // Parámetros de vista y filtros
    $view = $_GET['view'] ?? 'list';
    $date = $_GET['date'] ?? date('Y-m-d');
    $technician = $_GET['technician'] ?? 'all';
    $status = $_GET['status'] ?? 'all';

    // Obtener lista de técnicos
    $stmt = $db->query("SELECT id, full_name FROM users WHERE role = 'technician' AND active = 1 ORDER BY full_name");
    $technicians = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Construir la consulta base
    $query = "
        SELECT v.*, u.full_name as technician_name 
        FROM visits v
        INNER JOIN users u ON v.technician_id = u.id
        WHERE 1=1
    ";

    $params = [];

    // Aplicar filtros
    if ($view === 'week') {
        $weekStart = date('Y-m-d', strtotime('monday this week', strtotime($date)));
        $weekEnd = date('Y-m-d', strtotime('sunday this week', strtotime($date)));
        $query .= " AND v.visit_date BETWEEN :start_date AND :end_date";
        $params[':start_date'] = $weekStart;
        $params[':end_date'] = $weekEnd;
    } elseif ($view === 'month') {
        $monthStart = date('Y-m-01', strtotime($date));
        $monthEnd = date('Y-m-t', strtotime($date));
        $query .= " AND v.visit_date BETWEEN :start_date AND :end_date";
        $params[':start_date'] = $monthStart;
        $params[':end_date'] = $monthEnd;
    } else {
        $query .= " AND DATE(v.visit_date) = :date";
        $params[':date'] = $date;
    }

    if ($technician !== 'all') {
        $query .= " AND v.technician_id = :technician_id";
        $params[':technician_id'] = $technician;
    }

    if ($status !== 'all') {
        $query .= " AND v.status = :status";
        $params[':status'] = $status;
    }

    $query .= " ORDER BY v.visit_date ASC, v.visit_time ASC";

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $visits = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Iniciar buffer de salida
    ob_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    
    <!-- Estilos -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <!-- Encabezado y Controles -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold"><?php echo $page_title; ?></h1>
                <p class="text-gray-600">Gestiona y programa las visitas técnicas</p>
            </div>
            <div class="flex space-x-4">
                <?php if (!empty($_GET['technician']) || !empty($_GET['status'])): ?>
                    <button onclick="clearFilters()" 
                            class="px-4 py-2 text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 flex items-center">
                        <i class="fas fa-times mr-2"></i>
                        Limpiar filtros
                    </button>
                <?php endif; ?>
                <button onclick="showNewVisitModal()" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    <i class="fas fa-plus mr-2"></i>Nueva Visita
                </button>
            </div>
        </div>

        <!-- Filtros y Controles -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 mb-6">
            <!-- Vista y Fecha -->
            <div class="lg:col-span-4 grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Vista</label>
                    <select id="viewSelect" onchange="changeView(this.value)" 
                            class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="list" <?php echo $view === 'list' ? 'selected' : ''; ?>>Lista</option>
                        <option value="week" <?php echo $view === 'week' ? 'selected' : ''; ?>>Semana</option>
                        <option value="month" <?php echo $view === 'month' ? 'selected' : ''; ?>>Mes</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha</label>
                    <input type="date" id="dateSelect"
                           value="<?php echo $date; ?>" 
                           onchange="changeDate(this.value)"
                           class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <!-- Filtros -->
            <div class="lg:col-span-6 grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                    <input type="text" id="searchClient" 
                           placeholder="Buscar cliente..." 
                           class="w-full pl-10 p-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                    <select id="filterStatus" onchange="applyFilters()"
                            class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="all">Todos los estados</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pendientes</option>
                        <option value="in_route" <?php echo $status === 'in_route' ? 'selected' : ''; ?>>En Camino</option>
                        <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completadas</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Técnico</label>
                    <select id="filterTechnician" onchange="applyFilters()"
                            class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="all">Todos los técnicos</option>
                        <?php foreach ($technicians as $tech): ?>
                            <option value="<?php echo $tech['id']; ?>" 
                                    <?php echo $technician == $tech['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($tech['full_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Navegación -->
            <div class="lg:col-span-2 flex items-end justify-end space-x-2">
                <button onclick="goToDate('today')" 
                        class="px-4 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200">
                    Hoy
                </button>
                <div class="flex border rounded-lg">
                    <button onclick="goToDate('prev')" 
                            class="px-3 py-2 hover:bg-gray-100 rounded-l-lg">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button onclick="goToDate('next')" 
                            class="px-3 py-2 hover:bg-gray-100 rounded-r-lg border-l">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Vista Principal -->
        <div class="bg-white rounded-lg shadow-sm">
            <?php 
            switch($view) {
                case 'month':
                    include 'views/month_view.php';
                    break;
                case 'week':
                    include 'views/week_view.php';
                    break;
                default:
                    include 'views/list_view.php';
            }
            ?>
        </div>
    </div>

    <!-- Modales -->
    <?php 
    include 'modals/visit_form.php';
    include 'modals/visit_details.php';
    ?>

<script>
// Variables globales
let currentVisitId = null;

// Funciones de navegación y filtros (las que ya teníamos)
function changeView(view) {
    const url = new URL(window.location);
    url.searchParams.set('view', view);
    window.location.href = url.toString();
}

function changeDate(date) {
    const url = new URL(window.location);
    url.searchParams.set('date', date);
    window.location.href = url.toString();
}

function goToDate(direction) {
    const currentDate = new Date(document.getElementById('dateSelect').value);
    let newDate;

    switch(direction) {
        case 'today':
            newDate = new Date();
            break;
        case 'prev':
            newDate = new Date(currentDate);
            newDate.setDate(currentDate.getDate() - 1);
            break;
        case 'next':
            newDate = new Date(currentDate);
            newDate.setDate(currentDate.getDate() + 1);
            break;
    }

    changeDate(newDate.toISOString().split('T')[0]);
}

function applyFilters() {
    const status = document.getElementById('filterStatus').value;
    const techId = document.getElementById('filterTechnician').value;
    const url = new URL(window.location);

    if (status && status !== 'all') {
        url.searchParams.set('status', status);
    } else {
        url.searchParams.delete('status');
    }

    if (techId && techId !== 'all') {
        url.searchParams.set('technician', techId);
    } else {
        url.searchParams.delete('technician');
    }

    window.location.href = url.toString();
}

function clearFilters() {
    const url = new URL(window.location);
    url.searchParams.delete('status');
    url.searchParams.delete('technician');
    window.location.href = url.toString();
}

// Funciones de manejo de visitas
function showNewVisitModal() {
    const modal = document.getElementById('visitFormModal');
    if (modal) {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        // Limpiar el formulario
        document.getElementById('visitForm').reset();
        // Actualizar título
        document.getElementById('modalTitle').textContent = 'Nueva Visita';
    }
}

function editVisit(visitId) {
    fetch(`actions/get_visit_details.php?id=${visitId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                fillEditForm(data.visit);
                document.getElementById('visitFormModal').classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            } else {
                showNotification(data.error || 'Error al cargar la visita', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error al cargar la visita', 'error');
        });
}

function fillEditForm(visit) {
    const form = document.getElementById('visitForm');
    // Agregar campo hidden para el ID
    let idInput = form.querySelector('input[name="visit_id"]');
    if (!idInput) {
        idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'visit_id';
        form.appendChild(idInput);
    }
    idInput.value = visit.id;

    // Llenar el resto de los campos
    form.querySelector('[name="client_name"]').value = visit.client_name;
    form.querySelector('[name="contact_name"]').value = visit.contact_name;
    form.querySelector('[name="contact_phone"]').value = visit.contact_phone;
    form.querySelector('[name="address"]').value = visit.address;
    form.querySelector('[name="reference"]').value = visit.reference || '';
    form.querySelector('[name="location_url"]').value = visit.location_url || '';
    form.querySelector('[name="visit_date"]').value = visit.visit_date;
    form.querySelector('[name="visit_time"]').value = visit.visit_time;
    form.querySelector('[name="service_type"]').value = visit.service_type;
    form.querySelector('[name="technician_id"]').value = visit.technician_id;
    form.querySelector('[name="duration"]').value = visit.duration || '60';
    form.querySelector('[name="notes"]').value = visit.notes || '';

    // Actualizar título del modal
    document.getElementById('modalTitle').textContent = 'Editar Visita';
}

function showVisitDetails(visitId) {
    currentVisitId = visitId;
    fetch(`actions/get_visit_details.php?id=${visitId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const modal = document.getElementById('visitDetailsModal');
                const content = document.getElementById('visitDetailsContent');
                content.innerHTML = generateVisitDetailsHTML(data.visit);
                modal.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            } else {
                showNotification(data.error || 'Error al cargar los detalles', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error al cargar los detalles', 'error');
        });
}

function updateStatus(visitId, newStatus) {
    if (!confirm(`¿Está seguro de cambiar el estado a ${getStatusLabel(newStatus)}?`)) return;

    fetch('actions/update_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            visit_id: visitId,
            status: newStatus
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Estado actualizado exitosamente');
            location.reload();
        } else {
            showNotification(data.error || 'Error al actualizar el estado', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error al actualizar el estado', 'error');
    });
}

function deleteVisit(visitId) {
    if (!confirm('¿Está seguro de eliminar esta visita?')) return;

    fetch('actions/delete_visit.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            visit_id: visitId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Visita eliminada exitosamente');
            location.reload();
        } else {
            showNotification(data.error || 'Error al eliminar la visita', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error al eliminar la visita', 'error');
    });
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }
}

// Funciones de utilidad
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `fixed bottom-4 right-4 px-6 py-3 rounded-lg shadow-lg text-white ${
        type === 'success' ? 'bg-green-500' : 'bg-red-500'
    }`;
    notification.textContent = message;
    document.body.appendChild(notification);

    setTimeout(() => {
        notification.remove();
    }, 3000);
}

function generateVisitDetailsHTML(visit) {
    return `
        <div class="space-y-4">
            <div class="flex justify-between items-start">
                <div>
                    <div class="text-xl font-bold">
                        ${formatTime(visit.visit_time)}
                    </div>
                    <div class="text-gray-600">${visit.client_name}</div>
                    <div class="flex items-center mt-2 space-x-2">
                        <span class="px-2 py-1 bg-blue-50 text-blue-700 rounded-full text-sm">
                            <i class="fas fa-tools mr-1"></i>
                            ${visit.service_type}
                        </span>
                    </div>
                </div>
                <span class="px-3 py-1 rounded-full text-sm ${getStatusClass(visit.status)}">
                    ${getStatusLabel(visit.status)}
                </span>
            </div>

            <div class="space-y-3">
                <div class="flex items-center text-gray-600">
                    <i class="fas fa-user w-5"></i>
                    <span class="ml-2">${visit.technician_name}</span>
                </div>
                <div class="flex items-center text-gray-600">
                    <i class="fas fa-map-marker-alt w-5"></i>
                    <span class="ml-2">${visit.address}</span>
                </div>
                ${visit.contact_phone ? `
                    <a href="tel:${visit.contact_phone}" class="flex items-center text-blue-600">
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

                    <button onclick="editVisit(${visit.id})"
                            class="flex items-center justify-center bg-blue-500 text-white p-2 rounded">
                        <i class="fas fa-edit mr-2"></i>
                        Editar
                    </button>

                    <button onclick="deleteVisit(${visit.id})"
                            class="flex items-center justify-center bg-red-500 text-white p-2 rounded">
                        <i class="fas fa-trash mr-2"></i>
                        Eliminar
                    </button>
                </div>
            ` : ''}
        </div>
    `;
}

function formatTime(time) {
    if (!time) return '';
    return new Date(`2000-01-01T${time}`).toLocaleTimeString('es-ES', {
        hour: '2-digit',
        minute: '2-digit'
    });
}

function getStatusClass(status) {
    switch(status) {
        case 'completed': return 'bg-green-100 text-green-800';
        case 'in_route': return 'bg-yellow-100 text-yellow-800';
        case 'pending': return 'bg-blue-100 text-blue-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}

function getStatusLabel(status) {
    switch(status) {
        case 'completed': return 'Completada';
        case 'in_route': return 'En Camino';
        case 'pending': return 'Pendiente';
        default: return status;
    }
}

// Inicialización cuando el DOM está listo
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar Select2 si está disponible
    if (typeof $.fn.select2 !== 'undefined') {
        $('.select2').select2({
            width: '100%'
        });
    }

    // Inicializar búsqueda
    const searchInput = document.getElementById('searchClient');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(filterVisits, 300));
    }
});

// Función de utilidad para debounce
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function filterVisits() {
    const searchTerm = document.getElementById('searchClient').value.toLowerCase();
    document.querySelectorAll('.visit-item').forEach(visit => {
        const clientName = visit.querySelector('.client-name')?.textContent.toLowerCase() || '';
        const clientAddress = visit.querySelector('.client-address')?.textContent.toLowerCase() || '';
        const shouldShow = clientName.includes(searchTerm) || clientAddress.includes(searchTerm);
        visit.style.display = shouldShow ? '' : 'none';
    });
}

async function saveVisit(event) {
    event.preventDefault();
    const form = event.target;
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    try {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Verificando disponibilidad...';

        // Verificar disponibilidad primero
        const availabilityCheck = await validateAvailability();
        if (!availabilityCheck.success) {
            showNotification(availabilityCheck.error, 'error');
            return;
        }

        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Guardando...';
        const formData = new FormData(form);

        const response = await fetch('actions/save_visit.php', {
            method: 'POST',
            body: formData
        });

        // Verificar si la respuesta es válida
        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }

        let data;
        const responseText = await response.text();
        try {
            data = JSON.parse(responseText);
        } catch (e) {
            console.error('Respuesta del servidor:', responseText);
            throw new Error('Error al procesar la respuesta del servidor');
        }

        if (data.success) {
            showNotification(
                formData.get('visit_id') ? 'Visita actualizada exitosamente' : 'Visita creada exitosamente', 
                'success'
            );
            closeModal('visitFormModal');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            throw new Error(data.error || 'Error al guardar la visita');
        }
    } catch (error) {
        console.error('Error completo:', error);
        showNotification(error.message || 'Error al procesar la solicitud', 'error');
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    }
}

async function validateAvailability() {
    const form = document.getElementById('visitForm');
    const formData = new FormData(form);
    
    return fetch('../actions/check_visit_availability.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            technician_id: formData.get('technician_id'),
            visit_date: formData.get('visit_date'),
            visit_time: formData.get('visit_time'),
            duration: formData.get('duration'),
            visit_id: formData.get('visit_id')
        })
    })
    .then(response => response.json());
}

function setupRealTimeValidation() {
    const form = document.getElementById('visitForm');
    const fields = ['technician_id', 'visit_date', 'visit_time'];
    
    fields.forEach(field => {
        form.elements[field]?.addEventListener('change', async () => {
            // Solo validar si todos los campos necesarios están llenos
            if (fields.every(f => form.elements[f]?.value)) {
                try {
                    const check = await validateAvailability();
                    const feedback = document.getElementById('availabilityFeedback');
                    
                    if (check.success) {
                        feedback.className = 'text-sm text-green-600 mt-2';
                        feedback.innerHTML = '<i class="fas fa-check mr-1"></i>Horario disponible';
                    } else {
                        feedback.className = 'text-sm text-red-600 mt-2';
                        feedback.innerHTML = `<i class="fas fa-exclamation-triangle mr-1"></i>${check.error}`;
                    }
                } catch (error) {
                    console.error('Error en validación:', error);
                }
            }
        });
    });
}

document.addEventListener('DOMContentLoaded', setupRealTimeValidation);

</script>


</body>
</html>
<?php
    $content = ob_get_clean();
    require_once '../../includes/layout.php';
} catch (Exception $e) {
    error_log("Error en visits/index.php: " . $e->getMessage());
    
    // Si es una petición AJAX, devolver error en JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
    
    // Si no es AJAX, mostrar página de error
    ob_end_clean();
    require_once '../../includes/error_page.php';
}
?>