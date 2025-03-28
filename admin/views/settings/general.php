
<form method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
    <?php if (isset($success)): ?>
        <div class="p-4 bg-green-100 text-green-700 border-l-4 border-green-500">
            <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="p-4 bg-red-100 text-red-700 border-l-4 border-red-500">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <!-- Información de la Empresa -->
    <div>
        <h3 class="text-lg font-medium mb-4">Información de la Empresa</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Nombre de la Empresa</label>
                <input type="text" name="company_name" 
                       value="<?php echo htmlspecialchars($currentSettings['company_name']); ?>"
                       class="w-full p-2 border rounded focus:border-blue-500">
            </div>
            
            <div>
                <label class="block text-sm font-medium mb-1">Logo</label>
                <?php if ($currentSettings['company_logo']): ?>
                    <div class="mb-2">
                        <img src="/assets/images/<?php echo $currentSettings['company_logo']; ?>" 
                             alt="Logo actual" class="h-12">
                    </div>
                <?php endif; ?>
                <input type="file" name="company_logo" accept="image/jpeg,image/png"
                       class="w-full p-2 border rounded focus:border-blue-500">
                <p class="text-sm text-gray-500 mt-1">Formatos permitidos: JPG, PNG. Máximo 2MB.</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium mb-1">Email</label>
                <input type="email" name="company_email"
                       value="<?php echo htmlspecialchars($currentSettings['company_email']); ?>"
                       class="w-full p-2 border rounded focus:border-blue-500">
            </div>
            
            <div>
                <label class="block text-sm font-medium mb-1">Teléfono</label>
                <input type="tel" name="company_phone"
                       value="<?php echo htmlspecialchars($currentSettings['company_phone']); ?>"
                       class="w-full p-2 border rounded focus:border-blue-500">
            </div>
            
            <div class="md:col-span-2">
                <label class="block text-sm font-medium mb-1">Dirección</label>
                <textarea name="company_address" rows="2"
                          class="w-full p-2 border rounded focus:border-blue-500"><?php echo htmlspecialchars($currentSettings['company_address']); ?></textarea>
            </div>
        </div>
    </div>

    <!-- Horario de Trabajo -->
    <div>
        <h3 class="text-lg font-medium mb-4">Horario de Trabajo</h3>
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium mb-2">Días Laborables</label>
                <div class="grid grid-cols-7 gap-4">
                    <?php
                    $days = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
                    foreach ($days as $index => $day):
                        $dayNum = $index + 1;
                    ?>
                    <label class="flex flex-col items-center">
                        <span class="text-sm text-gray-600"><?php echo $day; ?></span>
                        <input type="checkbox" name="working_days[]" 
                               value="<?php echo $dayNum; ?>"
                               <?php echo in_array($dayNum, $currentSettings['working_days']) ? 'checked' : ''; ?>
                               class="mt-1 rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Hora de Inicio</label>
                    <input type="time" name="working_hours_start"
                           value="<?php echo $currentSettings['working_hours_start']; ?>"
                           class="w-full p-2 border rounded focus:border-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-1">Hora de Fin</label>
                    <input type="time" name="working_hours_end"
                           value="<?php echo $currentSettings['working_hours_end']; ?>"
                           class="w-full p-2 border rounded focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Duración Default de Visita</label>
                    <select name="visit_duration_default" class="w-full p-2 border rounded focus:border-blue-500">
                        <?php
                        $durations = [
                            '30' => '30 minutos',
                            '60' => '1 hora',
                            '90' => '1.5 horas',
                            '120' => '2 horas',
                            '180' => '3 horas',
                            '240' => '4 horas'
                        ];
                        foreach ($durations as $value => $label):
                        ?>
                            <option value="<?php echo $value; ?>" 
                                    <?php echo $currentSettings['visit_duration_default'] == $value ? 'selected' : ''; ?>>
                                <?php echo $label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Zona Horaria -->
    <div>
        <h3 class="text-lg font-medium mb-4">Zona Horaria</h3>
        <div>
            <label class="block text-sm font-medium mb-1">Zona Horaria del Sistema</label>
            <select name="default_timezone" class="w-full p-2 border rounded focus:border-blue-500">
                <?php
                $timezones = DateTimeZone::listIdentifiers(DateTimeZone::AMERICA);
                foreach ($timezones as $timezone):
                ?>
                    <option value="<?php echo $timezone; ?>"
                            <?php echo $currentSettings['default_timezone'] === $timezone ? 'selected' : ''; ?>>
                        <?php echo $timezone; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="text-sm text-gray-500 mt-1">
                Hora actual del sistema: <?php echo date('Y-m-d H:i:s'); ?>
            </p>
        </div>
    </div>

    <!-- Mantenimiento -->
    <div>
        <h3 class="text-lg font-medium mb-4">Modo Mantenimiento</h3>
        <div class="flex items-center">
            <input type="checkbox" name="maintenance_mode" value="1"
                   <?php echo $settings->get('maintenance_mode') === 'true' ? 'checked' : ''; ?>
                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
            <span class="ml-2 text-sm text-gray-600">
                Activar modo mantenimiento (solo administradores podrán acceder)
            </span>
        </div>
    </div>

    <!-- Botones -->
    <div class="flex justify-end space-x-3 pt-6 border-t">
        <button type="button" onclick="confirmReset()"
                class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded">
            Restaurar Valores
        </button>
        <button type="submit"
                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
            Guardar Cambios
        </button>
    </div>
</form>

<script>
function confirmReset() {
    if (confirm('¿Está seguro de restaurar los valores predeterminados? Esta acción no se puede deshacer.')) {
        window.location.href = '?reset=1';
    }
}

// Preview de imagen antes de subir
document.querySelector('input[name="company_logo"]').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        if (file.size > 2 * 1024 * 1024) {
            alert('El archivo es demasiado grande. El tamaño máximo permitido es 2MB.');
            e.target.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            const currentLogo = document.querySelector('img[alt="Logo actual"]');
            if (currentLogo) {
                currentLogo.src = e.target.result;
            } else {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.alt = 'Logo actual';
                img.className = 'h-12 mb-2';
                e.target.parentElement.insertBefore(img, e.target);
            }
        };
        reader.readAsDataURL(file);
    }
});
</script>