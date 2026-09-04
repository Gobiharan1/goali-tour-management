# Goali Tours — Browser Itinerary Studio

A polished, database-free tour management prototype that runs entirely in the browser.

## Live system

Open the published GitHub Pages site:

`https://gobiharan1.github.io/goali-tour-management/`

## What works

- Create, edit, duplicate, archive, restore, and delete tour proposals
- Build day-by-day itineraries with customer details, pricing, notes, and images
- Drag day cards into any position with automatic day-number and default-title updates (plus accessible up/down controls)
- Build each day with reorderable text, activity, hotel, map/location, highlight, and divider blocks
- Work in a bright, Notion-inspired interface using the eye-friendly Poppins typeface
- Move through a guided six-step itinerary editor with sticky section navigation and clearer save feedback
- Use a refined responsive dashboard with richer journey stats, improved search states, and touch-friendly controls
- Reuse locally saved images from the media library and apply them as itinerary covers
- Undo and redo day-builder changes, recover earlier saved versions, autosave drafts, improve writing, and run a completeness check
- Upload a company logo and automatically select a matching PDF color without changing the workspace interface
- Choose from seven professional PDF designs, with Botanical Organic as the ready-to-use default, or save reusable custom designs
- Use premium travel-magazine, campaign-split, and organic-editorial compositions inspired by modern brochure design
- Customize PDF colors, fonts (including Poppins Clean), spacing, corners, cover style, day layout, section order, and visible content
- Assign a different saved design to each itinerary and choose a workspace default
- Preview a branded, page-safe A4 itinerary with space-filling day layouts that avoid large empty areas
- Download a precise vector PDF with deterministic A4 page templates, or use browser printing as a fallback
- Add a QR code to every PDF that opens a compressed, read-only mobile copy of the itinerary
- Copy the QR sharing link directly from the document preview
- Let customers record approval or copy a change-request response from the shared mobile view
- Download one complete tour PDF containing the proposal, quotation, and invoice, or export the quotation and invoice as their own financial documents
- Add deposit percentages, payment methods, payment policy, and cancellation policy to every customer document
- Search and filter the itinerary library
- Add reusable custom package categories directly from the itinerary editor
- Export and import a complete JSON workspace backup
- Responsive dashboard for desktop, tablet, and mobile

## Storage

All company settings, tours, custom designs, and uploaded images are saved in the browser's `localStorage`. No PHP, MySQL, server, account, or setup is required.

Browser data is specific to the device and browser profile. Use **Export backup** before clearing browser data or moving to another computer, then use **Import backup** on the new device.

## Run locally

Open `index.html` directly, or serve the folder with any static web server.

## PDF export

Open a proposal and select **Preview**. Choose **Full tour PDF** for a single document containing the proposal, quotation, and invoice. **Quotation PDF** and **Invoice PDF** each export a focused one-page financial document instead of repeating the itinerary. The exporter follows an FPDF-style layout model: millimetre-based positioning, explicit page templates, controlled image crops, automatic text wrapping, and predictable page numbering.

The Design Studio includes four quick PDF styles plus controls for colors, fonts, spacing, corners, cover design, daily layout, section order, and visible content. You can also select **Print** and use the browser dialog:

- Destination: Save as PDF
- Paper size: A4
- Margins: None
- Background graphics: Enabled

## Technology

HTML, CSS, vanilla JavaScript, jsPDF, and QRCode.js. The libraries are bundled locally, so there are no build tools, database services, PHP runtime, or install steps. jsPDF provides the browser-compatible vector PDF engine; literal FPDF is PHP-only and cannot execute on GitHub Pages.

QR links contain a compressed text-and-design snapshot so customers can read the itinerary on another device without an account. Device-local uploaded images remain in the downloaded PDF but are omitted from the QR snapshot to keep the code reliably scannable.
