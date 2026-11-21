<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_workbench extends CI_Model
{
    public function summary($sales_user_id)
    {
        $today = date('Y-m-d');
        $now = date('Y-m-d H:i:s');
        $active_status = ['OPEN','IN_PROGRESS','WAITING'];

        // My tasks
        $my_open = (int)$this->db->from('crm_tasks')
            ->where('deleted_at IS NULL', null, false)
            ->where('assignee_sales_id', $sales_user_id)
            ->where_in('status', $active_status)
            ->count_all_results();

        $my_due_today = (int)$this->db->from('crm_tasks')
            ->where('deleted_at IS NULL', null, false)
            ->where('assignee_sales_id', $sales_user_id)
            ->where_in('status', $active_status)
            ->where('due_at IS NOT NULL', null, false)
            ->where('DATE(due_at) =', $today, false)
            ->count_all_results();

        $my_overdue = (int)$this->db->from('crm_tasks')
            ->where('deleted_at IS NULL', null, false)
            ->where('assignee_sales_id', $sales_user_id)
            ->where_in('status', $active_status)
            ->where('due_at IS NOT NULL', null, false)
            ->where('due_at <', $now)
            ->count_all_results();

        // Watched tasks (sales tahu complaint/task team)
        $watched_open = (int)$this->db->from('crm_task_watchers w')
            ->join('crm_tasks t', 't.task_id = w.task_id', 'inner')
            ->where('w.sales_user_id', $sales_user_id)
            ->where('t.deleted_at IS NULL', null, false)
            ->where_in('t.status', $active_status)
            ->count_all_results();

        // Unread notifications
        $unread_notif = (int)$this->db->from('crm_notifications')
            ->where('user_id', $sales_user_id)
            ->where('is_read', 0)
            ->count_all_results();

        return [
            'my_open' => $my_open,
            'my_due_today' => $my_due_today,
            'my_overdue' => $my_overdue,
            'watched_open' => $watched_open,
            'unread_notifications' => $unread_notif,
        ];
    }
}
