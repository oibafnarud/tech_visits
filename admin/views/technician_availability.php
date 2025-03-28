<?php
class TechnicianAvailability {
    private $db;
    private $technicianId;
    private $startDate;
    private $endDate;

    public function __construct($db, $technicianId, $startDate, $endDate) {
        $this->db = $db;
        $this->technicianId = $technicianId;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function getFormattedSchedule() {
        $schedule = $this->getSchedule();
        $formatted = [];
        
        // Formatear días
        $currentDate = new DateTime($this->startDate);
        $endDate = new DateTime($this->endDate);
        
        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format('Y-m-d');
            $dayOfWeek = $currentDate->format('w'); // 0 (domingo) a 6 (sábado)
            
            $formatted[$dateStr] = [
                'date' => $dateStr,
                'dayOfWeek' => $dayOfWeek,
                'regularSchedule' => array_filter($schedule['regular'], function($s) use ($dayOfWeek) {
                    return $s['day_of_week'] == $dayOfWeek + 1;
                }),
                'exceptions' => array_filter($schedule['exceptions'], function($e) use ($dateStr) {
                    return $e['exception_date'] == $dateStr;
                }),
                'visits' => array_filter($schedule['visits'], function($v) use ($dateStr) {
                    return $v['visit_date'] == $dateStr;
                })
            ];
            
            $currentDate->modify('+1 day');
        }
        
        return $formatted;
    }

