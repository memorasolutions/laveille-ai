/**
 * JavaScript - Animations et effets visuels
 * Fonctions préfixées "geq_" pour compatibilité iframe
 * 
 * @author MEMORA Solutions
 * @version 1.0.0
 * @date 1er août 2025
 */

/**
 * Animation de fondu à l'apparition
 */
function geq_fadeIn(element, duration = 300) {
    element.style.opacity = '0';
    element.style.display = 'block';
    
    let start = null;
    const animate = (timestamp) => {
        if (!start) start = timestamp;
        const progress = timestamp - start;
        const percentage = Math.min(progress / duration, 1);
        
        element.style.opacity = percentage;
        
        if (progress < duration) {
            window.requestAnimationFrame(animate);
        }
    };
    
    window.requestAnimationFrame(animate);
}

/**
 * Animation de fondu à la disparition
 */
function geq_fadeOut(element, duration = 300) {
    let start = null;
    const initialOpacity = parseFloat(window.getComputedStyle(element).opacity);
    
    const animate = (timestamp) => {
        if (!start) start = timestamp;
        const progress = timestamp - start;
        const percentage = Math.min(progress / duration, 1);
        
        element.style.opacity = initialOpacity * (1 - percentage);
        
        if (progress < duration) {
            window.requestAnimationFrame(animate);
        } else {
            element.style.display = 'none';
        }
    };
    
    window.requestAnimationFrame(animate);
}

/**
 * Animation de glissement vers le bas
 */
function geq_slideDown(element, duration = 300) {
    element.style.overflow = 'hidden';
    const height = element.scrollHeight;
    element.style.height = '0px';
    element.style.display = 'block';
    
    let start = null;
    const animate = (timestamp) => {
        if (!start) start = timestamp;
        const progress = timestamp - start;
        const percentage = Math.min(progress / duration, 1);
        
        element.style.height = (height * percentage) + 'px';
        
        if (progress < duration) {
            window.requestAnimationFrame(animate);
        } else {
            element.style.height = 'auto';
            element.style.overflow = '';
        }
    };
    
    window.requestAnimationFrame(animate);
}

/**
 * Animation de glissement vers le haut
 */
function geq_slideUp(element, duration = 300) {
    element.style.overflow = 'hidden';
    const height = element.scrollHeight;
    
    let start = null;
    const animate = (timestamp) => {
        if (!start) start = timestamp;
        const progress = timestamp - start;
        const percentage = Math.min(progress / duration, 1);
        
        element.style.height = (height * (1 - percentage)) + 'px';
        
        if (progress < duration) {
            window.requestAnimationFrame(animate);
        } else {
            element.style.display = 'none';
            element.style.height = '';
            element.style.overflow = '';
        }
    };
    
    window.requestAnimationFrame(animate);
}

/**
 * Animation de shake (tremblement)
 */
function geq_shake(element, duration = 500) {
    element.classList.add('shake');
    
    setTimeout(() => {
        element.classList.remove('shake');
    }, duration);
}

/**
 * Animation de pulse (pulsation)
 */
function geq_pulse(element, duration = 1000) {
    element.classList.add('pulse');
    
    setTimeout(() => {
        element.classList.remove('pulse');
    }, duration);
}

/**
 * Animation d'apparition des cartes d'équipes
 */
function geq_animateTeamCards() {
    const cards = document.querySelectorAll('.team-card');
    
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.4s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
}

/**
 * Animation de compteur
 */
function geq_animateCounter(element, start, end, duration = 1000) {
    const range = end - start;
    let current = start;
    const startTime = Date.now();
    
    const animate = () => {
        const elapsed = Date.now() - startTime;
        const progress = Math.min(elapsed / duration, 1);
        
        current = Math.floor(start + (range * progress));
        element.textContent = current;
        
        if (progress < 1) {
            requestAnimationFrame(animate);
        }
    };
    
    animate();
}

/**
 * Animation de notification toast
 */
function geq_animateToast(toast) {
    toast.style.transform = 'translateX(400px)';
    toast.style.opacity = '0';
    
    setTimeout(() => {
        toast.style.transition = 'all 0.3s ease';
        toast.style.transform = 'translateX(0)';
        toast.style.opacity = '1';
    }, 10);
}

/**
 * Animation de bouton loading
 */
function geq_showButtonLoading(button) {
    const btnText = button.querySelector('.btn-text');
    const btnLoading = button.querySelector('.btn-loading');
    
    if (btnText && btnLoading) {
        btnText.style.display = 'none';
        btnLoading.style.display = 'flex';
        button.disabled = true;
        button.classList.add('loading');
    }
}

/**
 * Animation de bouton normal
 */
function geq_hideButtonLoading(button) {
    const btnText = button.querySelector('.btn-text');
    const btnLoading = button.querySelector('.btn-loading');
    
    if (btnText && btnLoading) {
        btnText.style.display = 'inline';
        btnLoading.style.display = 'none';
        button.disabled = false;
        button.classList.remove('loading');
    }
}

/**
 * Animation de succès
 */
function geq_animateSuccess(element) {
    element.classList.add('success-animation');
    
    setTimeout(() => {
        element.classList.remove('success-animation');
    }, 1000);
}

/**
 * Animation d'erreur
 */
function geq_animateError(element) {
    element.classList.add('error-animation');
    
    setTimeout(() => {
        element.classList.remove('error-animation');
    }, 1000);
}

/**
 * Initialisation des animations au chargement
 */
document.addEventListener('DOMContentLoaded', function() {
    // Animation du header
    const header = document.querySelector('.app-header');
    if (header) {
        geq_fadeIn(header, 500);
    }
    
    // Animation du contenu principal
    const mainContent = document.querySelector('.main-content');
    if (mainContent) {
        setTimeout(() => {
            mainContent.style.opacity = '0';
            mainContent.style.transform = 'translateY(20px)';
            mainContent.style.transition = 'all 0.6s ease';
            mainContent.style.opacity = '1';
            mainContent.style.transform = 'translateY(0)';
        }, 200);
    }
});