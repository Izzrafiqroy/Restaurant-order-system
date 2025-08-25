// Add smooth entrance animation
window.addEventListener('DOMContentLoaded', () => {
    const content = document.querySelector('.content');
    content.style.opacity = '0';
    content.style.transform = 'translateY(30px)';
    
    setTimeout(() => {
        content.style.transition = 'all 1s ease';
        content.style.opacity = '1';
        content.style.transform = 'translateY(0)';
    }, 100);

    // Animate order optionsff
    const orderOptions = document.querySelectorAll('.order-option');
    orderOptions.forEach((option, index) => {
        option.style.opacity = '0';
        option.style.transform = 'translateY(50px)';
        
        setTimeout(() => {
            option.style.transition = 'all 0.8s ease';
            option.style.opacity = '1';
            option.style.transform = 'translateY(0)';
        }, 500 + (index * 200));
    });

    // Animate features
    const features = document.querySelectorAll('.feature');
    features.forEach((feature, index) => {
        feature.style.opacity = '0';
        feature.style.transform = 'translateY(30px)';
        
        setTimeout(() => {
            feature.style.transition = 'all 0.6s ease';
            feature.style.opacity = '1';
            feature.style.transform = 'translateY(0)';
        }, 1000 + (index * 150));
    });
});

// Add click tracking for analytics (optional)
document.querySelectorAll('.order-option').forEach(option => {
    option.addEventListener('click', function() {
        const orderType = this.classList.contains('walk-in-option') ? 'walk-in' : 'online';
        console.log(`User selected ${orderType} ordering`);
        // You can add analytics tracking here
    });
});