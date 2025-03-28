<div class="p-6">
    <!-- Sección de lista de administradores -->
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-medium">Gestión de Administradores</h3>
        <?php if ($_SESSION['role'] === 'super_admin'): ?>
        <button onclick="showAdminModal()" 
                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
            <i class="fas fa-plus mr-2"></i>Nuevo Administrador
        </button>
        <?php endif; ?>
    </div>

    <!-- Tabla de administradores -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuario</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rol</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php 
                $stmt = $db->query("SELECT * FROM users WHERE role IN ('admin', 'super_admin', 'editor') ORDER BY created_at DESC");
                $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($admins as $admin): 
                ?>
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div>
                                <div class="text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($admin['username']); ?>
                                </div>
                                <div class="text-sm text-gray-500">
                                    <?php echo htmlspecialchars($admin['email']); ?>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($admin['full_name']); ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                            <?php echo $admin['role'] === 'super_admin' ? 'bg-purple-100 text-purple-800' : 
                                    ($admin['role'] === 'admin' ? 'bg-blue-100 text-blue-800' : 
                                    'bg-green-100 text-green-800'); ?>">
                            <?php echo ucfirst($admin['role']); ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                            <?php echo $admin['active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                            <?php echo $admin['active'] ? 'Activo' : 'Inactivo'; ?>
                        </span>
                    </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <?php if ($_SESSION['role'] === 'super_admin'): ?>
                                <button onclick="editAdmin(<?php echo $admin['id']; ?>)" 
                                        class="text-blue-600 hover:text-blue-900 mr-3">
                                    Editar
                                </button>
                                <?php if ($admin['id'] !== $_SESSION['user_id']): ?>
                                    <button onclick="toggleAdminStatus(<?php echo $admin['id']; ?>, <?php echo $admin['active'] ? 'false' : 'true'; ?>)" 
                                            class="text-<?php echo $admin['active'] ? 'yellow' : 'green'; ?>-600 hover:text-<?php echo $admin['active'] ? 'yellow' : 'green'; ?>-900 mr-3">
                                        <?php echo $admin['active'] ? 'Desactivar' : 'Activar'; ?>
                                    </button>
                                    <button onclick="deleteAdmin(<?php echo $admin['id']; ?>)"
                                            class="text-red-600 hover:text-red-900">
                                        Eliminar
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal para nuevo/editar administrador -->
<div id="adminModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="relative top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-lg">
        <div class="bg-white rounded-lg shadow-xl m-4">
            <div class="flex justify-between items-center p-6 border-b">
                <h3 class="text-lg font-medium" id="adminModalTitle">Nuevo Administrador</h3>
                <button onclick="hideAdminModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="adminForm" onsubmit="saveAdmin(event)" class="p-6">
                <input type="hidden" name="admin_id" id="adminId">
                <input type="hidden" name="active_tab" id="activeTab" value="administrators">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Nombre Completo</label>
                        <input type="text" name="full_name" required
                               class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Usuario</label>
                        <input type="text" name="username" required pattern="[a-zA-Z0-9_]+"
                               class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Email</label>
                        <input type="email" name="email" required
                               class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div id="passwordFields">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-1">Contraseña</label>
                                <input type="password" name="password" minlength="6"
                                       class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Confirmar Contraseña</label>
                                <input type="password" name="password_confirm" minlength="6"
                                       class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Rol</label>
                        <select name="role" required class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-500">
                            <option value="admin">Administrador</option>
                            <option value="editor">Editor</option>
                        </select>
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="hideAdminModal()"
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

<script>
let currentAdminId = null;

function showAdminModal(adminId = null) {
    currentAdminId = adminId;
    const modal = document.getElementById('adminModal');
    const form = document.getElementById('adminForm');
    const title = document.getElementById('adminModalTitle');
    const passwordFields = document.getElementById('passwordFields');

    // Limpiar el formulario
    form.reset();
    document.getElementById('adminId').value = '';

    if (adminId) {
        title.textContent = 'Editar Administrador';
        passwordFields.style.display = 'none';
        // Cargar datos del administrador
        fetch(`/admin/actions/admin_actions.php?action=get&id=${adminId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('adminId').value = data.admin.id;
                    form.elements['full_name'].value = data.admin.full_name;
                    form.elements['username'].value = data.admin.username;
                    form.elements['email'].value = data.admin.email || '';
                    form.elements['role'].value = data.admin.role;
                    modal.classList.remove('hidden');
                } else {
                    alert(data.error || 'Error al cargar los datos del administrador');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al cargar los datos del administrador');
            });
    } else {
        title.textContent = 'Nuevo Administrador';
        passwordFields.style.display = 'block';
        modal.classList.remove('hidden');
    }
}

function hideAdminModal() {
    const modal = document.getElementById('adminModal');
    modal.classList.add('hidden');
    const form = document.getElementById('adminForm');
    form.reset();
    currentAdminId = null;
}

function saveAdmin(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    formData.append('active_tab', 'administrators'); // Mantener la pestaña activa

    // Validar contraseñas si es nuevo administrador o si se está cambiando la contraseña
    if (!formData.get('admin_id') || formData.get('password')) {
        if (formData.get('password') !== formData.get('password_confirm')) {
            alert('Las contraseñas no coinciden');
            return;
        }
    }

    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Guardando...';

    fetch('/admin/actions/admin_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            hideAdminModal();
            window.location.reload();
        } else {
            alert(data.error || 'Error al guardar el administrador');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al guardar el administrador');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
}

// Cerrar modal al hacer clic fuera
document.getElementById('adminModal').addEventListener('click', function(e) {
    if (e.target === this) {
        hideAdminModal();
    }
});

// Mantener la pestaña activa al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const activeTab = urlParams.get('tab') || 'administrators';
    switchTab(activeTab);
});

function toggleAdminStatus(adminId, active) {
    if (!confirm(`¿Está seguro de ${active ? 'activar' : 'desactivar'} este administrador?`)) {
        return;
    }

    fetch('/admin/actions/admin_actions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: 'toggle_status',
            admin_id: adminId,
            active: active
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.search = '?tab=administrators';
        } else {
            alert(data.error || 'Error al cambiar el estado del administrador');
        }
    });
}

function editAdmin(adminId) {
    fetch(`/admin/actions/admin_actions.php?action=get&id=${adminId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const form = document.getElementById('adminForm');
                document.getElementById('adminId').value = data.admin.id;
                form.elements['full_name'].value = data.admin.full_name;
                form.elements['username'].value = data.admin.username;
                form.elements['email'].value = data.admin.email || '';
                form.elements['role'].value = data.admin.role;
                
                // Ocultar campos de contraseña en edición
                document.getElementById('passwordFields').style.display = 'none';
                document.getElementById('adminModalTitle').textContent = 'Editar Administrador';
                document.getElementById('adminModal').classList.remove('hidden');
            } else {
                alert(data.error || 'Error al cargar los datos del administrador');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al cargar los datos del administrador');
        });
}

function deleteAdmin(adminId) {
    if (!confirm('¿Está seguro de eliminar este administrador? Esta acción no se puede deshacer.')) {
        return;
    }

    fetch('/admin/actions/admin_actions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: 'delete',
            admin_id: adminId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.search = '?tab=administrators';
        } else {
            alert(data.error || 'Error al eliminar el administrador');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al eliminar el administrador');
    });
}
</script>