<?php
require_once '../includes/session_check.php';
require_once '../config/database.php';

$database = new Database();
$db = $database->connect();

// Rango de fechas (por defecto últimos 7 días)
$end_date = date('Y-m-d');
$start_date = date('Y-m-d', strtotime('-7 days'));

if (isset($_GET['range'])) {
    switch($_GET['range']) {
        case 'today':
            $start_date = $end_date;
            break;
        case 'week':
            $start_date = date('Y-m-d', strtotime('-7 days'));
            break;
        case 'month':
            $start_date = date('Y-m-d', strtotime('-30 days'));
            break;
    }
}

$settings = $db->query("SELECT company_logo FROM settings WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
$logo_url = $settings['company_logo'] ? '/assets/images/' . $settings['company_logo'] : '/assets/images/default-logo.png';

// Estadísticas generales del período
$stmt = $db->prepare("
    SELECT 
        COUNT(*) as total_visits,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_visits,
        SUM(CASE WHEN status = 'in_route' THEN 1 ELSE 0 END) as in_route_visits,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_visits,
        COUNT(DISTINCT technician_id) as active_technicians,
        COUNT(DISTINCT DATE(visit_date)) as working_days
    FROM visits 
    WHERE visit_date BETWEEN :start_date AND :end_date
");
$stmt->execute([':start_date' => $start_date, ':end_date' => $end_date]);
$period_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Visitas sin confirmar (realizadas pero no marcadas)
$stmt = $db->prepare("
    SELECT 
        v.*,
        u.full_name as technician_name,
        u.phone as technician_phone
    FROM visits v
    JOIN users u ON v.technician_id = u.id
    WHERE v.visit_date < CURRENT_DATE
    AND v.status IN ('pending', 'in_route')
    ORDER BY v.visit_date DESC, v.visit_time DESC
    LIMIT 10
");
$stmt->execute();
$unconfirmed_visits = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Técnicos actualmente en ruta
$stmt = $db->prepare("
    SELECT 
        u.id,
        u.full_name,
        u.phone,
        v.client_name,
        v.address,
        v.visit_time,
        v.location_url,
        TIMESTAMPDIFF(MINUTE, CONCAT(v.visit_date, ' ', v.visit_time), NOW()) as minutes_elapsed
    FROM users u
    JOIN visits v ON v.technician_id = u.id
    WHERE v.status = 'in_route'
    AND v.visit_date >= DATE_SUB(CURRENT_DATE, INTERVAL 1 DAY)
    ORDER BY v.visit_date DESC, v.visit_time DESC
");
$stmt->execute();
$active_technicians = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Rendimiento por técnico
$stmt = $db->prepare("
    SELECT 
        u.full_name,
        COUNT(*) as total_visits,
        SUM(CASE WHEN v.status = 'completed' THEN 1 ELSE 0 END) as completed_visits,
        AVG(TIMESTAMPDIFF(MINUTE, CONCAT(v.visit_date, ' ', v.visit_time), v.completion_time)) as avg_completion_time
    FROM users u
    LEFT JOIN visits v ON v.technician_id = u.id 
        AND v.visit_date BETWEEN :start_date AND :end_date
    WHERE u.role = 'technician' AND u.active = 1
    GROUP BY u.id
    HAVING total_visits > 0
    ORDER BY completed_visits DESC
");
$stmt->execute([':start_date' => $start_date, ':end_date' => $end_date]);
$technician_performance = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Próximas visitas urgentes
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
    AND TIMESTAMPDIFF(MINUTE, NOW(), CONCAT(v.visit_date, ' ', v.visit_time)) BETWEEN -30 AND 30
    ORDER BY v.visit_time ASC
");
$stmt->execute();
$urgent_visits = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->prepare("
    SELECT 
        DATE(visit_date) as date,
        COUNT(*) as total,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'in_route' THEN 1 ELSE 0 END) as in_route,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
    FROM visits 
    WHERE visit_date BETWEEN :start_date AND :end_date
    GROUP BY DATE(visit_date)
    ORDER BY date ASC
");
$stmt->execute([':start_date' => $start_date, ':end_date' => $end_date]);
$chart_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

//Maps
$stmt = $db->prepare("
    SELECT 
        v.*,
        u.full_name as technician_name
    FROM visits v
    JOIN users u ON v.technician_id = u.id
    WHERE v.location_lat IS NOT NULL 
    AND v.location_lng IS NOT NULL
    AND v.visit_date BETWEEN :start_date AND :end_date
");
$stmt->execute([':start_date' => $start_date, ':end_date' => $end_date]);
$map_visits = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Agregar debug
error_log("SQL Debug - Visitas encontradas: " . json_encode([
    'total' => count($map_visits),
    'start_date' => $start_date,
    'end_date' => $end_date,
    'visitas' => $map_visits
]));

$page_title = 'Dashboard';
ob_start();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Sistema de Visitas</title>
    
    <!-- Estilos -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/leaflet.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/leaflet.min.css" />
    
    <style>
    .dashboard-card {
        @apply bg-white rounded-lg shadow-sm p-6;
    }
    .card-title {
        @apply text-lg font-semibold mb-4;
    }
    .status-badge {
        @apply px-2 py-1 rounded-full text-xs font-medium;
    }
    .status-badge.completed {
        @apply bg-green-100 text-green-800;
    }
    .status-badge.in-route {
        @apply bg-yellow-100 text-yellow-800;
    }
    .status-badge.pending {
        @apply bg-red-100 text-red-800;
    }
    .action-button {
        @apply p-2 rounded-full hover:bg-gray-100 transition-colors;
    }
    .leaflet-container {
        height: 400px;
        width: 100%;
        border-radius: 0.5rem;
    }
    </style>
</head>

<body class="bg-gray-100">

<div class="container mx-auto px-4 py-8">
   <!-- Selector de rango de fechas -->
   <div class="mb-6 flex justify-between items-center">
       <div class="flex space-x-2">
           <button onclick="changeRange('today')" 
                   class="px-4 py-2 rounded-lg <?php echo $_GET['range'] === 'today' ? 'bg-blue-600 text-white' : 'bg-gray-100'; ?>">
               Hoy
           </button>
           <button onclick="changeRange('week')"
                   class="px-4 py-2 rounded-lg <?php echo $_GET['range'] === 'week' ? 'bg-blue-600 text-white' : 'bg-gray-100'; ?>">
               Última Semana
           </button>
           <button onclick="changeRange('month')"
                   class="px-4 py-2 rounded-lg <?php echo $_GET['range'] === 'month' ? 'bg-blue-600 text-white' : 'bg-gray-100'; ?>">
               Último Mes
           </button>
       </div>
       <div class="text-sm text-gray-600">
           <?php echo date('d/m/Y', strtotime($start_date)); ?> - 
           <?php echo date('d/m/Y', strtotime($end_date)); ?>
       </div>
   </div>

   <!-- Estadísticas Generales -->
   <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
       <div class="bg-white rounded-lg shadow-sm p-6">
           <div class="flex justify-between items-start">
               <div>
                   <p class="text-gray-500">Total Visitas</p>
                   <h3 class="text-3xl font-bold mt-1"><?php echo $period_stats['total_visits']; ?></h3>
               </div>
               <span class="p-3 bg-blue-100 text-blue-600 rounded-full">
                   <i class="fas fa-calendar text-xl"></i>
               </span>
           </div>
           <div class="mt-4 text-sm">
               <span class="text-gray-600">
                   <?php echo round($period_stats['total_visits'] / $period_stats['working_days'], 1); ?> 
                   visitas/día
               </span>
           </div>
       </div>

       <div class="bg-white rounded-lg shadow-sm p-6">
           <div class="flex justify-between items-start">
               <div>
                   <p class="text-gray-500">Completadas</p>
                   <h3 class="text-3xl font-bold mt-1">
                       <?php echo $period_stats['completed_visits']; ?>
                   </h3>
               </div>
               <span class="p-3 bg-green-100 text-green-600 rounded-full">
                   <i class="fas fa-check text-xl"></i>
               </span>
           </div>
           <div class="mt-4 text-sm">
               <span class="text-green-600">
                   <?php 
                   echo $period_stats['total_visits'] > 0 
                       ? round(($period_stats['completed_visits'] / $period_stats['total_visits']) * 100) 
                       : 0; 
                   ?>% 
                   completadas
               </span>
           </div>
       </div>

       <div class="bg-white rounded-lg shadow-sm p-6">
           <div class="flex justify-between items-start">
               <div>
                   <p class="text-gray-500">En Ruta</p>
                   <h3 class="text-3xl font-bold mt-1">
                       <?php echo $period_stats['in_route_visits']; ?>
                   </h3>
               </div>
               <span class="p-3 bg-yellow-100 text-yellow-600 rounded-full">
                   <i class="fas fa-truck text-xl"></i>
               </span>
           </div>
           <div class="mt-4 text-sm">
               <?php echo count($active_technicians); ?> técnicos activos
           </div>
       </div>

       <div class="bg-white rounded-lg shadow-sm p-6">
           <div class="flex justify-between items-start">
               <div>
                   <p class="text-gray-500">Sin Confirmar</p>
                   <h3 class="text-3xl font-bold mt-1">
                       <?php echo count($unconfirmed_visits); ?>
                   </h3>
               </div>
               <span class="p-3 bg-red-100 text-red-600 rounded-full">
                   <i class="fas fa-exclamation-circle text-xl"></i>
               </span>
           </div>
           <div class="mt-4 text-sm text-red-600">
               Requieren atención
           </div>
       </div>
   </div>

   <!-- Grid Principal -->
   <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
       <!-- Visitas Sin Confirmar -->
       <div class="lg:col-span-2 bg-white rounded-lg shadow-sm">
           <div class="p-6 border-b">
               <h2 class="text-lg font-semibold">Visitas Sin Confirmar</h2>
           </div>
           <div class="p-6">
               <?php if (empty($unconfirmed_visits)): ?>
                   <p class="text-center text-gray-500 py-4">No hay visitas pendientes de confirmar</p>
               <?php else: ?>
                   <div class="space-y-4">
                       <?php foreach ($unconfirmed_visits as $visit): ?>
                           <div class="flex items-center justify-between p-4 bg-red-50 rounded-lg">
                               <div>
                                   <div class="font-medium">
                                       <?php echo htmlspecialchars($visit['client_name']); ?>
                                   </div>
                                   <div class="text-sm text-gray-600">
                                       <?php echo date('d/m/Y H:i', strtotime($visit['visit_date'] . ' ' . $visit['visit_time'])); ?>
                                   </div>
                                   <div class="text-sm text-gray-500">
                                       <?php echo htmlspecialchars($visit['technician_name']); ?>
                                   </div>
                               </div>
                               <div class="flex space-x-2">
                                   <?php if ($visit['location_url']): ?>
                                       <a href="<?php echo htmlspecialchars($visit['location_url']); ?>" 
                                          target="_blank"
                                          class="p-2 text-blue-600 hover:bg-blue-50 rounded">
                                           <i class="fas fa-map-marker-alt"></i>
                                       </a>
                                   <?php endif; ?>
                                   <a href="tel:<?php echo $visit['technician_phone']; ?>"
                                      class="p-2 text-green-600 hover:bg-green-50 rounded">
                                       <i class="fas fa-phone"></i>
                                   </a>
                                   <button onclick="markAsCompleted(<?php echo $visit['id']; ?>)"
                                           class="p-2 text-gray-600 hover:bg-gray-50 rounded">
                                       <i class="fas fa-check"></i>
                                   </button>
                               </div>
                           </div>
                       <?php endforeach; ?>
                   </div>
               <?php endif; ?>
           </div>
       </div>

       <!-- Técnicos en Ruta -->
       <div class="bg-white rounded-lg shadow-sm">
           <div class="p-6 border-b">
               <h2 class="text-lg font-semibold">Técnicos en Ruta</h2>
           </div>
           <div class="p-6">
               <?php if (empty($active_technicians)): ?>
                   <p class="text-center text-gray-500 py-4">No hay técnicos en ruta</p>
               <?php else: ?>
                   <div class="space-y-4">
                       <?php foreach ($active_technicians as $tech): ?>
                           <div class="p-4 bg-yellow-50 rounded-lg">
                               <div class="flex justify-between items-start">
                                   <div>
                                       <div class="font-medium">
                                           <?php echo htmlspecialchars($tech['full_name']); ?>
                                       </div>
                                       <div class="text-sm text-gray-600 mt-1">
                                           <?php echo htmlspecialchars($tech['client_name']); ?>
                                       </div>
                                       <div class="text-xs text-gray-500 mt-1">
                                           <?php 
                                           $hours = floor($tech['minutes_elapsed'] / 60);
                                           $minutes = $tech['minutes_elapsed'] % 60;
                                           echo "Hace {$hours}h {$minutes}m";
                                           ?>
                                       </div>
                                   </div>
                                   <div class="flex space-x-2">
                                       <?php if ($tech['location_url']): ?>
                                           <a href="<?php echo htmlspecialchars($tech['location_url']); ?>" 
                                              target="_blank"
                                              class="p-2 text-blue-600 hover:bg-blue-50 rounded">
                                               <i class="fas fa-map-marker-alt"></i>
                                           </a>
                                       <?php endif; ?>
                                       <a href="tel:<?php echo $tech['phone']; ?>"
                                          class="p-2 text-green-600 hover:bg-green-50 rounded">
                                           <i class="fas fa-phone"></i>
                                       </a>
                                   </div>
                               </div>
                           </div>
                       <?php endforeach; ?>
                   </div>
               <?php endif; ?>
           </div>
       </div>
       
                <!-- Gráfico de Tendencias -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-lg font-semibold mb-4">Tendencia de Visitas</h2>
                    <div id="trendsChart" class="h-80"></div>
                </div>
        
                <!-- Gráfico de Distribución -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-lg font-semibold mb-4">Distribución por Estado</h2>
                    <div id="distributionChart" class="h-80"></div>
                </div>
       
   </div>
   
      <div class="grid grid-cols-1 lg:grid-cols-1 gap-6">
        <div class="bg-white rounded-lg shadow-sm p-6">
           <h2 class="text-lg font-semibold mb-4">Distribución de Visitas</h2>
           <div id="visitsMap" class="h-[400px] w-full rounded-lg"></div>
        </div>
    </div>

</div>

<script>

// Configuración de gráficos
const chartData = <?php echo json_encode($chart_data); ?>;

// Gráfico de Tendencias
new ApexCharts(document.querySelector("#trendsChart"), {
    chart: {
        type: 'area',
        height: 350,
        stacked: true
    },
    series: [{
        name: 'Completadas',
        data: chartData.map(d => d.completed)
    }, {
        name: 'En Ruta',
        data: chartData.map(d => d.in_route)
    }, {
        name: 'Pendientes',
        data: chartData.map(d => d.pending)
    }],
    xaxis: {
        categories: chartData.map(d => d.date),
        type: 'datetime'
    },
    colors: ['#10B981', '#F59E0B', '#EF4444'],
    fill: {
        type: 'gradient'
    }
}).render();

// Gráfico de Distribución
new ApexCharts(document.querySelector("#distributionChart"), {
    chart: {
        type: 'donut',
        height: 350
    },
    series: [
        <?php echo $period_stats['completed_visits']; ?>,
        <?php echo $period_stats['in_route_visits']; ?>,
        <?php echo $period_stats['pending_visits']; ?>
    ],
    labels: ['Completadas', 'En Ruta', 'Pendientes'],
    colors: ['#10B981', '#F59E0B', '#EF4444']
}).render();

function changeRange(range) {
   window.location.href = `?range=${range}`;
}

function markAsCompleted(visitId) {
   if (!confirm('¿Marcar esta visita como completada?')) return;

   fetch('actions/complete_visit.php', {
       method: 'POST',
       headers: {
           'Content-Type': 'application/json'
       },
       body: JSON.stringify({ visit_id: visitId })
   })
   .then(response => response.json())
   .then(data => {
       if (data.success) {
           window.location.reload();
       } else {
           alert(data.error || 'Error al actualizar la visita');
       }
   });
}

// Actualizar cada 5 minutos
setInterval(() => {
   window.location.reload();
}, 5 * 60 * 1000);
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
   const map = L.map('visitsMap').setView([18.4861, -69.9312], 13);
   L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
       attribution: '© OpenStreetMap contributors'
   }).addTo(map);

   const visits = <?php echo json_encode($map_visits); ?>;
   const markers = [];

   if (visits && visits.length > 0) {
       visits.forEach(visit => {
           if (visit.location_lat && visit.location_lng) {
               const marker = L.marker([visit.location_lat, visit.location_lng])
                   .bindPopup(`
                       <div class="p-2">
                           <div class="font-medium">${visit.client_name}</div>
                           <div class="text-sm">${visit.technician_name}</div>
                           <div class="text-sm mt-1">${visit.formatted_date} ${visit.formatted_time}</div>
                           <div class="text-xs mt-1 font-medium ${
                               visit.status === 'completed' ? 'text-green-600' : 
                               visit.status === 'in_route' ? 'text-yellow-600' : 'text-blue-600'
                           }">
                               ${visit.status.toUpperCase()}
                           </div>
                       </div>
                   `)
                   .addTo(map);
               markers.push(marker);
           }
       });

       if (markers.length > 0) {
           const group = L.featureGroup(markers);
           map.fitBounds(group.getBounds());
       }
   } else {
       document.getElementById('visitsMap').innerHTML = `
           <div class="flex items-center justify-center h-full bg-gray-50 text-gray-500">
               No hay visitas con ubicación para mostrar
           </div>`;
   }
});
</script>

<?php
$content = ob_get_clean();
require_once '../includes/layout.php';
?>

</body>
</html>