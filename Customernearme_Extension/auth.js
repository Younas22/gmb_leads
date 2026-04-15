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
  console.log(`[CustomerNearMe] ▶ ${method} ${url}`, body ? { body } : '');
  try {
    const opts = { method, headers, signal: AbortSignal.timeout(12000) };
    if (body) opts.body = JSON.stringify(body);
    const res  = await fetch(url, opts);
    const json = await res.json().catch(e => {
      console.warn('[CustomerNearMe] Response JSON parse failed:', e.message);
      return {};
    });
    console.log(`[CustomerNearMe] ◀ ${res.status} ${url}`, json);
    return { ok: res.ok, status: res.status, data: json };
  } catch (e) {
    console.error(`[CustomerNearMe] ✖ Network error on ${method} ${url}:`, e.name, e.message);
    return { ok: false, status: 0, data: { message: 'Unable to connect to server.' } };
  }
}

// ---- Auth API calls ----

async function authLogin(email, password) {
  console.log('[CustomerNearMe] Login attempt:', email);
  const res = await apiFetch('/login', 'POST', { email, password }, null);
  if (!res.ok || !res.data.token) {
    console.error('[CustomerNearMe] Login failed — status:', res.status, '| full response JSON:', JSON.stringify(res.data));
    const msg = res.data?.message || res.data?.error || res.data?.msg
      || (res.status === 403 ? 'Access denied (403). Account may be inactive or blocked.' : 'Login failed. Invalid email or password.');
    return { ok: false, message: msg };
  }

  const token       = res.data.token;
  const fingerprint = generateFingerprint();
  const deviceName  = getDeviceName();
  console.log('[CustomerNearMe] Login OK. Registering device:', deviceName, '| fingerprint:', fingerprint);

  // Register this device
  let deviceId = null;
  let activeFingerprint = fingerprint;

  const devRes = await apiFetch('/register-device', 'POST',
    { device_fingerprint: fingerprint, device_name: deviceName }, token);

  if (devRes.ok) {
    // Fresh registration succeeded
    deviceId = devRes.data?.device?.id || devRes.data?.id || devRes.data?.data?.id || null;
    console.log('[CustomerNearMe] Device registered successfully. ID:', deviceId);

  } else if (devRes.status === 409) {
    // Device fingerprint already exists — fetch device list to get its ID
    console.log('[CustomerNearMe] Device already registered (409). Fetching device list...');
    const listRes = await apiFetch('/devices', 'GET', null, token);
    if (listRes.ok) {
      const devices = listRes.data?.devices || listRes.data?.data || listRes.data || [];
      const found = Array.isArray(devices)
        ? devices.find(d => d.device_fingerprint === fingerprint || d.fingerprint === fingerprint)
        : null;
      deviceId = found?.id || null;
      console.log('[CustomerNearMe] Device found in list:', found ? 'YES' : 'NO', '| ID:', deviceId, '| total devices:', Array.isArray(devices) ? devices.length : '?');

      if (!deviceId) {
        // Fingerprint not found in list — force re-register with a new fingerprint
        console.warn('[CustomerNearMe] Fingerprint not found in devices list. Force re-registering...');
        activeFingerprint = fingerprint + '_r' + Date.now().toString(36);
        const reRes = await apiFetch('/register-device', 'POST',
          { device_fingerprint: activeFingerprint, device_name: deviceName }, token);
        if (reRes.ok) {
          deviceId = reRes.data?.device?.id || reRes.data?.id || reRes.data?.data?.id || null;
          console.log('[CustomerNearMe] Re-registration success. ID:', deviceId);
        } else {
          console.warn('[CustomerNearMe] Re-registration failed:', reRes.status, JSON.stringify(reRes.data));
          activeFingerprint = fingerprint; // revert
        }
      }
    } else {
      console.warn('[CustomerNearMe] Could not fetch devices list:', listRes.status, JSON.stringify(listRes.data));
    }

  } else {
    console.warn('[CustomerNearMe] Device registration failed — status:', devRes.status, '| response:', JSON.stringify(devRes.data));
  }

  const auth = {
    token,
    user: res.data.user,
    fingerprint: activeFingerprint,
    deviceName,
    deviceId
  };
  console.log('[CustomerNearMe] Auth saved:', { user: auth.user, deviceId: auth.deviceId });
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
  console.log('[CustomerNearMe] Trying web auto-login...');
  try {
    const res = await fetch('https://customernearme.com/extension/web-token', {
      method: 'GET',
      credentials: 'include',        // send session cookie
      headers: { 'Accept': 'application/json' },
      signal: AbortSignal.timeout(6000)
    });

    if (!res.ok) {
      console.warn('[CustomerNearMe] Auto-login: web-token endpoint returned', res.status);
      return { ok: false };
    }

    const data = await res.json();
    if (!data.token || !data.user) {
      console.warn('[CustomerNearMe] Auto-login: missing token/user in response', data);
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
      deviceId: devRes.data?.device?.id || devRes.data?.id || devRes.data?.data?.id || null
    };
    await saveAuth(auth);
    return { ok: true, auth };
  } catch (e) {
    console.error('[CustomerNearMe] Auto-login exception:', e.name, e.message);
    return { ok: false };
  }
}
