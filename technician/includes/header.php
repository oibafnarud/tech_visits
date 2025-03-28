// File: /tech_visits/technician/includes/header.php

<header class="fixed top-0 left-0 right-0 bg-white shadow z-50">
    <div class="flex justify-between items-center px-4 h-16">
        <div class="flex items-center">
            <img src="assets/images/logo.png" alt="Logo" class="h-8 w-auto mr-2">
            <h1 class="text-xl font-semibold">App Técnicos</h1>
        </div>
        <div class="flex items-center space-x-4">
            <!-- Botón de pendientes -->
            <button onclick="showPendingVisits()" 
                    class="header-icon-button">
                <i class="fas fa-clock text-xl"></i>
                <?php if ($pendingCount > 0): ?>
                    <span class="pending-badge">
                        <?php echo $pendingCount; ?>
                    </span>
                <?php endif; ?>
            </button>

            <!-- Botón de notificaciones -->
            <button onclick="toggleNotifications()" 
                    class="header-icon-button">
                <i class="fas fa-bell text-xl"></i>
                <span class="notification-badge hidden"></span>
            </button>
        </div>
    </div>
</header>