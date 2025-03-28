<?php
require_once '../../../includes/session_check.php';
require_once '../../../config/database.php';

header('Content-Type: application/json');

if ($_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso no autorizado']);
    exit;
}

$database = new Database();
$db = $database->connect();

// Procesar solicitud GET
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    switch ($_GET['action'] ?? '') {
        case 'get_details':
            getVisitDetails($db);
            break;
        
        default:
            echo json_encode(['error' => 'Acción no válida']);
    }
    exit;
}

// Procesar solicitud POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $action = $input['action'] ?? '';

    switch ($action) {
        case 'create':
            createVisit($db, $input);
            break;

        case 'update':
            updateVisit($db, $input);
            break;

        case 'update_status':
            updateVisitStatus($db, $input);
            break;

        case 'reschedule':
            rescheduleVisit($db, $input);
            break;

        case 'reassign':
            reassignVisit($db, $input);
            break;

        case 'delete':
            deleteVisit($db, $input);
            break;

        default:
            echo json_encode(['error' => 'Acción no válida']);
    }
    exit;
}

function getVisitDetails($db) {
    if (!isset($_GET['id'])) {
        echo json_encode(['error' => 'ID no proporcionado']);
        return;
    }

    $stmt = $db->prepare("
        SELECT v.*, u.full_name as technician_name
        FROM visits v
        LEFT JOIN users u ON v.technician_id = u.id
        WHERE v.id = :id
    ");
    
    $stmt->execute([':id' => $_GET['id']]);
    $visit = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$visit) {
        echo json_encode(['error' => 'Visita no encontrada']);
        return;
    }

    echo json_encode(['success' => true, 'visit' => $visit]);
}

function createVisit($db, $data) {
    try {
        $stmt = $db->prepare("
            INSERT INTO visits (
                client_name, contact_name, contact_phone, contact_email,
                address, reference, location_url,
                visit_date, visit_time,
                service_type, technician_id,
                notes, status
            ) VALUES (
                :client_name, :contact_name, :contact_phone, :contact_email,
                :address, :reference, :location_url,
                :visit_date, :visit_time,
                :service_type, :technician_id,
                :notes, 'pending'
            )
        ");

        $result = $stmt->execute([
            ':client_name' => $data['client_name'],
            ':contact_name' => $data['contact_name'],
            ':contact_phone' => $data['contact_phone'],
            ':contact_email' => $data['contact_email'] ?? null,
            ':address' => $data['address'],
            ':reference' => $data['reference'] ?? null,
            ':location_url' => $data['location_url'] ?? null,
            ':visit_date' => $data['visit_date'],
            ':visit_time' => $data['visit_time'],
            ':service_type' => $data['service_type'],
            ':technician_id' => $data['technician_id'],
            ':notes' => $data['notes'] ?? null
        ]);

        if ($result) {
            logVisitAction($db, $db->lastInsertId(), 'create', 'Visita creada');
            echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
        } else {
            echo json_encode(['error' => 'Error al crear la visita']);
        }
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]);
    }
}

function updateVisit($db, $data) {
    if (!isset($data['visit_id'])) {
        echo json_encode(['error' => 'ID no proporcionado']);
        return;
    }

    try {
        $stmt = $db->prepare("
            UPDATE visits SET
                client_name = :client_name,
                contact_name = :contact_name,
                contact_phone = :contact_phone,
                contact_email = :contact_email,
                address = :address,
                reference = :reference,
                location_url = :location_url,
                visit_date = :visit_date,
                visit_time = :visit_time,
                service_type = :service_type,
                technician_id = :technician_id,
                notes = :notes
            WHERE id = :visit_id
        ");

        $result = $stmt->execute([
            ':visit_id' => $data['visit_id'],
            ':client_name' => $data['client_name'],
            ':contact_name' => $data['contact_name'],
            ':contact_phone' => $data['contact_phone'],
            ':contact_email' => $data['contact_email'] ?? null,
            ':address' => $data['address'],
            ':reference' => $data['reference'] ?? null,
            ':location_url' => $data['location_url'] ?? null,
            ':visit_date' => $data['visit_date'],
            ':visit_time' => $data['visit_time'],
            ':service_type' => $data['service_type'],
            ':technician_id' => $data['technician_id'],
            ':notes' => $data['notes'] ?? null
        ]);

        if ($result) {
            logVisitAction($db, $data['visit_id'], 'update', 'Visita actualizada');
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Error al actualizar la visita']);
        }
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]);
    }
}

