/**
 * Production-Ready Sidebar Scrolling Component
 * Lightweight, compatible, accessible
 */
(function() {
  'use strict';
  
  // Feature detection
  var supportsScrollBehavior = 'scrollBehavior' in document.documentElement.style;
  
  // Polyfill for smooth scrolling
  function smoothScrollTo(element, target, duration) {
    if (supportsScrollBehavior) {
      element.scrollTo({
        top: target,
        behavior: 'smooth'
      });
      return;
    }
    
    // Fallback animation for older browsers
    var start = element.scrollTop;
    var change = target - start;
    var startTime = performance.now();
    
    function animateScroll(currentTime) {
      var elapsed = currentTime - startTime;
      var progress = Math.min(elapsed / duration, 1);
      
      // Easing function (ease-out)
      var easeOut = 1 - Math.pow(1 - progress, 3);
      
      element.scrollTop = start + (change * easeOut);
      
      if (progress < 1) {
        requestAnimationFrame(animateScroll);
      }
    }
    
    requestAnimationFrame(animateScroll);
  }
  
  // Scroll active item into view
  function scrollActiveIntoView() {
    var sidebar = document.querySelector('.sidebar-nav');
    var activeItem = document.querySelector('.sidebar-nav .is-active');
    
    if (!sidebar || !activeItem) return;
    
    var sidebarRect = sidebar.getBoundingClientRect();
    var itemRect = activeItem.getBoundingClientRect();
    
    // Check if item is already visible
    var isVisible = itemRect.top >= sidebarRect.top && 
                   itemRect.bottom <= sidebarRect.bottom;
    
    if (!isVisible) {
      var scrollTop = activeItem.offsetTop - sidebar.offsetTop - 
                     (sidebar.clientHeight / 2) + (activeItem.clientHeight / 2);
      
      // Ensure scroll position is within bounds
      scrollTop = Math.max(0, Math.min(scrollTop, sidebar.scrollHeight - sidebar.clientHeight));
      
      smoothScrollTo(sidebar, scrollTop, 300);
    }
  }
  
  // Initialize on DOM ready
  function init() {
    // Scroll active item into view on page load
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', scrollActiveIntoView);
    } else {
      scrollActiveIntoView();
    }
    
    // Handle navigation clicks
    document.addEventListener('click', function(e) {
      var link = e.target.closest('.sidebar-nav a');
      if (!link) return;
      
      // Remove active class from all items
      var allLinks = document.querySelectorAll('.sidebar-nav a');
      for (var i = 0; i < allLinks.length; i++) {
        allLinks[i].classList.remove('is-active');
      }
      
      // Add active class to clicked item
      link.classList.add('is-active');
      
      // Scroll into view after a short delay (for page transition)
      setTimeout(scrollActiveIntoView, 100);
    });
    
    // Handle keyboard navigation
    document.addEventListener('keydown', function(e) {
      var activeItem = document.querySelector('.sidebar-nav .is-active');
      if (!activeItem) return;
      
      var allLinks = Array.prototype.slice.call(document.querySelectorAll('.sidebar-nav a'));
      var currentIndex = allLinks.indexOf(activeItem);
      var newIndex = currentIndex;
      
      if (e.key === 'ArrowUp' && currentIndex > 0) {
        newIndex = currentIndex - 1;
        e.preventDefault();
      } else if (e.key === 'ArrowDown' && currentIndex < allLinks.length - 1) {
        newIndex = currentIndex + 1;
        e.preventDefault();
      }
      
      if (newIndex !== currentIndex) {
        allLinks[currentIndex].classList.remove('is-active');
        allLinks[newIndex].classList.add('is-active');
        allLinks[newIndex].focus();
        scrollActiveIntoView();
      }
    });
  }
  
  // Auto-initialize
  init();
  
  // Export for manual use
  window.SidebarScroll = {
    scrollActiveIntoView: scrollActiveIntoView,
    smoothScrollTo: smoothScrollTo
  };
})();