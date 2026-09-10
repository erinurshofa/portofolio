/**
 * AI Chat Widget Controller (ENS-AI)
 * Connects directly to secure backend api/chat.php with failover detection
 * Eri Nur Sofa Portfolio
 */

(function () {
  'use strict';

  // State
  const state = {
    isOpen: false,
    isThinking: false,
    history: [],
    activeLeadRef: sessionStorage.getItem('ens_chat_lead_ref') || null
  };

  // Storage key
  const STORAGE_KEY = 'ens_chat_history_v1';

  // DOM references
  let triggerBtn, modal, closeBtn, clearBtn, chatBody, textarea, sendBtn, quickStarters;

  function init() {
    createDOM();
    bindEvents();
    const restored = restoreSessionHistory();
    if (!restored) {
      renderWelcomeMessage();
    }
  }

  function createDOM() {
    // 1. Floating Trigger Button
    triggerBtn = document.createElement('button');
    triggerBtn.id = 'ensAiTriggerBtn';
    triggerBtn.className = 'ens-ai-trigger';
    triggerBtn.setAttribute('aria-label', 'Buka Chat Asisten AI Eri Nur Sofa');
    triggerBtn.setAttribute('title', 'Tanya AI seputar portofolio & estimasi harga');
    triggerBtn.innerHTML = `
      <div class="ens-ai-trigger-icon">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 2a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2 2 2 0 0 1-2-2V4a2 2 0 0 1 2-2z"></path>
          <rect x="4" y="8" width="16" height="12" rx="2"></rect>
          <path d="M2 14h2"></path>
          <path d="M20 14h2"></path>
          <path d="M15 13v2"></path>
          <path d="M9 13v2"></path>
        </svg>
        <span class="ens-ai-status-pulse"></span>
      </div>
      <div class="ens-ai-trigger-text">
        <span class="ens-ai-trigger-label">Tanya Eri AI</span>
        <span class="ens-ai-trigger-sub">Online • Portofolio &amp; Estimasi</span>
      </div>
    `;
    document.body.appendChild(triggerBtn);

    // 2. Chat Modal Window
    modal = document.createElement('div');
    modal.id = 'ensAiChatModal';
    modal.className = 'ens-chat-modal';
    modal.setAttribute('role', 'dialog');
    modal.setAttribute('aria-modal', 'true');
    modal.setAttribute('aria-label', 'Jendela Percakapan Eri AI');
    modal.innerHTML = `
      <div class="ens-chat-header">
        <div class="ens-chat-header-info">
          <div class="ens-chat-avatar">
            ERI
            <span class="online-indicator"></span>
          </div>
          <div class="ens-chat-title-box">
            <h3>Eri AI <span class="ens-badge-pill">Asisten Cerdas</span></h3>
            <p>Asisten Resmi Mas Eri <span class="ens-failover-tag">• Siap Konsultasi</span></p>
          </div>
        </div>
        <div class="ens-chat-actions">
          <button type="button" class="ens-icon-btn" id="ensClearChatBtn" title="Hapus Percakapan" aria-label="Hapus Percakapan">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M3 6h18"></path>
              <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path>
              <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path>
            </svg>
          </button>
          <button type="button" class="ens-icon-btn" id="ensCloseChatBtn" title="Tutup Chat" aria-label="Tutup Chat">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>
      </div>

      <!-- Quick starter prompt pills -->
      <div class="ens-quick-starters" id="ensQuickStarters">
        <button type="button" class="ens-starter-pill" data-query="Apakah bisa buat fitur atau mini project mulai 500k?">⚡ Fitur Mini Mulai 500k</button>
        <button type="button" class="ens-starter-pill" data-query="Berapa kisaran harga dan fitur untuk sistem POS kasir dan inventori toko?">🛒 Harga POS Kasir</button>
        <button type="button" class="ens-starter-pill" data-query="Apa saja proyek nyata yang pernah dikerjakan Mas Eri (seperti Catet.ai dan E-Sankem)?">🏛️ Portofolio Karya</button>
        <button type="button" class="ens-starter-pill" data-query="Berapa estimasi biaya dan waktu untuk landing page atau web profile perusahaan?">🌐 Web Profile</button>
        <button type="button" class="ens-starter-pill" data-action="open-breakdown">📋 Breakdown Aplikasi</button>
      </div>

      <!-- Messages Body -->
      <div class="ens-chat-body" id="ensChatBody"></div>

      <!-- Footer & Input -->
      <div class="ens-chat-footer">
        <div class="ens-input-row">
          <textarea id="ensChatInput" class="ens-chat-textarea" placeholder="Tanya sesuatu tentang portofolio atau estimasi harga..." rows="1"></textarea>
          <button type="button" id="ensSendBtn" class="ens-send-btn" aria-label="Kirim Pesan" title="Kirim">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <line x1="22" y1="2" x2="11" y2="13"></line>
              <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
            </svg>
          </button>
        </div>
        <div class="ens-footer-meta">
          <span>Tekan <strong>Enter</strong> kirim, <strong>Shift+Enter</strong> baris baru</span>
          <button type="button" class="ens-breakdown-trigger-link" id="ensOpenBreakdownLink">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
              <polyline points="14 2 14 8 20 8"></polyline>
              <line x1="16" y1="13" x2="8" y2="13"></line>
              <line x1="16" y1="17" x2="8" y2="17"></line>
            </svg>
            Form Breakdown Aplikasi
          </button>
        </div>
      </div>
    `;
    document.body.appendChild(modal);

    // Cache elements
    closeBtn = modal.querySelector('#ensCloseChatBtn');
    clearBtn = modal.querySelector('#ensClearChatBtn');
    chatBody = modal.querySelector('#ensChatBody');
    textarea = modal.querySelector('#ensChatInput');
    sendBtn = modal.querySelector('#ensSendBtn');
    quickStarters = modal.querySelector('#ensQuickStarters');
  }

  function bindEvents() {
    triggerBtn.addEventListener('click', toggleChat);
    closeBtn.addEventListener('click', closeChat);
    clearBtn.addEventListener('click', clearChat);

    sendBtn.addEventListener('click', handleSend);
    textarea.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        handleSend();
      }
    });

    // Auto-resize textarea
    textarea.addEventListener('input', function () {
      this.style.height = 'auto';
      this.style.height = Math.min(this.scrollHeight, 100) + 'px';
    });

    // Mouse Wheel to Horizontal Scroll for quick starters
    quickStarters.addEventListener('wheel', function (e) {
      if (e.deltaY !== 0) {
        e.preventDefault();
        quickStarters.scrollLeft += e.deltaY;
      }
    }, { passive: false });

    // Click and drag to scroll for desktop mouse
    let isDown = false;
    let startX = 0;
    let scrollLeftPos = 0;
    let hasDragged = false;

    quickStarters.addEventListener('mousedown', function (e) {
      isDown = true;
      hasDragged = false;
      startX = e.pageX - quickStarters.offsetLeft;
      scrollLeftPos = quickStarters.scrollLeft;
    });

    quickStarters.addEventListener('mouseleave', function () {
      isDown = false;
    });

    quickStarters.addEventListener('mouseup', function () {
      isDown = false;
    });

    quickStarters.addEventListener('mousemove', function (e) {
      if (!isDown) return;
      e.preventDefault();
      const x = e.pageX - quickStarters.offsetLeft;
      const walk = (x - startX) * 1.5;
      if (Math.abs(walk) > 5) {
        hasDragged = true;
      }
      quickStarters.scrollLeft = scrollLeftPos - walk;
    });

    // Quick starter pills click
    quickStarters.addEventListener('click', function (e) {
      // If user was dragging, do not trigger pill click
      if (hasDragged) {
        hasDragged = false;
        return;
      }

      const pill = e.target.closest('.ens-starter-pill');
      if (!pill) return;

      if (pill.dataset.action === 'open-breakdown') {
        if (window.PortfolioBreakdown && typeof window.PortfolioBreakdown.open === 'function') {
          window.PortfolioBreakdown.open();
        }
        return;
      }

      const query = pill.dataset.query;
      if (query) {
        sendMessage(query);
      }
    });

    // Breakdown link in footer
    const breakdownLink = modal.querySelector('#ensOpenBreakdownLink');
    if (breakdownLink) {
      breakdownLink.addEventListener('click', function () {
        if (window.PortfolioBreakdown && typeof window.PortfolioBreakdown.open === 'function') {
          window.PortfolioBreakdown.open();
        }
      });
    }
  }

  function getVisitorSessionId() {
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

  function toggleChat() {
    state.isOpen = !state.isOpen;
    if (state.isOpen) {
      modal.classList.add('active');
      triggerBtn.style.opacity = '0.7';
      textarea.focus();
      // Track interaction
      try {
        fetch('api/track.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            session_id: getVisitorSessionId(),
            page: window.location.pathname.split('/').pop() || 'index.html',
            interaction: 'Opened AI Chat'
          })
        }).catch(() => {});
      } catch (e) {}
    } else {
      modal.classList.remove('active');
      triggerBtn.style.opacity = '1';
    }
  }

  function closeChat() {
    state.isOpen = false;
    modal.classList.remove('active');
    triggerBtn.style.opacity = '1';
  }

  function clearChat() {
    state.history = [];
    state.activeLeadRef = null;
    try {
      sessionStorage.removeItem(STORAGE_KEY);
      sessionStorage.removeItem('ens_chat_lead_ref');
    } catch (e) {}
    chatBody.innerHTML = '';
    renderWelcomeMessage();
  }

  function saveSessionHistory() {
    try {
      sessionStorage.setItem(STORAGE_KEY, JSON.stringify(state.history));
    } catch (e) {}
  }

  function restoreSessionHistory() {
    try {
      const stored = sessionStorage.getItem(STORAGE_KEY);
      if (stored) {
        const parsed = JSON.parse(stored);
        if (Array.isArray(parsed) && parsed.length > 0) {
          state.history = parsed;
          chatBody.innerHTML = '';
          state.history.forEach(item => {
            if (item.role === 'user') {
              appendUserMessage(item.content, false);
            } else if (item.role === 'assistant') {
              appendBotMessage(formatMarkdown(item.content), true, false);
            }
          });
          scrollToBottom();
          return true;
        }
      }
    } catch (e) {}
    return false;
  }

  function renderWelcomeMessage() {
    const welcomeHtml = `
      <p>Halo! Saya <strong>Eri AI</strong>, asisten dari Mas Eri Nur Sofa. 👋</p>
      <p>Senang bisa ngobrol dengan Anda. Ada ide aplikasi yang mau diwujudkan, ingin tahu portofolio karya Mas Eri, atau mau cek estimasi biaya? (Untuk fitur atau mini project pengerjaannya bisa mulai dari <strong>Rp 500 rb</strong> lho).</p>
      <p>Silakan tanyakan apa saja, atau langsung pilih topik cepat di atas ya!</p>
    `;
    appendBotMessage(welcomeHtml, false);
  }

  function handleSend() {
    const text = textarea.value.trim();
    if (!text || state.isThinking) return;

    textarea.value = '';
    textarea.style.height = 'auto';
    sendMessage(text);
  }

  async function sendMessage(text) {
    if (state.isThinking) return;

    // Validate using Zod client validator
    if (window.PortfolioValidator) {
      const check = window.PortfolioValidator.validateChat(text);
      if (!check.isValid) {
        alert(check.error);
        return;
      }
      text = check.cleanMessage;
    }

    // Append user message bubble
    appendUserMessage(text);
    state.history.push({ role: 'user', content: text });
    saveSessionHistory();

    // Show typing indicator
    showTyping();
    state.isThinking = true;
    sendBtn.disabled = true;

    try {
      const response = await fetch('api/chat.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          session_id: getVisitorSessionId(),
          message: text,
          history: state.history.slice(-6),
          lead_ref: state.activeLeadRef
        })
      });

      const data = await response.json();
      removeTyping();

      if (data.success && data.reply) {
        state.history.push({ role: 'assistant', content: data.reply });
        saveSessionHistory();

        let structuredLead = null;
        if (data.structured_lead && typeof data.structured_lead === 'object') {
          structuredLead = data.structured_lead;
          if (structuredLead.lead_ref) {
            state.activeLeadRef = structuredLead.lead_ref;
            try {
              sessionStorage.setItem('ens_chat_lead_ref', structuredLead.lead_ref);
            } catch (e) {}
          }

          // Auto-sync structured lead into breakdown draft
          try {
            const rawDraft = sessionStorage.getItem('ens_breakdown_draft_v1');
            const existingDraft = rawDraft ? JSON.parse(rawDraft) : {};
            const cleanName = (structuredLead.name && !structuredLead.name.includes('Calon Klien')) ? structuredLead.name : (existingDraft.name || '');
            const cleanWa = (structuredLead.whatsapp && !structuredLead.whatsapp.includes('Belum')) ? structuredLead.whatsapp : (existingDraft.whatsapp || '');

            const updatedDraft = {
              ...existingDraft,
              name: cleanName,
              whatsapp: cleanWa,
              project_category: structuredLead.project_category || existingDraft.project_category || '',
              project_status: structuredLead.project_status || existingDraft.project_status || '',
              target_platform: Array.isArray(structuredLead.target_platform) ? structuredLead.target_platform : (existingDraft.target_platform || []),
              selected_features: Array.isArray(structuredLead.selected_features) ? structuredLead.selected_features : (existingDraft.selected_features || []),
              budget_range: structuredLead.budget_range || existingDraft.budget_range || '',
              timeline: structuredLead.timeline || existingDraft.timeline || '',
              notes: structuredLead.notes || existingDraft.notes || ''
            };
            sessionStorage.setItem('ens_breakdown_draft_v1', JSON.stringify(updatedDraft));
          } catch (e) {}
        }

        appendBotMessage(formatMarkdown(data.reply), true, true, structuredLead);
      } else {
        const errMsg = data.error || 'Terjadi kendala saat menghubungi AI. Silakan coba sesaat lagi.';
        appendBotMessage(`<p style="color:#ef4444;">${escapeHtml(errMsg)}</p>`);
      }
    } catch (err) {
      removeTyping();
      appendBotMessage(`
        <p>Maaf, koneksi ke server sedang mengalami gangguan sementara.</p>
        <a href="https://wa.me/6285641280960?text=Halo%20Mas%20Eri%2C%20saya%20ingin%20konsultasi%20langsung." target="_blank" rel="noopener noreferrer" class="ens-bubble-cta">
          Chat WhatsApp Langsung ke Mas Eri
        </a>
      `);
    } finally {
      state.isThinking = false;
      sendBtn.disabled = false;
    }
  }

  function appendUserMessage(text, scroll = true) {
    const row = document.createElement('div');
    row.className = 'ens-message-row user';
    row.innerHTML = `
      <div class="ens-message-bubble">
        <p>${escapeHtml(text)}</p>
        <span class="ens-message-time">${getCurrentTime()}</span>
      </div>
    `;
    chatBody.appendChild(row);
    if (scroll) scrollToBottom();
  }

  function appendBotMessage(htmlContent, showBreakdownCta = true, scroll = true, structuredLead = null) {
    const row = document.createElement('div');
    row.className = 'ens-message-row bot';

    let chipBlock = '';
    if (structuredLead && structuredLead.lead_ref) {
      chipBlock = `
        <div class="ens-lead-chip-box">
          <div class="ens-lead-chip-info">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; vertical-align:middle; margin-right:3px;">
              <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
              <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
            Kebutuhan Anda tersimpan ke sistem (Ref: <strong>${escapeHtml(structuredLead.lead_ref)}</strong>)
          </div>
          <button type="button" class="ens-lead-chip-btn" onclick="window.PortfolioBreakdown && window.PortfolioBreakdown.open()">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
              <polyline points="14 2 14 8 20 8"></polyline>
              <line x1="16" y1="13" x2="8" y2="13"></line>
              <line x1="16" y1="17" x2="8" y2="17"></line>
              <polyline points="10 9 9 9 8 9"></polyline>
            </svg>
            Tinjau Form Estimasi Terstruktur
          </button>
        </div>
      `;
    }

    let ctaBlock = '';
    // If reply talks about price or building an app, offer Breakdown button
    if (showBreakdownCta && (htmlContent.includes('Rp') || htmlContent.includes('biaya') || htmlContent.includes('harga') || htmlContent.includes('Breakdown'))) {
      ctaBlock = `
        <div>
          <button type="button" class="ens-bubble-cta" onclick="window.PortfolioBreakdown && window.PortfolioBreakdown.open()">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="9 11 12 14 22 4"></polyline>
              <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
            </svg>
            Isi Form Breakdown Aplikasi
          </button>
          <a href="https://wa.me/6285641280960?text=Halo%20Mas%20Eri%2C%20saya%20sudah%20tanya%20AI%20dan%20ingin%20konsultasi%20penawaran%20resmi." target="_blank" rel="noopener noreferrer" class="ens-bubble-cta" style="margin-left:0.35rem; background:rgba(37,211,102,0.18); color:#86efac; border-color:rgba(37,211,102,0.35);">
            Chat WhatsApp
          </a>
        </div>
      `;
    }

    row.innerHTML = `
      <div class="ens-message-bubble">
        ${htmlContent}
        ${chipBlock}
        ${ctaBlock}
        <span class="ens-message-time">${getCurrentTime()} • Eri AI</span>
      </div>
    `;
    chatBody.appendChild(row);
    if (scroll) scrollToBottom();
  }

  function showTyping() {
    removeTyping();
    const typing = document.createElement('div');
    typing.id = 'ensTypingIndicator';
    typing.className = 'ens-typing-indicator';
    typing.innerHTML = `<span></span><span></span><span></span>`;
    chatBody.appendChild(typing);
    scrollToBottom();
  }

  function removeTyping() {
    const el = document.getElementById('ensTypingIndicator');
    if (el) el.remove();
  }

  function scrollToBottom() {
    chatBody.scrollTop = chatBody.scrollHeight;
  }

  function getCurrentTime() {
    const now = new Date();
    return now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
  }

  function escapeHtml(str) {
    return str
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function formatMarkdown(raw) {
    if (!raw) return '';
    // Security: Neutralize raw HTML from LLM output to prevent Indirect Stored XSS
    let text = escapeHtml(raw);

    // Headers
    text = text.replace(/^### (.*$)/gim, '<h4 style="margin:0.5rem 0 0.3rem 0; font-size:0.95rem; font-weight:700; color:#fff;">$1</h4>');
    text = text.replace(/^## (.*$)/gim, '<h3 style="margin:0.6rem 0 0.4rem 0; font-size:1.05rem; font-weight:700; color:#fff;">$1</h3>');

    // Convert bullet lists (*, -, •) to <li> before bold/italic to prevent asterisk collision
    text = text.replace(/^\s*[\*\-\•]\s+(.*)$/gim, '<li>$1</li>');

    // Bold & Italic
    text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    text = text.replace(/\*([^\*]+)\*/g, '<em>$1</em>');

    // Wrap consecutive <li> into a clean <ul>
    text = text.replace(/(<li>.*?<\/li>(?:\s*<li>.*?<\/li>)*)/gims, '<ul style="margin:0.4rem 0 0.6rem 0; padding-left:1.25rem;">$1</ul>');

    // Clean duplicate <ul> wrappers if any
    text = text.replace(/<\/ul>\s*<ul[^>]*>/g, '');

    // Paragraphs
    const paras = text.split(/\n\n+/);
    return paras.map(p => {
      p = p.trim();
      if (!p) return '';
      if (p.startsWith('<ul') || p.startsWith('<h3') || p.startsWith('<h4')) return p;
      return `<p>${p.replace(/\n/g, '<br>')}</p>`;
    }).join('');
  }

  // Public API
  window.PortfolioAiChat = {
    open: function () {
      if (!state.isOpen) toggleChat();
    },
    close: closeChat,
    ask: function (question) {
      if (!state.isOpen) toggleChat();
      sendMessage(question);
    }
  };

  // Initialize on DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