function updateVisitStatus($db, $data) {
    if (!isset($data['visit_id']) || !isset($data['status'])) {
        echo json_encode(['error' => 'Datos incompletos']);
        return;
    }

    $validStatus = ['pending', 'in_route', 'completed'];
    if (!in_array($data['status'], $validStatus)) {
        echo json_encode(['error' => 'Estado no válido']);
        return;
    }

    try {
        $stmt = $db->prepare("
            UPDATE visits 
            SET status = :status
            WHERE id = :visit_id
        ");

        $result = $stmt->execute([
            ':status' => $data['status'],
            ':visit_id' => $data['visit_id']
        ]);

        if ($result) {
            logVisitAction($db, $data['visit_id'], 'status_update', 'Estado actualizado a: ' . $data['status']);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Error al actualizar el estado']);
        }
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]);
    }
}

function rescheduleVisit($db, $data) {
    if (!isset($data['visit_id']) || !isset($data['new_date']) || !isset($data['new_time'])) {
        echo json_encode(['error' => 'Datos incompletos']);
        return;
    }

    try {
        $stmt = $db->prepare("
            UPDATE visits 
            SET visit_date = :new_date,
                visit_time = :new_time
            WHERE id = :visit_id
        ");

        $result = $stmt->execute([
            ':new_date' => $data['new_date'],
            ':new_time' => $data['new_time'],
            ':visit_id' => $data['visit_id']
        ]);

        if ($result) {
            logVisitAction($db, $data['visit_id'], 'reschedule', 
                          'Reprogramada para: ' . $data['new_date'] . ' ' . $data['new_time']);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Error al reprogramar la visita']);
        }
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]);
    }
}

function reassignVisit($db, $data) {
    if (!isset($data['visit_id']) || !isset($data['technician_id'])) {
        echo json_encode(['error' => 'Datos incompletos']);
        return;
    }

    try {
        // Verificar que el técnico existe y está activo
        $stmt = $db->prepare("
            SELECT id FROM users 
            WHERE id = :technician_id 
            AND role = 'technician' 
            AND active = 1
        ");
        $stmt->execute([':technician_id' => $data['technician_id']]);
        if (!$stmt->fetch()) {
            echo json_encode(['error' => 'Técnico no válido']);
            return;
        }

        $stmt = $db->prepare("
            UPDATE visits 
            SET technician_id = :technician_id
            WHERE id = :visit_id
        ");

        $result = $stmt->execute([
            ':technician_id' => $data['technician_id'],
            ':visit_id' => $data['visit_id']
        ]);

        if ($result) {
            logVisitAction($db, $data['visit_id'], 'reassign', 
                          'Reasignada al técnico ID: ' . $data['technician_id']);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Error al reasignar la visita']);
        }
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]);
    }
}

function deleteVisit($db, $data) {
    if (!isset($data['visit_id'])) {
        echo json_encode(['error' => 'ID no proporcionado']);
        return;
    }

    try {
        // Primero registrar el log (porque después de eliminar no tendríamos el ID)
        logVisitAction($db, $data['visit_id'], 'delete', 'Visita eliminada');

        $stmt = $db->prepare("DELETE FROM visits WHERE id = :visit_id");
        $result = $stmt->execute([':visit_id' => $data['visit_id']]);

        if ($result) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Error al eliminar la visita']);
        }
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]);
    }
}

