// LifeSync – Main JS

// Auto-hide alerts after 4 seconds
document.addEventListener('DOMContentLoaded', () => {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 4000);
    });

    // Animate progress bars on load
    document.querySelectorAll('.bar-fill, .habit-progress-fill').forEach(bar => {
        const targetWidth = bar.style.width;
        bar.style.width = '0%';
        setTimeout(() => bar.style.width = targetWidth, 100);
    });
});
