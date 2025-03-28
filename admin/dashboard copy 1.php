<?php
require_once '../includes/session_check.php';
require_once '../config/database.php';

$database = new Database();
$db = $database->connect();

// Obtener estadísticas del día
$today = date('Y-m-d');
$stmt = $db->prepare("
    SELECT 
        COUNT(*) as total_visits,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_visits,
        SUM(CASE WHEN status = 'in_route' THEN 1 ELSE 0 END) as in_route_visits,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_visits,
        COUNT(DISTINCT technician_id) as active_technicians
    FROM visits 
    WHERE visit_date = :today
");
$stmt->execute([':today' => $today]);
$daily_stats = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $db->prepare("
    SELECT 
        DATE(visit_date) as date,
        COUNT(*) as total,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'in_route' THEN 1 ELSE 0 END) as in_route,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        COUNT(DISTINCT technician_id) as technicians
    FROM visits 
    WHERE visit_date >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
    GROUP BY DATE(visit_date)
    ORDER BY date ASC
");
$stmt->execute();
$trends = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener técnicos en ruta actualmente
$stmt = $db->prepare("
    SELECT 
        u.id,
        u.full_name,
        u.phone,
        COUNT(v.id) as total_today,
        SUM(CASE WHEN v.status = 'completed' THEN 1 ELSE 0 END) as completed_today,
        SUM(CASE WHEN v.status = 'in_route' THEN 1 ELSE 0 END) as in_route,
        MAX(CASE WHEN v.status = 'in_route' THEN v.client_name ELSE NULL END) as current_client,
        MAX(CASE WHEN v.status = 'in_route' THEN v.address ELSE NULL END) as current_location,
        MAX(CASE WHEN v.status = 'in_route' THEN v.visit_time ELSE NULL END) as current_visit_time
    FROM users u
    LEFT JOIN visits v ON v.technician_id = u.id 
        AND v.visit_date = CURRENT_DATE
    WHERE u.role = 'technician' 
        AND u.active = 1
    GROUP BY u.id
    HAVING in_route > 0
    ORDER BY current_visit_time ASC
");
$stmt->execute();
$technicians_in_route = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener próximas visitas de hoy
$stmt = $db->prepare("
   SELECT 
       v.*,
       u.full_name as technician_name,
       TIMESTAMPDIFF(MINUTE, NOW(), CONCAT(v.visit_date, ' ', v.visit_time)) as minutes_until
   FROM visits v
   JOIN users u ON v.technician_id = u.id
   WHERE v.visit_date = CURRENT_DATE
   AND v.status = 'pending'
   AND v.visit_time > NOW()
   ORDER BY v.visit_time ASC
   LIMIT 5
");
$stmt->execute();
$upcoming_visits = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener resumen de servicios más comunes
$stmt = $db->prepare("
    SELECT 
        service_type,
        COUNT(*) as total,
        COUNT(DISTINCT technician_id) as technicians,
        AVG(CASE 
            WHEN status = 'completed' AND completion_time IS NOT NULL 
            THEN TIMESTAMPDIFF(MINUTE, CONCAT(visit_date, ' ', visit_time), completion_time)
            ELSE NULL 
        END) as avg_duration
    FROM visits
    WHERE visit_date >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
    GROUP BY service_type
    ORDER BY total DESC
    LIMIT 5
");
$stmt->execute();
$service_summary = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener estadísticas de cumplimiento
$stmt = $db->prepare("
    SELECT 
        DATE_FORMAT(visit_date, '%Y-%m') as month,
        COUNT(*) as total_visits,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_visits,
        AVG(CASE 
            WHEN status = 'completed' AND completion_time IS NOT NULL 
            THEN TIMESTAMPDIFF(MINUTE, CONCAT(visit_date, ' ', visit_time), completion_time)
            ELSE NULL 
        END) as avg_completion_time
    FROM visits
    WHERE visit_date >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(visit_date, '%Y-%m')
    ORDER BY month DESC
");
$stmt->execute();
$monthly_performance = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener top técnicos
$stmt = $db->prepare("
    SELECT 
        u.full_name,
        COUNT(v.id) as total_visits,
        SUM(CASE WHEN v.status = 'completed' THEN 1 ELSE 0 END) as completed_visits,
        ROUND((SUM(CASE WHEN v.status = 'completed' THEN 1 ELSE 0 END) / COUNT(v.id)) * 100, 1) as completion_rate,
        AVG(CASE 
            WHEN v.status = 'completed' AND v.completion_time IS NOT NULL 
            THEN TIMESTAMPDIFF(MINUTE, CONCAT(v.visit_date, ' ', v.visit_time), v.completion_time)
            ELSE NULL 
        END) as avg_completion_time
    FROM users u
    LEFT JOIN visits v ON v.technician_id = u.id 
        AND v.visit_date >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
    WHERE u.role = 'technician' AND u.active = 1
    GROUP BY u.id
    HAVING total_visits > 0
    ORDER BY completion_rate DESC, total_visits DESC
    LIMIT 5
");
$stmt->execute();
$top_technicians = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener estadísticas de los últimos 7 días
$stmt = $db->prepare("
    SELECT 
        DATE(visit_date) as date,
        COUNT(*) as total,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'in_route' THEN 1 ELSE 0 END) as in_route
    FROM visits 
    WHERE visit_date >= DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY)
    GROUP BY DATE(visit_date)
    ORDER BY date ASC
");
$stmt->execute();
$weekly_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Para visitas urgentes:
$stmt = $db->prepare("
    SELECT 
        v.*,
        u.full_name as technician_name,
        u.phone as technician_phone,
        TIMESTAMPDIFF(MINUTE, NOW(), CONCAT(v.visit_date, ' ', v.visit_time)) as minutes_until
    FROM visits v
    JOIN users u ON v.technician_id = u.id
    WHERE v.visit_date = CURRENT_DATE
    AND v.status = 'pending'
    AND TIMESTAMPDIFF(MINUTE, NOW(), CONCAT(v.visit_date, ' ', v.visit_time)) <= 30
    AND v.visit_time >= CURTIME()
    ORDER BY v.visit_time ASC
");

$stmt->execute();
$urgent_visits = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Dashboard';
ob_start();
?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    
    <!-- Scripts necesarios -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/leaflet.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/leaflet.min.css" />
    
    <style>
    .leaflet-container {
        height: 100%;
        width: 100%;
    }
    </style>

</head>

<div class="container mx-auto px-4 py-8">
    <!-- Tarjetas de Estadísticas -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <!-- Total Visitas -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-gray-500">Visitas Hoy</p>
                    <h3 class="text-3xl font-bold mt-1"><?php echo $daily_stats['total_visits']; ?></h3>
                </div>
                <span class="p-3 bg-blue-100 text-blue-600 rounded-full">
                    <i class="fas fa-calendar-day text-xl"></i>
                </span>
            </div>
            <div class="flex items-center mt-4">
                <div class="flex-1">
                    <div class="h-2 bg-gray-200 rounded-full">
                        <?php 
                        $completion_rate = $daily_stats['total_visits'] > 0 
                            ? ($daily_stats['completed_visits'] / $daily_stats['total_visits']) * 100 
                            : 0;
                        ?>
                        <div class="h-2 bg-blue-600 rounded-full" style="width: <?php echo $completion_rate; ?>%"></div>
                    </div>
                </div>
                <span class="ml-2 text-sm text-gray-600">
                    <?php echo round($completion_rate); ?>%
                </span>
            </div>
        </div>

        <!-- En Ruta -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-gray-500">En Ruta</p>
                    <h3 class="text-3xl font-bold mt-1"><?php echo $daily_stats['in_route_visits']; ?></h3>
                </div>
                <span class="p-3 bg-yellow-100 text-yellow-600 rounded-full">
                    <i class="fas fa-truck text-xl"></i>
                </span>
            </div>
            <div class="mt-4 text-sm">
                <?php 
                $inRoutePercent = $daily_stats['total_visits'] > 0 
                    ? ($daily_stats['in_route_visits'] / $daily_stats['total_visits']) * 100 
                    : 0;
                ?>
                <span class="text-yellow-600"><?php echo round($inRoutePercent); ?>%</span> del total
            </div>
        </div>

        <!-- Pendientes -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-gray-500">Pendientes</p>
                    <h3 class="text-3xl font-bold mt-1"><?php echo $daily_stats['pending_visits']; ?></h3>
                </div>
                <span class="p-3 bg-red-100 text-red-600 rounded-full">
                    <i class="fas fa-clock text-xl"></i>
                </span>
            </div>
            <div class="mt-4 text-sm">
                <?php 
                $pendingPercent = $daily_stats['total_visits'] > 0 
                    ? ($daily_stats['pending_visits'] / $daily_stats['total_visits']) * 100 
                    : 0;
                ?>
                <span class="text-red-600"><?php echo round($pendingPercent); ?>%</span> por atender
            </div>
        </div>

        <!-- Técnicos Activos -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-gray-500">Técnicos Activos</p>
                    <h3 class="text-3xl font-bold mt-1"><?php echo $daily_stats['active_technicians']; ?></h3>
                </div>
                <span class="p-3 bg-green-100 text-green-600 rounded-full">
                    <i class="fas fa-users text-xl"></i>
                </span>
            </div>
            <div class="mt-4 text-sm text-green-600">
                <?php echo $daily_stats['completed_visits']; ?> visitas completadas
            </div>
        </div>
    </div>

    <!-- Gráficos y Mapas -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Gráfico de Tendencias -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold mb-4">Tendencia de Visitas</h2>
            <div id="visitsChart" class="h-80"></div>
        </div>

        <!-- Mapa de Visitas -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold mb-4">Visitas del Día</h2>
            <div id="visitsMap" style="height:400px; width:100%;" class="border rounded"></div>
        </div>
    </div>

    <!-- Lista de Técnicos y Próximas Visitas -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Lista de Técnicos -->
        <div class="lg:col-span-1 bg-white rounded-lg shadow-sm">
            <div class="p-6 border-b">
                <h2 class="text-lg font-semibold">Técnicos en Ruta</h2>
            </div>
            <div class="p-6">
                <?php include 'views/dashboard_technicians.php'; ?>
            </div>
        </div>

        <!-- Próximas Visitas -->
        <div class="lg:col-span-2 bg-white rounded-lg shadow-sm">
            <div class="p-6 border-b">
                <h2 class="text-lg font-semibold">Próximas Visitas</h2>
            </div>
            <div class="p-6">
                <?php include 'views/dashboard_upcoming.php'; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/leaflet.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/leaflet.min.css" />

<script>
const trendData = <?php echo json_encode($trends); ?>;

// Gráfico de tendencias
new ApexCharts(document.querySelector("#visitsChart"), {
    chart: {
        type: 'area',
        height: 350,
        stacked: true,
        toolbar: {
            show: false
        }
    },
    series: [{
        name: 'Completadas',
        data: trendData.map(d => d.completed)
    }, {
        name: 'En Ruta',
        data: trendData.map(d => d.in_route)
    }, {
        name: 'Pendientes',
        data: trendData.map(d => d.pending)
    }],
    xaxis: {
        categories: trendData.map(d => d.date),
        type: 'datetime'
    },
    yaxis: {
        title: {
            text: 'Cantidad de Visitas'
        }
    },
    colors: ['#10B981', '#F59E0B', '#3B82F6'],
    fill: {
        type: 'gradient',
        gradient: {
            opacityFrom: 0.6,
            opacityTo: 0.1
        }
    },
    legend: {
        position: 'top',
        horizontalAlign: 'right'
    },
    tooltip: {
        shared: true,
        intersect: false
    }
}).render();

// Inicializar mapa
document.addEventListener('DOMContentLoaded', function() {
    const map = L.map('visitsMap');
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    // Vista por defecto de Santo Domingo
    map.setView([18.4861, -69.9312], 13);
});

fetch('actions/get_map_data.php')
    .then(response => response.json())
    .then(data => {
        const markers = [];
        data.data.forEach(visit => {
            // Extraer coordenadas de URL de Google Maps (ej: @18.xxx,-69.xxx)
            const match = visit.location_url.match(/@(-?\d+\.\d+),(-?\d+\.\d+)/);
            if (match) {
                const lat = parseFloat(match[1]);
                const lng = parseFloat(match[2]);
                const marker = L.marker([lat, lng]).addTo(map);
                marker.bindPopup(`
                    <div class="font-medium">${visit.client_name}</div>
                    <div>${visit.visit_time} - ${visit.technician_name}</div>
                    <div>${visit.status}</div>
                `);
                markers.push(marker);
            }
        });
        
        if (markers.length > 0) {
            const group = L.featureGroup(markers);
            map.fitBounds(group.getBounds());
        }
    })
    .catch(error => console.error('Error:', error));

// Cargar marcadores de visitas
fetch('actions/get_today_visits.php')
    .then(response => response.json())
    .then(data => {
        if (data.success && data.data.length > 0) {
            data.data.forEach(visit => {
                if (visit.location_lat && visit.location_lng) {
                    const marker = L.marker([visit.location_lat, visit.location_lng])
                        .bindPopup(`
                            <b>${visit.client_name}</b><br>
                            ${visit.address}<br>
                            <small>${visit.visit_time} - ${visit.technician_name}</small>
                        `)
                        .addTo(map);
                }
            });
            // Ajustar vista a los marcadores
            const bounds = L.featureGroup(map._layers).getBounds();
            map.fitBounds(bounds);
        } else {
            document.getElementById('visitsMap').innerHTML = 
                '<div class="flex items-center justify-center h-full text-gray-500">No hay visitas con ubicación para mostrar</div>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('visitsMap').innerHTML = 
            '<div class="flex items-center justify-center h-full text-red-500">Error al cargar las ubicaciones</div>';
    });
    
    setInterval(() => {
    window.location.reload();
    }, 5 * 60 * 1000);
</script>

<?php
$content = ob_get_clean();
require_once '../includes/layout.php';
?>