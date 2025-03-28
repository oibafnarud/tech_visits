<?php
require_once '../includes/session_check.php';
require_once '../config/database.php';
require_once '../includes/utils.php';

$page_title = 'Gestión de Disponibilidad';
$current_page = 'availability';

$database = new Database();
$db = $database->connect();

// Obtener lista de técnicos
$stmt = $db->query("SELECT id, full_name FROM users WHERE role = 'technician' AND active = 1 ORDER BY full_name");
$technicians = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener técnico seleccionado o el primero de la lista
$selected_technician = isset($_GET['technician_id']) ? $_GET['technician_id'] : ($technicians[0]['id'] ?? null);

// Obtener disponibilidad del técnico seleccionado
$availability = [];
if ($selected_technician) {
    $stmt = $db->prepare("
        SELECT * FROM technician_availability 
        WHERE technician_id = ? 
        ORDER BY day_of_week, start_time
    ");
    $stmt->execute([$selected_technician]);
    $availability = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Obtener excepciones
$exceptions = [];
if ($selected_technician) {
    $stmt = $db->prepare("
        SELECT * FROM availability_exceptions 
        WHERE technician_id = ? 
        AND exception_date >= CURRENT_DATE
        ORDER BY exception_date
    ");
    $stmt->execute([$selected_technician]);
    $exceptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

ob_start();
?>

<!-- Contenido principal -->
<div class="container mx-auto px-4 py-8">
    <div class="mb-8 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold"><?php echo $page_title; ?></h1>
            <p class="text-gray-600">Administra los horarios y disponibilidad de los técnicos</p>
        </div>
        <select id="technicianSelect" onchange="changeTechnician(this.value)" 
                class="border rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            <?php foreach ($technicians as $tech): ?>
                <option value="<?php echo $tech['id']; ?>" 
                        <?php echo $selected_technician == $tech['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($tech['full_name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Horario Regular -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold mb-4">Horario Regular</h2>
            <form id="regularScheduleForm" onsubmit="saveRegularSchedule(event)">
                <input type="hidden" name="technician_id" value="<?php echo $selected_technician; ?>">
                
                <?php
                $days = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
                foreach ($days as $index => $day):
                    $dayNumber = $index + 1;
                    $daySchedules = array_filter($availability, fn($a) => $a['day_of_week'] == $dayNumber);
                ?>
                    <div class="mb-4 pb-4 border-b last:border-0">
                        <div class="flex justify-between items-center mb-2">
                            <label class="font-medium"><?php echo $day; ?></label>
                            <button type="button" onclick="addTimeSlot(<?php echo $dayNumber; ?>)"
                                    class="text-blue-600 hover:text-blue-700">
                                <i class="fas fa-plus mr-1"></i>Agregar horario
                            </button>
                        </div>
                        
                        <div id="slots-<?php echo $dayNumber; ?>" class="space-y-2">
                            <?php if (!empty($daySchedules)): ?>
                                <?php foreach ($daySchedules as $schedule): ?>
                                    <div class="flex items-center space-x-2">
                                        <input type="time" name="start_time[<?php echo $dayNumber; ?>][]" 
                                               value="<?php echo $schedule['start_time']; ?>"
                                               class="border rounded p-2">
                                        <span>a</span>
                                        <input type="time" name="end_time[<?php echo $dayNumber; ?>][]"
                                               value="<?php echo $schedule['end_time']; ?>"
                                               class="border rounded p-2">
                                        <button type="button" onclick="removeTimeSlot(this)"
                                                class="text-red-600 hover:text-red-700">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <button type="submit" class="mt-4 w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                    <i class="fas fa-save mr-2"></i>Guardar Horario
                </button>
            </form>
        </div>

        <!-- Excepciones -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-semibold">Excepciones</h2>
                <button onclick="showAddExceptionModal()" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    <i class="fas fa-plus mr-2"></i>Nueva Excepción
                </button>
            </div>

            <div class="space-y-4">
                <?php if (empty($exceptions)): ?>
                    <p class="text-gray-500 text-center py-4">No hay excepciones programadas</p>
                <?php else: ?>
                    <?php foreach ($exceptions as $exception): ?>
                        <div class="flex justify-between items-center p-4 bg-gray-50 rounded-lg">
                            <div>
                                <div class="font-medium">
                                    <?php echo date('d/m/Y', strtotime($exception['exception_date'])); ?>
                                </div>
                                <?php if ($exception['is_available']): ?>
                                    <div class="text-sm text-gray-600">
                                        <?php 
                                        echo date('H:i', strtotime($exception['start_time'])) . ' a ' . 
                                             date('H:i', strtotime($exception['end_time']));
                                        ?>
                                    </div>
                                <?php else: ?>
                                    <div class="text-sm text-red-600">No Disponible</div>
                                <?php endif; ?>
                                <?php if ($exception['reason']): ?>
                                    <div class="text-sm text-gray-500">
                                        <?php echo htmlspecialchars($exception['reason']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <button onclick="deleteException(<?php echo $exception['id']; ?>)"
                                    class="text-red-600 hover:text-red-700">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal para agregar excepción -->
<div id="exceptionModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="fixed top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-md">
        <div class="bg-white rounded-lg shadow-xl mx-4">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold">Nueva Excepción</h3>
                    <button onclick="hideExceptionModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <form id="exceptionForm" onsubmit="saveException(event)">
                    <input type="hidden" name="technician_id" value="<?php echo $selected_technician; ?>">
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Fecha</label>
                            <input type="date" name="exception_date" required
                                   min="<?php echo date('Y-m-d'); ?>"
                                   class="w-full p-2 border rounded">
                        </div>

                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" name="is_available" value="1" 
                                       onchange="toggleTimeInputs(this)"
                                       class="rounded border-gray-300 text-blue-600">
                                <span class="ml-2">Disponible en horario específico</span>
                            </label>
                        </div>

                        <div id="timeInputs" class="hidden space-y-4">
                            <div>
                                <label class="block text-sm font-medium mb-1">Hora Inicio</label>
                                <input type="time" name="start_time" class="w-full p-2 border rounded">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium mb-1">Hora Fin</label>
                                <input type="time" name="end_time" class="w-full p-2 border rounded">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">Motivo</label>
                            <input type="text" name="reason"
                                   class="w-full p-2 border rounded"
                                   placeholder="Ej: Cita médica, Vacaciones, etc.">
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" onclick="hideExceptionModal()"
                                class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded">
                            Cancelar
                        </button>
                        <button type="submit"
                                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                            Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once '../includes/layout.php';
?>

<script>
// Aquí va todo el JavaScript que ya teníamos antes...
function changeTechnician(technicianId) {
    window.location.href = `?technician_id=${technicianId}`;
}

function addTimeSlot(dayNumber) {
    const container = document.getElementById(`slots-${dayNumber}`);
    const slot = document.createElement('div');
    slot.className = 'flex items-center space-x-2';
    slot.innerHTML = `
        <input type="time" name="start_time[${dayNumber}][]" class="border rounded p-2">
        <span>a</span>
        <input type="time" name="end_time[${dayNumber}][]" class="border rounded p-2">
        <button type="button" onclick="removeTimeSlot(this)" class="text-red-600 hover:text-red-700">
            <i class="fas fa-trash"></i>
        </button>
    `;
    container.appendChild(slot);
}

function removeTimeSlot(button) {
    button.closest('div').remove();
}

function saveRegularSchedule(event) {
    event.preventDefault();
    const form = event.target;
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Guardando...';

    fetch('actions/save_availability.php', {
        method: 'POST',
        body: new FormData(form)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Horario guardado exitosamente', 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showNotification(data.error || 'Error al guardar el horario', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error al guardar el horario', 'error');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
}

function showAddExceptionModal() {
    document.getElementById('exceptionModal').classList.remove('hidden');
}

function hideExceptionModal() {
    document.getElementById('exceptionModal').classList.add('hidden');
    document.getElementById('exceptionForm').reset();
    document.getElementById('timeInputs').classList.add('hidden');
}

function toggleTimeInputs(checkbox) {
    const timeInputs = document.getElementById('timeInputs');
    timeInputs.style.display = checkbox.checked ? 'block' : 'none';

    const startTime = document.querySelector('input[name="start_time"]');
    const endTime = document.querySelector('input[name="end_time"]');
    
    if (checkbox.checked) {
        startTime.required = true;
        endTime.required = true;
    } else {
        startTime.required = false;
        endTime.required = false;
    }
}

function saveException(event) {
    event.preventDefault();
    const form = event.target;
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Guardando...';

    fetch('actions/save_exception.php', {
        method: 'POST',
        body: new FormData(form)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Excepción guardada exitosamente', 'success');
            hideExceptionModal();
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showNotification(data.error || 'Error al guardar la excepción', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error al guardar la excepcion');

    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
}

function deleteException(exceptionId) {
    if (!confirm('¿Está seguro de eliminar esta excepción?')) {
        return;
    }

    fetch('actions/delete_exception.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ exception_id: exceptionId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Excepción eliminada exitosamente', 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showNotification(data.error || 'Error al eliminar la excepción', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error al eliminar la excepción', 'error');
    });
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

// Cerrar modal al hacer clic fuera
document.getElementById('exceptionModal').addEventListener('click', function(e) {
    if (e.target === this) {
        hideExceptionModal();
    }
});
</script>