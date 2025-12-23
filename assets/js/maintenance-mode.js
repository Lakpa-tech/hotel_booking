/**
 * Maintenance Mode User Interface Components
 * Provides persistent maintenance mode notifications and restrictions
 */

class MaintenanceMode {
    constructor() {
        this.isMaintenanceMode = document.getElementById('maintenanceBanner') !== null;
        this.init();
    }

    init() {
        if (!this.isMaintenanceMode) return;

        this.addMaintenanceStyles();
        this.addBookingRestrictions();
        this.addPeriodicReminders();
        this.addMaintenanceTooltips();
    }

    addMaintenanceStyles() {
        // Add visual indicators to booking-related elements
        const bookingButtons = document.querySelectorAll('button[type="submit"], .btn-primary');
        bookingButtons.forEach(button => {
            if (button.textContent.includes('Book') || button.textContent.includes('Reserve')) {
                button.classList.add('maintenance-restricted');
                button.style.position = 'relative';
                button.style.overflow = 'hidden';
                
                // Add maintenance overlay
                const overlay = document.createElement('div');
                overlay.className = 'maintenance-overlay';
                overlay.style.cssText = `
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: repeating-linear-gradient(
                        45deg,
                        rgba(220, 38, 38, 0.1),
                        rgba(220, 38, 38, 0.1) 10px,
                        rgba(255, 255, 255, 0.1) 10px,
                        rgba(255, 255, 255, 0.1) 20px
                    );
                    pointer-events: none;
                    animation: maintenanceStripes 2s linear infinite;
                `;
                button.appendChild(overlay);
            }
        });

        // Add CSS for maintenance stripes animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes maintenanceStripes {
                0% { background-position: 0 0; }
                100% { background-position: 20px 0; }
            }
            
            .maintenance-restricted:hover {
                transform: none !important;
                box-shadow: 0 0 0 2px rgba(220, 38, 38, 0.3) !important;
            }
        `;
        document.head.appendChild(style);
    }

    addBookingRestrictions() {
        // Add warning messages to booking forms
        const bookingForms = document.querySelectorAll('form[action*="checkout"], form[action*="booking"]');
        bookingForms.forEach(form => {
            const warning = document.createElement('div');
            warning.className = 'alert alert-warning maintenance-warning';
            warning.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <div>
                        <strong>Maintenance Mode Active</strong><br>
                        <small>Bookings are temporarily restricted during maintenance. Please try again later.</small>
                    </div>
                </div>
            `;
            warning.style.cssText = `
                border-left: 4px solid #dc2626;
                background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
                margin-bottom: 1rem;
            `;
            
            form.insertBefore(warning, form.firstChild);
        });
    }

    addPeriodicReminders() {
        let reminderCount = 0;
        
        // Show floating reminder every 3 minutes
        setInterval(() => {
            reminderCount++;
            this.showFloatingReminder(reminderCount);
        }, 180000); // 3 minutes
    }

    showFloatingReminder(count) {
        const reminder = document.createElement('div');
        reminder.className = 'maintenance-floating-reminder';
        reminder.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="fas fa-tools me-2"></i>
                <div>
                    <strong>Maintenance Mode</strong><br>
                    <small>Still under maintenance (${count} reminder${count > 1 ? 's' : ''})</small>
                </div>
                <button type="button" class="btn-close ms-3" onclick="this.parentElement.parentElement.remove()"></button>
            </div>
        `;
        
        reminder.style.cssText = `
            position: fixed;
            top: 100px;
            right: 20px;
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            color: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            z-index: 1050;
            max-width: 300px;
            animation: slideInRight 0.5s ease-out;
        `;

        document.body.appendChild(reminder);

        // Auto-remove after 10 seconds
        setTimeout(() => {
            if (reminder.parentNode) {
                reminder.style.animation = 'slideOutRight 0.5s ease-in';
                setTimeout(() => reminder.remove(), 500);
            }
        }, 10000);
    }

    addMaintenanceTooltips() {
        // Add tooltips to disabled elements
        const disabledElements = document.querySelectorAll('.maintenance-restricted');
        disabledElements.forEach(element => {
            element.setAttribute('title', 'Temporarily unavailable due to maintenance mode');
            element.setAttribute('data-bs-toggle', 'tooltip');
            element.setAttribute('data-bs-placement', 'top');
        });

        // Initialize Bootstrap tooltips if available
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }
    }
}

// Add slide animations CSS
const animationStyles = document.createElement('style');
animationStyles.textContent = `
    @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes slideOutRight {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
`;
document.head.appendChild(animationStyles);

// Initialize maintenance mode when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    new MaintenanceMode();
});

// Export for manual initialization if needed
window.MaintenanceMode = MaintenanceMode;