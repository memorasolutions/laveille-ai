/**
 * 🎨 SYSTÈME D'ANIMATIONS ÉQUILIBRÉES V3.6 - VERSION CORRIGÉE
 * Correction du problème de particules non visibles
 */

class AnimationsEquilibrees {
    constructor() {
        this.canvas = null;
        this.ctx = null;
        this.particles = [];
        this.isRunning = false;
        this.animationId = null;
        this.completeCalled = false;
        this.pendingTimeouts = []; // Pour tracker les timeouts
        
        // Configuration optimale basée sur vos tests
        this.baseConfig = {
            speedMultiplier: 0.8,
            particleLifetime: 4.0,
            gravity: 0.45,
            wind: 0,
            decayRate: 1.6
        };
    }

    init(canvas) {
        this.canvas = canvas;
        this.ctx = canvas.getContext('2d');
        // Fond transparent pour intégration sur la plateforme
        this.ctx.globalCompositeOperation = 'source-over';
        console.log('✅ AnimationsEquilibrees initialisé');
    }

    /**
     * BURST ÉQUILIBRÉ - Version corrigée
     */
    createBalancedBurst(config) {
        const colors = config.colors;
        const count = config.particleCount || 150;
        
        console.log(`🎯 Création burst avec ${count} particules`);
        
        // Points d'explosion multiples pour couvrir l'écran
        const explosionPoints = [
            { x: this.canvas.width / 2, y: this.canvas.height / 2, power: 1.0 },
            { x: this.canvas.width * 0.25, y: this.canvas.height * 0.25, power: 0.6 },
            { x: this.canvas.width * 0.75, y: this.canvas.height * 0.25, power: 0.6 },
            { x: this.canvas.width * 0.25, y: this.canvas.height * 0.75, power: 0.6 },
            { x: this.canvas.width * 0.75, y: this.canvas.height * 0.75, power: 0.6 }
        ];
        
        let totalParticlesCreated = 0;
        
        explosionPoints.forEach((point, index) => {
            const timeout = setTimeout(() => {
                const particlesForPoint = Math.floor(count * point.power / 3);
                
                for (let i = 0; i < particlesForPoint; i++) {
                    const angle = (Math.PI * 2 * i) / particlesForPoint + Math.random() * 0.5;
                    const speed = (15 + Math.random() * 25) * point.power * this.baseConfig.speedMultiplier;
                    
                    const particle = {
                        type: 'burst',
                        x: point.x,
                        y: point.y,
                        vx: Math.cos(angle) * speed,
                        vy: Math.sin(angle) * speed,
                        size: Math.random() * 12 + 6,
                        color: colors[Math.floor(Math.random() * colors.length)],
                        alpha: 1,
                        decay: 0.02 / this.baseConfig.particleLifetime,
                        gravity: 0.2,
                        friction: 0.98,
                        glow: Math.random() > 0.5
                    };
                    
                    this.particles.push(particle);
                    totalParticlesCreated++;
                }
                
                console.log(`✅ Point ${index + 1}: ${particlesForPoint} particules créées`);
                
                // Démarrer l'animation après la première explosion
                if (index === 0 && !this.isRunning) {
                    this.start();
                }
            }, index * 100);
            
            this.pendingTimeouts.push(timeout);
        });
    }

    /**
     * CONFETTIS ÉQUILIBRÉS - Version simplifiée
     */
    createBalancedConfetti(config) {
        const colors = config.colors;
        const count = config.particleCount || 120;
        
        console.log(`🎊 Création confetti avec ${count} particules`);
        
        // Créer tous les confettis immédiatement
        for (let i = 0; i < count; i++) {
            // Répartir sur toute la largeur + marges
            const x = -50 + Math.random() * (this.canvas.width + 100);
            const y = -100 - Math.random() * 200;
            
            const particle = {
                type: 'confetti',
                x: x,
                y: y,
                vx: (Math.random() - 0.5) * 3,
                vy: 4 + Math.random() * 6,
                size: Math.random() * 10 + 5,
                color: colors[Math.floor(Math.random() * colors.length)],
                alpha: 1,
                decay: 0.015 / this.baseConfig.particleLifetime,
                gravity: 0.25,
                rotation: Math.random() * Math.PI * 2,
                rotationSpeed: (Math.random() - 0.5) * 0.2,
                shape: Math.random() > 0.5 ? 'rect' : 'circle'
            };
            
            // Ajustements selon config
            particle.vy *= this.baseConfig.speedMultiplier;
            particle.gravity *= this.baseConfig.gravity / 0.3;
            
            this.particles.push(particle);
        }
    }

