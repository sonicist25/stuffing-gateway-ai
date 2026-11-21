<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<form method="post" action="<?= base_url('reference/sales_target/save_target') ?>">
  <div class="card">
    <div class="card-header bg-primary text-white">
      <h5>Input Master Sales Target <?= $this->session->userdata('db'); ?></h5>
    </div>
    <div class="card-body">

      <!-- Hidden Info -->
      <input type="hidden" name="year" value="<?= $this->session->userdata('db'); ?>">
      <input type="hidden" name="region_id" value="<?= $this->session->userdata('region_id'); ?>">

      <!-- Target Tahunan -->
      <div class="row mb-3">
        <div class="col-md-6">
          <label for="target_revenue">Target Revenue Tahunan</label>
          <input type="text" name="target_revenue" id="target_revenue"
                 class="form-control form-control-rounded input-revenue" 
                 value="<?= isset($master) ? number_format($master->target_revenue,0,',','.') : '' ?>"
                 placeholder="Rp ..." required>
        </div>
        <div class="col-md-6">
          <label for="target_profit">Target Profit Tahunan</label>
          <input type="text" name="target_profit" id="target_profit"
                 class="form-control form-control-rounded input-profit"
                 value="<?= isset($master) ? number_format($master->target_profit,0,',','.') : '' ?>"
                 placeholder="Rp ..." required>
        </div>
      </div>

      <!-- Distribusi Bulanan -->
      <h6>Distribusi Bulanan</h6>
      <div class="card border mb-3">
        <div class="card-header bg-light fw-bold">Januari - Desember</div>
        <div class="card-body">
          <div class="row">
            <?php 
            $months = [
              1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
              5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
              9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
            ];
            for ($j = 1; $j <= 12; $j++): ?>
              <div class="col-md-3 mb-3">
                <strong><?= $months[$j] ?></strong>
                <input type="text" name="monthly_revenue[<?= $j ?>]"
                       class="form-control form-control-rounded mb-2 input-revenue text-end"
                       value="<?= isset($monthly[$j]) ? number_format($monthly[$j]['revenue_target'],0,',','.') : '' ?>"
                       placeholder="Rp">
                <input type="text" name="monthly_profit[<?= $j ?>]"
                       class="form-control form-control-rounded input-profit text-end"
                       value="<?= isset($monthly[$j]) ? number_format($monthly[$j]['profit_target'],0,',','.') : '' ?>"
                       placeholder="Rp">
              </div>
            <?php endfor; ?>
          </div>
        </div>
      </div>

      <!-- Tools -->
      <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-secondary" onclick="distributeEvenly()">Distribusi Rata</button>
        <button type="button" class="btn btn-outline-secondary" onclick="applyFirstMonth()">Salin Bulan Pertama</button>
      </div>

      <!-- Indicator -->
      <div class="mt-3">
        <strong>Total Bulanan:</strong>
        <div id="totalIndicator" class="alert alert-secondary p-2 mt-2">
          Revenue: Rp 0 | Profit: Rp 0
        </div>
      </div>

      <!-- Submit -->
      <div class="text-end mt-3">
        <button type="submit" class="btn btn-success">Simpan Target</button>
      </div>
    </div>
  </div>
</form>

<script>
// fungsi bersih angka
function cleanCurrency(str) {
  return parseInt(str.replace(/[^0-9]/g, "")) || 0;
}

// distribusi rata
function distributeEvenly() {
  let revenue = cleanCurrency(document.getElementById('target_revenue').value);
  let profit  = cleanCurrency(document.getElementById('target_profit').value);

  let revPerMonth = Math.round(revenue / 12);
  let profPerMonth = Math.round(profit / 12);

  for (let i = 1; i <= 12; i++) {
    document.querySelector(`[name="monthly_revenue[${i}]"]`).value =
      new Intl.NumberFormat("id-ID").format(revPerMonth);
    document.querySelector(`[name="monthly_profit[${i}]"]`).value =
      new Intl.NumberFormat("id-ID").format(profPerMonth);
  }
  updateTotals();
}

// salin bulan pertama
function applyFirstMonth() {
  let rev = document.querySelector(`[name="monthly_revenue[1]"]`).value;
  let prof = document.querySelector(`[name="monthly_profit[1]"]`).value;
  for (let i = 2; i <= 12; i++) {
    document.querySelector(`[name="monthly_revenue[${i}]"]`).value = rev;
    document.querySelector(`[name="monthly_profit[${i}]"]`).value = prof;
  }
  updateTotals();
}

// update indikator total
function updateTotals() {
  let targetRevenue = cleanCurrency(document.getElementById('target_revenue').value);
  let targetProfit  = cleanCurrency(document.getElementById('target_profit').value);

  let totalRevenue = 0;
  let totalProfit  = 0;

  for (let i = 1; i <= 12; i++) {
    totalRevenue += cleanCurrency(document.querySelector(`[name="monthly_revenue[${i}]"]`).value);
    totalProfit  += cleanCurrency(document.querySelector(`[name="monthly_profit[${i}]"]`).value);
  }

  let indicator = document.getElementById("totalIndicator");
  indicator.innerHTML = `Revenue: Rp ${totalRevenue.toLocaleString()} | Profit: Rp ${totalProfit.toLocaleString()}`;

  if (totalRevenue === targetRevenue && totalProfit === targetProfit) {
    indicator.className = "alert alert-success p-2 mt-2";
  } else {
    indicator.className = "alert alert-danger p-2 mt-2";
  }
}

// jalankan saat halaman pertama kali load
window.addEventListener("DOMContentLoaded", updateTotals);

document.addEventListener("DOMContentLoaded", function() {
  <?php if ($this->session->flashdata('success')): ?>
    Swal.fire({
      icon: 'success',
      title: 'Berhasil',
      text: '<?= $this->session->flashdata('success'); ?>',
      showConfirmButton: false,
      timer: 2000
    });
  <?php endif; ?>

  <?php if ($this->session->flashdata('error')): ?>
    Swal.fire({
      icon: 'error',
      title: 'Gagal',
      text: '<?= $this->session->flashdata('error'); ?>',
      showConfirmButton: true
    });
  <?php endif; ?>
});

</script>