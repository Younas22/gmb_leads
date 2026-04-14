// Google Maps Business Scraper - Content Script v4

// =============================================
// SINGLE BUSINESS PAGE (/maps/place/)
// =============================================
function scrapeSingleBusiness() {
  const data = {
    name: null,
    phone: null,
    address: null,
    website: null,
    rating: null,
    total_reviews: null,
    opening_hours: null,
    reviews: [],
    profile: window.location.href,
    social_links: [],
    emails: [],
    latest_review_date: null
  };

  const mainPanel = document.querySelector('div[role="main"]');

  // === NAME ===
  if (mainPanel) {
    const h1 = mainPanel.querySelector('h1');
    if (h1 && h1.innerText.trim()) data.name = h1.innerText.trim();
  }
  if (!data.name) {
    for (const h1 of document.querySelectorAll('h1')) {
      const txt = h1.innerText.trim();
      if (txt && txt !== 'Google Maps') { data.name = txt; break; }
    }
  }

  // === PHONE & ADDRESS (aria-label on buttons) ===
  for (const btn of document.querySelectorAll('button')) {
    const label = (btn.getAttribute('aria-label') || '').trim();
    if (!data.phone && /^phone[:\s]/i.test(label))
      data.phone = label.replace(/^phone[:\s]+/i, '').trim();
    if (!data.address && /^address[:\s]/i.test(label))
      data.address = label.replace(/^address[:\s]+/i, '').trim();
  }

  // Phone fallback via data-tooltip
  if (!data.phone) {
    const btn = document.querySelector('[data-tooltip="Copy phone number"]');
    if (btn) {
      const txt = (btn.querySelector('.Io6YTe, .fontBodyMedium, .rogA2c')?.innerText || btn.innerText || '').trim();
      const m = txt.match(/[\+\d][\d\s\-\(\)\.]{5,}/);
      if (m) data.phone = m[0].trim();
    }
  }

  // Address fallback via data-tooltip
  if (!data.address) {
    const btn = document.querySelector('[data-tooltip="Copy address"]');
    if (btn) {
      const txt = (btn.querySelector('.Io6YTe, .fontBodyMedium, .rogA2c')?.innerText || btn.innerText || '').trim();
      if (txt && txt.length > 5) data.address = txt;
    }
  }

  // === WEBSITE ===
  for (const a of document.querySelectorAll('a[data-item-id="authority"], a[data-tooltip="Open website"]')) {
    let href = a.href || '';
    if (href.includes('google.com/url')) {
      try { href = new URL(href).searchParams.get('q') || href; } catch(e) {}
    }
    if (href.startsWith('http') && !href.includes('google.com/maps')) {
      data.website = href; break;
    }
  }

  // === RATING ===
  // Method 1: button aria-label "X.X stars"
  for (const btn of document.querySelectorAll('button')) {
    const label = btn.getAttribute('aria-label') || '';
    const m = label.match(/^([\d.]+)\s*star/i);
    if (m) { data.rating = parseFloat(m[1]); break; }
  }
  // Method 2: aria-hidden span with decimal like "4.5"
  if (!data.rating && mainPanel) {
    for (const span of mainPanel.querySelectorAll('span[aria-hidden="true"]')) {
      const txt = span.innerText.trim();
      if (/^\d\.\d$/.test(txt)) { data.rating = parseFloat(txt); break; }
    }
  }

  // === TOTAL REVIEWS ===
  // Method 1: any element aria-label containing "N reviews" — covers both:
  //   button[aria-label="1,529 reviews"]  AND
  //   span[role="img"][aria-label="4.0 stars 1,529 Reviews"]
  for (const el of document.querySelectorAll('button[aria-label], [role="img"][aria-label], [role="button"][aria-label]')) {
    const label = el.getAttribute('aria-label') || '';
    const m = label.match(/([\d,]+)\s*reviews?/i);
    if (m) { data.total_reviews = parseInt(m[1].replace(/,/g, '')); break; }
  }
  // Method 2: aria-hidden span "(1,234)" inside Google Maps rating containers
  if (!data.total_reviews && mainPanel) {
    for (const span of mainPanel.querySelectorAll('.F7nice span[aria-hidden="true"], .jANrlb span[aria-hidden="true"], .HHrUdb span[aria-hidden="true"], .UY7F9')) {
      const txt = (span.innerText || '').trim();
      const m = txt.match(/^\(([\d,]+)\)$/);
      if (m) { data.total_reviews = parseInt(m[1].replace(/,/g, '')); break; }
    }
  }
  // Method 3: any element aria-label containing "N reviews" (broad fallback)
  if (!data.total_reviews) {
    for (const el of document.querySelectorAll('[aria-label]')) {
      const label = (el.getAttribute('aria-label') || '').trim();
      const m = label.match(/([\d,]+)\s*reviews?/i);
      if (m) { data.total_reviews = parseInt(m[1].replace(/,/g, '')); break; }
    }
  }
  // Method 4: span exact pattern "(1,234)" in main panel — must be a real number (>4)
  if (!data.total_reviews && mainPanel) {
    for (const span of mainPanel.querySelectorAll('span')) {
      const txt = (span.innerText || '').trim();
      const m = txt.match(/^\(([\d,]{2,})\)$/);
      if (m) {
        const n = parseInt(m[1].replace(/,/g, ''));
        if (n > 4) { data.total_reviews = n; break; }
      }
    }
  }
  // Method 5: last resort — parenthesized number in full main panel text (skip if < 5)
  if (!data.total_reviews && mainPanel) {
    const allText = mainPanel.innerText || '';
    const matches = [...allText.matchAll(/\(([\d,]{2,})\)/g)];
    for (const m of matches) {
      const n = parseInt(m[1].replace(/,/g, ''));
      if (n > 4) { data.total_reviews = n; break; }
    }
  }

  // === OPENING HOURS ===
  // Method 1: table.eK4R0e (most reliable)
  const hoursTable = document.querySelector('table.eK4R0e');
  if (hoursTable) {
    const rows = Array.from(hoursTable.querySelectorAll('tr'));
    const hours = rows.map(r => r.innerText.replace(/\t+/g, ': ').replace(/\n+/g, ', ').trim()).filter(Boolean);
    if (hours.length > 0) data.opening_hours = hours;
  }
  // Method 2: aria-label containing full week schedule
  if (!data.opening_hours) {
    for (const el of document.querySelectorAll('[aria-label]')) {
      const label = el.getAttribute('aria-label') || '';
      if (label.includes('Monday') && label.includes('Tuesday') && label.length > 80) {
        data.opening_hours = label.split(';').map(s => s.trim()).filter(Boolean);
        break;
      }
    }
  }
  // Method 3: .t39EBf divs (older Maps layout)
  if (!data.opening_hours) {
    const hoursDivs = document.querySelectorAll('.t39EBf');
    if (hoursDivs.length > 0) {
      const hours = Array.from(hoursDivs).map(d => d.innerText.trim()).filter(Boolean);
      if (hours.length > 0) data.opening_hours = hours;
    }
  }

  // === REVIEWS ===
  // IMPORTANT: data-review-id appears on MULTIPLE nested elements per review
  // (outer .jftiEf div, inner div, buttons) — must select only the outer container.
  // Strategy 1: div.jftiEf[data-review-id] — exact outer review container
  let reviewEls = Array.from(document.querySelectorAll('div.jftiEf[data-review-id]'));
  // Strategy 2: .jftiEf without data-review-id check
  if (reviewEls.length === 0) reviewEls = Array.from(document.querySelectorAll('div.jftiEf'));
  // Strategy 3: div[data-review-id] with aria-label (reviewer name) — unique to outer container
  if (reviewEls.length === 0)
    reviewEls = Array.from(document.querySelectorAll('div[data-review-id][aria-label]'))
      .filter(el => el.tagName === 'DIV' && !el.closest('button'));
  // Strategy 4: .GHT2ce containers
  if (reviewEls.length === 0) reviewEls = Array.from(document.querySelectorAll('.GHT2ce'));
  // Strategy 5: divs that have star rating + review text
  if (reviewEls.length === 0) {
    reviewEls = Array.from(document.querySelectorAll('div')).filter(div =>
      (div.querySelector('span[aria-label*="star"]') || div.querySelector('.kvMYJc')) &&
      div.querySelector('.wiI7pd, .MyEned, .Jtu6Td')
    ).slice(0, 10);
  }

  for (const el of reviewEls) {
    if (data.reviews.length >= 3) break;
    const review = {};

    // Author name — .d4r55 is inside .WNxzHc > button > div
    const authorEl = el.querySelector('.d4r55, .WNxzHc .d4r55, .X43Kjb, .kvMYJc ~ .d4r55');
    review.author_name = authorEl?.innerText?.trim() || null;
    // Fallback: contributor/profile button text
    if (!review.author_name) {
      const contribBtn = el.querySelector('button[data-href*="contrib"] .d4r55, button[data-href*="contrib"] div');
      review.author_name = contribBtn?.innerText?.trim() || null;
    }

    // Star rating — .kvMYJc has aria-label="5 stars"
    const starEl = el.querySelector('.kvMYJc[aria-label], span[aria-label*="star"], span[aria-label*="Star"]');
    if (starEl) {
      const m = starEl.getAttribute('aria-label').match(/(\d)/);
      review.rating = m ? parseInt(m[1]) : null;
    } else {
      review.rating = null;
    }

    // Review text — .wiI7pd is the main text span
    const textEl = el.querySelector('.wiI7pd, .MyEned span, .Jtu6Td span, .review-full-text');
    review.text = textEl?.innerText?.trim() || null;
    // Fallback: jsname spans with enough content
    if (!review.text) {
      for (const span of el.querySelectorAll('span[jsname]')) {
        const t = span.innerText?.trim();
        if (t && t.length > 10) { review.text = t; break; }
      }
    }

    // Relative time — .rsqaWe has "2 weeks ago" etc.
    const timeEl = el.querySelector('.rsqaWe, .dehysf, .xRkPPb');
    review.relative_time_description = timeEl?.innerText?.trim() || null;

    if (review.author_name || review.text) {
      data.reviews.push(review);
    }
  }

  // Latest review date = first review's relative time
  if (data.reviews.length > 0 && data.reviews[0].relative_time_description) {
    data.latest_review_date = data.reviews[0].relative_time_description;
  }

  // === SOCIAL LINKS from Maps page ===
  const SOCIAL_DOMAINS = ['instagram.com', 'facebook.com', 'twitter.com', 'x.com', 'linkedin.com', 'youtube.com', 'tiktok.com', 'pinterest.com', 'snapchat.com'];
  const socialSet = new Set();
  for (const a of document.querySelectorAll('a[href]')) {
    const href = a.href || '';
    if (SOCIAL_DOMAINS.some(d => href.toLowerCase().includes(d)) && !href.includes('google.com')) {
      try {
        const u = new URL(href);
        socialSet.add(u.origin + u.pathname.replace(/\/$/, ''));
      } catch(e) { socialSet.add(href); }
    }
  }
  // If the "website" is actually a social media page, move it here & clear website
  // (so popup doesn't try to fetch emails from a Facebook/Instagram page)
  if (data.website && SOCIAL_DOMAINS.some(d => data.website.toLowerCase().includes(d))) {
    try {
      const u = new URL(data.website);
      socialSet.add(u.origin + u.pathname.replace(/\/$/, ''));
    } catch(e) { socialSet.add(data.website); }
    data.website = null;
  }
  data.social_links = [...socialSet];

  return data;
}

