<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<?php
// =========================================================================
// PRE-PROCESSING DATA
// =========================================================================
$fcl_routes = [];
$lcl_routes = [];

if (!empty($grouped_rates)) {
    foreach($grouped_rates as $route => $rates) {
        $f_rates = [];
        $l_rates = [];
        
        foreach($rates as $r) {
            // Cek apakah punya harga FCL
            $has_fcl = ((float)$r['prices']['20'] > 0 || (float)$r['prices']['40'] > 0 || (float)$r['prices']['40HC'] > 0);
            
            // Cek apakah punya harga LCL 
            $has_lcl = (!empty($r['lcl_options']) || (isset($r['lcl_price']) && $r['lcl_price'] !== null && $r['lcl_price'] !== ''));
            
            if ($has_fcl) $f_rates[] = $r;
            if ($has_lcl) $l_rates[] = $r;
        }
        
        if (count($f_rates) > 0) $fcl_routes[$route] = $f_rates;
        if (count($l_rates) > 0) $lcl_routes[$route] = $l_rates;
    }
}

// Inisialisasi fallback agar tidak error jika controller belum mengirim data
$air_list = isset($air_rates) ? $air_rates : []; 
$trucking_list = isset($trucking_rates) ? $trucking_rates : [];

// Menangani data Clearance jika belum ada
$clearance_tariffs = isset($clearance_data['tariffs']) 
    ? $clearance_data['tariffs'] 
    : [
        'EXP_LCL'   => [],
        'EXP_FCL'   => [],
        'IMP_LCL'   => [],
        'IMP_FCL'   => [],
        'FASILITAS' => []
    ];

$clearance_info = isset($clearance_data['info']) ? $clearance_data['info'] : (object)['doc_requirements' => '', 'terms_conditions' => '', 'time_processing' => ''];


function format_idr($val) {
    if (empty($val) || $val == 0) return '<span class="text-white-50">-</span>';
    return 'Rp ' . number_format($val, 0, ',', '.');
}
?>

