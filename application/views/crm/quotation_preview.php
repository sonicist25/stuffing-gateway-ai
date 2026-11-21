<?php
$qt = is_array($quote ?? null) ? $quote : [];
$groups = is_array($items['groups'] ?? null)
    ? $items['groups']
    : ['FREIGHT' => [], 'ORIGIN' => [], 'DESTINATION' => []];

foreach (['FREIGHT', 'ORIGIN', 'DESTINATION'] as $groupName) {
    if (!isset($groups[$groupName]) || !is_array($groups[$groupName])) {
        $groups[$groupName] = [];
    }
}

$h = static function ($value) {
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
};

$currency = strtoupper(trim((string)($qt['currency'] ?? 'IDR'))) ?: 'IDR';

$normalizeCurrency = static function ($value, $fallback = 'IDR') {
    $value = strtoupper(trim((string)$value));
    if ($value === '' || in_array($value, ['RP', 'RP.', 'RUPIAH'], true)) {
        return strtoupper((string)$fallback) ?: 'IDR';
    }
    return $value;
};

$fmtCurrency = static function ($number, $rowCurrency = 'IDR') use ($normalizeCurrency) {
    if ($number === null || $number === '') {
        return '';
    }

    $rowCurrency = $normalizeCurrency($rowCurrency, 'IDR');
    $decimals = $rowCurrency === 'IDR' ? 0 : 2;
    return number_format((float)$number, $decimals, '.', ',');
};

$fmt = static function ($number) use ($fmtCurrency, $currency) {
    return $fmtCurrency($number, $currency);
};

$multiline = static function ($value) use ($h) {
    $text = trim((string)($value ?? ''));
    if ($text === '') {
        return '';
    }

    $text = str_replace(["\r\n", "\r"], "\n", $text);
    return nl2br($h($text), false);
};

$mode = strtoupper(trim((string)($qt['transport_mode'] ?? '')));
$seaType = strtoupper(trim((string)($qt['sea_type'] ?? '')));
$shipmentType = trim((string)($qt['shipment_type'] ?? ''));
$serviceLevel = trim((string)($qt['service_level'] ?? ''));
$incoterm = strtoupper(trim((string)($qt['incoterm'] ?? $qt['term'] ?? '')));

// Min charge hanya relevan untuk AIR dan SEA LCL.
$showMinCharge = ($mode === 'AIR' || ($mode === 'SEA' && $seaType === 'LCL'));

$serviceMode = trim($mode . ($seaType !== '' ? ' / ' . $seaType : ''));
$service = trim(implode(' • ', array_filter([
    $shipmentType,
    $serviceMode,
    $serviceLevel,
], static function ($value) {
    return trim((string)$value) !== '';
})));

$placeLabel = static function ($code, $name) {
    $code = strtoupper(trim((string)$code));
    $name = trim((string)$name);

    if ($code !== '' && $name !== '') {
        return $name . ' (' . $code . ')';
    }

    return $name !== '' ? $name : $code;
};

$originLabel = $placeLabel($qt['origin'] ?? '', $qt['origin_name'] ?? '');
$destinationLabel = $placeLabel($qt['destination'] ?? '', $qt['destination_name'] ?? '');
$route = trim(implode(' → ', array_filter([
    $originLabel,
    $destinationLabel,
], static function ($value) {
    return trim((string)$value) !== '';
})));

$accountName = trim((string)(
    $qt['client_company_name']
    ?? $qt['account_name']
    ?? '-'
));
$accountAddress = trim((string)(
    $qt['client_company_address']
    ?? $qt['account_address']
    ?? ''
));

$cargoTypeRaw = strtoupper(trim((string)(
    $qt['cargo_type']
    ?? $qt['commodity_type']
    ?? ''
)));
$isDg = in_array($cargoTypeRaw, [
    'DG',
    'DANGEROUS GOODS',
    'DANGEROUS_GOODS',
    'DANGEROUS',
], true);

if ($cargoTypeRaw === '') {
    $cargoTypeLabel = '-';
} elseif ($isDg) {
    $cargoTypeLabel = 'Dangerous Goods (DG)';
} elseif (in_array($cargoTypeRaw, ['GENERAL', 'GENERAL CARGO', 'GENERAL_CARGO'], true)) {
    $cargoTypeLabel = 'General Cargo';
} else {
    $cargoTypeLabel = trim((string)($qt['cargo_type'] ?? $qt['commodity_type'] ?? '-'));
}

$dgClass = trim((string)($qt['dg_class'] ?? ''));
$hsCode = trim((string)($qt['hs_code'] ?? ''));

$decodeSubItems = static function ($raw) {
    if ($raw === null || $raw === '') {
        return [];
    }

    if (is_array($raw)) {
        return $raw;
    }

    if (is_object($raw)) {
        return (array)$raw;
    }

    if (is_string($raw)) {
        $decoded = json_decode(trim($raw), true);
        return is_array($decoded) ? $decoded : [];
    }

    return [];
};

