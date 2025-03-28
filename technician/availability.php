<?php
require_once '../includes/session_check.php';
require_once '../config/database.php';

$database = new Database();
$db = $database->connect();

// Obtener horarios actuales
$stmt = $db->prepare("
    SELECT * FROM technician_availability 
    WHERE technician_id = :technician_id 
    ORDER BY day_of_week, start_time
");
$stmt->execute([':technician_id' => $_SESSION['user_id']]);
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mx-auto px-4 py-8">
    <h2 class="text-2xl font-bold mb-6">Mi Disponibilidad</h2>
    
    <!-- Horario Semanal -->
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <h3 class="text-lg font-semibold mb-4">Horario Regular</h3>
        
        <form id="scheduleForm" class="space-y-4">
            <?php
            $days = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
            foreach ($days as $index => $day):
                $daySchedules = array_filter($schedules, fn($s) => $s['day_of_week'] == $index + 1);
            ?>
            <div class="border-b pb-4">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="font-medium"><?php echo $day; ?></h4>
                    <button type="button" onclick="addTimeSlot(<?php echo $index + 1; ?>)"
                            class="text-blue-600 hover:text-blue-800">
                        <i class="fas fa-plus"></i> Agregar horario
                    </button>
                </div>
                
                <div id="slots-<?php echo $index + 1; ?>" class="space-y-2">
                    <?php foreach ($daySchedules as $schedule): ?>
                    <div class="flex items-center space-x-2">
                        <input type="time" name="start_time[]" value="<?php echo $schedule['start_time']; ?>"
                               class="border rounded p-2">
                        <span>a</span>
                        <input type="time" name="end_time[]" value="<?php echo $schedule['end_time']; ?>"
                               class="border rounded p-2">
                        <button type="button" onclick="removeTimeSlot(this)"
                                class="text-red-600 hover:text-red-800 p-2">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
            
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                Guardar Cambios
            </button>
        </form>
    </div>
    
    <!-- Excepciones -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold mb-4">Excepciones</h3>
        
        <form id="exceptionForm" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-2">Fecha</label>
                    <input type="date" name="exception_date" required
                           min="<?php echo date('Y-m-d'); ?>"
                           class="w-full border rounded p-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">Desde</label>
                    <input type="time" name="start_time" required
                           class="w-full border rounded p-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">Hasta</label>
                    <input type="time" name="end_time" required
                           class="w-full border rounded p-2">
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium mb-2">Motivo</label>
                <input type="text" name="reason" required
                       class="w-full border rounded p-2"
                       placeholder="Ej: Cita médica, Vacaciones, etc.">
            </div>
            
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                Agregar Excepción
            </button>
        </form>
        
        <!-- Lista de excepciones -->
        <div class="mt-6">
            <h4 class="font-medium mb-4">Excepciones Programadas</h4>
            <div class="space-y-2">
                <!-- Se llena dinámicamente -->
            </div>
        </div>
    </div>
</div>

<script>
function addTimeSlot(dayIndex) {
    const container = document.getElementById(`slots-${dayIndex}`);
    const slot = document.createElement('div');
    slot.className = 'flex items-center space-x-2';
    slot.innerHTML = `
        <input type="time" name="start_time[]" class="border rounded p-2">
        <span>a</span>
        <input type="time" name="end_time[]" class="border rounded p-2">
        <button type="button" onclick="removeTimeSlot(this)"
                class="text-red-600 hover:text-red-800 p-2">
            <i class="fas fa-trash"></i>
        </button>
    `;
    container.appendChild(slot);
}

function removeTimeSlot(button) {
    button.closest('div').remove();
}

// Agregar lógica para guardar cambios y manejar excepciones
</script>