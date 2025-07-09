document.addEventListener('DOMContentLoaded', () => {
  const form = document.querySelector('form');
  const submitButton = form.querySelector('input[type="submit"]');
  let errorContainer = form.querySelector('.error');
  
  // Crear contenedor de error si no existe (para errores del lado cliente)
  if (!errorContainer) {
    errorContainer = document.createElement('div');
    errorContainer.classList.add('error');
    form.appendChild(errorContainer);
  }

  // Función para mostrar errores
  function showError(message) {
    errorContainer.textContent = message;
    errorContainer.style.display = 'block';
    errorContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }

  // Función para limpiar errores
  function clearError() {
    errorContainer.textContent = '';
    errorContainer.style.display = 'none';
  }

  // Validación en tiempo real
  const usernameInput = form.querySelector('input[name="username"]');
  const passwordInput = form.querySelector('input[name="password"]');

  // Limpiar error cuando el usuario empiece a escribir
  [usernameInput, passwordInput].forEach(input => {
    input.addEventListener('input', () => {
      clearError();
      input.classList.remove('input-error');
    });

    // Efecto focus mejorado
    input.addEventListener('focus', () => {
      if (!errorContainer.textContent) {
        input.style.borderColor = '#8ceabb';
        input.style.boxShadow = '0 0 0 3px rgba(140, 234, 187, 0.2)';
      }
    });

    input.addEventListener('blur', () => {
      if (!errorContainer.textContent) {
        input.style.borderColor = 'rgba(255, 255, 255, 0.2)';
        input.style.boxShadow = 'none';
      }
    });
  });

  // Validación del formulario
  form.addEventListener('submit', (e) => {
    const username = usernameInput.value.trim();
    const password = passwordInput.value.trim();

    // Limpiar error previo
    clearError();
    [usernameInput, passwordInput].forEach(input => {
      input.classList.remove('input-error');
    });

    // Validaciones del lado cliente
    if (!username || !password) {
      showError('Por favor, completa todos los campos.');
      
      // Resaltar campos vacíos
      if (!username) usernameInput.classList.add('input-error');
      if (!password) passwordInput.classList.add('input-error');
      
      e.preventDefault();
      return;
    }

    // Validación adicional de longitud
    if (username.length < 3) {
      showError('El nombre de usuario debe tener al menos 3 caracteres.');
      usernameInput.classList.add('input-error');
      e.preventDefault();
      return;
    }

    if (password.length < 4) {
      showError('La contraseña debe tener al menos 4 caracteres.');
      passwordInput.classList.add('input-error');
      e.preventDefault();
      return;
    }

    // Validación de formato de email (opcional)
    if (username.includes('@') && !validateEmail(username)) {
      showError('Por favor, ingresa un email válido.');
      usernameInput.classList.add('input-error');
      e.preventDefault();
      return;
    }

    // Mostrar estado de carga
    submitButton.value = 'Ingresando...';
    submitButton.disabled = true;
    submitButton.style.opacity = '0.7';
    
    // Agregar spinner visual (opcional)
    submitButton.style.position = 'relative';
  });

  // Función para validar email
  function validateEmail(email) {
    const re = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
    return re.test(email);
  }

  // Efectos adicionales para mejorar la UX
  
  // Animación del logo al hacer clic
  const logo = document.querySelector('.site__logo');
  if (logo) {
    logo.addEventListener('click', () => {
      logo.style.transform = 'rotate(360deg)';
      
      setTimeout(() => {
        logo.style.transform = 'rotate(0deg)';
      }, 600);
    });
  }

  // Prevenir doble envío del formulario
  let isSubmitting = false;
  
  form.addEventListener('submit', (e) => {
    if (isSubmitting) {
      e.preventDefault();
      return;
    }
    
    // Solo marcar como enviando si pasa todas las validaciones
    const username = usernameInput.value.trim();
    const password = passwordInput.value.trim();
    
    if (username && password && username.length >= 3 && password.length >= 4) {
      isSubmitting = true;
    }
  });

  // Auto-foco en el primer campo
  if (usernameInput) {
    usernameInput.focus();
  }

  // Efecto de typing para placeholders (opcional)
  function typewriterEffect(element, text, speed = 100) {
    let i = 0;
    element.setAttribute('placeholder', '');
    
    function typeWriter() {
      if (i < text.length) {
        element.setAttribute('placeholder', element.getAttribute('placeholder') + text.charAt(i));
        i++;
        setTimeout(typeWriter, speed);
      }
    }
    
    typeWriter();
  }

  // Detección de caps lock
  [usernameInput, passwordInput].forEach(input => {
    input.addEventListener('keydown', (e) => {
      if (e.getModifierState && e.getModifierState('CapsLock')) {
        showCapsLockWarning(input);
      } else {
        hideCapsLockWarning(input);
      }
    });
  });

  function showCapsLockWarning(input) {
    let warning = input.parentNode.querySelector('.caps-lock-warning');
    if (!warning) {
      warning = document.createElement('div');
      warning.className = 'caps-lock-warning';
      warning.style.cssText = `
        position: absolute;
        top: 100%;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(255, 193, 7, 0.9);
        color: #000;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-size: 0.75rem;
        z-index: 1000;
        white-space: nowrap;
        margin-top: 0.25rem;
      `;
      warning.textContent = 'Caps Lock está activado';
      input.parentNode.appendChild(warning);
    }
  }

  function hideCapsLockWarning(input) {
    const warning = input.parentNode.querySelector('.caps-lock-warning');
    if (warning) {
      warning.remove();
    }
  }

  // Mejorar accesibilidad con teclado
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && document.activeElement.type !== 'submit') {
      e.preventDefault();
      const inputs = Array.from(form.querySelectorAll('input[type="text"], input[type="password"]'));
      const currentIndex = inputs.indexOf(document.activeElement);
      
      if (currentIndex < inputs.length - 1) {
        inputs[currentIndex + 1].focus();
      } else {
        submitButton.focus();
      }
    }
  });

  // Limpiar estado al recargar la página
  window.addEventListener('beforeunload', () => {
    if (submitButton) {
      submitButton.value = 'Ingresar';
      submitButton.disabled = false;
      submitButton.style.opacity = '1';
    }
  });

  // Manejo de errores del servidor
  const serverError = document.querySelector('.error-message');
  if (serverError) {
    // Si hay un error del servidor, enfocar el primer campo para facilitar la corrección
    setTimeout(() => {
      if (usernameInput) {
        usernameInput.focus();
        usernameInput.select();
      }
    }, 100);
  }
});