$decodeRateInformation = static function ($raw) {
    if ($raw === null || $raw === '') {
        return [];
    }

    if (is_object($raw)) {
        $raw = (array)$raw;
    }

    if (is_string($raw)) {
        $text = trim($raw);
        if ($text === '') {
            return [];
        }

        $decoded = json_decode($text, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Dukungan data lama berupa plain text.
            return [[
                'source' => 'RATE INFORMATION',
                'remarks' => $text,
            ]];
        }

        $raw = $decoded;
    }

    if (!is_array($raw)) {
        return [];
    }

    // Dukungan wrapper seperti {"rate_meta": [...]}.
    if (isset($raw['rate_meta']) && is_array($raw['rate_meta'])) {
        $raw = $raw['rate_meta'];
    }

    if (isset($raw['rate_information']) && is_array($raw['rate_information'])) {
        $raw = $raw['rate_information'];
    }

    // Associative object tunggal diubah menjadi list satu elemen.
    $keys = array_keys($raw);
    $isList = ($keys === range(0, count($raw) - 1));
    if (!$isList) {
        $raw = [$raw];
    }

    $rows = [];
    foreach ($raw as $row) {
        if (is_object($row)) {
            $row = (array)$row;
        }
        if (!is_array($row)) {
            continue;
        }

        $normalized = [
            'source'       => trim((string)($row['source'] ?? $row['rate_source'] ?? '')),
            'liner'        => trim((string)($row['liner'] ?? $row['carrier'] ?? $row['vendor'] ?? '')),
            'routing'      => trim((string)($row['routing'] ?? $row['route'] ?? '')),
            'container'    => trim((string)($row['container'] ?? $row['container_size'] ?? $row['basis'] ?? '')),
            'validity'     => trim((string)($row['validity'] ?? $row['valid_until'] ?? '')),
            'transit_time' => trim((string)($row['transit_time'] ?? $row['tt'] ?? '')),
            'free_time'    => trim((string)($row['free_time'] ?? '')),
            'include'      => trim((string)($row['include'] ?? $row['included'] ?? '')),
            'exclude'      => trim((string)($row['exclude'] ?? $row['excluded'] ?? '')),
            'remarks'      => trim((string)($row['remarks'] ?? $row['remark'] ?? '')),
            'note'         => trim((string)($row['note'] ?? '')),
        ];

        $hasValue = false;
        foreach ($normalized as $value) {
            if ($value !== '') {
                $hasValue = true;
                break;
            }
        }

        if ($hasValue) {
            $rows[] = $normalized;
        }
    }

    return $rows;
};

$rateInformation = $decodeRateInformation(
    $qt['rate_information']
    ?? $qt['rate_meta']
    ?? []
);


// Customer-ready cleanup. Preview menjadi lapisan pertahanan terakhir agar
// internal source/margin/buying note tidak pernah ikut ke dokumen customer.
$customerInternalPatterns = [
    '/^ref\s*:/iu',
    '/\bmargin\s*\d+(?:[.,]\d+)?\s*%/iu',
    '/\bmarkup\b/iu',
    '/\bbuying\s+(?:rate|price|cost)\b/iu',
    '/\bcost\s+source\b/iu',
    '/\binternal\s+(?:rate|cost|note|reference)\b/iu',
    '/\bguidance\s+rate\b/iu',
    '/\brebate\b/iu',
    '/\bkomisi\b/iu',
    '/\bcommission\b/iu',
    '/\bagent\s+lain\b/iu',
    '/\bcek\s+master\s+data\b/iu',
    '/\bdari\s+data\s+agent\b/iu',
    '/\bmaster_(?:import|export|local|rate)/iu',
    '/\bchart(?:er)?link\b/iu',
];

$cleanCustomerNarrative = static function ($value) use ($customerInternalPatterns) {
    $raw = trim(preg_replace('/[ \t]+/u', ' ', str_replace(["\r\n", "\r"], "\n", (string)$value)));
    if ($raw === '') return '';

    $parts = preg_split('/\n+|(?<=[.!?])\s+(?=[A-Z0-9*\-])/u', $raw) ?: [];
    $kept = [];
    foreach ($parts as $part) {
        $part = trim((string)$part);
        if ($part === '') continue;

        $internal = false;
        foreach ($customerInternalPatterns as $pattern) {
            if (preg_match($pattern, $part)) {
                $internal = true;
                break;
            }
        }
        if ($internal) continue;

        $part = preg_replace('/^note\s*[:\-]\s*/iu', '', $part);
        $part = preg_replace('/^(?:kargo\s+dg|dg\s+cargo)\s*[:\-]\s*/iu', '', $part);
        $part = trim((string)$part);
        if ($part !== '' && !in_array($part, $kept, true)) $kept[] = $part;
    }
    return implode("\n", $kept);
};

$friendlyRateSource = static function ($value) {
    $raw = trim((string)$value);
    if ($raw === '') return '';
    $upper = strtoupper($raw);

    if (strpos($upper, 'DG') !== false) return 'Dangerous Goods Requirements';
    if (strpos($upper, 'TRUCK') !== false || strpos($upper, 'DELIVERY') !== false) return 'Delivery Rate';
    if (strpos($upper, 'AIR') !== false) return 'Air Freight';
    if (strpos($upper, 'IMPORT_LCL') !== false || strpos($upper, 'EXPORT_LCL') !== false || $upper === 'TS_RATE') return 'Sea Freight';
    if (strpos($upper, 'LOCAL') !== false) return 'Local Charges';
    if (strpos($upper, 'FREIGHT') !== false) return 'Freight Rate';
    if (strpos($raw, '_') !== false && preg_match('/^[A-Z0-9_\-\.\/]+$/', $raw)) return '';
    return $raw;
};

