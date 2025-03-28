<?php
require_once '../includes/session_check.php';
require_once '../config/database.php';
require_once '../includes/Settings.php';

$database = new Database();
$db = $database->connect();
$settings = Settings::getInstance($db);

$activeTab = isset($_POST['active_tab']) ? $_POST['active_tab'] : 'general';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Manejar logo
        if (isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] === 0) {
            $allowedTypes = ['image/jpeg', 'image/png'];
            $maxSize = 2 * 1024 * 1024; // 2MB

            if (!in_array($_FILES['company_logo']['type'], $allowedTypes)) {
                throw new Exception('Tipo de archivo no permitido. Use JPG o PNG.');
            }

            if ($_FILES['company_logo']['size'] > $maxSize) {
                throw new Exception('El archivo es demasiado grande. Máximo 2MB.');
            }

            $fileName = 'logo_' . time() . '.' . pathinfo($_FILES['company_logo']['name'], PATHINFO_EXTENSION);
            $uploadPath = '../assets/images/' . $fileName;

            if (move_uploaded_file($_FILES['company_logo']['tmp_name'], $uploadPath)) {
                $settings->set('company_logo', $fileName, $_SESSION['user_id']);
            }
        }

        // Actualizar configuraciones generales
        $configFields = [
            'company_name',
            'company_email',
            'company_phone',
            'company_address',
            'default_timezone',
            'working_days',
            'working_hours_start',
            'working_hours_end',
            'visit_duration_default'
        ];

        foreach ($configFields as $field) {
            if (isset($_POST[$field])) {
                $settings->set($field, $_POST[$field], $_SESSION['user_id']);
            }
        }

        $success = "Configuración actualizada exitosamente";
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Obtener configuraciones actuales
$currentSettings = [
    'company_logo' => $settings->get('company_logo'),
    'company_name' => $settings->get('company_name'),
    'company_email' => $settings->get('company_email'),
    'company_phone' => $settings->get('company_phone'),
    'company_address' => $settings->get('company_address'),
    'default_timezone' => $settings->get('default_timezone', 'America/Santo_Domingo'),
    'working_days' => explode(',', $settings->get('working_days', '1,2,3,4,5')),
    'working_hours_start' => $settings->get('working_hours_start', '08:00'),
    'working_hours_end' => $settings->get('working_hours_end', '17:00'),
    'visit_duration_default' => $settings->get('visit_duration_default', '60')
];

// Agregar nueva sección para gestión de administradores
$stmt = $db->query("
    SELECT u.*, c.full_name as created_by_name 
    FROM users u 
    LEFT JOIN users c ON u.created_by = c.id 
    WHERE u.role = 'admin' 
    ORDER BY u.created_at DESC
");
$administrators = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Configuración del Sistema';
ob_start();
?>

<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm">
        <!-- Pestañas de configuración -->
        <div class="border-b">
            <nav class="flex -mb-px">
                <button onclick="switchTab('general')" 
                        class="px-6 py-3 border-b-2 border-blue-500 text-blue-600 tab-button active">
                    General
                </button>
                <button onclick="switchTab('administrators')" 
                        class="px-6 py-3 border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 tab-button">
                    Administradores
                </button>
                <button onclick="switchTab('notifications')" 
                        class="px-6 py-3 border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 tab-button">
                    Notificaciones
                </button>
            </nav>
        </div>

        <!-- Contenido de las pestañas -->
        <div id="tab-general" class="tab-content">
            <!-- Contenido existente del formulario de configuración general -->
            <?php include 'views/settings/general.php'; ?>
        </div>

        <div id="tab-administrators" class="tab-content hidden">
            <!-- Nueva sección de administradores -->
            <?php include 'views/settings/administrators.php'; ?>
        </div>

        <div id="tab-notifications" class="tab-content hidden">
            <!-- Nueva sección de configuración de notificaciones -->
            <?php include 'views/settings/notifications.php'; ?>
        </div>
    </div>
</div>

<script>
function switchTab(tabName) {
    // Ocultar todos los contenidos
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.add('hidden');
    });
    
    // Desactivar todos los botones
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('border-blue-500', 'text-blue-600');
        button.classList.add('border-transparent', 'text-gray-500');
    });
    
    // Mostrar el contenido seleccionado
    document.getElementById('tab-' + tabName).classList.remove('hidden');
    
    // Activar el botón seleccionado
    document.querySelector(`[onclick="switchTab('${tabName}')"]`).classList.remove('border-transparent', 'text-gray-500');
    document.querySelector(`[onclick="switchTab('${tabName}')"]`).classList.add('border-blue-500', 'text-blue-600');

    // Guardar la pestaña activa en un campo oculto
    document.getElementById('activeTab').value = tabName;
}

document.addEventListener('DOMContentLoaded', function() {
    switchTab('<?php echo $activeTab; ?>');
});
</script>


<?php
$content = ob_get_clean();
require_once '../includes/layout.php';
?>