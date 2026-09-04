/**
 * Forno do Bairro — Interações gerais
 */
document.addEventListener('DOMContentLoaded', () => {
  // Menu mobile (site público)
  const toggle = document.querySelector('.nav-toggle');
  const links = document.querySelector('.nav-links');
  if (toggle && links) {
    toggle.addEventListener('click', () => links.classList.toggle('is-open'));
  }

  // Menu mobile (painel admin)
  const adminToggle = document.querySelector('.mobile-nav-toggle');
  const sidebar = document.querySelector('.admin-sidebar');
  if (adminToggle && sidebar) {
    adminToggle.addEventListener('click', () => sidebar.classList.toggle('is-open'));
  }

  // Fecha alertas automaticamente após alguns segundos
  document.querySelectorAll('.alert[data-auto-dismiss]').forEach((alerta) => {
    setTimeout(() => {
      alerta.style.transition = 'opacity .4s ease';
      alerta.style.opacity = '0';
      setTimeout(() => alerta.remove(), 400);
    }, 5000);
  });
});
