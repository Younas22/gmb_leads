{{-- Homepage-only styles (solid colours, no gradients). Loaded in <head> of home.blade.php. --}}
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
    :root { --hp-orange: #F97316; --hp-orange-strong: #FF5A00; --hp-orange-hover: #EA580C; --hp-ink: #111111; --hp-muted: #475569; --hp-soft: #FFF7F2; --hp-line: #E5E7EB; --hp-dark: #1C1C1E; }
    body.hp-body, .hp-body button, .hp-body input, .hp-body select, .hp-body textarea { font-family: 'Poppins', ui-sans-serif, system-ui, sans-serif; }
    .hp-body { color: var(--hp-ink); }
    .hp-body section[id], .hp-body footer[id] { scroll-margin-top: 80px; }

    .hp-wrap { max-width: 1240px; margin: 0 auto; padding-left: 16px; padding-right: 16px; }
    @media (min-width: 640px) { .hp-wrap { padding-left: 24px; padding-right: 24px; } }
    @media (min-width: 1024px) { .hp-wrap { padding-left: 32px; padding-right: 32px; } }
    .hp-sec { padding-top: 52px; padding-bottom: 52px; }
    @media (min-width: 1024px) { .hp-sec { padding-top: 64px; padding-bottom: 64px; } }

    /* Section headings with the short orange underline */
    .hp-h2 { font-size: 26px; line-height: 1.2; font-weight: 700; letter-spacing: -.02em; color: var(--hp-ink); }
    @media (min-width: 768px) { .hp-h2 { font-size: 32px; } }
    .hp-mark { position: relative; color: var(--hp-orange); white-space: nowrap; }
    .hp-mark::after { content: ''; position: absolute; left: 4%; right: 4%; bottom: -6px; height: 4px; border-radius: 99px; background: var(--hp-orange); opacity: .9; }
    .hp-lead { font-size: 15px; line-height: 1.65; color: var(--hp-muted); }

    /* Buttons */
    .hp-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; height: 48px; padding: 0 24px; border-radius: 999px; background: var(--hp-orange); color: #fff; font-size: 14.5px; font-weight: 600; border: 1px solid var(--hp-orange); transition: background-color .15s, border-color .15s, transform .15s; white-space: nowrap; }
    .hp-btn:hover { background: var(--hp-orange-hover); border-color: var(--hp-orange-hover); }
    .hp-btn-ghost { display: inline-flex; align-items: center; justify-content: center; gap: 8px; height: 48px; padding: 0 22px; border-radius: 999px; background: #fff; color: var(--hp-ink); font-size: 14.5px; font-weight: 600; border: 1px solid #D1D5DB; transition: border-color .15s, background-color .15s; white-space: nowrap; }
    .hp-btn-ghost:hover { border-color: var(--hp-ink); }
    .hp-btn-outline { display: inline-flex; align-items: center; justify-content: center; gap: 8px; height: 44px; padding: 0 20px; border-radius: 999px; background: #fff; color: var(--hp-orange-hover); font-size: 14px; font-weight: 600; border: 1.5px solid var(--hp-orange); transition: background-color .15s, color .15s; white-space: nowrap; }
    .hp-btn-outline:hover { background: var(--hp-orange); color: #fff; }
    .hp-sm { height: 40px; padding: 0 18px; font-size: 13.5px; }
    .hp-body :is(.hp-btn, .hp-btn-ghost, .hp-btn-outline, .hp-nav-link):focus-visible { outline: 2px solid var(--hp-orange); outline-offset: 3px; }

    /* Nav */
    .hp-nav-link { position: relative; display: inline-flex; align-items: center; gap: 6px; padding: 8px 12px; font-size: 13.5px; font-weight: 500; color: #1F2937; border-radius: 8px; transition: color .15s; }
    .hp-nav-link:hover { color: var(--hp-orange-hover); }
    .hp-new { padding: 2px 6px; border-radius: 6px; background: var(--hp-orange); color: #fff; font-size: 9.5px; font-weight: 700; letter-spacing: .03em; text-transform: uppercase; line-height: 1.2; }

    /* Cards */
    .hp-card { background: #fff; border: 1px solid var(--hp-line); border-radius: 16px; box-shadow: 0 1px 3px rgba(17, 17, 17, .05); }
    .hp-icon { width: 48px; height: 48px; border-radius: 14px; background: var(--hp-soft); color: var(--hp-orange); display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; }

    /* Hero preview map (solid blocks only) */
    .hp-map { position: relative; background: #EEF1F4; overflow: hidden; }
    .hp-road { position: absolute; background: #fff; }
    .hp-pin { color: var(--hp-orange); filter: drop-shadow(0 2px 2px rgba(17, 17, 17, .25)); }

    /* Step connector arrows */
    .hp-step-arrow { position: absolute; top: 50%; right: -22px; transform: translateY(-50%); width: 28px; height: 28px; border-radius: 50%; background: #fff; border: 1px solid #FED7AA; color: var(--hp-orange); display: none; align-items: center; justify-content: center; font-size: 11px; z-index: 2; }
    @media (min-width: 1024px) { .hp-step-arrow { display: flex; } }

    @media (prefers-reduced-motion: reduce) { .hp-body * { transition: none !important; scroll-behavior: auto !important; } }
</style>
