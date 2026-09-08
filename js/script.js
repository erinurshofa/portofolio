/**
 * ERI NUR SOFA — HIGH PERFORMANCE JAVASCRIPT
 * Features: Native Scroll Observer, Active Nav Spy, Scroll Progress Bar,
 *           Lightbox Dialog with Touch/Keyboard navigation & Zoom.
 */

// 1. GALLERY DATA (100% UNCHANGED CONTENT & SCREENSHOT MAPPINGS)
const bimtekEsankemScreenshots = [
  ...Array.from({ length: 4 }, (_, index) => ({
    src: `images/projects/bimtek-esankem-2026-06-04-${String(index + 1).padStart(2, "0")}.webp`,
    label: `Sesi 04 Juni 2026 · Dokumentasi ${index + 1}`,
  })),
  ...Array.from({ length: 10 }, (_, index) => ({
    src: `images/projects/bimtek-esankem-2026-06-11-${String(index + 1).padStart(2, "0")}.webp`,
    label: `Sesi 11 Juni 2026 · Dokumentasi ${index + 1}`,
  })),
  ...Array.from({ length: 6 }, (_, index) => ({
    src: `images/projects/bimtek-esankem-2026-06-19-${String(index + 1).padStart(2, "0")}.webp`,
    label: `Sesi 19 Juni 2026 · Dokumentasi ${index + 1}`,
  })),
];

const galleryProjects = [
  {
    title: "Catet.ai — Finance AI Assistant",
    kind: "Asisten keuangan AI",
    screenshots: [
      { src: "images/projects/catet-ai-landing-clean.webp", label: "Beranda & fitur AI" },
      { src: "images/projects/catet-ai-dashboard-clean.webp", label: "Dashboard keuangan" },
      { src: "images/projects/catet-ai-transactions-clean.webp", label: "Riwayat transaksi" },
      { src: "images/projects/catet-ai-reports-clean.webp", label: "Laporan & analisis" },
      { src: "images/projects/catet-ai-telegram-pengenalan.webp", label: "Bot Telegram · Pengenalan & perintah" },
      { src: "images/projects/catet-ai-telegram-transaksi-teks.webp", label: "Bot Telegram · Pencatatan via teks" },
      { src: "images/projects/catet-ai-telegram-pesan-suara.webp", label: "Bot Telegram · Pencatatan via pesan suara" },
    ],
  },
  {
    title: "Toko Online",
    kind: "E-commerce",
    screenshots: [
      { src: "images/projects/toko-online-beranda.jpg", label: "Beranda" },
      { src: "images/projects/toko-online.jpg", label: "Halaman toko" },
    ],
  },
  {
    title: "Website Dinas Koperasi",
    kind: "Website instansi",
    screenshots: [
      { src: "images/projects/website-dinas-koperasi.jpg", label: "Halaman utama" },
    ],
  },
  {
    title: "Website Dinsos",
    kind: "Website instansi",
    screenshots: [
      { src: "images/projects/website-dinsos.png", label: "Halaman utama" }
    ],
  },
  {
    title: "Website DPMPTSP",
    kind: "Website instansi",
    screenshots: [
      { src: "images/projects/website-dpmptsp.jpg", label: "Halaman utama" }
    ],
  },
  {
    title: "Antrian Dinsos",
    kind: "Sistem antrian",
    screenshots: [
      { src: "images/projects/antrian-dinsos-display-clean.webp", label: "Display antrean" },
      { src: "images/projects/antrian-dinsos-panel-satpam-clean.webp", label: "Panel satpam" },
      { src: "images/projects/antrian-dinsos-panel-petugas-clean.webp", label: "Panel petugas" },
      { src: "images/projects/antrian-dinsos-riwayat-layanan-clean.webp", label: "Riwayat layanan" },
      { src: "images/projects/antrian-dinsos-display-lama-clean.webp", label: "Tampilan sistem" },
      { src: "images/projects/testimoni-antrian-dinsos-pak-zefly.webp", label: "Testimoni WhatsApp · Pak Zefly (Dinas Sosial)" },
    ],
  },
  {
    title: "E-Sankem Dinsos",
    kind: "Layanan publik",
    screenshots: [
      { src: "images/projects/e-sankem-dinsos.jpg", label: "Tampilan sistem" }
    ],
  },
  {
    title: "Bimtek E-Sankem Seluruh Kecamatan",
    kind: "Pelatihan & pendampingan",
    screenshots: bimtekEsankemScreenshots,
  },
  {
    title: "P3KE Kemiskinan Ekstrem",
    kind: "Dashboard data",
    screenshots: [
      { src: "images/projects/p3ke-kemiskinan-ekstrem.jpg", label: "Ringkasan" },
      { src: "images/projects/p3ke-kemiskinan-ekstrem-detail.jpg", label: "Detail" },
      { src: "images/projects/p3ke-kemiskinan-ekstrem-rekap-kebutuhan.jpg", label: "Rekap kebutuhan" },
    ],
  },
  {
    title: "Point of Sales",
    kind: "POS & inventory",
    screenshots: [
      { src: "images/projects/point-of-sales-penjualan-berhasil.JPG", label: "Penjualan berhasil" },
      { src: "images/projects/point-of-sales-dashboard.png", label: "Dashboard" },
      { src: "images/projects/point-of-sales-hutang.png", label: "Hutang" },
      { src: "images/projects/point-of-sales-penjualan.png", label: "Penjualan" },
      { src: "images/projects/point-of-sales-piutang.png", label: "Piutang" },
      { src: "images/projects/testimoni-pos-client.webp", label: "Testimoni WhatsApp · Klien POS" },
    ],
  },
  {
    title: "Rumah Inspirasi",
    kind: "Layanan disabilitas",
    screenshots: [
      { src: "images/projects/rumah-inspirasi.jpg", label: "Halaman utama" },
      { src: "images/projects/rumah-inspirasi-login.jpg", label: "Halaman login" },
    ],
  },
  {
    title: "Semar Preneur Up UMKM Dinas Koperasi",
    kind: "Program UMKM",
    screenshots: [
      { src: "images/projects/semar-preneur-up-umkm-dinas-koperasi.png", label: "Tampilan program" },
    ],
  },
  {
    title: "Manajemen RT 02 Gasem Raya",
    kind: "Sistem Administrasi RT & Kas",
    screenshots: [
      { src: "images/projects/manajemen-rt-gasem-raya-beranda.webp", label: "Beranda & portal administrasi" },
      { src: "images/projects/manajemen-rt-gasem-raya-fitur.webp", label: "Fitur data warga, kas & surat" },
      { src: "images/projects/manajemen-rt-gasem-raya-pwa-apk.webp", label: "Dukungan instalasi PWA & Android APK" },
    ],
  },
];

