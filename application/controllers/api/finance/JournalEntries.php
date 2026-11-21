<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class JournalEntries extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('api/finance/M_journal_entries', 'mje');
    }

    /**
     * GET /api/finance/journal-entries
     */
    public function get()
    {
        return $this->index_get();
    }

    public function index()
    {
        return $this->index_get();
    }

    public function index_get()
    {
        log_message('error', 'DEBUG JE index_get HIT');

        $filters = [
            'status'    => $this->input->get('status', true),
            'date_from' => $this->input->get('date_from', true),
            'date_to'   => $this->input->get('date_to', true),
            'q'         => $this->input->get('q', true),
        ];

        $res = $this->mje->list($filters);

        return $this->_json(true, 'OK', $res, null, 200);
    }

    /**
     * POST /api/finance/journal-entries
     *
     * PENTING:
     * Beberapa route CI3 mengarah ke post(), bukan index_post().
     */
    public function post()
    {
        return $this->index_post();
    }

    public function index_post()
    {
        log_message('error', 'DEBUG JE index_post HIT');

        $payload = $this->_json_payload();

        log_message('error', 'DEBUG JE payload=' . json_encode($payload));

        $res = $this->mje->create_draft($payload);

        log_message('error', 'DEBUG JE create_draft result=' . json_encode($res));

        $data = $res['data'] ?? null;

        $body = [
            'status'  => (bool)($res['ok'] ?? false),
            'message' => $res['message'] ?? '',
            'data'    => $data,
            'errors'  => $res['errors'] ?? null,
            'meta'    => [
                'timezone'    => 'Asia/Jakarta',
                'server_time' => date('Y-m-d H:i:s'),
            ],
        ];

        // Redundant supaya aman walaupun frontend membaca root response.
        if (is_array($data)) {
            if (!empty($data['je_id'])) {
                $body['je_id'] = $data['je_id'];
                $body['id']    = $data['je_id'];
            } elseif (!empty($data['id'])) {
                $body['je_id'] = $data['id'];
                $body['id']    = $data['id'];
            }
        }

        return $this->output
            ->set_status_header((int)($res['http'] ?? 200))
            ->set_content_type('application/json')
            ->set_output(json_encode($body));
    }

    /**
     * GET /api/finance/journal-entries/{id}
     */
    public function show($id = null)
    {
        return $this->show_get($id);
    }

    public function show_get($id = null)
    {
        log_message('error', 'DEBUG JE show_get HIT id=' . $id);

        if (!$id || !is_numeric($id)) {
            return $this->_json(false, 'Invalid Journal Entry ID', null, [
                'je_id' => 'invalid',
            ], 400);
        }

        $res = $this->mje->get((int)$id);

        return $this->_json(
            $res['ok'] ?? false,
            $res['message'] ?? '',
            $res['data'] ?? null,
            $res['errors'] ?? null,
            $res['http'] ?? 200
        );
    }

    /**
     * PUT /api/finance/journal-entries/{id}
     */
    public function put($id = null)
    {
        return $this->update_put($id);
    }

    public function update($id = null)
    {
        return $this->update_put($id);
    }

    public function update_put($id = null)
    {
        log_message('error', 'DEBUG JE update_put HIT id=' . $id);

        if (!$id || !is_numeric($id)) {
            return $this->_json(false, 'Invalid Journal Entry ID', null, [
                'je_id' => 'invalid',
            ], 400);
        }

        $payload = $this->_json_payload();

        $res = $this->mje->update_draft((int)$id, $payload);

        return $this->_json(
            $res['ok'] ?? false,
            $res['message'] ?? '',
            $res['data'] ?? null,
            $res['errors'] ?? null,
            $res['http'] ?? 200
        );
    }

    /**
     * POST /api/finance/journal-entries/{id}/submit
     */
    public function submit($id = null)
    {
        return $this->submit_post($id);
    }

    public function submit_post($id = null)
    {
        log_message('error', 'DEBUG JE submit_post HIT id=' . $id);

        if (!$id || !is_numeric($id)) {
            return $this->_json(false, 'Invalid Journal Entry ID', null, [
                'je_id' => 'invalid',
            ], 400);
        }

        $res = $this->mje->submit((int)$id);

        return $this->_json(
            $res['ok'] ?? false,
            $res['message'] ?? '',
            $res['data'] ?? null,
            $res['errors'] ?? null,
            $res['http'] ?? 200
        );
    }

    /**
     * POST /api/finance/journal-entries/{id}/cancel
     */
    public function cancel($id = null)
    {
        return $this->cancel_post($id);
    }

    public function cancel_post($id = null)
    {
        log_message('error', 'DEBUG JE cancel_post HIT id=' . $id);

        if (!$id || !is_numeric($id)) {
            return $this->_json(false, 'Invalid Journal Entry ID', null, [
                'je_id' => 'invalid',
            ], 400);
        }

        $res = $this->mje->cancel((int)$id);

        return $this->_json(
            $res['ok'] ?? false,
            $res['message'] ?? '',
            $res['data'] ?? null,
            $res['errors'] ?? null,
            $res['http'] ?? 200
        );
    }

    private function _json_payload()
    {
        $raw = $this->input->raw_input_stream;
        $payload = json_decode($raw, true);

        if (!is_array($payload)) {
            return [];
        }

        return $payload;
    }

    protected function _json($ok, $message, $data = null, $errors = null, $http = 200)
    {
        return $this->output
            ->set_status_header((int)$http)
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status'  => (bool)$ok,
                'message' => $message,
                'data'    => $data,
                'errors'  => $errors,
                'meta'    => [
                    'timezone'    => 'Asia/Jakarta',
                    'server_time' => date('Y-m-d H:i:s'),
                ],
            ]));
    }
}