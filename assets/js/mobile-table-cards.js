/**
 * Mobile Table to Card Converter
 * Automatically converts tables to mobile-friendly cards on small screens
 */

// Pages where this script should not run at all
const DISABLED_PATHS = [
  '/admin/management',
  '/ledgers/',
  '/users/view',
  '/client-ledger',
];

function isDisabledPage() {
  const path = window.location.pathname;
  return DISABLED_PATHS.some(p => path.includes(p));
}

function convertTablesToCards() {
  if (isDisabledPage()) return;
  if (window.innerWidth > 768) return;

  document.querySelectorAll('.table-responsive').forEach(container => {
    if (container.dataset.mobileConverted === '1') return;

    const tables = container.querySelectorAll('table');
    if (!tables.length) return;

    const cardContainer = document.createElement('div');
    cardContainer.className = 'mobile-card-container';

    tables.forEach(table => {
      const headers = Array.from(table.querySelectorAll('thead th'))
        .map(th => th.textContent.trim().replace(/[⇅🔍▲▼]/g, '').trim());

      const lastHeader = headers[headers.length - 1] || '';
      const hasActionsCol = /action|edit|manage/i.test(lastHeader)
        || table.querySelector('tbody td:last-child .ab-container') !== null;

      table.querySelectorAll('tbody tr').forEach(row => {
        const cells = row.querySelectorAll('td');
        if (!cells.length) return;
        cardContainer.appendChild(createCard(headers, cells, hasActionsCol));
      });

      table.style.display = 'none';
    });

    container.appendChild(cardContainer);
    container.dataset.mobileConverted = '1';
  });
}

function createCard(headers, cells, hasActionsCol) {
  const card = document.createElement('div');
  card.className = 'task-card';

  // Title = first cell text
  const title = cells[0]?.textContent.trim() || 'Item';

  // Fields: skip first (title) and last if it's actions col
  const fieldEnd = hasActionsCol ? cells.length - 1 : cells.length;

  let fieldsHTML = '';
  for (let i = 1; i < fieldEnd; i++) {
    if (!cells[i] || !headers[i]) continue;
    fieldsHTML += `
      <div class="task-card__field">
        <div class="task-card__label">${headers[i]}</div>
        <div class="task-card__value">${cells[i].innerHTML}</div>
      </div>`;
  }

  // Actions: only if last col is actions col
  let actionsHTML = '';
  if (hasActionsCol) {
    const lastCell = cells[cells.length - 1];
    const abContainer = lastCell?.querySelector('.ab-container');
    if (abContainer) {
      actionsHTML = `<div class="task-card__actions">${abContainer.innerHTML}</div>`;
    } else {
      // loose buttons fallback
      const btns = Array.from(lastCell?.querySelectorAll('a.btn, button.btn') || []);
      if (btns.length) {
        actionsHTML = `<div class="task-card__actions">${btns.map(b => b.outerHTML).join('')}</div>`;
      }
    }
  }

  card.innerHTML = `
    <div class="task-card__header">
      <h3 class="task-card__title">${title}</h3>
    </div>
    <div class="task-card__meta">${fieldsHTML}</div>
    ${actionsHTML}`;

  return card;
}

// Init
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', convertTablesToCards);
} else {
  convertTablesToCards();
}

window.addEventListener('resize', () => {
  if (isDisabledPage()) return;
  if (window.innerWidth > 768) {
    document.querySelectorAll('.table-responsive').forEach(container => {
      container.querySelector('.mobile-card-container')?.remove();
      const table = container.querySelector('table');
      if (table) table.style.display = '';
      delete container.dataset.mobileConverted;
    });
  } else {
    convertTablesToCards();
  }
});

window.convertTablesToCards = convertTablesToCards;
