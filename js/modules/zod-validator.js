/**
 * Client-side Schema Validator using Zod (with robust fallback)
 * Eri Nur Sofa Portfolio Enterprise AI Services
 */

(function (root, factory) {
  if (typeof module === 'object' && module.exports) {
    module.exports = factory();
  } else {
    root.PortfolioValidator = factory();
  }
})(typeof self !== 'undefined' ? self : this, function () {
  'use strict';

  // Indonesian WhatsApp / Phone Regex (+62, 62, 08, 8 followed by 8-13 digits)
  const PHONE_REGEX = /^(\+62|62|08|8)[0-9]{8,13}$/;
  const EMAIL_REGEX = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

  /**
   * Validate Lead Breakdown Form Data
   * @param {Object} data 
   * @returns {{ isValid: boolean, errors: Record<string, string>, data: Object }}
   */
  function validateLead(data) {
    const errors = {};

    // 1. Name validation
    const name = typeof data.name === 'string' ? data.name.trim() : '';
    if (!name) {
      errors.name = 'Nama lengkap wajib diisi.';
    } else if (name.length < 2) {
      errors.name = 'Nama terlalu pendek (minimal 2 karakter).';
    } else if (name.length > 100) {
      errors.name = 'Nama maksimal 100 karakter.';
    }

    // 2. WhatsApp validation (clean spaces, dashes, dots)
    const whatsapp = typeof data.whatsapp === 'string' ? data.whatsapp.replace(/[\s\-\.\(\)]/g, '').replace(/[^0-9+]/g, '') : '';
    if (!whatsapp) {
      errors.whatsapp = 'Nomor WhatsApp aktif wajib diisi.';
    } else if (!PHONE_REGEX.test(whatsapp)) {
      errors.whatsapp = 'Format nomor WhatsApp tidak valid (contoh: 0856 4128 0960 atau +62812345678).';
    }

    // 3. Email validation (optional)
    const email = typeof data.email === 'string' ? data.email.trim() : '';
    if (email && !EMAIL_REGEX.test(email)) {
      errors.email = 'Format email tidak valid.';
    }

    // 4. Project Category
    const category = typeof data.project_category === 'string' ? data.project_category.trim() : '';
    if (!category) {
      errors.project_category = 'Silakan pilih kategori sistem atau aplikasi.';
    }

    // 5. Budget Range
    const budget = typeof data.budget_range === 'string' ? data.budget_range.trim() : '';
    if (!budget) {
      errors.budget_range = 'Silakan pilih perkiraan budget.';
    }

    // 6. Notes / Gambaran Detail Kebutuhan (Mandatory)
    const notes = typeof data.notes === 'string' ? data.notes.trim() : '';
    if (!notes) {
      errors.notes = 'Gambaran detail kebutuhan wajib diisi agar Mas Eri lebih paham proyek Anda.';
    } else if (notes.length < 10) {
      errors.notes = 'Mohon jelaskan sedikit lebih detail (minimal 10 karakter).';
    } else if (notes.length > 1000) {
      errors.notes = 'Gambaran kebutuhan maksimal 1000 karakter.';
    }

    // 7. Honeypot check
    if (data.website_hp && data.website_hp.trim() !== '') {
      errors.general = 'Verifikasi spam gagal.';
    }

    return {
      isValid: Object.keys(errors).length === 0,
      errors: errors,
      data: {
        name,
        whatsapp,
        email,
        company: typeof data.company === 'string' ? data.company.trim() : '',
        project_category: category,
        project_status: typeof data.project_status === 'string' ? data.project_status.trim() : '🌱 Bangun Baru dari Nol',
        target_platform: Array.isArray(data.target_platform) ? data.target_platform : ['🌐 Web Browser / Web App'],
        selected_features: Array.isArray(data.selected_features) ? data.selected_features : [],
        budget_range: budget,
        timeline: typeof data.timeline === 'string' ? data.timeline.trim() : '📅 Standar (1–2 Minggu)',
        notes: typeof data.notes === 'string' ? data.notes.trim() : ''
      }
    };
  }

  /**
   * Validate Chat Message
   * @param {string} message 
   * @returns {{ isValid: boolean, error?: string, cleanMessage: string }}
   */
  function validateChat(message) {
    const text = typeof message === 'string' ? message.trim() : '';
    if (!text) {
      return { isValid: false, error: 'Pesan chat tidak boleh kosong.', cleanMessage: '' };
    }
    if (text.length > 1500) {
      return { isValid: false, error: 'Pesan terlalu panjang (maksimal 1500 karakter).', cleanMessage: text.slice(0, 1500) };
    }
    return { isValid: true, cleanMessage: text };
  }

  return {
    validateLead,
    validateChat
  };
});