// Helper to get thumbnail image path
function getThumbSrc(src) {
  const fileName = src.split("/").pop() ?? "";
  const fileBase = fileName.replace(/\.[^/.]+$/, "");
  return `images/projects/thumbs/${fileBase}.webp`;
}

// 2. LIGHTBOX STATE & DOM ELEMENTS
let activeProjectIndex = null;
let activeScreenshotIndex = 0;
let currentZoom = 1;
let touchStartX = null;

const modal = document.getElementById("galleryModal");
const modalKind = document.getElementById("modalKind");
const modalTitle = document.getElementById("modalTitle");
const modalCounter = document.getElementById("modalCounter");
const modalImg = document.getElementById("modalImg");
const prevBtn = document.getElementById("modalPrevBtn");
const nextBtn = document.getElementById("modalNextBtn");
const closeBtn = document.getElementById("modalCloseBtn");
const zoomInBtn = document.getElementById("zoomInBtn");
const zoomOutBtn = document.getElementById("zoomOutBtn");
const zoomLevelText = document.getElementById("zoomLevelText");
const thumbsContainer = document.getElementById("modalThumbs");
const galleryGrid = document.getElementById("galleryGrid");
const progressBar = document.getElementById("scrollProgressBar");
const backToTopBtn = document.getElementById("backToTopBtn");