// =============================================
// SEARCH RESULTS PAGE (/maps/search/)
// =============================================
function scrapeSearchResults() {
  const businesses = [];

  const cardSelectors = [
    '.Nv2PK',
    'div[jsaction*="mouseover:pane.resultSection"]',
    'div[jsaction*="pane.resultSection.click"]',
    '[data-result-index]',
  ];

  let cards = [];
  for (const sel of cardSelectors) {
    cards = Array.from(document.querySelectorAll(sel));
    if (cards.length > 0) break;
  }

  if (cards.length === 0) {
    const feed = document.querySelector('div[role="feed"], div[aria-label*="Results"]');
    if (feed) {
      cards = Array.from(feed.children).filter(el =>
        el.querySelector('a[href*="/maps/place/"]')
      );
    }
  }

  for (const card of cards) {
    const biz = {
      name: null, address: null, phone: null,
      website: null, rating: null, total_reviews: null, url: null
    };

    // === NAME ===
    const mainLink = card.querySelector('a[href*="/maps/place/"]');
    if (mainLink) {
      biz.url = mainLink.href || null;
      const label = mainLink.getAttribute('aria-label');
      if (label) biz.name = label.trim();
    }
    if (!biz.name) {
      const nameEl = card.querySelector('.fontHeadlineSmall, .qBF1Pd, div[class*="fontHeadline"], [jsan*="name"]');
      if (nameEl) biz.name = nameEl.innerText.trim();
    }
    if (!biz.name) {
      const firstStrong = card.querySelector('div[role="heading"], strong, b');
      if (firstStrong) biz.name = firstStrong.innerText.trim();
    }

    // === ADDRESS ===
    const infoRows = card.querySelectorAll('.W4Efsd, div[class*="W4Efsd"]');
    for (const row of infoRows) {
      if (biz.address) break;
      const spans = row.querySelectorAll('span');
      for (const span of spans) {
        const s = span.innerText.trim();
        if (s.length > 10 && /\d/.test(s) && !/^\d+\.?\d*$/.test(s) && !/reviews/i.test(s)) {
          biz.address = s; break;
        }
      }
    }

    // === RATING ===
    // .MW4etd is the specific rating number span; avoid the outer [role="img"] which includes review count text
    const ratingEl = card.querySelector('.MW4etd');
    if (ratingEl) {
      biz.rating = ratingEl.innerText.trim();
    } else {
      // Fallback: parse from aria-label "4.0 stars 1,529 Reviews"
      const imgSpan = card.querySelector('[role="img"][aria-label*="stars"]');
      if (imgSpan) {
        const m = imgSpan.getAttribute('aria-label').match(/^([\d.]+)\s*stars?/i);
        if (m) biz.rating = m[1];
      }
    }

    // === TOTAL REVIEWS ===
    // .UY7F9 is "(1,529)" span — strip parens
    const reviewsEl = card.querySelector('.UY7F9, .RDApEe');
    if (reviewsEl) {
      biz.total_reviews = reviewsEl.innerText.trim().replace(/[()]/g, '');
    } else {
      // Fallback: parse from aria-label "4.0 stars 1,529 Reviews"
      const imgSpan = card.querySelector('[role="img"][aria-label]');
      if (imgSpan) {
        const m = imgSpan.getAttribute('aria-label').match(/([\d,]+)\s*reviews?/i);
        if (m) biz.total_reviews = m[1];
      }
    }

    if (biz.name) businesses.push(biz);
  }

  return businesses;
}

