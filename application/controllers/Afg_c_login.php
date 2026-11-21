<?php
class Afg_c_login extends CI_Controller {	
	var $title = 'Login';	
	
	public function __construct(){
    	parent::__construct();
		$this->load->model('m_login');
   }
    
    
   function cek_session($user_id,$fullname,$email,$regionId,$level,$username)	{
		echo $user_id.' '.$fullname.' '.$email.' '.$regionId.' '.$level.' '.$username;
	}
	
	function index(){
		if ($this->session->userdata('region_id') == TRUE){
 			redirect('afg_c_stuffing_plan','refresh');
 		} else {
			$cap = $this->proses_captcha();
			$data=array(
				'image_cap'=> $cap['image'],
				'cap_word'=>$cap['word'],
				'ipaddr' => isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'],
			);		
			$this->load->view('v_login',$data);
		}
	}	
	
	function proses_captcha() {
 		$this->load->helper('captcha');
		$vals = array(
                	'img_path'	 => './public/images/captcha/',
                	'img_url'	 => base_url().'public/images/captcha/',
		    			'font_path'	 => './public/images/captcha/font/segoeprb.ttf',
                	'img_width'	 => '100',
                	'img_height' => 40,
                	'border' => 2, 
                	'expiration' => 1800
		);
		$cap = create_captcha($vals);
		$this->session->set_userdata('word', $cap['word']);
		return $cap;
	}
	
	function recreate_captcha() {
 		$cap = $this->proses_captcha();
 		$value = array(
 			'image_cap'=> $cap['image'],
 		);
 		echo json_encode($value);
 	}
	
	public function captcha_check(){
		if ($this->input->post('word') != $this->session->userdata('word')){
			$this->form_validation->set_message('captcha_check', 'Wrong captcha word');
			return FALSE;
		}else{
			return TRUE;
		}
	}
	
	function process_login() {
		
		$this->load->library('form_validation');		
		$data = array('title'=>$this->title, 'menu_navigation'=>'afg_shipper_nav' ,'form_action'=>site_url('afg_c_login/process_login'));		
		$this->form_validation->set_rules('username', 'Username', 'required|callback_name_check');
		$this->form_validation->set_rules('password', 'Password', 'required');
		//$this->form_validation->set_rules('word', 'Captcha', 'required|callback_captcha_check');
		$this->form_validation->set_error_delimiters('', '');
						
		if ($this->form_validation->run() == TRUE) {			
			$username = $this->input->post('username');			
			$pswd = $this->input->post('password');
			$password = md5($pswd);
			
			if ($this->m_login->check_user($username, $password) == TRUE)	{	
					$user_id = $this->m_login->getInfoAdmin($username)->user_id;		
      	$fullname = $this->m_login->getInfoAdmin($username)->fullname;
					$email = $this->m_login->getInfoAdmin($username)->email;	
					$regionId = $this->m_login->getInfoAdmin($username)->region_id;
					$level = $this->m_login->getInfoAdmin($username)->level;
					$data = array(
					'user_id'=>$user_id,
					'username' => $username, 
					'fullname' => $fullname , 
					'email' => $email, 
					'region_id' => $regionId,
					'level'	=> $level,
					'login' => TRUE,
					
				);	
					$this->session->set_userdata($data);				
					$value = array(
					'status'=>2,
					'level'=>$level,
				);			
				echo json_encode($value);
			} else {	
				$value = array(
				'status'=>1,
				'err_username'=>form_error('username'),
				'err_password'=>form_error('password'),
				'err_word'=>form_error('word'),
				'capt'=>$this->session->userdata('word'),
			);		
				echo json_encode($value);
			}		
		} else {	
			$value = array(
				'status'=>0,
				'err_username'=>form_error('username'),
				'err_password'=>form_error('password'),
				'err_word'=>form_error('word'),
				'capt'=>$this->session->userdata('word'),
			);			
			echo json_encode($value);		
		}	
	}
	
	function process_logout(){
		$this->session->sess_destroy();
		header("location:../");
	}
	
	public function name_check(){
		$username = $this->input->post('username');
		$this->load->model('user_model');
		$data = $this->user_model->check_username($username);
		if ($data != 1 ){
			$this->form_validation->set_message('name_check', 'No User " '.$username.' " in database');
			return FALSE;
		}else{
			return TRUE;
		}
	}
	
	
}