
<nav class="fixed bottom-0 left-0 right-0 bg-white border-t">
    <div class="grid grid-cols-5 h-16">
        <a href="?view=today" 
           class="bottom-nav-item flex flex-col items-center justify-center <?php echo $view === 'today' ? 'active' : ''; ?>">
            <i class="fas fa-calendar-day text-xl mb-1"></i>
            <span class="text-xs">Hoy</span>
        </a>
        <a href="?view=week" 
           class="bottom-nav-item flex flex-col items-center justify-center <?php echo $view === 'week' ? 'active' : ''; ?>">
            <i class="fas fa-calendar-week text-xl mb-1"></i>
            <span class="text-xs">Semana</span>
        </a>
        <a href="?view=month" 
           class="bottom-nav-item flex flex-col items-center justify-center <?php echo $view === 'month' ? 'active' : ''; ?>">
            <i class="fas fa-calendar-alt text-xl mb-1"></i>
            <span class="text-xs">Mes</span>
        </a>
        <button onclick="showBlockTimeModal()" 
                class="bottom-nav-item flex flex-col items-center justify-center">
            <i class="fas fa-ban text-xl mb-1"></i>
            <span class="text-xs">Bloquear</span>
        </button>
        <button onclick="toggleMoreMenu()" 
                class="bottom-nav-item flex flex-col items-center justify-center">
            <i class="fas fa-bars text-xl mb-1"></i>
            <span class="text-xs">Más</span>
        </button>
    </div>
</nav>