    private function getRegularSchedule() {
        $stmt = $this->db->prepare("
            SELECT * FROM technician_availability 
            WHERE technician_id = ?
            ORDER BY day_of_week, start_time
        ");
        $stmt->execute([$this->technicianId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getExceptions() {
        $stmt = $this->db->prepare("
            SELECT * FROM availability_exceptions 
            WHERE technician_id = ?
            AND exception_date BETWEEN ? AND ?
            ORDER BY exception_date
        ");
        $stmt->execute([$this->technicianId, $this->startDate, $this->endDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getVisits() {
        $stmt = $this->db->prepare("
            SELECT * FROM visits 
            WHERE technician_id = ?
            AND visit_date BETWEEN ? AND ?
            ORDER BY visit_date, visit_time
        ");
        $stmt->execute([$this->technicianId, $this->startDate, $this->endDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Crear el modal para mostrar la disponibilidad
?>
<div id="availabilityModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="fixed inset-4 bg-white rounded-lg shadow-xl overflow-hidden flex flex-col">
        <!-- Cabecera -->
        <div class="p-4 border-b flex justify-between items-center">
            <div>
                <h2 class="text-xl font-bold">Disponibilidad del Técnico</h2>
                <p class="text-gray-600" id="technicianName"></p>
            </div>
            <div class="flex items-center space-x-4">
                <!-- Selector de vista -->
                <select id="viewType" class="border rounded p-2" onchange="changeViewType(this.value)">
                    <option value="week">Vista Semanal</option>
                    <option value="month">Vista Mensual</option>
                </select>
                <!-- Navegación de fechas -->
                <div class="flex items-center space-x-2">
                    <button onclick="navigateDate('prev')" class="p-2 hover:bg-gray-100 rounded">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <span id="currentDateRange" class="font-medium"></span>
                    <button onclick="navigateDate('next')" class="p-2 hover:bg-gray-100 rounded">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
                <button onclick="hideAvailabilityModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <!-- Contenido del calendario -->
        <div class="flex-1 overflow-auto p-4">
            <div id="availabilityContent" class="min-w-full"></div>
        </div>

        <!-- Leyenda -->
        <div class="p-4 border-t bg-gray-50">
            <div class="flex items-center justify-center space-x-4 text-sm">
                <div class="flex items-center">
                    <span class="w-3 h-3 bg-blue-100 rounded-full mr-2"></span>
                    Horario Regular
                </div>
                <div class="flex items-center">
                    <span class="w-3 h-3 bg-green-100 rounded-full mr-2"></span>
                    Visita Programada
                </div>
                <div class="flex items-center">
                    <span class="w-3 h-3 bg-red-100 rounded-full mr-2"></span>
                    No Disponible
                </div>
            </div>
        </div>
    </div>
</div>

<script>

function showAvailability(technicianId) {
    // Mostrar modal y cargar datos
    document.getElementById('availabilityModal').classList.remove('hidden');
    loadAvailabilityData(technicianId);
}

function hideAvailabilityModal() {
    document.getElementById('availabilityModal').classList.add('hidden');
}

function changeViewType(type) {
    currentView = type;
    renderAvailability();
}

function navigateDate(direction) {
    if (currentView === 'week') {
        currentDate.setDate(currentDate.getDate() + (direction === 'prev' ? -7 : 7));
    } else {
        currentDate.setMonth(currentDate.getMonth() + (direction === 'prev' ? -1 : 1));
    }
    loadAvailabilityData(currentTechData.id);
}

async function loadAvailabilityData(technicianId) {
    try {
        const start = getStartDate();
        const end = getEndDate();
        
        const response = await fetch(`actions/get_availability.php?technician_id=${technicianId}&start=${start}&end=${end}`);
        const data = await response.json();
        
        if (data.success) {
            currentTechData = data.data;
            renderAvailability();
        } else {
            showNotification(data.error || 'Error al cargar la disponibilidad', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error al cargar la disponibilidad', 'error');
    }
}

function renderAvailability() {
    const container = document.getElementById('availabilityContent');
    container.innerHTML = '';

    // Actualizar rango de fechas mostrado
    updateDateRange();

    if (currentView === 'week') {
        renderWeekView(container);
    } else {
        renderMonthView(container);
    }
}

function renderWeekView(container) {
    const days = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
    
    // Grid principal
    const grid = document.createElement('div');
    grid.className = 'grid grid-cols-8 gap-2 min-w-[1024px]'; // Añadido min-width

    // Columna de horas (ajustada)
    const hoursColumn = document.createElement('div');
    hoursColumn.className = 'space-y-2';
    hoursColumn.innerHTML = '<div class="h-12 sticky top-0 bg-white"></div>';
    
    for (let hour = 8; hour <= 18; hour++) {
        hoursColumn.innerHTML += `
            <div class="h-24 text-sm text-gray-500 pt-2 text-right pr-2 sticky left-0 bg-white">
                ${hour.toString().padStart(2, '0')}:00
            </div>
        `;
    }
    grid.appendChild(hoursColumn);

    // Generar columnas de días
    const startDate = new Date(currentTechData.range.start);
    for (let i = 0; i < 7; i++) {
        const dayColumn = document.createElement('div');
        dayColumn.className = 'space-y-2';

        const currentDate = new Date(startDate);
        currentDate.setDate(startDate.getDate() + i);
        const dateStr = currentDate.toISOString().split('T')[0];
        const isToday = currentDate.toDateString() === new Date().toDateString();

        // Header del día
        dayColumn.innerHTML = `
            <div class="h-12 text-center ${isToday ? 'bg-blue-50 rounded' : ''} sticky top-0 bg-white">
                <div class="font-medium">${days[currentDate.getDay()]}</div>
                <div class="text-sm text-gray-500">${currentDate.getDate()}</div>
            </div>
        `;

        // Slots de hora
        for (let hour = 8; hour <= 18; hour++) {
            const timeSlot = document.createElement('div');
            timeSlot.className = 'h-24 border rounded p-2 relative group hover:bg-gray-50 transition-colors';

            // Verificar disponibilidad regular
            const isRegularlyAvailable = currentTechData.regular.some(schedule => 
                schedule.day_of_week === ((currentDate.getDay() + 1) % 7 || 7) &&
                parseInt(schedule.start_time) <= hour &&
                parseInt(schedule.end_time) > hour
            );

            if (isRegularlyAvailable) {
                timeSlot.classList.add('bg-blue-50');
            }

            // Visitas programadas
            const visitsInSlot = currentTechData.visits.filter(visit => 
                visit.visit_date === dateStr &&
                parseInt(visit.visit_time) === hour
            );

            if (visitsInSlot.length > 0) {
                visitsInSlot.forEach(visit => {
                    timeSlot.innerHTML += `
                        <div class="text-xs bg-green-100 text-green-800 p-2 rounded mb-1 shadow-sm hover:shadow">
                            <div class="font-medium">${visit.visit_time.substr(0, 5)}</div>
                            <div class="truncate">${visit.client_name}</div>
                        </div>
                    `;
                });
            }

            // Excepciones
            const exceptionForDay = currentTechData.exceptions.find(exc => 
                exc.exception_date === dateStr
            );

            if (exceptionForDay) {
                timeSlot.classList.add('bg-red-50');
                timeSlot.innerHTML += `
                    <div class="text-xs text-red-600 font-medium">
                        ${exceptionForDay.reason || 'No disponible'}
                    </div>
                `;
            }

            dayColumn.appendChild(timeSlot);
        }

        grid.appendChild(dayColumn);
    }

    container.innerHTML = '';
    container.appendChild(grid);
}

function renderMonthView(container) {
    const days = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
    const firstDay = new Date(currentTechData.range.start);
    const lastDay = new Date(currentTechData.range.end);
    
    const grid = document.createElement('div');
    grid.className = 'grid grid-cols-7 gap-2';

    // Headers de días
    days.forEach(day => {
        const header = document.createElement('div');
        header.className = 'text-center font-medium p-2';
        header.textContent = day;
        grid.appendChild(header);
    });

    // Calcular el primer día del mes
    const firstDayOfMonth = new Date(firstDay.getFullYear(), firstDay.getMonth(), 1);
    const startingDay = firstDayOfMonth.getDay();

    // Días vacíos antes del primer día del mes
    for (let i = 0; i < startingDay; i++) {
        const emptyDay = document.createElement('div');
        emptyDay.className = 'p-2 h-32';
        grid.appendChild(emptyDay);
    }

    // Días del mes
    for (let date = new Date(firstDay); date <= lastDay; date.setDate(date.getDate() + 1)) {
        const dayCell = document.createElement('div');
        dayCell.className = 'border rounded p-2 h-32 overflow-y-auto';

        const dateStr = date.toISOString().split('T')[0];
        const isToday = date.toDateString() === new Date().toDateString();

        // Contenido del día
        dayCell.innerHTML = `
            <div class="text-right ${isToday ? 'font-bold text-blue-600' : ''}">${date.getDate()}</div>
            <div class="space-y-1 mt-1">
                ${currentTechData.visits
                    .filter(v => v.visit_date === dateStr)
                    .map(visit => `
                        <div class="text-xs bg-green-100 text-green-800 p-1 rounded truncate">
                            ${visit.visit_time.substr(0, 5)} - ${visit.client_name}
                        </div>
                    `).join('')}
            </div>
        `;

        grid.appendChild(dayCell);
    }

    container.innerHTML = '';
    container.appendChild(grid);
}
function checkAvailability(dayIndex, hour) {
    // Verificar horario regular
    const regularSchedule = currentTechData.regular.find(schedule => 
        schedule.day_of_week === dayIndex + 1 &&
        isHourInRange(hour, schedule.start_time, schedule.end_time)
    );

    if (regularSchedule) {
        return {
            class: 'bg-blue-100',
            content: 'Disponible'
        };
    }

    // Verificar excepciones
    // Verificar visitas programadas
    // etc.

    return null;
}

function updateDateRange() {
    const element = document.getElementById('currentDateRange');
    if (currentView === 'week') {
        const start = getStartDate();
        const end = getEndDate();
        element.textContent = `${formatDate(start)} - ${formatDate(end)}`;
    } else {
        element.textContent = currentDate.toLocaleDateString('es-ES', { 
            month: 'long', 
            year: 'numeric' 
        });
    }
}

// Funciones auxiliares
function getStartDate() {
    if (currentView === 'week') {
        const start = new Date(currentDate);
        start.setDate(start.getDate() - start.getDay());
        return start.toISOString().split('T')[0];
    } else {
        return new Date(currentDate.getFullYear(), currentDate.getMonth(), 1)
            .toISOString().split('T')[0];
    }
}

function getEndDate() {
    if (currentView === 'week') {
        const end = new Date(currentDate);
        end.setDate(end.getDate() + (6 - end.getDay()));
        return end.toISOString().split('T')[0];
    } else {
        return new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0)
            .toISOString().split('T')[0];
    }
}

function formatDate(dateStr) {
    return new Date(dateStr).toLocaleDateString('es-ES', {
        day: '2-digit',
        month: '2-digit'
    });
}

function isHourInRange(hour, startTime, endTime) {
    const start = parseInt(startTime.split(':')[0]);
    const end = parseInt(endTime.split(':')[0]);
    return hour >= start && hour < end;
}
</script>