$normalizeCustomerValidity = static function ($value) {
    $raw = trim((string)$value);
    if ($raw === '') return '';
    if (preg_match_all('/\b20\d{2}-\d{2}-\d{2}\b/', $raw, $matches) && !empty($matches[0])) {
        $iso = end($matches[0]);
        $dt = DateTime::createFromFormat('Y-m-d', $iso);
        if ($dt instanceof DateTime) return 'Valid until ' . $dt->format('d M Y');
    }
    $raw = preg_replace('/^(?:validity|valid)\s*[:\-]\s*/iu', '', $raw);
    return trim((string)$raw);
};

$cleanChargeDescription = static function ($value) {
    $out = trim(preg_replace('/\s+/u', ' ', (string)$value));
    $out = preg_replace('/\s*\((?:AS\s+PER\s+RECEIPT|AS\s+PER\s+PIB)\)\s*/iu', ' ', $out);
    $out = preg_replace('/\s*\(IF\s+ANY\)\s*/iu', ' ', $out);
    $out = preg_replace('/\s*\(OPTIONAL\s*,?\s*JIKA\s+DIMINTA\)\s*/iu', ' (Optional) ', $out);
    $out = preg_replace('/\s*\(FOR\s+CUSTOMS\s+PURPOSE\s+ONLY\s*,?\s*CANT\s+FOR\s+CLAIM\s+IF\s+DAMAGE\)\s*/iu', ' (For Customs Purpose Only - Not Valid for Cargo Damage Claims) ', $out);
    return trim(preg_replace('/\s{2,}/u', ' ', (string)$out));
};

$cleanChargeDetail = static function ($value) use ($cleanCustomerNarrative) {
    $out = $cleanCustomerNarrative($value);
    $out = preg_replace('/\(\s*\+?\s*margin\s*\d+(?:[.,]\d+)?\s*%\s*\)/iu', '', $out);
    $out = preg_replace('/\bmargin\s*\d+(?:[.,]\d+)?\s*%\b/iu', '', $out);
    $out = trim(preg_replace('/\s{2,}/u', ' ', (string)$out));
    if (preg_match('/^(?:local\s+charge|freight\s+rate|rate)$/iu', $out)) return '';
    return $out;
};

$headerValidUntil = trim((string)($qt['valid_until'] ?? ''));
$customerRateInformation = [];
foreach ($rateInformation as $rate) {
    if (!is_array($rate)) continue;

    $narrative = trim(implode("\n", array_filter([
        (string)($rate['remarks'] ?? ''),
        (string)($rate['note'] ?? ''),
    ], static function ($v) { return trim((string)$v) !== ''; })));

    $clean = [
        'source'       => $friendlyRateSource($rate['source'] ?? ''),
        'liner'        => $cleanCustomerNarrative($rate['liner'] ?? ''),
        'routing'      => $cleanCustomerNarrative($rate['routing'] ?? ''),
        'container'    => $cleanCustomerNarrative($rate['container'] ?? ''),
        'validity'     => $headerValidUntil !== '' ? '' : $normalizeCustomerValidity($rate['validity'] ?? ''),
        'transit_time' => $cleanCustomerNarrative($rate['transit_time'] ?? ''),
        'free_time'    => $cleanCustomerNarrative($rate['free_time'] ?? ''),
        'include'      => $cleanCustomerNarrative($rate['include'] ?? ''),
        'exclude'      => $cleanCustomerNarrative($rate['exclude'] ?? ''),
        'remarks'      => $cleanCustomerNarrative($narrative),
        'note'         => '',
    ];

    $hasCustomerValue = false;
    foreach ($clean as $key => $value) {
        if (!in_array($key, ['note', 'source'], true) && trim((string)$value) !== '') {
            $hasCustomerValue = true;
            break;
        }
    }
    if ($hasCustomerValue) $customerRateInformation[] = $clean;
}
$rateInformation = $customerRateInformation;

$pricingLabel = static function ($basis) {
    $basis = strtoupper(trim((string)$basis));

    $labels = [
        'AT_COST'        => 'AT COST',
        'IF_ANY'         => 'IF ANY',
        'PER_RECEIPT'    => 'AS PER RECEIPT',
        'QUOTE_REQUIRED' => 'RATE ON REQUEST',
        'COLLECT'        => 'COLLECT',
        'EXCLUDED'       => 'EXCLUDED',
        'ESTIMATE'       => 'ESTIMATED',
        'INFO'           => 'INFO',
        'AS_AGREED'      => 'AS AGREED',
    ];

    return $labels[$basis] ?? '';
};

$isIncludedInTotal = static function ($row) {
    if (!is_array($row)) {
        return false;
    }

    if (isset($row['include_in_total']) && (string)$row['include_in_total'] === '0') {
        return false;
    }

    $basis = strtoupper(trim((string)($row['pricing_basis'] ?? 'FIXED')));
    return !in_array($basis, [
        'AT_COST',
        'IF_ANY',
        'PER_RECEIPT',
        'QUOTE_REQUIRED',
        'COLLECT',
        'EXCLUDED',
        'INFO',
        'AS_AGREED',
    ], true);
};

// Hitung ulang subtotal preview agar COLLECT/EXCLUDED/IF ANY tidak masuk total.
$displaySubtotal = [
    'FREIGHT' => 0.0,
    'ORIGIN' => 0.0,
    'DESTINATION' => 0.0,
    'TOTAL' => 0.0,
];

$billableCurrencies = [];
$displaySubtotalByCurrency = [];