    /**
     * Autres méthodes d'animation (simplifiées pour le test)
     */
    createSparkles(config) {
        const colors = config.colors;
        const count = config.particleCount || 80;
        
        for (let i = 0; i < count; i++) {
            const x = Math.random() * this.canvas.width;
            const y = Math.random() * this.canvas.height;
            
            const particle = {
                type: 'sparkle',
                x: x,
                y: y,
                vx: (Math.random() - 0.5) * 2,
                vy: (Math.random() - 0.5) * 2,
                size: Math.random() * 8 + 4,
                color: colors[Math.floor(Math.random() * colors.length)],
                alpha: 1,
                decay: 0.01 / (this.baseConfig.particleLifetime * this.baseConfig.decayRate),
                pulsePhase: Math.random() * Math.PI * 2,
                pulseSpeed: 0.1 + Math.random() * 0.1,
                twinkle: true
            };
            
            this.particles.push(particle);
        }
    }

    createBalloons(config) {
        const colors = config.colors;
        const count = config.particleCount || 25;
        
        for (let i = 0; i < count; i++) {
            const x = Math.random() * this.canvas.width;
            const y = this.canvas.height + 50;
            
            const particle = {
                type: 'balloon',
                x: x,
                y: y,
                vx: (Math.random() - 0.5) * 1,
                vy: -(2 + Math.random() * 2) * this.baseConfig.speedMultiplier,
                size: 15 + Math.random() * 15,
                color: colors[Math.floor(Math.random() * colors.length)],
                alpha: 1,
                decay: 0.005 / (this.baseConfig.particleLifetime * this.baseConfig.decayRate),
                bobPhase: Math.random() * Math.PI * 2,
                bobSpeed: 0.05,
                stringLength: 20 + Math.random() * 15
            };
            
            this.particles.push(particle);
        }
    }

    createHearts(config) {
        const colors = ['#FF69B4', '#FF1493', '#DC143C', '#B22222'];
        const count = config.particleCount || 30;
        
        for (let i = 0; i < count; i++) {
            const x = Math.random() * this.canvas.width;
            const y = this.canvas.height + 30;
            
            const particle = {
                type: 'heart',
                x: x,
                y: y,
                vx: (Math.random() - 0.5) * 1,
                vy: -(1 + Math.random() * 2) * this.baseConfig.speedMultiplier,
                size: 12 + Math.random() * 8,
                color: colors[Math.floor(Math.random() * colors.length)],
                alpha: 1,
                decay: 0.008 / (this.baseConfig.particleLifetime * this.baseConfig.decayRate),
                pulsePhase: Math.random() * Math.PI * 2,
                pulseSpeed: 0.08,
                floatPhase: Math.random() * Math.PI * 2
            };
            
            this.particles.push(particle);
        }
    }

    createStars(config) {
        const colors = config.colors;
        const count = config.particleCount || 20;
        
        for (let i = 0; i < count; i++) {
            let x, y, vx, vy;
            const side = i % 4;
            
            switch(side) {
                case 0: // Du haut
                    x = Math.random() * this.canvas.width;
                    y = -50;
                    vx = (Math.random() - 0.5) * 6;
                    vy = 3 + Math.random() * 4;
                    break;
                case 1: // De la gauche
                    x = -50;
                    y = Math.random() * this.canvas.height * 0.6;
                    vx = 4 + Math.random() * 4;
                    vy = 2 + Math.random() * 3;
                    break;
                case 2: // De la droite
                    x = this.canvas.width + 50;
                    y = Math.random() * this.canvas.height * 0.6;
                    vx = -(4 + Math.random() * 4);
                    vy = 2 + Math.random() * 3;
                    break;
                case 3: // Diagonal
                    x = Math.random() * this.canvas.width;
                    y = -50;
                    vx = (Math.random() - 0.5) * 8;
                    vy = 4 + Math.random() * 3;
                    break;
            }
            
            const particle = {
                type: 'star',
                x: x,
                y: y,
                vx: vx * this.baseConfig.speedMultiplier,
                vy: vy * this.baseConfig.speedMultiplier,
                size: 8 + Math.random() * 6,
                color: colors[Math.floor(Math.random() * colors.length)],
                alpha: 1,
                decay: 0.012 / (this.baseConfig.particleLifetime * this.baseConfig.decayRate),
                rotation: 0,
                rotationSpeed: (Math.random() - 0.5) * 0.3,
                trail: [],
                maxTrailLength: 8
            };
            
            this.particles.push(particle);
        }
    }