function logVisitAction($db, $visitId, $action, $description) {
    try {
        $stmt = $db->prepare("
            INSERT INTO visit_logs (
                visit_id, action, description, 
                user_id, created_at
            ) VALUES (
                :visit_id, :action, :description,
                :user_id, NOW()
            )
        ");

        $stmt->execute([
            ':visit_id' => $visitId,
            ':action' => $action,
            ':description' => $description,
            ':user_id' => $_SESSION['user_id']
        ]);
    } catch (PDOException $e) {
        error_log("Error al registrar log: " . $e->getMessage());
    }
}

<!-- views/visit_actions.php -->
<div class="fixed bottom-0 right-0 p-4 space-y-4" style="z-index: 1000;">
    <!-- Botones de acción flotantes -->
    <div class="flex flex-col items-end space-y-2">
        <button onclick="updateStatus('in_route')" 
                class="bg-yellow-500 text-white p-3 rounded-full shadow hover:bg-yellow-600">
            <i class="fas fa-truck"></i>
        </button>
        <button onclick="updateStatus('completed')" 
                class="bg-green-500 text-white p-3 rounded-full shadow hover:bg-green-600">
            <i class="fas fa-check"></i>
        </button>
    </div>
</div>

<!-- Modal de Actualización de Estado -->
<div id="statusModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="fixed bottom-0 left-0 right-0 bg-white rounded-t-xl">
        <form id="statusForm" class="p-6">
            <input type="hidden" name="visit_id" id="visitId">
            <input type="hidden" name="status" id="newStatus">

            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold" id="statusTitle">Actualizar Estado</h3>
                <button type="button" onclick="closeStatusModal()" class="text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Notas -->
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Notas</label>
                <textarea name="notes" rows="3" class="w-full p-2 border rounded"></textarea>
            </div>

            <!-- Ubicación (solo para completar) -->
            <div id="locationFields" class="mb-4 hidden">
                <label class="flex items-center mb-2">
                    <input type="checkbox" id="useLocation" class="mr-2">
                    <span class="text-sm">Registrar ubicación actual</span>
                </label>
                <input type="hidden" name="lat" id="lat">
                <input type="hidden" name="lng" id="lng">
            </div>

            <!-- Notificación -->
            <div class="mb-6">
                <label class="flex items-center">
                    <input type="checkbox" name="notify" class="mr-2">
                    <span class="text-sm">Notificar al cliente</span>
                </label>
            </div>

            <button type="submit" class="w-full bg-blue-600 text-white p-3 rounded-lg">
                Confirmar
            </button>
        </form>
    </div>
</div>

<script>
let currentVisit = null;

function updateStatus(status) {
    currentVisit = /* obtener ID de visita actual */;
    document.getElementById('visitId').value = currentVisit;
    document.getElementById('newStatus').value = status;
    
    const modal = document.getElementById('statusModal');
    const locationFields = document.getElementById('locationFields');
    
    // Mostrar campos de ubicación solo al completar
    if (status === 'completed') {
        locationFields.classList.remove('hidden');
        if (document.getElementById('useLocation').checked) {
            getLocation();
        }
    } else {
        locationFields.classList.add('hidden');
    }
    
    modal.classList.remove('hidden');
}

function closeStatusModal() {
    document.getElementById('statusModal').classList.add('hidden');
    document.getElementById('statusForm').reset();
}

// Obtener ubicación
function getLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            position => {
                document.getElementById('lat').value = position.coords.latitude;
                document.getElementById('lng').value = position.coords.longitude;
            },
            error => {
                console.error('Error getting location:', error);
                alert('No se pudo obtener la ubicación');
            }
        );
    }
}

document.getElementById('useLocation').addEventListener('change', function(e) {
    if (e.target.checked) {
        getLocation();
    } else {
        document.getElementById('lat').value = '';
        document.getElementById('lng').value = '';
    }
});

document.getElementById('statusForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = {
        visit_id: formData.get('visit_id'),
        status: formData.get('status'),
        notes: formData.get('notes'),
        notify: formData.get('notify') === 'on',
        coordinates: formData.get('lat') ? {
            lat: formData.get('lat'),
            lng: formData.get('lng')
        } : null
    };

    try {
        const response = await fetch('actions/visit_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();
        
        if (result.success) {
            closeStatusModal();
            location.reload();
        } else {
            alert(result.error || 'Error al actualizar el estado');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al actualizar el estado');
    }
});
</script>

?>