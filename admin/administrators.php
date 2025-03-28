
<?php
require_once '../includes/session_check.php';
require_once '../config/database.php';
require_once '../includes/Permissions.php';

if ($_SESSION['role'] !== 'super_admin') {  // Eliminar la verificación de admin_level ya que no existe
    header('Location: /admin/dashboard.php');
    exit;
}

$database = new Database();
$db = $database->connect();
$permissions = new Permissions($db, $_SESSION);

$page_title = 'Gestión de Administradores';
$current_page = 'administrators';

// Obtener lista de administradores
$stmt = $db->prepare("
    SELECT u.*, c.full_name as created_by_name
    FROM users u
    LEFT JOIN users c ON u.created_by = c.id
    WHERE u.role IN ('admin', 'editor')  // Incluir también editores
    ORDER BY u.created_at DESC
");
$stmt->execute();
$administrators = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Iniciar buffer de salida
ob_start();
?>

<!-- HTML para la vista de administradores -->
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Gestión de Administradores</h1>
        <?php if ($permissions->can('create_admin')): ?>
            <button onclick="showNewAdminModal()" 
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                <i class="fas fa-plus mr-2"></i>Nuevo Administrador
            </button>
        <?php endif; ?>
    </div>

    <!-- Lista de administradores -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead>
                <tr>
                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Nombre
                    </th>
                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Nivel
                    </th>
                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Último Acceso
                    </th>
                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Creado Por
                    </th>
                    <th class="px-6 py-3 bg-gray-50"></th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($administrators as $admin): ?>
                    <tr>
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div>
                                    <div class="font-medium text-gray-900">
                                        <?php echo htmlspecialchars($admin['full_name']); ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?php echo htmlspecialchars($admin['email']); ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                       <?php echo $admin['admin_level'] === 'super_admin' ? 'bg-purple-100 text-purple-800' : 
                                                  ($admin['admin_level'] === 'admin' ? 'bg-green-100 text-green-800' : 
                                                   'bg-blue-100 text-blue-800'); ?>">
                                <?php echo ucfirst($admin['admin_level']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <?php echo $admin['last_login'] ? date('d/m/Y H:i', strtotime($admin['last_login'])) : 'Nunca'; ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <?php echo htmlspecialchars($admin['created_by_name'] ?? 'Sistema'); ?>
                        </td>
                        <td class="px-6 py-4 text-right text-sm font-medium">
                            <?php if ($permissions->can('edit_admin')): ?>
                                <button onclick="editAdmin(<?php echo $admin['id']; ?>)"
                                        class="text-blue-600 hover:text-blue-900 mr-3">
                                    Editar
                                </button>
                            <?php endif; ?>
                            
                            <?php if ($permissions->can('delete_admin')): ?>
                                <button onclick="deleteAdmin(<?php echo $admin['id']; ?>)"
                                        class="text-red-600 hover:text-red-900">
                                    Eliminar
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal para nuevo/editar administrador -->
<?php include 'modals/admin_form.php'; ?>

<script src="/admin/js/administrators.js"></script>

<?php
$content = ob_get_clean();
require_once '../includes/layout.php';
?>