<style>
    /* GLASS TAB PILLS UNTUK LIST DEPAN */
    .glass-tab-btn {
        background: rgba(255, 255, 255, 0.05);
        color: #fff; border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 30px;
        padding: 10px 24px; font-weight: 600; transition: all 0.3s ease;
    }
    .glass-tab-btn:hover { background: rgba(255, 255, 255, 0.1); color: #fff; }
    
    .glass-tab-btn-info.active { background: #0dcaf0 !important; color: #000 !important; box-shadow: 0 0 15px rgba(13, 202, 240, 0.4); border-color: #0dcaf0; }
    .glass-tab-btn-warning.active { background: #ffc107 !important; color: #000 !important; box-shadow: 0 0 15px rgba(255, 193, 7, 0.4); border-color: #ffc107; }
    .glass-tab-btn-primary.active { background: #0d6efd !important; color: #fff !important; box-shadow: 0 0 15px rgba(13, 110, 253, 0.4); border-color: #0d6efd; }
    .glass-tab-btn-success.active { background: #198754 !important; color: #fff !important; box-shadow: 0 0 15px rgba(25, 135, 84, 0.4); border-color: #198754; }

    .table-glass th, .table-glass td { font-size: 0.85rem !important; }
    .pricing-page {
        background: linear-gradient(135deg, #0b131e, #1a2a3a, #203a43); color: #fff;
        font-family: 'Segoe UI', sans-serif; min-height: calc(100vh - 80px); padding: 25px; border-radius: 12px;
    }
    .search-wrap { position: relative; width: 320px; max-width: 100%; flex-shrink: 0; }
    @media (max-width: 768px) { .search-wrap { width: 100%; } }
    .search-icon { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #aaa; font-size: 1.2rem; pointer-events: none; z-index: 2; }
    
    /* SEARCH BAR GLASSMORPHISM FIX */
    .glass-input.form-control {
        padding-left: 45px !important; 
        height: 48px !important; 
        border-radius: 30px !important;
        border: 1px solid rgba(255, 255, 255, 0.2) !important; 
        background-color: rgba(25, 35, 50, 0.6) !important; 
        backdrop-filter: blur(6px); 
        color: #fff !important; 
        font-size: 1rem;
    }
    
    .glass-input.form-control:focus { 
        outline: none !important; 
        border-color: #0dcaf0 !important; 
        box-shadow: 0 0 8px rgba(13, 202, 240, 0.5) !important; 
        background-color: rgba(25, 35, 50, 0.9) !important; 
        color: #fff !important;
    }
    
    .glass-input::placeholder { color: rgba(255,255,255,0.5) !important; }

    .glass-input.form-control:-webkit-autofill,
    .glass-input.form-control:-webkit-autofill:hover, 
    .glass-input.form-control:-webkit-autofill:focus, 
    .glass-input.form-control:-webkit-autofill:active {
        -webkit-box-shadow: 0 0 0 30px #1a2a3a inset !important;
        -webkit-text-fill-color: white !important;
        transition: background-color 5000s ease-in-out 0s;
    }

    .glass-accordion { max-height: 65vh; overflow-y: auto; overflow-x: hidden; padding-right: 4px; }
    .glass-accordion::-webkit-scrollbar { width: 8px; }
    .glass-accordion::-webkit-scrollbar-track { background: rgba(255,255,255,0.06); border-radius: 10px; }
    .glass-accordion::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.22); border-radius: 10px; }
    
    .glass-accordion .accordion-item { background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); margin-bottom: 10px; border-radius: 10px !important; overflow: hidden; }
    .glass-accordion .accordion-button { background: transparent; color: white; box-shadow: none; font-weight: 700; text-transform: uppercase; }
    .glass-accordion .accordion-button:not(.collapsed) { background: rgba(0, 255, 195, 0.1); color: #00ffc3; border-bottom: 1px solid rgba(255,255,255,0.1); }
    .glass-accordion .accordion-button::after { filter: brightness(0) invert(1); }

    .table-glass { color: rgba(255,255,255,0.9); margin-bottom: 0; }
    .table-glass th { background: rgba(0,0,0,0.3) !important; border-color: rgba(255,255,255,0.1); font-weight: 600; position: sticky; top: 0; z-index: 2; }
    .table-glass td { border-color: rgba(255,255,255,0.05); background: transparent; }
    .table-hover tbody tr:hover td { background-color: rgba(255, 255, 255, 0.08) !important; }

    .badge-genco { background: #00b09b; color: #fff; }
    .badge-dg { background: #ff4b2b; color: #fff; }
    .badge-reefer { background: #0dcaf0; color: #000; } 
    .badge-breakbulk { background: #fd7e14; color: #fff; } 
    
    /* MODAL TABS GLASSMORPHISM OVERRIDE */
    .glass-modal { background: rgba(15, 25, 35, 0.96) !important; backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.15); border-radius: 20px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); }
    .info-box { background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.08); padding: 15px; border-radius: 12px; height: 100%; }
    .info-box label { font-size: 0.75rem; text-transform: uppercase; color: rgba(255,255,255,0.5); font-weight: 600; margin-bottom: 6px; }
    .info-content { font-size: 0.85rem; color: #efefef; line-height: 1.5; white-space: pre-line; }
    .table-header-group td { border-bottom: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.02); }
    .row-total-allin td { border-top: 2px solid rgba(255,255,255,0.2); background: rgba(0, 255, 195, 0.05); }

    /* CLEARANCE SPECIFIC STYLES */
    .clr-info-box {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.08);
        padding: 20px;
        border-radius: 16px;
        height: 100%;
    }
    .clr-info-box.amber { border-color: rgba(251, 191, 36, 0.3); background: rgba(251, 191, 36, 0.05); }
    .clr-info-box.rose { border-color: rgba(244, 63, 94, 0.3); background: rgba(244, 63, 94, 0.05); }
    .clr-info-box.cyan { border-color: rgba(34, 211, 238, 0.3); background: rgba(34, 211, 238, 0.05); }
    
    .clr-info-box h6 { font-size: 0.7rem; font-weight: 900; letter-spacing: 0.2em; text-transform: uppercase; margin-bottom: 12px; }
    .clr-info-box.amber h6 { color: #fcd34d; }
    .clr-info-box.rose h6 { color: #fda4af; }
    .clr-info-box.cyan h6 { color: #67e8f9; }
    
    .clr-info-content { font-size: 0.85rem; line-height: 1.6; white-space: pre-line; color: #e2e8f0; }
</style>

<div class="pricing-page">
    <div class="container-fluid py-2">
		
        <div class="d-flex align-items-center mb-4 flex-wrap gap-3">
            <h2 class="fw-bold mb-0 text-uppercase me-3">
                <i class="bi bi-shield-check text-info me-2"></i> Gateway Pricing Database
            </h2>

            <div class="search-wrap ms-auto" id="mainSearchWrap">
                <i class="bi bi-search search-icon"></i>
                <input type="text" id="searchInput" class="form-control glass-input" placeholder="Search route, destination, liner..." autocomplete="off">
            </div>
        </div>

        <ul class="nav nav-pills mb-4 gap-3" id="mainPricingTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active glass-tab-btn glass-tab-btn-info" id="list-fcl-tab" data-bs-toggle="pill" data-bs-target="#list-fcl" type="button" role="tab" aria-controls="list-fcl" aria-selected="true" onclick="toggleSearch(true)">
                    <i class="fa-solid fa-box me-2"></i> FCL
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link glass-tab-btn glass-tab-btn-warning" id="list-lcl-tab" data-bs-toggle="pill" data-bs-target="#list-lcl" type="button" role="tab" aria-controls="list-lcl" aria-selected="false" onclick="toggleSearch(true)">
                    <i class="fa-solid fa-boxes-stacked me-2"></i> LCL
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link glass-tab-btn glass-tab-btn-primary" id="list-air-tab" data-bs-toggle="pill" data-bs-target="#list-air" type="button" role="tab" aria-controls="list-air" aria-selected="false" onclick="toggleSearch(true)">
                    <i class="fa-solid fa-plane-departure me-2"></i> Air
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link glass-tab-btn glass-tab-btn-success" id="list-truck-tab" data-bs-toggle="pill" data-bs-target="#list-truck" type="button" role="tab" aria-controls="list-truck" aria-selected="false" onclick="toggleSearch(true)">
                    <i class="fa-solid fa-truck-fast me-2"></i> Trucking
                </button>
            </li>
			<li class="nav-item" role="presentation">
                <button class="nav-link glass-tab-btn glass-tab-btn-primary" id="list-transhipment-tab" data-bs-toggle="pill" data-bs-target="#list-transhipment" type="button" role="tab" aria-controls="list-transhipment" aria-selected="false" onclick="toggleSearch(true)">
                    <i class="fa-solid fa-ship me-2"></i> Transhipment
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link glass-tab-btn glass-tab-btn-success" id="list-clearance-tab" data-bs-toggle="pill" data-bs-target="#list-clearance" type="button" role="tab" aria-controls="list-clearance" aria-selected="false" onclick="toggleSearch(false)">
                    <i class="fa-solid fa-file-signature me-2"></i>Clearance
                </button>
            </li>
        </ul>

        <div class="tab-content" id="mainPricingTabContent">
            
            <div class="tab-pane fade show active" id="list-fcl" role="tabpanel" aria-labelledby="list-fcl-tab">
                <div class="accordion glass-accordion" id="fclAccordion">
                    <?php if(empty($fcl_routes)): ?>
                        <div class="text-center text-white-50 my-5 py-5 border border-secondary border-opacity-25 rounded-4 bg-dark bg-opacity-25">
                            <i class="bi bi-box fs-1 mb-3 d-block"></i> No FCL rates available.
                        </div>
                    <?php endif; ?>

                    <?php $i = 0; foreach($fcl_routes as $route => $rates): $i++; ?>
                        <div class="accordion-item dest-item" data-dest="<?= strtolower(htmlspecialchars($route)) ?>">
                            <h2 class="accordion-header" id="fclHeading<?= $i ?>">
                                <button class="accordion-button collapsed fw-bold text-uppercase" type="button" data-bs-toggle="collapse" data-bs-target="#fclCol<?= $i ?>" aria-expanded="false" aria-controls="fclCol<?= $i ?>">
                                    <i class="fa-solid fa-ship me-2 text-info"></i>
                                    <span class="flex-grow-1 text-start text-truncate pr-2"><?= htmlspecialchars($route) ?></span>
                                    <span class="badge bg-info text-dark bg-opacity-75 border border-info me-3"><?= count($rates) ?> LINERS</span>
                                </button>
                            </h2>

                            <div id="fclCol<?= $i ?>" class="accordion-collapse collapse" aria-labelledby="fclHeading<?= $i ?>" data-bs-parent="#fclAccordion">
                                <div class="accordion-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover table-glass align-middle text-center">
                                            <thead>
                                                <tr>
                                                    <th class="text-center text-white ps-4">Liner</th>
                                                    <th class="text-center text-white">Type</th>
                                                    <th class="text-center text-white">FCL Validity</th>
                                                    <th class="text-center text-white">Transit Time</th>
                                                    <th class="text-center text-white">20' (All-In)</th>
                                                    <th class="text-center text-white">40' (All-In)</th>
                                                    <th class="text-center text-white">40'HC (All-In)</th>
                                                    <th class="text-center text-white pe-4">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach($rates as $rate): ?>
                                                <tr class="dest-item" data-dest="<?= strtolower(htmlspecialchars($rate['liner_name'])) ?>">
                                                    <td class="text-start ps-4 fw-bold text-white align-middle" style="font-size: 0.82rem;"><?= htmlspecialchars($rate['liner_name']) ?></td>
                                                    <td class="align-middle">
                                                        <?php $ctype = !empty($rate['cargo_type']) ? $rate['cargo_type'] : 'GENCO'; ?>
                                                        <span class="badge badge-<?= strtolower($ctype) ?> text-uppercase shadow-sm" style="font-size: 0.72rem;"><?= htmlspecialchars($ctype) ?></span>
                                                    </td>
                                                    <td class="align-middle text-center">
                                                        <?php 
                                                            $from_str = !empty($rate['fcl_validity_from']) ? date('d M', strtotime($rate['fcl_validity_from'])) : '';
                                                            $to_str = !empty($rate['fcl_validity_to']) ? date('d M Y', strtotime($rate['fcl_validity_to'])) : '';
                                                            $validity_display = ($from_str) ? $from_str . ' - ' . $to_str : ($to_str ?: '-');
                                                        ?>
                                                        <span class="text-info" title="FCL Validity Period"><i class="bi bi-calendar3 me-1"></i> <?= $validity_display ?></span>
                                                    </td>
                                                    <td class="align-middle text-center text-white-50"><i class="bi bi-clock me-1"></i> <?= !empty($rate['fcl_transit_time']) ? htmlspecialchars($rate['fcl_transit_time']) : '-' ?></td>
                                                    <td class="text-success fw-bold align-middle font-monospace" style="font-size: 0.85rem;">$<?= number_format((float)$rate['prices']['20'], 2) ?></td>
                                                    <td class="text-success fw-bold align-middle font-monospace" style="font-size: 0.85rem;">$<?= number_format((float)$rate['prices']['40'], 2) ?></td>
                                                    <td class="text-success fw-bold align-middle font-monospace" style="font-size: 0.85rem;">$<?= number_format((float)$rate['prices']['40HC'], 2) ?></td>
                                                    <td class="pe-4 align-middle">
                                                        <button class="btn btn-sm btn-outline-info rounded-pill px-3" style="font-size: 0.75rem;" onclick="openDetailsModal(<?= (int)$rate['id'] ?>, '<?= htmlspecialchars($rate['liner_name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($route, ENT_QUOTES) ?>', 'FCL')">
                                                            <i class="fa-solid fa-circle-info me-1"></i> Detail
                                                        </button>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="tab-pane fade" id="list-lcl" role="tabpanel" aria-labelledby="list-lcl-tab">
                <div class="accordion glass-accordion" id="lclAccordion">
                    <?php if(empty($lcl_routes)): ?>
                        <div class="text-center text-white-50 my-5 py-5 border border-secondary border-opacity-25 rounded-4 bg-dark bg-opacity-25">
                            <i class="bi bi-boxes fs-1 mb-3 d-block"></i> No LCL rates available.
                        </div>
                    <?php endif; ?>

                    <?php $j = 0; foreach($lcl_routes as $route => $rates): $j++; ?>
                        <div class="accordion-item dest-item" data-dest="<?= strtolower(htmlspecialchars($route)) ?>">
                            <h2 class="accordion-header" id="lclHeading<?= $j ?>">
                                <button class="accordion-button collapsed fw-bold text-uppercase" type="button" data-bs-toggle="collapse" data-bs-target="#lclCol<?= $j ?>" aria-expanded="false" aria-controls="lclCol<?= $j ?>">
                                    <i class="fa-solid fa-box me-2 text-warning"></i>
                                    <span class="flex-grow-1 text-start text-truncate pr-2"><?= htmlspecialchars($route) ?></span>
                                    <span class="badge bg-warning text-dark bg-opacity-75 border border-warning me-3"><?= count($rates) ?> LINERS</span>
                                </button>
                            </h2>

                            <div id="lclCol<?= $j ?>" class="accordion-collapse collapse" aria-labelledby="lclHeading<?= $j ?>" data-bs-parent="#lclAccordion">
                                <div class="accordion-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover table-glass align-middle text-center">
                                            <thead>
                                                <tr>
                                                    <th class="text-center text-white ps-4">Liner</th>
                                                    <th class="text-center text-white">Type</th>
                                                    <th class="text-center text-white">Validity</th>
                                                    <th class="text-start text-white">Route</th>
                                                    <th class="text-end text-white">Price / CBM</th>
                                                    <th class="text-center text-white">Transit Time</th>
                                                    <th class="text-end text-white pe-4">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach($rates as $rate): ?>
                                                    <?php 
                                                    $display_options = [];
                                                    if (!empty($rate['lcl_options'])) {
                                                        foreach ($rate['lcl_options'] as $opt) {
                                                            $price = isset($opt->price) ? $opt->price : null;
                                                            if (isset($opt->is_priority) && $opt->is_priority == 1 && $price !== null && $price !== '') {
                                                                $display_options[] = $opt;
                                                            }
                                                        }
                                                        if (empty($display_options)) {
                                                            foreach ($rate['lcl_options'] as $opt) {
                                                                $price = isset($opt->price) ? $opt->price : null;
                                                                if ($price !== null && $price !== '') {
                                                                    $display_options[] = $opt; break;
                                                                }
                                                            }
                                                        }
                                                    } else {
                                                        $legacy_price = isset($rate['lcl_price']) ? $rate['lcl_price'] : (isset($rate['min_lcl_price']) ? $rate['min_lcl_price'] : null);
                                                        if ($legacy_price !== null && $legacy_price !== '') {
                                                            $fallback_opt = new stdClass();
                                                            $fallback_opt->price = $legacy_price;
                                                            $fallback_opt->type_name = isset($rate['lcl_type_name']) ? $rate['lcl_type_name'] : '-';
                                                            $fallback_opt->transit_time = isset($rate['lcl_transit_time']) ? $rate['lcl_transit_time'] : '-';
                                                            $fallback_opt->is_priority = 0;
                                                            $display_options[] = $fallback_opt;
                                                        }
                                                    }
                                                    if (empty($display_options)) continue;
                                                    ?>

                                                    <?php foreach($display_options as $opt): ?>
                                                        <?php 
                                                            $is_priority = isset($opt->is_priority) && $opt->is_priority == 1;
                                                            $price_val = (float)$opt->price;
                                                            $type_name = isset($opt->type_name) ? $opt->type_name : '-';
                                                            $transit_time = !empty($opt->transit_time) ? $opt->transit_time : '-';
                                                        ?>
                                                        <tr class="dest-item" data-dest="<?= strtolower(htmlspecialchars($rate['liner_name'])) ?> <?= strtolower(htmlspecialchars($type_name)) ?>">
                                                            <td class="text-start ps-4 fw-bold text-white align-middle" style="font-size: 0.82rem;">
                                                                <i class="fa-solid fa-boxes-stacked me-2 text-warning opacity-75"></i>
                                                                <?= htmlspecialchars($rate['liner_name']) ?>
                                                            </td>
                                                            <td class="align-middle">
                                                                <?php $ctype = !empty($rate['cargo_type']) ? $rate['cargo_type'] : 'GENCO'; ?>
                                                                <span class="badge badge-<?= strtolower($ctype) ?> text-uppercase shadow-sm" style="font-size: 0.72rem;"><?= htmlspecialchars($ctype) ?></span>
                                                            </td>
                                                            <td class="align-middle text-center">
                                                                <?php 
                                                                    $lcl_from_str = !empty($rate['lcl_validity_from']) ? date('d M', strtotime($rate['lcl_validity_from'])) : '';
                                                                    $lcl_to_str = !empty($rate['lcl_validity_to']) ? date('d M Y', strtotime($rate['lcl_validity_to'])) : '';
                                                                    $lcl_validity_display = ($lcl_from_str) ? $lcl_from_str . ' - ' . $lcl_to_str : ($lcl_to_str ?: '-');
                                                                ?>
                                                                <span class="text-white-50" title="LCL Validity Period"><i class="bi bi-calendar3 text-warning me-1"></i> <?= $lcl_validity_display ?></span>
                                                            </td>
                                                            <td class="align-middle text-start">
                                                                <span class="text-white fw-semibold" style="font-size: 0.85rem;"><?= htmlspecialchars($type_name) ?></span>
                                                                <?php if($is_priority): ?>
                                                                    <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25 ms-2" style="font-size: 0.65rem;">PRIORITY</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td class="text-end fw-bold align-middle font-monospace" style="font-size: 0.85rem;">
                                                                <?php if ($price_val < 0): ?>
                                                                    <span class="text-danger">$ -<?= number_format(abs($price_val), 2) ?></span>
                                                                <?php elseif ($price_val == 0): ?>
                                                                    <span class="text-info">FREE</span>
                                                                <?php else: ?>
                                                                    <span class="text-warning">$ <?= number_format($price_val, 2) ?></span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td class="align-middle text-center text-white-50" style="font-size: 0.85rem;">
                                                                <?php if($transit_time !== '-'): ?>
                                                                    <i class="bi bi-clock me-1"></i> <?= htmlspecialchars($transit_time) ?>
                                                                <?php else: ?> - <?php endif; ?>
                                                            </td>
                                                            <td class="pe-4 align-middle text-end">
                                                                <button class="btn btn-sm btn-outline-warning text-white rounded-pill px-3" style="font-size: 0.75rem;" onclick="openDetailsModal(<?= (int)$rate['id'] ?>, '<?= htmlspecialchars($rate['liner_name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($route, ENT_QUOTES) ?>', 'LCL')">
                                                                    <i class="fa-solid fa-circle-info me-1"></i> Detail
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="tab-pane fade" id="list-air" role="tabpanel" aria-labelledby="list-air-tab">
                <div class="accordion glass-accordion" id="airAccordion">
                    <?php if(empty($air_list)): ?>
                        <div class="text-center text-white-50 my-5 py-5 border border-secondary border-opacity-25 rounded-4 bg-dark bg-opacity-25">
                            <i class="bi bi-airplane fs-1 mb-3 d-block"></i> No Air rates available.
                        </div>
                    <?php endif; ?>

                    <?php $k = 0; foreach($air_list as $route => $rates): $k++; ?>
                        <div class="accordion-item dest-item" data-dest="<?= strtolower(htmlspecialchars($route)) ?>">
                            <h2 class="accordion-header" id="airHeading<?= $k ?>">
                                <button class="accordion-button collapsed fw-bold text-uppercase" type="button" data-bs-toggle="collapse" data-bs-target="#airCol<?= $k ?>" aria-expanded="false" aria-controls="airCol<?= $k ?>">
                                    <i class="fa-solid fa-plane-departure me-2 text-primary"></i>
                                    <span class="flex-grow-1 text-start text-truncate pr-2"><?= htmlspecialchars($route) ?></span>
                                    <span class="badge bg-primary text-white bg-opacity-75 border border-primary me-3"><?= count($rates) ?> AIRLINES</span>
                                </button>
                            </h2>

                            <div id="airCol<?= $k ?>" class="accordion-collapse collapse" aria-labelledby="airHeading<?= $k ?>" data-bs-parent="#airAccordion">
                                <div class="accordion-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover table-glass align-middle text-center">
                                            <thead>
                                                <tr>
                                                    <th class="text-start text-white ps-4">Airline</th>
                                                    <th class="text-center text-white">SHC</th>
                                                    <th class="text-end text-white">Min</th>
                                                    <th class="text-end text-white">Normal</th>
                                                    <th class="text-end text-white">+45 Kg</th>
                                                    <th class="text-end text-white">+100 Kg</th>
                                                    <th class="text-end text-white">+300 Kg</th>
                                                    <th class="text-end text-white">+500 Kg</th>
                                                    <th class="text-end text-white">+1000 Kg</th>
                                                   
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach($rates as $rate): ?>
                                                <tr class="dest-item" data-dest="<?= strtolower(htmlspecialchars($rate['airline'])) ?> <?= strtolower(htmlspecialchars($rate['shc'])) ?>">
                                                    <td class="text-start ps-4 fw-bold text-white align-middle" style="font-size: 0.82rem;">
                                                        <?= htmlspecialchars($rate['airline']) ?>
                                                    </td>
                                                    <td class="align-middle">
                                                        <span class="badge badge-genco text-uppercase shadow-sm" style="font-size: 0.72rem;"><?= htmlspecialchars($rate['shc']) ?></span>
                                                    </td>
                                                    <td class="text-end text-white-50 font-monospace" style="font-size: 0.82rem;">Rp.<?= number_format((float)$rate['minimum'], 2) ?></td>
                                                    <td class="text-end text-primary fw-bold font-monospace" style="font-size: 0.85rem;">Rp.<?= number_format((float)$rate['normal'], 2) ?></td>
                                                    <td class="text-end text-primary fw-bold font-monospace" style="font-size: 0.85rem;">Rp.<?= number_format((float)$rate['rate_45'], 2) ?></td>
                                                    <td class="text-end text-primary fw-bold font-monospace" style="font-size: 0.85rem;">Rp.<?= number_format((float)$rate['rate_100'], 2) ?></td>
                                                    <td class="text-end text-primary fw-bold font-monospace" style="font-size: 0.85rem;">Rp.<?= number_format((float)$rate['rate_300'], 2) ?></td>
                                                    <td class="text-end text-primary fw-bold font-monospace" style="font-size: 0.85rem;">Rp.<?= number_format((float)$rate['rate_500'], 2) ?></td>
                                                    <td class="text-end text-primary fw-bold font-monospace" style="font-size: 0.85rem;">Rp.<?= number_format((float)$rate['rate_1000'], 2) ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="tab-pane fade" id="list-truck" role="tabpanel" aria-labelledby="list-truck-tab">
                <div class="accordion glass-accordion">
                    <div class="accordion-item" style="border:none;">
                        <div class="accordion-collapse collapse show">
                            <div class="accordion-body p-0">
                                <?php if(empty($trucking_list)): ?>
                                    <div class="text-center text-white-50 my-5 py-5 border border-secondary border-opacity-25 rounded-4 bg-dark bg-opacity-25">
                                        <i class="bi bi-truck fs-1 mb-3 d-block"></i> No Inland / Trucking rates available.
                                    </div>
                                <?php else: ?>
                                    <div class="p-3 bg-dark bg-opacity-25 border-bottom border-white border-opacity-10 text-white-50 small">
                                        <i class="bi bi-info-circle me-1 text-success"></i> Tarif di bawah adalah estimasi harga dari dan menuju <b>Tanjung Priok</b>.
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-hover table-glass align-middle text-center">
                                            <thead>
                                                <tr>
                                                    <th class="text-start text-white ps-4">Destination Area</th>
                                                    <th class="text-end text-white">CDD Price</th>
                                                    <th class="text-end text-white">FUSO Price</th>
                                                    <th class="text-end text-white">Wingbox Price</th>
                                                    <th class="text-end text-white">20' Container</th>
                                                    <th class="text-end text-white pe-4">40' Container</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach($trucking_list as $truck): ?>
                                                <tr class="dest-item" data-dest="<?= strtolower(htmlspecialchars($truck['destination_name'])) ?>">
                                                    <td class="text-start ps-4 fw-bold text-white align-middle" style="font-size: 0.85rem;">
                                                        <i class="fa-solid fa-map-location-dot text-success opacity-75 me-2"></i> <?= htmlspecialchars($truck['destination_name']) ?>
                                                    </td>
                                                    <td class="text-end text-success fw-bold font-monospace" style="font-size: 0.85rem;"><?= format_idr($truck['cdd_price']) ?></td>
                                                    <td class="text-end text-success fw-bold font-monospace" style="font-size: 0.85rem;"><?= format_idr($truck['fuso_price']) ?></td>
                                                    <td class="text-end text-success fw-bold font-monospace" style="font-size: 0.85rem;"><?= format_idr($truck['wb_price']) ?></td>
                                                    <td class="text-end text-success fw-bold font-monospace" style="font-size: 0.85rem;"><?= format_idr($truck['price_20']) ?></td>
                                                    <td class="text-end text-success fw-bold font-monospace pe-4" style="font-size: 0.85rem;"><?= format_idr($truck['price_40']) ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
			<?php
				/**
				 * Convert ISO 3166-1 alpha-2 code (e.g. "US") to flag emoji
				 */
					function isoToFlagSvg(string $iso, int $size = 16): string {
						$iso = strtolower($iso); // flagcdn pakai lowercase
						// contoh: https://flagcdn.com/16x12/eg.png
						return '<img src="https://flagcdn.com/' . $size . 'x' . round($size*0.75) . '/' . $iso . '.png" 
									 alt="' . strtoupper($iso) . '" 
									 style="vertical-align:middle; margin-right:4px;">';
					}

				/**
				 * Mapping country name (as in ts_rates) to ISO code
				 * Tambahkan sesuai kebutuhan
				 */
				function countryToIso(string $country): ?string {
					$map = [
						"ALBANIA" => "AL",
						"ALGERIA" => "DZ",
						"AMERICAN SAMOA" => "AS",
						"ANGOLA" => "AO",
						"ARGENTINA" => "AR",
						"AUSTRALIA" => "AU",
						"AUSTRIA" => "AT",
						"AZERBAIJAN" => "AZ",
						"BAHAMAS" => "BS",
						"BAHRAIN" => "BH",
						"BANGLADESH" => "BD",
						"BELGIUM" => "BE",
						"BENIN" => "BJ",
						"BRAZIL" => "BR",
						"BRUNEI" => "BN",
						"BULGARIA" => "BG",
						"CAMBODIA" => "KH",
						"CAMEROON" => "CM",
						"CANADA" => "CA",
						"CHILE" => "CL",
						"CHINA" => "CN",
						"COLOMBIA" => "CO",
						"COSTA RICA" => "CR",
						"COTE DIVOIRE" => "CI",
						"COTE D'LVOIRE" => "CI",
						"CROATIA" => "HR",
						"CYPRUS" => "CY",
						"CZECH REPUBLIC" => "CZ",
						"DENMARK" => "DK",
						"DJIBOUTI" => "DJ",
						"DOMINICAN REPUBLIC" => "DO",
						"ECUADOR" => "EC",
						"EGYPT" => "EG",
						"EL SALVADOR" => "SV",
						"EL SALAVDOR" => "SV",
						"ESTONIA" => "EE",
						"FIJI" => "FJ",
						"FINLAND" => "FI",
						"FRANCE" => "FR",
						"GERMANY" => "DE",
						"GHANA" => "GH",
						"GREECE" => "GR",
						"GUATEMALA" => "GT",
						"GUINEA" => "GN",
						"GUYANA" => "GY",
						"HAITI" => "HT",
						"HONDURAS" => "HN",
						"HUNGARY" => "HU",
						"ICELAND" => "IS",
						"INDIA" => "IN",
						"INDONESIA" => "ID",
						"IRAN" => "IR",
						"IRAQ" => "IQ",
						"IRELAND" => "IE",
						"ISRAEL" => "IL",
						"ITALY" => "IT",
						"JAMAICA" => "JM",
						"JAPAN" => "JP",
						"JORDAN" => "JO",
						"KENYA" => "KE",
						"KOREA" => "KR",
						"KUWAIT" => "KW",
						"KAZAKHSTAN" => "KZ",
						"LATVIA" => "LV",
						"LEBANON" => "LB",
						"LITHUANIA" => "LT",
						"MALAYSIA" => "MY",
						"MALDIVES" => "MV",
						"MALTA" => "MT",
						"MAURITIUS" => "MU",
						"MEXICO" => "MX",
						"MOROCCO" => "MA",
						"MYANMAR" => "MM",
						"NEPAL" => "NP",
						"NETHERLANDS" => "NL",
						"NEW ZEALAND" => "NZ",
						"NIGERIA" => "NG",
						"NORWAY" => "NO",
						"OMAN" => "OM",
						"PAKISTAN" => "PK",
						"PANAMA" => "PA",
						"PARAGUAY" => "PY",
						"PERU" => "PE",
						"PHILIPPINES" => "PH",
						"POLAND" => "PL",
						"PORTUGAL" => "PT",
						"QATAR" => "QA",
						"ROMANIA" => "RO",
						"RUSSIA" => "RU",
						"RWANDA" => "RW",
						"SAUDI ARABIA" => "SA",
						"SENEGAL" => "SN",
						"SERBIA" => "RS",
						"SINGAPORE" => "SG",
						"SLOVAKIA" => "SK",
						"SLOVENIA" => "SI",
						"SOUTH AFRICA" => "ZA",
						"SPAIN" => "ES",
						"SRI LANKA" => "LK",
						"SWEDEN" => "SE",
						"SWITZERLAND" => "CH",
						"TAIWAN" => "TW",
						"TANZANIA" => "TZ",
						"THAILAND" => "TH",
						"TUNISIA" => "TN",
						"TURKEY" => "TR",
						"UGANDA" => "UG",
						"UK" => "GB",
						"USA" => "US",
						"U.A.E" => "AE",
						"UAE" => "AE",
						"UKRAINE" => "UA",
						"UNITED STATES" => "US",
						"URUGUAY" => "UY",
						"UZBEKISTAN" => "UZ",
						"VANUATU" => "VU",
						"VIETNAM" => "VN",
						"YEMEN" => "YE",
						"ZAMBIA" => "ZM",
						"ZIMBABWE" => "ZW"
					];

					$normalized = normalizeCountry($country);
					return $map[$normalized] ?? null;
				}
				
				function normalizeCountry(string $country): string {
					$country = strtoupper(trim($country));
					// hapus karakter aneh seperti bintang, titik, dll
					$country = preg_replace('/[^A-Z :]/', '', $country);

					// kalau ada ", CHINA" → jadikan "CHINA"
					if (strpos($country, 'CHINA') !== false || strpos($country, 'CHINIA')) {
						return 'CHINA';
					}

					// kalau ada "USA STATE :" → jadikan "USA"
					if (strpos($country, 'USA STATE') !== false) {
						return 'USA';
					}

					// kalau ada "USA" langsung → jadikan "USA"
					if (strpos($country, 'USA') !== false || strpos($country, 'UNITED STATES') !== false) {
						return 'USA';
					}

					return $country;
				}
				?>

			<div class="tab-pane fade" id="list-transhipment" role="tabpanel" aria-labelledby="list-transhipment-tab">
                <div class="accordion glass-accordion" id="tsAccordion">
                    <?php if(empty($transhipment_rates)): ?>
                        <div class="text-center text-white-50 my-5 py-5 border border-secondary border-opacity-25 rounded-4 bg-dark bg-opacity-25">
                            <i class="bi bi-diagram-3 fs-1 mb-3 d-block"></i> No Transhipment rates available.
                        </div>
                    <?php endif; ?>

                    <?php 
                    $t = 0; 
                    foreach($transhipment_rates as $hub => $rates): 
                        $t++; 
                        
                        // Ekstrak data header dari array index ke-0
                        $first_rate = isset($rates[0]) ? $rates[0] : null;
                        $agent_code = !empty($first_rate->agent_code) ? $first_rate->agent_code : 'UNKNOWN';
                        $validity_date = !empty($first_rate->validity_date) ? date('d M Y', strtotime($first_rate->validity_date)) : '-';
                        // PATCH: VIA SIN ditampilkan sbg perbandingan 3 agen
                        $is_cmp = !empty($first_rate->is_compare);
                        if ($is_cmp) {
                            $agent_code = '3 AGENTS';
                            $validity_date = !empty($first_rate->tarif_terlama) ? date('d M Y', strtotime($first_rate->tarif_terlama)) : '-';
                        }
                    ?>
                        <div class="accordion-item dest-item" data-dest="<?= strtolower(htmlspecialchars($hub)) ?>">
                            <h2 class="accordion-header" id="tsHeading<?= $t ?>">
                                <button class="accordion-button collapsed fw-bold text-uppercase" type="button" data-bs-toggle="collapse" data-bs-target="#tsCol<?= $t ?>" aria-expanded="false" aria-controls="tsCol<?= $t ?>">
                                    <i class="fa-solid fa-anchor me-2 text-info"></i>
                                    
                                    <div class="flex-grow-1 text-start text-truncate pr-2">
                                        <?= htmlspecialchars($hub) ?>
                                        <!-- Menampilkan Agent Code -->
                                        <?php if ($agent_code !== 'UNKNOWN'): ?>
                                            <span class="badge bg-secondary bg-opacity-50 text-white border border-secondary border-opacity-25 ms-2" style="font-size: 0.65rem;">
                                                <i class="bi bi-building me-1"></i> <?= htmlspecialchars($agent_code) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Menampilkan Validity Date -->
                                    <span class="text-warning fw-normal me-4" style="font-size: 0.75rem;" title="Tariff Validity Date">
                                        <i class="bi bi-calendar-check me-1"></i> Valid to: <?= $validity_date ?>
                                    </span>
                                    
                                    <span class="badge bg-info text-dark bg-opacity-75 border border-info me-3"><?= count($rates) ?> DESTINATIONS</span>
                                </button>
                            </h2>

                            <div id="tsCol<?= $t ?>" class="accordion-collapse collapse" aria-labelledby="tsHeading<?= $t ?>" data-bs-parent="#tsAccordion">
                                <div class="accordion-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover table-glass align-middle text-center">
                                            <thead>
                                                <tr>
                                                    <th class="text-start text-white ps-4">Destination</th>
                                                    <?php if ($is_cmp): ?>
                                                    <th class="text-center text-white">Benkel Int'l</th>
                                                    <th class="text-center text-white">Freight Links</th>
                                                    <th class="text-center text-white">Astro Pacific</th>
                                                    <th class="text-center text-white">Charterlink</th>
                                                    <!--<th class="text-center text-white">Spread</th>--!>
                                                    <?php else: ?>
                                                    <th class="text-start text-white">Via Port</th>
                                                    <th class="text-center text-white">Rate</th>
                                                    <th class="text-center text-white">Transit Time (Days)</th>
                                                    <?php endif; ?>
                                                    <th class="text-end text-white pe-4">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if ($is_cmp): /* BLOK PERBANDINGAN */ ?>
                                                    <?php foreach($rates as $rate):
                                                        $cells = [
                                                          ['Benkel International', $rate->rate_benkel, $rate->biaya_1cbm_benkel, $rate->tt_benkel, $rate->basis_benkel],
                                                          ['Freight Links', $rate->rate_freightlinks, $rate->biaya_1cbm_freightlinks, $rate->tt_freightlinks, $rate->basis_freightlinks],
                                                          ['Astro Pacific', $rate->rate_astro,        $rate->biaya_1cbm_astro,        $rate->tt_astro,        $rate->basis_astro],
                                                          ['Charterlink SG', $rate->rate_charterlinksg, $rate->biaya_1cbm_charterlinksg, $rate->tt_charterlinksg, $rate->basis_charterlinksg],
                                                        ];
                                                        $vals = [];
                                                        foreach ($cells as $c) { if ($c[2] !== null && $c[2] !== '') { $vals[] = (float)$c[2]; } }
                                                        $lo = $vals ? min($vals) : null;
                                                        $hi = $vals ? max($vals) : null;
                                                    ?>
                                                    <tr class="dest-item" data-dest="<?= strtolower(htmlspecialchars((string)$rate->destination)) ?> <?= strtolower(htmlspecialchars((string)$rate->country)) ?>">
                                                        <td class="text-start ps-4 fw-bold text-white align-middle" style="font-size: 0.82rem;">
                                                            <?= htmlspecialchars((string)$rate->destination) ?>
                                                            <?php if (!empty($rate->perlu_ditinjau)): ?>
                                                                <i class="bi bi-exclamation-triangle-fill text-warning ms-1" title="Nama pelabuhan sama tapi negara berbeda antar agen - periksa dulu"></i>
                                                            <?php endif; ?>
                                                            <?php if (!empty($rate->basis_campuran)): ?>
                                                                <i class="bi bi-rulers text-info ms-1" title="Basis berat berbeda antar agen - angka belum tentu setara"></i>
                                                            <?php endif; ?>
                                                            <?php if (!empty($rate->ada_surcharge)): ?>
                                                                <i class="bi bi-plus-circle text-white-50 ms-1" title="Ada surcharge terpisah di luar tarif"></i>
                                                            <?php endif; ?>
                                                            <div class="text-white-50 fw-normal" style="font-size: 0.7rem;">
                                                                <?php $iso = countryToIso((string)$rate->country); echo $iso ? isoToFlagSvg($iso, 16) : ''; ?>
                                                                <?= htmlspecialchars((string)$rate->country) ?>
                                                            </div>
                                                        </td>
                                                        <?php foreach ($cells as $c): ?>
                                                        <td class="text-center align-middle font-monospace" style="font-size: 0.8rem;">
                                                            <?php if ($c[2] === null || $c[2] === ''): ?>
                                                                <span class="text-white-50" style="font-size:0.72rem;">&mdash;</span>
                                                            <?php else:
                                                                $eff = (float)$c[2];
                                                                $cls = 'text-white';
                                                                if (count($vals) > 1 && $eff == $lo) { $cls = 'text-success fw-bold'; }
                                                                elseif (count($vals) > 1 && $eff == $hi) { $cls = 'text-warning'; }
                                                            ?>
                                                                <span class="<?= $cls ?>">$ <?= number_format($eff, 2) ?></span>
                                                                <div class="text-white-50 fw-normal" style="font-size:0.65rem;">
                                                                    <?php if ($c[1] !== null && (float)$c[1] != $eff): ?>W/M <?= number_format((float)$c[1], 2) ?> &middot; <?php endif; ?>
                                                                    <?php if (!empty($c[3])): ?><?= (int)$c[3] ?>d<?php endif; ?>
                                                                    <?php if (!empty($c[4]) && $c[4] !== 'W/M'): ?> &middot; <?= htmlspecialchars((string)$c[4]) ?><?php endif; ?>
                                                                </div>
                                                            <?php endif; ?>
                                                        </td>
                                                        <?php endforeach; ?>
                                                       <!--  <td class="text-center align-middle" style="font-size: 0.78rem;">
                                                            <?php if ((int)$rate->jml_agen > 1): ?>
                                                                <span class="font-monospace text-white">$ <?= number_format((float)$rate->selisih_1cbm, 0) ?></span>
                                                                <div class="text-white-50" style="font-size:0.65rem;">
                                                                    <?= (int)$rate->jml_agen ?> agen<?php if ($lo > 0): ?> &middot; <?= number_format($hi / $lo, 1) ?>x<?php endif; ?>
                                                                </div>
                                                            <?php else: ?>
                                                                <span class="badge bg-secondary bg-opacity-50 text-white-50" style="font-size:0.62rem;">1 agen</span>
                                                            <?php endif; ?>
                                                        </td> */ --!>
                                                        <td class="pe-4 align-middle text-end">
                                                            <button type="button"
                                                                    class="btn btn-sm btn-outline-info text-white rounded-pill px-3 js-ts-detail"
                                                                    style="font-size: 0.75rem;"
                                                                    data-ts-port-key="<?= htmlspecialchars((string)$rate->port_key, ENT_QUOTES) ?>"
                                                                    data-ts-dest="<?= htmlspecialchars((string)$rate->destination, ENT_QUOTES) ?>"
                                                                    data-ts-country="<?= htmlspecialchars((string)$rate->country, ENT_QUOTES) ?>">
                                                                <i class="fa-solid fa-circle-info me-1"></i> Detail
                                                            </button>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                <?php foreach($rates as $rate): ?>
                                                <tr class="dest-item" data-dest="<?= strtolower(htmlspecialchars((string)$rate->destination)) ?> <?= strtolower(htmlspecialchars((string)$rate->country)) ?>">
                                                    <td class="text-start ps-4 fw-bold text-white align-middle" style="font-size: 0.82rem;">
														<?= htmlspecialchars((string)$rate->destination) ?>
														<div class="text-white-50 fw-normal" style="font-size: 0.7rem;">
															<?php 
															$iso = countryToIso((string)$rate->country);
															echo $iso ? isoToFlagSvg($iso, 16) : '';
															?>
															<?= htmlspecialchars((string)$rate->country) ?>
														</div>
													</td>


                                                    <td class="align-left text-start">
                                                        <span class="badge bg-secondary text-uppercase shadow-sm" style="font-size: 0.72rem;"><?= htmlspecialchars((string)($rate->via_port ?: 'DIRECT')) ?></span>
                                                    </td>
                                                    <td class="text-end fw-bold align-middle font-monospace" style="font-size: 0.85rem;">
                                                        <?php if ($rate->freight_wm === null || $rate->freight_wm === ''): ?>
                                                            <?php if (!empty($rate->rate_text)): ?>
                                                                <span class="text-warning" style="font-size:0.7rem;"><?= htmlspecialchars((string)$rate->rate_text) ?></span>
                                                            <?php else: ?>
                                                                <span class="text-white-50">-</span>
                                                            <?php endif; ?>
                                                        <?php elseif ((float)$rate->freight_wm < 0): ?>
                                                            <span class="text-danger">$ <?= number_format((float)$rate->freight_wm, 2) ?></span>
                                                        <?php else: ?>
                                                            <span class="text-success">$ <?= number_format((float)$rate->freight_wm, 2) ?></span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="align-middle text-center text-white-50" style="font-size: 0.85rem;">
                                                        <?php 
                                                        $tt_arr = [];
                                                        if (!empty($rate->transit_time_1st)) {
                                                            $tt_arr[] = htmlspecialchars((string)$rate->transit_time_1st);
                                                        }
                                                        if (!empty($rate->transit_time_2nd)) {
                                                            $tt_arr[] = htmlspecialchars((string)$rate->transit_time_2nd);
                                                        }
                                                        ?>
                                                        
                                                        <?php if (!empty($tt_arr)): ?>
                                                            <i class="bi bi-clock me-1"></i> <?= implode(' + ', $tt_arr) ?>
                                                        <?php elseif (!empty($rate->transit_time_note)): ?>
                                                            <i class="bi bi-clock me-1"></i> <?= htmlspecialchars((string)$rate->transit_time_note) ?>
                                                        <?php else: ?>
                                                            -
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="pe-4 align-middle text-end">
                                                        <button type="button"
                                                                class="btn btn-sm btn-outline-info text-white rounded-pill px-3 js-ts-detail"
                                                                style="font-size: 0.75rem;"
                                                                data-ts-id="<?= (int)$rate->id ?>"
                                                                data-ts-dest="<?= htmlspecialchars((string)$rate->destination, ENT_QUOTES) ?>"
                                                                data-ts-country="<?= htmlspecialchars((string)$rate->country, ENT_QUOTES) ?>">
                                                            <i class="fa-solid fa-circle-info me-1"></i> Detail
                                                        </button>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- TAB CLEARANCE -->
            <div class="tab-pane fade" id="list-clearance" role="tabpanel" aria-labelledby="list-clearance-tab">
                <div class="container-fluid p-0">
                    <div class="row g-4 mb-4">
                        <div class="col-md-4">
                            <div class="clr-info-box amber">
                                <h6><i class="bi bi-file-earmark-text me-1"></i> Document Requirements</h6>
                                <div class="clr-info-content"><?= htmlspecialchars(trim($clearance_info->doc_requirements)) ?></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="clr-info-box rose">
                                <h6><i class="bi bi-exclamation-triangle me-1"></i> Terms & Conditions</h6>
                                <div class="clr-info-content"><?= htmlspecialchars(trim($clearance_info->terms_conditions)) ?></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="clr-info-box cyan">
                                <h6><i class="bi bi-clock-history me-1"></i> Time Processing (Est)</h6>
                                <div class="clr-info-content"><?= htmlspecialchars(trim($clearance_info->time_processing)) ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-4">
                        <!-- TABEL CLEARANCE DINAMIS -->
                    <?php 
                    // Konfigurasi section agar UI tetap rapi dan warna bervariasi
                    $clearance_sections = [
                        'EXP_LCL'   => ['title' => 'Clearance Export LCL', 'color' => 'success', 'size' => 'col-lg-6'],
                        'EXP_FCL'   => ['title' => 'Clearance Export FCL', 'color' => 'info',    'size' => 'col-lg-6'],
                        'IMP_LCL'   => ['title' => 'Clearance Import LCL', 'color' => 'warning', 'size' => 'col-lg-6'],
                        'IMP_FCL'   => ['title' => 'Clearance Import FCL', 'color' => 'danger',  'size' => 'col-lg-6'],
                        'FASILITAS' => ['title' => 'Fasilitas Lainnya',    'color' => 'primary', 'size' => 'col-lg-12']
                    ];
                    ?>
                    <div class="row g-4">
                        <?php foreach($clearance_sections as $key => $sec): ?>
                        <div class="<?= $sec['size'] ?>">
                            <div class="accordion glass-accordion">
                                <div class="accordion-item" style="border:none;">
                                    <div class="border-bottom border-white border-opacity-10 bg-<?= $sec['color'] ?> bg-opacity-10 px-4 py-3">
                                        <h6 class="mb-0 text-<?= $sec['color'] ?> fw-bold text-uppercase tracking-wider"><?= $sec['title'] ?></h6>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-hover table-glass align-middle text-center m-0">
                                            <thead>
                                                <tr>
                                                    <th class="text-start text-white ps-4">Component</th>
                                                    <th class="text-end text-white pe-4">Fee (IDR)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if(!empty($clearance_tariffs[$key])): ?>
                                                    <?php foreach($clearance_tariffs[$key] as $item): ?>
                                                        <tr class="border-t border-white border-opacity-10">
                                                            <td class="text-start ps-4 text-white align-middle" style="font-size: 0.85rem;">
                                                                <?= htmlspecialchars($item->component_name) ?>
                                                            </td>
                                                            <td class="text-end pe-4 align-middle font-monospace" style="font-size: 0.85rem;">
                                                                <?php if($item->is_at_cost === 'Y'): ?>
                                                                    <span class="text-warning fst-italic">As per receipt <?= $key === 'FASILITAS' ? '/ As Agree' : '' ?></span>
                                                                <?php else: ?>
                                                                    <span class="text-<?= $sec['color'] ?> fw-bold"><?= format_idr($item->price_idr) ?></span>
                                                                    <?php if(!empty($item->unit_basis)): ?>
                                                                        <span class="text-white-50 ms-1" style="font-size: 0.7rem;">/ <?= htmlspecialchars($item->unit_basis) ?></span>
                                                                    <?php endif; ?>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr><td colspan="2" class="text-white-50 py-3">No <?= htmlspecialchars($sec['title']) ?> tariffs found.</td></tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    </div>
                </div>
            </div>

        </div> </div>
</div>
<div class="modal fade" id="pricingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content glass-modal text-white">
            <div class="modal-header border-bottom border-secondary border-opacity-50">
                <div class="d-flex align-items-center w-100">
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h5 class="modal-title fw-bold text-uppercase text-info mb-0 d-flex align-items-center">
                                <i class="fa-solid fa-ship me-2"></i>
                                <span id="modalLinerTitle">Liner</span>
                            </h5>
                            <div>
                                <span id="modalCargoType" class="badge bg-info text-dark fs-6 px-3 py-2">Cargo</span>
                                <span id="modalRateType" class="badge bg-secondary text-white fs-6 px-3 py-2 ms-1">RATE TYPE</span>
                            </div>
                        </div>
                        <small class="text-white-50 d-flex align-items-center mt-1">
                            <i class="bi bi-geo-alt me-2"></i>
                            <span id="modalDestSubtitle">Destination</span>
                        </small>
                    </div>
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal"></button>
                </div>
            </div>

            <div class="modal-body p-0">
                <ul class="nav nav-tabs nav-justified border-secondary border-opacity-50" id="pricingTab" role="tablist" style="background: rgba(0,0,0,0.2);">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active text-uppercase fw-bold py-3 text-white border-0" id="fcl-tab" data-bs-toggle="tab" data-bs-target="#fcl-pane" type="button" role="tab" aria-selected="true"><i class="fa-solid fa-box me-2 text-info"></i> FCL Rates</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link text-uppercase fw-bold py-3 text-white-50 border-0" id="lcl-tab" data-bs-toggle="tab" data-bs-target="#lcl-pane" type="button" role="tab" aria-selected="false"><i class="fa-solid fa-boxes-stacked me-2 text-warning"></i> LCL Rates</button>
                    </li>
                </ul>

                <div class="tab-content p-4" id="pricingTabContent">
                    <div class="tab-pane fade show active" id="fcl-pane" role="tabpanel" aria-labelledby="fcl-tab">
                        <div class="table-responsive mb-4">
                            <table class="table table-borderless table-glass align-middle">
                                <thead>
                                    <tr class="text-white-50 small border-bottom border-secondary">
                                        <th class="text-white ps-2">Component</th>
                                        <th class="text-white text-center">Unit</th>
                                        <th class="text-white text-end">20'</th>
                                        <th class="text-white text-end">40'</th>
                                        <th class="text-white text-end">40'HC</th>
                                    </tr>
                                </thead>
                                <tbody id="modalBodyFCL"></tbody>
                            </table>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6"><div class="info-box"><label><i class="bi bi-check-circle-fill text-success me-1"></i> FCL Include</label><div id="fclInclude" class="info-content">-</div></div></div>
                            <div class="col-md-6"><div class="info-box"><label><i class="bi bi-x-circle-fill text-danger me-1"></i> FCL Exclude</label><div id="fclExclude" class="info-content">-</div></div></div>
                            <div class="col-md-6"><div class="info-box"><label><i class="bi bi-journal-text text-warning me-1"></i> FCL Remarks</label><div id="fclRemarks" class="info-content">-</div></div></div>
                            <div class="col-md-6"><div class="info-box"><label><i class="bi bi-clock-history text-warning me-1"></i> FCL Free Time</label><div id="fclFreeTime" class="info-content">-</div></div></div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="lcl-pane" role="tabpanel" aria-labelledby="lcl-tab">
                        
                        <h6 class="text-warning fw-bold mb-3"><i class="fa-solid fa-route me-2"></i> Available LCL Routes</h6>
                        <div class="row g-3 mb-4" id="modalLclRoutes"></div>
                        <h6 class="text-warning fw-bold mb-3"><i class="fa-solid fa-money-bill-wave me-2"></i> Local & Freight Charges</h6>
                        <div class="table-responsive mb-4">
                            <table class="table table-borderless table-glass align-middle">
                                <thead>
                                    <tr class="text-white-50 small border-bottom border-secondary">
                                        <th class="text-white ps-2">Component</th>
                                        <th class="text-white text-center">Unit</th>
                                        <th class="text-white text-end pe-3">Price</th>
                                    </tr>
                                </thead>
                                <tbody id="modalBodyLCL"></tbody>
                            </table>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4"><div class="info-box"><label><i class="bi bi-check-circle-fill text-success me-1"></i> LCL Include</label><div id="lclInclude" class="info-content">-</div></div></div>
                            <div class="col-md-4"><div class="info-box"><label><i class="bi bi-x-circle-fill text-danger me-1"></i> LCL Exclude</label><div id="lclExclude" class="info-content">-</div></div></div>
                            <div class="col-md-4"><div class="info-box"><label><i class="bi bi-journal-text text-warning me-1"></i> LCL Remarks</label><div id="lclRemarks" class="info-content">-</div></div></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- TRANSHIPMENT MODAL -->
<div class="modal fade" id="tsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content glass-modal text-white">
            <div class="modal-header border-bottom border-secondary border-opacity-50">
                <div class="d-flex align-items-center w-100">
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h5 class="modal-title fw-bold text-uppercase text-info mb-0 d-flex align-items-center">
                                <i class="fa-solid fa-anchor me-2"></i>
                                <span id="tsModalDestTitle">Destination</span>
                            </h5>
                            <div>
                                <span id="tsModalHubType" class="badge bg-primary text-white fs-6 px-3 py-2">HUB</span>
                            </div>
                        </div>
                        <small class="text-white-50 d-flex align-items-center mt-1 flex-wrap gap-3">
                            <span><i class="bi bi-geo-alt me-2"></i><span id="tsModalCountrySubtitle">Country</span></span>
                            <span id="tsModalAgentWrap" class="d-none"><i class="bi bi-building me-1"></i><span id="tsModalAgent"></span></span>
                            <span id="tsModalValidWrap" class="d-none text-warning"><i class="bi bi-calendar-check me-1"></i>Valid to: <span id="tsModalValid"></span></span>
                        </small>
                    </div>
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal"></button>
                </div>
            </div>

            <div class="modal-body p-4">
                
                <h6 class="text-info fw-bold mb-3"><i class="fa-solid fa-route me-2"></i> Route Details</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-4"><div class="info-box"><label>Via Port</label><div id="tsViaPort" class="info-content fw-bold text-white">-</div></div></div>
                    <div class="col-md-4"><div class="info-box"><label>1st Transit Time</label><div id="tsTt1" class="info-content">-</div></div></div>
                    <div class="col-md-4"><div class="info-box"><label>2nd Transit Time</label><div id="tsTt2" class="info-content">-</div></div></div>
                    <div class="col-md-12"><div class="info-box"><label>Transit Time Note</label><div id="tsTtNote" class="info-content">-</div></div></div>
                </div>

                <h6 class="text-success fw-bold mb-3"><i class="fa-solid fa-money-bill-wave me-2"></i> Rates & Terms</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-4"><div class="info-box"><label>Rate / W/M</label><div id="tsRateWm" class="info-content fw-bold font-monospace fs-5">-</div></div></div>
                    <div class="col-md-4"><div class="info-box"><label>Min CBM</label><div id="tsMinCbm" class="info-content">-</div></div></div>
                    <div class="col-md-4"><div class="info-box"><label>Frequency</label><div id="tsFreq" class="info-content">-</div></div></div>
                    <!-- Tambahan Freight Collect & Telex Release -->
                    <div class="col-md-4"><div class="info-box"><label>IPI / Inland Rate</label><div id="tsIpi" class="info-content">-</div></div></div>
                    <div class="col-md-4"><div class="info-box"><label>Special Bonus / Set</label><div id="tsBonus" class="info-content">-</div></div></div>
                    <div class="col-md-4"><div class="info-box"><label>Freight Collect</label><div id="tsFc" class="info-content">-</div></div></div>
                    <div class="col-md-4"><div class="info-box"><label>Telex Release</label><div id="tsTelex" class="info-content">-</div></div></div>
                </div>

                <h6 class="text-warning fw-bold mb-3"><i class="fa-solid fa-triangle-exclamation me-2"></i> Surcharges & DG</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-3"><div class="info-box"><label>GRI</label><div id="tsGri" class="info-content">-</div></div></div>
                    <div class="col-md-3"><div class="info-box"><label>DDC</label><div id="tsDdc" class="info-content">-</div></div></div>
                    <div class="col-md-3"><div class="info-box"><label>AMS/AFR</label><div id="tsAms" class="info-content">-</div></div></div>
                    <div class="col-md-3"><div class="info-box"><label>Fumigation</label><div id="tsFumi" class="info-content">-</div></div></div>
                    
                    <div class="col-md-6"><div class="info-box"><label>DG Surcharge (Grp 3)</label><div id="tsDg3" class="info-content">-</div></div></div>
                    <div class="col-md-6"><div class="info-box"><label>DG Surcharge (Grp 2)</label><div id="tsDg2" class="info-content">-</div></div></div>
                </div>

                <!-- PATCH: RINCIAN PER AGEN (VIA SIN, 3 agen) -->
                <div id="tsAgentRatesWrap" class="d-none">
                    <h6 class="text-info fw-bold mb-2"><i class="fa-solid fa-scale-balanced me-2"></i> Perbandingan Agen</h6>
                    <div id="tsAgentRates" class="mb-4">-</div>
                </div>

                <!-- PATCH: BIAYA LOKAL DI PELABUHAN TUJUAN -->
                <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                    <h6 class="text-info fw-bold mb-0">
                        <i class="fa-solid fa-file-invoice-dollar me-2"></i> Destination Local Charges
                    </h6>
                    <small id="tsChargePorts" class="text-white-50"></small>
                </div>
                <div id="tsLocalCharges" class="mb-4">-</div>

                <!-- PATCH: DATA AGEN DI PELABUHAN TUJUAN -->
                <h6 class="text-info fw-bold mb-2"><i class="fa-solid fa-address-card me-2"></i> Destination Agent</h6>
                <div id="tsAgents" class="mb-4">-</div>

                <div class="info-box w-100"><label><i class="bi bi-journal-text text-info me-1"></i> Remarks</label><div id="tsRemarks" class="info-content">-</div></div>

            </div>
        </div>
    </div>
</div>
<script>
    // Menyembunyikan Search Bar khusus untuk tab Clearance
    function toggleSearch(show) {
        if (show) {
            document.getElementById('mainSearchWrap').style.display = 'block';
        } else {
            document.getElementById('mainSearchWrap').style.display = 'none';
        }
    }

    // Pencarian Multi-Layanan Super Cerdas
    document.getElementById('searchInput').addEventListener('keyup', function() {
        let filter = this.value.toLowerCase().trim();
        
        // Looping ke semua grup rute (Accordion) di semua tab
        document.querySelectorAll('.accordion-item').forEach(group => {
            let btn = group.querySelector('.accordion-button');
            // Ambil nama rute dari judul accordion. (Untuk tab Trucking yang tidak punya judul, biarkan kosong).
            let routeName = btn ? btn.innerText.toLowerCase() : ''; 
            
            // Ambil semua baris data di dalam grup tersebut
            let rows = group.querySelectorAll('tbody tr');
            let groupMatches = routeName.includes(filter);
            let hasVisibleRow = false;

            rows.forEach(row => {
                // Ambil semua teks yang ada di dalam baris (Nama Liner, Tipe, Harga, dll)
                let rowText = row.innerText.toLowerCase();
                
                // Tampilkan baris jika Rutenya cocok, ATAU teks di dalam baris tersebut cocok
                if (groupMatches || rowText.includes(filter)) {
                    row.style.display = '';
                    hasVisibleRow = true;
                } else {
                    row.style.display = 'none';
                }
            });

            // Tampilkan grup accordion jika Rutenya cocok atau ada minimal 1 baris data yang cocok
            if (groupMatches || hasVisibleRow) {
                group.style.display = '';
            } else {
                group.style.display = 'none';
            }
        });
    });

    const pricingModalEl = document.getElementById('pricingModal');
    const pricingModal = new bootstrap.Modal(pricingModalEl);

    function openDetailsModal(rateId, linerName, destName, targetTab = 'FCL') {
        document.getElementById('modalLinerTitle').innerText = linerName;
        document.getElementById('modalDestSubtitle').innerText = destName;
        
        let rateTypeBadge = document.getElementById('modalRateType');

        document.getElementById('modalBodyFCL').innerHTML = '<tr><td colspan="5" class="text-center py-4"><div class="spinner-border text-info" role="status"></div></td></tr>';
        document.getElementById('modalBodyLCL').innerHTML = '<tr><td colspan="3" class="text-center py-4"><div class="spinner-border text-warning" role="status"></div></td></tr>';
        document.getElementById('modalLclRoutes').innerHTML = '';
        
        // Sembunyikan tombol Tab Header agar tidak bisa di-switch manual
        document.getElementById('pricingTab').style.display = 'none';

        let fclPane = document.getElementById('fcl-pane');
        let lclPane = document.getElementById('lcl-pane');

        // STRICT MODE: Hanya tampilkan Pane yang diminta
        if (targetTab === 'LCL') {
            rateTypeBadge.innerText = 'LCL RATES';
            rateTypeBadge.className = 'badge bg-warning text-dark fs-6 px-3 py-2 ms-1';
            
            lclPane.classList.add('show', 'active');
            fclPane.classList.remove('show', 'active');
        } else {
            rateTypeBadge.innerText = 'FCL RATES';
            rateTypeBadge.className = 'badge bg-info text-dark fs-6 px-3 py-2 ms-1';
            
            fclPane.classList.add('show', 'active');
            lclPane.classList.remove('show', 'active');
        }

        pricingModal.show();

        fetch('<?= base_url("pricelist/freight/get_details") ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'rate_id=' + encodeURIComponent(rateId)
        })
        .then(res => res.text()) 
        .then(rawText => {
            let res;
            try { res = JSON.parse(rawText); } 
            catch (errParse) {
                document.getElementById('modalBodyFCL').innerHTML = `<tr><td colspan="5" class="text-center py-4 text-danger"><b>Format Data Rusak!</b></td></tr>`;
                return;
            }

            try {
                if (res.status === 'success') {
                    // Update Badge Cargo
                    let cargoType = res.header.cargo_type || 'GENCO';
                    let badgeEl = document.getElementById('modalCargoType');
                    badgeEl.innerText = cargoType;
                    
                    let badgeClass = 'bg-success text-white'; 
                    if (cargoType === 'DG') badgeClass = 'bg-danger text-white';
                    else if (cargoType === 'REEFER') badgeClass = 'bg-info text-dark';
                    else if (cargoType === 'BREAKBULK') badgeClass = 'bg-warning text-dark';
                    badgeEl.className = `badge ${badgeClass} fs-6 px-3 py-2`;

                    // =====================================
                    // RENDER FCL
                    // =====================================
                    document.getElementById('fclInclude').innerText = res.header.fcl_include_rate || '-';
                    document.getElementById('fclExclude').innerText = res.header.fcl_exclude_rate || '-';
                    document.getElementById('fclRemarks').innerText = res.header.fcl_remarks || '-';
                    document.getElementById('fclFreeTime').innerText = res.header.fcl_free_time || '-';

                    let fclHtml = '';
                    let grandTotal = { '20': 0, '40': 0, '40hc': 0 };
                    
                    if (res.fcl_data && Object.keys(res.fcl_data).length > 0) {
                        for (const [group, items] of Object.entries(res.fcl_data)) {
                            fclHtml += `<tr class="table-header-group"><td colspan="5" class="fw-bold text-uppercase text-info pt-3 pb-1 ps-2 small">-- ${escapeHtml(group)} --</td></tr>`;
                            items.forEach(item => {
                                grandTotal['20'] += parseFloat(item.price_20 || 0);
                                grandTotal['40'] += parseFloat(item.price_40 || 0);
                                grandTotal['40hc'] += parseFloat(item.price_40hc || 0);
                                
                                fclHtml += `
                                <tr>
                                    <td class="ps-4 text-white">${escapeHtml(item.component_name)}</td>
                                    <td class="text-center text-white-50 small">${escapeHtml(item.unit_basis || 'Per Container')}</td>
                                    <td class="text-end font-monospace text-white">${formatCurrency(item.price_20)}</td>
                                    <td class="text-end font-monospace text-white">${formatCurrency(item.price_40)}</td>
                                    <td class="text-end font-monospace text-white">${formatCurrency(item.price_40hc)}</td>
                                </tr>`;
                            });
                        }
                        fclHtml += `
                            <tr class="row-total-allin border-bottom border-secondary border-opacity-50">
                                <td colspan="2" class="fw-bold fs-6 ps-2 pt-3 pb-3 text-white">TOTAL ALL IN</td>
                                <td class="text-end text-success fw-bold fs-6 pt-3 pb-3">${formatCurrency(grandTotal['20'])}</td>
                                <td class="text-end text-success fw-bold fs-6 pt-3 pb-3">${formatCurrency(grandTotal['40'])}</td>
                                <td class="text-end text-success fw-bold fs-6 pt-3 pb-3">${formatCurrency(grandTotal['40hc'])}</td>
                            </tr>`;
                    } else {
                        fclHtml = '<tr><td colspan="5" class="text-center py-4 text-warning">Belum ada rincian harga FCL</td></tr>';
                    }
                    document.getElementById('modalBodyFCL').innerHTML = fclHtml;

                    // =====================================
                    // RENDER LCL
                    // =====================================
                    document.getElementById('lclInclude').innerText = res.header.lcl_include_rate || '-';
                    document.getElementById('lclExclude').innerText = res.header.lcl_exclude_rate || '-';
                    document.getElementById('lclRemarks').innerText = res.header.lcl_remarks || '-';

                    // RENDER LCL ROUTES
                    let routesHtml = '';
                    let displayOptions = [];
                    
                    if (res.lcl_routes && res.lcl_routes.length > 0) {
                        displayOptions = res.lcl_routes;
                    } else if ((res.header.min_lcl_price !== null && res.header.min_lcl_price !== undefined) || 
                               (res.header.lcl_price !== null && res.header.lcl_price !== undefined)) {
                        let fallbackPrice = res.header.lcl_price ?? res.header.min_lcl_price;
                        if (fallbackPrice !== null && fallbackPrice !== "") {
                            displayOptions = [{
                                type_name: res.header.lcl_type_name || res.header.min_lcl_type_name || 'Direct Consol',
                                price: fallbackPrice,
                                transit_time: res.header.lcl_transit_time || res.header.transit_time || '-',
                                is_priority: 0
                            }];
                        }
                    }

                    if (displayOptions.length > 0) {
                        displayOptions.forEach(rt => {
                            let priceVal = rt.price ?? rt.raw_price ?? rt.min_lcl_price;
                            if(priceVal !== null && priceVal !== undefined && priceVal !== "") {
                                let isPrio = parseInt(rt.is_priority) === 1;
                                let star = isPrio ? '<i class="bi bi-star-fill text-warning ms-1" title="Priority"></i>' : '';
                                
                                routesHtml += `
                                <div class="col-6 col-md-3">
                                    <div class="p-3 border ${isPrio ? 'border-warning border-opacity-50' : 'border-secondary border-opacity-25'} rounded-4" style="background: rgba(255,255,255,0.03);">
                                        <div class="text-white-50 fw-semibold mb-2" style="font-size:0.75rem;">${escapeHtml(rt.type_name || 'Direct Consol')} ${star}</div>
                                        <div class="text-warning fw-bolder fs-4 font-monospace">${formatCurrency(priceVal)}</div>
                                        <div class="text-white-50 mt-2" style="font-size:0.75rem;"><i class="bi bi-clock-history me-1"></i> ${escapeHtml(rt.transit_time || 'N/A')}</div>
                                    </div>
                                </div>`;
                            }
                        });
                    }
                    
                    if(routesHtml === '') routesHtml = '<div class="col-12 text-white-50 p-3">Tidak ada rute LCL tersedia.</div>';
                    document.getElementById('modalLclRoutes').innerHTML = routesHtml;

                    // RENDER LCL CHARGES
                    let lclHtml = '';
                    if (res.lcl_data && Object.keys(res.lcl_data).length > 0) {
                        for (const [group, items] of Object.entries(res.lcl_data)) {
                            lclHtml += `<tr class="table-header-group"><td colspan="3" class="fw-bold text-uppercase text-warning pt-3 pb-1 ps-2 small">-- ${escapeHtml(group)} --</td></tr>`;
                            items.forEach(item => {
                                lclHtml += `
                                <tr>
                                    <td class="ps-4 text-white">${escapeHtml(item.service_name || item.component_name)}</td>
                                    <td class="text-center text-white-50 small">${escapeHtml(item.unit_basis || 'W/M')}</td>
                                    <td class="text-end pe-3 font-monospace text-success fw-bold">${formatCurrency(item.price_wm || item.price)}</td>
                                </tr>`;
                            });
                        }
                    } else {
                        lclHtml = '<tr><td colspan="3" class="text-center py-4 text-warning">Belum ada rincian charges LCL</td></tr>';
                    }
                    document.getElementById('modalBodyLCL').innerHTML = lclHtml;

                } else {
                    document.getElementById('modalBodyFCL').innerHTML = '<tr><td colspan="5" class="text-center py-4 text-warning">Data tidak ditemukan</td></tr>';
                }
            } catch (errRender) {
                document.getElementById('modalBodyFCL').innerHTML = `<tr><td colspan="5" class="text-center py-4 text-danger"><b>Error Render!</b><br><small>${errRender.message}</small></td></tr>`;
            }
        })
        .catch((errNetwork) => {
            document.getElementById('modalBodyFCL').innerHTML = `<tr><td colspan="5" class="text-center py-4 text-danger"><b>Koneksi / Jaringan Gagal!</b><br><small>${errNetwork.message}</small></td></tr>`;
        });
    }
	
    function formatCurrency(val) {
        if (val === null || val === "" || val === undefined) return '<span class="text-white-50">-</span>';
        let num = parseFloat(val);
        if (isNaN(num)) return '<span class="text-white-50">-</span>';
        if (num === 0) return '<span class="text-info fw-bold">FREE</span>';
        if (num < 0) return '<span class="text-warning fw-bold">Rebate ($' + Math.abs(num).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ')</span>';
        return '$' + num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function escapeHtml(text) {
        return String(text || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }
	
	const tsModalEl = document.getElementById('tsModal');
    const tsModal = new bootstrap.Modal(tsModalEl);

    // ===== PATCH: tombol Detail pakai event delegation =====
    // Sebelumnya memakai inline onclick dengan nama tujuan/negara disisipkan
    // langsung ke dalam string JS. Nama yang memuat apostrof (mis. negara
    // "COTE D'LVOIRE") membuat JS error sehingga tombol tidak berfungsi.
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.js-ts-detail');
        if (!btn) return;
        openTranshipmentModal(btn.dataset.tsId, btn.dataset.tsDest || '',
                              btn.dataset.tsCountry || '', btn.dataset.tsPortKey || '');
    });

    // ===== PATCH: render tabel biaya lokal pelabuhan tujuan =====
    // Dua tingkat: agen sumber -> jenis penerima. Satu pelabuhan bisa
    // punya tarif lokal dari beberapa agen, ditampilkan berdampingan.
    function tsRenderChargeTier(key, rows) {
        const label = { 'COLOADER': 'Co-Loader', 'DIRECT': 'Direct Consignee', 'PERSONAL EFFECTS': 'Personal Effects' };
        const meta = rows[0];
        let html = '<div class="mb-3">'
            + '<div class="d-flex justify-content-between align-items-center mb-1 flex-wrap gap-2">'
            + '<span class="badge bg-info bg-opacity-25 text-info border border-info border-opacity-25">'
            + escapeHtml(label[key] || key) + '</span>'
            + '<small class="text-white-50">' + escapeHtml(meta.port || '')
            + (meta.last_update ? ' &middot; updated ' + escapeHtml(meta.last_update) : '') + '</small>'
            + '</div>'
            + '<div class="table-responsive"><table class="table table-sm table-dark table-borderless align-middle mb-0" style="font-size:.78rem;">'
            + '<thead><tr class="text-white-50">'
            + '<th class="text-start">Item</th><th class="text-end">Amount</th>'
            + '<th class="text-start">Unit</th><th class="text-start">Note</th>'
            + '</tr></thead><tbody>';
        rows.forEach(r => {
            if (r.line_type === 'note') {
                html += '<tr><td colspan="4" class="text-warning fst-italic">' + escapeHtml(r.charge_item) + '</td></tr>';
                return;
            }
            const numv = parseFloat(r.amount);
            const amt = isFinite(numv)
                ? numv.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                : escapeHtml(r.amount_text || '-');
            html += '<tr>'
                 + '<td class="text-start text-white">'
                 + (String(r.is_continuation) === '1' ? '<span class="text-white-50">&#8627; or </span>' : '')
                 + escapeHtml(r.charge_item) + '</td>'
                 + '<td class="text-end font-monospace text-white">'
                 + (r.currency ? '<span class="text-white-50 me-1">' + escapeHtml(r.currency) + '</span>' : '')
                 + amt + '</td>'
                 + '<td class="text-start text-white-50">' + escapeHtml(r.unit || '') + '</td>'
                 + '<td class="text-start text-white-50">' + escapeHtml(r.min_note || '') + '</td>'
                 + '</tr>';
        });
        return html + '</tbody></table></div></div>';
    }

    function tsRenderCharges(bySource) {
        const order = ['COLOADER', 'DIRECT', 'PERSONAL EFFECTS'];
        const sources = Object.keys(bySource || {});
        if (!sources.length) {
            return '<div class="text-white-50 small"><i class="bi bi-info-circle me-1"></i>'
                 + 'Belum ada data biaya lokal untuk pelabuhan ini.</div>';
        }
        let html = '';
        sources.forEach(src => {
            const tiers = bySource[src] || {};
            let inner = '';
            order.forEach(k => {
                const rows = tiers[k];
                if (rows && rows.length) inner += tsRenderChargeTier(k, rows);
            });
            Object.keys(tiers).forEach(k => {
                if (order.indexOf(k) === -1 && tiers[k] && tiers[k].length) inner += tsRenderChargeTier(k, tiers[k]);
            });
            if (!inner) return;
            if (sources.length > 1 || src !== 'AGENT') {
                html += '<div class="mb-1"><span class="badge bg-primary bg-opacity-50 text-white border border-primary border-opacity-25">'
                     +  '<i class="bi bi-building me-1"></i>' + escapeHtml(src) + '</span></div>';
            }
            html += inner;
        });
        return html || '<div class="text-white-50 small">Belum ada data biaya lokal untuk pelabuhan ini.</div>';
    }

    // ===== PATCH: rincian tarif per agen (VIA SIN) =====
    // Menampilkan ketiga agen berdampingan beserta hal-hal yang membuat
    // angkanya belum tentu setara: minimum, basis berat, dan surcharge.
    function tsRenderAgentRates(list) {
        if (!list || !list.length) return '';
        const eff = r => {
            const wm = parseFloat(r.freight_wm);
            if (!isFinite(wm)) return null;
            const mc = Math.max(parseFloat(r.min_cbm) || 1, 1);
            const mu = parseFloat(r.min_charge_usd);
            return Math.max(wm * mc, isFinite(mu) ? mu : wm * mc);
        };
        const costs = list.map(eff).filter(v => v !== null);
        const lo = costs.length ? Math.min.apply(null, costs) : null;
        const hi = costs.length ? Math.max.apply(null, costs) : null;

        let html = '<div class="table-responsive"><table class="table table-sm table-dark table-borderless align-middle mb-2" style="font-size:.78rem;">'
            + '<thead><tr class="text-white-50">'
            + '<th class="text-start">Agen</th><th class="text-end">Biaya 1 CBM</th>'
            + '<th class="text-end">Per W/M</th><th class="text-end">Per TON</th>'
            + '<th class="text-start">Minimum</th><th class="text-start">Basis</th>'
            + '<th class="text-center">T/T</th><th class="text-center">F/C</th>'
            + '</tr></thead><tbody>';

        list.forEach(r => {
            const c = eff(r);
            let cls = 'text-white';
            if (costs.length > 1 && c === lo) cls = 'text-success fw-bold';
            else if (costs.length > 1 && c === hi) cls = 'text-warning';
            const wm = parseFloat(r.freight_wm), tn = parseFloat(r.rate_per_ton);
            const mu = parseFloat(r.min_charge_usd), mc = parseFloat(r.min_cbm);
            let minTxt = [];
            if (isFinite(mc)) minTxt.push(mc + ' CBM');
            if (isFinite(mu)) minTxt.push('USD ' + mu.toFixed(2));
            html += '<tr>'
                 + '<td class="text-start text-white">' + escapeHtml(r.agent_name || r.agent_code || '-') + '</td>'
                 + '<td class="text-end font-monospace ' + cls + '">'
                 +   (c === null ? (r.rate_text ? escapeHtml(r.rate_text) : '&mdash;') : '$ ' + c.toFixed(2)) + '</td>'
                 + '<td class="text-end font-monospace text-white-50">' + (isFinite(wm) ? wm.toFixed(2) : '&mdash;') + '</td>'
                 + '<td class="text-end font-monospace text-white-50">' + (isFinite(tn) ? tn.toFixed(2) : '&mdash;') + '</td>'
                 + '<td class="text-start text-white-50">' + (minTxt.length ? escapeHtml(minTxt.join(' / ')) : '&mdash;') + '</td>'
                 + '<td class="text-start text-white-50">' + escapeHtml(r.weight_basis || 'W/M') + '</td>'
                 + '<td class="text-center text-white-50">' + (r.transit_time_1st ? r.transit_time_1st + 'd' : '&mdash;') + '</td>'
                 + '<td class="text-center text-white-50">' + escapeHtml(r.freight_collect || '-') + '</td>'
                 + '</tr>';
            if (r.surcharges_text) {
                html += '<tr><td></td><td colspan="7" class="text-start text-white-50" style="font-size:.7rem;">'
                     +  '<i class="bi bi-plus-circle me-1"></i>' + escapeHtml(r.surcharges_text) + '</td></tr>';
            }
        });
        html += '</tbody></table></div>';

        const bases = Array.from(new Set(list.map(r => r.weight_basis || 'W/M')));
        if (bases.length > 1) {
            html += '<div class="text-warning" style="font-size:.72rem;">'
                 +  '<i class="bi bi-rulers me-1"></i>Basis berat berbeda (' + escapeHtml(bases.join(', '))
                 +  ') - angka belum tentu setara untuk kargo padat.</div>';
        }
        return html;
    }

    // ===== PATCH: render kartu agen pelabuhan tujuan =====
    function tsRenderAgents(list) {
        if (!list || !list.length) {
            return '<div class="text-white-50 small"><i class="bi bi-info-circle me-1"></i>'
                 + 'Belum ada data agen untuk pelabuhan ini.</div>';
        }
        return list.map(a => {
            let contact = '';
            if (a.tel)   contact += '<span class="me-3"><i class="bi bi-telephone me-1 text-info"></i>' + escapeHtml(a.tel) + '</span>';
            if (a.fax)   contact += '<span class="me-3"><i class="bi bi-printer me-1 text-info"></i>' + escapeHtml(a.fax) + '</span>';
            if (a.email) contact += '<span class="me-3"><i class="bi bi-envelope me-1 text-info"></i>' + escapeHtml(a.email) + '</span>';
            return '<div class="info-box mb-2">'
                 + '<div class="fw-bold text-white">' + escapeHtml(a.agent_name)
                 + (a.source_agent ? ' <span class="badge bg-primary bg-opacity-50 text-white border border-primary border-opacity-25" style="font-size:.6rem;">' + escapeHtml(a.source_agent) + '</span>' : '')
                 + '</div>'
                 + '<div class="text-white-50" style="font-size:.72rem;"><i class="bi bi-geo-alt me-1"></i>'
                 + escapeHtml(a.port || '') + (a.region ? ' &middot; ' + escapeHtml(a.region) : '') + '</div>'
                 + (a.address ? '<div class="text-white-50 mt-1" style="font-size:.75rem;">' + escapeHtml(a.address) + '</div>' : '')
                 + (contact ? '<div class="mt-2" style="font-size:.75rem;">' + contact + '</div>' : '')
                 + '</div>';
        }).join('');
    }

    function openTranshipmentModal(rateId, destName, countryName, portKey) {
        document.getElementById('tsModalDestTitle').innerText = destName;
        document.getElementById('tsModalCountrySubtitle').innerText = countryName;
        
        const fields = ['tsViaPort', 'tsTt1', 'tsTt2', 'tsTtNote', 'tsRateWm', 'tsMinCbm', 'tsFreq', 'tsIpi', 'tsBonus', 'tsFc', 'tsTelex', 'tsGri', 'tsDdc', 'tsAms', 'tsFumi', 'tsDg3', 'tsDg2', 'tsRemarks', 'tsLocalCharges', 'tsAgents'];
        fields.forEach(f => document.getElementById(f).innerHTML = '<span class="spinner-border spinner-border-sm text-info"></span>');
        document.getElementById('tsChargePorts').innerText = '';
        document.getElementById('tsModalAgentWrap').classList.add('d-none');
        document.getElementById('tsModalValidWrap').classList.add('d-none');

        tsModal.show();

        fetch('<?= base_url("pricelist/freight/get_transhipment_details") ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'rate_id=' + encodeURIComponent(rateId || '')
                + '&port_key=' + encodeURIComponent(portKey || '')
        })
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success') {
                let data = res.data;
                
                document.getElementById('tsModalHubType').innerText = data.ts_type;
                
                document.getElementById('tsViaPort').innerText = data.via_port || 'DIRECT';
                document.getElementById('tsTt1').innerText = data.transit_time_1st ? data.transit_time_1st + ' Days' : '-';
                document.getElementById('tsTt2').innerText = data.transit_time_2nd ? data.transit_time_2nd + ' Days' : '-';
                
                // PATCH: tarif bisa NULL (DOOR SERVICE / SUSPEND / CASE BY CASE).
                // Sebelumnya menghasilkan "$ NaN".
                let rateVal = parseFloat(data.freight_wm);
                let rateHtml;
                if (!isFinite(rateVal)) {
                    rateHtml = data.rate_text
                        ? '<span class="text-warning fw-bold">' + escapeHtml(data.rate_text) + '</span>'
                        : '<span class="text-white-50">N/A</span>';
                } else if (rateVal < 0) {
                    rateHtml = `<span class="text-danger fw-bold">Rebate ($ ${Math.abs(rateVal).toFixed(2)})</span>`;
                } else {
                    rateHtml = `<span class="text-success">$ ${rateVal.toFixed(2)}</span>`;
                }
                document.getElementById('tsRateWm').innerHTML = rateHtml;

                document.getElementById('tsTtNote').innerText = data.transit_time_note || '-';
                let ipiVal = parseFloat(data.ipi_rate);
                document.getElementById('tsIpi').innerHTML = isFinite(ipiVal)
                    ? `<span class="text-info fw-bold font-monospace">$ ${ipiVal.toFixed(2)}</span>`
                    : '-';

                // PATCH: SPECIAL BONUS (SET) - kolom baru tarif Nov 2026
                let bonusVal = parseFloat(data.special_bonus_set);
                document.getElementById('tsBonus').innerHTML = isFinite(bonusVal)
                    ? `<span class="text-info fw-bold font-monospace">$ ${bonusVal.toFixed(2)}</span><small class="text-white-50"> / set</small>`
                    : '-';

                // PATCH: info agen & masa berlaku tarif di header modal
                if (data.agent_code || data.agent_name) {
                    document.getElementById('tsModalAgent').innerText = data.agent_name || data.agent_code;
                    document.getElementById('tsModalAgentWrap').classList.remove('d-none');
                }
                if (data.validity_date) {
                    document.getElementById('tsModalValid').innerText = data.validity_date;
                    document.getElementById('tsModalValidWrap').classList.remove('d-none');
                }
                
                document.getElementById('tsMinCbm').innerText = data.min_cbm || '-';
                document.getElementById('tsFreq').innerText = data.frequency || '-';

                // Render Freight Collect & Telex Release
                let fcBadge = data.freight_collect === 'Y' ? '<span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25">YES</span>' : '<span class="badge bg-secondary bg-opacity-25 text-white-50 border border-secondary border-opacity-25">NO</span>';
                document.getElementById('tsFc').innerHTML = data.freight_collect ? fcBadge : '-';

                let telexBadge = data.telex_release === 'Y' ? '<span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25">YES</span>' : '<span class="badge bg-secondary bg-opacity-25 text-white-50 border border-secondary border-opacity-25">NO</span>';
                document.getElementById('tsTelex').innerHTML = data.telex_release ? telexBadge : '-';
                
                document.getElementById('tsGri').innerText = data.surcharge_gri ? '$' + data.surcharge_gri : '-';
                document.getElementById('tsDdc').innerText = data.surcharge_ddc ? '$' + data.surcharge_ddc : '-';
                document.getElementById('tsAms').innerText = data.surcharge_ams || '-';
                document.getElementById('tsFumi').innerText = data.fumigation || '-';
                
                document.getElementById('tsDg3').innerText = data.dg_class3 || '-';
                document.getElementById('tsDg2').innerText = data.dg_class2 || '-';
                
                // MENGGABUNGKAN REMARKS & TARIFF_REMARKS
                let combinedRemarks = [];
                if (data.remarks) combinedRemarks.push(data.remarks);
                if (data.tariff_remarks) combinedRemarks.push(data.tariff_remarks);
                
                document.getElementById('tsRemarks').innerText = combinedRemarks.length > 0 ? combinedRemarks.join('\n\n') : '-';

                // PATCH: render biaya lokal & agen tujuan
                const wrap = document.getElementById('tsAgentRatesWrap');
                if (res.sin_agents && res.sin_agents.length) {
                    document.getElementById('tsAgentRates').innerHTML = tsRenderAgentRates(res.sin_agents);
                    wrap.classList.remove('d-none');
                } else {
                    wrap.classList.add('d-none');
                }

                document.getElementById('tsLocalCharges').innerHTML = tsRenderCharges(res.local_charges || {});
                document.getElementById('tsAgents').innerHTML       = tsRenderAgents(res.agents || []);
                document.getElementById('tsChargePorts').innerText  =
                    (res.charge_ports && res.charge_ports.length) ? 'Source: ' + res.charge_ports.join(' | ') : '';

            } else {
                fields.forEach(f => document.getElementById(f).innerText = 'Error loading data');
            }
        })
        .catch(err => {
            fields.forEach(f => document.getElementById(f).innerText = 'Network Error');
        });
    }
</script>