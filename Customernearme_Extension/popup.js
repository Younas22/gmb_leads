let singleData = {};
let bulkData   = [];
let currentAuth = null; // { token, user, fingerprint, deviceId }

const btnScrape  = document.getElementById('btn-scrape');
const statusEl   = document.getElementById('status');
const modeBadge  = document.getElementById('mode-badge');
const viewSingle = document.getElementById('view-single');
const viewBulk   = document.getElementById('view-bulk');

function setStatus(msg, type = '') {
  statusEl.textContent = msg;
  statusEl.className = type;
}

function showView(mode) {
  viewSingle.classList.toggle('visible', mode === 'single');
  viewBulk.classList.toggle('visible', mode === 'bulk');
}

function escHtml(str) {
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

// =============================================
// UTILITY FUNCTIONS
// =============================================
function sleep(ms) {
  return new Promise(r => setTimeout(r, ms));
}

// Wait until tab finishes loading
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

// Wait until Google Maps has rendered the business name (h1)
async function waitForMapsContent(tabId, maxWait = 10000) {
  const start = Date.now();
  while (Date.now() - start < maxWait) {
    try {
      const [{ result }] = await chrome.scripting.executeScript({
        target: { tabId },
        func: () => !!(document.querySelector('div[role="main"] h1')?.innerText?.trim())
      });
      if (result) return true;
    } catch(e) { /* content script not injected yet */ }
    await sleep(400);
  }
  return false;
}

// =============================================
// PROGRESS BAR
// =============================================
function showProgress() {
  document.getElementById('progress-wrap').classList.add('visible');
}
function hideProgress() {
  document.getElementById('progress-wrap').classList.remove('visible');
}
function updateProgress(current, total, phase = '') {
  const pct = total > 0 ? Math.round((current / total) * 100) : 0;
  document.getElementById('progress-bar').style.width   = pct + '%';
  document.getElementById('progress-count').textContent = `${current}/${total}`;
  document.getElementById('progress-text').textContent  = `Business ${current}`;
  document.getElementById('progress-phase').textContent = phase;
}

// =============================================
// WEBSITE SCRAPING (pure data — no DOM)
// =============================================
const EMAIL_RX        = /[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/g;
const SOCIAL_RX       = /https?:\/\/(www\.)?(instagram|facebook|twitter|x|linkedin|youtube|tiktok|pinterest|snapchat)\.com\/[^\s"'<>\s]{2,}/gi;
const JUNK_EMAIL      = /\.(png|jpg|gif|webp|svg|css|js|woff|ttf|eot|otf)$/i;
const PLACEHOLDER_EMAIL = /(@example\.|@domain\.|@yourdomain\.|@yoursite\.|@test\.|@email\.|@company\.|@mail\.|noreply@|no-reply@|donotreply@|support@example|info@example|admin@example|user@|name@|placeholder|dummy|sample)/i;

function parseEmails(html) {
  // Priority 1: mailto: links — most reliable, often used in footers
  const mailtoEmails = [...html.matchAll(/mailto:([a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,})/gi)]
    .map(m => m[1]);

  // Priority 2: plain text emails in HTML
  const plainEmails = html.match(EMAIL_RX) || [];

  const allEmails = [...new Set([...mailtoEmails, ...plainEmails])];

  return allEmails.filter(e =>
    !JUNK_EMAIL.test(e) &&
    !PLACEHOLDER_EMAIL.test(e) &&
    !e.match(/@\d+x\./i) &&
    e.split('@')[0].length > 1 &&
    e.split('@')[1]?.includes('.') &&
    e.length < 80
  );
}

function parseSocials(html) {
  const matches = html.match(SOCIAL_RX) || [];
  return [...new Set(
    matches
      .map(s => {
        // Strip trailing punctuation/quotes that got captured
        s = s.replace(/['"<>\)\]\s]+$/, '').replace(/\/$/, '');
        // Decode HTML entities in query strings (&amp; → &)
        s = s.replace(/&amp;/gi, '&');
        return s;
      })
      .filter(s => {
        try { new URL(s); return true; } catch(e) { return false; }
      })
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
      signal: AbortSignal.timeout(8000)
    });
    if (!res.ok) return null;
    return await res.text();
  } catch(e) { return null; }
}

async function fetchWebsiteRawData(url) {
  // Social media pages have no emails — skip fetch, just return the URL as a social link
  if (isSocialUrl(url)) return { emails: [], socialLinks: [url.replace(/\/$/, '')] };

  let emails = [];
  let socialLinks = [];

  // Step 1: Fetch main page
  const mainHtml = await safeFetch(url);
  if (mainHtml) {
    emails      = parseEmails(mainHtml);
    socialLinks = parseSocials(mainHtml);
  }

  // Step 2: If no emails found, try contact & about pages
  if (emails.length === 0) {
    const base = new URL(url);
    const contactPaths = ['/contact', '/contact-us', '/contactus', '/about', '/about-us'];
    for (const path of contactPaths) {
      const html = await safeFetch(base.origin + path);
      if (!html) continue;
      const found = parseEmails(html);
      if (found.length > 0) {
        emails = found;
        socialLinks = [...new Set([...socialLinks, ...parseSocials(html)])];
        break;
      }
      const pageSocials = parseSocials(html);
      if (pageSocials.length > 0) {
        socialLinks = [...new Set([...socialLinks, ...pageSocials])];
      }
    }
  }

  return {
    emails:      emails.slice(0, 10),
    socialLinks: socialLinks.slice(0, 10)
  };
}

// =============================================
// AUTH UI FUNCTIONS
// =============================================
function showLoginView() {
  document.getElementById('view-login').style.display = '';
  document.getElementById('view-app').style.display   = 'none';
}

function showAppView() {
  document.getElementById('view-login').style.display = 'none';
  document.getElementById('view-app').style.display   = '';
}

function updateUserBar(user) {
  if (!user) return;
  document.getElementById('user-name-text').textContent    = user.name || user.email || '';
  document.getElementById('user-package-badge').textContent = user.package_name || '';
  const limitStr = user.credits_limit === -1 ? '∞' : (user.credits_limit ?? '?');
  document.getElementById('credits-info').textContent = `Credits: ${user.credits_used ?? 0}/${limitStr}`;
}

function creditsExceeded(user) {
  if (!user) return false;
  if (user.credits_limit === -1) return false; // unlimited
  return (user.credits_used || 0) >= (user.credits_limit || 0);
}

// =============================================
// AUTH INIT (runs on popup open)
// =============================================
async function initAuth() {
  let auth = await loadAuth();

  // No stored token — try auto-login from web session
  if (!auth || !auth.token) {
    const autoRes = await authWebAutoLogin();
    if (autoRes.ok) {
      currentAuth = autoRes.auth;
      showAppView();
      updateUserBar(currentAuth.user);
      return;
    }
    showLoginView();
    return;
  }

  // Validate stored token with server
  const res = await authCheckStatus(auth.token, auth.fingerprint);

  if (!res.ok) {
    if (res.status === 401) {
      await authLogout();
      // Try auto-login before giving up
      const autoRes = await authWebAutoLogin();
      if (autoRes.ok) {
        currentAuth = autoRes.auth;
        showAppView();
        updateUserBar(currentAuth.user);
        return;
      }
      showLoginView();
      const el = document.getElementById('login-status');
      el.textContent = 'Session expired. Please log in again.';
      el.className   = 'login-error';
      return;
    }
    if (res.status === 403) {
      await authLogout();
      showLoginView();
      const el = document.getElementById('login-status');
      el.textContent = res.data?.message || 'Access denied. Please contact support.';
      el.className   = 'login-error';
      return;
    }
    // Server unreachable (status 0) — allow offline access with warning
    currentAuth = auth;
    showAppView();
    updateUserBar(auth.user);
    setStatus('⚠️ Server unreachable. Offline mode.', 'error');
    return;
  }

  // Update user info from status response
  if (res.data) {
    const d = res.data;
    // Status API returns credits under d.credits, not d.user
    if (d.credits?.used  !== undefined) auth.user.credits_used  = d.credits.used;
    if (d.credits?.limit !== undefined) auth.user.credits_limit = d.credits.limit;
    const u = d.user || {};
    if (u.package_name !== undefined) auth.user.package_name = u.package_name;
    await saveAuth(auth);
  }

  currentAuth = auth;
  showAppView();
  updateUserBar(currentAuth.user);
}

// =============================================
// LOGIN FORM
// =============================================
document.getElementById('btn-login').addEventListener('click', async () => {
  const email    = document.getElementById('input-email').value.trim();
  const password = document.getElementById('input-password').value;
  const loginStatusEl = document.getElementById('login-status');
  const btn = document.getElementById('btn-login');

  if (!email || !password) {
    loginStatusEl.textContent = 'Please enter your email and password.';
    loginStatusEl.className   = 'login-error';
    return;
  }

  btn.disabled = true;
  btn.textContent = 'Logging in...';
  loginStatusEl.textContent = 'Connecting to server...';
  loginStatusEl.className   = 'login-info';

  const res = await authLogin(email, password);

  if (res.ok) {
    currentAuth = res.auth;
    loginStatusEl.textContent = '';
    showAppView();
    updateUserBar(currentAuth.user);
  } else {
    loginStatusEl.textContent = res.message;
    loginStatusEl.className   = 'login-error';
  }

  btn.disabled = false;
  btn.textContent = 'Login';
});

// Enter key on login inputs
['input-email', 'input-password'].forEach(id => {
  document.getElementById(id).addEventListener('keydown', e => {
    if (e.key === 'Enter') document.getElementById('btn-login').click();
  });
});

// =============================================
// LOGOUT
// =============================================
document.getElementById('btn-logout').addEventListener('click', async () => {
  await authLogout();
  currentAuth = null;
  // Reset views
  showView('none');
  setStatus('Open a Google Maps business or search page');
  showLoginView();
});

// =============================================
// SAVE LEADS TO SERVER
// =============================================
function formatLeadForApi(d) {
  // Convert social_links array to object { platform: url }
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

async function saveLeadsToServer(leads, searchData) {
  if (!currentAuth) return;

  setStatus(`Saving ${leads.length} lead(s) to server...`, '');

  let res;
  try {
    const formatted = leads.map(formatLeadForApi);
    res = await fetch('https://customernearme.com/api/extension/save-leads', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer ' + currentAuth.token
      },
      body: JSON.stringify({
        device_fingerprint: currentAuth.fingerprint,
        search_data: searchData,
        leads: formatted
      }),
      signal: AbortSignal.timeout(15000)
    });
  } catch(e) {
    setStatus('⚠️ Server unreachable. Data scraped but not saved.', 'error');
    return;
  }

  let json = {};
  try { json = await res.json(); } catch(e) {}

  if (!res.ok) {
    const msg = json?.message || json?.error || `Server error (${res.status})`;
    setStatus('⚠️ Save failed: ' + msg, 'error');
    return;
  }

  // Update credits from server response
  const d = json || {};
  if (d.credits_used !== undefined) {
    currentAuth.user.credits_used = d.credits_used;
  } else if (d.credits?.used !== undefined) {
    currentAuth.user.credits_used = d.credits.used;
  } else {
    currentAuth.user.credits_used = (currentAuth.user.credits_used || 0) + 1; // 1 credit per call
  }
  await saveAuth(currentAuth);
  updateUserBar(currentAuth.user);
  setStatus(`✓ ${leads.length} lead(s) saved to server.`, 'success');
}

// =============================================
// SINGLE BUSINESS — DISPLAY
// =============================================
function setField(id, value) {
  const el = document.getElementById('val-' + id);
  if (value) { el.textContent = value; el.classList.remove('empty'); }
  else        { el.textContent = 'Not found'; el.classList.add('empty'); }
}

function setFieldLink(id, value) {
  const el = document.getElementById('val-' + id);
  if (value) {
    el.innerHTML = `<a href="${escHtml(value)}" target="_blank">${escHtml(value)}</a>`;
    el.classList.remove('empty');
  } else {
    el.textContent = 'Not found';
    el.classList.add('empty');
  }
}

function displayEmails(emails) {
  const el = document.getElementById('val-emails');
  if (emails?.length) {
    el.innerHTML = '<div class="chip-list">' +
      emails.map(e => `<span class="chip">${escHtml(e)}</span>`).join('') + '</div>';
    el.classList.remove('empty');
  } else {
    el.textContent = 'Not found';
    el.classList.add('empty');
  }
}

function displaySocials(socials) {
  const el = document.getElementById('val-socials');
  if (socials?.length) {
    el.innerHTML = '<div class="chip-list">' +
      socials.map(s => {
        const name = getSocialName(s);
        return `<span class="chip social"><a href="${escHtml(s)}" target="_blank">${escHtml(name)}</a></span>`;
      }).join('') + '</div>';
    el.classList.remove('empty');
  } else {
    el.textContent = 'Not found';
    el.classList.add('empty');
  }
}

function getSocialName(url) {
  try {
    const u = new URL(url);
    const platform = u.hostname.replace('www.', '').split('.')[0];
    const path = u.pathname.replace(/\/$/, '').split('/').filter(Boolean).join('/');
    return platform.charAt(0).toUpperCase() + platform.slice(1) + (path ? '/' + path : '');
  } catch(e) { return url; }
}

function displayReviews(reviews) {
  const el = document.getElementById('val-reviews');
  if (reviews?.length) {
    const stars = n => n ? '★'.repeat(n) + '☆'.repeat(5 - n) : '';
    el.innerHTML = '<div class="review-list">' +
      reviews.slice(0, 3).map(r => `
        <div class="review-item">
          <div class="review-header">
            <span class="review-author">${escHtml(r.author_name || 'Anonymous')}</span>
            ${r.rating ? `<span class="review-stars">${stars(r.rating)}</span>` : ''}
          </div>
          ${r.relative_time_description ? `<div class="review-time">${escHtml(r.relative_time_description)}</div>` : ''}
          ${r.text ? `<div class="review-text">${escHtml(r.text)}</div>` : ''}
        </div>`).join('') + '</div>';
    el.classList.remove('empty');
  } else {
    el.textContent = 'Not found — scroll down on Maps page to load reviews';
    el.classList.add('empty');
  }
}

function displaySingle(data) {
  singleData = data;
  setField('name', data.name);
  setField('phone', data.phone);
  setField('address', data.address);
  setFieldLink('website', data.website);

  const ratingEl = document.getElementById('val-rating');
  if (data.rating || data.total_reviews) {
    let html = '';
    if (data.rating) html += `<strong style="color:#e37400">${data.rating}</strong> ⭐`;
    if (data.total_reviews) html += ` <span style="color:#666;font-size:11px;">(${Number(data.total_reviews).toLocaleString()} reviews)</span>`;
    if (data.latest_review_date) html += ` <span style="color:#999;font-size:10px;">· Latest: ${escHtml(data.latest_review_date)}</span>`;
    ratingEl.innerHTML = html;
    ratingEl.classList.remove('empty');
  } else {
    ratingEl.textContent = 'Not found';
    ratingEl.classList.add('empty');
  }

  const hoursEl = document.getElementById('val-hours');
  if (data.opening_hours?.length) {
    const ul = document.createElement('ul');
    ul.className = 'hours-list';
    data.opening_hours.forEach(h => { const li = document.createElement('li'); li.textContent = h; ul.appendChild(li); });
    hoursEl.innerHTML = '';
    hoursEl.appendChild(ul);
    hoursEl.classList.remove('empty');
  } else {
    hoursEl.textContent = 'Not found';
    hoursEl.classList.add('empty');
  }

  displaySocials(data.social_links || []);
  displayEmails(data.emails || []);
  displayReviews(data.reviews || []);
  showView('single');
}

// Single mode website fetch (with badge UI)
async function fetchAndUpdateSingleWebsite(url) {
  const emailBadge  = document.getElementById('email-fetch-status');
  const socialBadge = document.getElementById('social-fetch-status');

  if (isSocialUrl(url)) {
    const cleanUrl = url.replace(/\/$/, '');
    singleData.social_links = [...new Set([...(singleData.social_links || []), cleanUrl])];
    displaySocials(singleData.social_links);
    emailBadge.textContent  = '✓ social URL, no email';
    emailBadge.className    = 'fetch-badge done';
    emailBadge.style.display = '';
    socialBadge.style.display = 'none';
    return;
  }

  emailBadge.textContent  = '⏳ main page...';
  emailBadge.className    = 'fetch-badge';
  emailBadge.style.display = '';
  socialBadge.textContent  = '⏳ fetching...';
  socialBadge.className    = 'fetch-badge';
  socialBadge.style.display = '';

  let emails = [], socialLinks = [];
  const mainHtml = await safeFetch(url);
  if (mainHtml) {
    emails      = parseEmails(mainHtml);
    socialLinks = parseSocials(mainHtml);
  }

  if (emails.length === 0) {
    const base = new URL(url);
    const contactPaths = ['/contact', '/contact-us', '/contactus', '/about', '/about-us'];
    for (const path of contactPaths) {
      emailBadge.textContent = `⏳ ${path}...`;
      const html = await safeFetch(base.origin + path);
      if (!html) continue;
      const found = parseEmails(html);
      if (found.length > 0) {
        emails = found;
        socialLinks = [...new Set([...socialLinks, ...parseSocials(html)])];
        break;
      }
      const pageSocials = parseSocials(html);
      if (pageSocials.length > 0) {
        socialLinks = [...new Set([...socialLinks, ...pageSocials])];
      }
    }
  }

  singleData.emails       = emails.slice(0, 10);
  const allSocials        = [...new Set([...(singleData.social_links || []), ...socialLinks])];
  singleData.social_links = allSocials;

  displayEmails(emails);
  displaySocials(allSocials);

  emailBadge.textContent  = emails.length     ? `✓ ${emails.length} found`     : '✓ none';
  emailBadge.className    = 'fetch-badge done';
  socialBadge.textContent = allSocials.length ? `✓ ${allSocials.length} found` : '✓ none';
  socialBadge.className   = 'fetch-badge done';
}

// =============================================
// BULK DEEP — DISPLAY (rich cards)
// =============================================
function displayBulkDeep(businesses) {
  const list = document.getElementById('bulk-list');
  list.innerHTML = '';

  businesses.forEach((biz, i) => {
    if (!biz.name && !biz._error) return;
    const card = document.createElement('div');
    card.className = 'biz-card';

    const nameHtml = biz.url
      ? `<a href="${escHtml(biz.url)}" target="_blank">${escHtml(biz.name || '(loading...)')}</a>`
      : escHtml(biz.name || '(no data)');

    let inner = `<div class="biz-name">${i + 1}. ${nameHtml}</div>`;

    if (biz.rating || biz.total_reviews) {
      let r = '';
      if (biz.rating) r += `⭐ <strong>${escHtml(String(biz.rating))}</strong>`;
      if (biz.total_reviews) r += ` (${Number(biz.total_reviews).toLocaleString()})`;
      if (biz.latest_review_date) r += ` · <span style="color:#999">${escHtml(biz.latest_review_date)}</span>`;
      inner += `<div class="biz-detail"><span class="di" style="color:#e37400">⭐</span><span class="dt">${r}</span></div>`;
    }
    if (biz.phone)   inner += `<div class="biz-detail"><span class="di">📞</span><span class="dt">${escHtml(biz.phone)}</span></div>`;
    if (biz.address) inner += `<div class="biz-detail"><span class="di">📌</span><span class="dt">${escHtml(biz.address)}</span></div>`;
    if (biz.website) inner += `<div class="biz-detail"><span class="di">🌐</span><span class="dt"><a href="${escHtml(biz.website)}" target="_blank" style="color:#1a73e8">${escHtml(biz.website)}</a></span></div>`;

    if (biz.opening_hours?.length) {
      inner += `<div class="biz-section-label">Opening Hours</div>
        <ul class="biz-hours-list">
          ${biz.opening_hours.map(h => `<li>${escHtml(h)}</li>`).join('')}
        </ul>`;
    }

    if (biz.emails?.length) {
      inner += `<div class="biz-section-label">Emails</div>
        <div class="biz-chip-list">
          ${biz.emails.map(e => `<span class="biz-chip">${escHtml(e)}</span>`).join('')}
        </div>`;
    }

    if (biz.social_links?.length) {
      inner += `<div class="biz-section-label">Social Links</div>
        <div class="biz-chip-list">
          ${biz.social_links.map(s => `<span class="biz-chip social"><a href="${escHtml(s)}" target="_blank">${escHtml(getSocialName(s))}</a></span>`).join('')}
        </div>`;
    }

    if (biz.reviews?.length) {
      const stars = n => n ? '★'.repeat(n) + '☆'.repeat(5 - n) : '';
      inner += `<div class="biz-section-label">Reviews</div>`;
      biz.reviews.slice(0, 3).forEach(r => {
        inner += `<div class="biz-review">
          <div class="biz-review-meta">
            <strong>${escHtml(r.author_name || 'Anonymous')}</strong>
            ${r.rating ? ` <span style="color:#e37400">${stars(r.rating)}</span>` : ''}
            ${r.relative_time_description ? ` · <span style="color:#aaa">${escHtml(r.relative_time_description)}</span>` : ''}
          </div>
          ${r.text ? `<div>${escHtml(r.text)}</div>` : ''}
        </div>`;
      });
    }

    card.innerHTML = inner;
    list.appendChild(card);
  });
}

// Simple bulk display (initial search results)
function displayBulk(businesses) {
  bulkData = businesses;
  document.getElementById('bulk-count').textContent = businesses.length + ' businesses found';
  const list = document.getElementById('bulk-list');
  list.innerHTML = '';
  businesses.forEach((biz, i) => {
    const card = document.createElement('div');
    card.className = 'biz-card';
    const nameHtml = biz.url
      ? `<a href="${escHtml(biz.url)}" target="_blank">${escHtml(biz.name || 'Unknown')}</a>`
      : escHtml(biz.name || 'Unknown');
    card.innerHTML = `
      <div class="biz-name">${i + 1}. ${nameHtml}</div>
      <div class="biz-detail"><span class="di">📌</span>
        <span class="dt ${biz.address ? '' : 'empty'}">${escHtml(biz.address || 'Not found')}</span>
      </div>
      ${biz.rating ? `<span class="biz-rating">⭐ ${escHtml(String(biz.rating))}${biz.total_reviews ? ` (${escHtml(String(biz.total_reviews))})` : ''}</span>` : ''}`;
    list.appendChild(card);
  });
  showView('bulk');
}

// =============================================
// SCRAPE BUTTON
// =============================================
btnScrape.addEventListener('click', async () => {
  // Auth check
  if (!currentAuth) {
    setStatus('Please log in first!', 'error');
    return;
  }

  // Credit limit check
  if (creditsExceeded(currentAuth.user)) {
    setStatus('Credit limit reached. Please upgrade your package.', 'error');
    return;
  }

  btnScrape.disabled = true;
  setStatus('Scraping...', '');
  hideProgress();

  document.getElementById('email-fetch-status').style.display  = 'none';
  document.getElementById('social-fetch-status').style.display = 'none';

  try {
    const [tab] = await chrome.tabs.query({ active: true, currentWindow: true });

    if (!tab.url.includes('google.com/maps') && !tab.url.includes('maps.google.com')) {
      setStatus('Please open a Google Maps page!', 'error');
      btnScrape.disabled = false;
      return;
    }

    // Scroll to trigger lazy-loading of reviews & hours
    try {
      await chrome.scripting.executeScript({
        target: { tabId: tab.id },
        func: () => {
          const panel = document.querySelector('div[role="main"]');
          if (panel) panel.scrollTop = 1500;
        }
      });
      await sleep(900);
      await chrome.scripting.executeScript({
        target: { tabId: tab.id },
        func: () => {
          const panel = document.querySelector('div[role="main"]');
          if (panel) panel.scrollTop = 3500;
        }
      });
      await sleep(2000);
    } catch(e) {}

    const response = await chrome.tabs.sendMessage(tab.id, { action: 'scrape' });

    if (!response || !response.success) {
      setStatus(response?.error || 'Error. Please reload the page (Ctrl+R)', 'error');
      btnScrape.disabled = false;
      return;
    }

    // ===== SINGLE MODE =====
    if (response.mode === 'single') {
      modeBadge.textContent = 'Single';
      const data = response.data;

      if (data.name || data.phone || data.address || data.website) {
        displaySingle(data);
        const found = [
          data.name    && 'Name',
          data.phone   && 'Phone',
          data.address && 'Address',
          data.website && 'Website',
          data.rating  && 'Rating',
          data.opening_hours?.length && 'Hours',
          data.reviews?.length && `${data.reviews.length} Reviews`,
        ].filter(Boolean).join(', ');
        setStatus('Scraped: ' + found, 'success');

        if (data.website) {
          setStatus('Scraped! Fetching website data...', 'success');
          await fetchAndUpdateSingleWebsite(data.website);
          setStatus('Done! All data fetched.', 'success');
        }

        // Save to server
        await saveLeadsToServer([singleData], {
          query:         singleData.name    || 'Single Business',
          city_name:     '',
          country_name:  '',
          location_name: singleData.address || ''
        });

      } else {
        setStatus('No details found. Please wait for the page to fully load.', 'error');
      }

    // ===== BULK MODE — Background service worker ko delegate karo =====
    } else if (response.mode === 'bulk') {
      modeBadge.textContent = 'Bulk';
      showView('bulk');
      showProgress();

      // Background service worker ko start karo — popup close hone par bhi kaam karega
      const result = await chrome.runtime.sendMessage({
        action:    'start_bulk_scrape',
        tabId:     tab.id,
        searchUrl: tab.url,
        auth: {
          token:       currentAuth.token,
          fingerprint: currentAuth.fingerprint
        }
      });

      if (result?.ok) {
        setStatus('Background scrape started — you can switch tabs!', 'success');
        // Button will re-enable when background sends done/error
        return;
      } else {
        setStatus(result?.error || 'Failed to start background scrape.', 'error');
      }
    }

  } catch (err) {
    if (err.message?.includes('Could not establish connection')) {
      setStatus('Please reload the page and try again (Ctrl+R)', 'error');
    } else {
      setStatus('Error: ' + err.message, 'error');
    }
  }

  btnScrape.disabled = false;
});

// =============================================
// BACKGROUND SERVICE WORKER — Messages
// =============================================
chrome.runtime.onMessage.addListener((msg) => {
  if (msg.action === 'bg_progress') {
    updateProgress(msg.current, msg.total, msg.phase);
    document.getElementById('bulk-count').textContent =
      `${msg.bulkCount || 0}/${msg.totalExpected || msg.total} scraped`;
  }

  if (msg.action === 'bg_bulk_update') {
    bulkData = msg.bulkData || [];
    displayBulkDeep(bulkData);
    document.getElementById('bulk-count').textContent =
      `${bulkData.length}/${msg.totalExpected || bulkData.length} scraped`;
  }

  if (msg.action === 'bg_done') {
    bulkData = msg.bulkData || [];
    displayBulkDeep(bulkData);
    setStatus(msg.status || 'Done!', 'success');
    hideProgress();
    btnScrape.disabled = false;
  }

  if (msg.action === 'bg_status') {
    setStatus(msg.status, msg.statusType || '');
  }

  if (msg.action === 'bg_error') {
    setStatus(msg.error, 'error');
    hideProgress();
    btnScrape.disabled = false;
  }

  if (msg.action === 'bg_credits_update' && currentAuth?.user) {
    const json = msg.json || {};
    if (json.credits_used !== undefined)
      currentAuth.user.credits_used = json.credits_used;
    else if (json.credits?.used !== undefined)
      currentAuth.user.credits_used = json.credits.used;
    else
      currentAuth.user.credits_used = (currentAuth.user.credits_used || 0) + 1;
    saveAuth(currentAuth);
    updateUserBar(currentAuth.user);
  }
});

// =============================================
// BULK — Clear Data
// =============================================
document.getElementById('btn-clear-bulk').addEventListener('click', async () => {
  bulkData = [];
  document.getElementById('bulk-list').innerHTML = '';
  document.getElementById('bulk-count').textContent = '0 businesses found';
  setStatus('Data cleared. Ready for fresh scrape.', '');
  // Also clear background state
  chrome.runtime.sendMessage({ action: 'clear_bulk_data' }).catch(() => {});
});

// =============================================
// SINGLE — Copy individual field
// =============================================
document.querySelectorAll('.field-copy').forEach(btn => {
  btn.addEventListener('click', () => {
    const val = singleData[btn.dataset.field];
    if (!val) return;
    navigator.clipboard.writeText(val).then(() => {
      const orig = btn.textContent;
      btn.textContent = '✓';
      btn.classList.add('copied');
      setTimeout(() => { btn.textContent = orig; btn.classList.remove('copied'); }, 1500);
    });
  });
});

// =============================================
// SINGLE — Copy All
// =============================================
document.getElementById('btn-copy-all').addEventListener('click', () => {
  const d = singleData;
  const lines = [
    d.name              ? `Name:         ${d.name}` : null,
    d.phone             ? `Phone:        ${d.phone}` : null,
    d.address           ? `Address:      ${d.address}` : null,
    d.website           ? `Website:      ${d.website}` : null,
    d.rating            ? `Rating:       ${d.rating}` + (d.total_reviews ? ` (${d.total_reviews} reviews)` : '') : null,
    d.latest_review_date? `Latest Review: ${d.latest_review_date}` : null,
    d.opening_hours?.length ? `Hours:\n  ${d.opening_hours.join('\n  ')}` : null,
    d.emails?.length    ? `Emails:       ${d.emails.join(', ')}` : null,
    d.social_links?.length ? `Social:\n  ${d.social_links.join('\n  ')}` : null,
    d.profile           ? `Profile:      ${d.profile}` : null,
  ].filter(Boolean);
  if (!lines.length) return;
  navigator.clipboard.writeText(lines.join('\n')).then(() => {
    const btn = document.getElementById('btn-copy-all');
    btn.textContent = '✓ Copied!';
    setTimeout(() => btn.textContent = 'Copy All', 1500);
  });
});

// =============================================
// CSV EXPORTS
// =============================================
document.getElementById('btn-csv-single').addEventListener('click', () => {
  exportSingleCSV(singleData, 'business_data.csv');
});

document.getElementById('btn-csv-bulk').addEventListener('click', () => {
  if (!bulkData.length) return;
  exportBulkCSV(bulkData, 'bulk_businesses.csv');
});

function csvCell(v) {
  return `"${String(v || '').replace(/"/g, '""')}"`;
}

function formatReviewsForCSV(reviews) {
  if (!reviews?.length) return '';
  return reviews.slice(0, 3).map(r => {
    const author = r.author_name || 'Anonymous';
    const stars  = r.rating ? `${r.rating}★` : '';
    const time   = r.relative_time_description ? ` [${r.relative_time_description}]` : '';
    const text   = r.text || '';
    return `${author}${stars ? ' (' + stars + ')' : ''}${time}: ${text}`;
  }).join(' || ');
}

const SOCIAL_URL_DOMAINS = ['facebook.com', 'instagram.com', 'twitter.com', 'x.com',
  'linkedin.com', 'youtube.com', 'tiktok.com', 'pinterest.com', 'snapchat.com'];
function isSocialUrl(url) {
  try { return SOCIAL_URL_DOMAINS.some(d => new URL(url).hostname.includes(d)); }
  catch(e) { return false; }
}

function exportSingleCSV(data, filename) {
  const headers = ['Name','Phone','Address','Website','Rating','Total Reviews',
                   'Latest Review Date','Latest Reviews','Opening Hours','Emails','Social Links','Profile URL'];
  const row = [
    data.name || '',
    data.phone || '',
    data.address || '',
    data.website || '',
    data.rating || '',
    data.total_reviews || '',
    data.latest_review_date || '',
    formatReviewsForCSV(data.reviews),
    (data.opening_hours || []).join(' | '),
    (data.emails || []).join(', '),
    (data.social_links || []).join(', '),
    data.profile || '',
  ];
  const csv = [headers.join(','), row.map(csvCell).join(',')].join('\n');
  downloadCSV('\uFEFF' + csv, filename);
}

function exportBulkCSV(rows, filename) {
  const headers = ['Name','Phone','Address','Website','Rating','Total Reviews',
                   'Latest Review Date','Latest Reviews','Opening Hours','Emails','Social Links','Maps URL'];
  const csvRows = rows.map(r => [
    r.name                || '',
    r.phone               || '',
    r.address             || '',
    r.website             || '',
    r.rating              || '',
    r.total_reviews       || '',
    r.latest_review_date  || '',
    formatReviewsForCSV(r.reviews),
    (r.opening_hours  || []).join(' | '),
    (r.emails         || []).join(', '),
    (r.social_links   || []).join(', '),
    r.url                 || '',
  ].map(csvCell).join(','));
  const csv = [headers.join(','), ...csvRows].join('\n');
  downloadCSV('\uFEFF' + csv, filename);
}

function downloadCSV(content, filename) {
  const blob = new Blob([content], { type: 'text/csv;charset=utf-8;' });
  const url  = URL.createObjectURL(blob);
  const a    = document.createElement('a');
  a.href = url; a.download = filename; a.click();
  URL.revokeObjectURL(url);
}

// =============================================
// START
// =============================================
// START — Auth + Background State Restore
// =============================================
async function restoreBackgroundState() {
  try {
    const state = await chrome.runtime.sendMessage({ action: 'get_scrape_state' });
    if (!state) return;

    // Agar koi scraped data hai to restore karo
    if (state.bulkData?.length > 0) {
      bulkData = state.bulkData;
      modeBadge.textContent = 'Bulk';
      displayBulkDeep(bulkData);
      showView('bulk');
      document.getElementById('bulk-count').textContent = `${bulkData.length} scraped`;
    }

    // Agar scrape abhi bhi chal rahi hai
    if (state.running) {
      showProgress();
      updateProgress(
        state.progress.current,
        state.progress.total,
        state.progress.phase
      );
      setStatus('Background scrape running...', '');
      btnScrape.disabled = true;
    }
  } catch(e) {}
}

initAuth().then(() => restoreBackgroundState());
