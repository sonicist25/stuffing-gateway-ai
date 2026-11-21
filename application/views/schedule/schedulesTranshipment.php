<div class="content-wrapper ts-page">
    <section class="content-header">
        <div class="container-fluid">
            <div class="ts-pagehead">
                <svg class="ts-pagehead-icon" width="26" height="26" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M12 2v8m0 0l-3-2m3 2l3-2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                    <circle cx="12" cy="14" r="1.6" fill="currentColor"/>
                    <path d="M4 15c0 4 3.5 6 8 6s8-2 8-6M4 15l2.5 5M20 15l-2.5 5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <div>
                    <h2 class="ts-title">LCL Transhipment Schedules</h2>
                    <p class="ts-subtitle">Cari jadwal sailing lanjutan dari hub transhipment ke pelabuhan tujuan.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">

            <!-- Search console -->
            <form method="POST" action="<?= site_url('transhipment/scheduleTranshipmentListing') ?>" id="tsSearchForm" class="ts-console" novalidate>
                <div class="ts-console-grid">

                    <div class="ts-field">
                        <label for="destinationInput">Destination Port</label>
                        <div class="ts-combobox" id="destinationCombobox">
                            <input
                                type="text"
                                id="destinationInput"
                                class="ts-combobox-input"
                                autocomplete="off"
                                role="combobox"
                                aria-expanded="false"
                                aria-controls="destinationList"
                                aria-autocomplete="list"
                                placeholder="Ketik nama kota atau kode (min. 3 huruf)…"
                                value="<?= htmlspecialchars($search_dest, ENT_QUOTES) ?>"
                            >
                            <input type="hidden" name="destination" id="destinationValue" value="<?= htmlspecialchars($search_dest, ENT_QUOTES) ?>">
                            <ul class="ts-combobox-list" id="destinationList" role="listbox" hidden></ul>
                        </div>
                        <p class="ts-field-error" id="destinationError" hidden>Pilih tujuan dari daftar saran yang muncul.</p>
                    </div>

                    <div class="ts-field">
                        <span class="ts-field-label">Transhipment Hub</span>
                        <div class="ts-hub-toggle" id="hubToggle">
                            <input type="radio" name="via" id="hubSin" value="SIN" <?= (($search_via ?? '') === 'SIN') ? 'checked' : '' ?>>
                            <label for="hubSin" class="ts-hub-option">
                                <span class="ts-hub-name">Singapore</span>
                                <span class="ts-hub-partner">via Benkel</span>
                            </label>

                            <input type="radio" name="via" id="hubHkg" value="HKG" <?= (($search_via ?? '') === 'HKG') ? 'checked' : '' ?>>
                            <label for="hubHkg" class="ts-hub-option">
                                <span class="ts-hub-name">Hong Kong</span>
                                <span class="ts-hub-partner">via Charterlink</span>
                            </label>
                        </div>
                        <p class="ts-field-error" id="hubError" hidden>Pilih salah satu hub transhipment.</p>
                    </div>

                    <button type="submit" class="ts-search-btn">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/>
                            <path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        Search
                    </button>
                </div>
            </form>

            <!-- Notice -->
            <?php if (!empty($api_status['error'])): ?>
                <div class="ts-notice" role="alert">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 9v4m0 4h.01M10.6 3.9L2.4 18a1.6 1.6 0 0 0 1.4 2.4h16.4a1.6 1.6 0 0 0 1.4-2.4L13.4 3.9a1.6 1.6 0 0 0-2.8 0z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" stroke-linecap="round"/>
                    </svg>
                    <div>
                        <strong>Notice</strong>
                        <p><?= $api_status['error'] ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Results board -->
            <?php if (!empty($search_dest) && !empty($search_via)): ?>
                <?php $count = is_array($scheduleRecords) ? count($scheduleRecords) : 0; ?>
                <div class="ts-board">
                    <div class="ts-board-head">
                        <h5>Schedules for <?= htmlspecialchars($search_dest, ENT_QUOTES) ?> via <?= $search_via === 'SIN' ? 'Singapore' : 'Hong Kong' ?></h5>
                        <span class="ts-board-count"><?= $count ?> sailing<?= $count === 1 ? '' : 's' ?> ditemukan</span>
                    </div>

                    <div class="ts-table-wrap">
                        <table class="ts-table">
                            <thead>
                                <tr>
                                    <th class="ts-col-no">No</th>
                                    <th>Vessel / Voyage</th>
                                    <th>Route</th>
                                    <th>CFS Closing</th>
                                    <th>ETD</th>
                                    <th>ETA</th>
                                    <th>Status</th>
                                    <th class="ts-col-action">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($scheduleRecords)): ?>
                                    <?php $no = 1; foreach ($scheduleRecords as $row): ?>
                                        <tr>
                                            <td class="ts-col-no" data-label="No"><?= $no++ ?></td>
                                            <td data-label="Vessel / Voyage">
                                                <div class="ts-vessel"><?= htmlspecialchars($row['vessel'] ?: '-', ENT_QUOTES) ?></div>
                                                <div class="ts-voyage"><?= htmlspecialchars($row['voyage'] ?: '-', ENT_QUOTES) ?></div>
                                            </td>
                                            <td data-label="Route">
                                                <span class="ts-route">
                                                    <span class="ts-port"><?= htmlspecialchars($row['pol'] ?: '-', ENT_QUOTES) ?></span>
                                                    <span class="ts-route-arrow" aria-hidden="true">&rarr;</span>
                                                    <span class="ts-port ts-port-dest"><?= htmlspecialchars($row['pod'] ?: '-', ENT_QUOTES) ?></span>
                                                </span>
                                            </td>
                                            <td data-label="CFS Closing"><?= $row['cfs_closing'] ? date('d M Y', strtotime($row['cfs_closing'])) : '-' ?></td>
                                            <td data-label="ETD" class="ts-etd"><?= $row['etd'] ? date('d M Y', strtotime($row['etd'])) : '-' ?></td>
                                            <td data-label="ETA" class="ts-eta"><?= $row['eta'] ? date('d M Y', strtotime($row['eta'])) : '-' ?></td>
                                            <td data-label="Status">
                                                <?php if ($row['is_tba']): ?>
                                                    <span class="ts-status ts-status-tba">TBA</span>
                                                <?php else: ?>
                                                    <span class="ts-status ts-status-ok">Scheduled</span>
                                                <?php endif; ?>
                                            </td>
                                            <td data-label="Action" class="ts-col-action">
                                                <button type="button" class="ts-btn-price"
                                                    data-vessel="<?= htmlspecialchars($row['vessel'] ?: '-', ENT_QUOTES) ?>"
                                                    data-voyage="<?= htmlspecialchars($row['voyage'] ?: '-', ENT_QUOTES) ?>"
                                                    data-pol="<?= htmlspecialchars($row['pol'] ?: '-', ENT_QUOTES) ?>"
                                                    data-pod="<?= htmlspecialchars($row['pod'] ?: '-', ENT_QUOTES) ?>"
                                                    data-dest-code="<?= htmlspecialchars($search_dest, ENT_QUOTES) ?>"
                                                    data-etd="<?= htmlspecialchars($row['etd'] ?: '-', ENT_QUOTES) ?>"
                                                    data-via="<?= htmlspecialchars($search_via, ENT_QUOTES) ?>">
                                                    Cek Harga
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr class="ts-empty-row">
                                        <td colspan="8">
                                            <div class="ts-empty">
                                                <p class="ts-empty-title">Belum ada jadwal ditemukan.</p>
                                                <p class="ts-empty-hint">Coba pelabuhan tujuan lain, atau ganti hub transhipment di atas.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </section>
