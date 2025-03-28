<div class="space-y-6">
   <!-- Visitas Urgentes -->
   <?php if (!empty($urgent_visits)): ?>
   <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
       <h3 class="text-red-700 font-medium mb-2">
           <i class="fas fa-exclamation-triangle mr-2"></i>
           Requieren Atención Inmediata
       </h3>
       <div class="space-y-3">
           <?php foreach ($urgent_visits as $visit): ?>
           <div class="p-3 bg-white rounded shadow-sm">
               <div class="flex justify-between items-center">
                   <div>
                       <span class="font-medium"><?php echo date('h:i A', strtotime($visit['visit_time'])); ?></span>
                       <span class="text-red-600 text-sm ml-2">
                           (en <?php echo abs($visit['minutes_until']); ?> min)
                       </span>
                   </div>
                   <a href="tel:<?php echo $visit['technician_phone']; ?>" 
                      class="text-blue-600 hover:text-blue-800">
                       <i class="fas fa-phone"></i>
                   </a>
               </div>
               <div class="mt-1">
                   <div class="font-medium"><?php echo htmlspecialchars($visit['client_name']); ?></div>
                   <div class="text-sm text-gray-600"><?php echo htmlspecialchars($visit['technician_name']); ?></div>
               </div>
           </div>
           <?php endforeach; ?>
       </div>
   </div>
   <?php endif; ?>

   <!-- Próximas Visitas -->
   <div class="space-y-3">
       <h3 class="font-medium">Próximas Visitas</h3>
       <?php if (!empty($upcoming_visits)): foreach ($upcoming_visits as $visit): ?>
           <div class="p-3 bg-gray-50 rounded-lg">
               <div class="flex justify-between items-center">
                   <div>
                       <span class="font-medium"><?php echo date('h:i A', strtotime($visit['visit_time'])); ?></span>
                       <span class="text-blue-600 text-sm ml-2">
                           (en <?php echo floor($visit['minutes_until']/60); ?>h <?php echo $visit['minutes_until']%60; ?>m)
                       </span>
                   </div>
                   <div class="text-sm text-gray-600"><?php echo htmlspecialchars($visit['technician_name']); ?></div>
               </div>
               <div class="mt-1">
                   <div><?php echo htmlspecialchars($visit['client_name']); ?></div>
                   <div class="text-sm text-gray-500 truncate"><?php echo htmlspecialchars($visit['address']); ?></div>
               </div>
           </div>
       <?php endforeach; else: ?>
           <p class="text-center text-gray-500 py-4">No hay visitas pendientes</p>
       <?php endif; ?>
   </div>
</div>