// test-remove-button-debug.js
(function runRemoveButtonAudit() {
  console.log('🧪 Starting Remove Button Audit');

  const removeButtons = document.querySelectorAll('.ab-btn[data-action="delete"]');

  if (removeButtons.length === 0) {
    console.warn('❌ No Remove buttons found with .ab-btn[data-action="delete"]');
    return;
  }

  console.log(`✅ Found ${removeButtons.length} Remove button(s)`);

  removeButtons.forEach((btn, index) => {
    const userId = btn.dataset.id || btn.dataset.userId || 'N/A';
    const action = btn.dataset.action || 'undefined';
    const title = btn.getAttribute('title') || 'missing';

    console.log(`🔍 Button ${index + 1}:`, {
      innerText: btn.innerText,
      userId,
      action,
      title,
      classList: [...btn.classList],
    });

    btn.addEventListener('click', function (e) {
      e.preventDefault();

      console.log(`🧪 Clicked Remove button for user ID: ${userId}`);
      console.log(`➡️ Action triggered: ${action}`);

      fetch(`/ergon/users/delete/${userId}`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        }
      })
      .then(res => res.json())
      .then(data => {
        console.log('✅ Server response:', data);
        if (data.success) {
          console.log(`🎉 User ${userId} successfully marked as removed.`);
        } else {
          console.error(`❌ Server error:`, data.message || data);
        }
      })
      .catch(err => {
        console.error('❌ Network or JS error:', err);
      });
    });
  });

  console.log('🧪 Audit script loaded. Click any Remove button to test.');
})();