foreach (['FREIGHT', 'ORIGIN', 'DESTINATION'] as $groupName) {
    foreach ($groups[$groupName] as $row) {
        if (!is_array($row)) continue;
        $subItems = $decodeSubItems($row['sub_items'] ?? null);

        // Parent rows with sub-items are option groups in the current design
        // and are intentionally excluded from the combined total.
        if ($subItems) {
            continue;
        } elseif ($isIncludedInTotal($row)) {
            $curr = $normalizeCurrency($row['currency'] ?? $currency, $currency);
            $billableCurrencies[$curr] = true;
            $displaySubtotalByCurrency[$curr] = ($displaySubtotalByCurrency[$curr] ?? 0)
                + (float)($row['amount'] ?? 0);
        }
    }
}
$billableCurrencies = array_keys($billableCurrencies);
$isMixedCurrency = count($billableCurrencies) > 1;

foreach (['FREIGHT', 'ORIGIN', 'DESTINATION'] as $groupName) {
    foreach ($groups[$groupName] as $row) {
        if (!is_array($row)) {
            continue;
        }

        $subItems = $decodeSubItems($row['sub_items'] ?? null);
        if ($subItems) {
            $parentIncluded = $isIncludedInTotal($row);
            foreach ($subItems as $subItem) {
                if (!is_array($subItem)) {
                    continue;
                }

                $effectiveSubItem = $subItem;
                if (!array_key_exists('include_in_total', $effectiveSubItem)) {
                    $effectiveSubItem['include_in_total'] = $parentIncluded ? 1 : 0;
                }
                if (!array_key_exists('pricing_basis', $effectiveSubItem)) {
                    $effectiveSubItem['pricing_basis'] = $row['pricing_basis'] ?? 'FIXED';
                }

                if ($isIncludedInTotal($effectiveSubItem)) {
                    $displaySubtotal[$groupName] += (float)($effectiveSubItem['amount'] ?? 0);
                }
            }
        } elseif ($isIncludedInTotal($row)) {
            $displaySubtotal[$groupName] += (float)($row['amount'] ?? 0);
        }
    }

    $displaySubtotal['TOTAL'] += $displaySubtotal[$groupName];
}

