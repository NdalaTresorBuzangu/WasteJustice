const hero = document.getElementById('hero');
    
    document.addEventListener('mousemove', (e) => {
      const { left, top, width, height } = hero.getBoundingClientRect();
      const x = (e.clientX - left) / width - 0.5;
      const y = (e.clientY - top) / height - 0.5;
      
      hero.style.transform = `
        perspective(1000px)
        rotateY(${x * 3}deg)
        rotateX(${-y * 3}deg)
        translateZ(10px)
      `;
    });

    hero.addEventListener('mouseleave', () => {
      hero.style.transform = 'perspective(1000px) rotateY(0) rotateX(0) translateZ(0)';
    });

    // Add ripple effect on click
    hero.addEventListener('click', (e) => {
      const ripple = document.createElement('div');
      ripple.className = 'ripple-effect';
      const rect = hero.getBoundingClientRect();
      ripple.style.left = `${e.clientX - rect.left}px`;
      ripple.style.top = `${e.clientY - rect.top}px`;
      hero.appendChild(ripple);
      
      setTimeout(() => {
        ripple.remove();
      }, 1500);
    });