<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Cron_notifications extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        // Pastikan hanya bisa via CLI
        if (!is_cli()) {
            show_error('Direct access not allowed', 403);
        }

        $this->load->model('api/crm/M_notifications', 'notif');
    }

    /**
     * php index.php cli/cron_notifications/task_due
     * Kirim notif: due today & overdue untuk task yang assigned ke sales
     */
    public function task_due()
    {
        $today = date('Y-m-d');
        $yyyymmdd = date('Ymd');
        $now = date('Y-m-d H:i:s');

        // Task yang masih aktif
        $active_status = ['OPEN','IN_PROGRESS','WAITING'];

        // 1) Due today (DATE(due_at)=today)
        $due_today = $this->db->select('task_id, title, due_at, assignee_sales_id, status')
            ->from('crm_tasks')
            ->where('deleted_at IS NULL', null, false)
            ->where_in('status', $active_status)
            ->where('assignee_sales_id IS NOT NULL', null, false)
            ->where('due_at IS NOT NULL', null, false)
            ->where('DATE(due_at) =', $today, false)
            ->get()->result_array();

        $sent_today = 0;
        foreach ($due_today as $t) {
            $user_id = (int)$t['assignee_sales_id'];
            $dedup_key = 'TASK_DUE_TODAY:'.$t['task_id'].':'.$yyyymmdd;

            $ok = $this->notif->push_dedup(
                $user_id,
                'Task Due Today',
                'Due today: '.$t['title'],
                '/app/tasks/'.$t['task_id'],
                'TASK',
                (int)$t['task_id'],
                'WARN',
                $dedup_key
            );
            if ($ok) $sent_today++;
        }

        // 2) Overdue (due_at < now)
        $overdue = $this->db->select('task_id, title, due_at, assignee_sales_id, status')
            ->from('crm_tasks')
            ->where('deleted_at IS NULL', null, false)
            ->where_in('status', $active_status)
            ->where('assignee_sales_id IS NOT NULL', null, false)
            ->where('due_at IS NOT NULL', null, false)
            ->where('due_at <', $now)
            ->get()->result_array();

        $sent_overdue = 0;
        foreach ($overdue as $t) {
            $user_id = (int)$t['assignee_sales_id'];
            $dedup_key = 'TASK_OVERDUE:'.$t['task_id'].':'.$yyyymmdd;

            $ok = $this->notif->push_dedup(
                $user_id,
                'Task Overdue',
                'Overdue: '.$t['title'],
                '/app/tasks/'.$t['task_id'],
                'TASK',
                (int)$t['task_id'],
                'HIGH',
                $dedup_key
            );
            if ($ok) $sent_overdue++;
        }

        echo "OK. Sent due_today={$sent_today}, overdue={$sent_overdue}\n";
    }
}
