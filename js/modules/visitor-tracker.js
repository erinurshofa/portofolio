/**
 * Visitor & Engagement Tracker
 * Non-blocking, privacy-friendly analytics for Eri Nur Sofa Portfolio
 */

(function () {
  'use strict';

  function getSessionId() {
    let sid = null;
    try {
      sid = sessionStorage.getItem('ens_visitor_sid');
      if (!sid) {
        sid = 'v_' + Math.random().toString(36).substring(2, 10) + '_' + Date.now().toString(36);
        sessionStorage.setItem('ens_visitor_sid', sid);
      }
    } catch (e) {
      sid = 'v_anon_' + Date.now();
    }
    return sid;
  }

  function getPageName() {
    const path = window.location.pathname;
    const page = path.substring(path.lastIndexOf('/') + 1);
    return page || 'index.html';
  }

  function ping(interaction = 'Page Visit') {
    const payload = {
      session_id: getSessionId(),
      page: getPageName(),
      referrer: document.referrer || 'Direct / None',
      interaction: interaction
    };

    try {
      if (navigator.sendBeacon) {
        const blob = new Blob([JSON.stringify(payload)], { type: 'application/json' });
        navigator.sendBeacon('api/track.php', blob);
      } else {
        fetch('api/track.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        }).catch(() => {});
      }
    } catch (e) {
      // Fail silently without interrupting user experience
    }
  }

  // Initial page load ping
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => ping('Page Visit'));
  } else {
    ping('Page Visit');
  }

  // Track breakdown button triggers
  document.addEventListener('click', function (e) {
    const trigger = e.target.closest('[data-breakdown-trigger], .btn-breakdown-open, #btnOpenBreakdown, .hero-cta-btn, .comic-action-btn');
    if (trigger) {
      ping('Clicked Breakdown CTA');
    }
  });

  // Track deep scroll once
  let scrollTracked = false;
  window.addEventListener('scroll', function () {
    if (scrollTracked) return;
    const scrollPos = window.scrollY + window.innerHeight;
    const docHeight = document.documentElement.scrollHeight;
    if (docHeight > 0 && scrollPos / docHeight > 0.65) {
      scrollTracked = true;
      ping('Deep Scroll (65%+)');
    }
  }, { passive: true });

  window.EnsTracker = {
    ping: ping,
    getSessionId: getSessionId
  };
})();
