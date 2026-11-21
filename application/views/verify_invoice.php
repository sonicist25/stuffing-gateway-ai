<?php
function esc($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function fmt_money($amount, $currency){
    if ($amount === null || $amount === '') return '-';
    $n = (float)$amount;
    $cur = strtoupper((string)$currency);

    if ($cur === 'IDR') return 'IDR '.number_format($n, 0, ',', '.');
    if ($cur === 'USD') return 'USD '.number_format($n, 2, ',', '.');

    return $cur.' '.number_format($n, 2, ',', '.');
}

function fmt_date($ymd){
    if (!$ymd) return '-';
    $dt = DateTime::createFromFormat('Y-m-d', $ymd);
    return $dt ? $dt->format('d M Y') : esc($ymd);
}

$status = '';
$badgeClass = 'badge-neutral';
if (!empty($data['status'])) {
    $status = strtolower($data['status']);
    if ($status === 'paid')   $badgeClass = 'badge-green';
    if ($status === 'unpaid') $badgeClass = 'badge-amber';
    if ($status === 'void' || $status === 'cancelled' || $status === 'canceled') $badgeClass = 'badge-red';
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
  <title><?= esc($title) ?></title>

  <style>
    :root{
      --bg1:#0b1220;
      --bg2:#0f2f5c;
      --text:#e5e7eb;
      --muted:#9ca3af;
      --line:rgba(255,255,255,.08);
      --shadow: 0 20px 60px rgba(0,0,0,.35);
      --radius: 18px;
    }
    *{box-sizing:border-box}
    body{
      margin:0;
      font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial;
      color:var(--text);
      background:
        radial-gradient(1200px 600px at 20% -10%, rgba(59,130,246,.25), transparent 55%),
        radial-gradient(900px 500px at 85% 0%, rgba(16,185,129,.18), transparent 55%),
        linear-gradient(180deg, var(--bg2), var(--bg1));
      min-height:100vh;
      display:flex;
      align-items:center;
      justify-content:center;
      padding:18px;
    }
    .wrap{width:100%; max-width:760px}
    .card{
      background: linear-gradient(180deg, rgba(255,255,255,.04), rgba(255,255,255,.02));
      border:1px solid var(--line);
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      overflow:hidden;
    }
    .head{
      padding:18px 18px 12px 18px;
      border-bottom:1px solid var(--line);
      display:flex;
      gap:12px;
      align-items:flex-start;
      justify-content:space-between;
    }
    .brand{display:flex; gap:10px; align-items:center;}
    .logo{
      width:42px;height:42px;border-radius:12px;
      background: linear-gradient(135deg, rgba(59,130,246,.9), rgba(14,165,233,.6));
      display:flex; align-items:center; justify-content:center;
      font-weight:800; letter-spacing:.5px;
    }
    .title h1{margin:0;font-size:16px}
    .title p{margin:4px 0 0 0;color:var(--muted);font-size:12px;line-height:1.3}
    .pill{
      padding:8px 10px;
      border:1px solid var(--line);
      border-radius: 999px;
      font-size:12px;
      color:var(--muted);
      white-space:nowrap;
    }

    .content{padding:16px 18px 18px 18px}
    .grid{display:grid; grid-template-columns:1fr; gap:12px;}
    @media (min-width: 640px){ .grid{grid-template-columns:1fr 1fr} }

    .box{
      background: rgba(255,255,255,.03);
      border:1px solid var(--line);
      border-radius: 14px;
      padding:12px;
    }
    .row{
      display:flex; justify-content:space-between; gap:12px;
      padding:8px 0;
      border-bottom:1px dashed rgba(255,255,255,.10)
    }
    .row:last-child{border-bottom:0}
    .k{color:var(--muted); font-size:12px}
    .v{font-weight:700; font-size:13px; text-align:right; word-break:break-word}

    .statusLine{
      margin-top:12px;
      display:flex;
      flex-wrap:wrap;
      gap:10px;
      align-items:center;
      justify-content:space-between;
    }
    .badge{
      display:inline-flex;
      align-items:center;
      gap:8px;
      padding:10px 12px;
      border-radius:999px;
      font-weight:800;
      font-size:12px;
      border:1px solid var(--line);
      background: rgba(255,255,255,.03);
      text-transform: uppercase;
      letter-spacing:.6px;
    }
    .dot{width:10px;height:10px;border-radius:999px;background:#94a3b8}
    .badge-green .dot{background:#22c55e}
    .badge-amber .dot{background:#f59e0b}
    .badge-red   .dot{background:#ef4444}

    .danger{
      margin-top:14px;
      padding:12px;
      border-radius:14px;
      border:1px solid rgba(239,68,68,.25);
      background: rgba(239,68,68,.06);
      color:#fecaca;
      font-size:12px;
      line-height:1.45;
    }
    .success{
      margin-top:14px;
      padding:12px;
      border-radius:14px;
      border:1px solid rgba(34,197,94,.25);
      background: rgba(34,197,94,.08);
      color:#d1fae5;
      font-size:12px;
      line-height:1.45;
    }
    .foot{
      padding:14px 18px;
      border-top:1px solid var(--line);
      display:flex; gap:8px; align-items:center; justify-content:space-between;
      color:var(--muted);
      font-size:12px;
      flex-wrap:wrap;
    }
    a{color:#93c5fd; text-decoration:none}
    .btns{display:flex; gap:10px; flex-wrap:wrap; margin-top:12px}
    .btn{
      display:inline-flex; align-items:center; justify-content:center;
      padding:10px 12px;
      border-radius:12px;
      border:1px solid var(--line);
      background: rgba(255,255,255,.03);
      color:var(--text);
      font-size:12px;
      cursor:pointer;
    }
    .btn:hover{background: rgba(255,255,255,.06)}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="card">
      <div class="head">
        <div class="brand">
          <div class="logo">
                      <img src="<?= base_url('application/third_party/img/minilogo.png') ?>" 
                 alt="Logo" 
                 style="width:42px;height:42px;border-radius:12px;">
          </div>
          <div class="title">
            <h1>Invoice Verification</h1>
            <p>PT. Gateway Container Line • This page is opened from a QR code on your document</p>
          </div>
        </div>
        <div class="pill"><?= esc($ts) ?></div>
      </div>

      <div class="content">
        <?php if (!$ok): ?>
          <div class="danger">
            <b>VALIDATION FAILED</b><br/>
            <?= esc($message ?: 'Unable to validate the document.') ?>
            <?php if (!empty($number)): ?>
              <div style="margin-top:8px;color:#fca5a5">
                Number: <b><?= esc($number) ?></b>
              </div>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <div class="grid">
            <div class="box">
              <div class="row">
                <div class="k">Number</div>
                <div class="v" id="invNo"><?= esc($data['number']) ?></div>
              </div>
              <div class="row">
                <div class="k">Document Date</div>
                <div class="v"><?= esc(fmt_date($data['date'])) ?></div>
              </div>
              <div class="row">
                <div class="k">Type</div>
                <div class="v"><?= esc(strtoupper($data['type'])) ?></div>
              </div>
            </div>

            <div class="box">
              <div class="row">
                <div class="k">Grand Total</div>
                <div class="v"><?= esc(fmt_money($data['grand_total'], $data['currency'])) ?></div>
              </div>
              <div class="row">
                <div class="k">Paid Date</div>
                <div class="v"><?= esc($data['tgl_bayar'] ? $data['tgl_bayar'] : '-') ?></div>
              </div>
              <div class="row">
                <div class="k">Status</div>
                <div class="v"><?= esc(strtoupper($data['status'])) ?></div>
              </div>
            </div>
          </div>

          <div class="statusLine">
            <div class="badge <?= esc($badgeClass) ?>">
              <span class="dot"></span>
              <?= esc($data['status'] ?: 'unknown') ?>
            </div>

            <div style="color:var(--muted);font-size:12px">
              Invoice status has been validated by our system.
            </div>
          </div>

          <div class="success">
            <b>VALID</b> — Invoice record found and QR token matched.
          </div>

          <div class="danger" style="margin-top:12px">
            <b>Important Notice</b><br/>
            To ensure secure transactions, please note that PT. Gateway Container Line never changes bank details via email. If you have any concerns or questions, kindly verify with our Finance Department before proceeding with your payment.
          </div>
        <?php endif; ?>
      </div>

      <div class="foot">
        <div>© <?= date('Y') ?> PT. Gateway Container Line</div>
        <div style="text-align:right">
          Need help? <a href="mailto:finance@gateway-id.com">acct5@gateway-id.com</a>
        </div>
      </div>
    </div>
  </div>

<script>
function copyNo(){
  var el = document.getElementById('invNo');
  if(!el) return;
  var text = el.textContent || el.innerText;
  if(navigator.clipboard && window.isSecureContext){
    navigator.clipboard.writeText(text);
    alert('Copied: ' + text);
  }else{
    var t = document.createElement('textarea');
    t.value = text;
    document.body.appendChild(t);
    t.select();
    document.execCommand('copy');
    document.body.removeChild(t);
    alert('Copied: ' + text);
  }
}
</script>
</body>
</html>