// 2. BILINGUAL DICTIONARY (INDONESIAN & ENGLISH)
const i18nTranslations = {
  id: {
    "site.title": "Eri Nur Sofa — Portfolio | Software Engineer Semarang",
    "site.description": "Portfolio Eri Nur Sofa — Software Engineer di Semarang. Membantu organisasi, instansi, dan pelaku usaha membangun sistem informasi, website publik, dashboard monitoring, dan POS.",
    "nav.about": "Tentang",
    "nav.how": "Cara kerja",
    "nav.works": "Karya",
    "nav.testimonials": "Testimoni",
    "nav.contact": "Kontak",
    "nav.wa_full": "WA: 0856-4128-0960",
    "nav.wa_short": "WA",
    "hero.subtitle": "Software engineer · Semarang, Indonesia",
    "hero.title": "Memahami proses sebelum membangun sistem.",
    "hero.desc": "Saya <strong>Eri Nur Sofa</strong>. Saya membantu organisasi, instansi, dan pelaku usaha menerjemahkan proses kerja menjadi solusi digital yang sederhana, mudah digunakan, dan benar-benar bermanfaat.",
    "hero.btn_wa": "Konsultasi WhatsApp: 085641280960",
    "hero.btn_works": "Pilihan Karya",
    "hero.card_tag": "Prinsip kerja",
    "hero.card_quote": "Teknologi adalah alat. Manusia dan manfaat adalah tujuan.",
    "hero.card_footer_1": "Masalah",
    "hero.card_footer_2": "Proses",
    "hero.card_footer_3": "Manfaat",
    "hero.bottom_bar": "Discovery · Perancangan · Pengembangan · Evaluasi",
    "about.eyebrow": "Cara pandang",
    "about.title": "Nilai sistem ada pada manfaatnya.",
    "about.lead": "Saya tidak memulai proyek dari framework atau daftar fitur. Saya memulai dengan mendengarkan pengguna, memahami alur kerja, dan mencari titik yang benar-benar menghambat pekerjaan.",
    "about.d1": "Discovery kebutuhan",
    "about.d2": "Pemetaan proses bisnis",
    "about.d3": "Sistem informasi custom",
    "about.d4": "Dashboard & monitoring",
    "about.d5": "POS, inventory & integrasi data",
    "about.d6": "Dokumentasi & pendampingan",
    "works.eyebrow": "Pilihan karya",
    "works.title": "Sistem untuk dipakai.",
    "works.desc": "Portofolio ini menempatkan masalah, keputusan, dan dampak sebagai cerita utama—bukan sekadar daftar teknologi.",
    "works.card1_tag": "Live project · Catet.ai",
    "works.card1_desc": "Asisten keuangan AI untuk mencatat transaksi melalui teks, suara, atau foto struk—tersedia melalui web dan Telegram.",
    "works.card2_tag": "Digitalisasi layanan publik",
    "works.card2_desc": "Sistem Santunan Kematian untuk Dinas Sosial—mengganti perpindahan berkas manual dengan alur digital, pemantauan status, dan dashboard durasi layanan. Implementasi juga didampingi bimbingan teknis untuk seluruh kecamatan di Kota Semarang.",
    "works.card2_stat1_label": "Masa awal",
    "works.card2_stat1_unit": "hari",
    "works.card2_stat2_label": "Setelah adaptasi",
    "works.card2_stat2_unit": "hari",
    "works.card3_tag": "03 / Pengalaman institusional",
    "works.card3_title": "Website & sistem informasi publik",
    "works.card3_desc": "Pengalaman digitalisasi bersama Dinas Sosial Kota Semarang, DPMPTSP, dan Dinas Koperasi dengan perhatian pada informasi yang jelas serta proses yang mudah dijalankan.",
    "works.card4_tag": "04 / Sistem operasional",
    "works.card4_title": "Data, monitoring, POS & inventory",
    "works.card4_desc": "Ruang kerja yang berangkat dari proses sehari-hari: antrean, pendataan, dashboard monitoring, transaksi, stok, dan integrasi data.",
    "official.eyebrow": "Tautan proyek resmi",
    "official.title": "Kunjungi sistem yang telah dipublikasikan.",
    "official.desc": "Sebagian sistem menggunakan pembatasan akses. Bukti visualnya tersedia pada dokumentasi proyek di bawah.",
    "official.open_site": "Buka situs",
    "official.alt_access": "Akses alternatif",
    "official.p1_tag": "Website instansi",
    "official.p1_desc": "Website informasi Dinas Koperasi dan UMKM Kota Semarang.",
    "official.p2_tag": "Platform layanan sosial",
    "official.p2_desc": "Sistem layanan sosial yang mendukung proses dan informasi Dinas Sosial.",
    "official.p3_tag": "Sistem layanan sosial",
    "official.p3_desc": "Portal Sistem Informasi Pelayanan dan Perlindungan Sosial Kota Semarang untuk akses layanan sosial utama.",
    "official.p4_tag": "Layanan santunan kematian",
    "official.p4_desc": "Portal pengajuan dan pemantauan Santunan Kematian secara digital.",
    "official.p5_tag": "Layanan disabilitas",
    "official.p5_desc": "Portal Rumah Inspirasi Penyandang Disabilitas.",
    "official.p6_tag": "Layanan perizinan",
    "official.p6_desc": "Portal layanan perizinan dengan jalur akses utama dan alternatif.",
    "official.p7_tag": "Website instansi",
    "official.p7_desc": "Website informasi Dinas Sosial Kota Semarang.",
    "official.p8_tag": "Program UMKM",
    "official.p8_desc": "Halaman program Semar Preneur Up milik Dinas Koperasi dan UMKM Kota Semarang.",
    "official.p9_tag": "Asisten keuangan AI",
    "official.p9_desc": "Asisten keuangan berbasis AI untuk pencatatan transaksi melalui web dan Telegram.",
    "official.p10_tag": "Keterbukaan informasi",
    "official.p10_desc": "Portal Pejabat Pengelola Informasi dan Dokumentasi (PPID) DPMPTSP Kota Semarang.",
    "official.p11_tag": "Sistem administrasi warga",
    "official.p11_desc": "Aplikasi Sistem Informasi RT: data warga, kartu keluarga, pembukuan kas keuangan RT, arsip dokumen, dan dukungan instalasi PWA/APK.",
    "sidaksos.eyebrow": "Dokumentasi koordinasi SIDAKSOS",
    "sidaksos.title": "Bukti publik pengembangan dan penyelarasan sistem.",
    "sidaksos.desc": "Empat berita resmi Diskominfo Kota Semarang yang mendokumentasikan rapat pengembangan, finalisasi, sosialisasi, dan assessment data melalui SIDAKSOS.",
    "sidaksos.open_news": "Buka berita resmi",
    "testimonials.eyebrow": "Testimoni & Rekam Jejak Dampak",
    "testimonials.title": "Kepercayaan dari instansi dan pelaku usaha.",
    "testimonials.desc": "Evaluasi autentik dari mitra pemerintah maupun pemilik usaha terkait kualitas sistem, pendampingan implementasi, dan penyelesaian masalah.",
    "testimonials.verified_badge": "Testimoni Terverifikasi",
    "testimonials.inst_badge": "Instansi Pemerintah",
    "testimonials.biz_badge": "Sektor Bisnis & Retail",
    "testimonials.zefly_role": "Dinas Sosial Kota Semarang · Manajemen Pelayanan Publik",
    "testimonials.zefly_chat_status": "Dinas Sosial Kota Semarang · Mitra",
    "testimonials.zefly_quote": "“Sistem antrian Dinsos sejauh ini sangat membantu OPD terutama untuk Dinsos...”",
    "testimonials.zefly_imp1_title": "Pintu Data Awal",
    "testimonials.zefly_imp1_desc": "Pengumpul data awal di gerbang Dinsos untuk menganalisis jangkauan dan peranan layanan ke masyarakat.",
    "testimonials.zefly_imp2_title": "Tertib & Teratur",
    "testimonials.zefly_imp2_desc": "Menata antrean ruang tunggu agar penanganan warga lebih terstruktur tanpa tumpang tindih.",
    "testimonials.zefly_imp3_title": "Cepat & Transparan",
    "testimonials.zefly_imp3_desc": "Proses administrasi dan pemanggilan loket menjadi jauh lebih mudah, cepat, dan transparan.",
    "testimonials.zefly_imp4_title": "Efisiensi Petugas",
    "testimonials.zefly_imp4_desc": "Petugas loket melayani pemohon dengan lebih nyaman dan waktu pelayanan jauh lebih efisien.",
    "testimonials.pos_role": "Pelaku Usaha Retail & Grosir Alat Teknik · Semarang",
    "testimonials.pos_chat_status": "Pelaku Usaha Retail & Grosir · Mitra",
    "testimonials.pos_quote": "“Aplikasi POS khusus sesuai kebutuhan kami... semua kendala berhasil ditangani dengan baik & profesional, saya sangat puas & akan merekomendasikan...”",
    "testimonials.pos_imp1_title": "100% Sesuai Kebutuhan",
    "testimonials.pos_imp1_desc": "Sistem dirancang khusus mengikuti proses kasir, hutang piutang, dan stok riil toko.",
    "testimonials.pos_imp2_title": "Pendampingan UAT",
    "testimonials.pos_imp2_desc": "Masa uji coba (running test) dan implementasi awal didampingi serta direvisi hingga tuntas.",
    "testimonials.pos_imp3_title": "Solusi Responsif",
    "testimonials.pos_imp3_desc": "Setiap kendala operasional lapangan langsung diberikan solusi teknis yang memuaskan.",
    "testimonials.pos_imp4_title": "Rekomendasi Mitra",
    "testimonials.pos_imp4_desc": "Kepuasan tinggi yang mendorong klien merekomendasikan sistem kepada sesama pemilik usaha.",
    "testimonials.open_ss": "Buka Screenshot WA",
    "testimonials.view_gallery_dinsos": "Lihat Galeri Antrian Dinsos",
    "testimonials.view_gallery_pos": "Lihat Galeri Point of Sales",
    "testimonials.view_ss_card": "Lihat Tangkapan Layar Asli WA",
    "testimonials.view_ss_sub": "Klik untuk membuka screenshot resolusi penuh",
    "gallery.eyebrow": "Dokumentasi proyek",
    "gallery.title": "Bukti visual dari sistem yang telah dibangun.",
    "gallery.desc": "Satu cover ditampilkan untuk setiap proyek. Semua screenshot lain dapat dilihat saat galeri interaktif dibuka.",
    "gallery.view_screens": "Lihat layar",
    "gallery.screens_count": "tampilan",
    "gallery.card_desc": "Klik untuk membuka galeri layar dan melihat detail.",
    "gallery.modal_hint": "Geser atau gunakan tombol panah (← / →)",
    "steps.eyebrow": "Cara kerja",
    "steps.title": "Solusi yang lahir dari pertanyaan yang tepat.",
    "steps.s1_title": "Dengarkan",
    "steps.s1_desc": "Memahami masalah, orang yang terlibat, dan hasil yang ingin dicapai.",
    "steps.s2_title": "Petakan",
    "steps.s2_desc": "Menyusun alur kerja bersama agar semua pihak memiliki pemahaman yang sama.",
    "steps.s3_title": "Bangun",
    "steps.s3_desc": "Merancang dan mengembangkan sistem yang sederhana, realistis, dan mudah dipakai.",
    "steps.s4_title": "Pelajari",
    "steps.s4_desc": "Mengevaluasi manfaat implementasi dengan data ketika kondisi proyek memungkinkan.",
    "contact.eyebrow": "Hubungi Saya",
    "contact.title": "Mari diskusikan kebutuhan sistem Anda.",
    "contact.desc": "Punya ide proyek baru, ingin mendigitalkan proses manual, atau butuh konsultasi teknis seputar sistem informasi dan aplikasi web? Silakan hubungi langsung melalui WhatsApp.",
    "contact.card_label": "WhatsApp Kontak Resmi",
    "contact.card_btn": "Kirim Pesan WhatsApp Sekarang",
    "contact.floating_wa": "Chat WhatsApp",
    "quote.text": "Software yang baik bukan yang paling rumit, melainkan yang benar-benar membantu.",
    "footer.rights": "Eri Nur Sofa · Semarang, Indonesia",
    "footer.tagline": "Dibangun dari masalah nyata, proses yang jelas, dan manfaat."
  },
  en: {
    "site.title": "Eri Nur Sofa — Portfolio | Software Engineer Semarang",
    "site.description": "Portfolio of Eri Nur Sofa — Software Engineer based in Semarang. Helping organizations, government agencies, and businesses build custom information systems, public websites, monitoring dashboards, and POS.",
    "nav.about": "About",
    "nav.how": "How It Works",
    "nav.works": "Projects",
    "nav.testimonials": "Testimonials",
    "nav.contact": "Contact",
    "nav.wa_full": "WA: +62 856-4128-0960",
    "nav.wa_short": "WA",
    "hero.subtitle": "Software engineer · Semarang, Indonesia",
    "hero.title": "Understanding processes before building systems.",
    "hero.desc": "I am <strong>Eri Nur Sofa</strong>. I help organizations, government institutions, and businesses translate complex workflows into simple, user-friendly, and truly valuable digital solutions.",
    "hero.btn_wa": "WhatsApp Consultation: 085641280960",
    "hero.btn_works": "Selected Works",
    "hero.card_tag": "Core Principle",
    "hero.card_quote": "Technology is the tool. People and real value are the goal.",
    "hero.card_footer_1": "Problem",
    "hero.card_footer_2": "Process",
    "hero.card_footer_3": "Impact",
    "hero.bottom_bar": "Discovery · Architecture · Development · Evaluation",
    "about.eyebrow": "Perspective",
    "about.title": "A system's true value lies in its impact.",
    "about.lead": "I don't start projects with frameworks or feature checklists. I begin by listening to users, mapping workflows, and identifying the bottlenecks that genuinely hinder daily operations.",
    "about.d1": "Requirements Discovery",
    "about.d2": "Business Process Mapping",
    "about.d3": "Custom Information Systems",
    "about.d4": "Dashboards & Monitoring",
    "about.d5": "POS, Inventory & Data Integration",
    "about.d6": "Documentation & User Training",
    "works.eyebrow": "Selected Works",
    "works.title": "Systems built to be used.",
    "works.desc": "This portfolio puts problems, architectural decisions, and real-world impact at the center—not just a list of technologies.",
    "works.card1_tag": "Live Project · Catet.ai",
    "works.card1_desc": "AI financial assistant for tracking transactions via text, voice notes, or receipt photos—accessible through Web and Telegram.",
    "works.card2_tag": "Public Service Digitalization",
    "works.card2_desc": "Death Grant Claim System for the Social Services Agency—replacing manual paperwork with digital workflows, status tracking, and duration dashboards, accompanied by technical training across all subdistricts in Semarang.",
    "works.card2_stat1_label": "Initial phase",
    "works.card2_stat1_unit": "days",
    "works.card2_stat2_label": "After adaptation",
    "works.card2_stat2_unit": "days",
    "works.card3_tag": "03 / Institutional Experience",
    "works.card3_title": "Public Websites & Information Systems",
    "works.card3_desc": "Digitalization experience with Semarang Social Services, DPMPTSP Licensing Agency, and Cooperatives & MSMEs Agency, focusing on clarity and ease of execution.",
    "works.card4_tag": "04 / Operational Systems",
    "works.card4_title": "Data, Monitoring, POS & Inventory",
    "works.card4_desc": "Operational solutions rooted in day-to-day work: queuing, citizen data gathering, monitoring dashboards, transactions, and inventory sync.",
    "official.eyebrow": "Official Project Links",
    "official.title": "Explore live and deployed systems.",
    "official.desc": "Certain systems use restricted internal access. Visual documentation is available in the project gallery below.",
    "official.open_site": "Visit site",
    "official.alt_access": "Alternative access",
    "official.p1_tag": "Government Website",
    "official.p1_desc": "Information portal for Cooperatives & MSMEs Agency of Semarang City.",
    "official.p2_tag": "Social Service Platform",
    "official.p2_desc": "Social welfare system supporting data workflows and service delivery for Social Services.",
    "official.p3_tag": "Social Service Platform",
    "official.p3_desc": "Central information portal for Social Assistance and Protection in Semarang City.",
    "official.p4_tag": "Death Grant Service",
    "official.p4_desc": "Digital portal for submitting and tracking bereavement grant applications.",
    "official.p5_tag": "Disability Services",
    "official.p5_desc": "Rumah Inspirasi portal for disability empowerment programs and services.",
    "official.p6_tag": "Licensing Portal",
    "official.p6_desc": "Public business licensing portal with primary and alternative access routes.",
    "official.p7_tag": "Government Website",
    "official.p7_desc": "Official information website of Semarang City Social Services Agency.",
    "official.p8_tag": "MSME Program",
    "official.p8_desc": "Official portal for Semar Preneur Up MSME acceleration program.",
    "official.p9_tag": "AI Finance Assistant",
    "official.p9_desc": "AI-powered bookkeeping assistant for recording expenses via web and Telegram.",
    "official.p10_tag": "Public Information Access",
    "official.p10_desc": "Information Documentation Officer (PPID) portal for DPMPTSP Semarang City.",
    "official.p11_tag": "Neighborhood Administration System",
    "official.p11_desc": "Community administration web app: resident records, family cards, RT treasury ledger, document archives, and PWA/APK installation support.",
    "sidaksos.eyebrow": "SIDAKSOS Coordination Evidence",
    "sidaksos.title": "Official public records of system development & alignment.",
    "sidaksos.desc": "Four official news releases from Semarang Communication & Informatics Agency documenting coordination, finalization, dissemination, and data assessment through SIDAKSOS.",
    "sidaksos.open_news": "Read official news",
    "testimonials.eyebrow": "Client Testimonials & Impact Track Record",
    "testimonials.title": "Trusted by institutions and business owners.",
    "testimonials.desc": "Authentic evaluations from government partners and business owners regarding system quality, implementation training, and proactive problem solving.",
    "testimonials.verified_badge": "Verified Testimonial",
    "testimonials.inst_badge": "Government Agency",
    "testimonials.biz_badge": "Business & Retail Sector",
    "testimonials.zefly_role": "Semarang Social Services Agency · Public Service Management",
    "testimonials.zefly_chat_status": "Semarang Social Services Agency · Partner",
    "testimonials.zefly_quote": "“The Social Services queue system has greatly assisted agency operations, especially for Dinsos...”",
    "testimonials.zefly_imp1_title": "Initial Data Gateway",
    "testimonials.zefly_imp1_desc": "Capturing front-door citizen intake data to measure service reach and social support impact.",
    "testimonials.zefly_imp2_title": "Orderly & Structured",
    "testimonials.zefly_imp2_desc": "Organizing waiting rooms and ticket calls to ensure systematic service flow without bottlenecks.",
    "testimonials.zefly_imp3_title": "Fast & Transparent",
    "testimonials.zefly_imp3_desc": "Streamlining administrative steps and counter calls, making services open and clear for citizens.",
    "testimonials.zefly_imp4_title": "Staff Efficiency",
    "testimonials.zefly_imp4_desc": "Enabling front-office staff to serve applicants comfortably with significantly reduced handling time.",
    "testimonials.pos_role": "Industrial & Technical Supplies Retail & Wholesale · Semarang",
    "testimonials.pos_chat_status": "Retail & Wholesale Business Owner · Partner",
    "testimonials.pos_quote": "“The custom POS app designed specifically for our needs... all challenges were resolved professionally, I am very satisfied and will recommend...”",
    "testimonials.pos_imp1_title": "100% Tailored to Needs",
    "testimonials.pos_imp1_desc": "Engineered specifically to match cashiers, credit receivables, and real store inventory flows.",
    "testimonials.pos_imp2_title": "Intensive UAT Support",
    "testimonials.pos_imp2_desc": "Hands-on guidance through trial testing and initial rollout with responsive revisions.",
    "testimonials.pos_imp3_title": "Responsive Solutions",
    "testimonials.pos_imp3_desc": "Prompt technical problem solving whenever operational obstacles arose in the field.",
    "testimonials.pos_imp4_title": "Client Referral",
    "testimonials.pos_imp4_desc": "High satisfaction leading the business owner to actively recommend the system to fellow peers.",
    "testimonials.open_ss": "View WA Screenshot",
    "testimonials.view_gallery_dinsos": "View Queue System Gallery",
    "testimonials.view_gallery_pos": "View Point of Sales Gallery",
    "testimonials.view_ss_card": "View Original WA Screenshot",
    "testimonials.view_ss_sub": "Click to view full-resolution screenshot",
    "gallery.eyebrow": "Project Documentation",
    "gallery.title": "Visual showcase of built systems.",
    "gallery.desc": "One cover shown per project. All additional screens can be viewed in the interactive lightbox gallery.",
    "gallery.view_screens": "View screens",
    "gallery.screens_count": "screens",
    "gallery.card_desc": "Click to open interactive gallery and view details.",
    "gallery.modal_hint": "Swipe or use arrow keys (← / →)",
    "steps.eyebrow": "How It Works",
    "steps.title": "Solutions born from asking the right questions.",
    "steps.s1_title": "Listen",
    "steps.s1_desc": "Understanding the problem, the people involved, and the intended outcome.",
    "steps.s2_title": "Map",
    "steps.s2_desc": "Co-mapping workflows to ensure shared clarity and alignment across all teams.",
    "steps.s3_title": "Build",
    "steps.s3_desc": "Designing and engineering systems that are simple, realistic, and intuitive to use.",
    "steps.s4_title": "Evaluate",
    "steps.s4_desc": "Evaluating adoption and measurable impact with operational data when feasible.",
    "contact.eyebrow": "Get In Touch",
    "contact.title": "Let's discuss your system requirements.",
    "contact.desc": "Have a new project idea, looking to digitize manual workflows, or need technical consulting on web apps and information systems? Connect directly via WhatsApp.",
    "contact.card_label": "Official WhatsApp Contact",
    "contact.card_btn": "Send WhatsApp Message Now",
    "contact.floating_wa": "WhatsApp Chat",
    "quote.text": "Good software isn't about complexity; it's about being genuinely helpful.",
    "footer.rights": "Eri Nur Sofa · Semarang, Indonesia",
    "footer.tagline": "Built from real problems, clear processes, and tangible value."
  }
};

