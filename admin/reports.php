<?php
require_once '../includes/session_check.php';
require_once '../config/database.php';

$database = new Database();
$db = $database->connect();

// Obtener rango de fechas
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Obtener estadísticas generales
$stmt = $db->prepare("
    SELECT 
        COUNT(*) as total_visits,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_visits,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_visits,
        SUM(CASE WHEN status = 'in_route' THEN 1 ELSE 0 END) as in_route_visits,
        AVG(TIMESTAMPDIFF(MINUTE, 
            CONCAT(visit_date, ' ', visit_time),
            CASE WHEN completion_time IS NOT NULL 
                THEN completion_time 
                ELSE NOW() 
            END
        )) as avg_completion_time,
        COUNT(DISTINCT technician_id) as active_technicians
    FROM visits 
    WHERE visit_date BETWEEN :start_date AND :end_date
");
$stmt->execute([':start_date' => $start_date, ':end_date' => $end_date]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Obtener datos para gráfico de visitas por día
$stmt = $db->prepare("
    SELECT 
        DATE(visit_date) as date,
        COUNT(*) as total,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'in_route' THEN 1 ELSE 0 END) as in_route
    FROM visits 
    WHERE visit_date BETWEEN :start_date AND :end_date
    GROUP BY DATE(visit_date)
    ORDER BY date
");
$stmt->execute([':start_date' => $start_date, ':end_date' => $end_date]);
$visits_by_date = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener rendimiento por técnico
$stmt = $db->prepare("
    SELECT 
        u.full_name,
        COUNT(*) as total_visits,
        SUM(CASE WHEN v.status = 'completed' THEN 1 ELSE 0 END) as completed_visits,
        AVG(TIMESTAMPDIFF(MINUTE, 
            CONCAT(v.visit_date, ' ', v.visit_time),
            v.completion_time
        )) as avg_completion_time,
        COUNT(DISTINCT DATE(v.visit_date)) as active_days
    FROM users u
    LEFT JOIN visits v ON u.id = v.technician_id 
        AND v.visit_date BETWEEN :start_date AND :end_date
    WHERE u.role = 'technician'
    GROUP BY u.id, u.full_name
    ORDER BY completed_visits DESC
");
$stmt->execute([':start_date' => $start_date, ':end_date' => $end_date]);
$technician_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener distribución por tipo de servicio
$stmt = $db->prepare("
    SELECT 
        service_type,
        COUNT(*) as total,
        COUNT(DISTINCT technician_id) as technicians_assigned,
        AVG(TIMESTAMPDIFF(MINUTE, 
            CONCAT(visit_date, ' ', visit_time),
            completion_time
        )) as avg_completion_time
    FROM visits 
    WHERE visit_date BETWEEN :start_date AND :end_date
        AND service_type IS NOT NULL
    GROUP BY service_type
    ORDER BY total DESC
");
$stmt->execute([':start_date' => $start_date, ':end_date' => $end_date]);
$service_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Reportes y Estadísticas';
ob_start();
?>

<div class="container mx-auto px-4 py-8">
    <!-- Filtros -->
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <form class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Inicial</label>
                <input type="date" name="start_date" value="<?php echo $start_date; ?>"
                       class="p-2 border rounded">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Final</label>
                <input type="date" name="end_date" value="<?php echo $end_date; ?>"
                       class="p-2 border rounded">
            </div>
            <div class="flex space-x-2">
                <button type="submit" 
                        class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                    <i class="fas fa-filter mr-2"></i>Filtrar
                </button>
                <button type="button" onclick="exportReport()" 
                        class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                    <i class="fas fa-file-excel mr-2"></i>Exportar
                </button>
            </div>
        </form>
    </div>

    <!-- KPIs -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold mb-2">Total Visitas</h3>
            <p class="text-3xl font-bold text-blue-600"><?php echo $stats['total_visits']; ?></p>
            <div class="mt-2 flex items-center text-sm text-gray-600">
                <span>Promedio: <?php echo round($stats['total_visits'] / max(1, $stats['active_technicians']), 1); ?> por técnico</span>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold mb-2">Tasa de Completitud</h3>
            <p class="text-3xl font-bold text-green-600">
                <?php echo round(($stats['completed_visits'] / max(1, $stats['total_visits'])) * 100, 1); ?>%
            </p>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold mb-2">Tiempo Promedio</h3>
            <p class="text-3xl font-bold text-yellow-600">
                <?php echo round($stats['avg_completion_time'] / 60, 1); ?>h
            </p>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold mb-2">Técnicos Activos</h3>
            <p class="text-3xl font-bold text-purple-600"><?php echo $stats['active_technicians']; ?></p>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Gráfico de visitas por día -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold mb-4">Visitas por Día</h3>
            <div id="visitsChart" style="height: 300px;"></div>
        </div>

        <!-- Gráfico de distribución por tipo de servicio -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold mb-4">Distribución por Servicio</h3>
            <div id="servicesChart" style="height: 300px;"></div>
        </div>
    </div>

    <!-- Tabla de rendimiento por técnico -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold mb-4">Rendimiento por Técnico</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Técnico
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Total Visitas
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Completadas
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Tiempo Promedio
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Días Activos
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($technician_stats as $tech): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php echo htmlspecialchars($tech['full_name']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php echo $tech['total_visits']; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php echo $tech['completed_visits']; ?>
                                (<?php echo round(($tech['completed_visits'] / max(1, $tech['total_visits'])) * 100, 1); ?>%)
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php echo round($tech['avg_completion_time'] / 60, 1); ?>h
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php echo $tech['active_days']; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Scripts para gráficos -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
// Datos para los gráficos
const visitsData = <?php echo json_encode($visits_by_date); ?>;
const serviceData = <?php echo json_encode($service_stats); ?>;

// Gráfico de visitas por día
new ApexCharts(document.querySelector("#visitsChart"), {
    chart: {
        type: 'area',
        height: 300,
        toolbar: {
            show: false
        }
    },
    series: [{
        name: 'Completadas',
        data: visitsData.map(d => d.completed)
    }, {
        name: 'En Ruta',
        data: visitsData.map(d => d.in_route)
    }, {
        name: 'Pendientes',
        data: visitsData.map(d => d.pending)
    }],
    xaxis: {
        categories: visitsData.map(d => d.date),
        type: 'datetime'
    },
    yaxis: {
        title: {
            text: 'Número de visitas'
        }
    },
    colors: ['#059669', '#d97706', '#2563eb'],
    fill: {
        type: 'gradient'
    }
}).render();

// Gráfico de distribución por tipo de servicio
new ApexCharts(document.querySelector("#servicesChart"), {
    chart: {
        type: 'donut',
        height: 300
    },
    series: serviceData.map(s => s.total),
    labels: serviceData.map(s => s.service_type),
    colors: ['#2563eb', '#059669', '#d97706', '#7c3aed'],
}).render();

// Función para exportar reportes
function exportReport() {
    const startDate = document.querySelector('input[name="start_date"]').value;
    const endDate = document.querySelector('input[name="end_date"]').value;
    
    // Crear la URL con los parámetros
    window.location.href = `actions/export_report.php?start_date=${encodeURIComponent(startDate)}&end_date=${encodeURIComponent(endDate)}`;
}
</script>

<?php
$content = ob_get_clean();
require_once '../includes/layout.php';
?>