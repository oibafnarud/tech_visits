<?php
require_once '../includes/session_check.php';
require_once '../config/database.php';
require_once '../includes/utils.php';

$page_title = 'Gestión de Técnicos';
$current_page = 'technicians';

$database = new Database();
$db = $database->connect();

// Obtener todos los técnicos con estadísticas
$stmt = $db->query("
    SELECT 
        u.*,
        (SELECT COUNT(*) FROM visits v WHERE v.technician_id = u.id) as total_visits,
        (SELECT COUNT(*) FROM visits v WHERE v.technician_id = u.id AND v.status = 'completed') as completed_visits,
        (SELECT COUNT(*) FROM visits v 
         WHERE v.technician_id = u.id 
         AND v.visit_date = CURRENT_DATE
         AND v.status != 'completed') as today_pending
    FROM users u 
    WHERE u.role = 'technician'
    ORDER BY u.active DESC, u.full_name ASC
");
$technicians = $stmt->fetchAll(PDO::FETCH_ASSOC);

ob_start();
?>

<!-- Contenido principal -->
<div class="container mx-auto px-4 py-8">
    <div class="mb-8 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold"><?php echo $page_title; ?></h1>
            <p class="text-gray-600">Administra el equipo de técnicos y sus especialidades</p>
        </div>
        <button onclick="showNewTechnicianModal()" 
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            <i class="fas fa-plus mr-2"></i>Nuevo Técnico
        </button>
    </div>

    <!-- Grid de técnicos -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($technicians as $tech): ?>
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="p-6">
                    <!-- Cabecera -->
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-lg font-semibold">
                                <?php echo htmlspecialchars($tech['full_name']); ?>
                            </h3>
                            <p class="text-sm text-gray-500">
                                @<?php echo htmlspecialchars($tech['username']); ?>
                            </p>
                            <p class="text-sm text-gray-500">
                                <?php echo htmlspecialchars($tech['email'] ?? ''); ?>
                            </p>
                        </div>
                        <span class="px-2 py-1 text-sm rounded-full <?php 
                            echo $tech['active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; 
                        ?>">
                            <?php echo $tech['active'] ? 'Activo' : 'Inactivo'; ?>
                        </span>
                    </div>

                    <!-- Especialidades -->
                    <div class="mb-4">
                        <h4 class="text-sm font-medium text-gray-700 mb-2">Especialidades</h4>
                        <div class="flex flex-wrap gap-2">
                            <?php 
                            $specialties = json_decode($tech['specialties'] ?? '[]', true);
                            if (!empty($specialties)): 
                                foreach ($specialties as $specialty): 
                                    $label = str_replace('_', ' ', ucwords($specialty));
                            ?>
                                <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">
                                    <?php echo htmlspecialchars($label); ?>
                                </span>
                            <?php 
                                endforeach;
                            else:
                            ?>
                                <span class="text-sm text-gray-500">Sin especialidades asignadas</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Estadísticas -->
                    <div class="grid grid-cols-3 gap-4 mb-4">
                        <div class="text-center p-3 bg-gray-50 rounded-lg">
                            <p class="text-2xl font-bold text-blue-600">
                                <?php echo $tech['total_visits']; ?>
                            </p>
                            <p class="text-xs text-gray-600">Total Visitas</p>
                        </div>
                        <div class="text-center p-3 bg-gray-50 rounded-lg">
                            <p class="text-2xl font-bold text-green-600">
                                <?php echo $tech['completed_visits']; ?>
                            </p>
                            <p class="text-xs text-gray-600">Completadas</p>
                        </div>
                        <div class="text-center p-3 bg-gray-50 rounded-lg">
                            <p class="text-2xl font-bold <?php echo $tech['today_pending'] > 0 ? 'text-yellow-600' : 'text-gray-600'; ?>">
                                <?php echo $tech['today_pending']; ?>
                            </p>
                            <p class="text-xs text-gray-600">Pendientes Hoy</p>
                        </div>
                    </div>

                    <!-- Acciones -->
                    <div class="flex space-x-2">
                        <button onclick="editTechnician(<?php echo $tech['id']; ?>)"
                                class="flex-1 px-3 py-2 text-sm bg-blue-50 text-blue-600 rounded hover:bg-blue-100">
                            <i class="fas fa-edit mr-1"></i>Editar
                        </button>
                        
                        <?php if ($tech['active']): ?>
                            <button onclick="toggleTechnicianStatus(<?php echo $tech['id']; ?>, false)"
                                    class="flex-1 px-3 py-2 text-sm bg-yellow-50 text-yellow-600 rounded hover:bg-yellow-100">
                                <i class="fas fa-ban mr-1"></i>Desactivar
                            </button>
                        <?php else: ?>
                            <button onclick="toggleTechnicianStatus(<?php echo $tech['id']; ?>, true)"
                                    class="flex-1 px-3 py-2 text-sm bg-green-50 text-green-600 rounded hover:bg-green-100">
                                <i class="fas fa-check mr-1"></i>Activar
                            </button>
                        <?php endif; ?>

                        <button onclick="showAvailability(<?php echo $tech['id']; ?>, '<?php echo htmlspecialchars($tech['full_name']); ?>')"
                                class="px-3 py-2 text-sm bg-gray-50 text-gray-600 rounded hover:bg-gray-100"
                                title="Ver disponibilidad">
                            <i class="fas fa-calendar-alt"></i>
                        </button>

                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Modales -->
<?php include 'modals/technician_form.php'; ?>
<?php include 'views/technician_availability.php'; ?>

<!-- Scripts específicos -->
<script>

let selectedSpecialties = new Set();
let currentTechData = null;
let currentDate = new Date();
let currentView = 'week';

function showNewTechnicianModal() {
    selectedSpecialties.clear();
    updateSpecialtiesDisplay();
    document.getElementById('technicianId').value = '';
    document.getElementById('technicianForm').reset();
    document.getElementById('modalTitle').textContent = 'Nuevo Técnico';
    document.getElementById('passwordFields').style.display = 'block';
    document.getElementById('technicianModal').classList.remove('hidden');
}

function editTechnician(techId) {
    fetch(`actions/technician_actions.php?action=get_technician&id=${techId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                fillTechnicianForm(data.technician);
                document.getElementById('technicianModal').classList.remove('hidden');
            } else {
                showNotification(data.error || 'Error al cargar los datos del técnico', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error al cargar los datos del técnico', 'error');
        });
}

function toggleTimeInputs(checkbox) {
    const timeInputs = document.getElementById('timeInputs');
    const startTime = document.querySelector('input[name="start_time"]');
    const endTime = document.querySelector('input[name="end_time"]');
    
    timeInputs.style.display = checkbox.checked ? 'block' : 'none';

    if (checkbox.checked) {
        startTime.required = true;
        endTime.required = true;
        // Establecer valores por defecto si están vacíos
        if (!startTime.value) startTime.value = '08:00';
        if (!endTime.value) endTime.value = '17:00';
    } else {
        startTime.required = false;
        endTime.required = false;
        startTime.value = '';
        endTime.value = '';
    }
}

function fillTechnicianForm(technician) {
    const form = document.getElementById('technicianForm');
    form.reset();
    
    document.getElementById('technicianId').value = technician.id;
    document.getElementById('modalTitle').textContent = 'Editar Técnico';
    document.getElementById('passwordFields').style.display = 'none';

    // Llenar campos básicos
    const fields = ['username', 'full_name', 'email', 'phone', 'work_start', 'work_end'];
    fields.forEach(field => {
        if (form.elements[field] && technician[field] != null) {
            form.elements[field].value = technician[field];
        }
    });

    // Manejar especialidades
    selectedSpecialties = new Set();
    if (technician.specialties) {
        try {
            const specs = JSON.parse(technician.specialties);
            if (Array.isArray(specs)) {
                specs.forEach(spec => selectedSpecialties.add(spec));
            }
        } catch (e) {
            console.error('Error parsing specialties:', e);
        }
    }
    updateSpecialtiesDisplay();
}

function toggleTechnicianStatus(techId, active) {
    if (!confirm(`¿Está seguro de ${active ? 'activar' : 'desactivar'} este técnico?`)) return;

    fetch(`actions/technician_actions.php?action=toggle_status&id=${techId}&status=${active ? 1 : 0}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(`Técnico ${active ? 'activado' : 'desactivado'} exitosamente`, 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showNotification(data.error || 'Error al actualizar el estado', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error al actualizar el estado', 'error');
        });
}

function addSpecialty() {
    const select = document.getElementById('specialtySelect');
    const specialty = select.value;
    
    if (specialty && !selectedSpecialties.has(specialty)) {
        selectedSpecialties.add(specialty);
        updateSpecialtiesDisplay();
        select.value = '';
    }
}

function removeSpecialty(specialty) {
    selectedSpecialties.delete(specialty);
    updateSpecialtiesDisplay();
}

function updateSpecialtiesDisplay() {
    const container = document.getElementById('selectedSpecialties');
    const input = document.getElementById('specialtiesInput');
    
    container.innerHTML = Array.from(selectedSpecialties).map(specialty => `
        <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-sm flex items-center">
            ${specialty.replace('_', ' ')}
            <button type="button" onclick="removeSpecialty('${specialty}')" 
                    class="ml-2 text-blue-600 hover:text-blue-800">
                <i class="fas fa-times"></i>
            </button>
        </span>
    `).join('');

    input.value = JSON.stringify(Array.from(selectedSpecialties));
}

function showAvailability(technicianId, technicianName) {
    // Actualizar nombre del técnico en el modal
    document.getElementById('technicianName').textContent = technicianName;
    
    // Mostrar modal y cargar datos
    document.getElementById('availabilityModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    
    // Establecer fecha actual si no está establecida
    if (!currentDate) {
        currentDate = new Date();
    }
    
    // Cargar datos iniciales
    loadAvailabilityData(technicianId);
}

function hideAvailabilityModal() {
    document.getElementById('availabilityModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
    currentTechData = null;
}

document.getElementById('availabilityModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        hideAvailabilityModal();
    }
});

function loadAvailabilityData(technicianId) {
    const container = document.getElementById('availabilityContent');
    
    // Mostrar loader
    container.innerHTML = `
        <div class="col-span-7 flex items-center justify-center py-12">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        </div>
    `;

    const start = getStartDate();
    const end = getEndDate();
    
    console.log('Cargando datos:', { technicianId, start, end });

    // Agregar un timestamp para evitar el caché
    const timestamp = new Date().getTime();
    
    fetch(`actions/get_availability.php?technician_id=${technicianId}&start=${start}&end=${end}&t=${timestamp}`)
        .then(response => response.json())
        .then(data => {
            console.log('Datos recibidos:', data);
            if (data.success) {
                currentTechData = data.data;
                renderAvailability();
            } else {
                container.innerHTML = `
                    <div class="col-span-7 text-center text-red-600 py-12">
                        ${data.error || 'Error al cargar la disponibilidad'}
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            container.innerHTML = `
                <div class="col-span-7 text-center text-red-600 py-12">
                    Error al cargar la disponibilidad
                </div>
            `;
        });
}

function renderWeekView(container) {
    console.log('Rendering week view with data:', currentTechData);
    
    const days = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
    
    // Grid principal
    const grid = document.createElement('div');
    grid.className = 'grid grid-cols-8 gap-1';

    // Columna de horas
    const hoursColumn = document.createElement('div');
    hoursColumn.className = 'space-y-1';
    hoursColumn.innerHTML = '<div class="h-12"></div>'; // Espacio para headers
    
    // Generar columna de horas
    for (let hour = 8; hour <= 18; hour++) {
        const time12h = new Date(2000, 0, 1, hour, 0).toLocaleTimeString('es-ES', {
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        });
        hoursColumn.innerHTML += `
            <div class="h-24 text-sm text-gray-500 pt-2 text-right pr-2 sticky left-0 bg-white">
                ${time12h}
            </div>
        `;
    }
    grid.appendChild(hoursColumn);

    // Generar columnas de días
    const startDate = new Date(currentTechData.range.start);
    for (let i = 0; i < 7; i++) {
        const currentDate = new Date(startDate);
        currentDate.setDate(startDate.getDate() + i);
        const dateStr = currentDate.toISOString().split('T')[0];
        
        const dayColumn = document.createElement('div');
        dayColumn.className = 'space-y-1';

        // Header del día
        const isToday = currentDate.toDateString() === new Date().toDateString();
        dayColumn.innerHTML = `
            <div class="h-12 text-center ${isToday ? 'bg-blue-50 rounded' : ''}">
                <div class="font-medium">${days[currentDate.getDay()]}</div>
                <div class="text-sm text-gray-500">${currentDate.getDate()}</div>
            </div>
        `;

        // Generar slots de hora
        for (let hour = 8; hour <= 18; hour++) {
            const timeSlot = document.createElement('div');
            timeSlot.className = 'h-24 border rounded p-2 relative group hover:bg-gray-50';

            // Verificar disponibilidad regular
            const isRegularlyAvailable = currentTechData.regular.some(schedule => {
                const startHour = parseInt(schedule.start_time);
                const endHour = parseInt(schedule.end_time);
                return schedule.day_of_week === ((currentDate.getDay() + 1) % 7 || 7) && 
                       hour >= startHour && 
                       hour < endHour;
            });

            if (isRegularlyAvailable) {
                timeSlot.classList.add('bg-blue-50');
            }

            // Verificar excepciones
            const exceptionForDay = currentTechData.exceptions.find(exc => 
                exc.exception_date === dateStr
            );
            
            if (exceptionForDay) {
                // Si es no disponible o no tiene horario específico
                if (!exceptionForDay.is_available) {
                    timeSlot.classList.add('bg-red-50');
                    timeSlot.innerHTML += `
                        <div class="text-xs text-red-600">
                            ${exceptionForDay.reason || 'No disponible'}
                        </div>
                    `;
                } else {
                    // Si tiene horario específico
                    const exceptionStartHour = parseInt(exceptionForDay.start_time);
                    const exceptionEndHour = parseInt(exceptionForDay.end_time);
                    
                    if (hour >= exceptionStartHour && hour < exceptionEndHour) {
                        timeSlot.classList.add('bg-blue-50');
                        timeSlot.innerHTML += `
                            <div class="text-xs text-blue-600">
                                Disponible
                            </div>
                        `;
                    } else {
                        timeSlot.classList.add('bg-red-50');
                        timeSlot.innerHTML += `
                            <div class="text-xs text-red-600">
                                No disponible
                            </div>
                        `;
                    }
                }
            }

            // Buscar visitas en este horario
            const visitsInSlot = currentTechData.visits.filter(visit => {
                const visitHour = parseInt(visit.visit_time.split(':')[0]);
                return visit.visit_date === dateStr && visitHour === hour;
            });

            if (visitsInSlot.length > 0) {
                visitsInSlot.forEach(visit => {
                    timeSlot.innerHTML += `
                        <div class="text-xs bg-green-100 text-green-800 p-1 rounded mb-1">
                            ${visit.visit_time.substr(0, 5)} - ${visit.client_name}
                        </div>
                    `;
                });
            }

            dayColumn.appendChild(timeSlot);
        }

        grid.appendChild(dayColumn);
    }

    container.innerHTML = '';
    container.appendChild(grid);
}
            

function renderException(exception) {
    return `
        <div class="flex justify-between items-center p-4 bg-gray-50 rounded-lg">
            <div>
                <div class="font-medium">
                    ${new Date(exception.exception_date).toLocaleDateString()}
                </div>
                ${exception.is_available ? `
                    <div class="text-sm text-gray-600">
                        ${exception.start_time_12h} - ${exception.end_time_12h}
                    </div>
                ` : `
                    <div class="text-sm text-red-600">No Disponible</div>
                `}
                ${exception.reason ? `
                    <div class="text-sm text-gray-500">
                        ${exception.reason}
                    </div>
                ` : ''}
            </div>
            <button onclick="deleteException(${exception.id})"
                    class="text-red-600 hover:text-red-700">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `;
}

function deleteException(exceptionId) {
    if (!confirm('¿Está seguro de eliminar esta excepción?')) {
        return;
    }

    console.log('Eliminando excepción:', exceptionId);

    fetch('actions/delete_exception.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ exception_id: exceptionId })
    })
    .then(response => {
        console.log('Respuesta del servidor:', response);
        return response.json();
    })
    .then(data => {
        console.log('Datos de respuesta:', data);
        if (data.success) {
            showNotification('Excepción eliminada exitosamente', 'success');
            // Forzar recarga inmediata
            if (currentTechData && currentTechData.id) {
                loadAvailabilityData(currentTechData.id);
            } else {
                window.location.reload();
            }
        } else {
            showNotification(data.error || 'Error al eliminar la excepción', 'error');
        }
    })
    .catch(error => {
        console.error('Error en la eliminación:', error);
        showNotification('Error al eliminar la excepción', 'error');
    });
}

function renderExceptions(exceptions) {
    const container = document.getElementById('exceptionsContainer');
    if (!exceptions || exceptions.length === 0) {
        container.innerHTML = '<div class="text-center text-gray-500 py-4">No hay excepciones programadas</div>';
        return;
    }

    container.innerHTML = exceptions.map(exception => `
        <div class="flex justify-between items-center p-4 bg-gray-50 rounded-lg">
            <div>
                <div class="font-medium">
                    ${new Date(exception.exception_date).toLocaleDateString()}
                </div>
                ${exception.is_available ? `
                    <div class="text-sm text-gray-600">
                        ${exception.start_time_12h} - ${exception.end_time_12h}
                    </div>
                ` : `
                    <div class="text-sm text-red-600">No Disponible</div>
                `}
                ${exception.reason ? `
                    <div class="text-sm text-gray-500">
                        ${exception.reason}
                    </div>
                ` : ''}
            </div>
            <button onclick="deleteException(${exception.id})" 
                    class="text-red-600 hover:text-red-700 p-2">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `).join('');
}

function renderAvailability() {
    const container = document.getElementById('availabilityContent');
    updateDateRange();

    if (currentView === 'week') {
        renderWeekView(container);
    } else {
        renderMonthView(container);
    }
}

function renderMonthView(container) {
    const days = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
    const firstDay = new Date(currentTechData.range.start);
    const lastDay = new Date(currentTechData.range.end);
    
    const grid = document.createElement('div');
    grid.className = 'grid grid-cols-7 gap-2';

    // Headers de días
    days.forEach(day => {
        const header = document.createElement('div');
        header.className = 'text-center font-medium py-2 px-4 bg-gray-50';
        header.textContent = day;
        grid.appendChild(header);
    });

    // Calcular el primer día del mes
    const firstDayOfMonth = new Date(firstDay.getFullYear(), firstDay.getMonth(), 1);
    const startingDay = firstDayOfMonth.getDay();

    // Días vacíos antes del primer día del mes
    for (let i = 0; i < startingDay; i++) {
        const emptyDay = document.createElement('div');
        emptyDay.className = 'p-2 h-32 bg-gray-50 rounded-lg';
        grid.appendChild(emptyDay);
    }

    // Días del mes
    for (let date = new Date(firstDay); date <= lastDay; date.setDate(date.getDate() + 1)) {
        const dayCell = document.createElement('div');
        const dateStr = date.toISOString().split('T')[0];
        const isToday = date.toDateString() === new Date().toDateString();
        
        dayCell.className = `border rounded-lg p-2 h-32 overflow-y-auto ${isToday ? 'bg-blue-50' : 'hover:bg-gray-50'}`;

        // Verificar si hay excepciones para este día
        const hasException = currentTechData.exceptions.find(exc => exc.exception_date === dateStr);
        if (hasException) {
            dayCell.classList.add('bg-red-50');
        }

        // Obtener visitas del día
        const dayVisits = currentTechData.visits.filter(v => v.visit_date === dateStr);
        
        // Verificar disponibilidad regular
        const dayOfWeek = date.getDay();
        const hasRegularSchedule = currentTechData.regular.some(schedule => 
            schedule.day_of_week === ((dayOfWeek + 1) % 7 || 7)
        );

        if (hasRegularSchedule && !hasException) {
            dayCell.classList.add('bg-blue-50');
        }

        // Contenido del día
        dayCell.innerHTML = `
            <div class="flex justify-between items-start mb-1">
                <span class="${isToday ? 'font-bold text-blue-600' : 'text-gray-600'}">${date.getDate()}</span>
                ${dayVisits.length > 0 ? `
                    <span class="text-xs px-2 py-1 bg-green-100 text-green-800 rounded-full">
                        ${dayVisits.length}
                    </span>
                ` : ''}
            </div>
            <div class="space-y-1">
                ${hasException ? `
                    <div class="text-xs text-red-600 font-medium">
                        ${hasException.reason || 'No disponible'}
                    </div>
                ` : ''}
                ${dayVisits.map(visit => `
                    <div class="text-xs bg-green-100 text-green-800 p-1 rounded truncate" 
                         title="${visit.client_name}">
                        <div class="font-medium">${visit.visit_time.substr(0, 5)}</div>
                        <div class="truncate">${visit.client_name}</div>
                    </div>
                `).join('')}
            </div>
        `;

        grid.appendChild(dayCell);
    }

    // Calcular y agregar días vacíos al final si es necesario
    const totalCells = grid.children.length;
    const cellsNeeded = Math.ceil(totalCells / 7) * 7;
    
    for (let i = totalCells; i < cellsNeeded; i++) {
        const emptyDay = document.createElement('div');
        emptyDay.className = 'p-2 h-32 bg-gray-50 rounded-lg';
        grid.appendChild(emptyDay);
    }

    container.innerHTML = '';
    container.appendChild(grid);
}

function navigateDate(direction) {
    const offset = currentView === 'week' ? 7 : 30;
    currentDate = new Date(currentDate.setDate(
        currentDate.getDate() + (direction === 'prev' ? -offset : offset)
    ));
    if (currentTechData) {
        loadAvailabilityData(currentTechData.id);
    }
}

function updateDateRange() {
    const element = document.getElementById('currentDateRange');
    if (currentView === 'week') {
        const start = new Date(currentTechData.range.start);
        const end = new Date(currentTechData.range.end);
        element.textContent = `${start.toLocaleDateString()} - ${end.toLocaleDateString()}`;
    } else {
        element.textContent = currentDate.toLocaleDateString('es-ES', { 
            month: 'long', 
            year: 'numeric' 
        });
    }
}

function getStartDate() {
    const date = new Date(currentDate);
    if (currentView === 'week') {
        date.setDate(date.getDate() - date.getDay());
    } else {
        date.setDate(1);
    }
    return date.toISOString().split('T')[0];
}

function getEndDate() {
    const date = new Date(currentDate);
    if (currentView === 'week') {
        date.setDate(date.getDate() - date.getDay() + 6);
    } else {
        date.setMonth(date.getMonth() + 1);
        date.setDate(0);
    }
    return date.toISOString().split('T')[0];
}

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

function saveTechnician(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    
    // Validar contraseña si es nuevo técnico
    if (!formData.get('id') || formData.get('password')) {
        if (formData.get('password') !== formData.get('password_confirm')) {
            showNotification('Las contraseñas no coinciden', 'error');
            return;
        }
    }

    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Guardando...';

    fetch('actions/technician_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(
                formData.get('id') ? 'Técnico actualizado exitosamente' : 'Técnico creado exitosamente', 
                'success'
            );
            document.getElementById('technicianModal').classList.add('hidden');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showNotification(data.error || 'Error al guardar el técnico', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error al guardar el técnico', 'error');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
}
</script>

<?php
$content = ob_get_clean();
require_once '../includes/layout.php';
?>