$status = strtoupper(trim((string)($qt['status'] ?? 'DRAFT'))) ?: 'DRAFT';
$statusClass = 'status-draft';
if (in_array($status, ['APPROVED', 'ACCEPTED'], true)) {
    $statusClass = 'status-approved';
} elseif (in_array($status, ['REJECTED', 'CANCELLED'], true)) {
    $statusClass = 'status-rejected';
} elseif ($status === 'SENT') {
    $statusClass = 'status-sent';
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    * { box-sizing: border-box; }

    body {
      font-family: DejaVu Sans, Arial, sans-serif;
      font-size: 10px;
      color: #334155;
      margin: 0;
      padding: 0;
      background-image: url("<?= $h(base_url('application/third_party/img/vector.jpg')) ?>");
      background-repeat: no-repeat;
      background-position: top center;
      background-size: cover;
    }

    .page { padding: 20px 35px; }

    .header-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 15px;
    }

    .header-table td { vertical-align: top; }
    .title-label {
      font-size: 22px;
      font-weight: bold;
      color: #1e3a8a;
      text-transform: uppercase;
      margin: 0;
    }

    .status-pill {
      display: inline-block;
      padding: 3px 8px;
      font-weight: bold;
      font-size: 8px;
      text-transform: uppercase;
      border-radius: 3px;
    }

    .status-draft { background: #f1f5f9; color: #475569; }
    .status-sent { background: #dbeafe; color: #1d4ed8; }
    .status-approved { background: #dcfce7; color: #166534; }
    .status-rejected { background: #fee2e2; color: #b91c1c; }

    .block-card {
      border: 1px solid #dbe3ee;
      background: #ffffff;
      margin-bottom: 12px;
      page-break-inside: avoid;
    }

    .block-title {
      background: #eef4ff;
      border-bottom: 1px solid #dbe3ee;
      color: #1e3a8a;
      font-size: 9px;
      font-weight: bold;
      padding: 7px 10px;
      text-transform: uppercase;
      letter-spacing: .3px;
    }

    .meta-table {
      width: 100%;
      border-collapse: collapse;
      table-layout: fixed;
    }

    .meta-table td {
      border-right: 1px solid #edf2f7;
      border-bottom: 1px solid #edf2f7;
      padding: 8px 10px;
      vertical-align: top;
    }

    .meta-table tr:last-child td { border-bottom: 0; }
    .meta-table td:last-child { border-right: 0; }

    .info-k {
      display: block;
      font-size: 7.5px;
      color: #64748b;
      font-weight: bold;
      text-transform: uppercase;
      margin-bottom: 3px;
    }

    .info-v {
      display: block;
      font-size: 9.5px;
      color: #0f172a;
      font-weight: bold;
      line-height: 1.35;
    }

    .info-sub {
      display: block;
      font-size: 8.5px;
      color: #64748b;
      font-weight: normal;
      line-height: 1.35;
      margin-top: 2px;
    }

    .danger-value { color: #b91c1c; }

    .rate-card {
      border: 1px solid #cbdcf6;
      margin: 0 10px 10px 10px;
      page-break-inside: avoid;
    }

    .rate-card:first-child { margin-top: 10px; }

    .rate-head {
      background: #f7faff;
      border-bottom: 1px solid #dbe3ee;
      padding: 7px 9px;
      font-weight: bold;
      color: #1e3a8a;
    }

    .rate-chip {
      display: inline-block;
      border: 1px solid #bfdbfe;
      background: #eff6ff;
      color: #1d4ed8;
      border-radius: 3px;
      padding: 2px 5px;
      margin-right: 4px;
      font-size: 7.5px;
      font-weight: bold;
    }

    .rate-meta-table,
    .rate-detail-table {
      width: 100%;
      border-collapse: collapse;
      table-layout: fixed;
    }

    .rate-meta-table td {
      width: 25%;
      padding: 6px 8px;
      border-right: 1px solid #edf2f7;
      border-bottom: 1px solid #edf2f7;
      vertical-align: top;
    }

    .rate-meta-table td:last-child { border-right: 0; }

    .rate-detail-table td {
      padding: 6px 8px;
      border-bottom: 1px solid #edf2f7;
      vertical-align: top;
      line-height: 1.4;
    }

    .rate-detail-table tr:last-child td { border-bottom: 0; }
    .rate-detail-label {
      width: 78px;
      color: #475569;
      font-size: 7.5px;
      font-weight: bold;
      text-transform: uppercase;
    }

    .include-text { color: #166534; }
    .exclude-text { color: #b91c1c; }
    .remarks-text { color: #334155; }

    .section-title {
      font-size: 10px;
      font-weight: bold;
      color: #1e3a8a;
      border-left: 4px solid #1e3a8a;
      padding-left: 8px;
      margin: 14px 0 7px 0;
      text-transform: uppercase;
    }

    .data-table {
      width: 100%;
      border-collapse: collapse;
      table-layout: fixed;
      margin-bottom: 12px;
    }

    /* Some HTML-to-PDF engines render THEAD with equal-width cells even when
       COLGROUP uses percentages. Explicit widths are therefore also applied
       directly to TH/TD below. */
    .data-table th,
    .data-table td {
      box-sizing: border-box;
    }

    .data-table th {
      background: #f8fafc;
      color: #475569;
      font-size: 8px;
      padding: 7px 4px;
      border-bottom: 2px solid #cbd5e1;
      text-align: left;
    }

    .data-table td {
      padding: 7px 4px;
      border-bottom: 1px solid #edf2f7;
      vertical-align: top;
      overflow-wrap: break-word;
      word-wrap: break-word;
    }

    .text-left { text-align: left; }
    .text-right { text-align: right; }
    .text-center { text-align: center; }
    .bold { font-weight: bold; }

    .pricing-label {
      display: inline-block;
      font-size: 7px;
      font-weight: bold;
      color: #475569;
      background: #f1f5f9;
      border: 1px solid #e2e8f0;
      padding: 2px 4px;
      border-radius: 2px;
      white-space: nowrap;
    }

    .pricing-warning { color: #92400e; background: #fffbeb; border-color: #fde68a; }
    .pricing-excluded { color: #b91c1c; background: #fef2f2; border-color: #fecaca; }
    .pricing-collect { color: #0369a1; background: #f0f9ff; border-color: #bae6fd; }

    .summary-table {
      width: 265px;
      margin-left: auto;
      border-collapse: collapse;
      border: 1px solid #e2e8f0;
    }

    .summary-table td {
      padding: 6px 8px;
      border-bottom: 1px solid #e2e8f0;
      font-size: 9px;
    }

    .total-row {
      background: #1e3a8a;
      color: #ffffff;
      font-weight: bold;
    }

    .footer-note {
      font-size: 7.5px;
      color: #64748b;
      margin-top: 24px;
      line-height: 1.5;
      border-top: 1px solid #e2e8f0;
      padding-top: 8px;
    }
  </style>
</head>
<body>
  <div class="page">
    <table class="header-table">
      <tr>
        <td width="50%">
          <img
            src="<?= $h(base_url('application/third_party/img/Desain.png')) ?>"
            alt="Gateway Logo"
            style="height:85px;"
          >
        </td>
        <td width="50%" class="text-right">
          <h1 class="title-label">Quotation</h1>
          <div style="margin-top:5px;">No: <b><?= $h($qt['quote_no'] ?? '-') ?></b></div>
          <div>Date: <?= $h($qt['quote_date'] ?? '-') ?></div>
          <?php if (!empty($qt['valid_until'])): ?>
            <div style="color:#b91c1c;">Valid Until: <b><?= $h($qt['valid_until']) ?></b></div>
          <?php endif; ?>
        </td>
      </tr>
    </table>

    <div class="block-card">
      <div class="block-title">Customer &amp; Shipment Details</div>
      <table class="meta-table">
        <tr>
          <td colspan="2" style="width:50%;">
            <span class="info-k">To</span>
            <span class="info-v"><?= $h($accountName ?: '-') ?></span>
            <?php if ($accountAddress !== ''): ?>
              <span class="info-sub"><?= $multiline($accountAddress) ?></span>
            <?php endif; ?>
          </td>
          <td style="width:25%;">
            <span class="info-k">Shipment Type</span>
            <span class="info-v"><?= $h($shipmentType ?: '-') ?></span>
          </td>
          <td style="width:25%;">
            <span class="info-k">Service Mode</span>
            <span class="info-v"><?= $h($serviceMode ?: '-') ?></span>
            <?php if ($serviceLevel !== ''): ?>
              <span class="info-sub"><?= $h($serviceLevel) ?></span>
            <?php endif; ?>
          </td>
        </tr>
        <tr>
          <td colspan="2">
            <span class="info-k">Route</span>
            <span class="info-v"><?= $h($route ?: '-') ?></span>
          </td>
          <td>
            <span class="info-k">Incoterm</span>
            <span class="info-v"><?= $h($incoterm ?: '-') ?></span>
          </td>
          <td>
            <span class="info-k">Service Summary</span>
            <span class="info-v"><?= $h($service ?: '-') ?></span>
          </td>
        </tr>
      </table>
    </div>

    <div class="block-card">
      <div class="block-title">Cargo Information</div>
      <table class="meta-table">
        <tr>
          <td style="width:34%;">
            <span class="info-k">Cargo Type</span>
            <span class="info-v<?= $isDg ? ' danger-value' : '' ?>"><?= $h($cargoTypeLabel) ?></span>
          </td>
          <td style="width:33%;">
            <span class="info-k">DG Class</span>
            <span class="info-v<?= $isDg ? ' danger-value' : '' ?>"><?= $h($dgClass !== '' ? $dgClass : '-') ?></span>
          </td>
          <td style="width:33%;">
            <span class="info-k">HS Code</span>
            <span class="info-v"><?= $h($hsCode !== '' ? $hsCode : '-') ?></span>
          </td>
        </tr>
      </table>
    </div>

    <?php if ($rateInformation): ?>
      <div class="block-card">
        <div class="block-title">Rate Information</div>

        <?php foreach ($rateInformation as $index => $rate): ?>
          <?php
          $rateTitle = trim((string)($rate['source'] ?? ''));
          if ($rateTitle === '') $rateTitle = 'Rate Conditions';

          $metaFields = [];
          if ($rate['container'] !== '') $metaFields[] = ['Container / Basis', $rate['container']];
          if ($rate['validity'] !== '') $metaFields[] = ['Validity', $rate['validity']];
          if ($rate['transit_time'] !== '') $metaFields[] = ['Transit Time', $rate['transit_time']];
          if ($rate['free_time'] !== '') $metaFields[] = ['Free Time', $rate['free_time']];
          $metaWidth = $metaFields ? floor(100 / count($metaFields)) : 100;
          ?>
          <div class="rate-card">
            <div class="rate-head">
              <?= $h($rateTitle) ?>
              <?php if ($rate['liner'] !== ''): ?>
                <span class="rate-chip"><?= $h($rate['liner']) ?></span>
              <?php endif; ?>
              <?php if ($rate['routing'] !== ''): ?>
                <span class="rate-chip"><?= $h($rate['routing']) ?></span>
              <?php endif; ?>
            </div>

            <?php if ($metaFields): ?>
              <table class="rate-meta-table">
                <tr>
                  <?php foreach ($metaFields as $meta): ?>
                    <td style="width:<?= (int)$metaWidth ?>%;">
                      <span class="info-k"><?= $h($meta[0]) ?></span>
                      <span class="info-v"><?= $h($meta[1]) ?></span>
                    </td>
                  <?php endforeach; ?>
                </tr>
              </table>
            <?php endif; ?>

            <?php if ($rate['include'] !== '' || $rate['exclude'] !== '' || $rate['remarks'] !== ''): ?>
              <table class="rate-detail-table">
                <?php if ($rate['include'] !== ''): ?>
                  <tr>
                    <td class="rate-detail-label">Includes</td>
                    <td class="include-text"><?= $multiline($rate['include']) ?></td>
                  </tr>
                <?php endif; ?>

                <?php if ($rate['exclude'] !== ''): ?>
                  <tr>
                    <td class="rate-detail-label">Excludes</td>
                    <td class="exclude-text"><?= $multiline($rate['exclude']) ?></td>
                  </tr>
                <?php endif; ?>

                <?php if ($rate['remarks'] !== ''): ?>
                  <tr>
                    <td class="rate-detail-label">Conditions</td>
                    <td class="remarks-text"><?= $multiline($rate['remarks']) ?></td>
                  </tr>
                <?php endif; ?>
              </table>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php
    $renderPricingValue = static function ($row, $field) use ($fmtCurrency, $h, $pricingLabel, $normalizeCurrency, $currency) {
        $basis = strtoupper(trim((string)($row['pricing_basis'] ?? 'FIXED')));
        $label = $pricingLabel($basis);
        $value = (float)($row[$field] ?? 0);
        $rowCurrency = $normalizeCurrency($row['currency'] ?? $currency, $currency);
        $money = $h($rowCurrency) . ' ' . $fmtCurrency($value, $rowCurrency);

        if ($basis === 'FIXED' || $basis === '') {
            return $money;
        }

        if ($basis === 'ESTIMATE' && $value != 0.0) {
            return $money . '<br><span class="pricing-label pricing-warning">ESTIMATED</span>';
        }

        $class = 'pricing-label';
        if (in_array($basis, ['IF_ANY', 'PER_RECEIPT', 'QUOTE_REQUIRED', 'ESTIMATE'], true)) {
            $class .= ' pricing-warning';
        } elseif ($basis === 'EXCLUDED') {
            $class .= ' pricing-excluded';
        } elseif ($basis === 'COLLECT') {
            $class .= ' pricing-collect';
        }

        return $label !== ''
            ? '<span class="' . $h($class) . '">' . $h($label) . '</span>'
            : $money;
    };

    $renderTable = static function (
        $title,
        $rows,
        $showDestination,
        $showMinimumCharge
    ) use (
        $h,
        $fmt,
        $decodeSubItems,
        $renderPricingValue,
        $multiline,
        $cleanChargeDescription,
        $cleanChargeDetail
    ) {
        if (!$rows) {
            return;
        }

        // Named widths are used both on COLGROUP and directly on TH/TD.
        // Direct cell widths are important for Dompdf/mPDF/TCPDF-like renderers
        // that may lay out THEAD separately and otherwise make header cells equal.
        $columnWidths = [
            'dest'        => null,
            'description' => null,
            'qty'         => null,
            'unit_price'  => null,
            'uom'         => null,
            'min_qty'     => null,
            'amount'      => null,
        ];

        if ($showDestination && $showMinimumCharge) {
            $columnWidths = [
                'dest'        => '10%',
                'description' => '34%',
                'qty'         => '7%',
                'unit_price'  => '13%',
                'uom'         => '8%',
                'min_qty'     => '13%',
                'amount'      => '15%',
            ];
        } elseif ($showDestination) {
            $columnWidths = [
                'dest'        => '12%',
                'description' => '44%',
                'qty'         => '8%',
                'unit_price'  => '14%',
                'uom'         => '8%',
                'min_qty'     => null,
                'amount'      => '14%',
            ];
        } elseif ($showMinimumCharge) {
            $columnWidths = [
                'dest'        => null,
                'description' => '46%',
                'qty'         => '7%',
                'unit_price'  => '13%',
                'uom'         => '8%',
                'min_qty'     => '13%',
                'amount'      => '13%',
            ];
        } else {
            $columnWidths = [
                'dest'        => null,
                'description' => '56%',
                'qty'         => '8%',
                'unit_price'  => '14%',
                'uom'         => '8%',
                'min_qty'     => null,
                'amount'      => '14%',
            ];
        }

        $widths = array_values(array_filter([
            $columnWidths['dest'],
            $columnWidths['description'],
            $columnWidths['qty'],
            $columnWidths['unit_price'],
            $columnWidths['uom'],
            $columnWidths['min_qty'],
            $columnWidths['amount'],
        ], static function ($value) {
            return $value !== null;
        }));
        ?>
        <div class="section-title"><?= $h($title) ?></div>
        <table class="data-table">
          <colgroup>
            <?php foreach ($widths as $width): ?>
              <col style="width:<?= $h($width) ?>;">
            <?php endforeach; ?>
          </colgroup>
          <thead>
            <tr>
              <?php if ($showDestination): ?>
                <th style="width:<?= $h($columnWidths['dest']) ?>;">Dest.</th>
              <?php endif; ?>
              <th style="width:<?= $h($columnWidths['description']) ?>;">Charge Description</th>
              <th class="text-center" style="width:<?= $h($columnWidths['qty']) ?>;">Qty</th>
              <th class="text-right" style="width:<?= $h($columnWidths['unit_price']) ?>;">Unit Price</th>
              <th class="text-center" style="width:<?= $h($columnWidths['uom']) ?>;">UoM</th>
              <?php if ($showMinimumCharge): ?>
                <th class="text-right" style="width:<?= $h($columnWidths['min_qty']) ?>;">Min. Qty</th>
              <?php endif; ?>
              <th class="text-right" style="width:<?= $h($columnWidths['amount']) ?>;">Amount</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $row): ?>
              <?php
              if (!is_array($row)) {
                  continue;
              }

              $subItems = $decodeSubItems($row['sub_items'] ?? null);
              if (empty($row['description']) && empty($row['charge_code']) && !$subItems) {
                  continue;
              }

              $hasSubItems = !empty($subItems);
              $excludedFromTotal = isset($row['include_in_total'])
                  && (string)$row['include_in_total'] === '0';
              $uom = trim((string)($row['uom'] ?? ''));
              $displayDescription = $cleanChargeDescription($row['description'] ?? '');
              $displayDetail = $cleanChargeDetail($row['charge_detail'] ?? '');
              ?>
              <tr>
                <?php if ($showDestination): ?>
                  <td style="width:<?= $h($columnWidths['dest']) ?>;">
                    <?php if (!empty($row['dest_name'])): ?>
                      <div class="bold"><?= $h($row['dest_name']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($row['dest_code'])): ?>
                      <div class="info-sub"><?= $h($row['dest_code']) ?></div>
                    <?php endif; ?>
                  </td>
                <?php endif; ?>

                <td style="width:<?= $h($columnWidths['description']) ?>;">
                  <div class="bold">
                    <?php if (!empty($row['charge_code'])): ?>
                      <?= $h($row['charge_code']) ?> •
                    <?php endif; ?>
                    <?= $h($displayDescription) ?>
                  </div>
                  <?php if ($displayDetail !== ''): ?>
                    <div class="info-sub"><?= $multiline($displayDetail) ?></div>
                  <?php endif; ?>
                </td>

                <td class="text-center" style="width:<?= $h($columnWidths['qty']) ?>;">
                  <?= $hasSubItems ? '' : $h((float)($row['qty'] ?? 0)) ?>
                </td>
                <td class="text-right" style="width:<?= $h($columnWidths['unit_price']) ?>; white-space:nowrap;">
                  <?= $hasSubItems ? '' : $renderPricingValue($row, 'unit_price') ?>
                </td>
                <td class="text-center" style="width:<?= $h($columnWidths['uom']) ?>;">
                  <?= $hasSubItems ? '' : $h($uom) ?>
                </td>
                <?php if ($showMinimumCharge): ?>
                  <td class="text-right" style="width:<?= $h($columnWidths['min_qty']) ?>; white-space:nowrap;">
                    <?= $hasSubItems ? '' : (((float)($row['min_charge'] ?? 0)) > 0 ? $h((float)$row['min_charge']) : '') ?>
                  </td>
                <?php endif; ?>
                <td class="text-right bold" style="width:<?= $h($columnWidths['amount']) ?>; white-space:nowrap;">
                  <?= $hasSubItems ? '' : $renderPricingValue($row, 'amount') ?>
                </td>
              </tr>

              <?php foreach ($subItems as $subItem): ?>
                <?php
                if (!is_array($subItem)) {
                    continue;
                }

                $subExcluded = isset($subItem['include_in_total'])
                    ? (string)$subItem['include_in_total'] === '0'
                    : $excludedFromTotal;
                $subUom = trim((string)($subItem['uom'] ?? ''));
                $subDisplay = $subItem;
                if (!array_key_exists('include_in_total', $subDisplay)) {
                    $subDisplay['include_in_total'] = $subExcluded ? 0 : 1;
                }
                if (!array_key_exists('pricing_basis', $subDisplay)) {
                    $subDisplay['pricing_basis'] = $row['pricing_basis'] ?? 'FIXED';
                }
                ?>
                <tr style="background:#fcfcfd;">
                  <?php if ($showDestination): ?>
                    <td style="width:<?= $h($columnWidths['dest']) ?>;"></td>
                  <?php endif; ?>
                  <td style="width:<?= $h($columnWidths['description']) ?>; padding-left:14px; color:#475569;">
                    <span style="color:#cbd5e1;">—</span>
                    <?= $h($cleanChargeDescription($subItem['description'] ?? $subItem['label'] ?? '')) ?>
                  </td>
                  <td class="text-center" style="width:<?= $h($columnWidths['qty']) ?>;">
                    <?= $h((float)($subItem['qty'] ?? 0)) ?>
                  </td>
                  <td class="text-right" style="width:<?= $h($columnWidths['unit_price']) ?>; white-space:nowrap;">
                    <?= $renderPricingValue($subDisplay, 'unit_price') ?>
                  </td>
                  <td class="text-center" style="width:<?= $h($columnWidths['uom']) ?>;"><?= $h($subUom) ?></td>
                  <?php if ($showMinimumCharge): ?>
                    <td class="text-right" style="width:<?= $h($columnWidths['min_qty']) ?>; white-space:nowrap;">
                      <?= ((float)($subItem['min_charge'] ?? 0)) > 0 ? $h((float)$subItem['min_charge']) : '' ?>
                    </td>
                  <?php endif; ?>
                  <td class="text-right" style="width:<?= $h($columnWidths['amount']) ?>; white-space:nowrap;">
                    <?= $renderPricingValue($subDisplay, 'amount') ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php
    };

    $renderTable('Freight Charges', $groups['FREIGHT'], true, $showMinCharge);
    $renderTable('Origin Charges', $groups['ORIGIN'], false, $showMinCharge);
    $renderTable('Destination Charges', $groups['DESTINATION'], false, $showMinCharge);
    ?>

    <?php if ($isMixedCurrency): ?>
      <div class="footer-note" style="margin-top:12px;">
        <b>Multiple currencies apply.</b>
        A combined grand total is intentionally not shown.
        <?php if ($displaySubtotalByCurrency): ?>
          <br>
          <?php foreach ($displaySubtotalByCurrency as $curr => $value): ?>
            <?= $h($curr) ?> <?= $h($fmtCurrency($value, $curr)) ?>&nbsp;&nbsp;
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    <?php elseif ((int)($qt['show_total'] ?? 1) === 1): ?>
      <div class="summary-wrapper">
        <table class="summary-table">
          <?php if ($displaySubtotal['FREIGHT'] != 0.0): ?>
            <tr>
              <td style="color:#64748b;">Freight Subtotal</td>
              <td class="text-right"><?= $fmt($displaySubtotal['FREIGHT']) ?></td>
            </tr>
          <?php endif; ?>

          <?php if ($displaySubtotal['ORIGIN'] != 0.0): ?>
            <tr>
              <td style="color:#64748b;">Origin Subtotal</td>
              <td class="text-right"><?= $fmt($displaySubtotal['ORIGIN']) ?></td>
            </tr>
          <?php endif; ?>

          <?php if ($displaySubtotal['DESTINATION'] != 0.0): ?>
            <tr>
              <td style="color:#64748b;">Destination Subtotal</td>
              <td class="text-right"><?= $fmt($displaySubtotal['DESTINATION']) ?></td>
            </tr>
          <?php endif; ?>

          <tr class="total-row">
            <td>GRAND TOTAL (<?= $h($currency) ?>)</td>
            <td class="text-right"><?= $fmt($displaySubtotal['TOTAL']) ?></td>
          </tr>
        </table>
      </div>
    <?php endif; ?>

    <div class="footer-note">
      <b>Terms &amp; Conditions:</b><br>
      * Rates are subject to space availability and carrier terms at the time of booking.<br>
      * Charges marked AT COST, IF ANY, AS PER RECEIPT, AS AGREED, COLLECT, EXCLUDED, INFO, or RATE ON REQUEST are not included in the grand total.<br>
      <?php if (!empty($qt['created_by_name']) && trim((string)$qt['created_by_name']) !== '-'): ?>
        * Quotation prepared by: <?= $h($qt['created_by_name']) ?>.
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
