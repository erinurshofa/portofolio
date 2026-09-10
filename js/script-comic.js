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
     6. COMIC PROJECTS SCREENSHOTS DATASET & LIGHTBOX SLIDER
     ========================================================================== */
  const bimtekEsankemScreenshots = [
    ...Array.from({ length: 4 }, (_, index) => ({
      src: `images/projects/bimtek-esankem-2026-06-04-${String(index + 1).padStart(2, '0')}.webp`,
      label: `Sesi 04 Juni 2026 · Dokumentasi ${index + 1}`,
    })),
    ...Array.from({ length: 10 }, (_, index) => ({
      src: `images/projects/bimtek-esankem-2026-06-11-${String(index + 1).padStart(2, '0')}.webp`,
      label: `Sesi 11 Juni 2026 · Dokumentasi ${index + 1}`,
    })),
    ...Array.from({ length: 6 }, (_, index) => ({
      src: `images/projects/bimtek-esankem-2026-06-19-${String(index + 1).padStart(2, '0')}.webp`,
      label: `Sesi 19 Juni 2026 · Dokumentasi ${index + 1}`,
    })),
  ];

  const comicProjectsData = {
    'point-of-sales': {
      category: '🏪 Retail & Grosir',
      title: 'Point of Sales (POS) & Manajemen Stok Multi-Harga',
      desc: 'Dirancang untuk toko retail, teknik, dan distributor dengan perputaran ribuan SKU. Mengatasi kebocoran kasir, kontrol piutang pelanggan dengan alert jatuh tempo, serta perhitungan margin otomatis.',
      screenshots: [
        { src: 'images/projects/point-of-sales-dashboard.png', label: 'Dashboard Utama & Omset' },
        { src: 'images/projects/point-of-sales-penjualan.png', label: 'Layar Transaksi Kasir Cepat' },
        { src: 'images/projects/point-of-sales-penjualan-berhasil.JPG', label: 'Struk & Pembayaran Berhasil' },
        { src: 'images/projects/point-of-sales-piutang.png', label: 'Buku Kartu Piutang Pelanggan' },
        { src: 'images/projects/point-of-sales-hutang.png', label: 'Buku Kontrol Hutang Supplier' },
        { src: 'images/projects/testimoni-pos-client.webp', label: 'Testimoni WhatsApp · Klien POS' },
      ],
    },
    'antrian-dinsos': {
      category: '🏛️ Layanan Publik',
      title: 'Sistem Antrian Terpadu & TV Display Suara Dinsos Semarang',
      desc: 'Mengurai kepadatan ratusan pemohon bansos tiap pagi di Dinas Sosial Kota Semarang. Menggabungkan kios layar sentuh, mesin tiket thermal, panggilan suara otomatis bahasa Indonesia, serta dashboard satpam dan petugas loket.',
      screenshots: [
        { src: 'images/projects/antrian-dinsos-display-clean.webp', label: 'Display TV Ruang Tunggu Antrean' },
        { src: 'images/projects/antrian-dinsos-panel-satpam-clean.webp', label: 'Panel Kontrol Satpam & Registrasi' },
        { src: 'images/projects/antrian-dinsos-panel-petugas-clean.webp', label: 'Panel Pemanggilan Loket Petugas' },
        { src: 'images/projects/antrian-dinsos-riwayat-layanan-clean.webp', label: 'Riwayat Layanan & Durasi Warga' },
        { src: 'images/projects/antrian-dinsos-display-lama-clean.webp', label: 'Tampilan Display Informasi' },
        { src: 'images/projects/testimoni-antrian-dinsos-pak-zefly.webp', label: 'Testimoni WhatsApp · Pak Zefly (Dinsos)' },
      ],
    },
    'catet-ai': {
      category: '🤖 AI & Otomasi',
      title: 'Catet AI: Catat Transaksi Cukup Kirim Pesan Suara',
      desc: 'Solusi bagi pengusaha yang malas mengetik form panjang. Cukup kirim voice note di Telegram: "Beli bensin 50rb dan beli semen 2 sak 120rb", AI otomatis mengekstrak pos anggaran, nominal, dan tanggal ke database.',
      screenshots: [
        { src: 'images/projects/catet-ai-dashboard-clean.webp', label: 'Dashboard Rekap Keuangan AI' },
        { src: 'images/projects/catet-ai-landing-clean.webp', label: 'Beranda Aplikasi & Fitur Unggulan' },
        { src: 'images/projects/catet-ai-transactions-clean.webp', label: 'Riwayat & Detail Transaksi' },
        { src: 'images/projects/catet-ai-reports-clean.webp', label: 'Analisis Grafik & Laporan Laba Rugi' },
        { src: 'images/projects/catet-ai-telegram-pengenalan.webp', label: 'Bot Telegram · Panduan & Perintah' },
        { src: 'images/projects/catet-ai-telegram-transaksi-teks.webp', label: 'Bot Telegram · Pencatatan via Teks' },
        { src: 'images/projects/catet-ai-telegram-pesan-suara.webp', label: 'Bot Telegram · Ekstraksi Pesan Suara AI' },
      ],
    },
    'bimtek-esankem': {
      category: '🏛️ Instansi & Bansos',
      title: 'E-Sankem & Dokumentasi Bimtek 16 Kecamatan Se-Semarang',
      desc: 'Portal digitalisasi santunan kematian dan verifikasi keluarga rentan (P3KE). Bukan cuma bikin software, saya mendampingi bimbingan teknis tatap muka langsung bagi staf kelurahan dan kecamatan agar adopsi sistem berjalan mulus.',
      screenshots: [
        { src: 'images/projects/bimtek-esankem-2026-06-04-01.webp', label: 'Bimtek Lapangan Sesi 1' },
        ...bimtekEsankemScreenshots,
        { src: 'images/projects/e-sankem-dinsos.jpg', label: 'Tampilan Sistem E-Sankem Dinsos' },
      ],
    },
    'manajemen-rt': {
      category: '🏘️ Komunitas & Warga',
      title: 'Aplikasi Manajemen RT Gasem Raya (PWA & Android APK)',
      desc: 'Digitalisasi pencatatan kas iuran bulanan, arsip surat pengantar digital, dan broadcast informasi penting bagi warga perumahan, bisa diinstall langsung di layar HP warga seperti aplikasi bawaan.',
      screenshots: [
        { src: 'images/projects/manajemen-rt-gasem-raya-beranda.webp', label: 'Beranda & Portal Administrasi Warga' },
        { src: 'images/projects/manajemen-rt-gasem-raya-fitur.webp', label: 'Fitur Iuran, Buku Kas & Surat Digital' },
        { src: 'images/projects/manajemen-rt-gasem-raya-pwa-apk.webp', label: 'Instalasi PWA & Android APK' },
      ],
    },
  };

  const getThumbSrc = (src) => {
    const fileName = src.split('/').pop() || '';
    const fileBase = fileName.replace(/\.[^/.]+$/, '');
    return `images/projects/thumbs/${fileBase}.webp`;
  };

  const setupModal = () => {
    const modal = document.getElementById('comicModal');
    const modalClose = document.getElementById('modalCloseBtn');
    const modalImg = document.getElementById('modalImg');
    const modalCaption = document.getElementById('modalCaption');
    const modalCategory = document.getElementById('modalCategory');
    const modalCounter = document.getElementById('modalCounter');
    const modalTitle = document.getElementById('modalTitle');
    const modalDesc = document.getElementById('modalDesc');
    const modalPrevBtn = document.getElementById('modalPrevBtn');
    const modalNextBtn = document.getElementById('modalNextBtn');
    const modalThumbs = document.getElementById('modalThumbs');
    const slideViewport = document.getElementById('modalSlideViewport');

    if (!modal || !modalClose || !modalImg) return;

    let currentProjectKey = 'point-of-sales';
    let currentSlideIdx = 0;
    let touchStartX = null;

    const updateSlideView = (index) => {
      const project = comicProjectsData[currentProjectKey];
      if (!project || !project.screenshots || !project.screenshots.length) return;

      const total = project.screenshots.length;
      currentSlideIdx = (index + total) % total;
      const currentScreenshot = project.screenshots[currentSlideIdx];

      // Switching animation
      modalImg.classList.add('slide-switching');
      setTimeout(() => {
        modalImg.src = currentScreenshot.src;
        modalImg.alt = `${project.title} — ${currentScreenshot.label}`;
        modalImg.classList.remove('slide-switching');
      }, 80);

      if (modalCaption) {
        modalCaption.textContent = currentScreenshot.label;
      }
      if (modalCategory) {
        modalCategory.textContent = project.category;
      }
      if (modalCounter) {
        modalCounter.textContent = `${currentSlideIdx + 1} / ${total}`;
      }
      if (modalTitle) {
        modalTitle.textContent = project.title;
      }
      if (modalDesc) {
        modalDesc.textContent = project.desc;
      }

      // Prev / Next button state
      if (modalPrevBtn) modalPrevBtn.disabled = total <= 1;
      if (modalNextBtn) modalNextBtn.disabled = total <= 1;

      // Update active thumbnail
      if (modalThumbs) {
        const thumbBtns = modalThumbs.querySelectorAll('.comic-thumb-btn');
        thumbBtns.forEach((tb, i) => {
          if (i === currentSlideIdx) {
            tb.classList.add('active');
            tb.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
          } else {
            tb.classList.remove('active');
          }
        });
      }
    };

    const renderThumbnails = () => {
      if (!modalThumbs) return;
      const project = comicProjectsData[currentProjectKey];
      if (!project || !project.screenshots) return;

      modalThumbs.innerHTML = '';
      project.screenshots.forEach((sc, idx) => {
        const thumbBtn = document.createElement('button');
        thumbBtn.className = `comic-thumb-btn ${idx === currentSlideIdx ? 'active' : ''}`;
        thumbBtn.type = 'button';
        thumbBtn.setAttribute('aria-label', `Pilih slide ${idx + 1}: ${sc.label}`);

        const thumbImg = document.createElement('img');
        thumbImg.src = getThumbSrc(sc.src);
        thumbImg.alt = sc.label;
        thumbImg.loading = 'lazy';
        // Fallback to original image if thumb not found
        thumbImg.onerror = () => {
          thumbImg.src = sc.src;
        };

        thumbBtn.appendChild(thumbImg);
        thumbBtn.addEventListener('click', () => {
          updateSlideView(idx);
        });

        modalThumbs.appendChild(thumbBtn);
      });
    };

    const openModal = (projectId, startIndex = 0) => {
      if (comicProjectsData[projectId]) {
        currentProjectKey = projectId;
      } else {
        // Fallback to first project
        currentProjectKey = Object.keys(comicProjectsData)[0];
      }

      currentSlideIdx = startIndex;
      renderThumbnails();
      updateSlideView(currentSlideIdx);

      modal.classList.add('active');
      modal.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
      modal.focus();
    };

    const closeModal = () => {
      modal.classList.remove('active');
      modal.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
    };

    const nextSlide = () => updateSlideView(currentSlideIdx + 1);
    const prevSlide = () => updateSlideView(currentSlideIdx - 1);

    // Click handler on card buttons
    document.querySelectorAll('.open-modal-btn').forEach((btn) => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        const projectId = btn.getAttribute('data-project-id');
        openModal(projectId || 'point-of-sales', 0);
      });
    });

    // Also allow clicking on project card images to open the gallery!
    document.querySelectorAll('.work-comic-card').forEach((card) => {
      const media = card.querySelector('.work-card-media');
      const btn = card.querySelector('.open-modal-btn');
      if (media && btn) {
        media.style.cursor = 'pointer';
        media.setAttribute('title', 'Klik untuk melihat galeri screenshot');
        media.addEventListener('click', () => {
          const projectId = btn.getAttribute('data-project-id');
          openModal(projectId || 'point-of-sales', 0);
        });
      }
    });

    // Navigation buttons
    if (modalNextBtn) modalNextBtn.addEventListener('click', nextSlide);
    if (modalPrevBtn) modalPrevBtn.addEventListener('click', prevSlide);
    modalClose.addEventListener('click', closeModal);

    // Backdrop click
    modal.addEventListener('click', (e) => {
      if (e.target === modal) closeModal();
    });

    // Keyboard navigation (Esc, ArrowLeft, ArrowRight)
    document.addEventListener('keydown', (e) => {
      if (!modal.classList.contains('active')) return;

      if (e.key === 'Escape') {
        closeModal();
      } else if (e.key === 'ArrowRight') {
        e.preventDefault();
        nextSlide();
      } else if (e.key === 'ArrowLeft') {
        e.preventDefault();
        prevSlide();
      }
    });

    // Touch Swipe Gesture for mobile
    if (slideViewport) {
      slideViewport.addEventListener(
        'touchstart',
        (e) => {
          touchStartX = e.touches[0].clientX;
        },
        { passive: true }
      );

      slideViewport.addEventListener(
        'touchend',
        (e) => {
          if (touchStartX === null) return;
          const diffX = e.changedTouches[0].clientX - touchStartX;
          if (diffX > 45) {
            // Swipe right -> Previous slide
            prevSlide();
          } else if (diffX < -45) {
            // Swipe left -> Next slide
            nextSlide();
          }
          touchStartX = null;
        },
        { passive: true }
      );
    }
  };

  /* ==========================================================================
     MOBILE NAVIGATION DRAWER CONTROLLER
     ========================================================================== */
  const setupMobileMenu = () => {
    const menuBtn = document.getElementById('comicMobileMenuBtn');
    const navLinks = document.getElementById('comicNavLinks');
    if (!menuBtn || !navLinks) return;

    const iconHamburger = menuBtn.querySelector('.icon-hamburger');
    const iconClose = menuBtn.querySelector('.icon-close');
    const toggleText = menuBtn.querySelector('.mobile-toggle-text');

    const toggleMenu = (forceState) => {
      const isCurrentlyOpen = navLinks.classList.contains('is-open');
      const shouldOpen = forceState !== undefined ? forceState : !isCurrentlyOpen;

      navLinks.classList.toggle('is-open', shouldOpen);
      menuBtn.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
      menuBtn.setAttribute('aria-label', shouldOpen ? 'Tutup Menu Navigasi' : 'Buka Menu Navigasi');

      if (iconHamburger) iconHamburger.style.display = shouldOpen ? 'none' : 'block';
      if (iconClose) iconClose.style.display = shouldOpen ? 'block' : 'none';
      if (toggleText) toggleText.textContent = shouldOpen ? 'Tutup' : 'Menu';
    };

    menuBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      toggleMenu();
    });

    // Close mobile menu when any nav link is tapped
    navLinks.querySelectorAll('a').forEach((link) => {
      link.addEventListener('click', () => {
        toggleMenu(false);
      });
    });

    // Close when tapping outside the menu
    document.addEventListener('click', (e) => {
      if (navLinks.classList.contains('is-open') && !navLinks.contains(e.target) && !menuBtn.contains(e.target)) {
        toggleMenu(false);
      }
    });

    // Close on Escape key
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && navLinks.classList.contains('is-open')) {
        toggleMenu(false);
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
    setupMobileMenu();
  });
})();