// 3. THEME SWITCHER LOGIC
function applyTheme(theme) {
  document.documentElement.setAttribute("data-theme", theme);
  localStorage.setItem("eri_theme", theme);
  const themeToggleBtn = document.getElementById("themeToggleBtn");
  const metaThemeColor = document.querySelector('meta[name="theme-color"]');
  
  if (metaThemeColor) {
    metaThemeColor.setAttribute("content", theme === "dark" ? "#070c16" : "#0b1220");
  }

  if (themeToggleBtn) {
    const isDark = theme === "dark";
    themeToggleBtn.setAttribute("aria-label", isDark ? "Beralih ke mode terang (Light Mode)" : "Beralih ke mode gelap (Dark Mode)");
    themeToggleBtn.setAttribute("title", isDark ? "Switch to Light Mode" : "Switch to Dark Mode");
    themeToggleBtn.innerHTML = isDark
      ? `<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>`
      : `<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>`;
  }
}

function toggleTheme() {
  const current = document.documentElement.getAttribute("data-theme") || "light";
  applyTheme(current === "dark" ? "light" : "dark");
}

function initTheme() {
  const savedTheme = localStorage.getItem("eri_theme");
  if (savedTheme) {
    applyTheme(savedTheme);
  } else if (window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches) {
    applyTheme("dark");
  } else {
    applyTheme("light");
  }
}

