<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_acquisition_costs extends CI_Model
{
    /** @var CI_DB_query_builder */
    public $dbx;

    private $tbl = 'crm_acquisition_costs';
    private $pk  = 'cost_id';

    public function __construct()
    {
        parent::__construct();
        $this->dbx = $this->load->database('master', TRUE);
    }

    public function upsertFromSalesActivity($activityId, $payload)
    {
        $activityId = (int)$activityId;

        if ($activityId <= 0) {
            return [
                'status' => false,
                'message' => 'Invalid activity ID',
            ];
        }

        $hasCost = !empty($payload['has_acquisition_cost']);
        $amount  = isset($payload['cost_amount']) ? (float)$payload['cost_amount'] : 0;

        $existing = $this->dbx
            ->where('source_module', 'SALES_ACTIVITY')
            ->where('source_id', $activityId)
            ->where('deleted_at IS NULL', null, false)
            ->get($this->tbl)
            ->row_array();

        /**
         * Jika user uncheck cost, maka existing cost dicancel.
         */
        if (!$hasCost || $amount <= 0) {
            if ($existing) {
                $this->dbx
                    ->where($this->pk, (int)$existing[$this->pk])
                    ->update($this->tbl, [
                        'status'     => 'CANCELLED',
                        'deleted_at' => date('Y-m-d H:i:s'),
                        'deleted_by' => $payload['updated_by'] ?? $payload['created_by'] ?? null,
                        'updated_by' => $payload['updated_by'] ?? $payload['created_by'] ?? null,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
            }

            return [
                'status' => true,
                'message' => 'No acquisition cost',
            ];
        }

        $currency = !empty($payload['cost_currency'])
            ? strtoupper(trim((string)$payload['cost_currency']))
            : 'IDR';

        $rate = isset($payload['cost_rate_to_idr'])
            ? (float)$payload['cost_rate_to_idr']
            : 1;

        if ($rate <= 0) {
            $rate = 1;
        }

        $amountIdr = $amount * $rate;

        $costDate = !empty($payload['activity_date'])
            ? $payload['activity_date']
            : date('Y-m-d');
		$periodMonth = date('Y-m', strtotime($costDate));
        $data = [
            'company_id'       => $payload['company_id'] ?? 1,

            'source_module'    => 'SALES_ACTIVITY',
            'source_id'        => $activityId,

            'cost_date'        => $costDate,

            'cost_scope'       => 'SALES',
            'allocation_method'=> 'DIRECT',

            'region_id'        => $payload['region_id'] ?? null,
            'sales_id'         => $payload['sales_id'] ?? $payload['owner_sales_id'] ?? null,

            'lead_source'      => $payload['lead_source'] ?? null,
            'campaign_name'    => null,
            'service_type'     => $payload['service_type'] ?? $payload['shipment_type'] ?? null,
            'industry'         => $payload['industry'] ?? null,

            'related_lead_id'        => $payload['lead_id'] ?? null,
            'related_opportunity_id' => $payload['opportunity_id'] ?? null,

            'cost_category'    => $payload['cost_category'] ?? 'Sales Activity',
            'cost_description' => $payload['cost_description'] ?? null,

            'currency'         => $currency,
            'amount'           => $amount,
            'rate_to_idr'      => $rate,
            'amount_idr'       => $amountIdr,
			'period_month' 	=> $periodMonth,

            /**
             * Untuk awal saya pakai SUBMITTED.
             * Kalau mau langsung masuk CAC Analysis, ubah jadi APPROVED.
             */
            'status'           => $payload['cost_status'] ?? 'SUBMITTED',

            'submitted_by'     => $payload['created_by'] ?? $payload['updated_by'] ?? null,
            'submitted_at'     => date('Y-m-d H:i:s'),

            'updated_by'       => $payload['updated_by'] ?? $payload['created_by'] ?? null,
            'updated_at'       => date('Y-m-d H:i:s'),
        ];

        if ($data['status'] === 'APPROVED') {
            $data['approved_by'] = $payload['created_by'] ?? $payload['updated_by'] ?? null;
            $data['approved_at'] = date('Y-m-d H:i:s');
        }

        if ($existing) {
            $this->dbx
                ->where($this->pk, (int)$existing[$this->pk])
                ->update($this->tbl, $data);

            return [
                'status' => true,
                'message' => 'Acquisition cost updated',
                'data' => [
                    'cost_id' => (int)$existing[$this->pk],
                ],
            ];
        }

        $data['created_by'] = $payload['created_by'] ?? $payload['updated_by'] ?? null;
        $data['created_at'] = date('Y-m-d H:i:s');

        $this->dbx->insert($this->tbl, $data);

        if ($this->dbx->affected_rows() < 1) {
            return [
                'status' => false,
                'message' => 'Failed to save acquisition cost',
            ];
        }

        return [
            'status' => true,
            'message' => 'Acquisition cost created',
            'data' => [
                'cost_id' => (int)$this->dbx->insert_id(),
            ],
        ];
    }

    public function cancelBySalesActivity($activityId, $userId = null)
    {
        $activityId = (int)$activityId;

        if ($activityId <= 0) {
            return [
                'status' => false,
                'message' => 'Invalid activity ID',
            ];
        }

        $this->dbx
            ->where('source_module', 'SALES_ACTIVITY')
            ->where('source_id', $activityId)
            ->where('deleted_at IS NULL', null, false)
            ->update($this->tbl, [
                'status'     => 'CANCELLED',
                'deleted_at' => date('Y-m-d H:i:s'),
                'deleted_by' => $userId,
                'updated_by' => $userId,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        return [
            'status' => true,
            'message' => 'Acquisition cost cancelled',
        ];
    }
}