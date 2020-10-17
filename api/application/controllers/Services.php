<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Services extends CI_Controller {
	function __construct(){
		parent::__construct();
		/** Setting up timezone for India **/
		date_default_timezone_set('Asia/Kolkata');
		$this->db->query('SET SESSION time_zone = "+05:30"');
		/** Setting up sql_mode **/
		$this->db->query("SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION'");
		$postJson = file_get_contents("php://input");
		/* Checking if Request is in Json Format */
		if ($this->Admin_model->checkjson($postJson)) {
			/** Skipping Json Decode Request if from_app is true **/
			if (isset($_POST['from_app']) && $_POST['from_app'] == "true") { 
			} else {
				$_POST = json_decode(file_get_contents("php://input"), true);
			}
		}

		/* Getting Access Token */
		/* apiId ==    YzMxYjMyMzY0Y2UxOWNhOGZjZDE1MGE0MTdlY2NlNTg=    */
		$accessToken = base64_encode(md5("android"));
		$accessKey = $this->input->post("apiId");

		$function = $this->router->fetch_method();
			header("Access-Control-Allow-Headers: Authorization, Content-Type");
			header("Access-Control-Allow-Origin: *");
			header('content-type: application/json; charset=utf-8');
		    if (empty($accessKey)) {
		    $response['success'] = 0;
		    $response['message'] = "Failed to authenticate request.";
		    echo json_encode($response);
		    exit;
		    } else {
		    if ($accessKey != $accessToken) {
			    $response['success'] = 0;
			    $response['message'] = "Failed to authenticate request.";
			    echo json_encode($response);
			    exit;
		    }
		    }
	}
	// Login
	public function login($action){
		$actions = array("signin");
		$post = $this->input->post();
		if(in_array($action, $actions)){
			if($action == "signin"){
				if(isset($post['email']) && $post['email'] != ""){
					if(isset($post['password']) && $post['password'] != ""){
						$checkUser = $this->db->get_where("admin", array("email" => $post['email'], "password" => md5($post['password'])))->row_array();
						if(!empty($checkUser)){
								$user = $this->Admin_model->getSingleUserById($checkUser['admin_id']);
								$response['success'] = 1;
								$response['message'] = "";
								$response['admin']    = $user;
						} else {
							$response['success'] = 0;
							$response['message'] = "Incorrect email or password. Please try again.";
						}
					} else {
						$response['success'] = 0;
						$response['message'] = "Password can not be blank.";	
					}
				} else {
					$response['success'] = 0;
					$response['message'] = "Email can not be blank.";
				}
			}
			if($action == "details"){
				if(isset($post['user_id']) && $post['user_id'] != ""){
					$user = $this->db->query('select * from admin where admin_id = '.$post['user_id'])->row_array();
					if(!empty($user)){
						$user_details = $this->Admin_model->getSingleUserById($user['user_id']);
						$response['user'] = $user_details;
						$response['message'] = "";
						$response['success'] = 1;
					} else {
						$response['message'] = "Invalid user id";
						$response['success'] = 0;
					}
				} else {
					$response['message'] = "Please provide user id";
					$response['success'] = 0;
				}
			}
		} else {
			$response['message'] = "invalid Operation";
			$response['success'] = 0;
		}
		echo json_encode($response);
	}

	public function clean($string) {
	   $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
	   return preg_replace('/[^A-Za-z0-9\-.]/', '', $string); // Removes special chars.
	}
	//Car Model
	public function model($action){
		$actions = array("list", "save", "delete", "details");
		$post = $this->input->post();
		if(in_array($action, $actions)){
			$post = $this->input->post();

			if($action == "details"){
				$post = $this->input->post();
				if(isset($post['model_id']) && $post['model_id'] != ""){
					$modals = $this->Admin_model->getModelById($post['model_id']);
					if(!empty($modals)){

						$response['success'] = 1;
						$response['message'] = "Model found.";
						$response['data']    = "Model found.";
					} else {
						$response['success'] = 0;
						$response['message'] = "Model not found.";
					}
				} else {
					$response['success'] = 0;
					$response['message'] = "Model ID can not be blank.";
				}
			}
			if($action == "list"){	
				$modalList = $this->Admin_model->modelListing();
				if(!empty($modalList)){
					$response['success'] = 1;
					$response['message'] = "";
					$response['data']    = $modalList;
				} else {
					$response['success'] = 0;
					$response['message'] = "No data found";
				}
			}
			if($action == "save"){
				if(isset($post['name']) && $post['name'] != ""){
					if(isset($post['start_price']) && $post['start_price'] != ""){
						if(isset($post['end_price']) && $post['end_price'] != ""){
							$data['name'] 			 = $post['name'];
							$data['start_price'] = $post['start_price'];
							$data['end_price']   = $post['end_price'];
							if(isset($post['model_id']) && $post['model_id'] != ""){
								$this->db->where("model_id", $post['model_id']);
								$update = $this->db->update("car_modal", $data);	
								if($update){
									$response['success'] = 1;
									$response['message'] = "Modal has been updated.";
								} else {
									$response['success'] = 0;
									$response['message'] = "Opps.. Something went wrong. Please try again.";
								}
							} else {
								$data["timestamp"] = time();
								$save = $this->db->insert("car_modal", $data);	
								if($save){
									$response['success'] = 1;
									$response['message'] = "Modal has been saved.";
								} else {
									$response['success'] = 0;
									$response['message'] = "Opps.. Something went wrong. Please try again.";
								}	
							}

						}else{
							$response['success'] = 0;
							$response['message'] = "End price can not be blank.";
						}
					}else{
						$response['success'] = 0;
						$response['message'] = "start price can not be blank.";
					}
				}else{
					$response['success'] = 0;
					$response['message'] = "name can not be blank.";
				}
			}
			if($action == "delete"){
				$this->db->where("model_id", $post['model_id']);
				$delete = $this->db->delete("car_modal", $data);
				if(!empty($delete)){
					$response['success'] = 1;
					$response['message'] = "model has been deleted!";
				} else {
					$response['success'] = 0;
					$response['message'] = "Opps.. Something went wrong. Please try again.";
				}
			}
		} else {
			$response['success'] = 0;
			$response['message'] = "Invalid Operation.";
		}
		echo json_encode($response);
	}
	//Car Model
	//Car Gallery
	public function gallery($action){
		$actions = array("list", "save", "delete", "details");
		$post = $this->input->post();
		if(in_array($action, $actions)){
			$post = $this->input->post();

			if($action == "details"){
				$post = $this->input->post();
				if(isset($post['model_id']) && $post['model_id'] != ""){
					$modals = $this->Admin_model->getModelById($post['model_id']);
					if(!empty($modals)){

						$response['success'] = 1;
						$response['message'] = "Model found.";
						$response['data']    = "Model found.";
					} else {
						$response['success'] = 0;
						$response['message'] = "Model not found.";
					}
				} else {
					$response['success'] = 0;
					$response['message'] = "Model ID can not be blank.";
				}
			}
			if($action == "list"){	
				$modalList = $this->Admin_model->modelListing();
				if(!empty($modalList)){
					$response['success'] = 1;
					$response['message'] = "";
					$response['data']    = $modalList;
				} else {
					$response['success'] = 0;
					$response['message'] = "No data found";
				}
			}
			if($action == "save"){
				if(isset($post['name']) && $post['name'] != ""){
					if(isset($post['start_price']) && $post['start_price'] != ""){
						if(isset($post['end_price']) && $post['end_price'] != ""){
							$data['name'] 			 = $post['name'];
							$data['start_price'] = $post['start_price'];
							$data['end_price']   = $post['end_price'];
							if(isset($post['model_id']) && $post['model_id'] != ""){
								$this->db->where("model_id", $post['model_id']);
								$update = $this->db->update("car_modal", $data);	
								if($update){
									$response['success'] = 1;
									$response['message'] = "Modal has been updated.";
								} else {
									$response['success'] = 0;
									$response['message'] = "Opps.. Something went wrong. Please try again.";
								}
							} else {
								$data["timestamp"] = time();
								$save = $this->db->insert("car_modal", $data);	
								if($save){
									$response['success'] = 1;
									$response['message'] = "Modal has been saved.";
								} else {
									$response['success'] = 0;
									$response['message'] = "Opps.. Something went wrong. Please try again.";
								}	
							}

						}else{
							$response['success'] = 0;
							$response['message'] = "End price can not be blank.";
						}
					}else{
						$response['success'] = 0;
						$response['message'] = "start price can not be blank.";
					}
				}else{
					$response['success'] = 0;
					$response['message'] = "name can not be blank.";
				}
			}
			if($action == "delete"){
				$this->db->where("model_id", $post['model_id']);
				$delete = $this->db->delete("car_modal", $data);
				if(!empty($delete)){
					$response['success'] = 1;
					$response['message'] = "model has been deleted!";
				} else {
					$response['success'] = 0;
					$response['message'] = "Opps.. Something went wrong. Please try again.";
				}
			}
		} else {
			$response['success'] = 0;
			$response['message'] = "Invalid Operation.";
		}
		echo json_encode($response);
	}
	//Car Gallery
}