    createSnow(config) {
        const colors = ['#1E90FF', '#4169E1', '#0000CD', '#191970', '#00008B', '#483D8B', '#6495ED', '#87CEEB'];
        const count = config.particleCount || 60;
        
        for (let i = 0; i < count; i++) {
            const x = -50 + Math.random() * (this.canvas.width + 100);
            const y = -Math.random() * this.canvas.height - 100;
            
            const particle = {
                type: 'snow',
                x: x,
                y: y,
                vx: (Math.random() - 0.5) * 2,
                vy: (1 + Math.random() * 2) * this.baseConfig.speedMultiplier,
                size: 3 + Math.random() * 5,
                color: colors[Math.floor(Math.random() * colors.length)],
                alpha: 0.8 + Math.random() * 0.2,
                decay: 0.002 / (this.baseConfig.particleLifetime * this.baseConfig.decayRate),
                rotation: 0,
                rotationSpeed: (Math.random() - 0.5) * 0.1,
                swayPhase: Math.random() * Math.PI * 2,
                swaySpeed: 0.03
            };
            
            this.particles.push(particle);
        }
    }

    createBalancedFireworks(config) {
        const colors = config.colors;
        const rocketCount = 8;
        
        for (let i = 0; i < rocketCount; i++) {
            const timeout = setTimeout(() => {
                const x = (this.canvas.width / (rocketCount + 1)) * (i + 1) + (Math.random() - 0.5) * 50;
                const targetY = this.canvas.height * (0.2 + Math.random() * 0.3);
                
                const rocket = {
                    type: 'rocket',
                    x: x,
                    y: this.canvas.height,
                    vx: (Math.random() - 0.5) * 2,
                    vy: -18 - Math.random() * 10,
                    targetY: targetY,
                    size: 6,
                    color: '#FFFFFF',
                    alpha: 1,
                    trail: [],
                    maxTrailLength: 20,
                    hasExploded: false,
                    explosionColors: colors
                };
                
                rocket.vy *= this.baseConfig.speedMultiplier;
                
                this.particles.push(rocket);
                
                // Démarrer l'animation si pas déjà en cours
                if (!this.isRunning) {
                    this.start();
                }
            }, i * 300);
            
            this.pendingTimeouts.push(timeout);
        }
    }