// 4. BILINGUAL LANGUAGE ENGINE
function setLanguage(lang) {
  if (!i18nTranslations[lang]) return;
  document.documentElement.setAttribute("lang", lang);
  localStorage.setItem("eri_lang", lang);

  const dict = i18nTranslations[lang];

  // Update text elements with data-i18n
  document.querySelectorAll("[data-i18n]").forEach((el) => {
    const key = el.getAttribute("data-i18n");
    if (dict[key] !== undefined) {
      el.innerHTML = dict[key];
    }
  });

  // Update aria-labels with data-i18n-aria
  document.querySelectorAll("[data-i18n-aria]").forEach((el) => {
    const key = el.getAttribute("data-i18n-aria");
    if (dict[key] !== undefined) {
      el.setAttribute("aria-label", dict[key]);
    }
  });

  // Update titles with data-i18n-title
  document.querySelectorAll("[data-i18n-title]").forEach((el) => {
    const key = el.getAttribute("data-i18n-title");
    if (dict[key] !== undefined) {
      el.setAttribute("title", dict[key]);
    }
  });

  // Update active state on language buttons
  document.querySelectorAll(".lang-btn").forEach((btn) => {
    if (btn.getAttribute("data-lang") === lang) {
      btn.classList.add("active");
      btn.setAttribute("aria-pressed", "true");
    } else {
      btn.classList.remove("active");
      btn.setAttribute("aria-pressed", "false");
    }
  });

  // Re-render gallery cards with new language
  renderGalleryCards();
}

