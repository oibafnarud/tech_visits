<?php
require_once '../includes/session_check.php';
require_once '../config/database.php';

$database = new Database();
$db = $database->connect();

// Obtener lista de técnicos activos
$stmt = $db->query("SELECT id, full_name FROM users WHERE role = 'technician' AND active = 1 ORDER BY full_name");
$technicians = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $stmt = $db->prepare("
            INSERT INTO visits (
                client_name, contact_name, contact_phone, address, 
                reference, location_url, visit_date, visit_time,
                service_type, technician_id, notes, status
            ) VALUES (
                :client_name, :contact_name, :contact_phone, :address,
                :reference, :location_url, :visit_date, :visit_time,
                :service_type, :technician_id, :notes, 'pending'
            )
        ");
        
        $result = $stmt->execute([
            ':client_name' => $_POST['client_name'],
            ':contact_name' => $_POST['contact_name'],
            ':contact_phone' => $_POST['contact_phone'],
            ':address' => $_POST['address'],
            ':reference' => $_POST['reference'],
            ':location_url' => $_POST['location_url'],
            ':visit_date' => $_POST['visit_date'],
            ':visit_time' => $_POST['visit_time'],
            ':service_type' => $_POST['service_type'],
            ':technician_id' => $_POST['technician_id'],
            ':notes' => $_POST['notes']
        ]);

        if ($result) {
            $success = "Visita programada exitosamente";
        }
    } catch(PDOException $e) {
        $error = "Error al programar la visita: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Visita</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <nav class="bg-blue-600 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <a href="dashboard.php" class="text-white hover:text-gray-200">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
                <h1 class="text-xl font-bold">Programar Nueva Visita</h1>
            </div>
            <div class="flex items-center space-x-4">
                <span><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                <a href="../logout.php" class="hover:underline">Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-8">
        <?php if (isset($success)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow-md p-6">
            <form method="POST" class="space-y-6">
                <!-- Información del Cliente -->
                <div class="border-b pb-6">
                    <h2 class="text-xl font-bold mb-4">Información del Cliente</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 mb-2">Nombre del Cliente/Empresa</label>
                            <input type="text" name="client_name" required 
                                   class="w-full p-2 border rounded">
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2">Nombre de Contacto</label>
                            <input type="text" name="contact_name" required 
                                   class="w-full p-2 border rounded">
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2">Teléfono de Contacto</label>
                            <input type="tel" name="contact_phone" required 
                                   class="w-full p-2 border rounded">
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2">Tipo de Servicio</label>
                            <select name="service_type" required class="w-full p-2 border rounded">
                                <option value="">Seleccionar...</option>
                                <option value="Reparación">Reparación</option>
                                <option value="Mantenimiento">Mantenimiento</option>
                                <option value="Instalación">Instalación</option>
                                <option value="Levantamiento">Levantamiento</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Ubicación -->
                <div class="border-b pb-6">
                    <h2 class="text-xl font-bold mb-4">Ubicación</h2>
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label class="block text-gray-700 mb-2">Dirección</label>
                            <input type="text" name="address" required 
                                   class="w-full p-2 border rounded">
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2">Referencia</label>
                            <input type="text" name="reference" 
                                   class="w-full p-2 border rounded">
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2">URL de Google Maps</label>
                            <input type="url" name="location_url" 
                                   placeholder="https://maps.google.com/?q=..." 
                                   class="w-full p-2 border rounded">
                        </div>
                    </div>
                </div>

                <!-- Programación -->
                <div class="border-b pb-6">
                    <h2 class="text-xl font-bold mb-4">Programación</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-gray-700 mb-2">Fecha</label>
                            <input type="date" name="visit_date" required 
                                   min="<?php echo date('Y-m-d'); ?>"
                                   class="w-full p-2 border rounded">
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2">Hora</label>
                            <input type="time" name="visit_time" required 
                                   class="w-full p-2 border rounded">
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2">Técnico Asignado</label>
                            <select name="technician_id" required class="w-full p-2 border rounded">
                                <option value="">Seleccionar técnico...</option>
                                <?php foreach ($technicians as $tech): ?>
                                    <option value="<?php echo $tech['id']; ?>">
                                        <?php echo htmlspecialchars($tech['full_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Notas -->
                <div>
                    <label class="block text-gray-700 mb-2">Notas Adicionales</label>
                    <textarea name="notes" rows="3" 
                              class="w-full p-2 border rounded"></textarea>
                </div>

                <!-- Botones -->
                <div class="flex justify-end space-x-4">
                    <a href="dashboard.php" 
                       class="px-6 py-2 border border-gray-300 rounded hover:bg-gray-100">
                        Cancelar
                    </a>
                    <button type="submit" 
                            class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        Programar Visita
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="border-b pb-6">
    <h2 class="text-xl font-bold mb-4">Programación</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block text-gray-700 mb-2">Fecha</label>
            <input type="date" 
                   name="visit_date" 
                   required
                   min="<?php echo date('Y-m-d'); ?>"
                   onchange="checkTechnicianAvailability()"
                   class="w-full p-2 border rounded">
        </div>
        <div>
            <label class="block text-gray-700 mb-2">Hora</label>
            <input type="time" 
                   name="visit_time" 
                   required
                   onchange="checkTechnicianAvailability()"
                   class="w-full p-2 border rounded">
        </div>
        <div>
            <label class="block text-gray-700 mb-2">Duración Estimada</label>
            <select name="duration" required class="w-full p-2 border rounded">
                <option value="30">30 minutos</option>
                <option value="60" selected>1 hora</option>
                <option value="90">1.5 horas</option>
                <option value="120">2 horas</option>
                <option value="180">3 horas</option>
                <option value="240">4 horas</option>
            </select>
        </div>
    </div>
    
    <div class="mt-4">
        <label class="block text-gray-700 mb-2">Técnico Asignado</label>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <select name="technician_id" required 
                    onchange="checkTechnicianAvailability()"
                    class="w-full p-2 border rounded">
                <option value="">Seleccionar técnico...</option>
                <?php foreach ($technicians as $tech): ?>
                    <option value="<?php echo $tech['id']; ?>">
                        <?php echo htmlspecialchars($tech['full_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <div id="availability-status" class="hidden p-2 rounded"></div>
        </div>
    </div>
</div>

<script>
async function checkTechnicianAvailability() {
    const technicianId = document.querySelector('[name="technician_id"]').value;
    const date = document.querySelector('[name="visit_date"]').value;
    const time = document.querySelector('[name="visit_time"]').value;
    const duration = document.querySelector('[name="duration"]').value;
    
    if (!technicianId || !date || !time || !duration) return;

    const response = await fetch('check_availability.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({technicianId, date, time, duration})
    });
    
    const data = await response.json();
    const status = document.getElementById('availability-status');
    status.classList.remove('hidden');
    
    if (data.available) {
        status.className = 'p-2 rounded bg-green-100 text-green-800';
        status.innerHTML = '<i class="fas fa-check mr-2"></i>Técnico disponible';
    } else {
        status.className = 'p-2 rounded bg-red-100 text-red-800';
        status.innerHTML = `<i class="fas fa-times mr-2"></i>${data.message}`;
    }
}
</script>


<script>
document.querySelector('form').addEventListener('submit', function(e) {
    const visitDate = new Date(document.querySelector('[name="visit_date"]').value);
    const visitTime = document.querySelector('[name="visit_time"]').value;
    const now = new Date();
    
    // Reset fecha actual a medianoche para comparar solo fechas
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const selectedDate = new Date(visitDate.getFullYear(), visitDate.getMonth(), visitDate.getDate());

    if (selectedDate < today) {
        e.preventDefault();
        showToast('La fecha de la visita no puede ser anterior a hoy', 'error');
        return;
    }

    // Si es hoy, validar la hora
    if (selectedDate.getTime() === today.getTime()) {
        const currentHour = now.getHours();
        const currentMinutes = now.getMinutes();
        const [visitHour, visitMinutes] = visitTime.split(':').map(Number);

        if (visitHour < currentHour || (visitHour === currentHour && visitMinutes <= currentMinutes)) {
            e.preventDefault();
            showToast('La hora de la visita debe ser posterior a la hora actual', 'error');
            return;
        }
    }
});
</script>
</body>
</html>