</div>

<!-- Price modal (vanilla, no Bootstrap JS / jQuery dependency) -->
<div class="ts-modal" id="priceModal" hidden>
    <div class="ts-modal-backdrop" data-close-modal></div>
    <div class="ts-modal-panel" role="dialog" aria-modal="true" aria-labelledby="priceModalTitle" tabindex="-1">
        <button type="button" class="ts-modal-close" data-close-modal aria-label="Tutup">&times;</button>
        <h5 id="priceModalTitle">Cek Harga Transhipment</h5>
        <dl class="ts-modal-meta">
            <div><dt>Vessel</dt><dd id="pmVessel">-</dd></div>
            <div><dt>Voyage</dt><dd id="pmVoyage">-</dd></div>
            <div><dt>Route</dt><dd id="pmRoute">-</dd></div>
            <div><dt>ETD</dt><dd id="pmEtd">-</dd></div>
        </dl>
        <div class="ts-modal-body" id="priceModalBody">
            <div class="ts-modal-loading">Mengambil data harga&hellip;</div>
        </div>
    </div>
</div>

<style>
.ts-page {
    --ts-ink: #0B2036;
    --ts-ink-2: #14304A;
    --ts-teal: #2C8C86;
    --ts-teal-dark: #226B67;
    --ts-amber: #DE9F3C;
    --ts-green: #2E9E6D;
    --ts-bg: #F5F7FA;
    --ts-surface: #FFFFFF;
    --ts-line: #E1E7EE;
    --ts-text: #1C2B3A;
    --ts-text-muted: #68798C;
    --ts-font: 'Segoe UI', system-ui, -apple-system, 'Helvetica Neue', Arial, sans-serif;
    --ts-mono: Consolas, 'SFMono-Regular', 'Cascadia Mono', Menlo, monospace;
    --ts-field-height: 52px;
    color: var(--ts-text);
    font-family: var(--ts-font);
}

