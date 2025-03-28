// File: /admin/visits/js/visits.js


window.changeView = function(view) {
    const url = new URL(window.location);
    url.searchParams.set('view', view);
    window.location.href = url.toString();
}

window.changeDate = function(date) {
    const url = new URL(window.location);
    url.searchParams.set('date', date);
    window.location.href = url.toString();
}

window.goToDate = function(direction) {
    const currentDate = new Date(document.getElementById('dateSelect').value);
    let newDate;

    switch(direction) {
        case 'today':
            newDate = new Date();
            break;
        case 'prev':
            newDate = new Date(currentDate);
            newDate.setDate(currentDate.getDate() - 1);
            break;
        case 'next':
            newDate = new Date(currentDate);
            newDate.setDate(currentDate.getDate() + 1);
            break;
    }

    changeDate(newDate.toISOString().split('T')[0]);
}

window.applyFilters = function() {
    const status = document.getElementById('filterStatus').value;
    const techId = document.getElementById('filterTechnician').value;
    const url = new URL(window.location);

    if (status && status !== 'all') {
        url.searchParams.set('status', status);
    } else {
        url.searchParams.delete('status');
    }

    if (techId && techId !== 'all') {
        url.searchParams.set('technician', techId);
    } else {
        url.searchParams.delete('technician');
    }

    window.location.href = url.toString();
}

window.clearFilters = function() {
    const url = new URL(window.location);
    url.searchParams.delete('status');
    url.searchParams.delete('technician');
    window.location.href = url.toString();
}

window.showNewVisitModal = function() {
    const modal = document.getElementById('visitFormModal');
    if (modal) {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    } else {
        console.error('Modal not found');
    }
}

window.closeModal = function(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }
}

// Inicialización cuando el DOM está listo
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar Select2 si está disponible
    if (typeof $.fn.select2 !== 'undefined') {
        $('.select2').select2({
            width: '100%'
        });
    }

    // Inicializar búsqueda
    const searchInput = document.getElementById('searchClient');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(filterVisits, 300));
    }
});

// Funciones de utilidad
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function filterVisits() {
    const searchTerm = document.getElementById('searchClient').value.toLowerCase();
    document.querySelectorAll('.visit-item').forEach(visit => {
        const clientName = visit.querySelector('.client-name')?.textContent.toLowerCase() || '';
        const clientAddress = visit.querySelector('.client-address')?.textContent.toLowerCase() || '';
        const shouldShow = clientName.includes(searchTerm) || clientAddress.includes(searchTerm);
        visit.style.display = shouldShow ? '' : 'none';
    });
}

async function validateAvailability() {
    const form = document.getElementById('visitForm');
    const formData = new FormData(form);
    
    return fetch('../actions/check_visit_availability.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            technician_id: formData.get('technician_id'),
            visit_date: formData.get('visit_date'),
            visit_time: formData.get('visit_time'),
            duration: formData.get('duration'),
            visit_id: formData.get('visit_id')
        })
    })
    .then(response => response.json());
}

function setupRealTimeValidation() {
    const form = document.getElementById('visitForm');
    const fields = ['technician_id', 'visit_date', 'visit_time'];
    
    fields.forEach(field => {
        form.elements[field]?.addEventListener('change', async () => {
            // Solo validar si todos los campos necesarios están llenos
            if (fields.every(f => form.elements[f]?.value)) {
                try {
                    const check = await validateAvailability();
                    const feedback = document.getElementById('availabilityFeedback');
                    
                    if (check.success) {
                        feedback.className = 'text-sm text-green-600 mt-2';
                        feedback.innerHTML = '<i class="fas fa-check mr-1"></i>Horario disponible';
                    } else {
                        feedback.className = 'text-sm text-red-600 mt-2';
                        feedback.innerHTML = `<i class="fas fa-exclamation-triangle mr-1"></i>${check.error}`;
                    }
                } catch (error) {
                    console.error('Error en validación:', error);
                }
            }
        });
    });
}

async function saveVisit(event) {
    event.preventDefault();
    const form = event.target;
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    try {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Verificando disponibilidad...';

        // Verificar disponibilidad primero
        const availabilityCheck = await validateAvailability();
        if (!availabilityCheck.success) {
            showNotification(availabilityCheck.error, 'error');
            return;
        }

        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Guardando...';
        const formData = new FormData(form);

        const response = await fetch('actions/save_visit.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        if (data.success) {
            showNotification(
                formData.get('visit_id') ? 'Visita actualizada exitosamente' : 'Visita creada exitosamente', 
                'success'
            );
            closeModal('visitFormModal');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showNotification(data.error || 'Error al guardar la visita', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error al procesar la solicitud', 'error');
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    }
}

document.addEventListener('DOMContentLoaded', setupRealTimeValidation);


// Exportar todas las funciones al objeto window para que sean accesibles globalmente
window.filterVisits = filterVisits;