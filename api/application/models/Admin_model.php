<?php
Class Admin_model extends CI_Model {
	public function user_Details($email) {
		function __construct()
	    {
	        parent::__construct();
	    }
	}
	public function checkjson(&$json)
   {
      $json = json_decode($json);
      return (json_last_error() === JSON_ERROR_NONE);
   }
	public function getSingleUserById($admin_id){
    $admin = $this->db->get_where("admin", array("admin_id" => $admin_id))->row_array();
    if(!empty($admin)){
       $result['admin_id']   = $admin_id;
       $result['name']       = $admin['uname'];
       $result['email']      = $admin['email'];
       return $result;
    }
    else{
       return false;
    }
  }
  public function getModelById($model_id){
    $model = $this->db->get_where("car_modal", array("model_id" => $model_id))->row_array();
    if(!empty($model)){
       $result['model_id']   	  = $model['model_id'];
       $result['name']       	  = $admin['name'];
       $result['start_price']   = $admin['start_price'];
       $result['end_price']     = $admin['end_price'];
       return $result;
    }
    else{
       return false;
    }
  }
  public function modelListing(){
		$response['modal'] = array();
		$modals = $this->db->query("select * from car_modal where 1 = 1 order by model_id desc")->result_array();
		if(!empty($modals)){
			$i = 0;
			foreach ($modals as $modal) {
				$response['modal'][$i]['model_id']    = $modal['model_id'];
				$response['modal'][$i]['name'] 		    = $modal['name'] ? $modal['name'] : "";	
				$response['modal'][$i]['start_price'] = $modal['start_price'] ? $modal['start_price'] : "";	
				$response['modal'][$i]['end_price']   = $modal['end_price'] ? $modal['end_price'] : "";	
				$i++;
			}
			return $response;
		} else {
			return false;
		}
  }
  public function getGalleryById($model_id){
    $model = $this->db->get_where("car_modal", array("model_id" => $model_id))->row_array();
    if(!empty($model)){
       $result['model_id']   	  = $model['model_id'];
       $result['name']       	  = $admin['name'];
       $result['start_price']   = $admin['start_price'];
       $result['end_price']     = $admin['end_price'];
       return $result;
    }
    else{
       return false;
    }
  }
	public function add_category($data){
		$data['name'] = $data['name'];
		$chackName = $this->db->get_where('categories',array('name'=>$data['name']))->row_array();
	    if($chackName == 0){      
	      $data['timestamp'] = time();
	      $insert = $this->db->insert('categories',$data);
	      if($insert){
	        $response_data = array('success'=>1,'message'=>'Category successfully created!');
	      }else{
	         $response_data = array('success'=>0,'message'=>'Please try again.');
	      }
	    }else{
	      $response_data = array('success'=>0,'message'=>'Category name already exist.');
	    }
	      return $response_data; 
	}

	public function update_category($data){
	    $chackName = $this->db->get_where('categories',array('name'=>$data['name']))->row_array();
	    if($chackName == 0){      
	      $set_data['name'] = $data['name'];
	    }else{
	      $response_data = array('success'=>0,'message'=>'Category name already exist.');
	    }   
	    $set_data['status']   = $data['status'];
	      $this->db->where('category_id', $data['category_id']);
	      $update = $this->db->update('categories', $set_data);
	      if($update){
	        $response_data = array('success'=>1,'message'=>'Category successfully updated!');
	      }else{
	         $response_data = array('success'=>0,'message'=>'Please try again.');
	      }
	      return $response_data; 
	  }
	public function add_product($data){
		$chackName = $this->db->get_where('products',array('name'=>$data['name']))->row_array();
	    if($chackName == 0){      
	      $data['created_at'] = time();
	      $product_slug = strtolower($data['name']);
	      $insert = $this->db->insert('products',$data);
	      $insertid = $this->db->insert_id();
	      if($insert){
	        $response_data = array('success'=>1,'message'=>'Product successfully insert!','insert_id'=>$insertid);
	      }else{
	         $response_data = array('success'=>0,'message'=>'Please try again.');
	      }
	    }else{
	      $response_data = array('success'=>0,'message'=>'Product name already exist.');
	    }
	      return $response_data; 
	}
	public function update_product($data){
		    $chackName = $this->db->get_where('products',array('name'=>$data['name']))->row_array();
		    if($chackName == 0){      
		      $set_data['name'] = $data['name'];
		    }else{
		      $response_data = array('success'=>0,'message'=>'Product name already exist.');
		    }   
		    if($data['image']!=''){
		    	$set_data['image']  = $data['image'];
		    	$imageName = $this->db->get_where("products",array("product_id"=>$data['product_id']))->row_array();
				$galleryfolder = FCPATH."uploads/product/";
				unlink($galleryfolder . $imageName['image']);
		    }
		    $set_data['price'] 		     = $data['price'];
		    $set_data['cat_id']     	 = $data['cat_id'];
		    $set_data['status'] 		 = $data['status'];
		    $this->db->where('product_id', $data['product_id']);
	      $update = $this->db->update('products', $set_data);
	      if($update){
	        $response_data = array('success'=>1,'message'=>'Product successfully updated!');
	      }else{
	         $response_data = array('success'=>0,'message'=>'Please try again.');
	      }
	      return $response_data; 
	  }

	public function add_client($data){
	      $data['timestamp'] = time();
	      $insert = $this->db->insert('clients',$data);
	      if($insert){
	        $response_data = array('success'=>1,'message'=>'Client successfully insert!');
	      }else{
	         $response_data = array('success'=>0,'message'=>'Please try again.');
	      }

	      return $response_data; 
	}
	public function update_client($data){   
		    if($data['image']!=''){
		    	$set_data['image_url']  = $data['image'];
		    	$imageName = $this->db->get_where("clients",array("img_id"=>$data['img_id']))->row_array();
				$galleryfolder = FCPATH."uploads/client/";
				unlink($galleryfolder . $imageName['image_url']);
		    }
		    $set_data['name'] 		 = $data['name'];
		    $set_data['status'] 		 = $data['status'];
		    $this->db->where('img_id', $data['img_id']);
	      $update = $this->db->update('clients', $set_data);
	      if($update){
	        $response_data = array('success'=>1,'message'=>'Client successfully updated!');
	      }else{
	         $response_data = array('success'=>0,'message'=>'Please try again.');
	      }
	      return $response_data; 
	  }
	public function add_setting($data){
	      $data['timestamp'] = time();
	      $insert = $this->db->insert('settings',$data);
	      if($insert){
	        $response_data = array('success'=>1,'message'=>'setting successfully insert!');
	      }else{
	         $response_data = array('success'=>0,'message'=>'Please try again.');
	      }

	      return $response_data; 
	}
	public function update_setting($data){   
	    if($data['image']!=''){
	    	$set_data['image']  = $data['image'];
	    	$imageName = $this->db->get_where("settings",array("id"=>$data['id']))->row_array();
			$galleryfolder = FCPATH."uploads/setting/";
			unlink($galleryfolder . $imageName['image']);
	    }
	    $set_data['value'] 		 = $data['value'];
	    $set_data['status'] 	 = $data['status'];
	    $this->db->where('id', $data['id']);
      $update = $this->db->update('settings', $set_data);
      if($update){
        $response_data = array('success'=>1,'message'=>'Settings successfully updated!');
      }else{
         $response_data = array('success'=>0,'message'=>'Please try again.');
      }
      return $response_data; 
  }
}
?>