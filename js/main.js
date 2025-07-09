function agregarTooltip(selector, texto) {
  const campo = document.querySelector(selector);
  if (campo) {
    campo.setAttribute('title', texto);
    campo.addEventListener('mouseover', () => {
      campo.style.borderColor = '#388e3c';
    });
  }
}

// Ejemplo de uso:
document.addEventListener('DOMContentLoaded', () => {
  agregarTooltip('input[name="dni"]', 'Ingrese su DNI de 8 dígitos');
});
