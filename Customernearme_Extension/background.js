// Google Maps Scraper - Background Service Worker
// Runs independently of popup — continues even when popup is closed

// =============================================
// STATE
// =============================================
let scrapeState = {
  running:  false,
  bulkData: [],
  progress: { current: 0, total: 0, phase: '' }
};

chrome.storage.local.get('scrape_bg_state').then(stored => {
  if (stored.scrape_bg_state) {
    scrapeState         = stored.scrape_bg_state;
    scrapeState.running = false;
  }
});

async function saveState() {
  await chrome.storage.local.set({ scrape_bg_state: scrapeState });
}

function notifyPopup(msg) {
  chrome.runtime.sendMessage(msg).catch(() => {});
}

// =============================================
// UTILITY
// =============================================
function sleep(ms) {
  return new Promise(r => setTimeout(r, ms));
}

function waitForTabLoad(tabId) {
  return new Promise(resolve => {
    const poll = () => {
      chrome.tabs.get(tabId, tab => {
        if (chrome.runtime.lastError || !tab) { resolve(); return; }
        if (tab.status === 'complete') { resolve(); return; }
        setTimeout(poll, 250);
      });
    };
    setTimeout(poll, 300);
  });
}

async function waitForMapsContent(tabId, maxWait = 10000) {
  const start = Date.now();
  while (Date.now() - start < maxWait) {
    try {
      const [{ result }] = await chrome.scripting.executeScript({
        target: { tabId },
        func: () => !!(document.querySelector('div[role="main"] h1')?.innerText?.trim())
      });
      if (result) return true;
    } catch(e) {}
    await sleep(400);
  }
  return false;
}

