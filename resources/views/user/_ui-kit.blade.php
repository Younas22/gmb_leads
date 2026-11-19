{{-- Shared compact look for the user area pages (content only; the header and sidebar are not touched).
     Wrap a page in <div class="cnm-ui ui-page"> and use the ui-* classes below. --}}
@once
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    .cnm-ui, .cnm-ui button, .cnm-ui input, .cnm-ui select, .cnm-ui textarea { font-family: 'Poppins', ui-sans-serif, system-ui, sans-serif; }
    .cnm-ui { color: #111827; font-size: 13px; }

    /* Page frame */
    .ui-page { max-width: 1180px; margin: 0 auto; padding: 16px; }
    @media (min-width: 1024px) { .ui-page { padding: 20px 24px; } }
    .ui-stack > * + * { margin-top: 14px; }

    /* Page heading */
    .ui-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px 16px; }
    .ui-head-main { display: flex; align-items: center; gap: 12px; min-width: 0; }
    .ui-head-icon { width: 38px; height: 38px; border-radius: 10px; background: #FFF7ED; color: #EA580C; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 15px; }
    .ui-title { font-size: 19px; line-height: 1.25; font-weight: 700; color: #111827; letter-spacing: -.01em; }
    .ui-sub { font-size: 12.5px; color: #64748B; margin-top: 1px; max-width: 760px; line-height: 1.5; }
    .ui-actions { display: flex; flex-wrap: wrap; gap: 8px; }

    /* Cards */
    .ui-card { background: #fff; border: 1px solid #E2E8F0; border-radius: 14px; box-shadow: 0 1px 2px rgba(15, 23, 42, .04); }
    .ui-card-h { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 8px 12px; padding: 11px 16px; border-bottom: 1px solid #F1F5F9; }
    .ui-card-t { font-size: 14px; font-weight: 600; color: #111827; display: flex; align-items: center; gap: 8px; }
    .ui-card-t i { color: #F97316; font-size: 13px; }
    .ui-card-d { font-size: 12px; color: #64748B; margin-top: 1px; }
    .ui-card-b { padding: 14px 16px; }
    .ui-card-f { padding: 10px 16px; border-top: 1px solid #F1F5F9; }

    /* Stat tiles */
    .ui-stats { display: grid; gap: 10px; grid-template-columns: repeat(2, minmax(0, 1fr)); }
    @media (min-width: 768px) { .ui-stats.c3 { grid-template-columns: repeat(3, minmax(0, 1fr)); } .ui-stats.c4 { grid-template-columns: repeat(4, minmax(0, 1fr)); } .ui-stats.c5 { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    @media (min-width: 1100px) { .ui-stats.c5 { grid-template-columns: repeat(5, minmax(0, 1fr)); } }
    .ui-stat { background: #fff; border: 1px solid #E2E8F0; border-radius: 12px; padding: 10px 12px; min-width: 0; }
    .ui-stat-l { display: flex; align-items: center; gap: 6px; font-size: 11.5px; font-weight: 500; color: #64748B; }
    .ui-stat-l i { color: #F97316; font-size: 11px; }
    .ui-stat-v { font-size: 19px; font-weight: 700; color: #111827; line-height: 1.3; margin-top: 2px; }
    .ui-stat-v small { font-size: 12px; font-weight: 400; color: #64748B; }
    .ui-stat-h { font-size: 11px; color: #94A3B8; margin-top: 1px; }
    .ui-bar { height: 5px; background: #F1F5F9; border-radius: 99px; overflow: hidden; margin-top: 6px; }
    .ui-bar > span { display: block; height: 100%; background: #F97316; border-radius: 99px; }
    .ui-bar > span.is-hot { background: #DC2626; }

    /* Buttons */
    .ui-btn, .ui-btn-light, .ui-btn-dark, .ui-btn-danger { display: inline-flex; align-items: center; justify-content: center; gap: 6px; height: 36px; padding: 0 14px; border-radius: 9px; font-size: 13px; font-weight: 600; white-space: nowrap; transition: background-color .15s, border-color .15s, color .15s; cursor: pointer; }
    .ui-btn { background: #F97316; color: #fff; border: 1px solid #F97316; }
    .ui-btn:hover { background: #EA580C; border-color: #EA580C; }
    .ui-btn-light { background: #fff; color: #334155; border: 1px solid #E2E8F0; font-weight: 500; }
    .ui-btn-light:hover { background: #F8FAFC; border-color: #CBD5E1; color: #111827; }
    .ui-btn-dark { background: #111827; color: #fff; border: 1px solid #111827; }
    .ui-btn-dark:hover { background: #1F2937; }
    .ui-btn-danger { background: #fff; color: #DC2626; border: 1px solid #FECACA; font-weight: 500; }
    .ui-btn-danger:hover { background: #FEF2F2; border-color: #DC2626; }
    .ui-sm { height: 30px; padding: 0 10px; font-size: 12px; border-radius: 8px; }
    .ui-btn:disabled, .ui-btn-light:disabled { opacity: .5; cursor: not-allowed; }
    .ui-link { color: #EA580C; font-weight: 500; }
    .ui-link:hover { text-decoration: underline; }
    .cnm-ui :is(.ui-btn, .ui-btn-light, .ui-btn-dark, .ui-btn-danger, .ui-tab, .ui-link):focus-visible { outline: 2px solid #F97316; outline-offset: 2px; }

    /* Fields */
    .ui-label { display: block; font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 5px; }
    .ui-field { width: 100%; height: 36px; padding: 0 11px; border: 1px solid #CBD5E1; border-radius: 9px; background-color: #fff; color: #111827; font-size: 13px; transition: border-color .15s, box-shadow .15s; }
    .ui-field::placeholder { color: #94A3B8; }
    .ui-field:hover { border-color: #94A3B8; }
    .ui-field:focus { outline: none; border-color: #F97316; box-shadow: 0 0 0 3px rgba(249, 115, 22, .15); }
    textarea.ui-field { height: auto; padding: 8px 11px; line-height: 1.5; resize: vertical; }
    select.ui-field { -webkit-appearance: none; appearance: none; padding-right: 32px; cursor: pointer;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
        background-repeat: no-repeat; background-position: right 10px center; background-size: 13px; }
    .ui-field.ui-sm { height: 30px; font-size: 12px; }
    .ui-check { width: 16px; height: 16px; accent-color: #F97316; flex-shrink: 0; cursor: pointer; }

    /* Tabs (pill bar) */
    .ui-tabs { display: inline-flex; max-width: 100%; overflow-x: auto; gap: 3px; padding: 3px; background: #fff; border: 1px solid #E2E8F0; border-radius: 11px; }
    .ui-tab { display: inline-flex; align-items: center; gap: 6px; height: 32px; padding: 0 13px; border-radius: 8px; font-size: 12.5px; font-weight: 500; color: #475569; white-space: nowrap; transition: all .15s; }
    .ui-tab:hover { color: #111827; background: #F8FAFC; }
    .ui-tab.is-on { background: #F97316; color: #fff; font-weight: 600; }

    /* Tables */
    .ui-table { width: 100%; font-size: 13px; border-collapse: collapse; }
    .ui-table thead th { background: #F8FAFC; text-align: left; font-size: 11px; font-weight: 600; color: #64748B; text-transform: uppercase; letter-spacing: .04em; padding: 8px 12px; border-bottom: 1px solid #E2E8F0; white-space: nowrap; }
    .ui-table tbody td { padding: 9px 12px; border-bottom: 1px solid #F1F5F9; vertical-align: top; }
    .ui-table tbody tr:last-child td { border-bottom: 0; }
    .ui-table tbody tr:hover td { background: #FCFCFD; }

    /* Badges */
    .ui-badge { display: inline-flex; align-items: center; gap: 4px; height: 21px; padding: 0 8px; border-radius: 99px; border: 1px solid #E2E8F0; background: #F8FAFC; color: #475569; font-size: 11px; font-weight: 500; white-space: nowrap; }
    .ui-badge.is-ok { background: #F0FDF4; border-color: #BBF7D0; color: #15803D; }
    .ui-badge.is-warn { background: #FFFBEB; border-color: #FDE68A; color: #B45309; }
    .ui-badge.is-bad { background: #FEF2F2; border-color: #FECACA; color: #DC2626; }
    .ui-badge.is-brand { background: #FFF7ED; border-color: #FED7AA; color: #C2410C; }

    /* AI accent */
    .ui-ai { display: inline-flex; align-items: center; gap: 4px; height: 20px; padding: 0 7px; border-radius: 6px; background: #111827; color: #fff; font-size: 10.5px; font-weight: 600; letter-spacing: .02em; }
    .ui-ai i { color: #FDBA74; font-size: 9.5px; }

    /* Notices */
    .ui-note { display: flex; align-items: flex-start; gap: 10px; padding: 10px 14px; border-radius: 12px; border: 1px solid #E2E8F0; background: #F8FAFC; font-size: 12.5px; color: #334155; }
    .ui-note > i { margin-top: 2px; }
    .ui-note.is-brand { background: #FFF7ED; border-color: #FED7AA; }
    .ui-note.is-warn { background: #FFFBEB; border-color: #FDE68A; color: #78350F; }
    .ui-note.is-info { background: #EFF6FF; border-color: #BFDBFE; color: #1E3A8A; }
    .ui-note.is-ok { background: #F0FDF4; border-color: #BBF7D0; color: #14532D; }
    .ui-note.is-bad { background: #FEF2F2; border-color: #FECACA; color: #7F1D1D; }

    /* Empty state */
    .ui-empty { padding: 28px 16px; text-align: center; color: #64748B; font-size: 12.5px; }
    .ui-empty-i { width: 40px; height: 40px; margin: 0 auto 8px; border-radius: 12px; background: #FFF7ED; color: #F97316; display: flex; align-items: center; justify-content: center; }
    .ui-empty-t { font-size: 13.5px; font-weight: 600; color: #111827; }

    /* Pagination links inside cards */
    .ui-card nav[role="navigation"] { font-size: 12.5px; }

    @media (prefers-reduced-motion: reduce) { .cnm-ui * { transition: none !important; } }
</style>
@endonce
