<?php
require_once '../includes/session_check.php';
require_once '../config/database.php';

if ($_SESSION['role'] !== 'technician') {
    header('Location: ../admin/dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar Contraseña</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen py-6 flex flex-col justify-center sm:py-12">
        <div class="relative py-3 sm:max-w-xl sm:mx-auto">
            <div class="absolute inset-0 bg-gradient-to-r from-blue-500 to-blue-600 shadow-lg transform -skew-y-6 sm:skew-y-0 sm:-rotate-6 sm:rounded-3xl"></div>
            <div class="relative px-4 py-10 bg-white shadow-lg sm:rounded-3xl sm:p-20">
                <div class="max-w-md mx-auto">
                    <div class="divide-y divide-gray-200">
                        <div class="py-8 text-base leading-6 space-y-4 text-gray-700 sm:text-lg sm:leading-7">
                            <h2 class="text-2xl font-bold mb-8 text-center">Cambiar Contraseña</h2>
                            
                            <form id="changePasswordForm" onsubmit="changePassword(event)" class="space-y-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Contraseña Actual
                                    </label>
                                    <input type="password" 
                                           name="current_password" 
                                           required
                                           minlength="6"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Nueva Contraseña
                                    </label>
                                    <input type="password" 
                                           name="new_password" 
                                           required
                                           minlength="6"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                    <p class="mt-1 text-sm text-gray-500">
                                        Mínimo 6 caracteres
                                    </p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Confirmar Nueva Contraseña
                                    </label>
                                    <input type="password" 
                                           name="confirm_password" 
                                           required
                                           minlength="6"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                </div>

                                <div class="flex justify-between items-center pt-4">
                                    <a href="visits.php" 
                                       class="text-blue-600 hover:text-blue-800">
                                        <i class="fas fa-arrow-left mr-2"></i>
                                        Volver
                                    </a>
                                    <button type="submit"
                                            class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                        Cambiar Contraseña
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function changePassword(event) {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);

        // Validar que las contraseñas coincidan
        if (formData.get('new_password') !== formData.get('confirm_password')) {
            showNotification('Las contraseñas no coinciden', 'error');
            return;
        }

        // Validar longitud mínima
        if (formData.get('new_password').length < 6) {
            showNotification('La contraseña debe tener al menos 6 caracteres', 'error');
            return;
        }

        // Mostrar loader
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Cambiando...';

        fetch('actions/password_actions.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Contraseña actualizada exitosamente', 'success');
                form.reset();
                // Redirigir después de 2 segundos
                setTimeout(() => {
                    window.location.href = 'visits.php';
                }, 2000);
            } else {
                showNotification(data.error || 'Error al cambiar la contraseña', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error al cambiar la contraseña', 'error');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    }

    function showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `fixed bottom-4 right-4 px-6 py-3 rounded-lg shadow-lg text-white transform translate-y-0 opacity-100 transition-all duration-300 ${
            type === 'success' ? 'bg-green-500' : 'bg-red-500'
        }`;
        notification.textContent = message;
        document.body.appendChild(notification);

        setTimeout(() => {
            notification.style.transform = 'translateY(100%)';
            notification.style.opacity = '0';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
    </script>
</body>
</html>