    /**
     * MISE À JOUR DES PARTICULES
     */
    updateParticles() {
        for (let i = this.particles.length - 1; i >= 0; i--) {
            const particle = this.particles[i];
            
            // Update position de base
            particle.x += particle.vx || 0;
            particle.y += particle.vy || 0;
            
            // Update selon le type
            switch (particle.type) {
                case 'burst':
                    particle.vx *= particle.friction || 0.98;
                    particle.vy += particle.gravity || 0.2;
                    particle.vy *= particle.friction || 0.98;
                    particle.alpha -= particle.decay || 0.02;
                    particle.size *= 0.99;
                    break;
                    
                case 'confetti':
                    particle.vy += particle.gravity || 0.25;
                    particle.rotation += particle.rotationSpeed || 0;
                    particle.alpha -= particle.decay || 0.015;
                    particle.vx += Math.sin(particle.y * 0.02) * 0.1;
                    break;
                    
                case 'sparkle':
                    particle.pulsePhase += particle.pulseSpeed || 0.1;
                    particle.alpha -= particle.decay || 0.01;
                    particle.vx += Math.sin(particle.pulsePhase) * 0.01;
                    particle.vy += Math.cos(particle.pulsePhase) * 0.01;
                    particle.vx *= 0.998;
                    particle.vy *= 0.998;
                    break;
                    
                case 'balloon':
                    particle.bobPhase += particle.bobSpeed || 0.05;
                    particle.alpha -= particle.decay || 0.005;
                    particle.vx += Math.sin(particle.bobPhase) * 0.02;
                    break;
                    
                case 'heart':
                    particle.pulsePhase += particle.pulseSpeed || 0.08;
                    particle.floatPhase += 0.03;
                    particle.alpha -= particle.decay || 0.008;
                    particle.vx += Math.sin(particle.floatPhase * 2) * 0.03;
                    particle.vy += Math.cos(particle.floatPhase) * 0.01;
                    break;
                    
                case 'star':
                    particle.rotation += particle.rotationSpeed || 0;
                    particle.alpha -= particle.decay || 0.012;
                    if (particle.trail) {
                        particle.trail.push({
                            x: particle.x,
                            y: particle.y,
                            alpha: particle.alpha * 0.6
                        });
                        if (particle.trail.length > particle.maxTrailLength) {
                            particle.trail.shift();
                        }
                    }
                    particle.vy += this.baseConfig.gravity * 0.1;
                    break;
                    
                case 'snow':
                    particle.rotation += particle.rotationSpeed || 0;
                    particle.swayPhase += particle.swaySpeed || 0.03;
                    particle.alpha -= particle.decay || 0.002;
                    particle.vx += Math.sin(particle.swayPhase) * 0.02;
                    break;
                    
                case 'rocket':
                    if (!particle.hasExploded) {
                        particle.vy += 0.5;
                        if (particle.trail) {
                            particle.trail.push({
                                x: particle.x,
                                y: particle.y,
                                alpha: 1
                            });
                            if (particle.trail.length > particle.maxTrailLength) {
                                particle.trail.shift();
                            }
                        }
                        if (particle.y <= particle.targetY || particle.vy >= 0) {
                            particle.hasExploded = true;
                            this.createExplosion(particle.x, particle.y, particle.explosionColors);
                        }
                    } else {
                        if (particle.trail) {
                            particle.trail.forEach(point => {
                                point.alpha -= 0.05;
                            });
                            if (particle.trail.length === 0 || particle.trail[0].alpha <= 0) {
                                particle.alpha = 0;
                            }
                        }
                    }
                    break;
            }
            
            // Supprimer si mort ou hors écran
            if (particle.alpha <= 0 || 
                particle.y > this.canvas.height + 100 ||
                particle.y < -200 ||
                particle.x < -200 ||
                particle.x > this.canvas.width + 200) {
                this.particles.splice(i, 1);
            }
        }
    }

    createExplosion(x, y, colors) {
        const sparkCount = 80;
        
        for (let i = 0; i < sparkCount; i++) {
            const angle = (Math.PI * 2 * i) / sparkCount + Math.random() * 0.2;
            const speed = 15 + Math.random() * 25;
            
            const spark = {
                type: 'spark',
                x: x,
                y: y,
                vx: Math.cos(angle) * speed * this.baseConfig.speedMultiplier,
                vy: Math.sin(angle) * speed * this.baseConfig.speedMultiplier,
                size: Math.random() * 6 + 3,
                color: colors[Math.floor(Math.random() * colors.length)],
                alpha: 1,
                decay: 0.025 / this.baseConfig.particleLifetime,
                gravity: 0.15,
                friction: 0.98,
                shimmer: Math.random() > 0.5
            };
            
            this.particles.push(spark);
        }
    }

