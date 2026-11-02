#!/usr/bin/env node
/**
 * Builds static, Google-indexable rental category pages in /rentals/
 * from the `rentalItems` array in app.js.
 *
 * Usage (from repo root):  node tools/build-rental-pages.js
 * Re-run whenever items or prices change in app.js.
 */
const fs = require('fs');
const path = require('path');

const ROOT = path.join(__dirname, '..');
const SITE = 'https://petalsparadiseevents.com';
const PHONE = '+1-848-448-6993';
const TEL = '+18484486993';

// ── Load rentalItems from app.js ──────────────────────────────────────────
const appJs = fs.readFileSync(path.join(ROOT, 'app.js'), 'utf8');
const start = appJs.indexOf('const rentalItems = [');
const end = appJs.indexOf('];', start) + 2;
const rentalItems = new Function(appJs.slice(start, end).replace('const rentalItems =', 'return'))();

// ── Reuse styles + footer from an existing location page ──────────────────
const tpl = fs.readFileSync(path.join(ROOT, 'locations/ashburn-va.html'), 'utf8');
const styleBlock = tpl.slice(tpl.indexOf('<style>'), tpl.indexOf('</style>') + 8);
const footerBlock = tpl
  .slice(tpl.indexOf('<!-- Footer -->'), tpl.indexOf('<!-- SMS Widget -->'))
  .replace(/serving Ashburn, Loudoun County, and all of Northern Virginia/g, 'serving Ashburn, Aldie, Loudoun County, and all of Northern Virginia')
  .replace(/© 2025/, `© ${new Date().getFullYear()}`)
  .replace(/Serving <strong style="color:#d4af37;">Ashburn, VA<\/strong>/, 'Serving <strong style="color:#d4af37;">Loudoun County &amp; the DMV</strong>');

const extraCss = `
  <style>
    .product-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); gap:24px; }
    .product-card { background:#0d0f12; border:1px solid rgba(255,255,255,0.08); border-radius:16px; overflow:hidden; display:flex; flex-direction:column; transition:border-color .3s, transform .3s; }
    .product-card:hover { border-color:rgba(212,175,55,0.5); transform:translateY(-4px); }
    .product-card img { width:100%; height:220px; object-fit:cover; background:#1a1d24; }
    .product-body { padding:20px; display:flex; flex-direction:column; gap:10px; flex:1; }
    .product-body h3 { font-family:'Playfair Display',serif; font-size:1.1rem; color:#f8fafc; margin:0; }
    .product-body p { font-family:'Outfit',sans-serif; font-size:.88rem; color:#cbd5e1; line-height:1.6; margin:0; flex:1; }
    .product-price { font-family:'Montserrat',sans-serif; font-weight:700; color:#d4af37; font-size:1.05rem; }
    .product-cta { align-self:flex-start; margin-top:6px; padding:10px 22px !important; font-size:.82rem !important; }
    .cat-nav { display:flex; flex-wrap:wrap; gap:10px; justify-content:center; margin-top:28px; }
  </style>`;

