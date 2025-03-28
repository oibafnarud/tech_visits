<!-- views/week_view.php -->
<div class="relative grid grid-cols-8 gap-4 h-[800px] overflow-y-auto">
   <!-- Columna de horas -->
   <div class="sticky top-0 z-10 bg-white border-r">
       <div class="h-16"></div>
       <?php for($hour = 8; $hour <= 20; $hour++): ?>
           <div class="h-24 flex items-start pt-2 text-sm text-gray-500">
               <?php echo sprintf('%02d:00', $hour); ?>
           </div>
       <?php endfor; ?>
   </div>

   <!-- Días de la semana -->
   <?php
   $weekStart = date('Y-m-d', strtotime('monday this week', strtotime($date)));
   for($i = 0; $i < 7; $i++):
       $currentDate = date('Y-m-d', strtotime($weekStart . " +$i days"));
       $isToday = $currentDate === date('Y-m-d');
   ?>
       <div class="flex-1">
           <!-- Cabecera del día -->
           <div class="sticky top-0 z-10 bg-white h-16 text-center border-b <?php echo $isToday ? 'bg-blue-50' : ''; ?>">
               <div class="font-medium"><?php echo strftime('%A', strtotime($currentDate)); ?></div>
               <div class="<?php echo $isToday ? 'text-blue-600 font-bold' : 'text-gray-500'; ?>">
                   <?php echo date('d M', strtotime($currentDate)); ?>
               </div>
           </div>

           <!-- Celdas de horas -->
           <?php for($hour = 8; $hour <= 20; $hour++): ?>
               <div class="h-24 border-t relative group hover:bg-gray-50">
                   <?php
                   if (isset($visitsByDay[$currentDate])) {
                       foreach($visitsByDay[$currentDate] as $visit) {
                           $visitHour = (int)date('G', strtotime($visit['visit_time']));
                           $visitMinutes = (int)date('i', strtotime($visit['visit_time']));
                           
                           if ($visitHour === $hour) {
                               $duration = $visit['duration'] ?? 60;
                               $top = ($visitMinutes / 60) * 96;
                               $height = ($duration / 60) * 96;
                               
                               $bgColor = match($visit['status']) {
                                   'completed' => 'bg-green-100',
                                   'in_route' => 'bg-yellow-100',
                                   default => 'bg-blue-100'
                               };
                               ?>
                               <div class="absolute w-full px-1" style="top: <?php echo $top; ?>px; height: <?php echo $height; ?>px;">
                                   <div class="h-full p-2 rounded <?php echo $bgColor; ?> shadow hover:shadow-md cursor-pointer"
                                        onclick="showVisitActions(<?php echo $visit['id']; ?>, event)">
                                       <div class="font-medium text-sm"><?php echo date('H:i', strtotime($visit['visit_time'])); ?></div>
                                       <div class="font-medium truncate"><?php echo htmlspecialchars($visit['client_name']); ?></div>
                                       <div class="text-xs text-gray-600"><?php echo htmlspecialchars($visit['technician_name']); ?></div>
                                   </div>
                               </div>
                               <?php
                           }
                       }
                   }
                   ?>
               </div>
           <?php endfor; ?>
       </div>
   <?php endfor; ?>
</div>

<!-- Menú contextual -->
<div id="contextMenu" class="fixed hidden z-50 bg-white rounded shadow-lg w-48">
   <div class="py-1">
       <a href="#" onclick="showVisitDetails(selectedVisitId)" class="px-4 py-2 hover:bg-gray-100 flex items-center">
           <i class="fas fa-eye w-5"></i>Ver detalles
       </a>
       <a href="#" onclick="editVisit(selectedVisitId)" class="px-4 py-2 hover:bg-gray-100 flex items-center">
           <i class="fas fa-edit w-5"></i>Editar
       </a>
       <a href="#" onclick="updateStatus(selectedVisitId, 'completed')" class="px-4 py-2 hover:bg-gray-100 flex items-center">
           <i class="fas fa-check w-5"></i>Completar
       </a>
   </div>
</div>

<script>
let selectedVisitId = null;

function showVisitActions(id, event) {
   event.preventDefault();
   selectedVisitId = id;
   const menu = document.getElementById('contextMenu');
   menu.style.display = 'block';
   menu.style.left = `${event.pageX}px`;
   menu.style.top = `${event.pageY}px`;
   
   document.addEventListener('click', closeContextMenu);
}

function closeContextMenu(e) {
   const menu = document.getElementById('contextMenu');
   if (!menu.contains(e.target)) {
       menu.style.display = 'none';
       document.removeEventListener('click', closeContextMenu);
   }
}
</script>