function initLanguage() {
  const savedLang = localStorage.getItem("eri_lang");
  if (savedLang && (savedLang === "id" || savedLang === "en")) {
    setLanguage(savedLang);
  } else {
    const browserLang = (navigator.language || navigator.userLanguage || "id").toLowerCase();
    setLanguage(browserLang.startsWith("en") ? "en" : "id");
  }
}

// 5. RENDER GALLERY CARDS (LANGUAGE-AWARE)
function renderGalleryCards() {
  if (!galleryGrid) return;
  galleryGrid.innerHTML = "";

  const currentLang = document.documentElement.getAttribute("lang") || "id";
  const viewScreensText = currentLang === "en" ? "View screens" : "Lihat layar";
  const screensCountText = currentLang === "en" ? "screens" : "tampilan";
  const clickDescText = currentLang === "en" ? "Click to open interactive gallery and view details." : "Klik untuk membuka galeri layar dan melihat detail.";
  const ariaPrefix = currentLang === "en" ? "Open gallery for" : "Buka galeri";

  galleryProjects.forEach((project, projectIndex) => {
    const cover = project.screenshots[0];
    const thumbUrl = getThumbSrc(cover.src);

    const card = document.createElement("article");
    card.className = "gallery-card reveal";
    card.setAttribute("role", "button");
    card.setAttribute("tabindex", "0");
    card.setAttribute("aria-label", `${ariaPrefix} ${project.title}`);

    card.innerHTML = `
      <div class="gallery-thumb-wrap">
        <img class="gallery-thumb" src="${thumbUrl}" alt="${project.title}" loading="lazy" decoding="async" width="400" height="250">
        <div class="gallery-thumb-overlay">
          <span class="tag tag-dark">
            <svg width="12" height="12" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8">
              <circle cx="8.5" cy="8.5" r="5.5"></circle>
              <path d="m13 13 4 4M6 8.5h5"></path>
              <path d="M8.5 6v5"></path>
            </svg>
            ${viewScreensText}
          </span>
          <span class="tag tag-lime">${project.screenshots.length} ${screensCountText}</span>
        </div>
      </div>
      <div class="gallery-info">
        <span class="eyebrow">${project.kind}</span>
        <h4 class="gallery-title">${project.title}</h4>
        <p class="gallery-desc">${clickDescText}</p>
      </div>
    `;

    const openHandler = () => openGallery(projectIndex);
    card.addEventListener("click", openHandler);
    card.addEventListener("keydown", (e) => {
      if (e.key === "Enter" || e.key === " ") {
        e.preventDefault();
        openHandler();
      }
    });

    galleryGrid.appendChild(card);
  });

  if (typeof initScrollObserver === "function") {
    initScrollObserver();
  }
}