// =============================================
// MESSAGE LISTENER
// =============================================
chrome.runtime.onMessage.addListener((request, _sender, sendResponse) => {

  // Ping — content script ready check
  if (request.action === 'ping') {
    sendResponse({ ok: true });
  }

  // Scroll the results feed panel down to load more cards
  if (request.action === 'scroll_feed') {
    const feed = document.querySelector(
      'div[role="feed"], div[aria-label*="Results"], div[aria-label*="result"]'
    );
    if (feed) {
      feed.scrollTop += (request.amount || 2000);
      sendResponse({ ok: true, scrollTop: feed.scrollTop, scrollHeight: feed.scrollHeight });
    } else {
      // Fallback: scroll the main results panel
      const panel = document.querySelector('div[role="main"]');
      if (panel) panel.scrollTop += (request.amount || 900);
      sendResponse({ ok: false });
    }
  }

  // Return all visible place URLs from search results
  if (request.action === 'get_bulk_urls') {
    const cardSelectors = [
      '.Nv2PK',
      'div[jsaction*="mouseover:pane.resultSection"]',
      '[data-result-index]',
    ];
    let cards = [];
    for (const sel of cardSelectors) {
      cards = Array.from(document.querySelectorAll(sel));
      if (cards.length > 0) break;
    }
    if (cards.length === 0) {
      const feed = document.querySelector('div[role="feed"], div[aria-label*="Results"]');
      if (feed) {
        cards = Array.from(feed.children).filter(el =>
          el.querySelector('a[href*="/maps/place/"]')
        );
      }
    }
    const urls = cards
      .map(c => c.querySelector('a[href*="/maps/place/"]')?.href || null)
      .filter(Boolean)
      // Remove duplicates
      .filter((u, i, a) => a.indexOf(u) === i);
    sendResponse({ urls });
  }

  // Scrape current page
  if (request.action === 'scrape') {
    const url = location.href;

    // Detect if this looks like a search results page (has visible business cards)
    const hasSearchCards = !!(
      document.querySelector('.Nv2PK') ||
      document.querySelector('div[role="feed"]') ||
      document.querySelector('[data-result-index]')
    );

    if (url.includes('/maps/place/')) {
      const data = scrapeSingleBusiness();
      sendResponse({ success: true, mode: 'single', data });
    } else if (
      url.includes('/maps/search/') ||
      url.includes('/maps/@') ||
      url.includes('maps.google.com') ||
      url.includes('?q=') ||
      url.includes('?query=') ||
      hasSearchCards
    ) {
      const businesses = scrapeSearchResults();
      sendResponse({ success: true, mode: 'bulk', businesses, count: businesses.length });
    } else {
      sendResponse({ success: false, error: 'Please open a Google Maps business or search page.' });
    }
  }

  return true;
});
