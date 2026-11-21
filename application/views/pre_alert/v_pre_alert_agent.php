<?php
$e = function($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
$default_agent = trim((string)$header->agen_penagihan);
if ($default_agent === '' && !empty($agents)) $default_agent = (string)$agents[0]->agent_company_code;
$missing_hbl = 0;
foreach ($details as $r) if (trim((string)$r->hb_l) === '') $missing_hbl++;
$release_mode = function($v) {
    $v = strtoupper(trim((string)$v));
    return in_array($v, array('TRUE', '1', 'YES', 'Y'), true) ? 'EXPRESS' : 'ORIGINAL';
};
?>
<style>
.prealert-wrap{font-family:Arial,sans-serif;font-size:12px;color:#263238;padding:12px}.prealert-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:8px 14px;background:#f7f9fc;border:1px solid #d8e0ea;padding:12px;margin-bottom:12px}.prealert-item b{display:block;font-size:10px;color:#607d8b;margin-bottom:2px}.prealert-table{width:100%;border-collapse:collapse}.prealert-table th{background:#00245e;color:#fff;font-size:11px;padding:7px;border:1px solid #d3d9e0}.prealert-table td{padding:6px;border:1px solid #d3d9e0;vertical-align:top}.prealert-warn{background:#fff4e5;border:1px solid #ffcc80;color:#8a4b00;padding:8px;margin:8px 0}.prealert-ok{background:#ecf8ef;border:1px solid #b7dfbf;color:#245c2f;padding:8px;margin:8px 0}.prealert-actions{text-align:right;margin-top:12px}.prealert-actions button{height:32px;padding:0 18px;margin-left:6px;cursor:pointer}.prealert-download{background:#00245e;color:#fff;border:1px solid #00245e}.prealert-close{background:#eee;border:1px solid #bbb}.prealert-textarea{width:99%;height:80px;resize:vertical;box-sizing:border-box;padding:8px}.doc-chip{display:inline-block;margin-right:8px;padding:4px 8px;border-radius:3px;background:#eef2f6}.doc-chip.warn{background:#fff0dd;color:#9a5400}
</style>

<div class="prealert-wrap">
    <form id="preAlertDownloadForm" method="post" target="_blank" action="<?= base_url('afg_c_pre_alert_agent/download') ?>">
        <input type="hidden" name="job_no" value="<?= $e($header->kode_ref) ?>">

        <div class="prealert-grid">
            <div class="prealert-item"><b>JOB NO.</b><?= $e($header->kode_ref) ?></div>
            <div class="prealert-item"><b>AGENT</b>
                <select name="agent_code" id="preAlertAgentCode" style="width:100%;height:26px;">
                    <?php foreach ($agents as $a): ?>
                        <option value="<?= $e($a->agent_company_code) ?>" <?= $a->agent_company_code == $default_agent ? 'selected' : '' ?>>
                            <?= $e($a->agent_company_code) ?><?= trim((string)$a->agent_company_name) !== '' ? ' - '.$e($a->agent_company_name) : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="prealert-item"><b>MASTER BL</b><?= $e($header->mother_bl) ?></div>
            <div class="prealert-item"><b>TOTAL POS</b><?= count($details) ?></div>
            <div class="prealert-item"><b>VESSEL / VOYAGE</b><?= $e($header->vessel) ?> / <?= $e($header->voyage) ?></div>
            <div class="prealert-item"><b>POL</b><?= $e($header->port_loading) ?></div>
            <div class="prealert-item"><b>POD</b><?= $e($header->destination) ?></div>
            <div class="prealert-item"><b>ETD / ETA</b><?= $e($header->etd_jkt) ?> / <?= $e($header->eta) ?></div>
            <div class="prealert-item"><b>CONTAINER / SEAL</b><?= $e($header->cont_seal_no) ?></div>
            <div class="prealert-item"><b>CONTAINER TYPE</b><?= $e($header->type_cont_qty) ?> <?= $e($header->type_cont_size) ?></div>
        </div>

        <?php if ($missing_hbl > 0): ?>
            <div class="prealert-warn"><b><?= $missing_hbl ?> position belum memiliki House BL Number.</b> Download akan ditolak sampai seluruh HBL lengkap agar Pre Alert tidak terkirim parsial.</div>
        <?php endif; ?>

        <table class="prealert-table">
            <thead><tr><th>POS</th><th>DESTINATION</th><th>HOUSE BL</th><th>HS CODE</th><th>PARTY</th><th>SHIPPER</th><th>TERM</th><th>RELEASE MODE</th></tr></thead>
            <tbody>
            <?php foreach ($details as $r): ?>
                <tr>
                    <td style="text-align:center"><?= $e($r->num) ?></td>
                    <td><?= $e($r->destination) ?></td>
                    <td><?= trim((string)$r->hb_l) !== '' ? $e($r->hb_l) : '<b style="color:#b71c1c">MISSING</b>' ?></td>
                    <td><?= $e($r->hs_code) ?></td>
                    <td><?= $e($r->no_pack_qty) ?> <?= $e($r->no_pack_unit) ?></td>
                    <td><?= $e($r->shipper_print) ?></td>
                    <td><?= $e($r->incoterms) ?></td>
                    <td style="text-align:center"><?= $e($release_mode($r->express_release)) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <div style="margin-top:12px"><b>DOCUMENTS FOR SELECTED AGENT</b></div>
        <div id="preAlertDocumentSummary" style="margin-top:5px">Loading finance documents...</div>

        <div style="margin-top:12px"><b>INSTRUCTION / MESSAGE TO AGENT</b></div>
        <textarea class="prealert-textarea" name="instruction" id="preAlertInstruction" maxlength="5000" placeholder="Example: DG cargo, DDU handling, destination instruction, document release instruction..."></textarea>

        <div class="prealert-actions">
            <button type="button" class="prealert-close" onclick="$('#pre_alert_agent').dialog('close')">CLOSE</button>
            <button type="submit" class="prealert-download" id="btnDownloadPreAlert" <?= ($missing_hbl > 0 || empty($agents)) ? 'disabled' : '' ?>>DOWNLOAD PRE ALERT</button>
        </div>
    </form>
</div>

<script>
(function(){
    function refreshPreAlertFinanceSummary() {
        var job = $('#preAlertDownloadForm input[name="job_no"]').val();
        var agent = $('#preAlertAgentCode').val();
        if (!agent) {
            $('#preAlertDocumentSummary').html('<div class="prealert-warn">Agent belum dipilih.</div>');
            return;
        }
        $.ajax({
            type: 'POST',
            url: base_url + '/afg_c_pre_alert_agent/document_summary',
            dataType: 'json',
            data: { job_no: job, agent_code: agent },
            success: function(res) {
                if (!res.status) {
                    $('#preAlertDocumentSummary').html('<div class="prealert-warn">'+(res.message || 'Unable to load finance documents')+'</div>');
                    return;
                }
                var html = '<span class="doc-chip">House BL: '+res.hbl_count+'</span>' +
                           '<span class="doc-chip">Debit Note: '+res.dn_count+'</span>' +
                           '<span class="doc-chip">Credit Note: '+res.cn_count+'</span>' +
                           '<div class="prealert-warn">⚠️ Mohon cek kembali data pre-alert dengan teliti sebelum dikirim ke agent. Pastikan DN sudah di posting ke finance</div>';
                $('#preAlertDocumentSummary').html(html);
            },
            error: function(xhr) {
                $('#preAlertDocumentSummary').html('<div class="prealert-warn">Failed to load finance summary.</div>');
            }
        });
    }

    $('#preAlertAgentCode').off('change.prealert').on('change.prealert', refreshPreAlertFinanceSummary);
    refreshPreAlertFinanceSummary();
})();
</script>