// 4. LIGHTBOX OPERATIONS
function openGallery(projectIndex, screenshotIndex = 0) {
  if (typeof projectIndex === "string") {
    const idx = galleryProjects.findIndex(
      (p) => p.title.toLowerCase().includes(projectIndex.toLowerCase())
    );
    if (idx !== -1) projectIndex = idx;
    else projectIndex = 0;
  }
  activeProjectIndex = projectIndex;
  const project = galleryProjects[activeProjectIndex];
  const maxIdx = project ? Math.max(0, project.screenshots.length - 1) : 0;
  activeScreenshotIndex = Math.max(0, Math.min(screenshotIndex, maxIdx));
  currentZoom = 1;

  updateModalContent();
  modal.classList.add("active");
  document.body.style.overflow = "hidden";
}
window.openProjectGallery = openGallery;

function closeGallery() {
  modal.classList.remove("active");
  document.body.style.overflow = "";
  activeProjectIndex = null;
  activeScreenshotIndex = 0;
  currentZoom = 1;
}

function updateModalContent() {
  if (activeProjectIndex === null) return;
  const project = galleryProjects[activeProjectIndex];
  const screenshot = project.screenshots[activeScreenshotIndex];

  modalKind.textContent = project.kind;
  modalTitle.textContent = project.title;
  modalCounter.textContent = `${screenshot.label} · ${activeScreenshotIndex + 1} dari ${project.screenshots.length}`;

  modalImg.src = screenshot.src;
  modalImg.alt = `${project.title} — ${screenshot.label}`;
  applyZoom();

  // Navigation states
  prevBtn.disabled = project.screenshots.length < 2;
  nextBtn.disabled = project.screenshots.length < 2;

  // Thumbnails render
  thumbsContainer.innerHTML = "";
  project.screenshots.forEach((sc, idx) => {
    const thumbBtn = document.createElement("button");
    thumbBtn.className = `lightbox-thumb-btn ${idx === activeScreenshotIndex ? "active" : ""}`;
    thumbBtn.type = "button";
    thumbBtn.setAttribute("aria-label", `Tampilkan ${sc.label}`);
    thumbBtn.innerHTML = `<img src="${getThumbSrc(sc.src)}" alt="" loading="lazy">`;

    thumbBtn.addEventListener("click", () => {
      activeScreenshotIndex = idx;
      currentZoom = 1;
      updateModalContent();
    });

    thumbsContainer.appendChild(thumbBtn);

    if (idx === activeScreenshotIndex) {
      setTimeout(() => {
        thumbBtn.scrollIntoView({ behavior: "smooth", inline: "center", block: "nearest" });
      }, 50);
    }
  });
}

function moveScreenshot(direction) {
  if (activeProjectIndex === null) return;
  const project = galleryProjects[activeProjectIndex];
  if (project.screenshots.length < 2) return;

  const total = project.screenshots.length;
  activeScreenshotIndex = (activeScreenshotIndex + direction + total) % total;
  currentZoom = 1;
  updateModalContent();
}

