# Goali Tours — Browser Itinerary Studio

A polished, database-free tour management prototype that runs entirely in the browser.

## Live system

Open the published GitHub Pages site:

`https://gobiharan1.github.io/goali-tour-management/`

## What works

- Create, edit, duplicate, archive, restore, and delete tour proposals
- Build day-by-day itineraries with customer details, pricing, notes, and images
- Upload a company logo and automatically select a matching brand color
- Choose from seven professional PDF designs or save reusable custom designs
- Use premium travel-magazine, campaign-split, and organic-editorial compositions inspired by modern brochure design
- Customize PDF colors, fonts, spacing, corners, cover style, day layout, section order, and visible content
- Assign a different saved design to each itinerary and choose a workspace default
- Preview a branded, page-safe A4 itinerary
- Download a precise vector PDF with deterministic A4 page templates, or use browser printing as a fallback
- Add a QR code to every PDF that opens a compressed, read-only mobile copy of the itinerary
- Copy the QR sharing link directly from the document preview
- Search and filter the itinerary library
- Export and import a complete JSON workspace backup
- Responsive dashboard for desktop, tablet, and mobile

## Storage

All company settings, tours, custom designs, and uploaded images are saved in the browser's `localStorage`. No PHP, MySQL, server, account, or setup is required.

Browser data is specific to the device and browser profile. Use **Export backup** before clearing browser data or moving to another computer, then use **Import backup** on the new device.

## Run locally

Open `index.html` directly, or serve the folder with any static web server.

## PDF export

Open a proposal, select **Preview**, then choose **Download PDF** for direct export. The exporter follows an FPDF-style layout model: millimetre-based positioning, explicit page templates, controlled image crops, automatic text wrapping, and predictable page numbering.

The Design Studio includes four quick PDF styles plus controls for colors, fonts, spacing, corners, cover design, daily layout, section order, and visible content. You can also select **Print** and use the browser dialog:

- Destination: Save as PDF
- Paper size: A4
- Margins: None
- Background graphics: Enabled

## Technology

HTML, CSS, vanilla JavaScript, jsPDF, and QRCode.js. The libraries are bundled locally, so there are no build tools, database services, PHP runtime, or install steps. jsPDF provides the browser-compatible vector PDF engine; literal FPDF is PHP-only and cannot execute on GitHub Pages.

QR links contain a compressed text-and-design snapshot so customers can read the itinerary on another device without an account. Device-local uploaded images remain in the downloaded PDF but are omitted from the QR snapshot to keep the code reliably scannable.