    /**
     * RENDU
     */
    render() {
        // Clear total pour transparence
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
        
        // Rendu de toutes les particules
        this.particles.forEach(p => {
            this.ctx.save();
            this.ctx.globalAlpha = p.alpha;
            
            switch(p.type) {
                case 'burst':
                case 'spark':
                    if (p.glow) {
                        this.ctx.shadowBlur = 15;
                        this.ctx.shadowColor = p.color;
                    }
                    this.ctx.fillStyle = p.color;
                    this.ctx.beginPath();
                    this.ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
                    this.ctx.fill();
                    break;
                    
                case 'confetti':
                    this.ctx.translate(p.x, p.y);
                    this.ctx.rotate(p.rotation || 0);
                    this.ctx.fillStyle = p.color;
                    if (p.shape === 'rect') {
                        this.ctx.fillRect(-p.size/2, -p.size/2, p.size, p.size);
                    } else {
                        this.ctx.beginPath();
                        this.ctx.arc(0, 0, p.size/2, 0, Math.PI * 2);
                        this.ctx.fill();
                    }
                    break;
                    
                case 'sparkle':
                    const pulse = 0.7 + 0.3 * Math.sin(p.pulsePhase || 0);
                    const sparkleSize = p.size * pulse;
                    this.ctx.shadowBlur = 15;
                    this.ctx.shadowColor = p.color;
                    this.ctx.fillStyle = p.color;
                    
                    // Étoile à 6 branches
                    this.ctx.translate(p.x, p.y);
                    this.ctx.beginPath();
                    for (let i = 0; i < 6; i++) {
                        const angle = (i * Math.PI) / 3;
                        const x1 = Math.cos(angle) * sparkleSize;
                        const y1 = Math.sin(angle) * sparkleSize;
                        const x2 = Math.cos(angle + Math.PI / 6) * sparkleSize * 0.5;
                        const y2 = Math.sin(angle + Math.PI / 6) * sparkleSize * 0.5;
                        
                        if (i === 0) this.ctx.moveTo(x1, y1);
                        else this.ctx.lineTo(x1, y1);
                        this.ctx.lineTo(x2, y2);
                    }
                    this.ctx.closePath();
                    this.ctx.fill();
                    break;
                    
                case 'balloon':
                    // Ficelle
                    this.ctx.strokeStyle = '#8B4513';
                    this.ctx.lineWidth = 1;
                    this.ctx.beginPath();
                    this.ctx.moveTo(p.x, p.y + p.size/2);
                    this.ctx.lineTo(p.x + Math.sin(p.bobPhase || 0) * 3, p.y + p.size/2 + p.stringLength);
                    this.ctx.stroke();
                    
                    // Ballon
                    this.ctx.fillStyle = p.color;
                    this.ctx.beginPath();
                    this.ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
                    this.ctx.fill();
                    break;
                    
                case 'heart':
                    const heartPulse = 0.8 + 0.2 * Math.sin(p.pulsePhase || 0);
                    const heartSize = p.size * heartPulse;
                    this.ctx.translate(p.x, p.y);
                    this.ctx.fillStyle = p.color;
                    this.ctx.shadowBlur = 10;
                    this.ctx.shadowColor = p.color;
                    
                    // Dessiner un cœur
                    this.ctx.beginPath();
                    this.ctx.moveTo(0, heartSize * 0.3);
                    this.ctx.bezierCurveTo(-heartSize * 0.5, -heartSize * 0.2, -heartSize * 0.8, heartSize * 0.1, 0, heartSize * 0.8);
                    this.ctx.bezierCurveTo(heartSize * 0.8, heartSize * 0.1, heartSize * 0.5, -heartSize * 0.2, 0, heartSize * 0.3);
                    this.ctx.fill();
                    break;
                    
                case 'star':
                    // Trail d'étoile
                    if (p.trail) {
                        p.trail.forEach((point, i) => {
                            this.ctx.globalAlpha = point.alpha * (i / p.trail.length);
                            this.ctx.fillStyle = p.color;
                            this.ctx.beginPath();
                            this.ctx.arc(point.x, point.y, 2, 0, Math.PI * 2);
                            this.ctx.fill();
                        });
                    }
                    
                    // Étoile principale
                    this.ctx.globalAlpha = p.alpha;
                    this.ctx.translate(p.x, p.y);
                    this.ctx.rotate(p.rotation || 0);
                    this.ctx.fillStyle = p.color;
                    this.ctx.shadowBlur = 12;
                    this.ctx.shadowColor = p.color;
                    
                    // Étoile à 5 branches
                    this.ctx.beginPath();
                    for (let i = 0; i < 5; i++) {
                        const angle = (i * Math.PI * 2) / 5 - Math.PI / 2;
                        const x1 = Math.cos(angle) * p.size;
                        const y1 = Math.sin(angle) * p.size;
                        const x2 = Math.cos(angle + Math.PI / 5) * p.size * 0.5;
                        const y2 = Math.sin(angle + Math.PI / 5) * p.size * 0.5;
                        
                        if (i === 0) this.ctx.moveTo(x1, y1);
                        else this.ctx.lineTo(x1, y1);
                        this.ctx.lineTo(x2, y2);
                    }
                    this.ctx.closePath();
                    this.ctx.fill();
                    break;
                    
                case 'snow':
                    this.ctx.translate(p.x, p.y);
                    this.ctx.rotate(p.rotation || 0);
                    this.ctx.strokeStyle = p.color;
                    this.ctx.lineWidth = 1;
                    
                    // Flocon à 6 branches
                    for (let i = 0; i < 6; i++) {
                        this.ctx.beginPath();
                        this.ctx.moveTo(0, 0);
                        const angle = (i * Math.PI) / 3;
                        const endX = Math.cos(angle) * p.size;
                        const endY = Math.sin(angle) * p.size;
                        this.ctx.lineTo(endX, endY);
                        this.ctx.stroke();
                        
                        // Petites branches
                        this.ctx.beginPath();
                        this.ctx.moveTo(endX * 0.7, endY * 0.7);
                        this.ctx.lineTo(endX * 0.7 + Math.cos(angle + Math.PI/4) * p.size * 0.3, 
                                      endY * 0.7 + Math.sin(angle + Math.PI/4) * p.size * 0.3);
                        this.ctx.moveTo(endX * 0.7, endY * 0.7);
                        this.ctx.lineTo(endX * 0.7 + Math.cos(angle - Math.PI/4) * p.size * 0.3, 
                                      endY * 0.7 + Math.sin(angle - Math.PI/4) * p.size * 0.3);
                        this.ctx.stroke();
                    }
                    break;
                    
                case 'rocket':
                    // Trail
                    if (p.trail) {
                        p.trail.forEach((point, i) => {
                            this.ctx.globalAlpha = point.alpha * (i / p.trail.length);
                            this.ctx.fillStyle = '#FFA500';
                            this.ctx.beginPath();
                            this.ctx.arc(point.x, point.y, 3, 0, Math.PI * 2);
                            this.ctx.fill();
                        });
                    }
                    
                    // Rocket
                    if (!p.hasExploded) {
                        this.ctx.globalAlpha = 1;
                        this.ctx.shadowBlur = 10;
                        this.ctx.shadowColor = '#FFFFFF';
                        this.ctx.fillStyle = p.color;
                        this.ctx.beginPath();
                        this.ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
                        this.ctx.fill();
                    }
                    break;
            }
            
            this.ctx.restore();
        });
    }

