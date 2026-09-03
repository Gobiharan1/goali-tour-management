(() => {
  'use strict';

  const PAGE = { width: 210, height: 297 };

  const hex = value => {
    const clean = /^#[0-9a-f]{6}$/i.test(String(value || '')) ? String(value).slice(1) : '173f32';
    return [0, 2, 4].map(index => parseInt(clean.slice(index, index + 2), 16));
  };

  const text = value => String(value ?? '')
    .replace(/[\u2018\u2019]/g, "'")
    .replace(/[\u201C\u201D]/g, '"')
    .replace(/[\u2013\u2014]/g, '-')
    .replace(/\u2192/g, '>')
    .replace(/\u00B7/g, '|')
    .replace(/[^\x20-\x7E\xA0-\xFF\n]/g, '');

  const lines = value => Array.isArray(value)
    ? value.filter(Boolean).map(item => text(item).trim()).filter(Boolean)
    : text(value).split(/\r?\n/).map(item => item.trim()).filter(Boolean);

  const fontSet = design => ({
    heading: ['modern', 'clean'].includes(design.fontPair) ? 'helvetica' : 'times',
    body: 'helvetica'
  });

  const radiusFor = design => design.cornerStyle === 'sharp' ? 0 : design.cornerStyle === 'round' ? 6 : 3;
  const marginFor = design => design.density === 'airy' ? 20 : design.density === 'compact' ? 13 : 17;

  function setFill(doc, color) {
    doc.setFillColor(...color);
  }

  function setDraw(doc, color) {
    doc.setDrawColor(...color);
  }

  function setText(doc, color) {
    doc.setTextColor(...color);
  }

  function mix(first, second, amount = .5) {
    return first.map((value, index) => Math.round(value + (second[index] - value) * amount));
  }

  function contrast(color) {
    const luminance = (0.299 * color[0] + 0.587 * color[1] + 0.114 * color[2]) / 255;
    return luminance > .58 ? [20, 28, 24] : [255, 255, 255];
  }

  function rounded(doc, x, y, width, height, radius, style = 'F') {
    if (radius > 0) doc.roundedRect(x, y, width, height, radius, radius, style);
    else doc.rect(x, y, width, height, style);
  }

  function wrapped(doc, value, x, y, maxWidth, options = {}) {
    const {
      size = 10,
      lineHeight = 1.35,
      color = [20, 35, 28],
      font = 'helvetica',
      style = 'normal',
      maxLines = 20,
      align = 'left'
    } = options;
    doc.setFont(font, style);
    doc.setFontSize(size);
    setText(doc, color);
    let result = doc.splitTextToSize(text(value), maxWidth);
    if (result.length > maxLines) {
      result = result.slice(0, maxLines);
      result[maxLines - 1] = `${result[maxLines - 1].replace(/[. ]+$/, '')}...`;
    }
    doc.text(result, x, y, { lineHeightFactor: lineHeight, align });
    return y + result.length * size * .3528 * lineHeight;
  }

  function label(doc, value, x, y, color, font = 'helvetica') {
    doc.setFont(font, 'bold');
    doc.setFontSize(7.5);
    doc.setCharSpace(.7);
    setText(doc, color);
    doc.text(text(value).toUpperCase(), x, y);
    doc.setCharSpace(0);
  }

  function title(doc, value, x, y, width, fonts, color, size = 28, maxLines = 3) {
    return wrapped(doc, value, x, y, width, {
      size,
      lineHeight: .98,
      color,
      font: fonts.heading,
      style: 'bold',
      maxLines
    });
  }

  function pageBase(doc, palette) {
    setFill(doc, palette.paper);
    doc.rect(0, 0, PAGE.width, PAGE.height, 'F');
  }

  function footer(doc, design, palette, pageNumber, totalPages) {
    if (!design.showPageNumbers) return;
    setDraw(doc, mix(palette.ink, palette.paper, .78));
    doc.setLineWidth(.25);
    doc.line(17, 282, 193, 282);
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(7);
    setText(doc, mix(palette.ink, palette.paper, .42));
    doc.text(`${pageNumber} / ${totalPages}`, 193, 287, { align: 'right' });
  }

  function addPage(doc, palette) {
    doc.addPage('a4', 'portrait');
    pageBase(doc, palette);
  }

  function header(doc, kicker, heading, design, palette, fonts) {
    const margin = marginFor(design);
    label(doc, kicker, margin, 20, palette.primary);
    const end = title(doc, heading, margin, 31, PAGE.width - margin * 2, fonts, palette.ink, 24, 2);
    setFill(doc, palette.accent);
    doc.rect(margin, end + 4, 18, 1.5, 'F');
    return end + 14;
  }

  async function loadImage(source) {
    if (!source || !String(source).startsWith('data:image/') && !String(source).startsWith('assets/')) return null;
    return new Promise(resolve => {
      const image = new Image();
      image.onload = () => {
        try {
          const max = 1800;
          const scale = Math.min(1, max / Math.max(image.naturalWidth, image.naturalHeight));
          const canvas = document.createElement('canvas');
          canvas.width = Math.max(1, Math.round(image.naturalWidth * scale));
          canvas.height = Math.max(1, Math.round(image.naturalHeight * scale));
          const context = canvas.getContext('2d');
          context.fillStyle = '#ffffff';
          context.fillRect(0, 0, canvas.width, canvas.height);
          context.drawImage(image, 0, 0, canvas.width, canvas.height);
          const cropToRatio = ratio => {
            const sourceRatio = image.naturalWidth / image.naturalHeight;
            let sourceX = 0;
            let sourceY = 0;
            let sourceWidth = image.naturalWidth;
            let sourceHeight = image.naturalHeight;
            if (sourceRatio > ratio) {
              sourceWidth = image.naturalHeight * ratio;
              sourceX = (image.naturalWidth - sourceWidth) / 2;
            } else {
              sourceHeight = image.naturalWidth / ratio;
              sourceY = (image.naturalHeight - sourceHeight) / 2;
            }
            let targetWidth = ratio >= 1 ? 1500 : Math.round(1500 * ratio);
            let targetHeight = Math.round(targetWidth / ratio);
            if (targetHeight > 1500) {
              targetHeight = 1500;
              targetWidth = Math.round(targetHeight * ratio);
            }
            const crop = document.createElement('canvas');
            crop.width = Math.max(1, targetWidth);
            crop.height = Math.max(1, targetHeight);
            const cropContext = crop.getContext('2d');
            cropContext.drawImage(image, sourceX, sourceY, sourceWidth, sourceHeight, 0, 0, crop.width, crop.height);
            return {
              data: crop.toDataURL('image/jpeg', .88),
              width: crop.width,
              height: crop.height,
              format: 'JPEG',
              ratio
            };
          };
          resolve({
            data: canvas.toDataURL('image/jpeg', .88),
            width: canvas.width,
            height: canvas.height,
            format: 'JPEG',
            ratio: canvas.width / canvas.height,
            variants: [.62, .707, .9, 1.4, 1.6, 2.2].map(cropToRatio)
          });
        } catch (_) {
          resolve(null);
        }
      };
      image.onerror = () => resolve(null);
      image.src = source;
    });
  }

  function imageCover(doc, image, x, y, width, height, radius = 0) {
    if (!image) return false;
    const targetRatio = width / height;
    const selected = [image, ...(image.variants || [])].reduce((best, candidate) => (
      Math.abs(candidate.ratio - targetRatio) < Math.abs(best.ratio - targetRatio) ? candidate : best
    ));
    doc.addImage(selected.data, selected.format, x, y, width, height, undefined, 'FAST');
    if (radius > 0) {
      setDraw(doc, [255, 255, 255]);
      doc.setLineWidth(.35);
      doc.roundedRect(x, y, width, height, radius, radius, 'D');
    }
    return true;
  }

  function imagePanel(doc, image, x, y, width, height, radius, palette, dayIndex) {
    if (imageCover(doc, image, x, y, width, height, radius)) return;
    setFill(doc, mix(palette.primary, palette.paper, .84));
    rounded(doc, x, y, width, height, radius, 'F');
    setDraw(doc, mix(palette.primary, palette.paper, .66));
    doc.setLineWidth(.35);
    rounded(doc, x, y, width, height, radius, 'D');
    label(doc, 'Journey moment', x + 8, y + 13, palette.primary);
    doc.setFont('times', 'bold');
    doc.setFontSize(Math.min(54, height * 1.2));
    setText(doc, mix(palette.primary, palette.paper, .62));
    doc.text(String(dayIndex + 1).padStart(2, '0'), x + width - 8, y + height - 9, { align: 'right' });
  }

  function brandMark(doc, brand, logo, x, y, palette, options = {}) {
    const { light = false, size = 12 } = options;
    const markColor = light ? [255, 255, 255] : palette.primary;
    setFill(doc, light ? [255, 255, 255] : palette.primary);
    rounded(doc, x, y, size, size, size * .22, 'F');
    if (logo) {
      doc.addImage(logo.data, logo.format, x + 1.2, y + 1.2, size - 2.4, size - 2.4, undefined, 'FAST');
    } else {
      doc.setFont('times', 'bold');
      doc.setFontSize(size * .42);
      setText(doc, light ? palette.primary : contrast(palette.primary));
      doc.text('GT', x + size / 2, y + size * .64, { align: 'center' });
    }
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(8.5);
    doc.setCharSpace(.8);
    setText(doc, markColor);
    doc.text(text(brand.companyName || 'Goali Tours').toUpperCase(), x + size + 4, y + size * .63);
    doc.setCharSpace(0);
  }

  function cover(doc, context, images, palette, fonts) {
    const { tour, design, brand } = context;
    const hero = images.cover;
    const light = [255, 255, 255];
    const onPrimary = contrast(palette.primary);
    const style = design.coverStyle;
    const documentLabel = context.documentType === 'invoice' ? 'Invoice' : context.documentType === 'quotation' ? 'Quotation' : 'Travel proposal';
    const coverKicker = `${documentLabel} for ${tour.customerName || 'our guest'}`;
    pageBase(doc, palette);

    if (['split', 'campaign'].includes(style)) {
      imageCover(doc, hero, 103, 0, 107, 297);
      setFill(doc, palette.paper);
      doc.rect(0, 0, 108, 297, 'F');
      setFill(doc, palette.primary);
      doc.rect(101, 0, 7, 297, 'F');
      brandMark(doc, brand, images.logo, 17, 18, palette);
      label(doc, coverKicker, 17, 104, palette.accent);
      const end = title(doc, tour.tourName, 17, 119, 76, fonts, palette.primary, 31, 4);
      wrapped(doc, tour.locations || 'A journey made for you', 17, end + 8, 76, { size: 10, lineHeight: 1.45, color: mix(palette.ink, palette.paper, .25), maxLines: 5 });
      setFill(doc, palette.accent);
      doc.rect(17, 251, 28, 2, 'F');
      wrapped(doc, `${tour.durationDays || 1} days  |  ${tour.travelDates || 'Flexible dates'}`, 17, 263, 75, { size: 8.5, color: palette.ink, maxLines: 2 });
      return;
    }

    if (style === 'minimal') {
      brandMark(doc, brand, images.logo, 17, 17, palette);
      label(doc, coverKicker, 17, 64, palette.accent);
      const end = title(doc, tour.tourName, 17, 80, 176, fonts, palette.ink, 34, 3);
      wrapped(doc, tour.locations || 'A journey made for you', 17, end + 7, 150, { size: 10, color: mix(palette.ink, palette.paper, .32), maxLines: 2 });
      imageCover(doc, hero, 17, 142, 176, 124, radiusFor(design));
      setFill(doc, palette.primary);
      rounded(doc, 139, 249, 45, 11, 5, 'F');
      wrapped(doc, `${tour.durationDays || 1} DAYS`, 161.5, 256, 39, { size: 7.5, color: onPrimary, font: 'helvetica', style: 'bold', maxLines: 1, align: 'center' });
      return;
    }

    if (['magazine', 'organic'].includes(style)) {
      setFill(doc, style === 'magazine' ? palette.primary : palette.paper);
      doc.rect(0, 0, 210, 297, 'F');
      imageCover(doc, hero, 0, 0, style === 'magazine' ? 88 : 210, style === 'magazine' ? 205 : 165, style === 'magazine' ? 0 : 14);
      if (style === 'magazine') {
        setFill(doc, palette.accent);
        doc.circle(45, 227, 48, 'F');
        brandMark(doc, brand, images.logo, 105, 17, palette, { light: true });
        label(doc, coverKicker, 105, 85, palette.accent);
        const end = title(doc, tour.tourName, 105, 101, 88, fonts, light, 31, 4);
        wrapped(doc, tour.locations || 'A journey made for you', 105, end + 8, 84, { size: 9, color: mix(light, palette.primary, .22), maxLines: 4 });
      } else {
        brandMark(doc, brand, images.logo, 17, 17, palette, { light: true });
        label(doc, coverKicker, 17, 193, palette.accent);
        const end = title(doc, tour.tourName, 17, 210, 170, fonts, palette.ink, 33, 3);
        wrapped(doc, tour.locations || 'A journey made for you', 17, end + 7, 150, { size: 9.5, color: mix(palette.ink, palette.paper, .28), maxLines: 2 });
      }
      return;
    }

    imageCover(doc, hero, 0, 0, 210, 297);
    setFill(doc, palette.primary);
    doc.rect(0, 178, 210, 119, 'F');
    setFill(doc, palette.accent);
    doc.rect(17, 173, 44, 5, 'F');
    brandMark(doc, brand, images.logo, 17, 17, palette, { light: true });
    label(doc, coverKicker, 17, 202, palette.accent);
    const end = title(doc, tour.tourName, 17, 219, 176, fonts, onPrimary, 34, 3);
    wrapped(doc, tour.locations || 'A journey made for you', 17, end + 7, 160, { size: 9.5, color: mix(onPrimary, palette.primary, .2), maxLines: 2 });
    wrapped(doc, `${tour.durationDays || 1} days  |  ${tour.travelDates || 'Flexible dates'}  |  ${tour.packageId || ''}`, 17, 273, 176, { size: 7.5, color: mix(onPrimary, palette.primary, .3), maxLines: 1 });
  }

  function overview(doc, context, images, palette, fonts, pageNumber, totalPages) {
    const { tour, design } = context;
    addPage(doc, palette);
    let y = header(doc, 'Your journey', 'Journey overview', design, palette, fonts);
    y = wrapped(doc, tour.customerDetails || `A thoughtfully designed journey for ${tour.customerName || 'our guest'}.`, marginFor(design), y, PAGE.width - marginFor(design) * 2, {
      size: design.density === 'compact' ? 9 : 10.5,
      lineHeight: 1.55,
      color: mix(palette.ink, palette.paper, .28),
      maxLines: 5
    }) + 8;

    const margin = marginFor(design);
    const gap = 4;
    const cardWidth = (PAGE.width - margin * 2 - gap * 2) / 3;
    const cards = [
      ['Duration', `${tour.durationDays || 1} days / ${tour.durationNights || 0} nights`],
      ['Travel style', tour.activityLevel || 'Personalized'],
      ['Travel dates', tour.travelDates || 'Flexible dates']
    ];
    cards.forEach((card, index) => {
      const x = margin + index * (cardWidth + gap);
      setFill(doc, mix(palette.primary, palette.paper, .93));
      setDraw(doc, mix(palette.primary, palette.paper, .80));
      rounded(doc, x, y, cardWidth, 27, radiusFor(design), 'FD');
      label(doc, card[0], x + 5, y + 8, palette.primary);
      wrapped(doc, card[1], x + 5, y + 16, cardWidth - 10, { size: 8.5, lineHeight: 1.1, color: palette.ink, font: fonts.heading, style: 'bold', maxLines: 2 });
    });
    y += 35;
    setFill(doc, palette.primary);
    rounded(doc, margin, y, PAGE.width - margin * 2, 31, radiusFor(design), 'F');
    label(doc, 'The route', margin + 7, y + 9, mix(contrast(palette.primary), palette.primary, .25));
    wrapped(doc, tour.locations || 'A route tailored around you', margin + 7, y + 20, PAGE.width - margin * 2 - 14, { size: 12, color: contrast(palette.primary), font: fonts.heading, style: 'bold', maxLines: 2 });
    y += 39;

    if (design.showHighlights) {
      const highlights = lines(tour.highlights).slice(0, 6);
      label(doc, 'Journey highlights', margin, y, palette.primary);
      y += 7;
      highlights.forEach((item, index) => {
        const column = index % 2;
        const row = Math.floor(index / 2);
        const x = margin + column * ((PAGE.width - margin * 2 + 4) / 2);
        const boxWidth = (PAGE.width - margin * 2 - 4) / 2;
        setFill(doc, palette.accent);
        doc.circle(x + 2.2, y + row * 14 + 4, 1.5, 'F');
        wrapped(doc, item, x + 7, y + row * 14 + 6, boxWidth - 7, { size: 8.2, lineHeight: 1.2, color: palette.ink, maxLines: 2 });
      });
      y += Math.ceil(highlights.length / 2) * 14 + 4;
    }

    const gallery = images.days.filter(Boolean).slice(0, 3);
    if (gallery.length && y < 235) {
      const imageGap = 3;
      const imageWidth = (PAGE.width - margin * 2 - imageGap * (gallery.length - 1)) / gallery.length;
      const imageHeight = Math.min(42, 271 - y);
      gallery.forEach((image, index) => imageCover(doc, image, margin + index * (imageWidth + imageGap), y, imageWidth, imageHeight, radiusFor(design)));
    }
    footer(doc, design, palette, pageNumber, totalPages);
  }

  function dayPage(doc, context, day, dayIndex, images, palette, fonts, pageNumber, totalPages) {
    const { design } = context;
    addPage(doc, palette);
    const margin = marginFor(design);
    const image = images.days[dayIndex] || images.cover;
    const secondary = images.days[(dayIndex + 1) % Math.max(images.days.length, 1)] || images.cover;
    const layout = design.dayLayout;
    const blockText = (day.blocks || []).filter(block => block.type !== 'divider' && block.content).map(block => `${String(block.type || 'note').toUpperCase()}: ${block.content}`);
    const narrative = [day.details, ...blockText].filter(Boolean).join('\n\n') || 'This day is ready to be personalized around your interests and preferred pace.';

    if (['split', 'poster'].includes(layout)) {
      setFill(doc, palette.primary);
      doc.rect(0, 0, 86, 297, 'F');
      imagePanel(doc, image, 86, 0, 124, 297, 0, palette, dayIndex);
      label(doc, `Day ${dayIndex + 1} of ${context.tour.days.length}`, 14, 28, palette.accent);
      const end = title(doc, day.title || `Day ${dayIndex + 1}`, 14, 45, 62, fonts, contrast(palette.primary), 25, 5);
      wrapped(doc, narrative, 14, end + 12, 61, {
        size: 9.2,
        lineHeight: 1.55,
        color: mix(contrast(palette.primary), palette.primary, .22),
        maxLines: 17
      });
      doc.setFont(fonts.heading, 'bold');
      doc.setFontSize(42);
      setText(doc, mix(palette.accent, palette.primary, .15));
      doc.text(String(dayIndex + 1).padStart(2, '0'), 14, 274);
    } else if (['collage', 'organic'].includes(layout)) {
      label(doc, `Day ${dayIndex + 1} of ${context.tour.days.length}`, margin, 20, palette.primary);
      title(doc, day.title || `Day ${dayIndex + 1}`, margin, 33, 165, fonts, palette.ink, 25, 2);
      imagePanel(doc, image, margin, 58, 112, 120, radiusFor(design) + 4, palette, dayIndex);
      imagePanel(doc, secondary, 135, 73, 58, 75, radiusFor(design) + 5, palette, dayIndex);
      setFill(doc, palette.primary);
      rounded(doc, 75, 166, 118, 85, radiusFor(design) + 3, 'F');
      label(doc, `Day ${dayIndex + 1}`, 87, 184, palette.accent);
      title(doc, day.title || `Day ${dayIndex + 1}`, 87, 198, 92, fonts, contrast(palette.primary), 22, 3);
      wrapped(doc, narrative, 87, 225, 92, { size: 8.7, lineHeight: 1.45, color: mix(contrast(palette.primary), palette.primary, .2), maxLines: 6 });
      doc.setFont(fonts.heading, 'bold');
      doc.setFontSize(56);
      setText(doc, mix(palette.accent, palette.paper, .2));
      doc.text(String(dayIndex + 1).padStart(2, '0'), margin, 244);
    } else {
      doc.setFont(fonts.heading, 'bold');
      doc.setFontSize(24);
      const headingLines = doc.splitTextToSize(text(day.title || `Day ${dayIndex + 1}`), PAGE.width - margin * 2).slice(0, 2);
      const bodyY = headingLines.length > 1 ? 66 : 53;
      const imageHeight = layout === 'text' ? 102 : 204 - bodyY;
      const storyHeight = layout === 'text' ? 95 : 61;
      imagePanel(doc, image, margin, bodyY, PAGE.width - margin * 2, imageHeight, radiusFor(design), palette, dayIndex);
      setFill(doc, mix(palette.primary, palette.paper, .94));
      rounded(doc, margin, bodyY + imageHeight + 7, PAGE.width - margin * 2, storyHeight, radiusFor(design), 'F');
      label(doc, `Day ${dayIndex + 1}`, margin + 8, bodyY + imageHeight + 20, palette.primary);
      wrapped(doc, narrative, margin + 8, bodyY + imageHeight + 33, PAGE.width - margin * 2 - 16, {
        size: 9.5,
        lineHeight: 1.55,
        color: mix(palette.ink, palette.paper, .22),
        maxLines: layout === 'text' ? 13 : 7
      });
      label(doc, `Day ${dayIndex + 1} of ${context.tour.days.length}`, margin, 20, palette.primary);
      title(doc, day.title || `Day ${dayIndex + 1}`, margin, 31, PAGE.width - margin * 2, fonts, palette.ink, 24, 2);
      setFill(doc, palette.accent);
      doc.rect(margin, bodyY - 8, 18, 1.5, 'F');
    }
    footer(doc, design, palette, pageNumber, totalPages);
  }

  function packagePage(doc, context, images, palette, fonts, pageNumber, totalPages) {
    const { tour, design } = context;
    addPage(doc, palette);
    let y = header(doc, 'Your package', 'Everything at a glance', design, palette, fonts);
    const margin = marginFor(design);
    const gap = 5;
    const boxWidth = (PAGE.width - margin * 2 - gap) / 2;
    const listHeight = 60;
    const drawList = (items, x, heading, included) => {
      setFill(doc, mix(included ? palette.primary : palette.accent, palette.paper, .94));
      setDraw(doc, mix(included ? palette.primary : palette.accent, palette.paper, .72));
      rounded(doc, x, y, boxWidth, listHeight, radiusFor(design), 'FD');
      wrapped(doc, heading, x + 7, y + 12, boxWidth - 14, { size: 12.5, color: palette.ink, font: fonts.heading, style: 'bold', maxLines: 1 });
      lines(items).slice(0, 7).forEach((item, index) => {
        setFill(doc, included ? palette.primary : palette.accent);
        doc.circle(x + 8, y + 22 + index * 5.3, 1, 'F');
        wrapped(doc, item, x + 12, y + 24 + index * 5.3, boxWidth - 18, { size: 7.1, lineHeight: 1, color: mix(palette.ink, palette.paper, .18), maxLines: 1 });
      });
    };
    drawList(tour.inclusions, margin, 'What is included', true);
    drawList(tour.exclusions, margin + boxWidth + gap, 'Not included', false);
    y += listHeight + 7;

    if (design.showPricing) {
      setFill(doc, palette.primary);
      rounded(doc, margin, y, PAGE.width - margin * 2, 27, radiusFor(design), 'F');
      label(doc, 'Package investment', margin + 8, y + 9, mix(contrast(palette.primary), palette.primary, .3));
      wrapped(doc, context.priceLabel || `${tour.priceCurrency || 'LKR'} ${Number(tour.priceAmount || 0).toLocaleString()}`, margin + 8, y + 21, PAGE.width - margin * 2 - 16, { size: 15, color: contrast(palette.primary), font: fonts.heading, style: 'bold', maxLines: 1 });
      y += 33;
    }

    if (design.showNotes) {
      setFill(doc, mix(palette.accent, palette.paper, .90));
      rounded(doc, margin, y, PAGE.width - margin * 2, 33, radiusFor(design), 'F');
      label(doc, 'Important notes', margin + 7, y + 9, palette.primary);
      wrapped(doc, tour.importantNotes || 'Your itinerary can be refined before confirmation. Final availability and rates are confirmed at booking.', margin + 7, y + 19, PAGE.width - margin * 2 - 14, { size: 7.7, lineHeight: 1.25, color: mix(palette.ink, palette.paper, .20), maxLines: 3 });
      y += 39;
    }

    if (images.qr) {
      setDraw(doc, mix(palette.primary, palette.paper, .76));
      rounded(doc, margin, y, PAGE.width - margin * 2, 31, radiusFor(design), 'D');
      doc.addImage(images.qr.data, images.qr.format, margin + 4, y + 3, 25, 25, undefined, 'FAST');
      label(doc, 'Take this journey with you', margin + 35, y + 10, palette.primary);
      wrapped(doc, 'Scan to read on your phone', margin + 35, y + 21, PAGE.width - margin * 2 - 42, { size: 10.5, color: palette.ink, font: fonts.heading, style: 'bold', maxLines: 1 });
      y += 37;
    }

    const policyHeight = Math.max(37, Math.min(58, 274 - y));
    const columnWidth = (PAGE.width - margin * 2 - 5) / 2;
    setFill(doc, mix(palette.primary, palette.paper, .95));
    setDraw(doc, mix(palette.primary, palette.paper, .76));
    rounded(doc, margin, y, PAGE.width - margin * 2, policyHeight, radiusFor(design), 'FD');
    label(doc, 'Payment and booking policies', margin + 7, y + 9, palette.primary);
    const methods = lines(tour.paymentMethods).join(' | ') || 'Contact us to arrange payment.';
    const deposit = Math.max(0, Math.min(100, Number(tour.depositPercent || 0)));
    const amount = Number(tour.priceAmount || 0);
    const depositAmount = amount * deposit / 100;
    const paymentSummary = context.documentType === 'invoice'
      ? `${deposit}% deposit ${tour.priceCurrency || 'LKR'} ${depositAmount.toLocaleString(undefined, { maximumFractionDigits: 0 })} | Balance ${tour.priceCurrency || 'LKR'} ${(amount - depositAmount).toLocaleString(undefined, { maximumFractionDigits: 0 })}`
      : `${deposit}% deposit | ${methods}`;
    wrapped(doc, paymentSummary, margin + 7, y + 19, columnWidth - 10, { size: 8.5, color: palette.ink, font: fonts.heading, style: 'bold', maxLines: 2 });
    const paymentBody = `${context.documentType === 'invoice' ? `${methods}. ` : ''}${tour.paymentPolicy || 'A deposit confirms the booking and the balance is due before arrival.'}`;
    wrapped(doc, paymentBody, margin + 7, y + 31, columnWidth - 10, { size: 7, lineHeight: 1.2, color: mix(palette.ink, palette.paper, .2), maxLines: policyHeight > 45 ? 3 : 2 });
    label(doc, 'Cancellation', margin + columnWidth + 8, y + 19, palette.primary);
    wrapped(doc, tour.cancellationPolicy || 'Cancellation charges depend on notice and committed supplier costs.', margin + columnWidth + 8, y + 30, columnWidth - 14, { size: 7, lineHeight: 1.2, color: mix(palette.ink, palette.paper, .2), maxLines: policyHeight > 45 ? 4 : 2 });
    footer(doc, design, palette, pageNumber, totalPages);
  }

  function closingPage(doc, context, images, palette, fonts, pageNumber, totalPages) {
    addPage(doc, palette);
    setFill(doc, palette.primary);
    doc.rect(0, 0, 210, 297, 'F');
    setFill(doc, palette.accent);
    doc.circle(184, 28, 44, 'F');
    doc.circle(24, 276, 34, 'F');
    brandMark(doc, context.brand, images.logo, 82, 49, palette, { light: true, size: 15 });
    label(doc, context.brand.companyName || 'Goali Tours', 82, 106, palette.accent);
    wrapped(doc, 'Your next great story starts here.', 105, 127, 142, { size: 30, lineHeight: 1.02, color: contrast(palette.primary), font: fonts.heading, style: 'bold', maxLines: 4, align: 'center' });
    wrapped(doc, 'Thank you for considering this journey. We would love to shape every detail around you.', 105, 175, 128, { size: 10, lineHeight: 1.55, color: mix(contrast(palette.primary), palette.primary, .22), maxLines: 4, align: 'center' });
    setDraw(doc, mix(contrast(palette.primary), palette.primary, .64));
    doc.line(55, 214, 155, 214);
    wrapped(doc, context.brand.contact || context.brand.companyName || 'Goali Tours', 105, 226, 120, { size: 8.5, color: contrast(palette.primary), maxLines: 2, align: 'center' });
    footer(doc, context.design, { ...palette, ink: contrast(palette.primary), paper: palette.primary }, pageNumber, totalPages);
  }

  function countPages(context) {
    const { tour, design } = context;
    return (design.showCover ? 1 : 0) + design.sectionOrder.reduce((count, section) => {
      if (section === 'days') return count + (tour.days || []).length;
      return count + 1;
    }, 0) + (design.showClosing ? 1 : 0);
  }

  async function preload(context) {
    const coverSource = context.tour.coverImage || context.tour.days?.find(day => day.image)?.image || '';
    const [cover, logo, qr, ...days] = await Promise.all([
      loadImage(coverSource),
      loadImage(context.brand.logo),
      loadImage(context.qrImage),
      ...(context.tour.days || []).map(day => loadImage(day.image))
    ]);
    return { cover, logo, qr, days };
  }

  async function exportItinerary(rawContext, filename = 'goali-itinerary.pdf') {
    if (!window.jspdf?.jsPDF) throw new Error('The precise PDF engine is unavailable.');
    const context = JSON.parse(JSON.stringify(rawContext));
    context.tour.days = Array.isArray(context.tour.days) ? context.tour.days : [];
    context.design.sectionOrder = Array.isArray(context.design.sectionOrder) ? context.design.sectionOrder : ['overview', 'days', 'package'];
    const palette = {
      primary: hex(context.design.primary),
      accent: hex(context.design.accent),
      paper: hex(context.design.paper),
      ink: hex(context.design.ink)
    };
    const fonts = fontSet(context.design);
    const images = await preload(context);
    const totalPages = countPages(context);
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4', compress: true, putOnlyUsedFonts: true });
    doc.setProperties({
      title: text(`${context.documentType || 'Proposal'} - ${context.tour.tourName || 'Goali itinerary'}`),
      subject: text(`${context.documentType || 'Travel proposal'} for ${context.tour.customerName || 'guest'}`),
      author: text(context.brand.companyName || 'Goali Tours'),
      creator: 'Goali Tours Itinerary Studio'
    });

    let pageNumber = 0;
    if (context.design.showCover) {
      pageNumber += 1;
      cover(doc, context, images, palette, fonts);
      footer(doc, context.design, palette, pageNumber, totalPages);
    } else {
      pageBase(doc, palette);
      doc.deletePage(1);
    }

    for (const section of context.design.sectionOrder) {
      if (section === 'overview') {
        pageNumber += 1;
        overview(doc, context, images, palette, fonts, pageNumber, totalPages);
      }
      if (section === 'days') {
        for (const [index, day] of context.tour.days.entries()) {
          pageNumber += 1;
          dayPage(doc, context, day, index, images, palette, fonts, pageNumber, totalPages);
        }
      }
      if (section === 'package') {
        pageNumber += 1;
        packagePage(doc, context, images, palette, fonts, pageNumber, totalPages);
      }
    }
    if (context.design.showClosing) {
      pageNumber += 1;
      closingPage(doc, context, images, palette, fonts, pageNumber, totalPages);
    }
    doc.save(filename);
    return { pages: totalPages };
  }

  window.GoaliPdf = { exportItinerary };
})();
