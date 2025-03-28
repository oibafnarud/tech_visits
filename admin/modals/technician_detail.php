<div id="technicianDetailModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="relative top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-2xl">
        <div class="bg-white rounded-lg shadow-xl m-4">
            <!-- Cabecera -->
            <div class="flex justify-between items-center p-6 border-b">
                <h3 class="text-xl font-bold">Detalles del Técnico</h3>
                <button onclick="document.getElementById('technicianDetailModal').classList.add('hidden')"
                        class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Contenido -->
            <div class="p-6">
                <div id="technicianDetailContent">
                    <!-- El contenido se cargará dinámicamente -->
                </div>

                <div class="mt-6 border-t pt-6">
                    <h4 class="text-lg font-medium mb-4">Visitas Recientes</h4>
                    <div id="recentVisitsContent" class="space-y-4">
                        <!-- Las visitas se cargarán dinámicamente -->
                    </div>
                </div>

                <div class="mt-6 border-t pt-6">
                    <h4 class="text-lg font-medium mb-4">Estadísticas</h4>
                    <div class="grid grid-cols-2 gap-4" id="technicianStats">
                        <!-- Las estadísticas se cargarán dinámicamente -->
                    </div>
                </div>
            </div>

            <!-- Pie del modal -->
            <div class="bg-gray-50 px-6 py-4 rounded-b-lg flex justify-end space-x-3">
                <button onclick="document.getElementById('technicianDetailModal').classList.add('hidden')"
                        class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    Cerrar
                </button>
                <button onclick="editTechnician(currentTechnicianId)"
                        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                    Editar Técnico
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let currentTechnicianId = null;

function showTechnicianDetail(id) {
    currentTechnicianId = id;
    
    // Mostrar el modal con un loader
    const modal = document.getElementById('technicianDetailModal');
    document.getElementById('technicianDetailContent').innerHTML = `
        <div class="flex justify-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        </div>
    `;
    modal.classList.remove('hidden');

    // Cargar los detalles
    fetch(`actions/technician_actions.php?action=get_details&id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const tech = data.technician;
                
                // Información básica
                document.getElementById('technicianDetailContent').innerHTML = `
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Nombre Completo</p>
                            <p class="mt-1">${tech.full_name}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Usuario</p>
                            <p class="mt-1">${tech.username}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Email</p>
                            <p class="mt-1">
                                <a href="mailto:${tech.email}" class="text-blue-600 hover:underline">
                                    ${tech.email}
                                </a>
                            </p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Teléfono</p>
                            <p class="mt-1">
                                <a href="tel:${tech.phone}" class="text-blue-600 hover:underline">
                                    ${tech.phone}
                                </a>
                            </p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Estado</p>
                            <p class="mt-1">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    ${tech.active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                                    ${tech.active ? 'Activo' : 'Inactivo'}
                                </span>
                            </p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Fecha de Registro</p>
                            <p class="mt-1">${new Date(tech.created_at).toLocaleDateString()}</p>
                        </div>
                    </div>
                `;

<!-- Continuación del script anterior -->
                // Estadísticas
                document.getElementById('technicianStats').innerHTML = `
                    <div class="text-center p-4 bg-blue-50 rounded-lg">
                        <div class="text-2xl font-bold text-blue-600">${tech.stats.total_visits}</div>
                        <div class="text-sm text-gray-600">Total Visitas</div>
                    </div>
                    <div class="text-center p-4 bg-green-50 rounded-lg">
                        <div class="text-2xl font-bold text-green-600">${tech.stats.completed_visits}</div>
                        <div class="text-sm text-gray-600">Completadas</div>
                    </div>
                    <div class="text-center p-4 bg-yellow-50 rounded-lg">
                        <div class="text-2xl font-bold text-yellow-600">${tech.stats.pending_visits}</div>
                        <div class="text-sm text-gray-600">Pendientes</div>
                    </div>
                    <div class="text-center p-4 bg-purple-50 rounded-lg">
                        <div class="text-2xl font-bold text-purple-600">${Math.round(tech.stats.completion_rate)}%</div>
                        <div class="text-sm text-gray-600">Tasa de Completitud</div>
                    </div>
                `;

                // Visitas Recientes
                if (tech.recent_visits && tech.recent_visits.length > 0) {
                    const visitsHtml = tech.recent_visits.map(visit => `
                        <div class="border rounded-lg p-4 ${
                            visit.status === 'completed' ? 'border-green-200 bg-green-50' :
                            visit.status === 'in_route' ? 'border-yellow-200 bg-yellow-50' :
                            'border-gray-200'
                        }">
                            <div class="flex justify-between items-start">
                                <div>
                                    <div class="font-medium">${visit.client_name}</div>
                                    <div class="text-sm text-gray-600">
                                        ${new Date(visit.visit_date).toLocaleDateString()} - 
                                        ${visit.visit_time}
                                    </div>
                                </div>
                                <span class="px-2 py-1 text-xs rounded-full ${
                                    visit.status === 'completed' ? 'bg-green-200 text-green-800' :
                                    visit.status === 'in_route' ? 'bg-yellow-200 text-yellow-800' :
                                    'bg-blue-200 text-blue-800'
                                }">
                                    ${visit.status.charAt(0).toUpperCase() + visit.status.slice(1)}
                                </span>
                            </div>
                            <div class="mt-2 text-sm text-gray-600">
                                <i class="fas fa-map-marker-alt mr-1"></i>
                                ${visit.address}
                            </div>
                        </div>
                    `).join('');
                    
                    document.getElementById('recentVisitsContent').innerHTML = visitsHtml;
                } else {
                    document.getElementById('recentVisitsContent').innerHTML = `
                        <div class="text-center text-gray-500 py-4">
                            No hay visitas recientes
                        </div>
                    `;
                }

                // Gráfico de actividad (opcional)
                if (tech.activity_chart) {
                    renderActivityChart(tech.activity_chart);
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('technicianDetailContent').innerHTML = `
                <div class="text-center text-red-600 py-4">
                    Error al cargar los detalles del técnico
                </div>
            `;
        });
}

// Función para renderizar el gráfico de actividad
function renderActivityChart(data) {
    // Aquí puedes usar una librería de gráficos como Chart.js o similar
    // Por ahora, lo dejamos como un placeholder
    const chartContainer = document.createElement('div');
    chartContainer.className = 'mt-6 border-t pt-6';
    chartContainer.innerHTML = `
        <h4 class="text-lg font-medium mb-4">Actividad Reciente</h4>
        <div class="bg-gray-50 rounded-lg p-4">
            <div id="activityChart" class="h-48">
                <!-- Aquí iría el gráfico -->
            </div>
        </div>
    `;
    document.getElementById('technicianDetailContent').appendChild(chartContainer);
}

// Función para exportar datos del técnico
function exportTechnicianData(id) {
    fetch(`actions/technician_actions.php?action=export_data&id=${id}`)
        .then(response => response.blob())
        .then(blob => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `tecnico_${id}_reporte.xlsx`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            a.remove();
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error al exportar los datos', 'error');
        });
}
</script>