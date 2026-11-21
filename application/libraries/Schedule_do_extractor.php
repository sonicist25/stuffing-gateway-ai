<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Extractor DO / Booking Confirmation pelayaran.
 * Flow:
 * 1) PDF text-based: pdftotext -layout
 * 2) PDF scan/image: pdftoppm -> tesseract per page
 * 3) image: tesseract directly
 *
 * Return fields are normalized for Schedule Direct form.
 */
class Schedule_do_extractor
{
   public function extract($filePath, $originalName = '', $withDebug = false)
	{
		$debug = array();
		$rawText = $this->extract_text($filePath, $debug);
		$fields  = $this->parse_fields($rawText, $originalName);
		
		// Apply parser khusus per pelayaran.
		if (method_exists($this, 'apply_cma_booking_confirmation')) {
			$this->apply_cma_booking_confirmation($fields, $rawText);
		}

		if (method_exists($this, 'apply_samudera_booking_confirmation')) {
			$this->apply_samudera_booking_confirmation($fields, $rawText);
		}
		$this->apply_yangming_booking_confirmation($fields, $rawText);
		$this->apply_kmtc_booking_notice($fields, $rawText);

		// WAJIB paling akhir: pisahkan type dan qty.
		$this->finalize_container_fields($fields);

		$required = array('vessel', 'voy', 'etd_jkt', 'eta', 'stf_cls');
		$missing  = array();

		foreach ($required as $field) {
			if (!isset($fields[$field]) || trim((string) $fields[$field]) === '') {
				$missing[] = $field;
			}
		}

		if (empty($fields['stuffing_date'])) {
			$missing[] = 'stuffing_date';
		}

		$response = array(
			'raw_text'       => $rawText,
			'fields'         => $fields,
			'missing_fields' => $missing,
		);

		if ($withDebug) {
			$response['debug'] = $debug;
		}

		return $response;
	}
	
	private function finalize_container_fields(&$fields)
	{
		if (!isset($fields['container_type'])) {
			$fields['container_type'] = '';
		}

		if (!isset($fields['container_qty'])) {
			$fields['container_qty'] = '';
		}

		if (!isset($fields['container_type_raw'])) {
			$fields['container_type_raw'] = '';
		}

		$type = strtoupper(trim((string) $fields['container_type']));
		$raw  = strtoupper(trim((string) $fields['container_type_raw']));

		// Kalau container_type masih "40 HC x1", pisahkan jadi type=40 HC, qty=1.
		if ($type !== '') {
			if (preg_match('/^(.*?)\s*x\s*(\d+)$/i', $type, $m)) {
				$type = trim($m[1]);

				if (empty($fields['container_qty'])) {
					$fields['container_qty'] = (string) ((int) $m[2]);
				}
			}

			if (preg_match('/^(.*?)-\s*(\d+)$/i', $type, $m)) {
				$type = trim($m[1]);

				if (empty($fields['container_qty'])) {
					$fields['container_qty'] = (string) ((int) $m[2]);
				}
			}

			$fields['container_type'] = $this->normalize_container_type_label($type);
		}

		// Kalau qty masih kosong, ambil dari raw: 40Hx1 / 40 HC X 1 / 1 X H40.
		if (empty($fields['container_qty']) && $raw !== '') {
			if (preg_match('/(?:x|-)\s*(\d+)\b/i', $raw, $m)) {
				$fields['container_qty'] = (string) ((int) $m[1]);
			} elseif (preg_match('/^\s*(\d+)\s*x/i', $raw, $m)) {
				$fields['container_qty'] = (string) ((int) $m[1]);
			}
		}

		// Kalau type kosong tapi raw ada, parse ulang.
		if (empty($fields['container_type']) && $raw !== '') {
			$parsed = $this->normalize_container($raw);
			$fields['container_type'] = $parsed['type'];

			if (empty($fields['container_qty'])) {
				$fields['container_qty'] = $parsed['qty'];
			}
		}

		// Final safety: jangan pernah ada "x1" di container_type.
		$fields['container_type'] = preg_replace('/\s*x\s*\d+\s*$/i', '', (string) $fields['container_type']);
		$fields['container_type'] = preg_replace('/\s*-\s*\d+\s*$/i', '', (string) $fields['container_type']);
		$fields['container_type'] = $this->normalize_container_type_label($fields['container_type']);
	}

    public function extract_text($filePath, &$debug = null)
    {
        if (!is_array($debug)) {
            $debug = array();
        }

        $debug['file_path'] = $filePath;
        $debug['file_exists'] = is_file($filePath) ? 1 : 0;
        $debug['is_readable'] = is_readable($filePath) ? 1 : 0;
        $debug['file_size'] = is_file($filePath) ? filesize($filePath) : 0;
        $debug['disabled_functions'] = (string) ini_get('disable_functions');
        $debug['shell_exec_exists'] = function_exists('shell_exec') ? 1 : 0;
        $debug['exec_exists'] = function_exists('exec') ? 1 : 0;
        $debug['proc_open_exists'] = function_exists('proc_open') ? 1 : 0;

        if (!is_file($filePath)) {
            $debug['reason'] = 'file_not_found';
            return '';
        }

        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $debug['extension'] = $ext;
        $text = '';

        if ($ext === 'pdf') {
            $text = $this->extract_pdf_text($filePath, $debug);
            $debug['pdftotext_text_length'] = strlen(trim($text));

            // If PDF is scanned or pdftotext failed, fallback to image OCR.
            if (strlen(trim($text)) < 20) {
                $text = $this->ocr_pdf($filePath, $debug);
                $debug['ocr_pdf_text_length'] = strlen(trim($text));
            }
        } else {
            $text = $this->ocr_image($filePath, $debug);
            $debug['ocr_image_text_length'] = strlen(trim($text));
        }

        $text = $this->clean_text($text);
        $debug['final_text_length'] = strlen(trim($text));
        $debug['final_text_sample'] = substr($text, 0, 300);

        return $text;
    }

    private function extract_pdf_text($filePath, &$debug)
    {
        $pdftotext = $this->find_binary('pdftotext');
        $debug['pdftotext_path'] = $pdftotext;
        if ($pdftotext === '') {
            $debug['pdftotext_error'] = 'pdftotext_not_found';
            return '';
        }

        $tmpFile = tempnam(sys_get_temp_dir(), 'schedule_do_txt_');
        if (!$tmpFile) {
            $debug['pdftotext_error'] = 'cannot_create_temp_file';
            return '';
        }

        // Output-to-file is more reliable than stdout on some shared/old servers.
        $cmd = escapeshellarg($pdftotext) . ' -layout -enc UTF-8 ' . escapeshellarg($filePath) . ' ' . escapeshellarg($tmpFile);
        $result = $this->run_command($cmd);
        $debug['pdftotext_cmd'] = $this->mask_path($cmd);
        $debug['pdftotext_exit_code'] = $result['exit_code'];
        $debug['pdftotext_stderr'] = substr($result['stderr'], 0, 500);

        $text = is_file($tmpFile) ? (string) @file_get_contents($tmpFile) : '';
        @unlink($tmpFile);

        // Some old poppler builds are picky about -enc. Retry without -enc.
        if (strlen(trim($text)) < 20) {
            $tmpFile2 = tempnam(sys_get_temp_dir(), 'schedule_do_txt_');
            $cmd2 = escapeshellarg($pdftotext) . ' -layout ' . escapeshellarg($filePath) . ' ' . escapeshellarg($tmpFile2);
            $result2 = $this->run_command($cmd2);
            $debug['pdftotext_retry_cmd'] = $this->mask_path($cmd2);
            $debug['pdftotext_retry_exit_code'] = $result2['exit_code'];
            $debug['pdftotext_retry_stderr'] = substr($result2['stderr'], 0, 500);
            $text = is_file($tmpFile2) ? (string) @file_get_contents($tmpFile2) : '';
            @unlink($tmpFile2);
        }

        return $text;
    }

    private function ocr_pdf($filePath, &$debug)
    {
        $pdftoppm = $this->find_binary('pdftoppm');
        $tesseract = $this->find_binary('tesseract');
        $debug['pdftoppm_path'] = $pdftoppm;
        $debug['tesseract_path'] = $tesseract;

        if ($pdftoppm === '' || $tesseract === '') {
            $debug['ocr_pdf_error'] = 'pdftoppm_or_tesseract_not_found';
            return '';
        }

        $tmpDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'schedule_do_ocr_' . uniqid('', true);
        if (!@mkdir($tmpDir, 0775, true)) {
            $debug['ocr_pdf_error'] = 'cannot_create_temp_dir';
            return '';
        }

        $prefix = $tmpDir . DIRECTORY_SEPARATOR . 'page';
        $cmd = escapeshellarg($pdftoppm) . ' -r 300 -png ' . escapeshellarg($filePath) . ' ' . escapeshellarg($prefix);
        $result = $this->run_command($cmd);
        $debug['pdftoppm_cmd'] = $this->mask_path($cmd);
        $debug['pdftoppm_exit_code'] = $result['exit_code'];
        $debug['pdftoppm_stderr'] = substr($result['stderr'], 0, 500);

        $pages = glob($tmpDir . DIRECTORY_SEPARATOR . 'page-*.png');
        sort($pages);
        $debug['ocr_pages'] = count($pages);

        $text = '';
        foreach ($pages as $page) {
            $text .= "\n" . $this->ocr_image($page, $debug);
        }

        $this->remove_dir($tmpDir);
        return $text;
    }

    private function ocr_image($filePath, &$debug = null)
    {
        if (!is_array($debug)) {
            $debug = array();
        }

        $tesseract = $this->find_binary('tesseract');
        $debug['tesseract_path'] = $tesseract;
        if ($tesseract === '') {
            $debug['ocr_image_error'] = 'tesseract_not_found';
            return '';
        }

        // psm 6 is good for form/table-like DO; fallback psm 4 if result is too short.
        $cmd = escapeshellarg($tesseract) . ' ' . escapeshellarg($filePath) . ' stdout -l eng --psm 6';
        $result = $this->run_command($cmd);
        $text = (string) $result['stdout'];
        $debug['tesseract_exit_code'] = $result['exit_code'];
        $debug['tesseract_stderr'] = substr($result['stderr'], 0, 500);

        if (strlen(trim($text)) < 20) {
            $cmd2 = escapeshellarg($tesseract) . ' ' . escapeshellarg($filePath) . ' stdout -l eng --psm 4';
            $result2 = $this->run_command($cmd2);
            $text = (string) $result2['stdout'];
            $debug['tesseract_retry_exit_code'] = $result2['exit_code'];
            $debug['tesseract_retry_stderr'] = substr($result2['stderr'], 0, 500);
        }

        return $text;
    }

