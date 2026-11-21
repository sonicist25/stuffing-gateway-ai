<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;

class Quotations extends RestController {

    public function __construct() {
        parent::__construct();
        $this->load->library('crm_auth');
        $this->load->model('api/crm/M_quotations', 'm_quotations');
    }

    /**
     * GET /api/crm/quotations
     * Query:
     * - quote_id (optional) => detail
     * - page, limit, q, region, sales, status, opportunity_id
     */
    public function index_get() {
        try {
            $u = $this->crm_auth->require_user();

            $quoteId = $this->get('quote_id');
            if ($quoteId) {
                $detail = $this->m_quotations->get((int)$quoteId);
                if (!$detail) return $this->response(['status'=>false,'message'=>'Quotation not found'], 404);

                return $this->response(['status'=>true,'data'=>$detail], 200);
            }

            // list mode
            $filters = [
                'page'          => $this->get('page') ?? 1,
                'limit'         => $this->get('limit') ?? 20,
                'q'             => $this->get('q') ?? '',
                'status'        => $this->get('status') ?? '',
                'opportunity_id'=> $this->get('opportunity_id') ?? null,
            ];

            // default region from user (management boleh override via query)
            $filters['region'] = $this->get('region') ?: ($u['region_id'] ?? '');

            // sales scope: non-management selalu dirinya
            if ((int)($u['management'] ?? 0) !== 1) {
                $filters['sales'] = (string)$u['user_id'];
            } else {
                $filters['sales'] = $this->get('sales') ?? 'ALL';
            }

            $out = $this->m_quotations->list($filters);
            return $this->response($out, 200);

        } catch (Exception $e) {
            return $this->response(['status'=>false,'message'=>$e->getMessage()], 401);
        }
    }

    /**
     * POST /api/crm/quotations
     * Body JSON:
     * {
     *   quote_no?, opportunity_id?, account_name, status?, quote_date?, valid_until?,
     *   currency?, show_total?, multi_destination?,
     *   items?: [{item_type, description, qty, unit_price, amount?, pricing_basis?}]
     * }
     */
    public function index_post() {
        try {
            $u = $this->crm_auth->require_user();
            $body = json_decode(file_get_contents('php://input'), true);
            if (!is_array($body)) $body = [];

            if (empty($body['account_name']) && empty($body['opportunity_id'])) {
                throw new Exception('account_name or opportunity_id is required');
            }

            $id = $this->m_quotations->create($body, (int)$u['user_id']);

            return $this->response([
                'status' => true,
                'message'=> 'Quotation created',
                'quote_id'=> $id,
            ], 201);

        } catch (Exception $e) {
            return $this->response(['status'=>false,'message'=>$e->getMessage()], 400);
        }
    }

    /**
     * PUT /api/crm/quotations?quote_id=123
     * Body JSON:
     * - header fields optional
     * - items optional (kalau ada => replace items)
     */
	
    public function index_put($id = null)
	{
		try {
			$this->crm_auth->require_user();

			// ✅ Ambil dari URL query string dulu, karena $this->get() tidak reliable di PUT
			$quoteId = (int)($id ?: $this->input->get('quote_id', true));
			if (!$quoteId) {
				// fallback: dari payload PUT
				$quoteId = (int)($this->put('quote_id') ?? 0);
			}
			if (!$quoteId) throw new Exception('quote_id is required');

			// payload
			$payload = $this->put();
			if (!is_array($payload) || empty($payload)) {
				$payload = json_decode($this->input->raw_input_stream, true);
				if (!is_array($payload)) $payload = [];
			}

			$ok = $this->m_quotations->update($quoteId, $payload);

			return $this->response([
				'status'  => true,
				'message' => 'Quotation updated',
				'data'    => ['quote_id' => $quoteId, 'updated' => (bool)$ok],
			], 200);

		} catch (Exception $e) {
			return $this->response(['status'=>false,'message'=>$e->getMessage()], 400);
		}
	}

	public function index_delete($id = null)
	{
		try {
			$this->crm_auth->require_user();

			$quoteId = (int)($id ?: $this->input->get('quote_id', true));
			if (!$quoteId) $quoteId = (int)($this->delete('quote_id') ?? 0);
			if (!$quoteId) throw new Exception('quote_id is required');

			$res = $this->m_quotations->soft_delete($quoteId);

			return $this->response([
				'status'  => (bool)$res,
				'message' => $res ? 'Deleted' : 'Nothing deleted',
			], $res ? 200 : 400);

		} catch (Exception $e) {
			return $this->response(['status'=>false,'message'=>$e->getMessage()], 400);
		}
	}


    /**
     * GET /api/crm/quotations/items?quote_id=123
     */
    public function items_get() {
        try {
            $this->crm_auth->require_user();
            $quoteId = (int)($this->get('quote_id') ?? 0);
            if (!$quoteId) throw new Exception('quote_id is required');

            $items = $this->m_quotations->get_items($quoteId);
            return $this->response(['status'=>true,'data'=>$items], 200);

        } catch (Exception $e) {
            return $this->response(['status'=>false,'message'=>$e->getMessage()], 400);
        }
    }