    /**
     * BOUCLE D'ANIMATION
     */
    animate = () => {
        if (!this.isRunning) return;
        
        this.updateParticles();
        this.render();
        
        // Arrêter automatiquement s'il n'y a plus de particules
        if (this.particles.length === 0) {
            console.log('🛑 Plus de particules, arrêt de l\'animation');
            this.stop();
            return;
        }
        
        this.animationId = requestAnimationFrame(this.animate);
    }

    start() {
        if (!this.isRunning) {
            this.isRunning = true;
            console.log('▶️ Animation démarrée');
            this.animate();
        }
    }

    stop() {
        this.isRunning = false;
        if (this.animationId) {
            cancelAnimationFrame(this.animationId);
            this.animationId = null;
        }
        
        // Clear pending timeouts
        this.pendingTimeouts.forEach(timeout => clearTimeout(timeout));
        this.pendingTimeouts = [];
        
        console.log('⏹️ Animation arrêtée');
        
        // Appeler onComplete une seule fois
        if (this.onComplete && !this.completeCalled) {
            this.completeCalled = true;
            this.onComplete();
            this.onComplete = null;
        }
    }

    clear() {
        this.particles = [];
        if (this.ctx && this.canvas) {
            this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
        }
        this.stop();
        console.log('🧹 Canvas nettoyé');
    }

    /**
     * INTERFACE PRINCIPALE
     */
    launch(type, config) {
        console.log(`🎨 Lancement animation équilibrée: ${type}`);
        console.log('Config:', config);
        
        // Réinitialiser
        this.completeCalled = false;
        this.particles = [];
        
        // Appliquer la configuration
        this.baseConfig = {
            speedMultiplier: config.speedMultiplier || 1.0,
            particleLifetime: config.particleLifetime || 1.0,
            gravity: config.gravity || 0.3,
            wind: config.wind || 0,
            decayRate: config.decayRate || 1.6
        };
        
        switch(type) {
            case 'burst':
            case 'explosion':
                this.createBalancedBurst(config);
                break;
            case 'confetti-rain':
            case 'confetti':
                this.createBalancedConfetti(config);
                break;
            case 'fireworks':
                this.createBalancedFireworks(config);
                break;
            case 'sparkles':
                this.createSparkles(config);
                break;
            case 'balloons':
                this.createBalloons(config);
                break;
            case 'hearts':
                this.createHearts(config);
                break;
            case 'stars':
                this.createStars(config);
                break;
            case 'snow':
                this.createSnow(config);
                break;
            default:
                console.warn(`Type d'animation inconnu: ${type}, utilisation de burst`);
                this.createBalancedBurst(config);
        }
        
        // Démarrer immédiatement pour les animations sans timeout
        if (this.particles.length > 0 && !this.isRunning) {
            this.start();
        }
        
        console.log(`✅ ${this.particles.length} particules créées immédiatement`);
    }
}

// Exposer globalement
window.AnimationsEquilibrees = AnimationsEquilibrees;