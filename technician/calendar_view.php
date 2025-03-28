<?php
require_once '../includes/session_check.php';
require_once '../config/database.php';

$database = new Database();
$db = $database->connect();

$viewMode = isset($_GET['view']) ? $_GET['view'] : 'day';
$currentDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Obtener rango de fechas según el modo de vista
switch($viewMode) {
    case 'week':
        $startDate = date('Y-m-d', strtotime('monday this week', strtotime($currentDate)));
        $endDate = date('Y-m-d', strtotime('sunday this week', strtotime($currentDate)));
        break;
    case 'month':
        $startDate = date('Y-m-01', strtotime($currentDate));
        $endDate = date('Y-m-t', strtotime($currentDate));
        break;
    default:
        $startDate = $currentDate;
        $endDate = $currentDate;
}

// Obtener visitas para el rango de fechas
$stmt = $db->prepare("
    SELECT 
        v.*,
        COALESCE(NULLIF(v.location_lat, ''), '0') as lat,
        COALESCE(NULLIF(v.location_lng, ''), '0') as lng
    FROM visits v
    WHERE v.technician_id = :technician_id 
    AND v.visit_date BETWEEN :start_date AND :end_date
    ORDER BY v.visit_date, v.visit_time
");

$stmt->execute([
    ':technician_id' => $_SESSION['user_id'],
    ':start_date' => $startDate,
    ':end_date' => $endDate
]);

$visits = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Agrupar visitas por fecha y hora
$groupedVisits = [];
foreach ($visits as $visit) {
    $date = $visit['visit_date'];
    $hour = date('H', strtotime($visit['visit_time']));
    $groupedVisits[$date][$hour][] = $visit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendario de Visitas</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Leaflet CSS para el mapa -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <style>
        #map { height: 400px; }
        .time-slot:hover { background-color: rgba(59, 130, 246, 0.1); }
    </style>
</head>
<body class="bg-gray-100">
    <?php include '../includes/header.php'; ?>

    <div class="container mx-auto px-4 py-8 mt-16">
        <!-- Mapa de Visitas -->
        <div class="bg-white rounded-lg shadow-md p-4 mb-6">
            <h2 class="text-lg font-semibold mb-4">Mapa de Visitas</h2>
            <div id="map" class="rounded-lg"></div>
        </div>

        <!-- Calendario -->
        <div class="bg-white rounded-lg shadow-md p-4">
            <div class="grid grid-cols-1 md:grid-cols-7 gap-4">
                <?php
                if ($viewMode === 'week' || $viewMode === 'month') {
                    // Mostrar los días en el rango
                    $current = new DateTime($startDate);
                    $end = new DateTime($endDate);
                    
                    while ($current <= $end) {
                        $dateStr = $current->format('Y-m-d');
                        echo '<div class="day-column">';
                        echo '<h3 class="text-center font-semibold mb-2">' . $current->format('D j') . '</h3>';
                        
                        // Mostrar slots de tiempo (8am a 6pm)
                        for ($hour = 8; $hour <= 18; $hour++) {
                            $timeClass = 'time-slot p-2 border-t ';
                            $hasVisits = isset($groupedVisits[$dateStr][$hour]);
                            
                            if ($hasVisits) {
                                $timeClass .= 'bg-blue-50';
                            }
                            
                            echo "<div class='$timeClass'>";
                            echo "<div class='text-sm text-gray-500'>" . sprintf("%02d:00", $hour) . "</div>";
                            
                            if ($hasVisits) {
                                foreach ($groupedVisits[$dateStr][$hour] as $visit) {
                                    echo "<div class='mt-1 p-2 rounded bg-white shadow-sm'>";
                                    echo "<div class='font-semibold'>" . htmlspecialchars($visit['client_name']) . "</div>";
                                    echo "<div class='text-sm text-gray-600'>" . htmlspecialchars($visit['address']) . "</div>";
                                    echo "</div>";
                                }
                            }
                            
                            echo "</div>";
                        }
                        
                        echo '</div>';
                        $current->modify('+1 day');
                    }
                } else {
                    // Vista diaria
                    echo '<div class="col-span-full">';
                    echo '<h3 class="text-center font-semibold mb-4">' . date('l, F j', strtotime($currentDate)) . '</h3>';
                    
                    for ($hour = 8; $hour <= 18; $hour++) {
                        $timeClass = 'time-slot p-4 border-t flex ';
                        $hasVisits = isset($groupedVisits[$currentDate][$hour]);
                        
                        if ($hasVisits) {
                            $timeClass .= 'bg-blue-50';
                        }
                        
                        echo "<div class='$timeClass'>";
                        echo "<div class='w-24 text-gray-500'>" . sprintf("%02d:00", $hour) . "</div>";
                        echo "<div class='flex-1'>";
                        
                        if ($hasVisits) {
                            foreach ($groupedVisits[$currentDate][$hour] as $visit) {
                                echo "<div class='mb-2 p-3 rounded bg-white shadow-sm'>";
                                echo "<div class='font-semibold'>" . htmlspecialchars($visit['client_name']) . "</div>";
                                echo "<div class='text-sm text-gray-600'>" . htmlspecialchars($visit['address']) . "</div>";
                                echo "<div class='mt-2 flex space-x-2'>";
                                echo "<a href='tel:" . htmlspecialchars($visit['contact_phone']) . "' class='text-blue-600 hover:underline text-sm'>";
                                echo "<i class='fas fa-phone mr-1'></i>" . htmlspecialchars($visit['contact_phone']) . "</a>";
                                if ($visit['location_url']) {
                                    echo "<a href='" . htmlspecialchars($visit['location_url']) . "' target='_blank' class='text-blue-600 hover:underline text-sm'>";
                                    echo "<i class='fas fa-map-marker-alt mr-1'></i>Ver en mapa</a>";
                                }
                                echo "</div>";
                                echo "</div>";
                            }
                        }
                        
                        echo "</div>";
                        echo "</div>";
                    }
                    
                    echo '</div>';
                }
                ?>
            </div>
        </div>
    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script>
        // Inicializar mapa
        var map = L.map('map').setView([10.4806, -66.9036], 12); // Coordenadas de Caracas
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        // Agregar marcadores para cada visita
        <?php foreach ($visits as $visit): ?>
        <?php if ($visit['lat'] != '0' && $visit['lng'] != '0'): ?>
        L.marker([<?php echo $visit['lat']; ?>, <?php echo $visit['lng']; ?>])
         .bindPopup(`
            <strong><?php echo htmlspecialchars($visit['client_name']); ?></strong><br>
            <?php echo htmlspecialchars($visit['address']); ?><br>
            <small><?php echo date('H:i', strtotime($visit['visit_time'])); ?></small>
         `)
         .addTo(map);
        <?php endif; ?>
        <?php endforeach; ?>

        // Manejar cambios en el modo de vista
        document.getElementById('viewMode').addEventListener('change', function() {
            const date = document.getElementById('dateSelector').value;
            window.location.href = `?view=${this.value}&date=${date}`;
        });

        document.getElementById('dateSelector').addEventListener('change', function() {
            const view = document.getElementById('viewMode').value;
            window.location.href = `?view=${view}&date=${this.value}`;
        });

        function goToDate(type) {
            const view = document.getElementById('viewMode').value;
            const date = type === 'today' ? '<?php echo date('Y-m-d'); ?>' : document.getElementById('dateSelector').value;
            window.location.href = `?view=${view}&date=${date}`;
        }
    </script>
</body>
</html>