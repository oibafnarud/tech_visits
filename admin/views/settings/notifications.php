
<div class="p-6">
    <form method="POST" class="space-y-6">
        <h3 class="text-lg font-medium">Configuración de Notificaciones del Sistema</h3>
        
        <!-- Notificaciones por Email -->
        <div class="border rounded-lg p-4 space-y-4">
            <h4 class="font-medium">Notificaciones por Email</h4>
            
            <div>
                <label class="block text-sm font-medium mb-1">Email para Notificaciones</label>
                <input type="email" name="notification_email"
                       value="<?php echo htmlspecialchars($settings->get('notification_email')); ?>"
                       class="w-full p-2 border rounded focus:border-blue-500">
                <p class="mt-1 text-sm text-gray-500">Email principal para recibir notificaciones del sistema</p>
            </div>

            <div class="space-y-2">
                <label class="flex items-center">
                    <input type="checkbox" name="notify_new_visit" 
                           value="1" 
                           <?php echo $settings->get('notify_new_visit') ? 'checked' : ''; ?>
                           class="rounded border-gray-300">
                    <span class="ml-2 text-sm">Notificar nuevas visitas</span>
                </label>

                <label class="flex items-center">
                    <input type="checkbox" name="notify_visit_status" 
                           value="1"
                           <?php echo $settings->get('notify_visit_status') ? 'checked' : ''; ?>
                           class="rounded border-gray-300">
                    <span class="ml-2 text-sm">Notificar cambios de estado en visitas</span>
                </label>

                <label class="flex items-center">
                    <input type="checkbox" name="notify_tech_unavailable" 
                           value="1"
                           <?php echo $settings->get('notify_tech_unavailable') ? 'checked' : ''; ?>
                           class="rounded border-gray-300">
                    <span class="ml-2 text-sm">Notificar cuando un técnico no está disponible</span>
                </label>
            </div>
        </div>

        <!-- Notificaciones Web -->
        <div class="border rounded-lg p-4 space-y-4">
            <h4 class="font-medium">Notificaciones Web</h4>
            
            <div class="space-y-2">
                <label class="flex items-center">
                    <input type="checkbox" name="web_notify_new_visit" 
                           value="1"
                           <?php echo $settings->get('web_notify_new_visit') ? 'checked' : ''; ?>
                           class="rounded border-gray-300">
                    <span class="ml-2 text-sm">Mostrar notificación de nuevas visitas</span>
                </label>

                <label class="flex items-center">
                    <input type="checkbox" name="web_notify_status_change" 
                           value="1"
                           <?php echo $settings->get('web_notify_status_change') ? 'checked' : ''; ?>
                           class="rounded border-gray-300">
                    <span class="ml-2 text-sm">Mostrar notificación de cambios de estado</span>
                </label>
            </div>

            <div class="mt-4">
                <label class="block text-sm font-medium mb-1">Tiempo de retención de notificaciones (días)</label>
                <input type="number" name="notification_retention_days"
                       value="<?php echo $settings->get('notification_retention_days', 30); ?>"
                       min="1" max="365"
                       class="w-full p-2 border rounded focus:border-blue-500">
                <p class="mt-1 text-sm text-gray-500">Después de este periodo, las notificaciones antiguas serán eliminadas automáticamente</p>
            </div>
        </div>

        <!-- Botón de guardado -->
        <div class="flex justify-end">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                Guardar Configuración
            </button>
        </div>
    </form>
</div>