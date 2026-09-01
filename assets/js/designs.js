(() => {
  'use strict';

  const SECTION_LABELS = {
    overview: 'Journey overview',
    days: 'Day-by-day story',
    package: 'Package & pricing'
  };

  const FONT_PAIRS = {
    editorial: {
      label: 'Editorial',
      heading: '"Playfair Display", Georgia, serif',
      body: '"DM Sans", ui-sans-serif, system-ui, sans-serif'
    },
    elegant: {
      label: 'Elegant',
      heading: '"Cormorant Garamond", Georgia, serif',
      body: '"Montserrat", ui-sans-serif, system-ui, sans-serif'
    },
    modern: {
      label: 'Modern',
      heading: '"Montserrat", ui-sans-serif, system-ui, sans-serif',
      body: '"DM Sans", ui-sans-serif, system-ui, sans-serif'
    },
    clean: {
      label: 'Poppins Clean',
      heading: '"Poppins", ui-sans-serif, system-ui, sans-serif',
      body: '"Poppins", ui-sans-serif, system-ui, sans-serif'
    },
    classic: {
      label: 'Classic',
      heading: 'Georgia, "Times New Roman", serif',
      body: 'Arial, Helvetica, sans-serif'
    }
  };

  const BASE_DESIGN = {
    id: 'editorial-forest',
    name: 'Editorial Forest',
    builtIn: true,
    primary: '#173f32',
    accent: '#d7a94b',
    paper: '#ffffff',
    ink: '#14231c',
    fontPair: 'editorial',
    coverStyle: 'full',
    dayLayout: 'top',
    density: 'comfortable',
    cornerStyle: 'soft',
    showCover: true,
    showHighlights: true,
    showPricing: true,
    showNotes: true,
    showClosing: true,
    showPageNumbers: true,
    sectionOrder: ['overview', 'days', 'package']
  };

  const PRESETS = [
    BASE_DESIGN,
    {
      ...BASE_DESIGN,
      id: 'coastal-light',
      name: 'Coastal Light',
      primary: '#176b72',
      accent: '#e39b61',
      paper: '#fffdf8',
      ink: '#19343a',
      fontPair: 'elegant',
      coverStyle: 'split',
      dayLayout: 'split',
      density: 'airy',
      cornerStyle: 'round'
    },
    {
      ...BASE_DESIGN,
      id: 'modern-terracotta',
      name: 'Modern Terracotta',
      primary: '#9d4f3f',
      accent: '#e6b85c',
      paper: '#fffaf5',
      ink: '#33241f',
      fontPair: 'modern',
      coverStyle: 'minimal',
      dayLayout: 'split',
      density: 'comfortable',
      cornerStyle: 'sharp'
    },
    {
      ...BASE_DESIGN,
      id: 'midnight-luxe',
      name: 'Midnight Luxe',
      primary: '#17243d',
      accent: '#c8a55a',
      paper: '#fbfaf7',
      ink: '#172033',
      fontPair: 'classic',
      coverStyle: 'bold',
      dayLayout: 'text',
      density: 'compact',
      cornerStyle: 'sharp'
    },
    {
      ...BASE_DESIGN,
      id: 'tropical-magazine',
      name: 'Tropical Magazine',
      primary: '#075a55',
      accent: '#c9ec5b',
      paper: '#f6f1e8',
      ink: '#15342d',
      fontPair: 'modern',
      coverStyle: 'magazine',
      dayLayout: 'collage',
      density: 'comfortable',
      cornerStyle: 'round'
    },
    {
      ...BASE_DESIGN,
      id: 'gallery-campaign',
      name: 'Gallery Campaign',
      primary: '#087d73',
      accent: '#d96c32',
      paper: '#fffdf8',
      ink: '#17352f',
      fontPair: 'modern',
      coverStyle: 'campaign',
      dayLayout: 'poster',
      density: 'airy',
      cornerStyle: 'sharp'
    },
    {
      ...BASE_DESIGN,
      id: 'botanical-organic',
      name: 'Botanical Organic',
      primary: '#325948',
      accent: '#e36e35',
      paper: '#f4ecdc',
      ink: '#181c18',
      fontPair: 'elegant',
      coverStyle: 'organic',
      dayLayout: 'organic',
      density: 'airy',
      cornerStyle: 'round'
    }
  ];

  function clone(value) {
    return JSON.parse(JSON.stringify(value));
  }

  function validHex(value, fallback) {
    return /^#[0-9a-f]{6}$/i.test(String(value || '')) ? String(value).toLowerCase() : fallback;
  }

  function normalize(design = {}) {
    const merged = { ...clone(BASE_DESIGN), ...clone(design) };
    merged.primary = validHex(merged.primary, BASE_DESIGN.primary);
    merged.accent = validHex(merged.accent, BASE_DESIGN.accent);
    merged.paper = validHex(merged.paper, BASE_DESIGN.paper);
    merged.ink = validHex(merged.ink, BASE_DESIGN.ink);
    merged.fontPair = FONT_PAIRS[merged.fontPair] ? merged.fontPair : BASE_DESIGN.fontPair;
    merged.coverStyle = ['full', 'split', 'minimal', 'bold', 'magazine', 'campaign', 'organic'].includes(merged.coverStyle) ? merged.coverStyle : 'full';
    merged.dayLayout = ['top', 'split', 'text', 'collage', 'poster', 'organic'].includes(merged.dayLayout) ? merged.dayLayout : 'top';
    merged.density = ['airy', 'comfortable', 'compact'].includes(merged.density) ? merged.density : 'comfortable';
    merged.cornerStyle = ['sharp', 'soft', 'round'].includes(merged.cornerStyle) ? merged.cornerStyle : 'soft';
    const requestedOrder = Array.isArray(merged.sectionOrder) ? merged.sectionOrder : BASE_DESIGN.sectionOrder;
    merged.sectionOrder = [...new Set(requestedOrder.filter(section => SECTION_LABELS[section]))];
    Object.keys(SECTION_LABELS).forEach(section => {
      if (!merged.sectionOrder.includes(section)) merged.sectionOrder.push(section);
    });
    ['showCover', 'showHighlights', 'showPricing', 'showNotes', 'showClosing', 'showPageNumbers'].forEach(key => {
      merged[key] = merged[key] !== false;
    });
    return merged;
  }

  function all(customDesigns = []) {
    const custom = Array.isArray(customDesigns)
      ? customDesigns.map(design => ({ ...normalize(design), builtIn: false }))
      : [];
    return [...PRESETS.map(preset => normalize(preset)), ...custom];
  }

  function find(id, customDesigns = []) {
    return all(customDesigns).find(design => design.id === id) || normalize(PRESETS[0]);
  }

  function createCustom(source = BASE_DESIGN, name = 'My custom design') {
    return {
      ...normalize(source),
      id: typeof crypto !== 'undefined' && crypto.randomUUID ? crypto.randomUUID() : `design-${Date.now()}`,
      name,
      builtIn: false,
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString()
    };
  }

  window.GoaliDesigns = {
    BASE_DESIGN: normalize(BASE_DESIGN),
    PRESETS: PRESETS.map(preset => normalize(preset)),
    FONT_PAIRS,
    SECTION_LABELS,
    normalize,
    all,
    find,
    createCustom
  };
})();