    private function find_binary($command)
    {
        $command = preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)$command);
        if ($command === '') return '';

        $isWindows = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
        $exeName   = $isWindows ? $command . '.exe' : $command;

        $candidates = array();

        // 1) Allow explicit config override (recommended for XAMPP/Windows).
        // CI3 note:
        // - $this->config->load('schedule_do', FALSE)  => global item: schedule_do_pdftotext_path
        // - $this->config->load('schedule_do', TRUE)   => grouped item: schedule_do[schedule_do_pdftotext_path]
        // This supports both, because many projects load config with TRUE by habit.
        if (function_exists('get_instance')) {
            $CI =& get_instance();
            if (isset($CI->config)) {
                $key = 'schedule_do_' . $command . '_path';

                // Global config, e.g. application/config/config.php or load(..., FALSE).
                $cfg = (string) $CI->config->item($key);
                if ($cfg !== '') {
                    $candidates[] = $cfg;
                }

                // Sectioned config, e.g. $this->config->load('schedule_do', TRUE).
                $cfg2 = (string) $CI->config->item($key, 'schedule_do');
                if ($cfg2 !== '') {
                    $candidates[] = $cfg2;
                }

                // Fallback if config section returned as array.
                $section = $CI->config->item('schedule_do');
                if (is_array($section) && !empty($section[$key])) {
                    $candidates[] = (string) $section[$key];
                }
            }
        }

        // 2) Common Linux/macOS paths.
        $candidates[] = '/usr/bin/' . $command;
        $candidates[] = '/usr/local/bin/' . $command;
        $candidates[] = '/bin/' . $command;
        $candidates[] = '/opt/homebrew/bin/' . $command;

        // 3) Common Windows paths.
        if ($isWindows) {
            $programFiles = getenv('ProgramFiles') ?: 'C:\\Program Files';
            $programFilesX86 = getenv('ProgramFiles(x86)') ?: 'C:\\Program Files (x86)';

            if ($command === 'tesseract') {
                $candidates[] = $programFiles . '\\Tesseract-OCR\\tesseract.exe';
                $candidates[] = $programFilesX86 . '\\Tesseract-OCR\\tesseract.exe';
                $candidates[] = 'C:\\Tesseract-OCR\\tesseract.exe';
            } else {
                // Poppler for Windows usually puts pdftotext/pdftoppm in Library\bin.
                $candidates[] = $programFiles . '\\poppler\\Library\\bin\\' . $exeName;
                $candidates[] = $programFilesX86 . '\\poppler\\Library\\bin\\' . $exeName;
                $candidates[] = 'C:\\poppler\\Library\\bin\\' . $exeName;
                $candidates[] = 'C:\\poppler\\bin\\' . $exeName;
                $candidates[] = 'C:\\tools\\poppler\\Library\\bin\\' . $exeName;
                // Common GitHub release layout used by user:
                // C:\tools\poppler\bin\poppler-26.02.0\Library\bin\pdftotext.exe
                $candidates[] = 'C:\\tools\\poppler\\bin\\poppler-26.02.0\\Library\\bin\\' . $exeName;
                $candidates[] = 'C:\\tools\\poppler\\bin\\poppler-25.12.0\\Library\\bin\\' . $exeName;
                $candidates[] = 'C:\\xampp\\poppler\\Library\\bin\\' . $exeName;
            }
        }

        // 4) Search PATH manually. Useful after adding Poppler/Tesseract to System PATH.
        $pathEnv = (string) getenv('PATH');
        $separator = $isWindows ? ';' : ':';
        foreach (explode($separator, $pathEnv) as $dir) {
            $dir = trim($dir, " \t\n\r\0\x0B\"");
            if ($dir === '') continue;
            $candidates[] = rtrim($dir, DIRECTORY_SEPARATOR . '/\\') . DIRECTORY_SEPARATOR . $exeName;
            if (!$isWindows) {
                $candidates[] = rtrim($dir, DIRECTORY_SEPARATOR . '/\\') . DIRECTORY_SEPARATOR . $command;
            }
        }

        foreach ($candidates as $path) {
            $path = trim((string)$path);
            if ($path === '') continue;
            // On Windows is_executable may be unreliable with .exe in some PHP builds, so use is_file.
            if (is_file($path) && ($isWindows || is_executable($path))) {
                return $path;
            }
        }

        // 5) Shell lookup fallback.
        if (function_exists('exec')) {
            $out = array();
            $ret = 1;
            if ($isWindows) {
                @exec('where ' . escapeshellarg($exeName) . ' 2>NUL', $out, $ret);
            } else {
                @exec('command -v ' . escapeshellarg($command) . ' 2>/dev/null', $out, $ret);
            }
            if ($ret === 0 && !empty($out[0])) {
                $found = trim($out[0], " \t\n\r\0\x0B\"");
                if ($found !== '' && is_file($found)) return $found;
                if ($found !== '') return $found;
            }
        }

        return '';
    }

    private function run_command($cmd)
    {
        $response = array('stdout' => '', 'stderr' => '', 'exit_code' => null);
        $isWindows = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');

        if (function_exists('proc_open')) {
            $descriptors = array(
                0 => array('pipe', 'r'),
                1 => array('pipe', 'w'),
                2 => array('pipe', 'w'),
            );
            $pipes = array();

            // Do not override PATH on Windows/XAMPP; inherit environment so DLLs and installed tools resolve correctly.
            $env = $isWindows ? null : array('PATH' => '/usr/local/bin:/usr/bin:/bin:' . (string)getenv('PATH'));
            $process = @proc_open($cmd, $descriptors, $pipes, null, $env);
            if (is_resource($process)) {
                @fclose($pipes[0]);
                $response['stdout'] = stream_get_contents($pipes[1]);
                $response['stderr'] = stream_get_contents($pipes[2]);
                @fclose($pipes[1]);
                @fclose($pipes[2]);
                $response['exit_code'] = proc_close($process);
                return $response;
            }
        }

        if (function_exists('shell_exec')) {
            $output = @shell_exec($cmd . ' 2>&1');
            $response['stdout'] = (string)$output;
            $response['stderr'] = '';
            $response['exit_code'] = null;
            return $response;
        }

        $response['stderr'] = 'proc_open_and_shell_exec_not_available';
        return $response;
    }

    private function mask_path($cmd)
    {
        // Keep command readable but avoid exposing full private server path in UI logs.
        return preg_replace('#/[^\s\']+/uploads/schedule_do/#', '.../uploads/schedule_do/', (string)$cmd);
    }

    private function clean_text($text)
    {
        $text = (string) $text;
        $text = str_replace("\r", "\n", $text);
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        return trim($text);
    }

    private function one_line($text)
    {
        $text = preg_replace('/[ \t]+/', ' ', (string) $text);
        $text = preg_replace('/\s*\n\s*/', ' ', $text);
        return trim($text);
    }
	
	private function apply_samudera_booking_confirmation(&$fields, $text)
{
    $flat = $this->one_line($this->clean_text($text));
    $hay  = strtoupper($flat);

    if (strpos($hay, 'SAMUDERA') === false && strpos($hay, 'SUDU') === false) {
        return;
    }

    // Booking number Samudera
    if (empty($fields['booking_number'])) {
        if (preg_match('/BOOKING\s+CONFIRMATION\s*:\s*(SUDU[A-Z0-9]+)/i', $flat, $m)) {
            $fields['booking_number'] = strtoupper(trim($m[1]));
        }
    }

    // QUANTITY : 1 X H40'
    // QUANTITY : 1 X 40HC
    // QUANTITY : 1 X 40'HC
    if (
        empty($fields['container_type']) ||
        empty($fields['container_qty']) ||
        empty($fields['container_type_raw'])
    ) {
        if (preg_match("/QUANTITY\s*:?\s*(\d+)\s*[xX]\s*(H?\s*(?:20|40)|(?:20|40)\s*'?\s*(?:HC|HQ|H|GP|DC|DRY|FT)?)/i", $flat, $m)) {
            $qty = (int) $m[1];
            if ($qty <= 0) {
                $qty = 1;
            }

            $rawType = trim($m[2]);
            $rawType = str_replace(' ', '', $rawType);

            $fields['container_type_raw'] = trim($m[1] . ' X ' . $m[2]);
            $fields['container_type']     = $this->_normalize_samudera_container_type($rawType);
            $fields['container_qty']      = (string) $qty;
        }
    }

    // Fallback kalau text OCR hanya menangkap "1 X H40'" tanpa label QUANTITY.
    if (empty($fields['container_type'])) {
        if (preg_match("/\b(\d+)\s*[xX]\s*(H\s*(?:20|40)|(?:20|40)\s*H|(?:20|40)\s*HC|(?:20|40)\s*HQ|(?:20|40))\s*'?/i", $flat, $m)) {
            $qty = (int) $m[1];
            if ($qty <= 0) {
                $qty = 1;
            }

            $fields['container_type_raw'] = trim($m[0]);
            $fields['container_type']     = $this->_normalize_samudera_container_type($m[2]);
            $fields['container_qty']      = (string) $qty;
        }
    }

    // Samudera: jika ada DG DETAILS / BOOKING REMARKS DG, set DG.
    if (stripos($flat, 'DG DETAILS') !== false || stripos($flat, 'DG CARGO') !== false) {
        $fields['is_dg']     = '1';
        $fields['cargo_type'] = 'DG';
    }

    if (empty($fields['liner_id'])) {
        $fields['liner_id'] = 'SUDU';
    }
}

private function parse_cosco_hicube_container($flat)
{
    $flat = preg_replace('/\s+/', ' ', (string) $flat);

    // COSCO:
    // BOOKING QTY SIZE/TYPE: 1 X 40' Hi-Cube Container
    if (preg_match("/BOOKING\s+QTY\s+SIZE\s*\/\s*TYPE\s*:?\s*(\d+)\s*[xX]\s*(20|40)\s*'?\s*(Hi[\-\s]?Cube|High[\-\s]?Cube|HC|HQ|H|GP|DC|DRY|FT)?\s*(?:Container)?/i", $flat, $m)) {
        $qty  = (int) $m[1];
        $size = $m[2];
        $type = isset($m[3]) ? $m[3] : '';

        return array(
            'raw'  => trim($m[0]),
            'type' => $this->normalize_container_type_label($size . ' ' . $type),
            'qty'  => (string) ($qty > 0 ? $qty : 1),
        );
    }

    // COSCO:
    // TOTAL BOOKING CONTAINER 1 X 40' Hi-Cube Container
    if (preg_match("/TOTAL\s+BOOKING\s+CONTAINER\s+(\d+)\s*[xX]\s*(20|40)\s*'?\s*(Hi[\-\s]?Cube|High[\-\s]?Cube|HC|HQ|H|GP|DC|DRY|FT)?\s*(?:Container)?/i", $flat, $m)) {
        $qty  = (int) $m[1];
        $size = $m[2];
        $type = isset($m[3]) ? $m[3] : '';

        return array(
            'raw'  => trim($m[0]),
            'type' => $this->normalize_container_type_label($size . ' ' . $type),
            'qty'  => (string) ($qty > 0 ? $qty : 1),
        );
    }

    // COSCO split layout:
    // TOTAL BOOKING CONTAINER 1 X 40' Hi-Cube Container
    // QTY SIZE/TYPE:
    if (preg_match("/(?:QTY\s+SIZE\s*\/\s*TYPE\s*:?\s*)?(\d+)\s*[xX]\s*(20|40)\s*'?\s*(Hi[\-\s]?Cube|High[\-\s]?Cube|HC|HQ|H|GP|DC|DRY|FT)\s*(?:Container)?/i", $flat, $m)) {
        $qty  = (int) $m[1];
        $size = $m[2];
        $type = isset($m[3]) ? $m[3] : '';

        return array(
            'raw'  => trim($m[0]),
            'type' => $this->normalize_container_type_label($size . ' ' . $type),
            'qty'  => (string) ($qty > 0 ? $qty : 1),
        );
    }

    return null;
}