.ts-page .container-fluid { max-width: 1200px; }

/* Header */
.ts-pagehead { display: flex; align-items: flex-start; gap: 12px; padding: 4px 0 18px; }
.ts-pagehead-icon { color: var(--ts-teal); flex-shrink: 0; margin-top: 3px; }
.ts-title { margin: 0; font-size: 1.5rem; font-weight: 700; color: var(--ts-ink); letter-spacing: -0.01em; }
.ts-subtitle { margin: 3px 0 0; font-size: 0.92rem; color: var(--ts-text-muted); }

/* Search console */
.ts-console {
    background: linear-gradient(155deg, var(--ts-ink) 0%, var(--ts-ink-2) 100%);
    border-radius: 10px;
    padding: 22px 24px;
    margin-bottom: 22px;
    box-shadow: 0 8px 24px rgba(11, 32, 54, 0.18);
}
.ts-console-grid { display: grid; grid-template-columns: 1.4fr 1.4fr auto; gap: 18px; align-items: start; }
.ts-field { display: flex; flex-direction: column; min-width: 0; }
.ts-field label, .ts-field-label {
    font-size: 0.78rem; font-weight: 600; color: #B9CBDD; margin-bottom: 7px;
}
.ts-field-error { color: #FFC9A3; font-size: 0.8rem; margin: 6px 0 0; }

/* Combobox */
.ts-combobox { position: relative; }
.ts-combobox-input {
    width: 100%; box-sizing: border-box; height: var(--ts-field-height); padding: 0 12px;
    border: 1px solid rgba(255,255,255,0.16); border-radius: 7px;
    background: rgba(255,255,255,0.06); color: #fff; font-size: 0.94rem;
    transition: border-color .15s, background .15s;
}
.ts-combobox-input::placeholder { color: #7E93A8; }
.ts-combobox-input:focus {
    outline: none; border-color: var(--ts-teal); background: rgba(255,255,255,0.1);
}
.ts-combobox-list {
    position: absolute; z-index: 20; top: calc(100% + 6px); left: 0; right: 0;
    background: var(--ts-surface); border-radius: 8px; box-shadow: 0 12px 28px rgba(11,32,54,0.28);
    list-style: none; margin: 0; padding: 6px; max-height: 260px; overflow-y: auto;
}
.ts-combobox-option {
    padding: 9px 10px; border-radius: 6px; font-size: 0.9rem; color: var(--ts-text);
    cursor: pointer; line-height: 1.35;
}
.ts-combobox-option:hover, .ts-combobox-option.is-active { background: #EAF3F2; color: var(--ts-teal-dark); }
.ts-combobox-empty, .ts-combobox-loading { padding: 10px; font-size: 0.85rem; color: var(--ts-text-muted); }

/* Hub toggle (pure CSS, radio + label) */
.ts-hub-toggle { display: flex; gap: 10px; }
.ts-hub-toggle input[type="radio"] { position: absolute; opacity: 0; width: 0; height: 0; }
.ts-hub-option {
    flex: 1; box-sizing: border-box; height: var(--ts-field-height);
    display: flex; flex-direction: column; justify-content: center; gap: 2px; padding: 0 12px;
    border: 1px solid rgba(255,255,255,0.16); border-radius: 7px;
    background: rgba(255,255,255,0.05); cursor: pointer; transition: border-color .15s, background .15s;
}
.ts-hub-name { color: #fff; font-size: 0.92rem; font-weight: 600; }
.ts-hub-partner { color: #93A9BE; font-size: 0.76rem; }
.ts-hub-toggle input[type="radio"]:checked + .ts-hub-option {
    border-color: var(--ts-teal); background: rgba(44,140,134,0.22);
}
.ts-hub-toggle input[type="radio"]:focus-visible + .ts-hub-option {
    outline: 2px solid var(--ts-teal); outline-offset: 2px;
}

.ts-search-btn {
    display: flex; align-items: center; justify-content: center; gap: 8px; padding: 0 22px;
    height: var(--ts-field-height); box-sizing: border-box;
    background: var(--ts-teal); color: #fff; border: none; border-radius: 7px;
    font-size: 0.94rem; font-weight: 600; cursor: pointer; align-self: end;
    transition: background .15s;
}
.ts-search-btn:hover { background: var(--ts-teal-dark); }
.ts-search-btn:focus-visible { outline: 2px solid #fff; outline-offset: 2px; }

/* Notice */
.ts-notice {
    display: flex; gap: 10px; align-items: flex-start;
    background: #FDF3E3; border: 1px solid #F1D6A0; color: #7A5416;
    border-radius: 8px; padding: 12px 14px; margin-bottom: 20px;
}
.ts-notice svg { flex-shrink: 0; margin-top: 2px; color: var(--ts-amber); }
.ts-notice strong { font-size: 0.85rem; }
.ts-notice p { margin: 2px 0 0; font-size: 0.88rem; }

/* Results board */
.ts-board { background: var(--ts-surface); border: 1px solid var(--ts-line); border-radius: 10px; overflow: hidden; }
.ts-board-head {
    display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 6px;
    padding: 16px 20px; border-bottom: 1px solid var(--ts-line);
}
.ts-board-head h5 { margin: 0; font-size: 1.02rem; font-weight: 700; color: var(--ts-ink); }
.ts-board-count { font-size: 0.82rem; color: var(--ts-text-muted); }

.ts-table-wrap { overflow-x: auto; }
.ts-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
.ts-table thead th {
    text-align: left; padding: 11px 16px; background: #F8FAFC; color: var(--ts-text-muted);
    font-size: 0.74rem; font-weight: 600; text-transform: none; border-bottom: 1px solid var(--ts-line);
    white-space: nowrap;
}
.ts-table td { padding: 13px 16px; border-bottom: 1px solid var(--ts-line); vertical-align: middle; }
.ts-table tbody tr:last-child td { border-bottom: none; }
.ts-table tbody tr:hover { background: #FAFBFD; }
.ts-col-no { width: 44px; color: var(--ts-text-muted); font-variant-numeric: tabular-nums; }
.ts-col-action { width: 130px; text-align: right; }

.ts-vessel { font-weight: 600; color: var(--ts-ink); }
.ts-voyage { font-family: var(--ts-mono); font-size: 0.82rem; color: var(--ts-text-muted); margin-top: 1px; }

.ts-route { display: inline-flex; align-items: center; gap: 6px; font-family: var(--ts-mono); font-size: 0.86rem; }
.ts-route-arrow { color: var(--ts-text-muted); }
.ts-port-dest { font-weight: 700; color: var(--ts-ink); }

.ts-etd { font-family: var(--ts-mono); font-weight: 700; color: var(--ts-teal-dark); }
.ts-eta { font-family: var(--ts-mono); color: var(--ts-text); }

.ts-status { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 0.76rem; font-weight: 600; }
.ts-status-ok { background: #E5F4EC; color: var(--ts-green); }
.ts-status-tba { background: #FBEFDA; color: #A9720F; }

.ts-btn-price {
    padding: 7px 14px; border-radius: 6px; border: 1px solid var(--ts-teal);
    background: #fff; color: var(--ts-teal-dark); font-size: 0.82rem; font-weight: 600;
    cursor: pointer; transition: background .15s, color .15s;
}
.ts-btn-price:hover { background: var(--ts-teal); color: #fff; }

.ts-empty { text-align: center; padding: 34px 20px; }
.ts-empty-title { margin: 0; font-weight: 600; color: var(--ts-ink); }
.ts-empty-hint { margin: 4px 0 0; font-size: 0.86rem; color: var(--ts-text-muted); }

/* Modal */
.ts-modal[hidden] { display: none; }
.ts-modal { position: fixed; inset: 0; z-index: 1050; display: flex; align-items: center; justify-content: center; }
.ts-modal-backdrop { position: absolute; inset: 0; background: rgba(11,32,54,0.55); }
.ts-modal-panel {
    position: relative; background: #fff; border-radius: 10px; width: min(440px, 92vw);
    max-height: 86vh; overflow-y: auto; padding: 24px; box-shadow: 0 20px 50px rgba(0,0,0,0.25);
    animation: ts-modal-in .15s ease-out;
}
@keyframes ts-modal-in { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }
.ts-modal-close {
    position: absolute; top: 14px; right: 14px; width: 30px; height: 30px; border-radius: 50%;
    border: none; background: #F1F4F8; color: var(--ts-text-muted); font-size: 1.2rem; line-height: 1;
    cursor: pointer;
}
.ts-modal-close:hover { background: #E4E9EF; }
.ts-modal-panel h5 { margin: 0 0 14px; font-size: 1.05rem; font-weight: 700; color: var(--ts-ink); padding-right: 24px; }
.ts-modal-meta { display: grid; grid-template-columns: 1fr 1fr; gap: 10px 14px; margin: 0 0 16px; padding-bottom: 16px; border-bottom: 1px dashed var(--ts-line); }
.ts-modal-meta dt { font-size: 0.72rem; color: var(--ts-text-muted); margin-bottom: 2px; }
.ts-modal-meta dd { margin: 0; font-family: var(--ts-mono); font-size: 0.88rem; color: var(--ts-ink); }
.ts-modal-loading, .ts-modal-error { text-align: center; padding: 18px 6px; color: var(--ts-text-muted); font-size: 0.9rem; }
.ts-modal-error { color: #A9720F; }

.ts-price-notice {
    background: #FDF3E3; border: 1px solid #F1D6A0; color: #7A5416;
    border-radius: 7px; padding: 10px 12px; font-size: 0.83rem; margin-bottom: 14px; line-height: 1.4;
}
.ts-price-highlight { display: flex; gap: 12px; margin-bottom: 16px; }
.ts-price-highlight > div {
    flex: 1; background: #F5F9F8; border: 1px solid #DCEAE8; border-radius: 8px; padding: 10px 12px;
    display: flex; flex-direction: column; gap: 3px;
}
.ts-price-highlight-label { font-size: 0.72rem; color: var(--ts-text-muted); }
.ts-price-highlight-value { font-family: var(--ts-mono); font-size: 1.05rem; font-weight: 700; color: var(--ts-teal-dark); }
.ts-price-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px 14px; margin-bottom: 14px; }
.ts-price-item { display: flex; flex-direction: column; gap: 2px; }
.ts-price-label { font-size: 0.72rem; color: var(--ts-text-muted); }
.ts-price-value { font-size: 0.87rem; color: var(--ts-ink); font-family: var(--ts-mono); }
.ts-price-remarks {
    font-size: 0.85rem; color: var(--ts-text); background: #F8FAFC; border-radius: 7px;
    padding: 10px 12px; margin: 0 0 14px; white-space: pre-line; line-height: 1.45;
}
.ts-price-details { margin-bottom: 14px; border: 1px solid var(--ts-line); border-radius: 7px; padding: 4px 12px; }
.ts-price-details summary { cursor: pointer; padding: 8px 0; font-size: 0.85rem; font-weight: 600; color: var(--ts-teal-dark); }
.ts-price-details-body {
    white-space: pre-line; font-size: 0.83rem; color: var(--ts-text); line-height: 1.5;
    padding: 4px 0 12px; max-height: 220px; overflow-y: auto;
}
.ts-price-footer { font-size: 0.78rem; color: var(--ts-text-muted); border-top: 1px dashed var(--ts-line); padding-top: 10px; }

.ts-price-multi-note { font-size: 0.85rem; color: var(--ts-text-muted); margin: 0 0 14px; }
.ts-price-dest-heading { margin: 0 0 10px; font-size: 0.92rem; font-weight: 700; color: var(--ts-ink); }
.ts-price-divider { border: none; border-top: 1px solid var(--ts-line); margin: 18px 0; }

/* Motion & accessibility */
@media (prefers-reduced-motion: reduce) {
    .ts-modal-panel { animation: none; }
}
.ts-page :focus-visible { outline: 2px solid var(--ts-teal); outline-offset: 2px; }

/* Responsive */
@media (max-width: 820px) {
    .ts-console-grid { grid-template-columns: 1fr; }
    .ts-search-btn { justify-content: center; }
}
@media (max-width: 680px) {
    .ts-table thead { display: none; }
    .ts-table, .ts-table tbody, .ts-table tr, .ts-table td { display: block; width: 100%; }
    .ts-table tr { border-bottom: 1px solid var(--ts-line); padding: 12px 16px; }
    .ts-table td {
        display: flex; justify-content: space-between; align-items: center; gap: 12px;
        padding: 6px 0; border-bottom: none; text-align: right;
    }
    .ts-table td::before { content: attr(data-label); font-size: 0.76rem; color: var(--ts-text-muted); text-align: left; }
    .ts-col-action { text-align: left; }
    .ts-col-action::before { display: none; }
    .ts-btn-price { width: 100%; }
}
</style>

<script>
(function () {
    "use strict";

    var SEARCH_URL = <?= json_encode(site_url('transhipment/ajax_search_destination')) ?>;
    // Lookup rate dari ts_rates (join ts_tariff_headers) -- lihat Transhipment::ajax_get_harga()
    var PRICE_URL = <?= json_encode(site_url('transhipment/ajax_get_harga')) ?>;

    /* ---------- Destination combobox ---------- */
    var input = document.getElementById('destinationInput');
    var hidden = document.getElementById('destinationValue');
    var list = document.getElementById('destinationList');
    var errorEl = document.getElementById('destinationError');
    var debounceTimer = null;
    var activeIndex = -1;
    var currentController = null;

    function closeList() {
        list.hidden = true;
        list.innerHTML = '';
        activeIndex = -1;
        input.setAttribute('aria-expanded', 'false');
    }

    function renderMessage(text) {
        list.innerHTML = '';
        var li = document.createElement('li');
        li.className = 'ts-combobox-loading';
        li.textContent = text;
        list.appendChild(li);
        list.hidden = false;
        input.setAttribute('aria-expanded', 'true');
    }

    function renderResults(results) {
        list.innerHTML = '';
        activeIndex = -1;
        if (!results.length) {
            renderMessage('Tidak ada tujuan yang cocok.');
            return;
        }
        results.forEach(function (item, idx) {
            var li = document.createElement('li');
            li.className = 'ts-combobox-option';
            li.setAttribute('role', 'option');
            li.dataset.id = item.id;
            li.dataset.text = item.text;
            li.textContent = item.text;
            li.addEventListener('mousedown', function (e) {
                e.preventDefault();
                selectOption(item);
            });
            list.appendChild(li);
        });
        list.hidden = false;
        input.setAttribute('aria-expanded', 'true');
    }

    function selectOption(item) {
        input.value = item.text;
        hidden.value = item.id;
        errorEl.hidden = true;
        closeList();
    }

    function highlight(idx) {
        var opts = list.querySelectorAll('.ts-combobox-option');
        opts.forEach(function (el) { el.classList.remove('is-active'); });
        if (opts[idx]) {
            opts[idx].classList.add('is-active');
            opts[idx].scrollIntoView({ block: 'nearest' });
        }
    }

    input.addEventListener('input', function () {
        var term = input.value.trim();
        hidden.value = ''; // paksa pilih ulang dari daftar setiap kali teks berubah
        clearTimeout(debounceTimer);
        if (term.length < 3) { closeList(); return; }

        debounceTimer = setTimeout(function () {
            if (currentController) currentController.abort();
            currentController = new AbortController();
            renderMessage('Mencari…');

            fetch(SEARCH_URL + '?q=' + encodeURIComponent(term), { signal: currentController.signal })
                .then(function (res) { return res.json(); })
                .then(function (data) { renderResults(data.results || []); })
                .catch(function (err) {
                    if (err.name !== 'AbortError') renderMessage('Gagal memuat daftar tujuan.');
                });
        }, 250);
    });

    input.addEventListener('keydown', function (e) {
        var opts = list.querySelectorAll('.ts-combobox-option');
        if (list.hidden || !opts.length) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            activeIndex = Math.min(activeIndex + 1, opts.length - 1);
            highlight(activeIndex);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            activeIndex = Math.max(activeIndex - 1, 0);
            highlight(activeIndex);
        } else if (e.key === 'Enter') {
            if (activeIndex >= 0 && opts[activeIndex]) {
                e.preventDefault();
                selectOption({ id: opts[activeIndex].dataset.id, text: opts[activeIndex].dataset.text });
            }
        } else if (e.key === 'Escape') {
            closeList();
        }
    });

    document.addEventListener('click', function (e) {
        if (!document.getElementById('destinationCombobox').contains(e.target)) closeList();
    });

    /* ---------- Hub toggle validation ---------- */
    var hubError = document.getElementById('hubError');

    /* ---------- Form submit validation ---------- */
    document.getElementById('tsSearchForm').addEventListener('submit', function (e) {
        var valid = true;

        if (!hidden.value) {
            errorEl.hidden = false;
            input.focus();
            valid = false;
        } else {
            errorEl.hidden = true;
        }

        var hubChecked = document.querySelector('input[name="via"]:checked');
        if (!hubChecked) {
            hubError.hidden = false;
            if (valid) document.getElementById('hubSin').focus();
            valid = false;
        } else {
            hubError.hidden = true;
        }

        if (!valid) e.preventDefault();
    });

    /* ---------- Price modal ---------- */
    var modal = document.getElementById('priceModal');
    var modalBody = document.getElementById('priceModalBody');
    var lastTrigger = null;

    function openModal(data) {
        lastTrigger = data.trigger;
        document.getElementById('pmVessel').textContent = data.vessel;
        document.getElementById('pmVoyage').textContent = data.voyage;
        document.getElementById('pmRoute').textContent = data.pol + ' \u2192 ' + data.pod;
        document.getElementById('pmEtd').textContent = data.etd;
        modalBody.innerHTML = '<div class="ts-modal-loading">Mengambil data harga&hellip;</div>';
        modal.hidden = false;
        document.body.style.overflow = 'hidden';
        modal.querySelector('.ts-modal-panel').focus();

        var params = new URLSearchParams({
            pod: data.pod, dest_code: data.destCode, via: data.via
        });

        fetch(PRICE_URL + '?' + params.toString())
            .then(function (res) { return res.json(); })
            .then(function (json) {
                if (json.status) renderPrices(json.rates);
                else renderNotFound(json.message);
            })
            .catch(function () {
                modalBody.innerHTML =
                    '<div class="ts-modal-error">Gagal mengambil data harga. Coba lagi sebentar lagi.</div>';
            });
    }

    function renderNotFound(message) {
        modalBody.innerHTML = '';
        var div = document.createElement('div');
        div.className = 'ts-modal-error';
        div.textContent = message || 'Rate untuk tujuan ini belum ada di tariff master.';
        modalBody.appendChild(div);
    }

    // val -> teks aman utk ditampilkan; pakai '-' kalau kosong/null
    function val(v) { return (v === null || v === undefined || v === '') ? '-' : v; }

    // Baris label/nilai di dalam grid harga
    function priceItem(label, value) {
        var item = document.createElement('div');
        item.className = 'ts-price-item';
        var dt = document.createElement('span'); dt.className = 'ts-price-label'; dt.textContent = label;
        var dd = document.createElement('span'); dd.className = 'ts-price-value'; dd.textContent = value;
        item.appendChild(dt); item.appendChild(dd);
        return item;
    }

    function renderPrices(rates) {
        modalBody.innerHTML = '';

        if (rates.length > 1) {
            var summary = document.createElement('p');
            summary.className = 'ts-price-multi-note';
            summary.textContent = 'Ditemukan ' + rates.length + ' tujuan akhir yang lewat port ini. Pilih yang sesuai dengan consignee:';
            modalBody.appendChild(summary);
        }

        rates.forEach(function (rate, idx) {
            var heading = document.createElement('h6');
            heading.className = 'ts-price-dest-heading';
            heading.textContent = rate.destination + (rate.country ? ', ' + rate.country : '');
            modalBody.appendChild(heading);
            modalBody.appendChild(renderRateCard(rate));
            if (idx < rates.length - 1) {
                var hr = document.createElement('hr');
                hr.className = 'ts-price-divider';
                modalBody.appendChild(hr);
            }
        });
    }

    function renderRateCard(rate) {
        var card = document.createElement('div');
        card.className = 'ts-price-card';
        var currency = val(rate.currency_code) !== '-' ? rate.currency_code : 'USD';

        // Notice kalau freight belum ada nilainya (rute suspend/case-by-case/dsb)
        if (rate.freight_wm === null || rate.freight_wm === undefined || rate.freight_wm === '') {
            var notice = document.createElement('div');
            notice.className = 'ts-price-notice';
            notice.textContent = 'Freight rate belum tercantum otomatis untuk tujuan ini -- cek catatan/status di bawah, atau konfirmasi ke tim pricing sebelum quote ke customer.';
            card.appendChild(notice);
        }

        // Highlight: freight rate + min CBM
        var highlight = document.createElement('div');
        highlight.className = 'ts-price-highlight';
        var freightBox = document.createElement('div');
        freightBox.innerHTML =
            '<span class="ts-price-highlight-label">Freight (per WM/CBM)</span>' +
            '<span class="ts-price-highlight-value">' +
            (val(rate.freight_wm) !== '-' ? currency + ' ' + Number(rate.freight_wm).toFixed(2) : '-') +
            '</span>';
        var minBox = document.createElement('div');
        minBox.innerHTML =
            '<span class="ts-price-highlight-label">Min. CBM</span>' +
            '<span class="ts-price-highlight-value">' + val(rate.min_cbm) + '</span>';
        highlight.appendChild(freightBox);
        highlight.appendChild(minBox);
        card.appendChild(highlight);

        // Grid detail utama
        var grid = document.createElement('div');
        grid.className = 'ts-price-grid';
        var tt = val(rate.transit_time_1st) + (rate.transit_time_2nd ? ' + ' + rate.transit_time_2nd : '') + ' hari';
        grid.appendChild(priceItem('Transit Time', val(rate.transit_time_1st) !== '-' ? tt : '-'));
        grid.appendChild(priceItem('Frequency', val(rate.frequency)));
        grid.appendChild(priceItem('Via Port', val(rate.via_port) + (rate.via_service_note ? ' (' + rate.via_service_note + ')' : '')));
        grid.appendChild(priceItem('Fumigation', val(rate.fumigation)));
        grid.appendChild(priceItem('Freight Collect', rate.freight_collect === 'Y' ? 'Ya' : (rate.freight_collect === 'N' ? 'Tidak' : '-')));
        grid.appendChild(priceItem('Telex Release', rate.telex_release === 'Y' ? 'Ya' : (rate.telex_release === 'N' ? 'Tidak' : '-')));
        card.appendChild(grid);

        // Surcharge & DG, hanya tampil kalau ada isinya
        var extras = [
            ['Surcharge GRI', rate.surcharge_gri],
            ['Surcharge DDC', rate.surcharge_ddc],
            ['Surcharge AMS', rate.surcharge_ams],
            ['DG Class 2', rate.dg_class2],
            ['DG Class 3', rate.dg_class3],
        ].filter(function (pair) { return pair[1] !== null && pair[1] !== undefined && pair[1] !== ''; });

        if (extras.length) {
            var grid2 = document.createElement('div');
            grid2.className = 'ts-price-grid';
            extras.forEach(function (pair) { grid2.appendChild(priceItem(pair[0], pair[1])); });
            card.appendChild(grid2);
        }

        // Remarks singkat
        if (rate.remarks) {
            var remarks = document.createElement('p');
            remarks.className = 'ts-price-remarks';
            remarks.textContent = rate.remarks;
            card.appendChild(remarks);
        }

        // Catatan tariff lengkap -- collapsible, native <details> (tanpa JS tambahan)
        if (rate.tariff_remarks) {
            var details = document.createElement('details');
            details.className = 'ts-price-details';
            var summary2 = document.createElement('summary');
            summary2.textContent = 'Catatan tariff lengkap untuk tujuan ini';
            var body = document.createElement('div');
            body.className = 'ts-price-details-body';
            body.textContent = rate.tariff_remarks;
            details.appendChild(summary2);
            details.appendChild(body);
            card.appendChild(details);
        }

        // Footer meta
        var footer = document.createElement('div');
        footer.className = 'ts-price-footer';
        footer.textContent = 'Tariff berlaku: ' + val(rate.validity) + (rate.agent_name ? ' \u2022 Agent: ' + rate.agent_name : '');
        card.appendChild(footer);

        return card;
    }

    function closeModal() {
        modal.hidden = true;
        document.body.style.overflow = '';
        if (lastTrigger) lastTrigger.focus();
    }

    document.querySelectorAll('.ts-btn-price').forEach(function (btn) {
        btn.addEventListener('click', function () {
            openModal({
                vessel: btn.dataset.vessel, voyage: btn.dataset.voyage,
                pol: btn.dataset.pol, pod: btn.dataset.pod,
                destCode: btn.dataset.destCode,
                etd: btn.dataset.etd, via: btn.dataset.via, trigger: btn
            });
        });
    });

    modal.addEventListener('click', function (e) {
        if (e.target.hasAttribute('data-close-modal')) closeModal();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.hidden) closeModal();
    });
})();
</script>
