'use strict';

// =============================================
// MapScrap Auth & API Module
// Backend: https://customernearme.com/api/extension
// =============================================

const API_BASE = 'https://customernearme.com/api/extension';
const AUTH_KEY = 'ms_auth';

// ---- Device Fingerprint ----
function generateFingerprint() {
  const raw = [
    navigator.userAgent,
    navigator.language || '',
    `${screen.width}x${screen.height}`,
    screen.colorDepth || 24,
    navigator.hardwareConcurrency || 0,
    new Date().getTimezoneOffset()
  ].join('||');

  // FNV-1a 32-bit hash
  let h = 2166136261 >>> 0;
  for (let i = 0; i < raw.length; i++) {
    h ^= raw.charCodeAt(i);
    h = Math.imul(h, 16777619) >>> 0;
  }
  return h.toString(36) + '_' + raw.length.toString(36);
}

function getDeviceName() {
  const ua = navigator.userAgent;
  const b = ua.includes('Edg/')  ? 'Edge'    :
            ua.includes('OPR/')  ? 'Opera'   :
            ua.includes('Firefox') ? 'Firefox' : 'Chrome';
  const o = ua.includes('Windows')   ? 'Windows' :
            ua.includes('Macintosh') ? 'Mac'     :
            ua.includes('Android')   ? 'Android' :
            ua.includes('Linux')     ? 'Linux'   : 'Unknown';
  return `${b} on ${o}`;
}

// ---- Storage helpers ----
function saveAuth(data) {
  return chrome.storage.local.set({ [AUTH_KEY]: data });
}

function loadAuth() {
  return new Promise(r =>
    chrome.storage.local.get(AUTH_KEY, d => r(d[AUTH_KEY] || null))
  );
}

function clearAuth() {
  return chrome.storage.local.remove(AUTH_KEY);
}

// ---- HTTP helpers ----
async function apiFetch(endpoint, method, body, token) {
  const headers = { 'Content-Type': 'application/json' };
  if (token) headers['Authorization'] = 'Bearer ' + token;
  const url = API_BASE + endpoint;
  console.log(`[MapScrap] ▶ ${method} ${url}`, body ? { body } : '');
  try {
    const opts = { method, headers, signal: AbortSignal.timeout(12000) };
    if (body) opts.body = JSON.stringify(body);
    const res  = await fetch(url, opts);
    const json = await res.json().catch(e => {
      console.warn('[MapScrap] Response JSON parse failed:', e.message);
      return {};
    });
    console.log(`[MapScrap] ◀ ${res.status} ${url}`, json);
    return { ok: res.ok, status: res.status, data: json };
  } catch (e) {
    console.error(`[MapScrap] ✖ Network error on ${method} ${url}:`, e.name, e.message);
    return { ok: false, status: 0, data: { message: 'Unable to connect to server.' } };
  }
}

// ---- Auth API calls ----

async function authLogin(email, password) {
  console.log('[MapScrap] Login attempt:', email);
  const res = await apiFetch('/login', 'POST', { email, password }, null);
  if (!res.ok || !res.data.token) {
    console.error('[MapScrap] Login failed — status:', res.status, '| full response JSON:', JSON.stringify(res.data));
    const msg = res.data?.message || res.data?.error || res.data?.msg
      || (res.status === 403 ? 'Access denied (403). Account may be inactive or blocked.' : 'Login failed. Invalid email or password.');
    return { ok: false, message: msg };
  }

  const token       = res.data.token;
  const fingerprint = generateFingerprint();
  const deviceName  = getDeviceName();
  console.log('[MapScrap] Login OK. Registering device:', deviceName, '| fingerprint:', fingerprint);

  // Register this device
  const devRes = await apiFetch('/register-device', 'POST',
    { device_fingerprint: fingerprint, device_name: deviceName }, token);

  if (!devRes.ok) {
    console.warn('[MapScrap] Device registration failed — status:', devRes.status, '| response:', devRes.data);
  }

  const auth = {
    token,
    user: res.data.user,
    fingerprint,
    deviceName,
    deviceId: devRes.data?.device?.id || devRes.data?.id || null
  };
  console.log('[MapScrap] Auth saved:', { user: auth.user, deviceId: auth.deviceId });
  await saveAuth(auth);
  return { ok: true, auth };
}

async function authCheckStatus(token, fingerprint) {
  return apiFetch(
    `/status?device_fingerprint=${encodeURIComponent(fingerprint)}`,
    'GET', null, token
  );
}

async function authSaveLeads(token, fingerprint, searchData, leads) {
  return apiFetch('/save-leads', 'POST',
    { device_fingerprint: fingerprint, search_data: searchData, leads },
    token
  );
}

async function authGetDevices(token) {
  return apiFetch('/devices', 'GET', null, token);
}

async function authRemoveDevice(token, deviceId) {
  return apiFetch(`/devices/${deviceId}`, 'DELETE', null, token);
}

async function authLogout() {
  await clearAuth();
}

// ---- Web auto-login (reads web session cookie) ----
// Called when extension has no stored token but user may be logged into the web app.
async function authWebAutoLogin() {
  console.log('[MapScrap] Trying web auto-login...');
  try {
    const res = await fetch('https://customernearme.com/extension/web-token', {
      method: 'GET',
      credentials: 'include',        // send session cookie
      headers: { 'Accept': 'application/json' },
      signal: AbortSignal.timeout(6000)
    });

    if (!res.ok) {
      console.warn('[MapScrap] Auto-login: web-token endpoint returned', res.status);
      return { ok: false };
    }

    const data = await res.json();
    if (!data.token || !data.user) {
      console.warn('[MapScrap] Auto-login: missing token/user in response', data);
      return { ok: false };
    }

    const fingerprint = generateFingerprint();
    const deviceName  = getDeviceName();

    // Register device
    const devRes = await apiFetch('/register-device', 'POST',
      { device_fingerprint: fingerprint, device_name: deviceName }, data.token);

    const auth = {
      token: data.token,
      user:  data.user,
      fingerprint,
      deviceName,
      deviceId: devRes.data?.device?.id || devRes.data?.id || null
    };
    await saveAuth(auth);
    return { ok: true, auth };
  } catch (e) {
    console.error('[MapScrap] Auto-login exception:', e.name, e.message);
    return { ok: false };
  }
}