// ── Category page definitions ─────────────────────────────────────────────
const pages = [
  {
    slug: 'chair-table-rentals',
    navLabel: 'Chairs & Tables',
    match: i => ['Chairs', 'Tables'].includes(i.category),
    title: 'Chair & Table Rentals in Ashburn, Aldie & Loudoun County VA',
    h1: 'Chair &amp; Table <span class="gold">Rentals</span>',
    desc: 'Rent folding chairs from $2, round & rectangular tables, cocktail tables and accent seating in Ashburn, Aldie, South Riding & Northern Virginia. Delivery & setup available.',
    intro: 'From backyard birthdays to 200-guest weddings, we rent clean, sturdy folding chairs, round and rectangular tables, cocktail tables, loveseats and hand-carved accent chairs across Loudoun County and the DMV. Bulk pricing: chairs drop to $1.50 each when you rent 30 or more.',
    faqs: [
      ['How much does it cost to rent chairs and tables in Ashburn, VA?', 'Adult folding chairs start at $2 each ($1.50 each for 30+), rectangular folding tables at $8, round tables at $12 and cocktail tables with cloths at $11. Delivery and setup are quoted based on your address.'],
      ['How many chairs and tables do I need for 100 guests?', 'For 100 seated guests plan on 100 chairs and about 13 round 60-inch tables (8 per table) or 13 rectangular 6-ft tables. Add 2–4 extra tables for food, gifts and a cake.'],
      ['Do you deliver and set up chairs and tables?', 'Yes. We offer delivery, setup and pickup across Ashburn, Aldie, South Riding, Chantilly, Sterling, Leesburg and the wider Northern Virginia area, or you can pick up yourself to save on delivery.'],
    ],
  },
  {
    slug: 'tent-rentals',
    navLabel: 'Tents',
    match: i => i.category === 'Tents',
    title: 'Tent Rentals in Ashburn, Aldie & Northern Virginia | 10x20 & 16x26',
    h1: 'Party &amp; Wedding <span class="gold">Tent Rentals</span>',
    desc: 'Rent 10x20 and 16x26 party & wedding tents in Ashburn, Aldie, South Riding and Loudoun County VA. Great for graduations, backyard parties and outdoor ceremonies.',
    intro: 'Keep your guests shaded and dry with our 10x20 canopy tent and 16x26 wedding tent — ideal for graduation parties, backyard birthdays, Haldi and Mehandi ceremonies, and outdoor receptions across Northern Virginia.',
    faqs: [
      ['What size tent do I need for my party?', 'A 10x20 tent comfortably covers about 20 seated guests or 30 standing. A 16x26 tent covers roughly 40 seated guests or 60 standing. Tell us your headcount and layout and we will recommend the right size.'],
      ['How much does a tent rental cost in Ashburn, VA?', 'Our 10x20 tent rents for $100 and our 16x26 wedding tent for $150. Setup and delivery are quoted by location.'],
      ['Can the tent be set up on grass or a driveway?', 'Yes, our tents can be set up on lawns and on hard surfaces such as driveways and patios. Let us know the surface when booking so we bring the right anchoring.'],
    ],
  },
  {
    slug: 'backdrop-rentals',
    navLabel: 'Backdrops',
    match: i => i.category === 'Backdrops',
    title: 'Backdrop & Photo Prop Rentals in Ashburn & Loudoun County VA',
    h1: 'Backdrop &amp; Photo Prop <span class="gold">Rentals</span>',
    desc: 'Rent event backdrops, pipe & drape stands, Seemantham / baby shower backdrops and newborn photo props in Ashburn, Aldie and Northern Virginia.',
    intro: 'Create the photo spot everyone remembers. We rent ready-to-style event backdrops, heavy-duty pipe and drape stands, Seemantham and baby shower backdrops, and dreamy newborn photo props for celebrations across the DMV.',
    faqs: [
      ['Can you customize a backdrop for my event?', 'Yes. Our backdrops can be styled to your theme and colors with florals, drapes and signage. Contact us with your event details for a custom quote.'],
      ['Do you have backdrops for baby showers and Seemantham?', 'Yes, our Seemantham / Baby Shower backdrop is one of our most popular rentals and can be paired with a loveseat or accent chair for the guest of honor.'],
      ['Do you set up the backdrop for me?', 'We offer setup and takedown across Northern Virginia. Self-pickup is also available for pipe and drape stands.'],
    ],
  },
  {
    slug: 'marquee-letters-neon-signs',
    navLabel: 'Marquee & Neon',
    match: i => ['Marquee Letters', 'Neon Signs'].includes(i.category),
    title: 'Marquee Letter & Neon Sign Rentals in Ashburn & Northern Virginia',
    h1: 'Marquee Letters &amp; <span class="gold">Neon Signs</span>',
    desc: 'Rent light-up 4ft marquee numbers, GRAD marquee letters and Happy Birthday / Congrats Grad neon signs in Ashburn, Aldie and Loudoun County VA. From $10.',
    intro: 'Light up your celebration with giant 4-ft marquee numbers, GRAD marquee letters and glowing neon signs — perfect for birthdays, anniversaries and Class of ' + new Date().getFullYear() + ' graduation parties.',
    faqs: [
      ['How much are marquee number rentals?', '4-ft light-up marquee numbers rent for $20 each, the GRAD marquee letter set for $40, and neon signs from $10.'],
      ['Do marquee letters need a power outlet?', 'Yes, our marquee letters and neon signs plug into a standard outlet. For outdoor setups we recommend an extension cord rated for outdoor use.'],
      ['Are marquee letters good for graduation parties?', 'Absolutely — GRAD marquee letters and the Congrats Grad neon sign are among our most-booked items every graduation season, so reserve early.'],
    ],
  },
  {
    slug: 'haldi-mehandi-indian-decor-rentals',
    navLabel: 'Haldi & Traditional Decor',
    match: i => !/grad/i.test(i.title) && (i.category === 'Traditional Decor' || /haldi|mehandi|seemantham/i.test(i.title)),
    title: 'Haldi, Mehandi & Indian Wedding Decor Rentals in Northern Virginia',
    h1: 'Haldi, Mehandi &amp; <span class="gold">Traditional Decor</span>',
    desc: 'Rent Haldi Urli / Maiyan tubs, Haldi backdrop & staircase decor, Mehandi umbrellas, pedestals and centerpieces in Ashburn, Aldie & Loudoun County VA.',
    intro: 'Celebrate your traditions beautifully. We specialize in South Asian ceremony decor — Haldi Urli and Maiyan tubs, marigold Haldi backdrops and staircase drapes, colorful Mehandi umbrellas, gold pedestals, centerpieces and easels — for families across Northern Virginia and the DMV.',
    faqs: [
      ['Do you rent Haldi decor in Northern Virginia?', 'Yes. We rent Haldi Urli / Maiyan tubs and a full Haldi backdrop and staircase floral decor package across Ashburn, Aldie, South Riding, Chantilly and the wider DMV.'],
      ['Can the Haldi backdrop be customized?', 'Yes, the Haldi / Maiyan backdrop and staircase decor is fully customizable to your theme and colors. Pricing varies by size and design — contact us for a quote.'],
      ['Do you have Mehandi umbrellas?', 'Yes, colorful traditional Mehandi umbrellas rent for $3 each and pair beautifully with our Mehandi backdrops.'],
    ],
  },
];