// =============================================
// EMAIL / SOCIAL PARSING
// =============================================
const EMAIL_RX          = /[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/g;
const SOCIAL_RX         = /https?:\/\/(www\.)?(instagram|facebook|twitter|x|linkedin|youtube|tiktok|pinterest|snapchat)\.com\/[^\s"'<>\s]{2,}/gi;
const JUNK_EMAIL        = /\.(png|jpg|gif|webp|svg|css|js|woff|ttf|eot|otf)$/i;
const PLACEHOLDER_EMAIL = /(@example\.|@domain\.|@yourdomain\.|@yoursite\.|@test\.|@email\.|@company\.|@mail\.|noreply@|no-reply@|donotreply@|support@example|info@example|admin@example|user@|name@|placeholder|dummy|sample)/i;
const SOCIAL_DOMAINS    = ['facebook.com', 'instagram.com', 'twitter.com', 'x.com', 'linkedin.com', 'youtube.com', 'tiktok.com', 'pinterest.com', 'snapchat.com'];

function isSocialUrl(url) {
  try { return SOCIAL_DOMAINS.some(d => new URL(url).hostname.includes(d)); }
  catch(e) { return false; }
}

function parseEmails(html) {
  const mailtoEmails = [...html.matchAll(/mailto:([a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,})/gi)].map(m => m[1]);
  const plainEmails  = html.match(EMAIL_RX) || [];
  return [...new Set([...mailtoEmails, ...plainEmails])].filter(e =>
    !JUNK_EMAIL.test(e) && !PLACEHOLDER_EMAIL.test(e) &&
    !e.match(/@\d+x\./i) && e.split('@')[0].length > 1 &&
    e.split('@')[1]?.includes('.') && e.length < 80
  );
}

function parseSocials(html) {
  const matches = html.match(SOCIAL_RX) || [];
  return [...new Set(
    matches
      .map(s => s.replace(/['"<>\)\]\s]+$/, '').replace(/\/$/, '').replace(/&amp;/gi, '&'))
      .filter(s => { try { new URL(s); return true; } catch(e) { return false; } })
  )];
}

async function safeFetch(url) {
  try {
    const res = await fetch(url, {
      method: 'GET',
      headers: {
        'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Language': 'en-US,en;q=0.9',
        'Cache-Control': 'no-cache'
      },
      redirect: 'follow',
      signal: AbortSignal.timeout(5000)  // 5s per request (was 8s)
    });
    if (!res.ok) return null;
    return await res.text();
  } catch(e) { return null; }
}

async function _fetchWebsiteRawDataInner(url) {
  if (isSocialUrl(url)) return { emails: [], socialLinks: [url.replace(/\/$/, '')] };

  let emails = [], socialLinks = [];
  const mainHtml = await safeFetch(url);
  if (mainHtml) {
    emails      = parseEmails(mainHtml);
    socialLinks = parseSocials(mainHtml);
  }

  if (emails.length === 0) {
    const base = new URL(url);
    for (const path of ['/contact', '/contact-us', '/contactus', '/about', '/about-us']) {
      const html = await safeFetch(base.origin + path);
      if (!html) continue;
      const found = parseEmails(html);
      if (found.length > 0) {
        emails      = found;
        socialLinks = [...new Set([...socialLinks, ...parseSocials(html)])];
        break;
      }
      socialLinks = [...new Set([...socialLinks, ...parseSocials(html)])];
    }
  }

  return { emails: emails.slice(0, 10), socialLinks: socialLinks.slice(0, 10) };
}

// Hard 12-second total cap — if website doesn't respond, skip and move on
async function fetchWebsiteRawData(url) {
  const timeout = new Promise(resolve =>
    setTimeout(() => resolve({ emails: [], socialLinks: [], timedOut: true }), 12000)
  );
  return Promise.race([_fetchWebsiteRawDataInner(url), timeout]);
}

// =============================================
// SERVER API
// =============================================
function formatLeadForApi(d) {
  const socialObj = {};
  (d.social_links || []).forEach(url => {
    try {
      const p = new URL(url).hostname.replace('www.', '').split('.')[0];
      socialObj[p] = url;
    } catch(e) {}
  });
  return {
    name:               d.name               || '',
    address:            d.address            || '',
    phone:              d.phone              || '',
    website:            d.website            || '',
    emails:             d.emails             || [],
    rating:             d.rating             || null,
    total_reviews:      d.total_reviews      || null,
    profile:            d.profile            || d.url || '',
    social_links:       socialObj,
    opening_hours:      d.opening_hours      || [],
    reviews:            d.reviews            || [],
    latest_review_date: d.latest_review_date || null
  };
}

function extractSearchData(url) {
  try {
    const u = new URL(url);
    const m = u.pathname.match(/\/maps\/search\/([^/@]+)/);
    const raw = m ? decodeURIComponent(m[1]).replace(/\+/g, ' ') : '';
    const q = u.searchParams.get('q') || raw || 'Google Maps Search';
    return { query: q, city_name: '', country_name: '', location_name: q };
  } catch(e) {
    return { query: 'Bulk Scrape', city_name: '', country_name: '', location_name: '' };
  }
}

// Save a single lead immediately after scraping
async function saveSingleLead(data, searchUrl, auth) {
  if (!auth?.token || !data?.name) return;
  try {
    const res = await fetch('https://customernearme.com/api/extension/save-leads', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer ' + auth.token
      },
      body: JSON.stringify({
        device_fingerprint: auth.fingerprint,
        search_data:        extractSearchData(searchUrl),
        leads:              [formatLeadForApi(data)]
      }),
      signal: AbortSignal.timeout(10000)
    });
    let json = {};
    try { json = await res.json(); } catch(e) {}
    if (res.ok) {
      notifyPopup({ action: 'bg_credits_update', json });
    }
  } catch(e) {
    // Silent fail — lead is still saved locally in bulkData
  }
}

// Wait until search results feed is loaded and has cards
async function waitForSearchFeed(tabId, maxWait = 15000) {
  const start = Date.now();
  while (Date.now() - start < maxWait) {
    try {
      const [{ result }] = await chrome.scripting.executeScript({
        target: { tabId },
        func: () => {
          const feed = document.querySelector('div[role="feed"], div[aria-label*="Results"], div[aria-label*="result"]');
          if (feed && feed.querySelectorAll('a[href*="/maps/place/"]').length > 0) return true;
          // fallback: .Nv2PK cards
          return document.querySelectorAll('.Nv2PK').length > 0;
        }
      });
      if (result) return true;
    } catch(e) {}
    await sleep(500);
  }
  return false;
}

// =============================================
// URL COLLECTION — scrolls until N new URLs found or truly at end
// =============================================
async function collectNewUrls(tabId, alreadyScrapedUrls) {
  const TARGET_NEW   = 20;   // collect this many new URLs per batch
  const MAX_SCROLLS  = 100;  // hard cap on scroll attempts
  const SCROLL_PAUSE = 4000; // wait after each scroll — Maps needs time to render
  const NO_NEW_LIMIT = 10;   // only stop if 10 consecutive scrolls add zero new cards
  const allSeen      = new Set();
  let prevCardCount  = -1;
  let noNewCount     = 0;
  let scrolls        = 0;

  // Wait for search feed to render before starting
  await waitForSearchFeed(tabId);
  await sleep(1500);

  notifyPopup({ action: 'bg_status', status: 'Scanning for businesses...', statusType: '' });

  while (scrolls < MAX_SCROLLS) {
    // Get all currently rendered business URLs
    const urlsResp = await chrome.tabs.sendMessage(tabId, { action: 'get_bulk_urls' });
    (urlsResp?.urls || []).forEach(u => allSeen.add(u));

    const newUrls = [...allSeen].filter(u => !alreadyScrapedUrls.has(u));

    // Enough new businesses collected — start scraping this batch
    if (newUrls.length >= TARGET_NEW) break;

    // Count how many TOTAL cards are in DOM (including already-scraped)
    if (allSeen.size === prevCardCount) {
      noNewCount++;
      // Only stop if Maps truly stopped loading new cards after many attempts
      if (noNewCount >= NO_NEW_LIMIT) break;
    } else {
      noNewCount = 0; // new cards appeared — keep going
    }
    prevCardCount = allSeen.size;

    notifyPopup({
      action:     'bg_status',
      status:     `${newUrls.length} new found (${alreadyScrapedUrls.size} done) — scrolling for more...`,
      statusType: ''
    });

    // Scroll down — do NOT break on "scrolled to bottom" because Maps
    // lazy-loads more results after you reach the current bottom
    await chrome.tabs.sendMessage(tabId, { action: 'scroll_feed', amount: 3000 });

    // Wait for Maps to load the next batch of results
    await sleep(SCROLL_PAUSE);
    scrolls++;
  }

  return [...allSeen].filter(u => !alreadyScrapedUrls.has(u));
}

// =============================================
// SCRAPE ONE BUSINESS
// =============================================
async function scrapeOneBusiness(tabId, url, index, total, searchUrl, auth) {
  scrapeState.progress = { current: index, total, phase: 'Navigating...' };
  notifyPopup({
    action: 'bg_progress',
    ...scrapeState.progress,
    bulkCount:     scrapeState.bulkData.length,
    totalExpected: total
  });

  try {
    await chrome.tabs.update(tabId, { url });
    await waitForTabLoad(tabId);

    scrapeState.progress.phase = 'Waiting for Maps...';
    notifyPopup({ action: 'bg_progress', ...scrapeState.progress, bulkCount: scrapeState.bulkData.length, totalExpected: total });

    const rendered = await waitForMapsContent(tabId);
    if (!rendered) {
      const entry = { name: null, url };
      scrapeState.bulkData.push(entry);
      await saveState();
      notifyPopup({ action: 'bg_bulk_update', bulkData: scrapeState.bulkData, totalExpected: total });
      return;
    }

    scrapeState.progress.phase = 'Loading reviews...';
    notifyPopup({ action: 'bg_progress', ...scrapeState.progress, bulkCount: scrapeState.bulkData.length, totalExpected: total });

    await chrome.scripting.executeScript({
      target: { tabId },
      func: () => { const p = document.querySelector('div[role="main"]'); if (p) p.scrollTop = 1500; }
    });
    await sleep(900);
    await chrome.scripting.executeScript({
      target: { tabId },
      func: () => { const p = document.querySelector('div[role="main"]'); if (p) p.scrollTop = 3500; }
    });
    await sleep(2000);

    scrapeState.progress.phase = 'Scraping details...';
    notifyPopup({ action: 'bg_progress', ...scrapeState.progress, bulkCount: scrapeState.bulkData.length, totalExpected: total });

    const resp = await chrome.tabs.sendMessage(tabId, { action: 'scrape' });
    const data = (resp?.success && resp?.data) ? resp.data : {};
    data.url = url;

    if (data.website) {
      scrapeState.progress.phase = 'Fetching emails...';
      notifyPopup({ action: 'bg_progress', ...scrapeState.progress, bulkCount: scrapeState.bulkData.length, totalExpected: total });
      const webData = await fetchWebsiteRawData(data.website);
      data.emails       = webData.emails;
      data.social_links = [...new Set([...(data.social_links || []), ...webData.socialLinks])];
    }

    scrapeState.bulkData.push(data);
    await saveState();

    // Save to server immediately — don't wait for all leads
    notifyPopup({ action: 'bg_progress', phase: 'Saving to server...', current: index, total, bulkCount: scrapeState.bulkData.length, totalExpected: total });
    await saveSingleLead(data, searchUrl, auth);

    notifyPopup({ action: 'bg_bulk_update', bulkData: scrapeState.bulkData, totalExpected: total });

  } catch(e) {
    scrapeState.bulkData.push({ name: null, url, _error: e.message });
    await saveState();
    notifyPopup({ action: 'bg_bulk_update', bulkData: scrapeState.bulkData, totalExpected: total });
  }
}

// =============================================
// CONTINUOUS SCRAPE LOOP
// Keeps scrolling + scraping until no new results
// =============================================
async function runContinuousScrape(tabId, searchUrl, auth) {
  let round = 0;

  while (scrapeState.running) {
    round++;

    // Go back to search page and wait for results to render
    try {
      await chrome.tabs.update(tabId, { url: searchUrl });
      await waitForTabLoad(tabId);
      await sleep(2500);  // extra wait — Maps SPA needs time after tab load
    } catch(e) {
      break;
    }

    // Collect new URLs (scrolls automatically)
    const alreadyScrapedUrls = new Set(scrapeState.bulkData.map(b => b.url).filter(Boolean));
    const newUrls = await collectNewUrls(tabId, alreadyScrapedUrls);

    if (newUrls.length === 0) {
      // No more new businesses
      notifyPopup({
        action: 'bg_status',
        status: `No more new businesses found. ${alreadyScrapedUrls.size} total scraped.`,
        statusType: 'success'
      });
      break;
    }

    const total        = alreadyScrapedUrls.size + newUrls.length;
    const roundMsg     = round === 1
      ? `${newUrls.length} businesses found — scraping...`
      : `Round ${round}: ${newUrls.length} more businesses found — continuing...`;
    notifyPopup({ action: 'bg_status', status: roundMsg, statusType: 'success' });

    // Scrape each URL in this round
    for (let i = 0; i < newUrls.length; i++) {
      if (!scrapeState.running) break;
      await scrapeOneBusiness(tabId, newUrls[i], alreadyScrapedUrls.size + i + 1, total, searchUrl, auth);
    }
  }

  // Final wrap-up
  const successCount = scrapeState.bulkData.filter(b => b.name).length;
  scrapeState.running  = false;
  scrapeState.progress = { current: successCount, total: successCount, phase: 'Done!' };
  await saveState();

  notifyPopup({
    action:   'bg_done',
    bulkData: scrapeState.bulkData,
    status:   `Scraping complete! ${successCount} businesses saved.`
  });
}

// =============================================
// MESSAGE LISTENER
// =============================================
chrome.runtime.onMessage.addListener((request, _sender, sendResponse) => {

  if (request.action === 'start_bulk_scrape') {
    if (scrapeState.running) {
      sendResponse({ ok: false, error: 'Scrape already running' });
      return true;
    }

    scrapeState.running = true;
    saveState();

    (async () => {
      try {
        await runContinuousScrape(request.tabId, request.searchUrl, request.auth);
      } catch(e) {
        scrapeState.running = false;
        await saveState();
        notifyPopup({ action: 'bg_error', error: e.message });
      }
    })();

    sendResponse({ ok: true });
    return true;
  }

  if (request.action === 'get_scrape_state') {
    sendResponse(scrapeState);
    return true;
  }

  if (request.action === 'clear_bulk_data') {
    scrapeState.bulkData = [];
    scrapeState.running  = false;
    scrapeState.progress = { current: 0, total: 0, phase: '' };
    saveState();
    sendResponse({ ok: true });
    return true;
  }

  if (request.action === 'stop_scrape') {
    scrapeState.running = false;
    sendResponse({ ok: true });
    return true;
  }

  return true;
});