    /**
     * PUT /api/crm/quotations/items?quote_id=123
     * Body: { items: [...] }  OR langsung array [...]
     */
	public function item_put_put() {
			return $this->items_put();
		}
		
    public function items_put() {
		try {
			$this->crm_auth->require_user();

			$quoteId = (int)(
				$this->get('quote_id')
				?? $this->input->get('quote_id', true)
				?? $this->put('quote_id')
				?? 0
			);
			if (!$quoteId) throw new Exception('quote_id is required');

			$body = json_decode($this->input->raw_input_stream, true);
			if (!is_array($body)) $body = [];

			$items = $body;
			if (array_key_exists('items', $body)) $items = $body['items'];

			$this->m_quotations->replace_items($quoteId, $items);

			return $this->response(['status'=>true,'message'=>'Items replaced'], 200);
		} catch (Exception $e) {
			return $this->response(['status'=>false,'message'=>$e->getMessage()], 400);
		}
	}


    /**
     * POST /api/crm/quotations/clone_from_opportunity
     * Body: { opportunity_id, show_total?, multi_destination?, with_skeleton_items? }
     */
    public function clone_from_opportunity_post() {
        try {
            $u = $this->crm_auth->require_user();

            $body = json_decode(file_get_contents('php://input'), true);
            if (!is_array($body)) $body = [];

            $oppId = (int)($body['opportunity_id'] ?? 0);
            if (!$oppId) throw new Exception('opportunity_id is required');

            $opts = [
                'show_total'          => $body['show_total'] ?? 1,
                'multi_destination'   => $body['multi_destination'] ?? 0,
                'with_skeleton_items' => !empty($body['with_skeleton_items']) ? 1 : 0,
            ];

            $quoteId = $this->m_quotations->clone_from_opportunity($oppId, (int)$u['user_id'], $opts);

            return $this->response([
                'status'=>true,
                'message'=>'Cloned',
                'quote_id'=>$quoteId,
            ], 201);

        } catch (Exception $e) {
            return $this->response(['status'=>false,'message'=>$e->getMessage()], 400);
        }
    }

    /**
     * GET /api/crm/quotations/preview?quote_id=123
     * output: HTML
     */
    public function preview_get($id = null)
	{
		try {
			$u = $this->crm_auth->require_user();

			// Support: /quotations/preview/{id} OR /quotations/preview?quote_id=...
			$quotationId = (int)($id ?: $this->get('quote_id'));
			if ($quotationId <= 0) throw new Exception("Quotation id is required");

			$format = strtolower((string)$this->get('format'));
			if (!$format) $format = 'html';

			$download = (int)($this->get('download') ?? 0);

			$data = $this->m_quotations->get_preview_data($quotationId, $u);
			//print_r($data); die();
			// render HTML
			$html = $this->load->view('crm/quotation_preview', $data, true);

			if ($format === 'pdf') {
				$this->load->library('Pdf');
				$this->pdf
					->loadHtml($html)
					->setPaper('A4', 'portrait')
					->render();

				$filename = 'Quotation-' . $quotationId . '.pdf';
				$this->pdf->stream($filename, ['Attachment' => $download ? 1 : 0]);
				return;
			}


			$this->output
				->set_content_type('text/html', 'utf-8')
				->set_output($html);

		} catch (Exception $e) {
			return $this->response([
				'status' => false,
				'message' => $e->getMessage(),
			], RestController::HTTP_BAD_REQUEST);
		}
	}


    /**
     * GET /api/crm/quotations/print?quote_id=123&format=pdf|html
     * - pdf: butuh mpdf/mpdf via composer
     */
    public function print_get() {
        try {
            $this->crm_auth->require_user();
            $quoteId = (int)($this->get('quote_id') ?? 0);
            if (!$quoteId) throw new Exception('quote_id is required');

            $format = strtolower((string)($this->get('format') ?? 'pdf'));

            $detail = $this->m_quotations->get($quoteId);
            if (!$detail) throw new Exception('Quotation not found');

            $html = $this->load->view('crm/quotation_preview', $detail, true);

            if ($format === 'html') {
                $this->output
                    ->set_content_type('text/html', 'utf-8')
                    ->set_output($html);
                return;
            }

            // PDF via mPDF (composer)
            if (!class_exists('\\Mpdf\\Mpdf')) {
                // fallback
                $this->output
                    ->set_content_type('text/html', 'utf-8')
                    ->set_output($html . "<!-- Install mpdf/mpdf to enable PDF -->");
                return;
            }

            $mpdf = new \Mpdf\Mpdf([
                'tempDir' => APPPATH.'cache/mpdf',
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_left' => 10,
                'margin_right' => 10,
                'margin_top' => 12,
                'margin_bottom' => 12,
            ]);
            $mpdf->WriteHTML($html);

            $filename = 'Quotation-'.$quoteId.'.pdf';
            $this->output
                ->set_content_type('application/pdf')
                ->set_header('Content-Disposition: inline; filename="'.$filename.'"');

            $this->output->set_output($mpdf->Output($filename, 'S'));

        } catch (Exception $e) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json', 'utf-8')
                ->set_output(json_encode(['status'=>false,'message'=>$e->getMessage()]));
        }
    }
}