// ── Helpers ───────────────────────────────────────────────────────────────
const esc = s => String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
const absImg = p => SITE + '/' + encodeURI(p.replace(/^\.\//, ''));
const relImg = p => '../' + encodeURI(p.replace(/^\.\//, ''));
const priceText = p => (typeof p === 'number' ? `$${p}` : 'Custom quote');

function buildPage(page) {
  const items = rentalItems.filter(page.match);
  const url = `${SITE}/rentals/${page.slug}.html`;

  const itemListSchema = {
    '@context': 'https://schema.org',
    '@type': 'ItemList',
    name: page.title,
    itemListElement: items.map((it, idx) => ({
      '@type': 'ListItem',
      position: idx + 1,
      item: {
        '@type': 'Product',
        name: it.title,
        description: it.desc,
        image: absImg(it.img),
        brand: { '@type': 'Brand', name: 'Petals Paradise Events' },
        ...(typeof it.price === 'number' && {
          offers: { '@type': 'Offer', price: it.price, priceCurrency: 'USD', availability: 'https://schema.org/InStock', url },
        }),
      },
    })),
  };
  const faqSchema = {
    '@context': 'https://schema.org',
    '@type': 'FAQPage',
    mainEntity: page.faqs.map(([q, a]) => ({ '@type': 'Question', name: q, acceptedAnswer: { '@type': 'Answer', text: a } })),
  };
  const breadcrumbSchema = {
    '@context': 'https://schema.org',
    '@type': 'BreadcrumbList',
    itemListElement: [
      { '@type': 'ListItem', position: 1, name: 'Home', item: `${SITE}/` },
      { '@type': 'ListItem', position: 2, name: 'Rentals', item: `${SITE}/#rentals` },
      { '@type': 'ListItem', position: 3, name: page.navLabel, item: url },
    ],
  };

  const cards = items.map(it => `
        <article class="product-card">
          <img src="${relImg(it.img)}" alt="${esc(it.title)} rental - Petals Paradise Events" loading="lazy" width="400" height="220" />
          <div class="product-body">
            <h3>${esc(it.title)}</h3>
            <p>${esc(it.desc)}</p>
            <div class="product-price">${priceText(it.price)}</div>
            <a class="btn-primary product-cta" href="${SITE}/#rentals">Reserve Online →</a>
          </div>
        </article>`).join('');

  const faqHtml = page.faqs.map(([q, a]) => `
        <div class="faq-item">
          <button class="faq-question" aria-expanded="false"><span>${esc(q)}</span><span class="faq-chevron">▼</span></button>
          <div class="faq-answer"><div class="faq-answer-inner">${esc(a)}</div></div>
        </div>`).join('');

  const otherCats = pages.filter(p => p.slug !== page.slug).map(p =>
    `<a class="int-link" href="${p.slug}.html"><span class="int-arrow">→</span><span>${esc(p.navLabel)}</span></a>`).join('\n        ');

  return `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>${esc(page.title)} | Petals Paradise Events</title>
  <meta name="description" content="${esc(page.desc)}" />
  <meta name="robots" content="index, follow" />
  <link rel="canonical" href="${url}" />
  <meta property="og:type" content="website" />
  <meta property="og:title" content="${esc(page.title)}" />
  <meta property="og:description" content="${esc(page.desc)}" />
  <meta property="og:url" content="${url}" />
  ${items[0] ? `<meta property="og:image" content="${absImg(items[0].img)}" />` : ''}
  <meta property="og:site_name" content="Petals Paradise Events" />
  <script type="application/ld+json">${JSON.stringify(itemListSchema)}</script>
  <script type="application/ld+json">${JSON.stringify(faqSchema)}</script>
  <script type="application/ld+json">${JSON.stringify(breadcrumbSchema)}</script>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Outfit:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="../styles.css" />
  ${styleBlock}
  ${extraCss}
</head>
<body>
<div class="promo-banner" role="banner">
  <span>🎉 Serving Ashburn, Aldie &amp; all of Northern Virginia —</span>
  <span><a href="tel:${TEL}">Call ${PHONE}</a> to reserve today!</span>
</div>
<nav class="navbar" role="navigation" aria-label="Main navigation">
  <a href="${SITE}/" class="navbar-logo">Petals Paradise <span>Events</span></a>
  <ul class="nav-links">
    <li><a href="${SITE}/">Home</a></li>
    <li><a href="${SITE}/#rentals">Rentals</a></li>
    <li><a href="${SITE}/#gallery">Gallery</a></li>
    <li><a href="${SITE}/#about">About</a></li>
    <li><a href="tel:${TEL}" class="nav-cta">📞 Call Us</a></li>
  </ul>
</nav>
<main id="main-content">
  <header class="hero-location">
    <nav class="breadcrumb" aria-label="Breadcrumb"><a href="${SITE}/">Home</a> › <a href="${SITE}/#rentals">Rentals</a> › <span>${esc(page.navLabel)}</span></nav>
    <h1>${page.h1}</h1>
    <p class="hero-sub">${esc(page.intro)}</p>
    <div class="hero-btns">
      <a href="${SITE}/#rentals" class="btn-primary">Browse &amp; Reserve Online</a>
      <a href="tel:${TEL}" class="btn-secondary">📞 ${PHONE}</a>
    </div>
  </header>

  <section>
    <div class="section-inner">
      <div class="section-label">Available Now</div>
      <h2 class="section-title">${esc(page.navLabel)} for Rent</h2>
      <p class="section-desc">Delivery, setup and pickup available across Ashburn, Aldie, South Riding, Chantilly, Sterling, Leesburg and the DMV — or pick up yourself and save.</p>
      <div class="product-grid">${cards}
      </div>
    </div>
  </section>

  <section>
    <div class="section-inner">
      <div class="section-label">FAQ</div>
      <h2 class="section-title">Frequently Asked Questions</h2>
      <div class="faq-list">${faqHtml}
      </div>
    </div>
  </section>

  <section>
    <div class="section-inner">
      <div class="section-label">Explore More</div>
      <h2 class="section-title">Other Rental Categories</h2>
      <div class="internal-links-grid">
        ${otherCats}
        <a class="int-link" href="../locations/ashburn-va.html"><span class="int-arrow">→</span><span>Rentals in Ashburn, VA</span></a>
        <a class="int-link" href="../locations/aldie-va.html"><span class="int-arrow">→</span><span>Rentals in Aldie, VA</span></a>
      </div>
    </div>
  </section>

  <div class="cta-band">
    <h2>Ready to Plan Your Event?</h2>
    <p>Reserve online in minutes or text us your event details for a quick quote.</p>
    <div class="cta-band-btns">
      <a href="${SITE}/#rentals" class="btn-dark">Reserve Online</a>
      <a href="sms:${TEL}" class="btn-outline-dark">💬 Text Us</a>
    </div>
  </div>
</main>

${footerBlock}
<script>
  document.querySelectorAll('.faq-question').forEach(btn => {
    btn.addEventListener('click', () => {
      const item = btn.closest('.faq-item');
      const isOpen = item.classList.contains('open');
      document.querySelectorAll('.faq-item').forEach(i => i.classList.remove('open'));
      if (!isOpen) item.classList.add('open');
      btn.setAttribute('aria-expanded', !isOpen);
    });
  });
</script>
</body>
</html>
`;
}

// ── Write pages + update sitemap ──────────────────────────────────────────
// Categorize same way as app.js
rentalItems.forEach(item => {
  const t = item.title.toLowerCase();
  if (t.includes('chair') || t.includes('loveseat')) item.category = 'Chairs';
  else if (t.includes('table')) item.category = 'Tables';
  else if (t.includes('tent')) item.category = 'Tents';
  else if (t.includes('warmer') || t.includes('buffet')) item.category = 'Buffet Sets';
  else if (t.includes('backdrop') || t.includes('stand') || t.includes('photo prop') || t.includes('swing')) item.category = 'Backdrops';
  else if (t.includes('marquee') || t.includes('number') || t.includes('letter')) item.category = 'Marquee Letters';
  else if (t.includes('neon')) item.category = 'Neon Signs';
  else item.category = 'Traditional Decor';
});

const outDir = path.join(ROOT, 'rentals');
fs.mkdirSync(outDir, { recursive: true });
for (const page of pages) {
  fs.writeFileSync(path.join(outDir, `${page.slug}.html`), buildPage(page));
  console.log(`✔ rentals/${page.slug}.html (${rentalItems.filter(page.match).length} items)`);
}

const sitemapPath = path.join(ROOT, 'sitemap.xml');
let sitemap = fs.readFileSync(sitemapPath, 'utf8');
const today = new Date().toISOString().slice(0, 10);
for (const page of pages) {
  const loc = `${SITE}/rentals/${page.slug}.html`;
  if (sitemap.includes(loc)) {
    sitemap = sitemap.replace(new RegExp(`(<loc>${loc.replace(/[.]/g, '\\.')}</loc>\\s*<lastmod>)[^<]*`), `$1${today}`);
  } else {
    sitemap = sitemap.replace('</urlset>', `  <url>\n    <loc>${loc}</loc>\n    <lastmod>${today}</lastmod>\n    <changefreq>monthly</changefreq>\n    <priority>0.8</priority>\n  </url>\n</urlset>`);
  }
}
fs.writeFileSync(sitemapPath, sitemap);
console.log('✔ sitemap.xml updated');
