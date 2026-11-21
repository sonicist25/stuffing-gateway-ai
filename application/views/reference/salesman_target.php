<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<?php
$grouped_targets = [];
foreach ($salesmen_targets as $s) {
    $grouped_targets[$s->salesman_id]['info'] = $s;
    $grouped_targets[$s->salesman_id]['monthly'][] = $s;
}
?>

<div class="container mt-4 pb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0">Target Salesman Bulanan</h3>
            <p class="text-muted">Klik pada nama salesman untuk melihat detail target</p>
        </div>
        <span class="badge bg-dark px-3 py-2">Tahun <?= $this->session->userdata('db') ?></span>
    </div>

    <div class="accordion shadow-sm" id="accordionSalesman">
        <?php 
        $i = 0; 
        foreach ($grouped_targets as $salesman_id => $salesman): 
            $i++;
            $target_id = "collapse" . $salesman_id;
        ?>
        <div class="accordion-item">
            <h2 class="accordion-header" id="heading<?= $salesman_id ?>">
                <button class="accordion-button <?= $i === 1 ? '' : 'collapsed' ?>" type="button" 
                        data-bs-toggle="collapse" data-bs-target="#<?= $target_id ?>" 
                        aria-expanded="<?= $i === 1 ? 'true' : 'false' ?>" aria-controls="<?= $target_id ?>">
                    
                    <div class="d-flex justify-content-between align-items-center w-100 me-3">
                        <div>
                            <span class="fw-bold fs-5"><?= $salesman['info']->fullname ?></span>
                            <span class="ms-2 badge bg-light text-dark border"><?= $salesman['info']->region_id ?></span>
                        </div>
                        <div>
                            <?php if ($salesman['info']->management): ?>
                                <span class="badge bg-success-subtle text-success border border-success">Management</span>
                            <?php else: ?>
                                <span class="badge bg-primary-subtle text-primary border border-primary">Staff Sales</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </button>
            </h2>
            
            <div id="<?= $target_id ?>" class="accordion-collapse collapse <?= $i === 1 ? 'show' : '' ?>" 
                 aria-labelledby="heading<?= $salesman_id ?>" data-bs-parent="#accordionSalesman">
                <div class="accordion-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Bulan</th>
                                    <th class="text-end">Revenue Target</th>
                                    <th class="text-end pe-4">Profit Target</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($salesman['monthly'] as $m): ?>
                                <tr>
                                    <td class="ps-4 fw-semibold"><?= nama_bulan($m->month) ?></td>
                                    <td class="text-end text-primary fw-bold">Rp <?= number_format($m->revenue_target, 0, ',', '.') ?></td>
                                    <td class="text-end text-success fw-bold pe-4">Rp <?= number_format($m->profit_target, 0, ',', '.') ?></td>
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

<style>
    .accordion-button:not(.collapsed) {
        background-color: #f8f9fa;
        color: #000;
        box-shadow: none;
    }
    .accordion-button:focus {
        box-shadow: none;
        border-color: rgba(0,0,0,.125);
    }
    .table thead th {
        font-size: 0.8rem;
        text-transform: uppercase;
        color: #6c757d;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>