<?php
use chriskacerguis\RestServer\RestController;
 
class Update_gcomet_status extends RestController{
      
    public function __construct(){
        parent::__construct();
        $this->load->model('api/m_api');
        $this->dbx = $this->m_api->dbx;
    }

  public function index_get(){
        $tracking_numbers = [];
		$query = $this->dbx->select('mother_bl')->where('id_gocomet !=', '')->get('trnstuffingplan');
	if ($query->num_rows() > 0) {
		foreach ($query->result() as $row) {
			$tracking_numbers[] = $row->mother_bl;
			}
            // Initialize an array to hold all mother_bl values
			//print_r($tracking_numbers);
			//die();

            // Loop through the result set and add each mother_bl to the array
            $mother_bl_query_string = '';
			foreach ($tracking_numbers as $tracking_number) {
            $mother_bl_query_string .= 'tracking_numbers[]=' . urlencode($tracking_number) . '&';
			}

        // Remove the last '&' from the query string
        $mother_bl_query_string = rtrim($mother_bl_query_string, '&');

            // Use http_build_query to format as tracking_numbers[]=value&tracking_numbers[]=value2...
            //
			$token = $this->dbx->get('tokens')->row()->access_token;
			$api_url = "https://tracking.gocomet.com/api/v1/integrations/live-tracking";

			// Initialize Guzzle client
			$client = new \GuzzleHttp\Client();

        try {
            // Send POST request using Guzzle
             $response = $client->get($api_url, [
                'body' => $mother_bl_query_string . '&token=' . urlencode($token),  // Send query string as the body
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded'  // Set content type to form-urlencoded
                ]
            ]);

            //print_r($response);
			//die();
            $response_body = $response->getBody()->getContents();

            // Display the response (for debugging or further processing)
           $this->response($response_body);
        } catch (Exception $e) {
            // Handle errors
            echo "Error: " . $e->getMessage();
			}
        } else {
            echo "No data found";
        }
		//print_r($this->dbx->last_query());
		//die();
    }
}
?>