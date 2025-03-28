<div class="space-y-2 max-h-[300px] overflow-y-auto">
   <?php if (empty($technicians_in_route)): ?>
       <p class="text-center text-gray-500 py-4">No hay técnicos en ruta</p>
   <?php else: foreach ($technicians_in_route as $tech): ?>
       <div class="flex items-center justify-between p-2 bg-yellow-50 rounded-lg">
           <div class="flex items-center space-x-2">
               <span class="w-2 h-2 bg-yellow-400 rounded-full"></span>
               <div>
                   <div class="font-medium"><?php echo htmlspecialchars($tech['full_name']); ?></div>
                   <div class="text-xs text-gray-600">
                       <?php echo htmlspecialchars($tech['current_client']); ?>
                   </div>
               </div>
           </div>
           <div class="flex space-x-2">
               <a href="tel:<?php echo $tech['phone']; ?>" 
                  class="text-green-600 hover:text-green-800">
                   <i class="fas fa-phone"></i>
               </a>
           </div>
       </div>
   <?php endforeach; endif; ?>
</div>