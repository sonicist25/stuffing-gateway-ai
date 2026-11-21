<?php
class SupportTicket extends CI_Controller {
    
    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->model('SupportTicket_model');
    }

    public function index()
    {
		$data['title'] = 'IT Helpdesk';
		$data['menu_navigation'] = 'navigation';
		$data['main_view'] = 'support_ticket';	
		$this->load->view('afg_template', $data);
    }

public function ajax_list()
{
    $page = $this->input->get('page');
    $limit = $this->input->get('rows');
    $sidx = $this->input->get('sidx');
    $sord = $this->input->get('sord');
    $search = $this->input->get('search');

    if (!$sidx) $sidx = 'id';
    if (!$sord) $sord = 'asc';

    $start = $limit * $page - $limit; // calculate start index

    $data = $this->SupportTicket_model->get_tickets($limit, $start, $sidx, $sord, $search);

    $count = $data['count'];
    $tickets = $data['results'];

    $total_pages = $count > 0 ? ceil($count / $limit) : 0;

    $response = new stdClass();
    $response->page = $page;
    $response->total = $total_pages;
    $response->records = $count;
    $response->rows = [];

    foreach ($tickets as $ticket) {
        $response->rows[] = [
            'id' => $ticket->id,
            'username' => $ticket->username,
            'description' => $ticket->description,
            'action' => $ticket->action,
            'action_date' => $ticket->action_date,
            'finish_date' => $ticket->complete,
            'status' => $ticket->status,
            'oleh' => $ticket->oleh,
            'created_at' => $ticket->created_at
        ];
    }

    echo json_encode($response);
}


    public function ajax_add()
    {
        $data = [
            'username' => $this->session->userdata('fullname'),
            'description' => $this->input->post('description'),
            'status' => $this->input->post('status')
        ];

        $insert_id = $this->SupportTicket_model->add_ticket($data);

        echo json_encode(['success' => $insert_id ? true : false]);
    }

    public function ajax_edit()
    {
        $id = $this->input->post('id');
        $data = [
            'action' => $this->input->post('action'),
            'action_date' => $this->input->post('action_date'),
            'complete' => $this->input->post('finish_date'),
            'oleh' => $this->session->userdata('fullname'),
            'status' => $this->input->post('status')
        ];

        $success = $this->SupportTicket_model->update_ticket($id, $data);
        //print_r($this->dbx->last_query());
        //die();
        echo json_encode(['success' => $success]);
    }

    public function ajax_delete()
    {
        $id = $this->input->post('id');
        $success = $this->SupportTicket_model->delete_ticket($id);

        echo json_encode(['success' => $success]);
    }
	
	public function ajax_get()
	{
    $id = $this->input->post('id');
    $ticket = $this->SupportTicket_model->get_ticket($id);
    if ($ticket) {
        echo json_encode($ticket);
    } else {
        echo json_encode(null);
    }
}
 
}