function setZoom(newZoom) {
  currentZoom = Math.min(2.5, Math.max(1, newZoom));
  applyZoom();
}

function applyZoom() {
  modalImg.style.transform = `scale(${currentZoom})`;
  zoomLevelText.textContent = `Zoom ${currentZoom.toFixed(1)}×`;
  zoomInBtn.disabled = currentZoom >= 2.5;
  zoomOutBtn.disabled = currentZoom <= 1;
}

// 5. LIGHTBOX LISTENERS
if (closeBtn) closeBtn.addEventListener("click", closeGallery);
if (prevBtn) prevBtn.addEventListener("click", () => moveScreenshot(-1));
if (nextBtn) nextBtn.addEventListener("click", () => moveScreenshot(1));
if (zoomInBtn) zoomInBtn.addEventListener("click", () => setZoom(currentZoom + 0.5));
if (zoomOutBtn) zoomOutBtn.addEventListener("click", () => setZoom(currentZoom - 0.5));

if (modalImg) {
  modalImg.addEventListener("dblclick", () => {
    setZoom(currentZoom === 1 ? 2 : 1);
  });
}

modal.addEventListener("click", (e) => {
  if (e.target === modal) closeGallery();
});

window.addEventListener("keydown", (e) => {
  if (!modal.classList.contains("active")) return;
  if (e.key === "Escape") closeGallery();
  if (e.key === "ArrowLeft") {
    e.preventDefault();
    moveScreenshot(-1);
  }
  if (e.key === "ArrowRight") {
    e.preventDefault();
    moveScreenshot(1);
  }
});

const lightboxBody = document.querySelector(".lightbox-body");
if (lightboxBody) {
  lightboxBody.addEventListener("touchstart", (e) => {
    touchStartX = e.touches[0].clientX;
  }, { passive: true });

  lightboxBody.addEventListener("touchend", (e) => {
    if (touchStartX === null) return;
    const distance = e.changedTouches[0].clientX - touchStartX;
    if (distance > 50) moveScreenshot(-1);
    if (distance < -50) moveScreenshot(1);
    touchStartX = null;
  });
}

// 6. SCROLL OBSERVER FOR REVEAL ANIMATIONS
let scrollObserver;
function initScrollObserver() {
  if (!("IntersectionObserver" in window)) {
    document.querySelectorAll(".reveal").forEach((el) => el.classList.add("revealed"));
    return;
  }

  if (scrollObserver) scrollObserver.disconnect();

  scrollObserver = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add("revealed");
        scrollObserver.unobserve(entry.target);
      }
    });
  }, {
    threshold: 0.1,
    rootMargin: "0px 0px -40px 0px",
  });

  document.querySelectorAll(".reveal:not(.revealed)").forEach((el) => {
    scrollObserver.observe(el);
  });
}

// 7. ACTIVE NAVIGATION SPY & SCROLL PROGRESS
function handleScroll() {
  const scrollY = window.scrollY;
  const docHeight = document.documentElement.scrollHeight - window.innerHeight;
  
  // Progress Bar
  if (progressBar && docHeight > 0) {
    const progress = (scrollY / docHeight) * 100;
    progressBar.style.width = `${progress}%`;
  }

  // Back to top button
  if (backToTopBtn) {
    if (scrollY > 500) {
      backToTopBtn.classList.add("visible");
    } else {
      backToTopBtn.classList.remove("visible");
    }
  }

  // Active Nav Link Spy
  const navLinks = document.querySelectorAll(".nav-links a[href^='#']");
  const trackedElements = Array.from(navLinks)
    .map((link) => {
      const href = link.getAttribute("href");
      if (!href || href === "#top" || href.startsWith("https")) return null;
      return document.querySelector(href);
    })
    .filter(Boolean);

  let currentSectionId = "";
  trackedElements.forEach((el) => {
    const elTop = el.getBoundingClientRect().top + window.scrollY - 160;
    const elHeight = el.offsetHeight;
    if (scrollY >= elTop && scrollY < elTop + elHeight) {
      currentSectionId = el.getAttribute("id");
    }
  });

  navLinks.forEach((link) => {
    link.classList.remove("active");
    if (link.getAttribute("href") === `#${currentSectionId}`) {
      link.classList.add("active");
    }
  });
}

window.addEventListener("scroll", handleScroll, { passive: true });

if (backToTopBtn) {
  backToTopBtn.addEventListener("click", () => {
    window.scrollTo({ top: 0, behavior: "smooth" });
  });
}

// Initialize on DOM Ready
document.addEventListener("DOMContentLoaded", () => {
  // Initialize Theme and Language
  initTheme();
  initLanguage();

  renderGalleryCards();
  initScrollObserver();
  handleScroll();

  // Bind Theme Toggle Button
  const themeToggleBtn = document.getElementById("themeToggleBtn");
  if (themeToggleBtn) {
    themeToggleBtn.addEventListener("click", toggleTheme);
  }

  // Bind Language Switcher Buttons
  document.querySelectorAll(".lang-btn").forEach((btn) => {
    btn.addEventListener("click", (e) => {
      e.preventDefault();
      const targetLang = btn.getAttribute("data-lang");
      if (targetLang) {
        setLanguage(targetLang);
      }
    });
  });

  // Bind dynamic gallery trigger buttons
  document.querySelectorAll("[data-open-gallery]").forEach((btn) => {
    btn.addEventListener("click", (e) => {
      e.preventDefault();
      const proj = btn.getAttribute("data-open-gallery");
      const slide = parseInt(btn.getAttribute("data-gallery-slide") || "0", 10);
      openGallery(proj, slide);
    });
  });
});
