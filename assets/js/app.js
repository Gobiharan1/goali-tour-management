(() => {
  'use strict';

  const STORAGE_KEY = 'goali-itinerary-studio-v1';
  const CATEGORIES = [
    'Sri Lanka Family Tours',
    'Sri Lanka Honeymoon Tours',
    'Sri Lanka Cultural Tours',
    'Sri Lanka Adventure Tours',
    'Sri Lanka Luxury Tours',
    'Sri Lanka Beach Tours'
  ];

  const sampleTour = {
    id: 'sample-sri-lanka',
    packageId: 'GT-2026-001',
    tourName: 'Island soul & hill-country calm',
    category: 'Sri Lanka Cultural Tours',
    activityLevel: 'Intermediate',
    durationDays: 5,
    durationNights: 4,
    customerName: 'Alex & Jamie',
    travelDates: '12–17 December 2026',
    customerDetails: 'A private journey through Sri Lanka’s storied coast, emerald tea country, and warm cultural heart — paced with room to breathe.',
    locations: 'Colombo → Galle → Ella → Kandy',
    highlights: ['Sunset walk through Galle Fort', 'Scenic hill-country rail journey', 'Private tea estate experience', 'A thoughtfully paced cultural day'],
    inclusions: ['Private air-conditioned transport', 'Boutique accommodation', 'Daily breakfast', 'English-speaking chauffeur guide'],
    exclusions: ['International flights', 'Travel insurance', 'Personal expenses', 'Optional activities'],
    priceCurrency: 'USD',
    priceAmount: 2450,
    importantNotes: 'Rates are based on two guests sharing. The itinerary can be adjusted around your preferred pace, room style, and interests.',
    coverImage: 'assets/uploads/tours/gallery_20260813_121836_8b9055ea73.jpg',
    days: [
      { title: 'A warm island welcome', details: 'Arrive in Colombo and travel south along the coast. Settle into your boutique stay before an unhurried sunset walk through Galle Fort.', image: 'assets/uploads/tours/day_1_20260811_094104_7c53df62.png' },
      { title: 'Tea country by rail', details: 'Travel into the highlands and board one of Asia’s most scenic train journeys. Watch tea fields, waterfalls, and mountain villages drift by.', image: 'assets/uploads/tours/day_2_20260814_105250_fa3ddb58.jpg' },
      { title: 'The rhythm of Ella', details: 'A relaxed morning of viewpoints, local flavours, and forest paths. The afternoon is yours to slow down and enjoy the hills.', image: 'assets/uploads/tours/day_3_20260814_105250_6c910026.jpg' },
      { title: 'Stories of the cultural capital', details: 'Continue to Kandy for a privately guided introduction to the city, its lake, gardens, craft traditions, and sacred history.', image: 'assets/uploads/tours/day_4_20260814_105250_ace2556e.jpg' },
      { title: 'Until next time', details: 'Enjoy a final Sri Lankan breakfast and a comfortable private transfer for your onward journey.', image: 'assets/uploads/tours/day_5_20260814_112440_04d4683d.jpeg' }
    ],
    status: 'active',
    createdAt: '2026-08-15T05:30:00.000Z',
    updatedAt: '2026-08-15T05:30:00.000Z'
  };

  const defaultState = () => ({
    version: 1,
    settings: {
      companyName: 'Goali Tours',
      contact: 'hello@goalitours.com · +94 77 000 0000',
      brandColor: '#173f32',
      logo: ''
    },
    tours: [sampleTour]
  });

  let state = loadState();
  let currentView = 'dashboard';
  let draftDays = [];
  let draftCover = '';
  let toastTimer;

  const $ = (selector, root = document) => root.querySelector(selector);
  const $$ = (selector, root = document) => [...root.querySelectorAll(selector)];

  function clone(value) {
    return JSON.parse(JSON.stringify(value));
  }

  function loadState() {
    try {
      const stored = localStorage.getItem(STORAGE_KEY);
      if (!stored) return defaultState();
      const parsed = JSON.parse(stored);
      if (!parsed || !Array.isArray(parsed.tours) || !parsed.settings) return defaultState();
      return parsed;
    } catch (error) {
      console.warn('Could not read local workspace.', error);
      return defaultState();
    }
  }

  function saveState(message = 'Saved to this browser') {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
      renderAll();
      if (message) showToast(message);
      return true;
    } catch (error) {
      showToast('Browser storage is full. Export a backup, then remove large images.');
      console.error(error);
      return false;
    }
  }

  function showToast(message) {
    const toast = $('#toast');
    toast.textContent = message;
    toast.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => toast.classList.remove('show'), 2700);
  }

  function escapeHtml(value = '') {
    return String(value)
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  function safeImage(value = '') {
    const source = String(value || '');
    if (source.startsWith('data:image/') || source.startsWith('assets/uploads/')) return source;
    return '';
  }

  function normalizeLines(value) {
    if (Array.isArray(value)) return value.filter(Boolean).map(item => String(item).trim()).filter(Boolean);
    return String(value || '').split(/\r?\n/).map(item => item.trim()).filter(Boolean);
  }

  function formatDate(value) {
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return 'Recently updated';
    return new Intl.DateTimeFormat('en', { day: 'numeric', month: 'short', year: 'numeric' }).format(date);
  }

  function currency(value, code) {
    const amount = Number(value || 0);
    try {
      return new Intl.NumberFormat('en', { style: 'currency', currency: code || 'LKR', maximumFractionDigits: 0 }).format(amount);
    } catch (_) {
      return `${code || 'LKR'} ${amount.toLocaleString()}`;
    }
  }

  function uid() {
    return typeof crypto !== 'undefined' && crypto.randomUUID
      ? crypto.randomUUID()
      : `tour-${Date.now()}-${Math.random().toString(16).slice(2)}`;
  }

  function packageId() {
    const stamp = new Date().toISOString().slice(0, 10).replaceAll('-', '');
    return `GT-${stamp}-${String(state.tours.length + 1).padStart(3, '0')}`;
  }

  function hexToRgb(hex) {
    const clean = /^#[0-9a-f]{6}$/i.test(hex) ? hex.slice(1) : '173f32';
    return [parseInt(clean.slice(0, 2), 16), parseInt(clean.slice(2, 4), 16), parseInt(clean.slice(4, 6), 16)];
  }

  function darken(hex, factor = .66) {
    const [r, g, b] = hexToRgb(hex);
    return `rgb(${Math.round(r * factor)}, ${Math.round(g * factor)}, ${Math.round(b * factor)})`;
  }

  function contrastText(hex) {
    const [r, g, b] = hexToRgb(hex);
    return (r * 299 + g * 587 + b * 114) / 1000 > 160 ? '#12231b' : '#ffffff';
  }

  function applyBrand() {
    const color = /^#[0-9a-f]{6}$/i.test(state.settings.brandColor) ? state.settings.brandColor : '#173f32';
    const [r, g, b] = hexToRgb(color);
    document.documentElement.style.setProperty('--brand', color);
    document.documentElement.style.setProperty('--brand-rgb', `${r}, ${g}, ${b}`);
    document.documentElement.style.setProperty('--brand-dark', darken(color));
    updateLogo($('#sidebarLogo'), state.settings.logo);
    updateLogo($('#brandLogoPreview'), state.settings.logo);
    updateLogo($('#previewLogo'), state.settings.logo);
    $('#sidebarCompany').textContent = state.settings.companyName;
    $('#mobileCompany').textContent = state.settings.companyName;
    $('#previewCompany').textContent = state.settings.companyName;
    $('#companyName').value = state.settings.companyName;
    $('#companyContact').value = state.settings.contact || '';
    $('#brandColor').value = color;
    $('#brandColorText').value = color;
  }

  function initials(name = state.settings.companyName) {
    return String(name).split(/\s+/).filter(Boolean).slice(0, 2).map(part => part[0]).join('').toUpperCase() || 'GT';
  }

  function updateLogo(element, source, name = state.settings.companyName) {
    if (!element) return;
    if (safeImage(source)) element.innerHTML = `<img src="${escapeHtml(source)}" alt="${escapeHtml(name)} logo">`;
    else element.textContent = initials(name);
  }

  function setView(name) {
    currentView = name;
    $$('.view').forEach(view => view.classList.toggle('active', view.id === `${name}View`));
    $$('.nav-item').forEach(item => item.classList.toggle('active', item.dataset.view === name));
    if (name === 'editor' && !$('#tourId').value) resetEditor();
    if (name === 'dashboard') renderDashboard();
    if (name === 'recycle') renderRecycle();
    if (name === 'brand') applyBrand();
    closeMobileMenu();
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  function renderAll() {
    applyBrand();
    renderFilters();
    renderDashboard();
    renderRecycle();
  }

  function renderFilters() {
    const current = $('#categoryFilter').value;
    $('#categoryFilter').innerHTML = `<option value="">All categories</option>${CATEGORIES.map(category => `<option value="${escapeHtml(category)}">${escapeHtml(category.replace('Sri Lanka ', ''))}</option>`).join('')}`;
    $('#categoryFilter').value = CATEGORIES.includes(current) ? current : '';
    $('#tourCategory').innerHTML = CATEGORIES.map(category => `<option>${escapeHtml(category)}</option>`).join('');
  }

  function renderDashboard() {
    const active = state.tours.filter(tour => tour.status !== 'recycled');
    const customers = new Set(active.map(tour => tour.customerName).filter(Boolean));
    const totalDays = active.reduce((sum, tour) => sum + Number(tour.durationDays || 0), 0);
    const recent = active.filter(tour => Date.now() - new Date(tour.updatedAt).getTime() < 30 * 86400000).length;
    $('#statsGrid').innerHTML = [
      ['Active proposals', active.length],
      ['Customer stories', customers.size],
      ['Journey days', totalDays],
      ['Updated this month', recent]
    ].map(([label, value]) => `<article class="stat-card"><span>${label}</span><strong>${value}</strong></article>`).join('');

    const query = $('#searchTours').value.trim().toLowerCase();
    const category = $('#categoryFilter').value;
    const filtered = active
      .filter(tour => !category || tour.category === category)
      .filter(tour => !query || [tour.tourName, tour.customerName, tour.locations, tour.packageId].join(' ').toLowerCase().includes(query))
      .sort((a, b) => new Date(b.updatedAt) - new Date(a.updatedAt));

    if (!filtered.length) {
      $('#tourGrid').innerHTML = `<div class="empty-state"><strong>No itineraries found</strong><p>Start a new proposal or try a different search.</p><button class="button primary" data-view-jump="editor" type="button">Create itinerary</button></div>`;
      return;
    }

    $('#tourGrid').innerHTML = filtered.map(tour => {
      const image = safeImage(tour.coverImage || tour.days?.find(day => safeImage(day.image))?.image);
      return `<article class="tour-card">
        <div class="tour-image">
          ${image ? `<img src="${escapeHtml(image)}" alt="${escapeHtml(tour.tourName)}">` : `<div class="image-fallback">${escapeHtml(initials(tour.tourName))}</div>`}
          <span class="card-date">${escapeHtml(formatDate(tour.updatedAt))}</span>
        </div>
        <div class="tour-card-body">
          <div class="tour-meta"><span class="pill">${escapeHtml(String(tour.category || 'Tour').replace('Sri Lanka ', ''))}</span><span class="eyebrow">${Number(tour.durationDays || 1)} days</span></div>
          <h3>${escapeHtml(tour.tourName)}</h3>
          <p class="tour-customer">${tour.customerName ? `Tailored for ${escapeHtml(tour.customerName)}` : 'Ready to personalize'}</p>
          <p class="tour-route">${escapeHtml(tour.locations || 'Add the journey route')}</p>
          <div class="card-actions">
            <button class="button primary" data-action="preview" data-id="${escapeHtml(tour.id)}" type="button">Preview</button>
            <button class="button secondary" data-action="edit" data-id="${escapeHtml(tour.id)}" type="button">Edit</button>
            <button class="text-button more-actions" data-action="duplicate" data-id="${escapeHtml(tour.id)}" type="button">Duplicate</button>
            <button class="text-button danger-text" data-action="archive" data-id="${escapeHtml(tour.id)}" type="button" aria-label="Move to recycle bin">Archive</button>
          </div>
        </div>
      </article>`;
    }).join('');
  }

  function resetEditor() {
    $('#tourForm').reset();
    $('#tourId').value = '';
    $('#durationDays').value = 5;
    $('#durationNights').value = 4;
    $('#activityLevel').value = 'Intermediate';
    $('#tourCategory').value = CATEGORIES[0];
    $('#priceCurrency').value = 'LKR';
    $('#editorEyebrow').textContent = 'New proposal';
    $('#editorTitle').textContent = 'Create an itinerary';
    $('#saveStatus').textContent = 'Changes save on submit';
    draftCover = '';
    draftDays = Array.from({ length: 5 }, (_, index) => ({ title: `Day ${index + 1}`, details: '', image: '' }));
    renderDayEditors();
    renderCoverPreview();
  }

  function editTour(id) {
    const tour = state.tours.find(item => item.id === id);
    if (!tour) return;
    $('#tourId').value = tour.id;
    $('#tourName').value = tour.tourName || '';
    $('#tourCategory').value = CATEGORIES.includes(tour.category) ? tour.category : CATEGORIES[0];
    $('#activityLevel').value = tour.activityLevel || 'Intermediate';
    $('#durationDays').value = Number(tour.durationDays || 1);
    $('#durationNights').value = Number(tour.durationNights || 0);
    $('#customerName').value = tour.customerName || '';
    $('#travelDates').value = tour.travelDates || '';
    $('#customerDetails').value = tour.customerDetails || '';
    $('#locations').value = tour.locations || '';
    $('#highlights').value = normalizeLines(tour.highlights).join('\n');
    $('#inclusions').value = normalizeLines(tour.inclusions).join('\n');
    $('#exclusions').value = normalizeLines(tour.exclusions).join('\n');
    $('#priceCurrency').value = tour.priceCurrency || 'LKR';
    $('#priceAmount').value = Number(tour.priceAmount || 0);
    $('#importantNotes').value = tour.importantNotes || '';
    draftCover = safeImage(tour.coverImage);
    draftDays = clone(tour.days || []);
    if (!draftDays.length) draftDays = [{ title: 'Day 1', details: '', image: '' }];
    $('#editorEyebrow').textContent = tour.packageId || 'Proposal';
    $('#editorTitle').textContent = 'Edit itinerary';
    $('#saveStatus').textContent = `Last saved ${formatDate(tour.updatedAt)}`;
    renderDayEditors();
    renderCoverPreview();
    setView('editor');
  }

  function readDayInputs() {
    return $$('.day-editor').map((row, index) => ({
      title: $('.day-title', row).value.trim(),
      details: $('.day-details', row).value.trim(),
      image: safeImage(draftDays[index]?.image)
    }));
  }

  function renderDayEditors() {
    $('#dayPlans').innerHTML = draftDays.map((day, index) => {
      const image = safeImage(day.image);
      return `<article class="day-editor" data-day-index="${index}">
        <button class="remove-day" type="button" data-remove-day="${index}" aria-label="Remove day ${index + 1}">×</button>
        <div class="day-number"><span>Day</span><strong>${index + 1}</strong></div>
        <div class="day-fields">
          <input class="day-title" value="${escapeHtml(day.title || '')}" placeholder="Day title" aria-label="Day ${index + 1} title">
          <textarea class="day-details" placeholder="Describe the experiences, pace, and special moments." aria-label="Day ${index + 1} details">${escapeHtml(day.details || '')}</textarea>
        </div>
        <div class="day-image-box ${image ? 'has-image' : ''}">
          ${image ? `<img src="${escapeHtml(image)}" alt="Day ${index + 1} preview">` : ''}
          <label>${image ? 'Change image' : 'Add image'}<input class="day-image-input" type="file" accept="image/*" hidden></label>
        </div>
      </article>`;
    }).join('');
    $('#durationDays').value = draftDays.length;
    $('#durationNights').value = Math.max(0, Math.min(Number($('#durationNights').value || 0), draftDays.length));
  }

  function renderCoverPreview() {
    const preview = $('#coverPreview');
    preview.innerHTML = draftCover
      ? `<img src="${escapeHtml(draftCover)}" alt="Cover preview">`
      : '<span>No image selected</span>';
  }

  async function resizeImage(file, maxDimension = 1500, quality = .78) {
    if (!file || !file.type.startsWith('image/')) throw new Error('Please choose an image file.');
    const source = await fileToDataUrl(file);
    const image = await loadImage(source);
    const scale = Math.min(1, maxDimension / Math.max(image.width, image.height));
    const canvas = document.createElement('canvas');
    canvas.width = Math.max(1, Math.round(image.width * scale));
    canvas.height = Math.max(1, Math.round(image.height * scale));
    const context = canvas.getContext('2d', { alpha: false });
    context.fillStyle = '#ffffff';
    context.fillRect(0, 0, canvas.width, canvas.height);
    context.drawImage(image, 0, 0, canvas.width, canvas.height);
    return canvas.toDataURL('image/jpeg', quality);
  }

  function fileToDataUrl(file) {
    return new Promise((resolve, reject) => {
      const reader = new FileReader();
      reader.onload = () => resolve(reader.result);
      reader.onerror = reject;
      reader.readAsDataURL(file);
    });
  }

  function loadImage(source) {
    return new Promise((resolve, reject) => {
      const image = new Image();
      image.onload = () => resolve(image);
      image.onerror = reject;
      image.src = source;
    });
  }

  function getTourFromForm() {
    const id = $('#tourId').value || uid();
    const previous = state.tours.find(tour => tour.id === id);
    const days = readDayInputs();
    return {
      id,
      packageId: previous?.packageId || packageId(),
      tourName: $('#tourName').value.trim(),
      category: $('#tourCategory').value,
      activityLevel: $('#activityLevel').value,
      durationDays: days.length,
      durationNights: Math.max(0, Number($('#durationNights').value || 0)),
      customerName: $('#customerName').value.trim(),
      travelDates: $('#travelDates').value.trim(),
      customerDetails: $('#customerDetails').value.trim(),
      locations: $('#locations').value.trim(),
      highlights: normalizeLines($('#highlights').value),
      inclusions: normalizeLines($('#inclusions').value),
      exclusions: normalizeLines($('#exclusions').value),
      priceCurrency: $('#priceCurrency').value,
      priceAmount: Math.max(0, Number($('#priceAmount').value || 0)),
      importantNotes: $('#importantNotes').value.trim(),
      coverImage: safeImage(draftCover),
      days,
      status: previous?.status || 'active',
      createdAt: previous?.createdAt || new Date().toISOString(),
      updatedAt: new Date().toISOString()
    };
  }

  function saveTour(event) {
    event.preventDefault();
    if (!$('#tourName').value.trim()) {
      $('#tourName').focus();
      showToast('Please add a tour name.');
      return;
    }
    const tour = getTourFromForm();
    const index = state.tours.findIndex(item => item.id === tour.id);
    if (index >= 0) state.tours[index] = tour;
    else state.tours.unshift(tour);
    if (saveState('Itinerary saved to this browser')) {
      $('#tourId').value = '';
      setView('dashboard');
    }
  }

  function duplicateTour(id) {
    const source = state.tours.find(tour => tour.id === id);
    if (!source) return;
    const duplicate = clone(source);
    duplicate.id = uid();
    duplicate.packageId = packageId();
    duplicate.tourName = `${source.tourName} — Copy`;
    duplicate.createdAt = new Date().toISOString();
    duplicate.updatedAt = duplicate.createdAt;
    duplicate.status = 'active';
    state.tours.unshift(duplicate);
    saveState('A new editable copy was created');
  }

  function archiveTour(id) {
    const tour = state.tours.find(item => item.id === id);
    if (!tour) return;
    tour.status = 'recycled';
    tour.updatedAt = new Date().toISOString();
    saveState('Moved to the recycle bin');
  }

  function renderRecycle() {
    const recycled = state.tours.filter(tour => tour.status === 'recycled').sort((a, b) => new Date(b.updatedAt) - new Date(a.updatedAt));
    if (!recycled.length) {
      $('#recycleList').innerHTML = '<div class="empty-state"><strong>The recycle bin is empty</strong><p>Archived proposals will appear here.</p></div>';
      return;
    }
    $('#recycleList').innerHTML = recycled.map(tour => `<article class="recycle-row">
      <div><h3>${escapeHtml(tour.tourName)}</h3><p>${escapeHtml(tour.packageId)} · Archived ${escapeHtml(formatDate(tour.updatedAt))}</p></div>
      <div class="recycle-actions">
        <button class="button secondary compact" data-action="restore" data-id="${escapeHtml(tour.id)}" type="button">Restore</button>
        <button class="button danger compact" data-action="delete" data-id="${escapeHtml(tour.id)}" type="button">Delete permanently</button>
      </div>
    </article>`).join('');
  }

  function restoreTour(id) {
    const tour = state.tours.find(item => item.id === id);
    if (!tour) return;
    tour.status = 'active';
    tour.updatedAt = new Date().toISOString();
    saveState('Itinerary restored');
  }

  function deleteTour(id) {
    const tour = state.tours.find(item => item.id === id);
    if (!tour || !confirm(`Permanently delete “${tour.tourName}”? This cannot be undone.`)) return;
    state.tours = state.tours.filter(item => item.id !== id);
    saveState('Itinerary permanently deleted');
  }

  function listHtml(items, type) {
    const normalized = normalizeLines(items);
    if (!normalized.length) return '<li>No items added</li>';
    return normalized.map(item => `<li>${escapeHtml(item)}</li>`).join('');
  }

  function logoHtml(className = 'brand-logo') {
    const logo = safeImage(state.settings.logo);
    return `<span class="${className}">${logo ? `<img src="${escapeHtml(logo)}" alt="${escapeHtml(state.settings.companyName)} logo">` : escapeHtml(initials())}</span>`;
  }

  function openPreview(id) {
    const tour = state.tours.find(item => item.id === id);
    if (!tour) return;
    const cover = safeImage(tour.coverImage || tour.days?.find(day => safeImage(day.image))?.image);
    const customer = tour.customerName || 'Our valued guest';
    const overview = `<section class="pdf-page">
      <div class="pdf-page-inner">
        <header class="pdf-section-head"><p class="pdf-kicker">Your Sri Lankan story</p><h2>Journey overview</h2></header>
        <p class="pdf-intro">${escapeHtml(tour.customerDetails || `A thoughtfully designed journey for ${customer}, balancing discovery, comfort, and space to enjoy each place.`)}</p>
        <div class="pdf-overview-grid">
          <div class="pdf-overview-card"><span>Duration</span><strong>${Number(tour.durationDays || 1)} days / ${Number(tour.durationNights || 0)} nights</strong></div>
          <div class="pdf-overview-card"><span>Travel style</span><strong>${escapeHtml(tour.activityLevel || 'Intermediate')}</strong></div>
          <div class="pdf-overview-card"><span>Travel dates</span><strong>${escapeHtml(tour.travelDates || 'Flexible dates')}</strong></div>
        </div>
        <div class="pdf-route-box"><span>The route</span><p>${escapeHtml(tour.locations || 'A route tailored around you')}</p></div>
        <div class="highlight-grid">${normalizeLines(tour.highlights).map(item => `<div class="highlight-card">${escapeHtml(item)}</div>`).join('') || '<div class="highlight-card">Tailored experiences throughout your journey</div>'}</div>
      </div>
    </section>`;

    const dayPages = (tour.days || []).map((day, index) => {
      const image = safeImage(day.image);
      return `<section class="pdf-page">
        <div class="pdf-page-inner">
          <header class="pdf-section-head"><p class="pdf-kicker">Day ${index + 1} of ${tour.days.length}</p><h2>${escapeHtml(day.title || `Day ${index + 1}`)}</h2></header>
          <article class="day-story">
            ${image ? `<div class="day-story-image"><img src="${escapeHtml(image)}" alt="${escapeHtml(day.title || `Day ${index + 1}`)}"></div>` : ''}
            <div class="day-story-body"><span>Day ${index + 1}</span><h3>${escapeHtml(day.title || `Day ${index + 1}`)}</h3><p>${escapeHtml(day.details || 'This day is ready to be personalized around your interests and preferred pace.')}</p></div>
          </article>
        </div>
      </section>`;
    }).join('');

    const packagePage = `<section class="pdf-page">
      <div class="pdf-page-inner">
        <header class="pdf-section-head"><p class="pdf-kicker">The package</p><h2>Everything at a glance</h2></header>
        <div class="package-columns">
          <div class="package-box included"><h3>What is included</h3><ul>${listHtml(tour.inclusions, 'included')}</ul></div>
          <div class="package-box excluded"><h3>Not included</h3><ul>${listHtml(tour.exclusions, 'excluded')}</ul></div>
        </div>
        <div class="price-panel"><span>Package investment</span><strong>${escapeHtml(currency(tour.priceAmount, tour.priceCurrency))}</strong></div>
        <div class="notes-panel"><h3>Important notes</h3><p>${escapeHtml(tour.importantNotes || 'Your itinerary can be refined before confirmation. Final availability and rates are confirmed at the time of booking.')}</p></div>
      </div>
    </section>`;

    const closing = `<section class="pdf-page pdf-closing">
      ${logoHtml('brand-logo')}
      <p class="pdf-kicker">${escapeHtml(state.settings.companyName)}</p>
      <h2>Your next great story starts here.</h2>
      <p>Thank you for considering this journey. We would love to shape every detail around you.</p>
      <div class="pdf-contact">${escapeHtml(state.settings.contact || state.settings.companyName)}</div>
    </section>`;

    $('#previewModalTitle').textContent = tour.tourName;
    $('#itineraryDocument').style.setProperty('--doc-brand', state.settings.brandColor);
    $('#itineraryDocument').innerHTML = `<section class="pdf-page pdf-cover">
      ${cover ? `<div class="pdf-cover-image"><img src="${escapeHtml(cover)}" alt="${escapeHtml(tour.tourName)}"><div class="pdf-cover-overlay"></div></div>` : ''}
      <div class="pdf-cover-content">
        <div class="pdf-brand">${logoHtml('brand-logo')}<span>${escapeHtml(state.settings.companyName)}</span></div>
        <div class="pdf-cover-main"><p class="pdf-kicker">Tailored for ${escapeHtml(customer)}</p><h1>${escapeHtml(tour.tourName)}</h1><p class="pdf-cover-route">${escapeHtml(tour.locations || 'A journey made for you')}</p></div>
        <div class="pdf-cover-footer">
          <div><span>Duration</span><strong>${Number(tour.durationDays || 1)} days · ${Number(tour.durationNights || 0)} nights</strong></div>
          <div><span>Travel dates</span><strong>${escapeHtml(tour.travelDates || 'Flexible')}</strong></div>
          <div><span>Reference</span><strong>${escapeHtml(tour.packageId || '')}</strong></div>
        </div>
      </div>
    </section>${overview}${dayPages}${packagePage}${closing}`;
    $('#previewModal').hidden = false;
    document.body.classList.add('modal-open');
    $('#closePreview').focus();
  }

  function closePreview() {
    $('#previewModal').hidden = true;
    document.body.classList.remove('modal-open');
  }

  function saveBrand(event) {
    event.preventDefault();
    const color = $('#brandColorText').value.trim();
    if (!/^#[0-9a-f]{6}$/i.test(color)) {
      $('#brandColorText').focus();
      showToast('Use a six-digit color such as #173f32.');
      return;
    }
    state.settings.companyName = $('#companyName').value.trim() || 'Goali Tours';
    state.settings.contact = $('#companyContact').value.trim();
    state.settings.brandColor = color.toLowerCase();
    saveState('Brand settings applied to every itinerary');
  }

  function exportBackup() {
    const payload = JSON.stringify({ ...state, exportedAt: new Date().toISOString() }, null, 2);
    const url = URL.createObjectURL(new Blob([payload], { type: 'application/json' }));
    const link = document.createElement('a');
    link.href = url;
    link.download = `goali-workspace-${new Date().toISOString().slice(0, 10)}.json`;
    document.body.append(link);
    link.click();
    link.remove();
    URL.revokeObjectURL(url);
    showToast('Backup exported');
  }

  async function importBackup(file) {
    try {
      const parsed = JSON.parse(await file.text());
      if (!parsed || !Array.isArray(parsed.tours) || !parsed.settings) throw new Error('Invalid backup');
      if (!confirm(`Import ${parsed.tours.length} itineraries? This replaces the current browser workspace.`)) return;
      state = {
        version: 1,
        settings: {
          companyName: String(parsed.settings.companyName || 'Goali Tours').slice(0, 160),
          contact: String(parsed.settings.contact || '').slice(0, 240),
          brandColor: /^#[0-9a-f]{6}$/i.test(parsed.settings.brandColor) ? parsed.settings.brandColor : '#173f32',
          logo: safeImage(parsed.settings.logo)
        },
        tours: parsed.tours.map(item => ({ ...item, id: String(item.id || uid()), status: item.status === 'recycled' ? 'recycled' : 'active' }))
      };
      saveState('Workspace imported successfully');
      setView('dashboard');
    } catch (error) {
      console.error(error);
      showToast('That file is not a valid Goali workspace backup.');
    } finally {
      $('#importBackup').value = '';
    }
  }

  async function dominantColor(source) {
    try {
      const image = await loadImage(source);
      const canvas = document.createElement('canvas');
      canvas.width = canvas.height = 40;
      const context = canvas.getContext('2d', { willReadFrequently: true });
      context.drawImage(image, 0, 0, 40, 40);
      const pixels = context.getImageData(0, 0, 40, 40).data;
      let r = 0, g = 0, b = 0, count = 0;
      for (let index = 0; index < pixels.length; index += 20) {
        if (pixels[index + 3] < 180) continue;
        const max = Math.max(pixels[index], pixels[index + 1], pixels[index + 2]);
        const min = Math.min(pixels[index], pixels[index + 1], pixels[index + 2]);
        if (max > 245 || max - min < 12) continue;
        r += pixels[index]; g += pixels[index + 1]; b += pixels[index + 2]; count++;
      }
      if (!count) return null;
      const value = [r, g, b].map(total => Math.round(total / count).toString(16).padStart(2, '0')).join('');
      return `#${value}`;
    } catch (_) {
      return null;
    }
  }

  function openMobileMenu() {
    $('.sidebar').classList.add('open');
    $('#sidebarBackdrop').classList.add('open');
  }

  function closeMobileMenu() {
    $('.sidebar').classList.remove('open');
    $('#sidebarBackdrop').classList.remove('open');
  }

  function bindEvents() {
    document.addEventListener('click', event => {
      const jump = event.target.closest('[data-view-jump]');
      if (jump) {
        if (jump.dataset.viewJump === 'editor') resetEditor();
        setView(jump.dataset.viewJump);
        return;
      }
      const nav = event.target.closest('.nav-item');
      if (nav) {
        if (nav.dataset.view === 'editor') resetEditor();
        setView(nav.dataset.view);
        return;
      }
      const action = event.target.closest('[data-action]');
      if (action) {
        const { action: name, id } = action.dataset;
        if (name === 'preview') openPreview(id);
        if (name === 'edit') editTour(id);
        if (name === 'duplicate') duplicateTour(id);
        if (name === 'archive') archiveTour(id);
        if (name === 'restore') restoreTour(id);
        if (name === 'delete') deleteTour(id);
        return;
      }
      const remove = event.target.closest('[data-remove-day]');
      if (remove) {
        draftDays = readDayInputs();
        if (draftDays.length <= 1) return showToast('An itinerary needs at least one day.');
        draftDays.splice(Number(remove.dataset.removeDay), 1);
        renderDayEditors();
      }
    });

    $('#tourForm').addEventListener('submit', saveTour);
    $('#brandForm').addEventListener('submit', saveBrand);
    $('#searchTours').addEventListener('input', renderDashboard);
    $('#categoryFilter').addEventListener('change', renderDashboard);
    $('#mobileMenu').addEventListener('click', openMobileMenu);
    $('#mobileClose').addEventListener('click', closeMobileMenu);
    $('#sidebarBackdrop').addEventListener('click', closeMobileMenu);
    $('#closePreview').addEventListener('click', closePreview);
    $('#printItinerary').addEventListener('click', () => window.print());
    $('#exportBackup').addEventListener('click', exportBackup);
    $('#importBackup').addEventListener('change', event => event.target.files[0] && importBackup(event.target.files[0]));
    $('#addDay').addEventListener('click', () => {
      draftDays = readDayInputs();
      draftDays.push({ title: `Day ${draftDays.length + 1}`, details: '', image: '' });
      renderDayEditors();
      $$('.day-editor').at(-1)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });

    $('#dayPlans').addEventListener('change', async event => {
      if (!event.target.matches('.day-image-input') || !event.target.files[0]) return;
      const row = event.target.closest('.day-editor');
      const index = Number(row.dataset.dayIndex);
      draftDays = readDayInputs();
      showToast('Optimizing image…');
      try {
        draftDays[index].image = await resizeImage(event.target.files[0], 1300, .76);
        renderDayEditors();
        showToast('Day image ready');
      } catch (_) {
        showToast('That image could not be used.');
      }
    });

    $('#coverImage').addEventListener('change', async event => {
      if (!event.target.files[0]) return;
      showToast('Optimizing cover image…');
      try {
        draftCover = await resizeImage(event.target.files[0], 1700, .8);
        renderCoverPreview();
        showToast('Cover image ready');
      } catch (_) {
        showToast('That image could not be used.');
      } finally {
        event.target.value = '';
      }
    });
    $('#removeCover').addEventListener('click', () => { draftCover = ''; renderCoverPreview(); });

    $('#brandColor').addEventListener('input', event => {
      $('#brandColorText').value = event.target.value;
      const [r, g, b] = hexToRgb(event.target.value);
      document.documentElement.style.setProperty('--brand', event.target.value);
      document.documentElement.style.setProperty('--brand-rgb', `${r}, ${g}, ${b}`);
      document.documentElement.style.setProperty('--brand-dark', darken(event.target.value));
    });
    $('#brandColorText').addEventListener('input', event => {
      if (/^#[0-9a-f]{6}$/i.test(event.target.value)) {
        $('#brandColor').value = event.target.value;
        const [r, g, b] = hexToRgb(event.target.value);
        document.documentElement.style.setProperty('--brand', event.target.value);
        document.documentElement.style.setProperty('--brand-rgb', `${r}, ${g}, ${b}`);
        document.documentElement.style.setProperty('--brand-dark', darken(event.target.value));
      }
    });
    $('#companyName').addEventListener('input', event => {
      $('#previewCompany').textContent = event.target.value || 'Your company';
      if (!state.settings.logo) updateLogo($('#previewLogo'), '', event.target.value);
    });
    $('#brandLogo').addEventListener('change', async event => {
      if (!event.target.files[0]) return;
      try {
        const logo = await resizeImage(event.target.files[0], 700, .86);
        state.settings.logo = logo;
        updateLogo($('#brandLogoPreview'), logo);
        updateLogo($('#previewLogo'), logo);
        const color = await dominantColor(logo);
        if (color) {
          $('#brandColor').value = color;
          $('#brandColorText').value = color;
          const [r, g, b] = hexToRgb(color);
          document.documentElement.style.setProperty('--brand', color);
          document.documentElement.style.setProperty('--brand-rgb', `${r}, ${g}, ${b}`);
          document.documentElement.style.setProperty('--brand-dark', darken(color));
          showToast('Logo added and a matching brand color selected');
        } else showToast('Logo added');
      } catch (_) {
        showToast('That logo could not be used.');
      } finally {
        event.target.value = '';
      }
    });
    $('#removeLogo').addEventListener('click', () => {
      state.settings.logo = '';
      updateLogo($('#brandLogoPreview'), '');
      updateLogo($('#previewLogo'), '', $('#companyName').value);
      showToast('Logo removed from the preview. Save to apply.');
    });

    document.addEventListener('keydown', event => {
      if (event.key === 'Escape' && !$('#previewModal').hidden) closePreview();
    });
  }

  function init() {
    renderFilters();
    resetEditor();
    renderAll();
    bindEvents();
    setView('dashboard');
  }

  init();
})();
