<?php
// Validar la conexión y obtener las visitas
$stmt = $db->prepare("
    SELECT v.* 
    FROM visits v
    WHERE v.technician_id = :technician_id 
    AND v.visit_date = :date 
    ORDER BY v.visit_time ASC
");

$stmt->execute([
    ':technician_id' => $_SESSION['user_id'],
    ':date' => $selected_date
]);
$visits = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Vista simplificada y corregida -->
<div class="space-y-4">
    <?php if (empty($visits)): ?>
        <div class="bg-white rounded-lg shadow-sm p-8 text-center">
            <i class="fas fa-calendar-check text-4xl text-blue-500 mb-3"></i>
            <h3 class="text-lg font-medium">No hay visitas programadas para hoy</h3>
        </div>
    <?php else: ?>
        <?php foreach ($visits as $visit): ?>
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <!-- Cabecera de la visita -->
                <div class="p-4 border-b">
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="text-lg font-bold">
                                <?php echo date('h:i A', strtotime($visit['visit_time'])); ?>
                            </div>
                            <div class="text-gray-600">
                                <?php echo htmlspecialchars($visit['client_name']); ?>
                            </div>
                            <?php if ($visit['service_type']): ?>
                                <div class="mt-1 inline-block px-2 py-1 text-sm rounded-full bg-blue-100 text-blue-800">
                                    <?php echo htmlspecialchars($visit['service_type']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <span class="px-3 py-1 rounded-full text-sm <?php echo getStatusBadgeClass($visit['status']); ?>">
                            <?php echo getStatusLabel($visit['status']); ?>
                        </span>
                    </div>
                </div>

                <!-- Detalles y acciones -->
                <div class="p-4">
                    <!-- Dirección -->
                    <div class="mb-4">
                        <div class="flex items-start">
                            <i class="fas fa-map-marker-alt mt-1 w-5 text-gray-400"></i>
                            <div class="ml-2">
                                <div><?php echo htmlspecialchars($visit['address']); ?></div>
                                <?php if ($visit['reference']): ?>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($visit['reference']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Contacto -->
                    <?php if ($visit['contact_phone']): ?>
                        <div class="mb-4">
                            <a href="tel:<?php echo $visit['contact_phone']; ?>" 
                               class="flex items-center text-blue-600 hover:text-blue-800">
                                <i class="fas fa-phone w-5"></i>
                                <span class="ml-2"><?php echo htmlspecialchars($visit['contact_phone']); ?></span>
                            </a>
                        </div>
                    <?php endif; ?>

                    <!-- Botones de acción -->
                    <?php if ($visit['status'] !== 'completed'): ?>
                        <div class="grid grid-cols-2 gap-2 mt-4">
                            <?php if ($visit['location_url']): ?>
                                <a href="<?php echo htmlspecialchars($visit['location_url']); ?>" 
                                   target="_blank"
                                   class="col-span-2 flex items-center justify-center bg-gray-100 text-gray-700 p-3 rounded-lg hover:bg-gray-200">
                                    <i class="fas fa-map-marked-alt mr-2"></i>
                                    Ver en Mapa
                                </a>
                            <?php endif; ?>

                            <?php if ($visit['status'] === 'pending'): ?>
                                <button onclick="updateStatus(<?php echo $visit['id']; ?>, 'in_route')"
                                        class="flex items-center justify-center bg-yellow-500 text-white p-3 rounded-lg hover:bg-yellow-600">
                                    <i class="fas fa-truck mr-2"></i>
                                    En Camino
                                </button>
                            <?php endif; ?>

                            <button onclick="updateStatus(<?php echo $visit['id']; ?>, 'completed')"
                                    class="flex items-center justify-center bg-green-500 text-white p-3 rounded-lg hover:bg-green-600">
                                <i class="fas fa-check mr-2"></i>
                                Completar
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
// Función para actualizar el estado de una visita
function updateStatus(visitId, status) {
    if (!confirm(`¿Seguro que quieres marcar esta visita como "${getStatusLabel(status)}"?`)) {
        return;
    }

    fetch('actions/update_visit_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            visit_id: visitId,
            status: status
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(`Visita marcada como ${getStatusLabel(status)}`, 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            throw new Error(data.error || 'Error al actualizar el estado');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error al actualizar el estado', 'error');
    });
}

// Función auxiliar para mostrar notificaciones
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

// Funciones auxiliares
function getStatusLabel(status) {
    switch (status) {
        case 'completed': return 'Completada';
        case 'in_route': return 'En Camino';
        default: return 'Pendiente';
    }
}
</script>

<?php
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'completed':
            return 'bg-green-100 text-green-800';
        case 'in_route':
            return 'bg-yellow-100 text-yellow-800';
        default:
            return 'bg-blue-100 text-blue-800';
    }
}

function getStatusLabel($status) {
    switch ($status) {
        case 'completed':
            return 'Completada';
        case 'in_route':
            return 'En Camino';
        default:
            return 'Pendiente';
    }
}
?>