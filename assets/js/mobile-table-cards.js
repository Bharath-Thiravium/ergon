/**
 * Mobile Table to Card Converter
 * Automatically converts tables to mobile-friendly cards on small screens
 */

// Complete script wrapper with early exit for incompatible pages
(function() {
  if (window.location.pathname.includes('/workflow/daily-planner') || 
      window.location.pathname.includes('/admin/management')) {
    console.log('Mobile table cards disabled for this page');
    return; // Exit completely
  }

  function convertTablesToCards() {
  // Completely disable on incompatible pages
  if (window.location.pathname.includes('/admin/management') || 
      window.location.pathname.includes('/workflow/daily-planner')) {
    return;
  }
  
  if (window.innerWidth > 768) return;

  const tables = document.querySelectorAll('.table-responsive');
  
  tables.forEach(container => {
    if (container.querySelector('.mobile-card-container')) return;
    
    const table = container.querySelector('table');
    if (!table) return;

    const headers = Array.from(table.querySelectorAll('thead th')).map(th => th.textContent.trim());
    const rows = table.querySelectorAll('tbody tr');
    
    const cardContainer = document.createElement('div');
    cardContainer.className = 'mobile-card-container';
    cardContainer.style.display = 'block';
    
    rows.forEach(row => {
      const cells = row.querySelectorAll('td');
      const card = createCard(headers, cells);
      cardContainer.appendChild(card);
    });
    
    container.style.position = 'relative';
    container.appendChild(cardContainer);
    
    // Hide table on mobile
    table.style.display = 'none';
  });
}

  function createCard(headers, cells) {
  const card = document.createElement('div');
  card.className = 'task-card';
  
  // Card content
  let cardHTML = `
    <div class="task-card__header">
      <h3 class="task-card__title">${cells[0]?.textContent.trim() || 'Item'}</h3>
    </div>
    <div class="task-card__meta">
  `;
  
  // Add fields (skip first column as it's the title)
  for (let i = 1; i < Math.min(headers.length, cells.length); i++) {
    if (cells[i] && headers[i]) {
      cardHTML += `
        <div class="task-card__field">
          <div class="task-card__label">${headers[i]}</div>
          <div class="task-card__value">${cells[i].innerHTML}</div>
        </div>
      `;
    }
  }
  
  cardHTML += '</div>';
  
  // Add exactly two action buttons from last cell (exclude View button and extended actions)
  const lastCell = cells[cells.length - 1];
  if (lastCell) {
    const allButtons = lastCell.querySelectorAll('button, a, .ab-btn');
    const filteredButtons = Array.from(allButtons).filter(btn => {
      const text = btn.textContent.toLowerCase();
      const hasAbContainer = btn.closest('.ab-container');
      return !text.includes('view') && !text.includes('extended') && !hasAbContainer;
    });
    
    if (filteredButtons.length > 0) {
      cardHTML += '<div class="task-card__actions">';
      // Take first two filtered buttons only
      for (let i = 0; i < Math.min(2, filteredButtons.length); i++) {
        cardHTML += filteredButtons[i].outerHTML;
      }
      cardHTML += '</div>';
    }
  }
  
  card.innerHTML = cardHTML;
  return card;
}

  function getStatusFromRow(cells) {
  // Look for status in common column positions or badge elements
  for (let cell of cells) {
    const badge = cell.querySelector('.badge, .status, [class*="status"]');
    if (badge) return badge.textContent.trim();
    
    const text = cell.textContent.trim().toLowerCase();
    if (['pending', 'completed', 'in progress', 'overdue', 'active', 'inactive'].includes(text)) {
      return text;
    }
  }
  return 'pending';
}

  function getPriorityFromRow(cells) {
  // Look for priority indicators
  for (let cell of cells) {
    const text = cell.textContent.trim().toLowerCase();
    if (['high', 'medium', 'low'].includes(text)) {
      return text;
    }
    
    const badge = cell.querySelector('.badge, .priority');
    if (badge) {
      const badgeText = badge.textContent.trim().toLowerCase();
      if (['high', 'medium', 'low'].includes(badgeText)) {
        return badgeText;
      }
    }
  }
  return 'medium';
}

  // Debug function
  function debugTables() {
  // Skip debug on excluded pages
  if (window.location.pathname.includes('/workflow/daily-planner') || 
      window.location.pathname.includes('/admin/management')) {
    return;
  }
  console.log('Screen width:', window.innerWidth);
  console.log('Tables found:', document.querySelectorAll('.table-responsive').length);
  console.log('Mobile cards found:', document.querySelectorAll('.mobile-card-container').length);
}

  // Initialize immediately and on events
  if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => {
    debugTables();
    convertTablesToCards();
  });
} else {
  debugTables();
  convertTablesToCards();
}

  // Also run after delays to catch dynamically loaded content
  setTimeout(() => {
  debugTables();
  convertTablesToCards();
}, 500);

  setTimeout(() => {
    debugTables();
    convertTablesToCards();
  }, 1000);

  window.addEventListener('resize', () => {
  if (window.location.pathname.includes('/admin/management') || 
      window.location.pathname.includes('/workflow/daily-planner')) {
    return;
  }
  
  if (window.innerWidth > 768) {
    document.querySelectorAll('.mobile-card-container').forEach(container => {
      container.remove();
    });
  } else {
    setTimeout(convertTablesToCards, 100);
  }
  });

  // Run on any table updates
  const observer = new MutationObserver(() => {
  if (window.location.pathname.includes('/admin/management') || 
      window.location.pathname.includes('/workflow/daily-planner')) {
    return;
  }
  if (window.innerWidth <= 768) {
    setTimeout(convertTablesToCards, 100);
  }
  });

  if (document.body) {
    observer.observe(document.body, { childList: true, subtree: true });
  }

  // Function to remove element by XPath
  function removeElementByXPath(xpath) {
  const result = document.evaluate(xpath, document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null);
  const element = result.singleNodeValue;
  if (element) {
    element.remove();
    console.log('Element removed:', xpath);
    return true;
  }
  console.log('Element not found:', xpath);
  return false;
  }

  // Remove the specific element
  removeElementByXPath('/html/body/main/div[3]/div[2]/div/div/div[1]/div[3]/div');

  // Export for manual triggering
  window.convertTablesToCards = convertTablesToCards;
  window.removeElementByXPath = removeElementByXPath;

})(); // Close the complete script IIFE