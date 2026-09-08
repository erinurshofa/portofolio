/**
 * SCRIPT-COMIC.JS — INTERACTIVE & HIGH-PERFORMANCE RELEVANT MOTION
 * Edisi Komik Editorial: Si Mager × Si Waras #LebihWarasPakaiSistem
 * Eri Nur Sofa — Software Engineer Semarang
 */

(function () {
  'use strict';

  // Check user preference for reduced motion
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* ==========================================================================
     1. HIGH-PERFORMANCE SCROLL REVEAL (IntersectionObserver + GPU transforms)
     ========================================================================== */
  const setupScrollReveal = () => {
    if (prefersReducedMotion) {
      document.querySelectorAll('.scroll-reveal').forEach((el) => {
        el.style.opacity = '1';
        el.style.transform = 'none';
      });
      return;
    }

    const revealElements = document.querySelectorAll('.scroll-reveal');
    if (!revealElements.length) return;

    const observer = new IntersectionObserver(
      (entries, obs) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add('is-visible');
            obs.unobserve(entry.target);
          }
        });
      },
      {
        root: null,
        threshold: 0.12,
        rootMargin: '0px 0px -40px 0px',
      }
    );

    revealElements.forEach((el) => observer.observe(el));
  };

  /* ==========================================================================
     2. HIGHLIGHTER BRUSH SWEEP REVEAL (Triggered when scrolled into view)
     ========================================================================== */
  const setupHighlighterMotion = () => {
    if (prefersReducedMotion) return;

    const highlighters = document.querySelectorAll('.highlighter');
    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add('highlight-drawn');
          }
        });
      },
      { threshold: 0.5 }
    );

    highlighters.forEach((h) => observer.observe(h));
  };

  /* ==========================================================================
     3. 3D TILT PHYSICS INTERACTION (requestAnimationFrame, transform-only)
     ========================================================================== */
  const setupCardTilt = () => {
    if (prefersReducedMotion) return;

    const tiltCards = document.querySelectorAll('.tilt-card');
    tiltCards.forEach((card) => {
      let rafId = null;
      let targetRotateX = 0;
      let targetRotateY = 0;
      let currentRotateX = 0;
      let currentRotateY = 0;

      const updateMotion = () => {
        // Smooth interpolation (lerp)
        currentRotateX += (targetRotateX - currentRotateX) * 0.12;
        currentRotateY += (targetRotateY - currentRotateY) * 0.12;

        card.style.transform = `perspective(900px) rotateX(${currentRotateX.toFixed(2)}deg) rotateY(${currentRotateY.toFixed(2)}deg)`;

        if (
          Math.abs(targetRotateX - currentRotateX) > 0.02 ||
          Math.abs(targetRotateY - currentRotateY) > 0.02
        ) {
          rafId = requestAnimationFrame(updateMotion);
        } else {
          rafId = null;
        }
      };

      card.addEventListener(
        'mousemove',
        (e) => {
          const rect = card.getBoundingClientRect();
          const x = e.clientX - rect.left;
          const y = e.clientY - rect.top;
          const centerX = rect.width / 2;
          const centerY = rect.height / 2;

          // Max 7 degrees tilt for professional feel
          targetRotateX = -((y - centerY) / centerY) * 6;
          targetRotateY = ((x - centerX) / centerX) * 6;

          if (!rafId) {
            rafId = requestAnimationFrame(updateMotion);
          }
        },
        { passive: true }
      );

      card.addEventListener('mouseleave', () => {
        targetRotateX = 0;
        targetRotateY = 0;
        if (!rafId) {
          rafId = requestAnimationFrame(updateMotion);
        }
      });
    });
  };

  /* ==========================================================================
     4. KALKULATOR "WARAS" (Hitung Waktu Rekap Manual & Ticker Counter)
     ========================================================================== */
  const setupCalculator = () => {
    const dailySlider = document.getElementById('dailyHoursSlider');
    const workDaysSlider = document.getElementById('workDaysSlider');
    const monthEndSlider = document.getElementById('monthEndSlider');

    const dailyLabel = document.getElementById('dailyHoursVal');
    const workDaysLabel = document.getElementById('workDaysVal');
    const monthEndLabel = document.getElementById('monthEndVal');
    const totalDisplay = document.getElementById('totalHoursDisplay');
    const quoteText = document.getElementById('calcQuoteText');

    if (!dailySlider || !workDaysSlider || !monthEndSlider || !totalDisplay) return;

    let currentDisplayValue = 49;
    let animFrame = null;

    const animateNumber = (targetVal) => {
      if (prefersReducedMotion) {
        totalDisplay.textContent = Math.round(targetVal);
        return;
      }

      if (animFrame) cancelAnimationFrame(animFrame);

      const startVal = currentDisplayValue;
      const startTime = performance.now();
      const duration = 280; // ms

      const step = (now) => {
        const progress = Math.min((now - startTime) / duration, 1);
        // easeOutQuad
        const ease = 1 - (1 - progress) * (1 - progress);
        const val = Math.round(startVal + (targetVal - startVal) * ease);
        totalDisplay.textContent = val;
        currentDisplayValue = val;

        if (progress < 1) {
          animFrame = requestAnimationFrame(step);
        }
      };

      animFrame = requestAnimationFrame(step);
    };

    const calculate = () => {
      const daily = parseFloat(dailySlider.value);
      const days = parseInt(workDaysSlider.value, 10);
      const monthEnd = parseInt(monthEndSlider.value, 10);

      dailyLabel.textContent = `${daily} Jam / hari`;
      workDaysLabel.textContent = `${days} Hari`;
      monthEndLabel.textContent = `${monthEnd} Jam`;

      const totalMonthlyHours = Math.round(daily * days + monthEnd);
      animateNumber(totalMonthlyHours);

      // Dynamic quote based on hours
      const workDaysLost = (totalMonthlyHours / 8).toFixed(1);
      if (totalMonthlyHours < 35) {
        quoteText.innerHTML = `Setara dengan <strong>${workDaysLost} hari kerja penuh</strong> hanya untuk catat-mencatat. Beralih ke sistem bisa memotong 80% waktu ini!`;
      } else if (totalMonthlyHours <= 60) {
        quoteText.innerHTML = `Setara dengan <strong>${workDaysLost} hari kerja</strong> terbuang cuma urus nota dan Excel. Waktu owner harusnya untuk ekspansi dan cari omset!`;
      } else {
        quoteText.innerHTML = `⚠️ Bahaya: <strong>${workDaysLost} hari kerja / bulan</strong> amblas buat urusan kertas! Rentan salah hitung kas dan stok bocor tanpa sadar.`;
      }
    };

    [dailySlider, workDaysSlider, monthEndSlider].forEach((slider) => {
      slider.addEventListener('input', calculate);
    });

    calculate();
  };

  /* ==========================================================================
     5. CATEGORY FILTER (With GPU Spring Stagger Animation)
     ========================================================================== */
  const setupWorkFilter = () => {
    const filterButtons = document.querySelectorAll('.filter-btn');
    const workCards = document.querySelectorAll('.work-comic-card');

    if (!filterButtons.length || !workCards.length) return;

    filterButtons.forEach((btn) => {
      btn.addEventListener('click', () => {
        filterButtons.forEach((b) => b.classList.remove('active'));
        btn.classList.add('active');

        const category = btn.getAttribute('data-filter');

        workCards.forEach((card, index) => {
          const cardCat = card.getAttribute('data-category');
          const isMatch = category === 'all' || cardCat === category;

          if (isMatch) {
            card.style.display = 'flex';
            if (!prefersReducedMotion) {
              card.style.opacity = '0';
              card.style.transform = 'translateY(16px) scale(0.97)';
              setTimeout(() => {
                card.style.transition = 'opacity 0.3s ease, transform 0.35s cubic-bezier(0.16, 1, 0.3, 1)';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0) scale(1)';
              }, index * 40);
            }
          } else {
            card.style.display = 'none';
          }
        });
      });
    });
  };

  /* ==========================================================================
     6. LIGHTBOX MODAL SCREENSHOT
     ========================================================================== */
  const setupModal = () => {
    const modal = document.getElementById('comicModal');
    const modalClose = document.getElementById('modalCloseBtn');
    const modalImg = document.getElementById('modalImg');
    const modalTitle = document.getElementById('modalTitle');
    const modalDesc = document.getElementById('modalDesc');

    if (!modal || !modalClose) return;

    document.querySelectorAll('.open-modal-btn').forEach((btn) => {
      btn.addEventListener('click', () => {
        const img = btn.getAttribute('data-img');
        const title = btn.getAttribute('data-title');
        const desc = btn.getAttribute('data-desc');

        modalImg.src = img;
        modalTitle.textContent = title;
        modalDesc.textContent = desc;

        modal.classList.add('active');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
      });
    });

    const closeModal = () => {
      modal.classList.remove('active');
      modal.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
    };

    modalClose.addEventListener('click', closeModal);
    modal.addEventListener('click', (e) => {
      if (e.target === modal) closeModal();
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && modal.classList.contains('active')) {
        closeModal();
      }
    });
  };

  /* ==========================================================================
     INITIALIZATION
     ========================================================================== */
  document.addEventListener('DOMContentLoaded', () => {
    setupScrollReveal();
    setupHighlighterMotion();
    setupCardTilt();
    setupCalculator();
    setupWorkFilter();
    setupModal();
  });
})();
