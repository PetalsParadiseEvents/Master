/* WebMCP: expose site tools to in-browser AI agents (navigator.modelContext). */
(function () {
  const mc = navigator.modelContext;
  if (!mc || typeof mc.registerTool !== 'function') return;

  const info = {
    name: 'Petals Paradise Events',
    phone: '+1-848-448-6993',
    email: 'contact@petalsparadiseevents.com',
    hours: 'Monday–Sunday, 9 AM – 9 PM',
    location: 'Ashburn, VA 20148 (serving Loudoun, Fairfax and the DMV)',
    booking_url: 'https://petalsparadiseevents.com/#rentals'
  };
  const text = (t) => ({ content: [{ type: 'text', text: typeof t === 'string' ? t : JSON.stringify(t) }] });
  const safe = (tool) => { try { mc.registerTool(tool); } catch (e) { /* already registered */ } };

  safe({
    name: 'get_business_info',
    description: 'Get contact details, hours, service area and booking link for Petals Paradise Events.',
    inputSchema: { type: 'object', properties: {} },
    execute: async () => text(info)
  });

  safe({
    name: 'navigate_site',
    description: 'Open a section of the site: home, rentals, services, gallery, contact, account, about or terms.',
    inputSchema: {
      type: 'object',
      properties: { section: { type: 'string', enum: ['home', 'rentals', 'services', 'gallery', 'contact', 'account', 'about', 'terms'] } },
      required: ['section']
    },
    execute: async ({ section }) => {
      if (section === 'about' || section === 'terms') { location.href = '/' + section + '/'; }
      else { location.hash = section === 'home' ? '' : section; }
      return text('Opened ' + section);
    }
  });

  safe({
    name: 'search_rentals',
    description: 'Search rental items (chairs, tables, tents, backdrops, decor) by keyword and open the rentals page.',
    inputSchema: { type: 'object', properties: { query: { type: 'string', description: 'Keyword, e.g. "chair"' } }, required: ['query'] },
    execute: async ({ query }) => {
      location.hash = 'rentals';
      await new Promise((r) => setTimeout(r, 600));
      const q = String(query).toLowerCase();
      const names = [...document.querySelectorAll('.product-card h3, .product-card .product-title, [data-product-name]')]
        .map((el) => el.getAttribute('data-product-name') || el.textContent.trim())
        .filter((n) => n.toLowerCase().includes(q));
      return text(names.length ? { matches: [...new Set(names)].slice(0, 25) } : 'No matches on page; see ' + info.booking_url);
    }
  });

  safe({
    name: 'request_event_quote',
    description: 'Pre-fill the event planning contact form with the customer details for them to review and submit.',
    inputSchema: {
      type: 'object',
      properties: {
        name: { type: 'string' }, email: { type: 'string' }, phone: { type: 'string' },
        event_type: { type: 'string' }, event_date: { type: 'string', description: 'YYYY-MM-DD' },
        guest_count: { type: 'number' }, message: { type: 'string' }
      },
      required: ['name', 'email']
    },
    execute: async (a) => {
      location.hash = 'contact';
      await new Promise((r) => setTimeout(r, 600));
      const set = (id, v) => { const el = document.getElementById(id); if (el && v != null) { el.value = v; el.dispatchEvent(new Event('input', { bubbles: true })); } };
      set('contact-name', a.name); set('contact-email', a.email); set('contact-phone', a.phone);
      set('contact-event-type', a.event_type); set('contact-event-date', a.event_date);
      set('contact-guest-count', a.guest_count); set('contact-msg', a.message);
      return text('Form pre-filled. Ask the user to review and press submit.');
    }
  });
})();
