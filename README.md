# Goali Tours — Browser Itinerary Studio

A polished, database-free tour management prototype that runs entirely in the browser.

## Live system

Open the published GitHub Pages site:

`https://gobiharan1.github.io/goali-tour-management/`

## What works

- Create, edit, duplicate, archive, restore, and delete tour proposals
- Build day-by-day itineraries with customer details, pricing, notes, and images
- Upload a company logo and automatically select a matching brand color
- Preview a branded, page-safe A4 itinerary
- Use **Print / Save PDF** for reliable PDF export without Dompdf
- Search and filter the itinerary library
- Export and import a complete JSON workspace backup
- Responsive dashboard for desktop, tablet, and mobile

## Storage

All company settings, tours, and uploaded images are saved in the browser's `localStorage`. No PHP, MySQL, server, account, or setup is required.

Browser data is specific to the device and browser profile. Use **Export backup** before clearing browser data or moving to another computer, then use **Import backup** on the new device.

## Run locally

Open `index.html` directly, or serve the folder with any static web server.

## PDF export

Open a proposal, select **Preview**, then choose **Print / Save PDF**. In the browser print dialog:

- Destination: Save as PDF
- Paper size: A4
- Margins: None
- Background graphics: Enabled

## Technology

HTML, CSS, and vanilla JavaScript. There are no build tools or runtime dependencies.
