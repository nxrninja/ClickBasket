/**
 * Admin Theme Management
 * Handles dark/light mode persistence across admin pages
 */

// Theme management functions
function initializeTheme() {
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
    updateThemeIcon(savedTheme);
    
    // Force body background update for better visual consistency
    document.body.style.backgroundColor = savedTheme === 'dark' ? '#1e293b' : '#f8fafc';
    document.body.style.color = savedTheme === 'dark' ? '#f8fafc' : '#1e293b';
}

function toggleTheme() {
    const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    
    console.log('Theme toggle:', currentTheme, '->', newTheme);
    
    document.documentElement.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
    updateThemeIcon(newTheme);
    
    // Force body background update
    document.body.style.backgroundColor = newTheme === 'dark' ? '#1e293b' : '#f8fafc';
    document.body.style.color = newTheme === 'dark' ? '#f8fafc' : '#1e293b';
    
    // Show notification if available
    if (typeof showNotification === 'function') {
        showNotification(`Switched to ${newTheme} mode`, 'info');
    }
}

function updateThemeIcon(theme) {
    // Update all theme toggle icons
    const themeIcons = document.querySelectorAll('.theme-toggle i, [onclick="toggleTheme()"] i');
    themeIcons.forEach(icon => {
        icon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
    });
}

// Initialize theme when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeTheme();
    
    // Add keyboard shortcut for theme toggle (Alt + T)
    document.addEventListener('keydown', function(e) {
        if (e.altKey && e.key === 't') {
            e.preventDefault();
            toggleTheme();
        }
    });
});

// Re-initialize theme when page becomes visible (handles browser back/forward)
document.addEventListener('visibilitychange', function() {
    if (!document.hidden) {
        initializeTheme();
    }
});

// Export functions for global use
window.toggleTheme = toggleTheme;
window.initializeTheme = initializeTheme;
window.updateThemeIcon = updateThemeIcon;
