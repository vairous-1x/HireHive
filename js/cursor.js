document.addEventListener('DOMContentLoaded', () => {
  const cursor = document.querySelector('.custom-cursor');
  const cursorDot = document.querySelector('.custom-cursor-dot');

  const updateCursorPosition = (e) => {
    cursor.style.left = `${e.clientX}px`;
    cursor.style.top = `${e.clientY}px`;
    cursorDot.style.left = `${e.clientX}px`;
    cursorDot.style.top = `${e.clientY}px`;
  };

  const updateHoverState = (e) => {
    const target = e.target;
    const isPaymentForm = target.closest('#payment-details-step');
    const isHoverable = target.closest('button:not(#payment-details-step button), a:not(#payment-details-step a)');
    
    if (isPaymentForm) {
      cursor.style.display = 'none';
      cursorDot.style.display = 'none';
      document.body.style.cursor = 'auto';
    } else {
      cursor.style.display = 'block';
      cursorDot.style.display = 'block';
      document.body.style.cursor = 'none';
      cursor.classList.toggle('hover', isHoverable !== null);
    }
  };

  document.addEventListener('mousemove', updateCursorPosition);
  document.addEventListener('mouseover', updateHoverState);
});