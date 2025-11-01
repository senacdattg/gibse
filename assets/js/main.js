document.addEventListener('DOMContentLoaded', function() {
    inicializarAcordeon();
    inicializarSmoothScroll();
    inicializarAnimaciones();
});

function inicializarAcordeon() {
    const accordionItems = document.querySelectorAll('[data-accordion-item]');
    
    accordionItems.forEach(item => {
        const header = item.querySelector('.accordion-header');
        const body = item.querySelector('.accordion-body');
        const icon = item.querySelector('.accordion-icon');
        
        if (header && body) {
            header.addEventListener('click', function() {
                const isActive = item.classList.contains('active');
                
                accordionItems.forEach(otherItem => {
                    if (otherItem !== item) {
                        otherItem.classList.remove('active');
                        const otherBody = otherItem.querySelector('.accordion-body');
                        const otherIcon = otherItem.querySelector('.accordion-icon');
                        if (otherBody) otherBody.style.display = 'none';
                        if (otherIcon) otherIcon.style.transform = 'rotate(0deg)';
                    }
                });
                
                if (isActive) {
                    item.classList.remove('active');
                    body.style.display = 'none';
                    if (icon) icon.style.transform = 'rotate(0deg)';
                } else {
                    item.classList.add('active');
                    body.style.display = 'block';
                    if (icon) icon.style.transform = 'rotate(180deg)';
                }
            });
        }
    });
}

function inicializarSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                const offsetTop = targetElement.offsetTop - 80;
                window.scrollTo({
                    top: offsetTop,
                    behavior: 'smooth'
                });
            }
        });
    });
}

function inicializarAnimaciones() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -100px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    const elementosAnimables = document.querySelectorAll('.card, .accordion-item, .contact-item');
    elementosAnimables.forEach(el => observer.observe(el));
}

