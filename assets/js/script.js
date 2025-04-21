document.addEventListener('DOMContentLoaded', function() {
    // Mobile navigation toggle
    const navToggle = document.getElementById('navToggle');
    const navList = document.getElementById('navList');
    
    if (navToggle && navList) {
        navToggle.addEventListener('click', function() {
            navList.classList.toggle('active');
        });
    }
    
    // Close mobile nav when clicking outside
    document.addEventListener('click', function(event) {
        if (navList && navList.classList.contains('active') && 
            !navList.contains(event.target) && 
            !navToggle.contains(event.target)) {
            navList.classList.remove('active');
        }
    });
});