private function _normalize_samudera_container_type($value)
	{
		$v = strtoupper(trim((string) $value));
		$v = str_replace(array("'", '"', '.', '-', '_'), ' ', $v);
		$v = preg_replace('/\s+/', ' ', $v);
		$v = trim($v);

		// H40 / H 40 / 40H / 40 H / 40HC / 40HQ => 40 HC
		if (
			preg_match('/^H\s*40$/i', $v) ||
			preg_match('/^40\s*H$/i', $v) ||
			preg_match('/^40\s*(HC|HQ|HIGH CUBE|HI CUBE)$/i', $v)
		) {
			return '40 HC';
		}

		// H20 jarang, tapi kalau ada treat sebagai 20 FT kecuali nanti mau bedakan 20 HC.
		if (
			preg_match('/^H\s*20$/i', $v) ||
			preg_match('/^20\s*H$/i', $v) ||
			preg_match('/^20\s*(HC|HQ)$/i', $v)
		) {
			return '20 FT';
		}

		if (preg_match('/^40\b/i', $v)) {
			return '40 FT';
		}

		if (preg_match('/^20\b/i', $v)) {
			return '20 FT';
		}

		return $v;
	}
	
	private function apply_cma_booking_confirmation(&$fields, $text)
{
    $flat = $this->one_line($this->clean_text($text));
    $hay  = strtoupper($flat);

    if (
        strpos($hay, 'CMA CGM') === false &&
        strpos($hay, 'CNC') === false &&
        strpos($hay, 'CONTAINER MARITIME ACTIVITIES') === false &&
        strpos($hay, 'ARM') === false
    ) {
        return;
    }

    // Booking Number / Booking Ref
    if (
        empty($fields['booking_number']) ||
        !preg_match('/^ARM[0-9]+$/i', $fields['booking_number'])
    ) {
        if (preg_match('/Booking\s+reference\s*:?\s*(ARM[0-9]+)/i', $flat, $m)) {
            $fields['booking_number'] = strtoupper(trim($m[1]));
        } elseif (preg_match('/Booking\s+Number\s*:?\s*(ARM[0-9]+)/i', $flat, $m)) {
            $fields['booking_number'] = strtoupper(trim($m[1]));
        } elseif (preg_match('/Booking\s+Ref\.?\s*:?\s*(ARM[0-9]+)/i', $flat, $m)) {
            $fields['booking_number'] = strtoupper(trim($m[1]));
        }
    }

    // Vessel/Voyage detail page:
    // Vessel/Voyage: CMA CGM POINTE DU PITON / 0XLBUN1NC / 0XLBUN
    if (preg_match('/Vessel\s*\/\s*Voyage\s*:?\s*(.+?)\s*\/\s*([A-Z0-9]+)(?:\s*\/\s*[A-Z0-9]+)?(?:\s+Receipt\s*:|\s+Connecting\s+Vessel|\s+Forwarder\s*:|\s+Shipper\s*:|\s+ETD\s*:|$)/i', $flat, $m)) {
        $fields['vessel_voy_raw'] = trim($m[1] . ' / ' . $m[2]);
        $fields['vessel'] = $this->clean_vessel_name_keep_space($m[1]);
        $fields['voy']    = strtoupper(trim($m[2]));
    }

    // Fallback page 1:
    // Voyage 0XLBUN1NC Vessel CMA CGM POINTE DU PITON
    if (empty($fields['vessel']) || empty($fields['voy'])) {
        if (preg_match('/Voyage\s+([A-Z0-9]+)\s+Vessel\s+(.+?)\s+(?:[0-9]{1,2}\-[A-Za-z]{3}\-[0-9]{2}|POL\s+|Booking\s+Ref\.?)/i', $flat, $m)) {
            $fields['voy'] = strtoupper(trim($m[1]));
            $fields['vessel'] = $this->clean_vessel_name_keep_space($m[2]);
            $fields['vessel_voy_raw'] = $fields['vessel'] . ' / ' . $fields['voy'];
        }
    }

    // POL
    if (empty($fields['pol'])) {
        if (preg_match('/Port\s+Of\s+Loading\s*:?\s*([A-Z ]+?)(?:\s+SI\s+Cut-Off|\s+Alternate\s+Base|\s+Loading\s+Terminal|$)/i', $flat, $m)) {
            $fields['pol'] = $this->clean_location_name($m[1]);
        } elseif (preg_match('/\bPOL\s+([A-Z ]+?)\s+(?:[0-9]{1,2}\-[A-Za-z]{3}\-[0-9]{2}|Transhipment|POD\b)/i', $flat, $m)) {
            $fields['pol'] = $this->clean_location_name($m[1]);
        }
    }

    // POD / Final destination.
    if (empty($fields['pod'])) {
        if (preg_match('/Port\s+Of\s+Discharge\s*:?.*?\b(PHMNN\s*\([^)]+\)|MANILA(?:\s+NORTH\s+HARBOUR)?)/i', $flat, $m)) {
            $fields['pod'] = $this->clean_location_name($m[1]);
        } elseif (preg_match('/\bPOD\s+([A-Z ]+?)\s+(?:Booking\s+Ref|$)/i', $flat, $m)) {
            $fields['pod'] = $this->clean_location_name($m[1]);
        }
    }

    if (empty($fields['final_destination']) && !empty($fields['pod'])) {
        $fields['final_destination'] = $fields['pod'];
    }

    // ETD: 15-Jun-2026 21:00
    if (empty($fields['etd_jkt'])) {
        if (preg_match('/\bETD\s*:?\s*([0-9]{1,2}\-[A-Za-z]{3}\-[0-9]{4}(?:\s+[0-9]{1,2}:[0-9]{2})?)/i', $flat, $m)) {
            $fields['etd_jkt'] = $this->normalize_date($m[1]);
        } elseif (preg_match('/\b([0-9]{1,2}\-[A-Za-z]{3}\-[0-9]{2})\s+POL\s+JAKARTA/i', $flat, $m)) {
            $fields['etd_jkt'] = $this->normalize_date($m[1]);
        }
    }

    // ETA: untuk format ini ambil tanggal POD / Transhipment MANILA.
    // Page 1: 22-Jun-26 POD MANILA
    // Page 2: Transhipment: 22-Jun-2026 13:30 MANILA
    if (empty($fields['eta'])) {
        if (preg_match('/Transhipment\s*:?\s*([0-9]{1,2}\-[A-Za-z]{3}\-[0-9]{4}(?:\s+[0-9]{1,2}:[0-9]{2})?)\s+MANILA/i', $flat, $m)) {
            $fields['eta'] = $this->normalize_date($m[1]);
        } elseif (preg_match('/\b([0-9]{1,2}\-[A-Za-z]{3}\-[0-9]{2})\s+POD\s+MANILA/i', $flat, $m)) {
            $fields['eta'] = $this->normalize_date($m[1]);
        } elseif (preg_match('/\b([0-9]{1,2}\-[A-Za-z]{3}\-[0-9]{2})\s+MANILA\s+Booking\s+Ref/i', $flat, $m)) {
            $fields['eta'] = $this->normalize_date($m[1]);
        }
    }

    // Earliest Receiving = pakai sebagai stuffing_date/open stack.
    if (empty($fields['open_stack_at'])) {
        if (preg_match('/Earliest\s+Receiving\s+([0-9]{1,2}\-[A-Za-z]{3}\-[0-9]{4}\s+[0-9]{1,2}:[0-9]{2}\s*(?:AM|PM)?)/i', $flat, $m)) {
            $fields['open_stack_at'] = $this->normalize_datetime($m[1]);
        }
    }

    if (empty($fields['stuffing_date']) && !empty($fields['open_stack_at'])) {
        $fields['stuffing_date'] = substr($fields['open_stack_at'], 0, 10);
    }

    // Port Cut-Off Date/Time: 14-Jun-2026 11:00
    if (empty($fields['stf_cls'])) {
        if (preg_match('/Port\s+Cut-Off\s+Date\/Time\s*:?\s*([0-9]{1,2}\-[A-Za-z]{3}\-[0-9]{4}\s+[0-9]{1,2}:[0-9]{2})/i', $flat, $m)) {
            $fields['stf_cls'] = $this->normalize_date($m[1]);
            $fields['cargo_closing_at'] = $this->normalize_datetime($m[1]);
        }
    }

    // SI Cut-Off
    if (empty($fields['si_cutoff_at'])) {
        if (preg_match('/SI\s+Cut-Off\s+Date\/Time\s*:?\s*([0-9]{1,2}\-[A-Za-z]{3}\-[0-9]{4}\s+[0-9]{1,2}:[0-9]{2})/i', $flat, $m)) {
            $fields['si_cutoff_at'] = $this->normalize_datetime($m[1]);
        }
    }

    // VGM Cut-Off
    if (empty($fields['vgm_cutoff_at'])) {
        if (preg_match('/VGM\s+Cut-Off\s+Date\/Time\s*:?\s*([0-9]{1,2}\-[A-Za-z]{3}\-[0-9]{4}\s+[0-9]{1,2}:[0-9]{2})/i', $flat, $m)) {
            $fields['vgm_cutoff_at'] = $this->normalize_datetime($m[1]);
        }
    }

    // Loading Terminal
    if (empty($fields['terminal'])) {
        if (preg_match('/Loading\s+Terminal\s*:?\s*(.+?)\s+VGM\s+Cut-Off/i', $flat, $m)) {
            $fields['terminal'] = $this->clean_location_name($m[1]);
        }
    }

    // Quantity: 1 x 40'HC
    if (
        empty($fields['container_type']) ||
        empty($fields['container_qty']) ||
        empty($fields['container_type_raw'])
    ) {
        if (preg_match("/Quantity\s*:?\s*(\d+)\s*[xX]\s*(20|40)\s*'?\s*(HC|HQ|H|GP|DC|DRY|FT)?/i", $flat, $m)) {
            $qty = (int) $m[1];
            if ($qty <= 0) {
                $qty = 1;
            }

            $rawType = trim($m[2] . ' ' . ($m[3] ?? ''));

            $fields['container_type_raw'] = trim($m[0]);
            $fields['container_type']     = $this->normalize_container_type_label($rawType);
            $fields['container_qty']      = (string) $qty;
        }
    }

    // Commodity
    if (empty($fields['commodity'])) {
        if (preg_match('/HS\s+Commodity\s*:?\s*(.+?)\s+Container\s+Grade/i', $flat, $m)) {
            $fields['commodity'] = $this->clean_value($m[1]);
        }
    }

    // Gross Weight
    if (empty($fields['gross_weight'])) {
        if (preg_match('/Gross\s+Weight\s*:?\s*([0-9\.,]+\s*KGM?)/i', $flat, $m)) {
            $fields['gross_weight'] = strtoupper(trim($m[1]));
        }
    }

    // Depot
    if (empty($fields['pickup_depot'])) {
        if (preg_match('/Preferred\s+Depot\s*:?\s*(.+?)\s+PLEASE\s+NOTE/i', $flat, $m)) {
            $fields['pickup_depot'] = $this->clean_value($m[1]);
        }
    }

    // DG / Hazardous
    if (preg_match('/Hazardous\s*:\s*Y\b/i', $flat) || stripos($flat, 'Special Cargo: Hazardous') !== false || stripos($flat, 'UN Number') !== false) {
        $fields['is_dg'] = '1';
        $fields['cargo_type'] = 'DG';
    }

    if (empty($fields['liner_id'])) {
        $fields['liner_id'] = 'CMA';
    }

    $fields['schedule_kind_hint'] = 'DIRECT';
}
	
	private function apply_yangming_booking_confirmation(&$fields, $text)
{
    $flat = preg_replace('/\s+/', ' ', (string) $text);
    $hay  = strtoupper($flat);

    if (
        strpos($hay, 'YANG MING') === false &&
        strpos($hay, 'Booking Ref: S') === false &&
        strpos($hay, 'No.of CNTRS') === false
    ) {
        return;
    }

    $fields['liner_id'] = !empty($fields['liner_id']) ? $fields['liner_id'] : 'YML';
    $fields['schedule_kind_hint'] = 'VIA';

    // Booking Ref: S425904464
    if (empty($fields['booking_number']) && preg_match('/Booking\s+Ref\s*:\s*([A-Z0-9]+)/i', $flat, $m)) {
        $fields['booking_number'] = strtoupper(trim($m[1]));
    }

    // SI No : WB261044726
    if (empty($fields['si_no']) && preg_match('/SI\s+No\s*:\s*([A-Z0-9]+)/i', $flat, $m)) {
        $fields['si_no'] = strtoupper(trim($m[1]));
    }

    // Date: 24/06/2026
    if (empty($fields['do_date']) && preg_match('/\bDate\s*:\s*(\d{1,2}\/\d{1,2}\/\d{4})/i', $flat, $m)) {
        $fields['do_date'] = $this->normalize_date($m[1]);
    }

    /*
     * Vessel Name:
     * ITAL BONNY N102 ( CIM2622N ) / YM EXCELLENCE 156W ( CPX2626W )
     */
    if (preg_match('/Vessel\s+Name\s*:\s*(.+?)(?:\s+Load\s+Voy\s+Call\s+Sign|\s+EST\.OPEN\s+STACK|\s+No\.of\s+CNTRS|$)/i', $flat, $m)) {
        $rawVessel = trim($m[1]);
        $fields['vessel_voy_raw'] = $rawVessel;

        $legs = preg_split('/\s*\/\s*/', $rawVessel);
        $firstLeg  = isset($legs[0]) ? trim($legs[0]) : '';
        $secondLeg = isset($legs[1]) ? trim($legs[1]) : '';

        $first = $this->_parse_yangming_vessel_leg($firstLeg);
        if (!empty($first['vessel'])) {
            $fields['vessel'] = $first['vessel'];
        }

        if (!empty($first['voy'])) {
            // Untuk direct-compatible response.
            $fields['voy'] = $first['voy'];

            // Untuk form VIA.
            $fields['voy_vessel'] = $first['voy'];
        }

        $second = $this->_parse_yangming_vessel_leg($secondLeg);
        if (!empty($second['vessel'])) {
            $fields['connecting'] = $second['vessel'];
            $fields['second_vessel'] = $second['vessel'];
        }

        if (!empty($second['voy'])) {
            $fields['voy_con'] = $second['voy'];
            $fields['second_voy'] = $second['voy'];
        }

        // Voyage dalam kurung kalau nanti dibutuhkan.
        if (!empty($first['ref_voy'])) {
            $fields['first_allocation_voy'] = $first['ref_voy'];
        }

        if (!empty($second['ref_voy'])) {
            $fields['second_allocation_voy'] = $second['ref_voy'];
        }
    }

    // ETD : 28/06/2026
    if (empty($fields['etd_jkt']) && preg_match('/\bETD\s*:\s*(\d{1,2}\/\d{1,2}\/\d{4})/i', $flat, $m)) {
        $fields['etd_jkt'] = $this->normalize_date($m[1]);
    }

    // ETA DEST: 27/07/2026
    if (empty($fields['eta']) && preg_match('/ETA\s+DEST\s*:\s*(\d{1,2}\/\d{1,2}\/\d{4})/i', $flat, $m)) {
        $fields['eta'] = $this->normalize_date($m[1]);
    }

    // EST.OPEN STACK: 23/06/2026 01:00
    if (empty($fields['open_stack_at']) && preg_match('/EST\.?\s*OPEN\s+STACK\s*:\s*(\d{1,2}\/\d{1,2}\/\d{4}\s+\d{1,2}:\d{2})/i', $flat, $m)) {
        $fields['open_stack_at'] = $this->normalize_datetime($m[1]);
    }

    if (empty($fields['stuffing_date']) && !empty($fields['open_stack_at'])) {
        $fields['stuffing_date'] = substr($fields['open_stack_at'], 0, 10);
    }

    // EST.CLOSING TIME: 25/06/2026 15:00
    if (empty($fields['stf_cls']) && preg_match('/EST\.?\s*CLOSING\s+TIME\s*:\s*(\d{1,2}\/\d{1,2}\/\d{4}\s+\d{1,2}:\d{2})/i', $flat, $m)) {
        $fields['cargo_closing_at'] = $this->normalize_datetime($m[1]);
        $fields['stf_cls'] = substr($fields['cargo_closing_at'], 0, 10);
    }

    // No.of CNTRS: 1 x 20DC
    if (preg_match('/No\.?\s*of\s+CNTRS\s*:\s*(\d+)\s*[xX]\s*(20|40)\s*\'?\s*(DC|GP|FT|HC|HQ|H|DRY|RF|REEFER)?/i', $flat, $m)) {
        $qty = (int) $m[1];
        if ($qty <= 0) {
            $qty = 1;
        }

        $rawContainer = trim($m[1] . ' x ' . $m[2] . ($m[3] ?? ''));
        $container = $this->normalize_container($rawContainer);

        $fields['container_type_raw'] = $rawContainer;
        $fields['container_type']     = $container['type'];
        $fields['container_qty']      = (string) $qty;
    }

    // COMMODITY: ... Remarks:
    if (empty($fields['commodity']) && preg_match('/COMMODITY\s*:\s*(.+?)(?:\s+Remarks\s*:|\s+POL\s*:|$)/i', $flat, $m)) {
        $fields['commodity'] = strtoupper(trim($m[1]));
    }

    // Remarks: ...
    if (empty($fields['remarks']) && preg_match('/Remarks\s*:\s*(.+?)(?:\s+POL\s*:|\s+POD\s*:|\s+Final\s+Dest\s*:|$)/i', $flat, $m)) {
        $fields['remarks'] = strtoupper(trim($m[1]));
    }

    // POL: (IDJKT) JAKARTA
    if (empty($fields['pol']) && preg_match('/POL\s*:\s*(?:\([^)]+\)\s*)?(.+?)(?:\s+Transit\s+Port\s*:|\s+POD\s*:|$)/i', $flat, $m)) {
        $fields['pol'] = $this->clean_location_name($m[1]);
    }

    // POD: (LKCMB) COLOMBO
    if (empty($fields['pod']) && preg_match('/POD\s*:\s*(?:\([^)]+\)\s*)?(.+?)(?:\s+Transit\s+Terminal\s*:|\s+Final\s+Dest\s*:|$)/i', $flat, $m)) {
        $fields['pod'] = $this->clean_location_name($m[1]);
    }

    // Transit Port: SINGAPORE
    if (empty($fields['transit_port']) && preg_match('/Transit\s+Port\s*:\s*(.+?)(?:\s+POD\s*:|\s+Transit\s+Terminal\s*:|\s+Final\s+Dest\s*:|$)/i', $flat, $m)) {
        $fields['transit_port'] = $this->clean_location_name($m[1]);
    }

    // Transit Terminal: SGSINP01
    // Supaya tidak ikut "Final Dest".
    if (preg_match('/Transit\s+Terminal\s*:\s*([A-Z0-9\-]+)/i', $flat, $m)) {
        $fields['transit_terminal'] = strtoupper(trim($m[1]));
    }

    // Final Dest: (LKCMB) COLOMBO
    if (empty($fields['final_destination']) && preg_match('/Final\s+Dest\s*:\s*(?:\([^)]+\)\s*)?(.+?)(?:\s+Empty\s+Container\s+Pickup|\s+Indonesia\s+-|\s+PLEASE\s+REVIEW|$)/i', $flat, $m)) {
        $fields['final_destination'] = $this->clean_location_name($m[1]);
    }

    // Empty Container Pickup At
    if (empty($fields['pickup_depot']) && preg_match('/Empty\s+Container\s+Pickup\s+At\s*:\s*(.+?)(?:\s+Indonesia\s+-|\s+Jl\.|\s+INDONESIA|\s+TELP|$)/i', $flat, $m)) {
        $fields['pickup_depot'] = strtoupper(trim($m[1]));
    }

    // Mapping untuk form VIA.
    if (!empty($fields['transit_port'])) {
        $fields['etd_city_con_id'] = $fields['transit_port'];
    }

    if (!empty($fields['final_destination'])) {
        $fields['eta_city_id'] = $fields['final_destination'];
    } elseif (!empty($fields['pod'])) {
        $fields['eta_city_id'] = $fields['pod'];
    }
}
private function _parse_yangming_vessel_leg($leg)
{
    $leg = strtoupper(trim((string) $leg));
    $leg = preg_replace('/\s+/', ' ', $leg);

    $out = array(
        'vessel' => '',
        'voy' => '',
        'ref_voy' => ''
    );

    if ($leg === '') {
        return $out;
    }

    // Ambil voyage dalam kurung: ( CIM2622N )
    if (preg_match('/\(\s*([A-Z0-9\-]+)\s*\)/i', $leg, $m)) {
        $out['ref_voy'] = strtoupper(trim($m[1]));
    }

    // Hilangkan bagian dalam kurung.
    $clean = preg_replace('/\s*\([^)]*\)\s*/', ' ', $leg);
    $clean = preg_replace('/\s+/', ' ', trim($clean));

    // Format:
    // ITAL BONNY N102
    // YM EXCELLENCE 156W
    if (preg_match('/^(.+?)\s+([A-Z0-9\-]+)$/i', $clean, $m)) {
        $out['vessel'] = strtoupper(trim($m[1]));
        $out['voy']    = strtoupper(trim($m[2]));
        return $out;
    }

    $out['vessel'] = $clean;
    return $out;
}
    
	
	private function apply_kmtc_booking_notice(&$fields, $text, $originalName = '')
    {
        $t = $this->normalize_ocr_text($text);
        $flat = $this->one_line($t);

        $isKmtc = $this->looks_like_kmtc_text($flat, $originalName);
        if (!$isKmtc) {
            return;
        }

        // Booking No. KMTC OCR sering rusak: "BookingNo ... 1001032994".
        // Paling aman ambil dari nama file dulu: DO ID01032994.pdf.
        $bookingFromName = $this->first_match(array(
            '/\b(ID[0-9]{6,})\b/i',
        ), strtoupper((string) $originalName));

        if ($this->looks_like_booking_number($bookingFromName)) {
            $fields['booking_number'] = strtoupper($bookingFromName);
        } else {
            $booking = $this->first_match(array(
                '/Booking\s*No\.?\s*[^A-Z0-9]{0,30}\s*(ID[0-9]{6,})/i',
                '/Booking\s*No\.?\s*[^0-9]{0,30}\s*([0-9O]{9,12})\b/i',
            ), $flat);

            if ($booking !== '') {
                $booking = strtoupper($booking);
                // OCR KMTC kadang membaca ID01032994 menjadi 1001032994.
                if (preg_match('/^[1I]0{0,2}([0-9]{8})$/', $booking, $m)) {
                    $booking = 'ID' . $m[1];
                }
                if ($this->looks_like_booking_number($booking)) {
                    $fields['booking_number'] = $booking;
                }
            }
        }

        // Date / DO Date.
        $doDate = $this->first_match(array(
            '/\bDate\s*[^0-9]{0,20}([0-9]{4}[.\/-][0-9]{2}[.\/-][0-9]{2})/i',
        ), $flat);
        if ($doDate !== '') {
            $fields['do_date'] = $this->normalize_do_date_value($doDate);
        }

        // Vessel / Voyage: "Vessel/Voyage = = §CMACGMFORT JAMES/ OXLBWN".
        if (preg_match('/Vessel\s*\/\s*Voyage\s*[^A-Z0-9]{0,40}(.+?)\s*\/\s*([A-Z0-9]{4,12})/i', $flat, $m)) {
            $vessel = $this->clean_vessel_name_keep_space($m[1]);
            $voy    = $this->normalize_voyage_code($m[2]);

            if ($vessel !== '') {
                $fields['vessel'] = $vessel; // override parser umum untuk KMTC.
            }
            if ($voy !== '') {
                $fields['voy'] = $voy;       // override parser umum untuk KMTC.
            }
            $fields['vessel_voy_raw'] = trim($vessel . ' / ' . $voy);
        }

        // KMTC: etd_jkt dari POL ETA / POLETA.
        $polEta = $this->extract_kmtc_date_near_label($flat, 'POL');
        if ($polEta !== '') {
            $fields['etd_jkt'] = $polEta;
        }

        // KMTC: eta dari POD ETA / PODETA.
        $podEta = $this->extract_kmtc_date_near_label($flat, 'POD');
        if ($podEta !== '') {
            $fields['eta'] = $podEta;
        }

        $pol = $this->extract_kmtc_location($flat, 'POL');
        if ($pol !== '') {
            $fields['pol'] = $pol;
        }

        $pod = $this->extract_kmtc_location($flat, 'POD');
        if ($pod !== '') {
            $fields['pod'] = $pod;
        }

        // Delivery / Final Destination. OCR bisa menjadi Delvery.
        if (preg_match('/\b(?:Delivery|Delvery|Delivry)\s*[^A-Z0-9(]{0,20}(.+?)(?:\s+Total\s+Volume|\s+Commodity|\s+Cargo\s+Closing|$)/i', $flat, $m)) {
            $fields['final_destination'] = $this->clean_location_name($m[1]);
        }

        // Total Volume: 40 HC X 1 / 40H.
        if (preg_match('/Total\s+Volume\s+(.+?)(?:\s+Commodity|\s+Cargo\s+Closing|$)/i', $flat, $m)) {
            $raw = $this->clean_ocr_noise($m[1]);
            $fields['container_type_raw'] = trim($raw);
            $parsed = $this->parse_kmtc_container_type($raw);
            if (!empty($parsed)) {
                $fields['container_type'] = $parsed['type'];
                $fields['container_qty']  = $parsed['qty'];
            }
        }

        // Commodity: buang noise "= =".
        if (preg_match('/\bCommodity\s+(.+?)(?:\s+Cargo\s+Closing\s+Time|\s+Document\s+Closing\s+Time|$)/i', $flat, $m)) {
            $fields['commodity'] = $this->clean_ocr_noise($m[1]);
        }

        // Cargo Closing Time.
        if (preg_match('/Cargo\s+Closing\s+Time\s*[^0-9]{0,20}([0-9]{4}[.\/-][0-9]{2}[.\/-][0-9]{2})\s+([0-9]{2}:[0-9]{2})/i', $flat, $m)) {
            $fields['cargo_closing_at'] = $this->normalize_do_datetime_value($m[1] . ' ' . $m[2]);
        }

        // Document Closing Time.
        if (preg_match('/Document\s+Closing\s+Time\s*[^0-9]{0,20}([0-9]{4}[.\/-][0-9]{2}[.\/-][0-9]{2})\s+([0-9]{2}:[0-9]{2})/i', $flat, $m)) {
            $fields['doc_closing_at'] = $this->normalize_do_datetime_value($m[1] . ' ' . $m[2]);
        }

        if (!empty($fields['cargo_closing_at'])) {
            $fields['stf_cls'] = substr($fields['cargo_closing_at'], 0, 10);
        }

        if (empty($fields['stuffing_date']) && !empty($fields['etd_jkt'])) {
            $fields['stuffing_date'] = $fields['etd_jkt'];
        }

        if (stripos($flat, 'DG CARGO') !== false) {
            $fields['is_dg'] = '1';
            $fields['cargo_type'] = 'DG';
        }

        $fields['liner_id'] = 'KMTC';
        $fields['schedule_kind_hint'] = 'DIRECT';

        // Field ini sering salah tertangkap dari kalimat remark KMTC, jadi kosongkan jika tidak jelas.
        if (!empty($fields['terminal']) && preg_match('/DG\s+certificate|stacking\s+DG|terminal\s+u\//i', $fields['terminal'])) {
            $fields['terminal'] = '';
        }

        // Final trim.
        foreach ($fields as $k => $v) {
            if (is_string($v)) {
                $fields[$k] = trim($v);
            }
        }
    }

	private function normalize_ocr_text($text)
	{
		$text = str_replace(["\r\n", "\r"], "\n", (string) $text);
		$text = preg_replace('/[ \t]+/', ' ', $text);
		$text = preg_replace('/\n+/', "\n", $text);

		return trim($text);
	}

    private function clean_vessel_name_keep_space($value)
    {
        $value = strtoupper(trim((string) $value));
        $value = str_replace(array("\xC2\xA7", '§'), ' ', $value);
        $value = preg_replace('/[^A-Z0-9 ]+/i', ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value);
        $value = trim($value, " \t\n\r\0\x0B/,-=_");

        // Normalisasi OCR tanpa spasi: CMACGMFORT JAMES => CMA CGM FORT JAMES.
        $value = preg_replace('/\bCMA\s*CGM\s*/i', 'CMA CGM ', $value);
        $value = preg_replace('/\bCMACGM\s*/i', 'CMA CGM ', $value);
        $value = preg_replace('/\s+/', ' ', $value);

        return trim($value);
    }

    private function clean_location_name($value)
    {
        $value = strtoupper(trim((string) $value));
        $value = str_replace(array('§', '“', '”', '—', '–', '_', '=', '~', '('), ' ', $value);
        $value = preg_replace('/[^A-Z0-9, .\-\/]+/i', ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value);
        $value = trim($value, " \t\n\r\0\x0B/,-=_~()");
        return $value;
    }

	private function normalize_do_date_value($value)
	{
		$value = trim((string) $value);
		$value = str_replace('.', '-', $value);
		$value = str_replace('/', '-', $value);

		if (preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/', $value, $m)) {
			return $m[1] . '-' . $m[2] . '-' . $m[3];
		}

		if (preg_match('/^([0-9]{2})-([0-9]{2})-([0-9]{4})$/', $value, $m)) {
			return $m[3] . '-' . $m[2] . '-' . $m[1];
		}

		return '';
	}

	private function normalize_do_datetime_value($value)
	{
		$value = trim((string) $value);

		if (preg_match('/([0-9]{4}[.\/-][0-9]{2}[.\/-][0-9]{2})\s+([0-9]{2}:[0-9]{2})/', $value, $m)) {
			$date = $this->normalize_do_date_value($m[1]);
			if ($date !== '') {
				return $date . ' ' . $m[2] . ':00';
			}
		}

		return '';
	}

    private function parse_kmtc_container_type($raw)
    {
        $raw = $this->clean_ocr_noise($raw);
        $raw = strtoupper(trim((string) $raw));
        $raw = str_replace(array("'", '"'), '', $raw);
        $raw = preg_replace('/\s+/', ' ', $raw);

        // Contoh: 40 HC X 1, 40H, 40 H, 40HC, 20, 20 FT.
        if (preg_match('/\b(20|40)\s*(HC|HQ|H|DC|GP|FT)?\s*(?:[xX]\s*(\d+))?\b/i', $raw, $m)) {
            $size = $m[1];
            $type = isset($m[2]) ? strtoupper(trim($m[2])) : '';
            $qty  = isset($m[3]) && $m[3] !== '' ? (int) $m[3] : 1;

            if ($qty <= 0) {
                $qty = 1;
            }

            if ($size === '40' && in_array($type, array('HC', 'HQ', 'H'), true)) {
                return array('type' => '40 HC', 'qty' => (string) $qty);
            }

            if ($size === '40') {
                return array('type' => '40 FT', 'qty' => (string) $qty);
            }

            if ($size === '20') {
                return array('type' => '20 FT', 'qty' => (string) $qty);
            }
        }

        return null;
    }

    private function looks_like_kmtc_text($text, $originalName = '')
    {
        $hay = strtoupper((string) $text . ' ' . (string) $originalName);
        return (strpos($hay, 'KMTC') !== false)
            || (strpos($hay, 'KMITC') !== false)
            || (strpos($hay, 'KOREA MARINE') !== false)
            || (strpos($hay, 'EKMTC') !== false)
            || (preg_match('/\bID[0-9]{6,}\b/i', $hay) && strpos($hay, 'BOOKING NOTICE') !== false);
    }

    private function clean_ocr_noise($value)
    {
        $value = strtoupper(trim((string) $value));
        $value = str_replace(array('§', '“', '”', '—', '–', '_'), ' ', $value);
        $value = preg_replace('/[^A-Z0-9,.\-\/ ]+/i', ' ', $value);
        $value = preg_replace('/\s*=\s*/', ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value);
        return trim($value, " \t\n\r\0\x0B/,-=_");
    }

    private function normalize_voyage_code($value)
    {
        $value = strtoupper(trim((string) $value));
        $value = preg_replace('/[^A-Z0-9]/', '', $value);
        // OCR sering baca angka 0 sebagai huruf O di voyage: OXLBWN => 0XLBWN.
        if (preg_match('/^OX/i', $value)) {
            $value = '0' . substr($value, 1);
        }
        return $value;
    }

    private function extract_kmtc_date_near_label($flat, $label)
    {
        $label = strtoupper($label);
        $patterns = array(
            '/\b' . $label . '\s*ETA\s*[^0-9]{0,20}([0-9]{4}[.\/-][0-9]{2}[.\/-][0-9]{2})/i',
            '/\b' . $label . '\s+.+?\b' . $label . '\s*ETA\s*[^0-9]{0,20}([0-9]{4}[.\/-][0-9]{2}[.\/-][0-9]{2})/i',
        );

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $flat, $m)) {
                return $this->normalize_do_date_value($m[1]);
            }
        }
        return '';
    }

    private function extract_kmtc_location($flat, $label)
    {
        $label = strtoupper($label);
        $patterns = array(
            '/\b' . $label . '\s+(.+?)\s+' . $label . '\s*ETA\b/i',
            '/\b' . $label . '\s+(.+?)\s+' . $label . 'ETA\b/i',
        );

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $flat, $m)) {
                return $this->clean_location_name($m[1]);
            }
        }
        return '';
    }
	
	private function parse_msc_equipment_container($flat)
	{
		$flat = preg_replace('/\s+/', ' ', (string) $flat);

		if (preg_match('/EQUIP\.?\s*TYPE\s*\/\s*NUMBER\s*:?\s*([0-9]{2}\s*(?:HC|HQ|H|GP|DC|DRY|RF|REEFER)?)\s+QUANTITY\s*:?\s*(\d+)/i', $flat, $m)) {
			return array(
				'raw'  => trim($m[1] . ' x' . $m[2]),
				'type' => $this->normalize_container_type_label($m[1]),
				'qty'  => (string) ((int) $m[2] > 0 ? (int) $m[2] : 1),
			);
		}

		// Fallback untuk text-based PDF yang kadang layout-nya turun baris:
		// EQUIP.TYPE/NUMBER ... QUANTITY ...
		// 40HC ... 1
		if (preg_match('/EQUIP\.?\s*TYPE\s*\/\s*NUMBER.*?QUANTITY.*?\b(20|40)\s*(HC|HQ|H|GP|DC|DRY|RF|REEFER)?\b.*?\b(\d+)\s+(?:HAZARDOUS|REEFER|S\.O\.C)\b/is', $flat, $m)) {
			return array(
				'raw'  => trim($m[1] . $m[2] . ' x' . $m[3]),
				'type' => $this->normalize_container_type_label($m[1] . $m[2]),
				'qty'  => (string) ((int) $m[3] > 0 ? (int) $m[3] : 1),
			);
		}

		return null;
	}

	private function normalize_container_type_label($value)
	{
		$v = strtoupper(trim((string) $value));
		$v = str_replace(array("'", '"', '.', '-', '_'), ' ', $v);
		$v = preg_replace('/\s+/', ' ', $v);

		if (preg_match('/\b40\s*(HC|HQ|H|HIGH CUBE|HI CUBE|HICUBE)\b/i', $v)) {
			return '40 HC';
		}

		if (preg_match('/\b20\s*(HC|HQ|H|HIGH CUBE|HI CUBE|HICUBE)\b/i', $v)) {
			return '20 FT';
		}

		if (preg_match('/\b40\b/i', $v)) {
			return '40 FT';
		}

		if (preg_match('/\b20\b/i', $v)) {
			return '20 FT';
		}

		return $v;
	}

    private function parse_fields($rawText, $originalName = '')
    {
        $text = $this->clean_text($rawText);
        $flat = $this->one_line($text);
        $fields = array();

        $fields['booking_number'] = $this->first_match(array(
            '/BOOKING\s+CONFIRMATION\s*:\s*([A-Z0-9\-\/]+)/i',
            '/Booking\s+reference\s*:\s*([A-Z0-9\-\/]+)/i',
            '/Booking\s+Ref\.?\s*(?:No\.?)?\s*:?\s*([A-Z0-9\-\/]+)/i',
            '/Booking\s+Number\s*:?\s*([A-Z0-9\-\/]+)/i',
            '/Booking\s+No\.?[^A-Z0-9]{0,20}([A-Z0-9]{5,})/i',
            '/BKG\s+NO\s*:?\s*([A-Z0-9\-\/]+)/i',
        ), $flat);

        $bookingFromName = $this->first_match(array(
            '/(COSU[0-9]{6,}|EBKG[0-9]{5,}|ARM[0-9]{5,}|ID[0-9]{6,}|S[0-9]{6,}|JKTA[0-9]{5,}|JKTG[0-9]{5,}|ONEY[A-Z0-9]{6,}|HASL[A-Z0-9]{6,}|SNKO[A-Z0-9]{6,}|SUDU[A-Z0-9]{6,})/i',
        ), strtoupper($originalName));

        if ($this->looks_like_booking_number($bookingFromName)) {
            $fields['booking_number'] = strtoupper($bookingFromName);
        }

        if (!$this->looks_like_booking_number($fields['booking_number'])) {
            $fields['booking_number'] = $this->first_match(array(
                '/(?:COSU|EBKG|ARM|ID|SUDU|SUD|S|JKTA|JKTG|ONEY|HASL|SNKO)[A-Z0-9]{5,}/i',
                '/([A-Z]{3,5}[0-9]{6,})/i',
            ), strtoupper($flat . ' ' . $originalName));
        }

        $fields['si_no'] = $this->first_match(array(
            '/SI\s+No\s*:?\s*([A-Z0-9\-\/]+)/i',
            '/S\/I\s+REF\.\s+NO\.?\s*:?\s*([A-Z0-9\-\/]+)/i',
            '/B\/L\s+No\.?\s*:?\s*([A-Z0-9\-\/]+)/i',
            '/ORIGINAL\/SEA\s+WAYBILL[^A-Z0-9]+NUMBER\s+([A-Z0-9\-\/]+)/i',
        ), $flat);

        $fields['do_date'] = $this->normalize_date($this->first_match(array(
            '/\bDate\s*:\s*([0-9]{1,2}[\-\/][0-9]{1,2}[\-\/][0-9]{2,4})/i',
            '/\bDate\s*:\s*([0-9]{4}[\.\-\/][0-9]{2}[\.\-\/][0-9]{2})/i',
            '/DATE\s*:\s*([0-9]{1,2}\s+[A-Za-z]{3}\s+[0-9]{4})/i',
            '/Booking\s+Date\s*:?\s*([0-9]{1,2}[\-\/][A-Za-z]{3}[\-\/]?[0-9]{2,4}|[0-9]{4}[\.\-\/][0-9]{2}[\.\-\/][0-9]{2})/i',
        ), $flat));

        $vesselRaw = $this->first_match(array(
            '/VSL\s+Name\s*\/\s*Voy\s+(.+?)(?:\s+Berthing\s|\s+E\.T\.D\s|$)/i',
            '/Vessel\/Voyage\s*:\s*(.+?)(?:\s+Receipt\s*:|\s+ETD\s*:|$)/i',
            '/Vessel\s*\/\s*Voyage\s*:?\s*(.+?)(?:\s+POL\s|\s+POD\s|$)/i',
            '/1st\s+Vessel\/Voyage\s*:\s*(.+?)(?:\s+T\/S\s+Port\s*:|\s+Call\s+Sign\s*:|$)/i',
            '/Trunk\s+Vessel\s*:\s*(.+?)(?:\s+Latest\s+ETA\/ETD\s*:|$)/i',
            '/Vessel\s+Name\s*:?\s*(.+?)(?:\s+ETA\s+DEST\s*:|\s+Flag\s*:|$)/i',
            '/INTENDED\s+VESSEL\/VOYAGE\s*:\s*(.+?)(?:\s+ETD\s*:|\s+SERVICE\s+CODE\s*:|$)/i',
            '/LOADING\s+VSL\s*\/\s*VOY\s*:\s*(.+?)(?:\s+PORT\s+OF\s+LOAD\s*:|$)/i',
        ), $flat);

        // Samudera text sometimes puts vessel in the details area without the original label.
        if ($vesselRaw === '') {
            $vesselRaw = $this->first_match(array('/\b([A-Z][A-Z ]+\s+V\.?\s*\d{2,4}[A-Z])\b/'), $flat);
        }

        $vesselParts = $this->split_vessel_voyage($vesselRaw);
        $fields['vessel_voy_raw'] = $vesselRaw;
        $fields['vessel'] = $vesselParts['vessel'];
        $fields['voy'] = $vesselParts['voy'];

        // MSC layout often separates vessel and voyage into two table cells.
        if (empty($fields['vessel']) || empty($fields['voy'])) {
            $mscVessel = $this->first_match(array('/VESSEL\s+NAME\s*\/\s*FLAG\s+(.+?)\s+PORT\s+OF\s+TRANSHIPMENT/i'), $flat);
            $mscVoy = $this->first_match(array('/VOYAGE\s+NUMBER\s+([A-Z0-9]{3,})/i'), $flat);
            if (!empty($mscVessel)) {
                $mscVessel = preg_replace('/\bLLOYDS\s+NO\.?\s*[0-9]+/i', '', $mscVessel);
                $mscVessel = preg_replace('/\bLR\s+VOYAGE\s+NUMBER.*$/i', '', $mscVessel);
                $mscVessel = preg_replace('/[()\/]+/', ' ', $mscVessel);
                $fields['vessel'] = $this->clean_value($mscVessel);
            }
            if (!empty($mscVoy)) $fields['voy'] = $mscVoy;
        }

        $fields['etd_jkt'] = $this->normalize_date($this->first_match(array(
            '/Proforma\s+1st\s+vessel\s+ETD\s*:?\s*([0-9]{1,2}[A-Za-z]{3}[0-9]{2})/i',
            '/Latest\s+ETA\/ETD\s*:\s*[0-9A-Za-z\-\/\.\s:]+\/\s*([0-9]{1,2}[A-Za-z]{3}[0-9]{2}|[0-9]{1,2}[A-Za-z]{3}[0-9]{4}|[0-9]{4}[\/\.\-][0-9]{2}[\/\.\-][0-9]{2})/i',
            '/\bE\.T\.D\s*:?\s*([0-9]{4}[\/\.\-][0-9]{2}[\/\.\-][0-9]{2}(?:\s+[0-9]{1,2}:[0-9]{2})?)/i',
            '/\bETD\s*:?\s*([0-9]{4}[\/\.\-][0-9]{2}[\/\.\-][0-9]{2}(?:\s+[0-9]{1,2}:[0-9]{2})?)/i',
            '/\bETD\s*:?\s*([0-9]{1,2}\s+[A-Za-z]{3}\s+[0-9]{4}(?:\s+[0-9]{1,2}:[0-9]{2})?)/i',
            '/\bETD\s*:?\s*([0-9]{1,2}[\-\/][A-Za-z]{3}[\-\/]?[0-9]{2,4}(?:\s+[0-9]{1,2}:[0-9]{2})?)/i',
            '/\bETD\s*:?\s*([0-9]{1,2}[\/\-][0-9]{1,2}[\/\-][0-9]{4}(?:\s+[0-9]{1,2}:[0-9]{2})?)/i',
            '/EST\.\s*TIME\s+OF\s+ARRIVAL\/DEPARTURE\s+[0-9\/\-\.\s:]+\s+([0-9]{1,2}\/\d{1,2}\/\d{4}(?:\s+\d{1,2}:\d{2})?)/i',
        ), $flat));

        $fields['eta'] = $this->normalize_date($this->first_match(array(
            '/ETA\s+DEST\s*:?\s*([0-9]{1,2}[\/\-][0-9]{1,2}[\/\-][0-9]{2,4})/i',
            '/POD\s*\/\s*DEL\s+ETA\s*:?\s*([0-9]{1,2}[A-Za-z]{3}[0-9]{2})/i',
            '/POD\s+ETA\s*:?\s*([0-9]{4}[\.\-\/][0-9]{2}[\.\-\/][0-9]{2})/i',
            '/\bE\.T\.A\s*:?\s*([0-9]{4}[\/\.\-][0-9]{2}[\/\.\-][0-9]{2}(?:\s+[0-9]{1,2}:[0-9]{2})?)/i',
            '/PORT\s+OF\s+DISCHARGE\s+.+?EST\.\s+TIME\s+OF\s+ARRIVAL\s+([0-9]{1,2}[\/\-][0-9]{1,2}[\/\-][0-9]{4}(?:\s+[0-9]{1,2}:[0-9]{2})?)/i',
            '/Port\s+of\s+Discharg(?:e|ing).*?ETA\s*:?\s*([0-9]{4}[\/\.\-][0-9]{2}[\/\.\-][0-9]{2}(?:\s+[0-9]{1,2}:[0-9]{2})?)/i',
            '/FINAL\s+DESTINATION.*?ETA\s*:?\s*([0-9]{1,2}\s+[A-Za-z]{3}\s+[0-9]{4}|[0-9]{4}[\/\.\-][0-9]{2}[\/\.\-][0-9]{2})/i',
            '/ETA\s*\(DEST\)\s*:?\s*([0-9]{1,2}[\-\/][0-9]{1,2}[\-\/][0-9]{4})/i',
            '/\bETA\s*:?\s*([0-9]{1,2}[\-\/][A-Za-z]{3}[\-\/]?[0-9]{2,4}|[0-9]{1,2}[\-\/][0-9]{1,2}[\-\/][0-9]{4})/i',
        ), $flat));

        $fields['open_stack_at'] = $this->normalize_datetime($this->first_match(array(
            '/EST\.\s*OPEN\s+STACK\s*:?\s*([0-9]{1,2}[\/\-][0-9]{1,2}[\/\-][0-9]{2,4}\s+[0-9]{1,2}:[0-9]{2})/i',
            '/OPEN\s+STACK\s*:?\s*([0-9]{4}[\/\-\.][0-9]{2}[\/\-\.][0-9]{2}\s+[0-9]{1,2}:[0-9]{2})/i',
            '/CY\s+Open\s*:?\s*([0-9]{4}[\/\-\.][0-9]{2}[\/\-\.][0-9]{2}(?:\s+[0-9]{1,2}:[0-9]{2})?)/i',
        ), $flat));

        $fields['stf_cls'] = $this->normalize_date($this->first_match(array(
            '/Cargo\s+Closing\s+Time\s*:?\s*([0-9]{4}[\.\-\/][0-9]{2}[\.\-\/][0-9]{2}(?:\s+[0-9]{1,2}:[0-9]{2})?)/i',
            '/Port\s+Cargo\s+Cut-off\s*:?\s*([0-9]{1,2}[A-Za-z]{3}[0-9]{2}\s+[0-9]{1,2}:[0-9]{2}|[0-9]{4}[\/\.\-][0-9]{2}[\/\.\-][0-9]{2}\s+[0-9]{1,2}:[0-9]{2})/i',
            '/Cargo\s+Cut-off[^0-9]*([0-9]{4}[\/\.\-][0-9]{2}[\/\.\-][0-9]{2}\s+[0-9]{1,2}:[0-9]{2})/i',
            '/Port\s+Cut-Off\s+Date\/Time\s*:?\s*([0-9]{1,2}\-[A-Za-z]{3}\-[0-9]{4}\s+[0-9]{1,2}:[0-9]{2})/i',
            '/EST\.\s*CLOSING\s+TIME\s*:?\s*([0-9]{1,2}[\/\-][0-9]{1,2}[\/\-][0-9]{2,4}\s+[0-9]{1,2}:[0-9]{2})/i',
            '/CLOSING\s+TIME\s*:?\s*([0-9]{4}[\/\-\.][0-9]{2}[\/\-\.][0-9]{2}\s+[0-9]{1,2}:[0-9]{2})/i',
        ), $flat));

        $fields['cargo_closing_at'] = $this->normalize_datetime($this->first_match(array(
            '/Cargo\s+Closing\s+Time\s*:?\s*([0-9]{4}[\.\-\/][0-9]{2}[\.\-\/][0-9]{2}\s+[0-9]{1,2}:[0-9]{2})/i',
            '/Port\s+Cargo\s+Cut-off\s*:?\s*([0-9]{1,2}[A-Za-z]{3}[0-9]{2}\s+[0-9]{1,2}:[0-9]{2}|[0-9]{4}[\/\.\-][0-9]{2}[\/\.\-][0-9]{2}\s+[0-9]{1,2}:[0-9]{2})/i',
            '/Cargo\s+Cut-off[^0-9]*([0-9]{4}[\/\.\-][0-9]{2}[\/\.\-][0-9]{2}\s+[0-9]{1,2}:[0-9]{2})/i',
        ), $flat));

        $fields['doc_closing_at'] = $this->normalize_datetime($this->first_match(array(
            '/Document\s+Closing\s+Time\s*:?\s*([0-9]{4}[\.\-\/][0-9]{2}[\.\-\/][0-9]{2}\s+[0-9]{1,2}:[0-9]{2})/i',
            '/Doc\s+Cut-off\s*:?\s*([0-9]{1,2}[A-Za-z]{3}[0-9]{2}\s+[0-9]{1,2}:[0-9]{2})/i',
            '/SI\s+General\s+Cut-off\s*:?\s*([0-9]{4}[\/\.\-][0-9]{2}[\/\.\-][0-9]{2}\s+[0-9]{1,2}:[0-9]{2})/i',
            '/SHIPPING\s+INSTRUCTIONS\s+CUT-OFF\s*([0-9]{1,2}[\/\-][0-9]{1,2}[\/\-][0-9]{2,4}\s+[0-9]{1,2}:[0-9]{2})/i',
        ), $flat));

        $fields['vgm_cutoff_at'] = $this->normalize_datetime($this->first_match(array(
            '/VGM\s+Cut-off\s*:?\s*([0-9]{1,2}[A-Za-z]{3}[0-9]{2}\s+[0-9]{1,2}:[0-9]{2}|[0-9]{4}[\/\.\-][0-9]{2}[\/\.\-][0-9]{2}\s+[0-9]{1,2}:[0-9]{2})/i',
            '/VERIFIED\s+GROSS\s+MASS[^0-9]*([0-9]{4}[\/\.\-][0-9]{2}[\/\.\-][0-9]{2}\s+[0-9]{1,2}:[0-9]{2})/i',
        ), $flat));

        $fields['si_cutoff_at'] = $this->normalize_datetime($this->first_match(array(
            '/SI\s+Cut-Off\s+Date\/Time\s*:?\s*([0-9]{1,2}\-[A-Za-z]{3}\-[0-9]{4}\s+[0-9]{1,2}:[0-9]{2})/i',
            '/SI\s+General\s+Cut-off\s*:?\s*([0-9]{4}[\/\.\-][0-9]{2}[\/\.\-][0-9]{2}\s+[0-9]{1,2}:[0-9]{2})/i',
            '/DUE\s+DATE\s*:?\s*([0-9]{1,2}\s+[A-Za-z]{3}\s+[0-9]{4}\s+[0-9]{1,2}:[0-9]{2})/i',
        ), $flat));

        $fields['stuffing_date'] = '';
        if (!empty($fields['open_stack_at'])) {
            $fields['stuffing_date'] = substr($fields['open_stack_at'], 0, 10);
        }
		

        $containerRaw = $this->first_match(array(
			// MSC: EQUIP.TYPE/NUMBER 40HC QUANTITY 1
			'/EQUIP\.?\s*TYPE\s*\/\s*NUMBER\s*:?\s*([0-9]{2}\s*(?:HC|HQ|H|GP|DC|DRY|RF|REEFER)?)\s+QUANTITY\s*:?\s*(\d+)/i',

			// KMTC: Total Volume 40 HC X 1 / 40H
			'/Total\s+Volume\s+([0-9]{2}\s*(?:HC|HQ|H|GP|DC|DRY|RF|REEFER)?(?:\s*[xX]\s*\d+)?)(?:\s+Commodity|\s+Cargo\s+Closing|\r?\n|$)/i',

			// Heung-A / Sinokor: Cntr Q'TY 40Hx1
			'/Cntr\s+Q\'?TY\s+(.+?)(?:\s+CY\s+Open|\s+Cargo\s+Position|$)/i',

			// COSCO
			'/BOOKING\s+QTY\s+SIZE\s*\/\s*TYPE\s*:?\s*(\d+\s*[xX]\s*\d+\s*\'?\s*(?:Hi[\-\s]?Cube|High[\-\s]?Cube|HC|HQ|H|GP|DC|DRY|FT)?\s*(?:Container)?)/i',
			'/TOTAL\s+BOOKING\s+CONTAINER\s+(\d+\s*[xX]\s*\d+\s*\'?\s*(?:Hi[\-\s]?Cube|High[\-\s]?Cube|HC|HQ|H|GP|DC|DRY|FT)?\s*(?:Container)?)/i',
			'/QTY\s+SIZE\s*\/\s*TYPE\s*:?\s*(\d+\s*[xX]\s*\d+\s*\'?\s*(?:Hi[\-\s]?Cube|High[\-\s]?Cube|HC|HQ|H|GP|DC|DRY|FT)?\s*(?:Container)?)/i',

			// ONE: Equipment Type/Q’ty : 20'DRY ST.-1, 40'DRY HC.-2
			'/Equipment\s+Type\s*\/\s*Q.?ty\s*:?\s*(.+?)(?:\s+Commodity\s*:|\s+Empty\s+Pick|\r?\n|$)/iu',

			'/TOTAL\s+BOOKING\s+CONTAINER\s+QTY\s+SIZE\/TYPE\s*:?\s*(.+?)(?:\s+PLACE\s+OF\s+RECEIPT|$)/i',
			'/No\.of\s+CNTRS\s*:?\s*(.+?)(?:\s+COMMODITY\s*:|\s+POL\s*:|$)/i',
		), $flat);

		$coscoContainer = $this->parse_cosco_hicube_container($flat);
		$mscContainer   = $this->parse_msc_equipment_container($flat);

		if (!empty($coscoContainer)) {
			// JANGAN normalize_container($coscoContainer), karena ini array.
			$fields['container_type_raw'] = $coscoContainer['raw'];
			$fields['container_type']     = $coscoContainer['type'];
			$fields['container_qty']      = $coscoContainer['qty'];

		} elseif (!empty($mscContainer)) {
			// JANGAN normalize_container($mscContainer), karena ini array.
			$fields['container_type_raw'] = $mscContainer['raw'];
			$fields['container_type']     = $mscContainer['type'];
			$fields['container_qty']      = $mscContainer['qty'];

		} else {
			$container = $this->normalize_container($containerRaw);

			$fields['container_type_raw'] = $containerRaw;
			$fields['container_type']     = $container['type'];
			$fields['container_qty']      = $container['qty'];
		}

		// Fallback khusus ONE kalau raw belum ketemu.
		if (empty($fields['container_type_raw'])) {
			if (preg_match('/Equipment\s+Type\s*\/\s*Q.?ty\s*:\s*(.+?)(?:\s+Commodity\s*:|\s+Empty\s+Pick|\r?\n)/isu', $text, $m)) {
				$fields['container_type_raw'] = trim($m[1]);

				if (empty($fields['container_type'])) {
					$container = $this->normalize_container($fields['container_type_raw']);
					$fields['container_type'] = $container['type'];
					$fields['container_qty']  = $container['qty'];
				}
			}
		}

		if (empty($fields['container_type_raw'])) {
			if (preg_match('/Equipment\s+Type\s*\/\s*Q.?ty\s*:\s*(.+)/iu', $text, $m)) {
				$fields['container_type_raw'] = trim($m[1]);

				if (empty($fields['container_type'])) {
					$container = $this->normalize_container($fields['container_type_raw']);
					$fields['container_type'] = $container['type'];
					$fields['container_qty']  = $container['qty'];
				}
			}
		}

        $fields['pol'] = $this->clean_value($this->first_match(array(
            '/Port\s+of\s+Loading\s*:?\s*(.+?)(?:\s+Port\s+of\s+Discharge|\s+Terminal\s*:|\s+TML\s*:|$)/i',
            '/PORT\s+OF\s+LOAD\s*:?\s*(.+?)(?:\s+PORT\s+OF\s+DISCHARGE|\s+PLACE\s+OF\s+DELIVERY|$)/i',
            '/POL\s*:?\s*(?:\([A-Z0-9]+\)\s*)?(.+?)(?:\s+Transit\s+Port|\s+POD\s*:|\s+POL\s+ETA|$)/i',
        ), $flat));

        $fields['pod'] = $this->clean_value($this->first_match(array(
            '/Port\s+of\s+Discharg(?:e|ing)\s*:?\s*(.+?)(?:\s+Place\s+of\s+Delivery|\s+Terminal\s*:|\s+ETA\s*:|\s+TML\s*:|$)/i',
            '/POD\s*:?\s*(?:\([A-Z0-9]+\)\s*)?(.+?)(?:\s+Transit\s+Terminal|\s+Final\s+Dest|\s+POD\s+ETA|$)/i',
        ), $flat));

        $fields['final_destination'] = $this->clean_value($this->first_match(array(
            '/Final\s+Dest(?:ination)?\s*:?\s*(?:\([A-Z0-9]+\)\s*)?(.+?)(?:\s+Empty\s+Container|\s+ETA\s*:|\s+ESTIMATED\s+CARGO|$)/i',
            '/Place\s+of\s+Delivery\s*:?\s*(.+?)(?:\s+T\/S\s+Port|\s+Ocean\s+Route|\s+ETA\s*:|$)/i',
        ), $flat));

        $fields['transit_port'] = $this->clean_value($this->first_match(array(
            '/Transit\s+Port\s*:?\s*(.+?)(?:\s+Transit\s+Terminal|\s+Final\s+Dest|\s+POD\s*:|$)/i',
            '/Transhipment\s*:?\s*(.+?)(?:\s+ETA\s*:|\s+Port\s+Of\s+Discharge|$)/i',
            '/T\/S\s+Port\s*:?\s*(.+?)(?:\s+Ocean\s+Route|\s+POD\s*:|$)/i',
        ), $flat));

        $fields['transit_terminal'] = $this->clean_value($this->first_match(array('/Transit\s+Terminal\s*:?\s*([A-Z0-9\-\/ ]+)/i'), $flat));

        $fields['terminal'] = $this->clean_value($this->first_match(array(
            '/Terminal\s*:?\s*(.+?)(?:\s+Port\s+of\s+Discharging|\s+T\/S\s+Port|$)/i',
            '/Loading\s+Terminal\s*:?\s*(.+?)(?:\s+VGM\s+Cut-Off|\s+ETD\s*:|$)/i',
            '/Berthing\s+(.+?)(?:\s+E\.T\.D|\s+Port\s+of\s+Loading|$)/i',
        ), $flat));

        $fields['pickup_depot'] = $this->clean_value($this->first_match(array(
            '/Empty\s+Container\s+Pickup\s+At\s*:?\s*(.+?)(?:\s+SINGAPORE|\s+PLEASE\s+REVIEW|\s+Indonesia\s+-|$)/i',
            '/Empty\s+Pick\s*UP\s+CY\s*:?\s*(.+?)(?:\s+Empty\s+Pick\s+Up\s+Date|\s+Address\s*:|$)/i',
            '/PICK\s+UP\s+AT\s+DEPOT\s*:?\s*(.+?)(?:\s+ORIGIN\s+ADDRESS|\s+REEFER|$)/i',
            '/Pickup\s+Place\s+(.+?)(?:\s+Online|\s+Booking\s+Root|$)/i',
        ), $flat));

        $fields['full_return_cy'] = $this->clean_value($this->first_match(array(
            '/Full\s+Return\s+CY\s*:?\s*(.+?)(?:\s+Full\s+Return\s+Date|\s+Address\s*:|$)/i',
            '/FULL\s+RETURN\s+LOCATION\s*:?\s*(.+?)(?:\s+EMPTY\s+PICK|$)/i',
        ), $flat));

        $fields['empty_pickup_at'] = $this->normalize_datetime($this->first_match(array(
            '/Empty\s+Pick\s+Up\s+Date\s*:?\s*([0-9]{1,2}[A-Za-z]{3}[0-9]{2}\s+[0-9]{1,2}:[0-9]{2}|[0-9]{4}[\/\.\-][0-9]{2}[\/\.\-][0-9]{2}\s+[0-9]{1,2}:[0-9]{2})/i',
            '/Estimated\s+Empty\s+Pick-up\s+Date.*?:\s*([0-9]{4}[\/\.\-][0-9]{2}[\/\.\-][0-9]{2}\s+[0-9]{1,2}:[0-9]{2})/i',
            '/Merchant\s+Haulage\s+Eqp\s+Available\s+Date.*?From\s+-\s+([0-9]{1,2}\-[A-Za-z]{3}\-[0-9]{4})/i',
        ), $flat));

        $fields['commodity'] = $this->clean_value($this->first_match(array(
            '/COMMODITY\s*:?\s*(.+?)(?:\s+Remarks\s*:|\s+POL\s*:|\s+Cargo\s+Position|\s+Cntr\s+Q|$)/i',
            '/Commodity\s+Desc\.\s*:?\s*(.+?)(?:\s+Flex\s+EQ|\s+PKG|$)/i',
            '/CARGO\s+DESCRIPTION\s*:?\s*(.+?)(?:\s+OUTBOUND\s+TRAFFIC|\s+HS\s+CODE|$)/i',
            '/CARGO\s+DESCRIPTION\s+HS\s+CODE\s+WEIGHT.*?\n\s*(.+?)\s+\d{6}/is',
        ), $text));

        $fields['gross_weight'] = $this->clean_value($this->first_match(array(
            '/Gross\s+WGT\s*:?\s*([0-9\.,]+\s*KGS?)/i',
            '/Cargo\s+Weight\s*:?\s*([0-9\.,]+\s*KGS?)/i',
            '/Estimated\s+Weight\s*:?\s*([0-9\.,]+\s*KGS?)/i',
            '/CARGO\s+WEIGHT\s*:?\s*([0-9\.,]+\s*KG)/i',
            '/WEIGHT\s*:?\s*([0-9\.,]+\s*KG)/i',
        ), $flat));

        $dgSummary = $this->extract_dg_summary($flat);
        $isDg = false;
        if (preg_match('/Cargo\s+Nature\s*:?\s*Dangerous/i', $flat)) $isDg = true;
        if (preg_match('/HAZARDOUS\s*:?[\sA-Z]*Y\b/i', $flat)) $isDg = true;
        if (preg_match('/DG\s+DETAILS\s*:/i', $flat)) $isDg = true;
        if ($dgSummary !== '') $isDg = true;
        if (!empty($fields['commodity']) && preg_match('/\bDG\b|DANGEROUS/i', $fields['commodity'])) $isDg = true;
        if (preg_match('/BOOKING\s+REMARKS\s*:?[^.]{0,80}DG\s+CARGO/i', $flat)) $isDg = true;

        $fields['is_dg'] = $isDg ? '1' : '0';
        $fields['cargo_type'] = ($fields['is_dg'] === '1') ? 'DG' : 'GENERAL';
        $fields['dg_details'] = $dgSummary;

        $fields['remarks'] = $this->clean_value($this->first_match(array(
            '/Remarks?\s*:?\s*(.+?)(?:\s+Keterangan\s*:|\s+IMPORTANT\s+NOTE|\s+THE\s+ABOVE\s+BOOKING|$)/i',
            '/BOOKING\s+REMARKS\s*:?\s*(.+?)(?:\s+CONTAINER\s+DETAILS|\s+DG\s+DETAILS|$)/i',
        ), $flat));

        $fields['liner_id'] = $this->detect_liner($flat, $originalName, $fields['booking_number']);
        $fields['schedule_kind_hint'] = !empty($fields['transit_port']) ? 'VIA' : 'DIRECT';

        // Remove empty values to keep response smaller, but keep key fields.
        foreach ($fields as $k => $v) {
            if (is_string($v)) {
                $fields[$k] = trim($v);
            }
        }

        return $fields;
    }

    private function first_match($patterns, $text)
    {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $m)) {
                for ($i = 1; $i < count($m); $i++) {
                    if (isset($m[$i]) && trim((string) $m[$i]) !== '') {
                        return trim($m[$i]);
                    }
                }
            }
        }
        return '';
    }

    private function clean_value($value)
    {
        $value = trim((string) $value);
        $value = preg_replace('/\s+/', ' ', $value);
        $value = preg_replace('/\s+(POD ETA|POL ETA|ETA|ETD|TML|Terminal|Port of|Place of).*$/i', '', $value);
        $value = trim($value, " \t\n\r\0\x0B:-");
        if (strlen($value) > 120) {
            return '';
        }
        $value = preg_replace('/\s+SI\s+Cut-Off.*$/i', '', $value);
        $value = preg_replace('/\s+Loading$/i', '', $value);
        if (preg_match('/^(of\s+Delivery|N\*?1|Call\s+Sign|POD\s*\/\s*DEL)/i', $value)) {
            return '';
        }
        return $value;
    }

    private function split_vessel_voyage($raw)
    {
        $raw = trim(preg_replace('/\s+/', ' ', (string) $raw));
        $raw = trim($raw, " :-");
        $result = array('vessel' => '', 'voy' => '');

        if ($raw === '') {
            return $result;
        }

        if (preg_match('/^(\d{2})\s*H$/i', $raw, $m)) {
            return array('type' => $m[1] . 'HC', 'qty' => '');
        }

        // Cut long OCR/table spillover after the useful vessel phrase.
        $raw = preg_replace('/\s+(ETA|ETD|EX\s+VSL|PORT\s+OF|BOOKING\s+REF|PLACE\s+OF|T\/S\s+PORT).*$/i', '', $raw);
        $rawNoParen = trim(preg_replace('/\s*\([^)]*\)\s*/', ' ', $raw));
        $rawNoParen = trim(preg_replace('/\s+/', ' ', $rawNoParen));

        // Example: SINAR SANUR V.145N
        if (preg_match('/^(.+?)\s+V\.?\s*([0-9]{2,4}[A-Z])\b/i', $rawNoParen, $m)) {
            $result['vessel'] = trim($m[1]);
            $result['voy'] = trim($m[2]);
            return $this->clean_vessel_parts($result);
        }

        // Example: CHENNAI VOYAGER // 2606N
        if (strpos($rawNoParen, '//') !== false) {
            $parts = preg_split('/\s*\/\/\s*/', $rawNoParen);
            $result['vessel'] = trim($parts[0]);
            $result['voy'] = isset($parts[1]) ? trim($parts[1]) : '';
            return $this->clean_vessel_parts($result);
        }

        // Example: CMA CGM POINTE DU PITON / 0XLBUN1NC / 0XLBUN
        // Example VIA/YM: ITAL BONNY N102 / YM EXCELLENCE 156W -> take first vessel/voyage.
        if (strpos($rawNoParen, '/') !== false) {
            $parts = preg_split('/\s*\/\s*/', $rawNoParen);
            $first = trim($parts[0]);
            if (preg_match('/^(.+?)\s+([A-Z0-9]*[0-9]{2,}[A-Z]?)$/i', $first, $m)) {
                $result['vessel'] = trim($m[1]);
                $result['voy'] = trim($m[2]);
            } else {
                $result['vessel'] = $first;
                $result['voy'] = isset($parts[1]) ? trim($parts[1]) : '';
            }
            return $this->clean_vessel_parts($result);
        }

        // Example: COSCO ADEN 142S, HIGHWAY 374N, HMM DAVAO 0022N
        if (preg_match('/^(.+?)\s+([A-Z0-9]*[0-9][A-Z0-9]*)\b/i', $rawNoParen, $m)) {
            $result['vessel'] = trim($m[1]);
            $result['voy'] = trim($m[2]);
            return $this->clean_vessel_parts($result);
        }

        $result['vessel'] = $rawNoParen;
        return $this->clean_vessel_parts($result);
    }

    private function clean_vessel_parts($parts)
    {
        $parts['vessel'] = trim(preg_replace('/\s*\([^)]*\)\s*/', ' ', $parts['vessel']));
        $parts['vessel'] = preg_replace('/\bLLOYDS\s+NO\.?\s*[0-9]+/i', '', $parts['vessel']);
        $parts['vessel'] = preg_replace('/\bLR\s+VOYAGE\s+NUMBER.*$/i', '', $parts['vessel']);
        $parts['vessel'] = preg_replace('/[^A-Z0-9 ]+/i', ' ', $parts['vessel']);
        $parts['vessel'] = trim(preg_replace('/\s+/', ' ', $parts['vessel']));
        $parts['voy'] = trim(preg_replace('/\s*\([^)]*\)\s*/', '', $parts['voy']));
        $parts['voy'] = trim($parts['voy'], " .:-");
        return $parts;
    }

	private function normalize_container($raw)
	{
		if (is_array($raw)) {
			return array(
				'type' => isset($raw['type']) ? $this->normalize_container_type_label($raw['type']) : '',
				'qty'  => isset($raw['qty']) ? (string) $raw['qty'] : ''
			);
		}

		$raw = strtoupper(trim((string) $raw));
		$raw = str_replace(array("’", "`", "´"), "'", $raw);
		$raw = preg_replace('/\s+/', ' ', $raw);

		$result = array(
			'type' => '',
			'qty'  => ''
		);

		if ($raw === '') {
			return $result;
		}

		$qtyTotal = 0;
		$types = array();

		// Format: 40Hx1 / 40HCx1 / 40 HC X 1 / 20GPx1
		if (preg_match_all('/\b(20|40)\s*\'?\s*(HC|HQ|H|GP|DC|DRY|FT|RF|REEFER)?\s*[xX]\s*(\d+)\b/i', $raw, $mm, PREG_SET_ORDER)) {
			foreach ($mm as $m) {
				$qty = (int) $m[3];
				if ($qty <= 0) {
					$qty = 1;
				}

				$qtyTotal += $qty;
				$types[] = $this->normalize_container_type_label($m[1] . ' ' . ($m[2] ?? ''));
			}
		}

		// Format: 1 X H40' / 1 X 40HC / 1 X 40' Hi-Cube Container
		if (empty($types) && preg_match_all("/\b(\d+)\s*[xX]\s*(H)?\s*(20|40)\s*'?\s*(HI[\-\s]?CUBE|HIGH[\-\s]?CUBE|HC|HQ|H|GP|DC|DRY|FT|RF|REEFER)?/i", $raw, $mm, PREG_SET_ORDER)) {
			foreach ($mm as $m) {
				$qty = (int) $m[1];
				if ($qty <= 0) {
					$qty = 1;
				}

				$qtyTotal += $qty;

				$prefixH = !empty($m[2]) ? 'H ' : '';
				$type    = isset($m[4]) ? $m[4] : '';

				$types[] = $this->normalize_container_type_label($m[3] . ' ' . $prefixH . ' ' . $type);
			}
		}

		// Format: 20'DRY ST.-1, 40'DRY HC.-2
		if (empty($types) && preg_match_all("/\b(20|40)\s*'?\s*DRY\s*(HC|HQ|ST|DC|GP)?\.?\s*-\s*(\d+)/i", $raw, $mm, PREG_SET_ORDER)) {
			foreach ($mm as $m) {
				$qty = (int) $m[3];
				if ($qty <= 0) {
					$qty = 1;
				}

				$qtyTotal += $qty;

				$suffix = strtoupper(trim($m[2] ?? ''));
				$types[] = $this->normalize_container_type_label($m[1] . ' ' . $suffix);
			}
		}

		// Format single: 40HC / 40H / H40 / 20GP
		if (empty($types)) {
			$clean = preg_replace('/\s*x\s*\d+\s*$/i', '', $raw);
			$clean = preg_replace('/\s*-\s*\d+\s*$/i', '', $clean);
			$types[] = $this->normalize_container_type_label($clean);
		}

		$types = array_values(array_unique(array_filter($types)));

		$result['type'] = implode(', ', $types);
		$result['qty']  = $qtyTotal > 0 ? (string) $qtyTotal : '';

		return $result;
	}

    private function normalize_size_suffix($suffix)
    {
        $suffix = strtoupper(trim((string) $suffix));
        if ($suffix === '' || $suffix === 'GP' || $suffix === 'DC' || $suffix === 'DRY') return 'FT';
		if ($suffix === 'H' || $suffix === 'HC' || $suffix === 'HQ' || strpos($suffix, 'HI') === 0) return ' HC';
        if ($suffix === 'DRY') return 'DRY';
        if ($suffix === 'DC') return 'DC';
        return $suffix;
    }

    private function extract_dg_summary($flat)
    {
        $parts = array();
        if (preg_match_all('/UN\s*(?:Number|No\.)?\s*:?\s*([0-9]{4})/i', $flat, $m)) {
            $parts[] = 'UN ' . implode(', UN ', array_unique($m[1]));
        }
        if (preg_match_all('/(?:IMO|IMDG)\s+Class\s*:?\s*([0-9\.]+)/i', $flat, $m)) {
            $parts[] = 'IMO ' . implode(', IMO ', array_unique($m[1]));
        }
        if (preg_match('/DG\s+CARGO[^A-Z0-9]*(CLASS\s*[0-9]+\s+UN\s*[0-9]+)/i', $flat, $m)) {
            $parts[] = strtoupper($m[1]);
        }
        if (preg_match('/DG\s+DETAILS\s*:?\s*(.+?)(?:\s+SPECIAL\s+DETAILS|\s+CONTAINER\s+OP|\s+RETURN\s+TO|$)/i', $flat, $m)) {
            $dg = trim(preg_replace('/\s+/', ' ', $m[1]));
            if ($dg !== '') $parts[] = strtoupper($dg);
        }
        return implode(' | ', array_unique($parts));
    }

    private function detect_liner($flat, $originalName, $bookingNumber)
    {
        $hay = strtoupper($flat . ' ' . $originalName . ' ' . $bookingNumber);
        if (strpos($hay, 'SAMUDERA') !== false || strpos($hay, 'SUDU') !== false) return 'SAMUDERA';
        if (strpos($hay, 'COSCO') !== false || strpos($hay, 'COSU') !== false) return 'COSCO';
        if (strpos($hay, 'MEDITERRANEAN SHIPPING') !== false || strpos($hay, 'MSC') !== false || strpos($hay, 'EBKG') !== false) return 'MSC';
        if (strpos($hay, 'CMA CGM') !== false || strpos($hay, 'CNC') !== false || strpos($hay, 'ARM') !== false) return 'CMA CGM';
        if (strpos($hay, 'KMTC') !== false || strpos($hay, 'KOREA MARINE') !== false) return 'KMTC';
        if (strpos($hay, 'YANG MING') !== false || strpos($hay, 'YML') !== false) return 'YML';
        if (strpos($hay, 'HMM') !== false || strpos($hay, 'HYUNDAI') !== false) return 'HYUNDAI';
        if (strpos($hay, 'SINOKOR') !== false || strpos($hay, 'SNKO') !== false) return 'SINOKOR';
        if (strpos($hay, 'OCEAN NETWORK EXPRESS') !== false || strpos($hay, 'ONEY') !== false) return 'ONE';
        if (strpos($hay, 'HEUNG A') !== false || strpos($hay, 'HASL') !== false) return 'HASPUL';
        return '';
    }

    private function normalize_date($value)
    {
        $dt = $this->parse_datetime_value($value);
        return $dt ? $dt->format('Y-m-d') : '';
    }

    private function normalize_datetime($value)
    {
        $dt = $this->parse_datetime_value($value);
        return $dt ? $dt->format('Y-m-d H:i:s') : '';
    }

    private function parse_datetime_value($value)
    {
        $value = trim((string) $value);
        if ($value === '') return null;

        $value = str_replace(array('.', '/', ','), array('-', '-', ' '), $value);
        $value = preg_replace('/\s+/', ' ', $value);

        // 09Jul26 / 29Jun26
        if (preg_match('/^(\d{1,2})([A-Za-z]{3})(\d{2})(?:\s+(\d{1,2}:\d{2}))?$/', $value, $m)) {
            $value = $m[1] . '-' . $m[2] . '-20' . $m[3] . (isset($m[4]) ? ' ' . $m[4] : '');
        }

        $formats = array(
            'Y-m-d H:i:s', 'Y-m-d H:i',
            'd-m-Y H:i:s', 'd-m-Y H:i',
            'd-M-Y H:i:s', 'd-M-Y H:i',
            'd-M-y H:i:s', 'd-M-y H:i',
            'd M Y H:i:s', 'd M Y H:i',
            'd-M-Y', 'd-M-y', 'd M Y', 'Y-m-d', 'd-m-Y', 'd-m-y',
        );

        foreach ($formats as $fmt) {
            $dt = DateTime::createFromFormat($fmt, $value);
            if ($dt instanceof DateTime) {
                $errors = DateTime::getLastErrors();
                if ($errors === false || ((int)$errors['warning_count'] === 0 && (int)$errors['error_count'] === 0)) {
                    return $dt;
                }
            }
        }

        $ts = strtotime($value);
        if ($ts !== false) {
            return new DateTime('@' . $ts);
        }

        return null;
    }

    private function looks_like_booking_number($value)
    {
        $value = strtoupper(trim((string)$value));
        if ($value === '' || strlen($value) < 5) return false;
        if (in_array($value, array('NO', 'NOTICE', 'NUMBER', 'REFERENCE'), true)) return false;
        // Jangan terima potongan OCR dari "Booking Notice" seperti TICE.
        if (!preg_match('/[0-9]/', $value)) return false;
        return (bool) preg_match('/[A-Z].*[0-9]|[0-9].*[A-Z]/', $value);
    }

    private function remove_dir($dir)
    {
        if (!is_dir($dir)) return;
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($path)) $this->remove_dir($path);
            else @unlink($path);
        }
        @rmdir($dir);
    }
}
