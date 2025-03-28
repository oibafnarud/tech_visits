<?php
// Obtener visitas con coordenadas
$stmt = $db->prepare("
    SELECT 
        v.*,
        u.full_name as technician_name,
        SUBSTRING_INDEX(SUBSTRING_INDEX(v.location_url, '@', -1), ',', 2) as coordinates
    FROM visits v
    LEFT JOIN users u ON v.technician_id = u.id
    WHERE v.visit_date = :date
    AND v.location_url IS NOT NULL
    " . ($technician !== 'all' ? "AND v.technician_id = :technician_id" : "") . "
    " . ($status !== 'all' ? "AND v.status = :status" : "") . "
    ORDER BY v.visit_time ASC
");

$params = [':date' => $date];
if ($technician !== 'all') $params[':technician_id'] = $technician;
if ($status !== 'all') $params[':status'] = $status;

$stmt->execute($params);
$visits = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <!-- Lista de visitas -->
    <div class="md:col-span-1 space-y-4">
        <div class="bg-white rounded-lg shadow p-4">
            <h3 class="font-medium mb-4">Visitas del día</h3>
            <?php if (empty($visits)): ?>
                <p class="text-gray-500 text-center py-4">No hay visitas con ubicación</p>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($visits as $visit): ?>
                        <div class="p-3 rounded-lg hover:bg-gray-50 cursor-pointer visit-card"
                             data-visit-id="<?php echo $visit['id']; ?>"
                             onclick="highlightMarker(<?php echo $visit['id']; ?>)">
                            <div class="flex justify-between">
                                <div class="font-medium">
                                    <?php echo date('H:i', strtotime($visit['visit_time'])); ?>
                                </div>
                                <span class="text-sm px-2 py-1 rounded-full
                                    <?php echo $visit['status'] === 'completed' ? 'bg-green-100 text-green-800' : 
                                        ($visit['status'] === 'in_route' ? 'bg-yellow-100 text-yellow-800' : 
                                         'bg-blue-100 text-blue-800'); ?>">
                                    <?php echo ucfirst($visit['status']); ?>
                                </span>
                            </div>
                            <div class="text-sm text-gray-600">
                                <?php echo htmlspecialchars($visit['client_name']); ?>
                            </div>
                            <div class="text-xs text-gray-500">
                                <?php echo htmlspecialchars($visit['technician_name']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Mapa -->
    <div class="md:col-span-2">
        <div class="bg-white rounded-lg shadow">
            <div id="map" class="h-[600px] rounded-lg"></div>
        </div>
    </div>
</div>

<!-- Incluir Leaflet CSS y JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>

<script>
let map;
let markers = {};
let currentHighlight = null;

// Inicializar mapa
function initMap() {
    map = L.map('map');
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    // Añadir marcadores
    <?php foreach ($visits as $visit): ?>
        <?php
        if (preg_match('/@(-?\d+\.\d+),(-?\d+\.\d+)/', $visit['location_url'], $matches)) {
            $lat = $matches[1];
            $lng = $matches[2];
        ?>
            let marker = L.marker([<?php echo $lat; ?>, <?php echo $lng; ?>])
                .bindPopup(`
                    <div class="p-2">
                        <div class="font-medium"><?php echo htmlspecialchars($visit['client_name']); ?></div>
                        <div class="text-sm"><?php echo date('H:i', strtotime($visit['visit_time'])); ?></div>
                        <div class="text-sm text-gray-600"><?php echo htmlspecialchars($visit['technician_name']); ?></div>
                        <div class="mt-2">
                            <a href="<?php echo htmlspecialchars($visit['location_url']); ?>" 
                               target="_blank"
                               class="text-blue-600 hover:text-blue-800 text-sm">
                                Ver en Google Maps
                            </a>
                        </div>
                    </div>
                `);
            markers[<?php echo $visit['id']; ?>] = marker;
            marker.addTo(map);
        <?php
        }
        ?>
    <?php endforeach; ?>

    // Ajustar vista a todos los marcadores
    if (Object.keys(markers).length > 0) {
        let group = new L.featureGroup(Object.values(markers));
        map.fitBounds(group.getBounds().pad(0.1));
    } else {
        // Centrar en una ubicación por defecto
        map.setView([18.4861, -69.9312], 13); // Santo Domingo
    }
}

function highlightMarker(visitId) {
    // Restaurar marcador anterior
    if (currentHighlight) {
        markers[currentHighlight].setZIndexOffset(0);
    }

    // Resaltar nuevo marcador
    if (markers[visitId]) {
        markers[visitId].setZIndexOffset(1000);
        markers[visitId].openPopup();
        map.panTo(markers[visitId].getLatLng());
        currentHighlight = visitId;
    }

    // Resaltar card en la lista
    document.querySelectorAll('.visit-card').forEach(card => {
        card.classList.remove('bg-blue-50');
    });
    document.querySelector(`.visit-card[data-visit-id="${visitId}"]`)?.classList.add('bg-blue-50');
}

// Inicializar mapa cuando se carga la página
document.addEventListener('DOMContentLoaded', initMap);
</script>