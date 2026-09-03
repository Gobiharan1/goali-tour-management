const path = require('path');
const fs = require('fs');
const jspdf = require('../assets/vendor/jspdf.umd.min.js');

jspdf.jsPDF.API.save = function save(filename) {
  fs.writeFileSync(filename, Buffer.from(this.output('arraybuffer')));
  return this;
};
global.window = { jspdf };
require('../assets/js/pdf-export.js');

const tour = {
  tourName: 'Sri Lanka Signature Journey',
  packageId: 'GT-QA-001',
  customerName: 'Alex & Jamie',
  durationDays: 2,
  durationNights: 1,
  travelDates: '12-13 December 2026',
  activityLevel: 'Comfortable',
  locations: 'Colombo | Galle | Ella',
  customerDetails: 'A carefully paced private journey through the coast and hill country.',
  highlights: ['Private guide', 'Boutique stays', 'Scenic rail journey'],
  inclusions: ['Private transport', 'Accommodation', 'Breakfast'],
  exclusions: ['Flights', 'Insurance', 'Personal expenses'],
  priceCurrency: 'USD',
  priceAmount: 2450,
  depositPercent: 30,
  paymentMethods: ['Bank transfer', 'Credit / debit card'],
  paymentPolicy: 'A 30% deposit confirms the booking. The balance is due 30 days before arrival.',
  cancellationPolicy: 'Cancellations more than 30 days before arrival are refundable less committed supplier costs.',
  importantNotes: 'Rates are based on two guests sharing and remain subject to availability.',
  coverImage: '',
  days: [
    { title: 'Coastal welcome', details: 'Arrive in Colombo and continue to Galle for an evening fort walk.', image: '', blocks: [{ type: 'hotel', content: 'Fort Bazaar - Bazaar Bedroom with breakfast.' }, { type: 'activity', content: 'Private guided Galle Fort walk at 5:00 PM.' }] },
    { title: 'Tea country by rail', details: 'Travel into the highlands on one of Asia most scenic rail routes.', image: '', blocks: [{ type: 'highlight', content: 'Reserved observation-class seats, subject to railway confirmation.' }] }
  ]
};

const design = {
  primary: '#173f32', accent: '#d7a94b', paper: '#ffffff', ink: '#14231c',
  fontPair: 'clean', coverStyle: 'minimal', dayLayout: 'text', density: 'comfortable', cornerStyle: 'soft',
  showCover: true, showHighlights: true, showPricing: true, showNotes: true, showClosing: true, showPageNumbers: true,
  sectionOrder: ['overview', 'days', 'package']
};

const base = { tour, design, brand: { companyName: 'Goali Tours', contact: 'hello@goalitours.com', logo: '' }, qrImage: '', priceLabel: 'USD 2,450' };

(async () => {
  for (const documentType of ['proposal', 'quotation', 'invoice']) {
    const filename = path.resolve(__dirname, `../output/pdf/goali-${documentType}-sample.pdf`);
    const result = await window.GoaliPdf.exportItinerary({ ...base, documentType }, filename);
    console.log(`${documentType}: ${result.pages} pages`);
  }
})().catch(error => { console.error(error); process.exitCode = 1; });
