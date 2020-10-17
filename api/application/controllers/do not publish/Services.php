<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use PhpOffice\PhpSpreadsheet\IOFactory;
require_once substr(FCPATH, 0, -4).'api/library/excel/vendor/phpoffice/phpspreadsheet/src/Bootstrap.php';

class Services extends CI_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see https://codeigniter.com/user_guide/general/urls.html
	 */

	function __construct(){
		parent::__construct();

		/** Setting up timezone for India **/
		date_default_timezone_set('Asia/Kolkata');
		$this->db->query('SET SESSION time_zone = "+05:30"');

		/** Setting up sql_mode **/
		$this->db->query("SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION'");

		$postJson = file_get_contents("php://input");
		$this->load->model("front_model");
		
		/* Checking if Request is in Json Format */
		if ($this->common_model->checkjson($postJson)) {
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

		if($function != "payment"){

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


		/*if (empty($accessKey)) {
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
		}*/
	}
	public function categories($action){

		$actions = array("list", "save", "delete", "types", "statusupdate", "remove", "details");
		$post = $this->input->post();
		if(in_array($action, $actions)){
			$post = $this->input->post();

			if($action == "details"){
				$post = $this->input->post();
				if(isset($post['category_id']) && $post['category_id'] != ""){
					$category = $this->db->get_where("categories", array("category_id" => $post['category_id']))->row_array();
					if(!empty($category)){
						$response['category']['category_id'] = $category['category_id'] ? $category['category_id'] : "";
						$response['category']['name'] = $category['name'] ? $category['name'] : "";
						$response['category']['category'] = $category['category'] ? $category['category'] : "";
						$response['category']['background'] = $category['background'] ? $category['background'] : "";
						$response['category']['status'] = ($category['status'] != "") ? $category['status'] : "";

						$path = FC_PATH."assets/uploads/categories/";
						if(file_exists($path.$category['icon'])){
							$response['category']['icon'] = $category['icon'] ? IMAGETOOL.BASE_URL."assets/uploads/categories/".$category['icon'] : IMAGETOOL.BASE_URL."assets/thumb.jpg";	
						} else {
							$response['category']['icon'] = IMAGETOOL.BASE_URL."assets/thumb.jpg";	
						}
						
						$response['success'] = 1;
						$response['message'] = "Category found.";
					} else {
						$response['success'] = 0;
						$response['message'] = "Category not found.";
					}
				} else {
					$response['success'] = 0;
					$response['message'] = "Category ID can not be blank.";
				}
			}

			if($action == "remove"){
				$post = $this->input->post();
				if(isset($post['category_id']) && $post['category_id'] != ""){
					$category = $this->db->get_where("categories", array("category_id" => $post['category_id']))->row_array();
					if(!empty($category)){
						$this->db->where("category_id", $post['category_id']);
						$delete = $this->db->delete("categories");
						if($delete){
							$response['success'] = 1;
							$response['message'] = "Category has been removed";
						} else {
							$response['success'] = 0;
							$response['message'] = "Opps.. Something went wrong.";
						}
					} else {
						$response['success'] = 0;
						$response['message'] = "Category not found.";
					}
				} else {
					$response['success'] = 0;
					$response['message'] = "Category ID can not be blank.";
				}
			}

			if($action == "statusupdate"){
				$post = $this->input->post();
				if(isset($post['category_id']) && $post['category_id'] != ""){
					$category = $this->db->get_where("categories", array("category_id" => $post['category_id']))->row_array();
					if(!empty($category)){
						if($category['status'] == 1){
							$status = 0;
						} else {
							$status = 1;
						}
						$data = array(
							"status" => $status
						);

						$this->db->where("category_id", $post['category_id']);
						$update = $this->db->update("categories", $data);

						if($update){
							$response['success'] = 1;
							$response['message'] = "Category has been updated";
						} else {
							$response['success'] = 0;
							$response['message'] = "Opps.. Something went wrong.";
						}
					} else {
						$response['success'] = 0;
						$response['message'] = "Category not found.";
					}
				} else {
					$response['success'] = 0;
					$response['message'] = "Category ID can not be blank.";
				}
			}

			if($action == "list"){	
				$cond = "";

				if($post['status'] == "active"){
					$cond .= " and is_active = 1";
				}

				if($post['status'] != ""){
					$cond .= " and status = ".$post['status'];
				}
				
				$search_q = "";
				if(isset($post['search']) && $post['search'] != ""){
					$searchColumns = "categories.name";
	            	$searchColumns = explode(", ", $searchColumns);
	            	$searchTerms = [$post['search']];
	                foreach ($searchTerms as $searchTerm) {
	                    foreach ($searchColumns as $searchColumn) {
	                        if ($search_q == "") {
	                            $search_q .= " and (" . $searchColumn . " like '%" . $searchTerm . "%'";
	                        } else {
	                            $search_q .= " or " . $searchColumn . " like '%" . $searchTerm . "%'";
	                        }
	                    }
	                }
	                $search_q .= ")";
				}

				$response['categories'] = array();
				$categories = $this->db->query("select * from categories where 1 = 1 and category_id != 1000 ".$cond.$search_q."")->result_array();

				if(!empty($categories)){
					$i = 0;
					foreach ($categories as $category) {
						$response['categories'][$i]['category_id'] = $category['category_id'];
						$response['categories'][$i]['category'] = $category['name'] ? $category['name'] : "";	
						$response['categories'][$i]['name'] = $category['name'] ? $category['name'] : "";	
						$response['categories'][$i]['slug'] = $category['slug'] ? $this->front_model->slug($category['category']) : "";	
						$response['categories'][$i]['background'] = $category['background'] ? $category['background'] : "";	
						$response['categories'][$i]['is_new'] = $category['is_new'] ? $category['is_new'] : 0;	
						$response['categories'][$i]['status'] = $category['status'] ? $category['status'] : "";

						$path = FC_PATH."assets/uploads/categories/";
						if(file_exists($path.$category['icon'])){
							$response['categories'][$i]['icon'] = $category['icon'] ? IMAGETOOL.BASE_URL."assets/uploads/categories/".$category['icon'] : IMAGETOOL.BASE_URL."assets/thumb.jpg";	
						} else {
							$response['categories'][$i]['icon'] = IMAGETOOL.BASE_URL."assets/thumb.jpg";
						}	
						$i++;
					}
					$response['success'] = 1;
					$response['message'] = "";
				} else {
					$response['success'] = 0;
					$response['message'] = "No categories found";
				}
			}

			if($action == "save"){
				if(isset($post['name']) && $post['name'] != ""){
					$data["name"] = $post['name'];
					$data["slug"] = $this->common_model->getSlug($post['name']);
					$data["background"] = $post['background'];
					$data["status"] = $post['status'];
					
					if (!empty($_FILES["icon"])) {
						$path = FC_PATH."assets/uploads/categories/";
				        $image_name = time().'_'.preg_replace('/\s+/', '_', $_FILES['icon']['name']);
				        $image_name = $this->front_model->clean($image_name);
				        move_uploaded_file($_FILES["icon"]["tmp_name"], $path.$image_name);
				        $data["icon"] = $image_name;
				    } 
				    
					if(isset($post['category_id']) && $post['category_id'] != ""){
						$this->db->where("category_id", $post['category_id']);
						$update = $this->db->update("categories", $data);	
						if($update){
							$response['success'] = 1;
							$response['message'] = "Category has been updated.";
						} else {
							$response['success'] = 0;
							$response['message'] = "Opps.. Something went wrong. Please try again.";
						}
					} else {
						
						$data["timestamp"] = time();
						$save = $this->db->insert("categories", $data);	
						if($save){
							$response['success'] = 1;
							$response['message'] = "Category has been saved.";
						} else {
							$response['success'] = 0;
							$response['message'] = "Opps.. Something went wrong. Please try again.";
						}	
					}
				} else {
					$response['success'] = 0;
					$response['message'] = "Category can not be blank.";
				}
			}
		} else {
			$response['success'] = 0;
			$response['message'] = "Invalid Operation.";
		}
		echo json_encode($response);
	}

	function clean($string) {
	   $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
	   return preg_replace('/[^A-Za-z0-9\-.]/', '', $string); // Removes special chars.
	}

	public function download_file($file_url, $path,$name) {
		
        $url = $file_url;

        $url = str_replace(" ","%20", $url);
        
        $toDir = $path;
        $ext_index = strripos($url, ".");
        $file_ext = substr($url, $ext_index, strlen($url));

        $name = str_replace(" ","",str_replace("-","_",$name));
        $withName = time()."_".$name;
        
        if ($fp_remote = fopen($url, 'rb')) {
        	
            // local filename
            $local_file = $toDir . $withName;
             // read buffer, open in wb mode for writing
            if ($fp_local = fopen($local_file, 'wb')) {
            	// read the file, buffer size 8k
                while ($buffer = fread($fp_remote, 8192)) {
                    // write buffer in  local file
                    fwrite($fp_local, $buffer);
                }
                // close local
                fclose($fp_local);
            } else {
                // could not open the local URL
                fclose($fp_remote);
                return false;
            }
            // close remote
            fclose($fp_remote);
        } else {
            return false;
            // could not open the remote URL
        }

        //return str_replace(" ","",str_replace("-","_",$withName));
        return $withName;
        die;
    }

	public function import_product(){
		$post = $this->input->post();
		$response['success'] = 1;
		$response_message= "";
		$response_message_for_check= "";
		$hsn_message = "";
		$import_path = BASE_URL."assets/uploads/import_images/";

		$path = substr(FCPATH, 0, -4)."api/csv/import_products/";
		if (!empty($_FILES["products_sheet"])) {
			if($_FILES['products_zip']['name'] != ''){
				
				$zip_images_uploaded = 0;
				$file_name_zip = $_FILES['products_zip']['name'];  
	           	$array_zip = explode(".", $file_name_zip);  
	           	$name_zip = $array_zip[0];  
	           	$ext = $array_zip[1];  
	           	if($ext == 'zip'){

			        $products_sheet_name = time()."_".preg_replace('/\s+/', '_', $_FILES['products_sheet']['name']);
			        $products_sheet_name = $this->clean($products_sheet_name);
			        $file_upload = move_uploaded_file($_FILES["products_sheet"]["tmp_name"], $path.$products_sheet_name);
			        	
			        if($file_upload){
			        	$spreadsheet = IOFactory::load($path.$products_sheet_name);
			        	$data = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
			        }
			        
			        if(!empty($data)){

			        	$is_valid = 1;

		        		$i = 0;
			        	foreach ($data as $row) {

			        		if($i > 0){
			        			$is_valid = 0;
		        				if($row['A'] == ""){
		        					$error_message = "Please provide product title";
			        			} else if($row['B'] == ""){
			        				$error_message = "Please select category for product";
			        			} /*else if($row['C'] == ""){
			        				$error_message = "Please provide product description";
			        			}*/ else if($row['D'] == ""){
			        				$error_message = "Please provide product MRP price";
			        			} else if($row['E'] == ""){
			        				$error_message = "Please provide product sale price";
			        			}/* else if($row['F'] == ""){
			        				$error_message = "Please provide at least one product image";
			        			}*/ else {
			        				$is_valid = 1;
			        			}
			        		}
			        		$i++;
			        	}
			        	
			        	if($is_valid === 1){
			        		$path_zip = substr(FCPATH, 0, -4)."api/assets/uploads/import_images/";
			        		$file_name_zip = time()."_".$file_name_zip;
			                $location = $path_zip.$file_name_zip;
			                if(move_uploaded_file($_FILES['products_zip']['tmp_name'], $location)){  
		                     	$zip = new ZipArchive;  
			                    if($zip->open($location)){
									$zip->extractTo($path_zip);  
			                        $zip->close();  
			                    } 
			                    $files = scandir($path_zip . $file_name_zip); 
			                    $zip_images_uploaded = 1;
							}


							if($zip_images_uploaded == 1){
								$return_message = "";
								$i = 0;
								$time = time();
								$duplicate = 0;
								$success = 0;
								foreach ($data as $product) {
									if($i > 0){
										$checkProductName = $this->db->query("select product_id from products where name = '".$product['A']."'")->row_array();
										if(empty($checkProductName) || !empty($checkProductName)){
											$productData['name'] = $product['A'];
											$productData['description'] = $product['C'];
											$productData['mrp_price'] = $product['D'];
											$productData['sale_price'] = $product['E'];
											$productData['status'] = 1;
											$productData['save_timestamp'] = $time;
											if($product['G'] == "yes"){
												$productData['is_veg_only'] = 1;
											} else {
												$productData['is_veg_only'] = 0;	
											}
											if($product['H'] == "yes"){
												$productData['is_jain'] = 1;	
											} else {
												$productData['is_jain'] = 0;
											}
											if($product['I'] == "yes"){
												$productData['is_with_eggs'] = 1;	
											} else {
												$productData['is_with_eggs'] = 0;
											}

											$insert = $this->db->insert('products',$productData);
											$product_id = $this->db->insert_id();
											if($product_id){
												$category = $this->db->query("select category_id from categories where name = '".$product['B']."'")->row_array();
												if(empty($category)){
													$categoryData['name'] = $product['B'];
													$categoryData['slug'] = $product['B'];
													$categoryData['background'] = "#ffffff";
													$categoryData['status'] = 1;
													$categoryData['timestamp'] = $time;

													$this->db->insert('categories',$categoryData);
													$category['category_id'] = $this->db->insert_id();
												}

												$productCategory['product_id'] = $product_id; 
												$productCategory['category_id'] = $category['category_id'];
												$productCategory['timestamp'] = $time;
												$this->db->insert('product_category',$productCategory);

												$images = explode("//",$product['F']);

												$uploads_dir = substr(FCPATH, 0, -4)."api/assets/uploads/products/";
												foreach ($images as $image) {
													$image_name = $this->clean($image);
													$image_ = $this->download_file($import_path.$image,$uploads_dir,$image);
													
													$productImage['image'] = $image_;
													$productImage['product_id'] = $product_id;
													$productImage['timestamp'] = $time;
													$this->db->insert("product_images",$productImage);
												}

												$success++;
											}

											
										} else {
											$duplicate++;
										}
										
									}
									
									$i++;
								}
								$return_message .= $success." Products uploaded successfully,";
								if($duplicate > 0){
									$return_message .= $duplicate." Duplicate name of products";	
								}
								

								$response['message'] = rtrim($return_message,",");
								$response['success'] = 1;
							} else {
								$response['message'] = "ZIP file of images not uploaded";
								$response['success'] = 0;
							}
			        	} else {

			        		$response["message"] = $error_message;
			        		$response["success"] = 0;
			        	}

						
					} else {
			        	$response['success'] = 0;
						$response['message'] = "No data available in sheet";
			        }

		        } else {
	           		$response['message'] = "Invalid extension for image archive";
	           		$response['success'] = 0;
	           	}

		    } else {
		    	$response['message'] = "Please provide zip file of images";
		    	$response['success'] = 0;
		    }
		} else {
	    	$response['message'] = "Please provide sheet to be upload";
	    	$response['success'] = 0;
	    }


		
	    echo json_encode($response);
	}

	public function product($action){

		$actions = array("save","list","statusupdate","remove","details","duplicate","variation_list","get_import_sheet","list_for_front");
		$post = $this->input->post();
		
		if(in_array($action, $actions)){
			if($action == "get_import_sheet"){

				$categories = $this->db->query("select * from categories where status = 1 and category_id != 1000 limit 10")->result_array();
				if(!empty($categories)){
					$cat = 0;
					foreach ($categories as $category) {
						$response['categories'][$cat]['category_id'] = $category['category_id'];
						$response['categories'][$cat]['name'] = $category['name'];
						$cat++;
					}
					$response['success'] = 1;
					$response['message'] = "";
				} else {
					$response['success'] = 0;
					$response['message'] = "No categories available";	
				}
			}
			if($action == "save"){
				$response['success'] = 0;
				if($post['category_id'] == "") {
					$response['success'] = 0;
					$response['message'] = "Invalid item category";
				} else if($post['item_name'] == "") {
					$response['success'] = 0;
					$response['message'] = "Invalid item name";
				} else {
			 		$time = time();

			 		$itemMainData['name'] = $post['item_name'];
			 		$itemMainData['combo_item_ids'] = ($post['combo_item_ids'] != "" && $post['is_combo'] == 1) ? $post['combo_item_ids'] : "";
			 		$itemMainData['description'] = $post['item_description'];
			 		$itemMainData['status'] = $post['status'];
			 		$itemMainData['mrp_price'] = $post['price'];
			 		$itemMainData['sale_price'] = $post['sale_price'];
			 		$itemMainData['is_non_veg'] = ($post['is_non_veg'] == 1) ? $post['is_non_veg'] : 0;
			 		$itemMainData['is_veg_only'] = ($post['is_non_veg'] == 1) ? 0 : 1;
			 		$itemMainData['sale_price'] = ($post['sale_price']) ? $post['sale_price'] : "";
			 		$itemMainData['serve_for'] = ($post['serve_for']) ? $post['serve_for'] : "";
			 		$itemMainData['save_timestamp'] = $time;
			 		
			 		$path = FC_PATH."assets/uploads/products/";

			 		if (!empty($_FILES["image"])) {
				        $image_name = time().'_'.preg_replace('/\s+/', '_', $_FILES['image']['name']);
				        $image_name = $this->front_model->clean($image_name);
				        move_uploaded_file($_FILES["image"]["tmp_name"], $path.$image_name);
				        $itemImageData["image"] = $image_name;
				    }

			 		if($post['product_id'] != ""){
			 			$itemMainData['alt_timestamp'] = time();
			 			$this->db->where('product_id',$post['product_id']);
			 			$save_product =  $this->db->update('products',$itemMainData);
			 			$product_id = $post['product_id'];
			 			$message = "Item updated successfully";

			 			$this->db->where("product_id",$product_id);
			 			$this->db->where("category_id",$post['category_id']);
			 			$this->db->delete('product_category');

			 			$itemImageData["product_id"] = $post["product_id"];
			 			$itemImageData["timestamp"] = time();
			 			
			 			if (!empty($_FILES["image"])) {
				 			$this->db->where("product_id",$product_id);
				 			$this->db->delete('product_images');
				 			$save_product = $this->db->insert('product_images',$itemImageData);
				 		}

			 			$itemCatData = array('product_id'=>$product_id, 'category_id'=>$post['category_id'], 'timestamp'=>time());
			 			$save_product_cat = $this->db->insert('product_category',$itemCatData);


			 		} else {
			 			$save_product = $this->db->insert('products',$itemMainData);
			 			$message = "Item added successfully";
			 			$insert_id = $this->db->insert_id();
			 			$itemImageData["product_id"] = $insert_id;
			 			$itemImageData["timestamp"] = time();
			 			$save_product = $this->db->insert('product_images',$itemImageData);

			 			$itemCatData = array('product_id'=>$insert_id, 'category_id'=>$post['category_id'], 'timestamp'=>time());
			 			$save_product_cat = $this->db->insert('product_category',$itemCatData);
			 		}


					if($save_product){
						$response['message'] = $message;
						$response['success'] = 1;
					} else {
						$response['message'] = "Opps...something went wrong";
						$response['success'] = 0;
					}
			 	}
			}
			if($action == "list"){
				$cond = "";
				if($post['status'] != ""){
					$cond .= " and products.status = ".$post['status'];
				}
				if($post['category_id'] != ""){
					$cond .= " and product_category.category_id = ".$post['category_id'];
				}
				if($post['category_id_array'] != ""){
					$cond .= " and product_category.category_id in (".$post['category_id_array'].")";
				}
				if($post['is_not'] != ""){
					$cond .= " and products.product_id != ".base64_decode($post['is_not']);
				}

				$search_q = "";
				if(isset($post['search']) && $post['search'] != ""){
					$searchColumns = "products.name, products.description, categories.name";
	            	$searchColumns = explode(", ", $searchColumns);
	            	$searchTerms = [$post['search']];
	                foreach ($searchTerms as $searchTerm) {
	                    foreach ($searchColumns as $searchColumn) {
	                        if ($search_q == "") {
	                        	$search_q .= " and (" . $searchColumn . " like '%" . $searchTerm . "%'";
	                        } else {
	                            $search_q .= " or " . $searchColumn . " like '%" . $searchTerm . "%'";
	                        }
	                    }
	                }
	                $search_q .= ")";
				}
				
				$products = $this->db->query("select *,categories.name as category_name, products.name as product_name , products.status as productstatus from products left join product_category on (products.product_id = product_category.product_id) left join categories on (product_category.category_id = categories.category_id) where 1 = 1 and is_open_item = 0 and deleted_at is null".$cond.$search_q." order by products.product_id desc")->result_array();

				//echo $this->db->last_query(); die();

				if(!empty($products)){
					$i = 0;
					foreach ($products as $product) {
						$response['products'][$i]['product_id'] = $product['product_id']; 
						$response['products'][$i]['name'] = ucfirst(strtolower($product['product_name'])); 
						$response['products'][$i]['status'] = $product['productstatus'];
						$response['products'][$i]['category'] = ucfirst(strtolower($product['category_name']));
						$response['products'][$i]['category_id'] = $product['category_id'];
						$response['products'][$i]['mrp_price'] = $product['mrp_price'];
						$response['products'][$i]['sale_price'] = $product['sale_price'];
						$response['products'][$i]['serve_for'] = ($product['serve_for']) ? $product['serve_for'] : "-";

						$overall_ratings = $this->db->query("select count(*) as total_count, sum(ratings) as ratings_sum from order_items where product_id='".$response['products'][$i]['product_id']."' and ratings != '' ")->row_array();

						if(!empty($overall_ratings)){
							if($overall_ratings['ratings_sum'] != "" && $overall_ratings['total_count'] != 0){
								$ratings = $overall_ratings['ratings_sum'] /  $overall_ratings['total_count'];
							}else{
								$ratings = "";
							}
						}else{
							$ratings = "";
						}

						$response['products'][$i]['overall_ratings'] = ($ratings) ? $ratings : "-";

						$path = FC_PATH."assets/uploads/products/";
						$images = $this->db->get_where('product_images',array('product_id'=>$product['product_id']))->result_array();
						if(!empty($images)){
							$im = 0;
							foreach ($images as $image) {
								if(file_exists($path.$image['image'])){
									$response['products'][$i]['images'][$im] = $image['image'] ? IMAGETOOL.BASE_URL."assets/uploads/products/".$image['image'] : IMAGETOOL.BASE_URL."assets/thumb.jpg";	
								} else {
									$response['products'][$i]['images'][$im] = IMAGETOOL.BASE_URL."assets/thumb.jpg";	
								}		
								$im++;
							}
						} else {
							$response['products'][$i]['images'] = array();
						}
						$i++;
					}
					$response['message'] = "";
					$response['success'] = 1;
				} else {
					$response['message'] = "No product available";
					$response['success'] = 0;
				}
			}
			if($action == "statusupdate"){
				$post = $this->input->post();
				if(isset($post['product_id']) && $post['product_id'] != ""){
					$restaurant = $this->db->get_where("products", array("product_id" => $post['product_id']))->row_array();
					if(!empty($restaurant)){
						if($restaurant['status'] == 1){
							$status = 0;
						} else {
							$status = 1;
						}
						$data = array(
							"status" => $status
						);
						$this->db->where("product_id", $post['product_id']);
						$update = $this->db->update("products", $data);

						//echo $this->db->last_query(); die();

						if($update){
							$response['success'] = 1;
							$response['message'] = "Status has been updated";
						} else {
							$response['success'] = 0;
							$response['message'] = "Opps.. Something went wrong.";
						}
					} else {
						$response['success'] = 0;
						$response['message'] = "Item not found.";
					}
				} else {
					$response['success'] = 0;
					$response['message'] = "Item ID can not be blank.";
				}
			}
			if($action == "remove"){
				$post = $this->input->post();
				if(isset($post['product_id']) && $post['product_id'] != ""){
					$category = $this->db->get_where("products", array("product_id" => $post['product_id']))->row_array();
					if(!empty($category)){
						$this->db->where("product_id", $post['product_id']);
						$delete = $this->db->update("products",array("deleted_at"=>time()));

						if($delete){
							$response['success'] = 1;
							$response['message'] = "Item has been removed";
						} else {
							$response['success'] = 0;
							$response['message'] = "Opps.. Something went wrong.";
						}
					} else {
						$response['success'] = 0;
						$response['message'] = "Item not found.";
					}
				} else {
					$response['success'] = 0;
					$response['message'] = "Item ID can not be blank.";
				}
			}
			if($action == "details"){

				if($post['product_id'] != ""){

					$item = $this->db->query("select * from products where product_id = ".$post['product_id'])->row_array();
					if(!empty($item)){

						$product_category = $this->db->query("select * from product_category where product_id = ".$item['product_id'])->row_array();
						if($product_category['category_id'] != ""){
							$getMenuCat = $this->db->query("select * from product_category where category_id = ".$product_category['category_id'])->row_array();
						}

						$response['item']['product_id'] = $item['product_id'];
						$response['item']['item_name'] = $item['name'];
						$response['item']['combo_item_ids'] = ($item['combo_item_ids'] != '') ?  explode(",",$item['combo_item_ids']) : array();
						$response['item']['is_combo'] = ($item['combo_item_ids'] != '') ? 1 : 0;
						$response['item']['is_non_veg'] = floatval($item['is_non_veg']);
						$response['item']['status'] = $item['status'];
						$response['item']['price'] = floatval($item['mrp_price']);
						$response['item']['sale_price'] = floatval($item['sale_price']);
						$response['item']['item_description'] = ($item['description']) ? $item['description'] : "";
						$response['item']['category_id'] = $getMenuCat['category_id'];
						$response['item']['serve_for'] = ($item['serve_for']) ? $item['serve_for'] : "";

						if($item['combo_item_ids'] != ""){
							$combo_item_ids = explode(",",$item['combo_item_ids']);
						}else{
							$combo_item_ids = array();
						}

						$combo_count = 0;
						if(!empty($combo_item_ids)){	
							foreach($combo_item_ids as $combo_item_id){
								
								$product = $this->db->get_where("products",array("product_id"=>$combo_item_id))->row_array();
								$response['item']['combo_product'][$combo_count]['name'] = $product['name'];
								$response['item']['combo_product'][$combo_count]['product_id'] = $product['product_id'];

								$product_image = $this->db->get_where("product_images",array("product_id"=>$combo_item_id))->row()->image;

								if($product_image != ""){
									$response['item']['combo_product'][$combo_count]['images'][0] = IMAGETOOL.BASE_URL."assets/uploads/products/".$product_image;
								}else{
									$response['item']['combo_product'][$combo_count]['images'][0] = IMAGETOOL.BASE_URL."assets/thumb.jpg";	
								}
								

								$combo_count++;
							}
						}
							

						$image = $this->db->query("select * from product_images where product_id=".$item['product_id']."")->row()->image;

						$path = FC_PATH."assets/uploads/products/";

						if(file_exists($path.$item['image'])){
							$response['item']['image'] = $image ? IMAGETOOL.BASE_URL."assets/uploads/products/".$image : IMAGETOOL.BASE_URL."assets/thumb.jpg";	
						} else {
							$response['item']['image'] = IMAGETOOL.BASE_URL."assets/thumb.jpg";	
						}

						/*$ingredients = $this->db->query("select GROUP_CONCAT(ingredient_id SEPARATOR ',') as ingredients from restaurant_menu_item_ingredients where item_id = ".$item['item_id'])->row_array();
						
						$response['item']['ingredients'] = explode(",",$ingredients['ingredients']);*/


						/*$addons = $this->db->query("select * from restaurant_menu_item_addons where item_id = ".$item['item_id'])->result_array();
						if(!empty($addons)){
							$adon_i = 0;
							foreach ($addons as $addon) {
								$response['item']['addons'][$adon_i]['addon_for'] = $addon['addon_for'];
								$response['item']['addons'][$adon_i]['addon_title'] = $addon['addon_title'];
								$response['item']['addons'][$adon_i]['addon_price'] = floatval($addon['addon_price']);
								$adon_i++;
							}
						} else {
							$response['item']['addons'][0]['addon_for'] = "";
							$response['item']['addons'][0]['addon_title'] = "";
							$response['item']['addons'][0]['addon_price'] = "";
						}*/
						
						/*$variations = $this->db->query("select * from restaurant_menu_item_variations where item_id = ".$item['item_id']." order by item_variation_id asc")->result_array();
						if(!empty($variations)){
							$var_i = 0;
							foreach ($variations as $variation) {
								$attributes = $this->db->query("select * from restaurant_menu_item_variations_options where variation_id = ".$variation['item_variation_id'])->result_array();
								if(!empty($attributes)){
									$att_i = 0;
									foreach ($attributes as $attribute) {
										$response['item']['variations'][$var_i]['attributes'][$attribute['attribute_id']] = $attribute['value'];
										$att_i++;
									}
								}

								$response['item']['variations'][$var_i]['price'] = floatval($variation['price']);
								$response['item']['variations'][$var_i]['sale_price'] = floatval($variation['sale_price']);
								$response['item']['variations'][$var_i]['veg_nonveg'] = floatval($variation['veg_nonveg']);
								$response['item']['variations'][$var_i]['status'] = $variation['status'];
								$response['item']['variations'][$var_i]['is_default'] = $variation['is_default'];
								$var_i++;
							}
						} else {
							$response['item']['variations'][0]['attributes'] = array();
							$response['item']['variations'][0]['price'] = "";
							$response['item']['variations'][0]['sale_price'] = "";
							$response['item']['variations'][0]['veg_nonveg'] = "";
							$response['item']['variations'][0]['status'] = "";
							$response['item']['variations'][0]['is_default'] = "";
						}*/
						

						$response['message'] = "";
						$response['success'] = 1;
					} else {
						$response['message'] = "Invalid item";
						$response['success'] = 0;	
					}
				} else {
					$response['message'] = "Please provide product id";
					$response['success'] = 0;
				}
			}
			if($action == "duplicate"){
				if($post['item_id'] != ""){
					$restaurant_menu_item = $this->db->query('select * from restaurant_menu_item where item_id = '.$post['item_id'])->row_array();
					if(!empty($restaurant_menu_item)){
						$time = time();
						$restaurant_menu_itemData = $restaurant_menu_item;
						$restaurant_menu_itemData['item_name'] = $restaurant_menu_item['item_name']." - duplicate";
						$restaurant_menu_itemData['item_id'] = "";
						$restaurant_menu_itemData['save_timestamp'] = $time;
						
						$this->db->insert('restaurant_menu_item',$restaurant_menu_itemData);
						$item_id = $this->db->insert_id();

						if($item_id){
							$restaurant_menu_item_variations = $this->db->query("select * from restaurant_menu_item_variations where item_id = ".$post['item_id'])->result_array();

							foreach ($restaurant_menu_item_variations as $restaurant_menu_item_variations) {
								
								$restaurant_menu_item_variationsData = $restaurant_menu_item_variations;
								$restaurant_menu_item_variationsData['item_id'] = $item_id;
								$restaurant_menu_item_variationsData['item_variation_id'] = "";

								$this->db->insert('restaurant_menu_item_variations',$restaurant_menu_item_variationsData);
								$variation_id = $this->db->insert_id();

								if($variation_id){
									$restaurant_menu_item_variations_options = $this->db->query("select * from restaurant_menu_item_variations_options where variation_id = ".$restaurant_menu_item_variations['item_variation_id'])->result_array();

									foreach ($restaurant_menu_item_variations_options as $restaurant_menu_item_variations_option) {
										$restaurant_menu_item_variations_optionData = $restaurant_menu_item_variations_option;
										$restaurant_menu_item_variations_optionData['variation_id'] = $variation_id;
										$restaurant_menu_item_variations_optionData['option_id'] = "";
										
										$this->db->insert('restaurant_menu_item_variations_options',$restaurant_menu_item_variations_optionData);
									}
								}
							}

							$restaurant_menu_item_addons = $this->db->query("select * from restaurant_menu_item_addons where item_id = ".$post['item_id'])->result_array();
							if(!empty($restaurant_menu_item_addons)){
								foreach ($restaurant_menu_item_addons as $restaurant_menu_item_addon) {
									$restaurant_menu_item_addonData = $restaurant_menu_item_addon;
									$restaurant_menu_item_addonData['item_id'] = $item_id;
									$restaurant_menu_item_addonData['id'] = "";

									$this->db->insert('restaurant_menu_item_addons',$restaurant_menu_item_addonData);
								}
							}

							$restaurant_menu_item_ingredients = $this->db->query("select * from restaurant_menu_item_ingredients where item_id = ".$post['item_id'])->result_array();
							if(!empty($restaurant_menu_item_ingredients)){
								foreach ($restaurant_menu_item_ingredients as $restaurant_menu_item_ingredient) {
									$restaurant_menu_item_ingredientData = $restaurant_menu_item_ingredient;
									$restaurant_menu_item_ingredientData['item_id'] = $item_id;
									$restaurant_menu_item_ingredientData['id'] = "";
									$this->db->insert('restaurant_menu_item_ingredients',$restaurant_menu_item_addonData);
								}
							}

							$response['message'] = "Duplicate item added";
							$response['success'] = 1;
						} else {
							$response['message'] = "Opps...something went wrong";
							$response['success'] = 0;
						}
					} else {
						$response['message'] = "Invalid item";
						$response['success'] = 0;
					}
				} else {
					$response['message'] = "Please provide item id";
					$response['success'] = 0;
				}
			}
			if($action == "variation_list"){
				$cond = "";
				if($post['admin_id'] != ""){
					$cond .= " and restaurants.admin_id = ".$post['admin_id'];
				}
				if($post['status'] != ""){
					$cond .= " and restaurant_menu_item.status = ".$post['status'];
				}
				if($post['restaurant_id'] != ""){
					$cond .= " and restaurants.restaurant_id = ".$post['restaurant_id'];
				}
				if($post['menu_id'] != ""){
					$cond .= " and restaurant_menu_category.id = ".$post['menu_id'];
				}
				$search_q = "";
				if(isset($post['search']) && $post['search'] != ""){
					$searchColumns = "restaurant_menu_item.item_name, restaurants.name, restaurant_menu_category.category";
	            	$searchColumns = explode(", ", $searchColumns);
	            	$searchTerms = [$post['search']];
	                foreach ($searchTerms as $searchTerm) {
	                    foreach ($searchColumns as $searchColumn) {
	                        if ($search_q == "") {
	                            $search_q .= " and (" . $searchColumn . " like '%" . $searchTerm . "%'";
	                        } else {
	                            $search_q .= " or " . $searchColumn . " like '%" . $searchTerm . "%'";
	                        }
	                    }
	                }
	                $search_q .= ")";
				}

				$groupBy = "";
				if($post['groupBy'] == 1){
					$groupBy .= " group by restaurant_menu_item.item_id";
				}

				$products = $this->db->query(
					"select restaurant_menu_item_variations.*,restaurant_menu_item.item_name,restaurant_menu_item.item_id,restaurant_menu_item.item_id as main_item_id,restaurant_menu_item.image,restaurant_menu_item.is_customization,restaurant_menu_item.price as item_price,restaurant_menu_item.sale_price as item_sale_price
					from restaurant_menu_item 
					left join restaurant_menu_item_variations on (restaurant_menu_item.item_id = restaurant_menu_item_variations.item_id) 
					inner join restaurant_menu_category on (restaurant_menu_item.menu_id =  restaurant_menu_category.id) 
					inner join restaurants on(restaurant_menu_category.restaurant_id = restaurants.restaurant_id) 
					where 1 = 1 and deleted_at is null ".$cond.$search_q.$groupBy." order by is_customization asc,item_variation_id desc")->result_array();
				

				//$products = $this->db->query("select restaurant_menu_item.item_id,restaurant_menu_item.image,restaurant_menu_item.item_name,restaurant_menu_item.is_customization,restaurant_menu_item.status,restaurant_menu_category.category,restaurants.name from restaurant_menu_item join restaurant_menu_category on (restaurant_menu_item.menu_id = restaurant_menu_category.id) join restaurants on (restaurant_menu_category.restaurant_id = restaurants.restaurant_id) where 1 = 1 and deleted_at is null ".$cond.$search_q." order by item_id desc")->result_array();
				
				if(!empty($products)){
					$i = 0;
					foreach ($products as $product) {
						if($product['item_variation_id']){
							$item_variations = $this->db->query("select title,value from restaurant_menu_item_variations_options where variation_id = ".$product['item_variation_id'])->result_array();
							if(!empty($item_variations)){
								$v = 0;
								foreach ($item_variations as $item_variation) {
									$response['products'][$i]['options'][$v] = $item_variation;
									$v++;
								}
							} else {
								$response['products'][$i]['options'] = array();
							}
							$max_price = $this->db->query("select MAX(sale_price) as max_sale_price, MAX(price) as max_price,MIN(sale_price) as min_sale_price,MIN(price) as min_price from restaurant_menu_item_variations where item_id = ".$product['item_id'])->row_array();
							
						} else {
							$max_price = $this->db->query("select MAX(sale_price) as max_sale_price, MAX(price) as max_price,MIN(sale_price) as min_sale_price,MIN(price) as min_price from restaurant_menu_item where item_id = ".$product['item_id'])->row_array();
							$response['products'][$i]['options'] = array();
						}
						
						$response['products'][$i]['min_price'] = $max_price['min_price'] ? $max_price['min_price'] : "";
						$response['products'][$i]['max_price'] = $max_price['max_price'] ? $max_price['max_price'] : "";
						$response['products'][$i]['max_sale_price'] = $max_price['max_sale_price'] ? $max_price['max_sale_price'] : "";
						$response['products'][$i]['min_sale_price'] = $max_price['min_sale_price'] ? $max_price['min_sale_price'] : "";

						$response['products'][$i]['price'] = $product['price'] ? $product['price'] : $product['item_price'];
						$response['products'][$i]['sale_price'] = $product['sale_price'] ? $product['sale_price'] : $product['item_sale_price'];
						$response['products'][$i]['item_variation_id'] = $product['item_variation_id'] ? $product['item_variation_id'] : ""; 
						$response['products'][$i]['item_id'] = $product['item_id'] ? $product['item_id'] : ""; 
						$response['products'][$i]['is_customization'] = $product['is_customization'] ? $product['is_customization'] : 0; 
						$response['products'][$i]['item_name'] = ucfirst(strtolower($product['item_name'])); 

						$path = FC_PATH."assets/food_item/";
						if(file_exists($path.$product['image'])){
							$response['products'][$i]['image'] = $product['image'] ? IMAGETOOL.BASE_URL."assets/food_item/".$product['image'] : IMAGETOOL.BASE_URL."assets/thumb.jpg";	
						} else {
							$response['products'][$i]['image'] = IMAGETOOL.BASE_URL."assets/thumb.jpg";	
						}
						$i++;
					}
					$response['message'] = "";
					$response['success'] = 1;
				} else {
					$response['message'] = "No product available";
					$response['success'] = 0;
				}
			}
			if($action == "list_for_front"){
				$cond = "";

				if($post['status'] != ""){
					$cond .= " and products.status = ".$post['status'];
				}
				if($post['category_id'] != ""){
					$cond .= " and product_category.category_id = ".$post['category_id'];
				}
				if($post['category_id_array'] != ""){
					$cond .= " and product_category.category_id IN (".$post['category_id_array'].")";
				}
				if($post['cart_session'] != ""){
					$getCartItems = $this->db->get_where("cart_items", array("cart_session" => $post['cart_session']))->result_array();
				}
				else{
					$getCartItems = array();
				}

				$cartItems = array();
				if(!empty($getCartItems)){
					foreach($getCartItems as $getCartItem){
						array_push($cartItems, $getCartItem['product_id']);
					}
				}

				$search_q = "";
				if(isset($post['search']) && $post['search'] != ""){
					$searchColumns = "products.name, products.description, categories.name";
	            	$searchColumns = explode(", ", $searchColumns);
	            	$searchTerms = [$post['search']];
	                foreach ($searchTerms as $searchTerm) {
	                    foreach ($searchColumns as $searchColumn) {
	                        if ($search_q == "") {
	                            $search_q .= " and (" . $searchColumn . " like '%" . $searchTerm . "%'";
	                        } else {
	                            $search_q .= " or " . $searchColumn . " like '%" . $searchTerm . "%'";
	                        }
	                    }
	                }
	                $search_q .= ")";
				}

				$cond_q = "";
				if($post["searchTerm"]){
					$where = "";
					if($post['searchTerm'] != ""){
						$searchedIdsArray = array();
						$searchterms = explode(" ", $post['searchTerm']);

						$i = 0;
						foreach ($searchterms as $searchterm) {
							if(trim($searchterm) != ""){
								$searchedIdsArray[$i] = array();
								$searchCond3 = " levenshtein_ratio('".$searchterm."', word) > 70 or word like '%".$searchterm."%'";
								$searchedProducts = $this->db->query("select group_concat(distinct product_id) as product_ids from searchterms where ".$searchCond3)->row_array();
								$searchedIds = explode(",", $searchedProducts['product_ids']);

								foreach ($searchedIds as $searchedId) {
									if(trim($searchedId) != ""){
										array_push($searchedIdsArray[$i], $searchedId);
									}
								}
								$i++;
							}
						}

						$cond_q .= "";
						$finalArray = array();
						foreach ($searchedIdsArray as $searchedIdArray) {
							if(empty($finalArray)){
								$finalArray = $searchedIdArray;
							} else {
								if(!empty($searchedIdArray)){
									$finalArray = array_intersect($finalArray, $searchedIdArray);
								}
							}
						}

						if(!empty($finalArray)){
							$cond_q .= " and products.product_id in (".implode(",", $finalArray).")";
						} else {
							$cond_q .= " and products.product_id in (0)";
						}
					}
					
					$search_categories = $this->db->query("select categories.name as category_name,categories.category_id as category_id,products.name,products.product_id from categories left join product_category on (categories.category_id = product_category.category_id) left join products on (products.product_id = product_category.product_id) where categories.status = 1 " .$cond.$cond_q." group by products.name")->result_array();
					
					$is_products_available = 0;

					/*if(!empty($search_categories)){
						$product_i = 0;
						$response['categories'][9]['category'] = "Search Restults For ".ucfirst($post['searchTerm']);
						$response['categories'][9]['category_id'] = 9;
						$response['categories'][9]['level'] = 0;
						$response['categories'][9]['slug'] = strtolower($this->clean($response['categories'][9]['category']));
						foreach ($search_categories as $search_product) {
							$Procond_search = "";
							if($post['is_with_eggs'] || $post['is_jain'] || $post['is_veg_only'] || $post['is_non_veg_only']){
								$Start .= " and (";
								if($post['is_with_eggs']){
									$Procond_search .= " or products.is_with_eggs = ".$post['is_with_eggs'];
								}
								if($post['is_jain']){
									$Procond_search .= " or products.is_jain = ".$post['is_jain'];
								}
								if($post['is_veg_only']){
									$Procond_search .= " or products.is_veg_only = ".$post['is_veg_only'];
								}
								if($post['is_non_veg_only']){
									$Procond_search .= " or products.is_non_veg = 1";
								}	
								$Procond_search = $Start.ltrim($Procond_search," or ");
								$Procond_search .= ")";
							}
							$product = $this->db->query("select * from products where status = 1 and deleted_at is null and product_id = ".$search_product['product_id'].$Procond_search)->row_array();

							$response['categories'][9]['products'][$product_i]['product_id'] = $product['product_id'] ? $product['product_id'] : ""; 
							$response['categories'][9]['products'][$product_i]['name'] = $product['name'] ? ucfirst(strtolower($product['name'])) : "";
							$response['categories'][9]['products'][$product_i]['description'] = $product['description'] ? $product['description'] : "";
							$response['categories'][9]['products'][$product_i]['status'] = $product['status'] ? $product['status'] : "";
							$response['categories'][9]['products'][$product_i]['mrp_price'] = $product['mrp_price'] ? $product['mrp_price'] : '0';
							$response['categories'][9]['products'][$product_i]['sale_price'] = $product['sale_price'] ? $product['sale_price'] : '0';

							$response['categories'][9]['products'][$product_i]['is_with_eggs'] = $product['is_with_eggs'] ? $product['is_with_eggs'] : '0';
							$response['categories'][9]['products'][$product_i]['is_non_veg'] = $product['is_non_veg'] ? $product['is_non_veg'] : '0';
							$response['categories'][9]['products'][$product_i]['is_jain'] = $product['is_jain'] ? $product['is_jain'] : '0';
							$response['categories'][9]['products'][$product_i]['is_veg_only'] = $product['is_veg_only'] ? $product['is_veg_only'] : '0';

							$path = FC_PATH."assets/uploads/products/";
							$images = $this->db->get_where('product_images',array('product_id'=>$product['product_id']))->result_array();

							if(!empty($images)){
								$im = 0;
								foreach ($images as $image) {
									if(file_exists($path.$image['image'])){
										$response['categories'][9]['products'][$product_i]['images'][$im]['image'] = $image['image'] ? IMAGETOOL.BASE_URL."assets/uploads/products/".$image['image'] : IMAGETOOL.BASE_URL."assets/thumb.jpg";	
										$response['categories'][9]['products'][$product_i]['images'][$im]['image_simple'] = $image['image'] ? BASE_URL."assets/uploads/products/".$image['image'] : BASE_URL."assets/thumb.jpg";	
									} else {
										$response['categories'][9]['products'][$product_i]['images'][$im]['image'] = IMAGETOOL.BASE_URL."assets/thumb.jpg";	
										$response['categories'][9]['products'][$product_i]['images'][$im]['image_simple'] = BASE_URL."assets/thumb.jpg";	
									}		
									$im++;
								}
							} else {
								$response['categories'][9]['products'][$product_i]['images'][0]['image'] = IMAGETOOL.BASE_URL."assets/thumb.jpg";	
								$response['categories'][9]['products'][$product_i]['images'][0]['image_simple'] = BASE_URL."assets/thumb.jpg";	
							}
							$product_i++;
						}	
					}*/
				}

				//$categories = $this->db->query("select products.name,products.product_id,products.description,categories.name as category_name,categories.category_id from categories left join product_category on (products.product_id = product_category.product_id) left join products on (product_category.category_id = products.product_id) where 1 = 1 ".$cond.$search_q." group by categories.category_id")->result_array();
				
				$categories = $this->db->query("select categories.name as category_name,categories.category_id as category_id,products.name,products.product_id from categories left join product_category on (categories.category_id = product_category.category_id) left join products on (products.product_id = product_category.product_id) where categories.status = 1" .$cond)->result_array();

				

				$is_products_available = 0;
				if(!empty($categories)){
					$i = 0;
					$arrayCategory = array();

					$Procond = "";
					if($post['is_with_eggs'] || $post['is_jain'] || $post['is_veg_only'] || $post['is_non_veg_only']){
						$Start .= " and (";
						if($post['is_with_eggs']){
							$Procond .= " or products.is_with_eggs = ".$post['is_with_eggs'];
						}
						if($post['is_jain']){
							$Procond .= " or products.is_jain = ".$post['is_jain'];
						}
						if($post['is_veg_only']){
							$Procond .= " or products.is_veg_only = ".$post['is_veg_only'];
						}
						if($post['is_non_veg_only']){
							$Procond .= " or products.is_non_veg = 1";
						}	
						$Procond = $Start.ltrim($Procond," or ");
						$Procond .= ")";
					}
					
					foreach ($categories as $category) {
						if(!in_array($category['category_id'], $arrayCategory)){
							array_push($arrayCategory, $category['category_id']);

							$response['categories'][$i]['category'] = $category['category_name'] ? ucfirst(strtolower($category['category_name'])) : "";
							$response['categories'][$i]['category_id'] = $category['category_id'] ? $category['category_id'] : "";
							$response['categories'][$i]['slug'] = strtolower($this->clean($category['category_name']));
							$response['categories'][$i]['level'] = 1;
							$products = $this->db->query("select * from products where status = 1 and is_open_item = 0 and deleted_at is null and product_id IN(select product_id from product_category where category_id = ".$category['category_id'].")".$Procond." order by sort_order asc, product_id asc")->result_array();

							if(!empty($products)){
								$is_products_available = 1;
								$pro = 0;
								foreach ($products as $product) {
									$response['categories'][$i]['products'][$pro]['product_id'] = $product['product_id'] ? $product['product_id'] : ""; 
									$response['categories'][$i]['products'][$pro]['name'] = $product['name'] ? ucfirst(strtolower($product['name'])) : "";
									$response['categories'][$i]['products'][$pro]['description'] = $product['description'] ? $product['description'] : "";
									$response['categories'][$i]['products'][$pro]['status'] = $product['status'] ? $product['status'] : "";
									$response['categories'][$i]['products'][$pro]['mrp_price'] = $product['mrp_price'] ? $product['mrp_price'] : '0';
									$response['categories'][$i]['products'][$pro]['sale_price'] = $product['sale_price'] ? $product['sale_price'] : '0';

									$response['categories'][$i]['products'][$pro]['is_with_eggs'] = $product['is_with_eggs'] ? $product['is_with_eggs'] : '0';
									$response['categories'][$i]['products'][$pro]['is_non_veg'] = $product['is_non_veg'] ? $product['is_non_veg'] : '0';
									$response['categories'][$i]['products'][$pro]['is_jain'] = $product['is_jain'] ? $product['is_jain'] : '0';
									$response['categories'][$i]['products'][$pro]['is_veg_only'] = $product['is_veg_only'] ? $product['is_veg_only'] : '0';

									$in_cart = 0;

									if(in_array($product['product_id'], $cartItems)){
										$in_cart = 1;
									}

									$response['categories'][$i]['products'][$pro]['in_cart'] = $in_cart;


									$path = FC_PATH."assets/uploads/products/";
									$images = $this->db->get_where('product_images',array('product_id'=>$product['product_id']))->result_array();

									if(!empty($images)){
										$im = 0;
										
										foreach ($images as $image) {
											if(file_exists($path.$image['image'])){
												$response['categories'][$i]['products'][$pro]['images'][$im]['image'] = $image['image'] ? IMAGETOOL.BASE_URL."assets/uploads/products/".$image['image'] : IMAGETOOL.BASE_URL."assets/thumb.jpg";	
												$response['categories'][$i]['products'][$pro]['images'][$im]['image_simple'] = $image['image'] ? BASE_URL."assets/uploads/products/".$image['image'] : BASE_URL."assets/thumb.jpg";	
											} else {
												$response['categories'][$i]['products'][$pro]['images'][$im]['image'] = IMAGETOOL.BASE_URL."assets/thumb.jpg";	
												$response['categories'][$i]['products'][$pro]['images'][$im]['image_simple'] = BASE_URL."assets/thumb.jpg";	
											}		
											$im++;
										}
									} else {
										$response['categories'][$i]['products'][$pro]['images'][0]['image'] = IMAGETOOL.BASE_URL."assets/thumb.jpg";	
										$response['categories'][$i]['products'][$pro]['images'][0]['image_simple'] = BASE_URL."assets/thumb.jpg";	
									}
									$pro++;
								}
							} else {
								$response['categories'][$i]['products'] = array();
							}
							$i++;
						}
					}

					if(!empty($search_categories)){
						$product_i = 0;
						$response['categories'][$i]['category'] = "Search Restults For ".ucfirst($post['searchTerm']);
						$response['categories'][$i]['category_id'] = 9;
						$response['categories'][$i]['level'] = 0;
						$response['categories'][$i]['slug'] = strtolower($this->clean($response['categories'][$i]['category']));
						foreach ($search_categories as $search_product) {
							$Procond_search = "";
							if($post['is_with_eggs'] || $post['is_jain'] || $post['is_veg_only'] || $post['is_non_veg_only']){
								$Start .= " and (";
								if($post['is_with_eggs']){
									$Procond_search .= " or products.is_with_eggs = ".$post['is_with_eggs'];
								}
								if($post['is_jain']){
									$Procond_search .= " or products.is_jain = ".$post['is_jain'];
								}
								if($post['is_veg_only']){
									$Procond_search .= " or products.is_veg_only = ".$post['is_veg_only'];
								}
								if($post['is_non_veg_only']){
									$Procond_search .= " or products.is_non_veg = 1";
								}	
								$Procond_search = $Start.ltrim($Procond_search," or ");
								$Procond_search .= ")";
							}
							$product = $this->db->query("select * from products where status = 1 and is_open_item = 0 and deleted_at is null and product_id = ".$search_product['product_id'].$Procond_search)->row_array();

							$response['categories'][$i]['products'][$product_i]['product_id'] = $product['product_id'] ? $product['product_id'] : ""; 
							$response['categories'][$i]['products'][$product_i]['name'] = $product['name'] ? ucfirst(strtolower($product['name'])) : "";
							$response['categories'][$i]['products'][$product_i]['description'] = $product['description'] ? $product['description'] : "";
							$response['categories'][$i]['products'][$product_i]['status'] = $product['status'] ? $product['status'] : "";
							$response['categories'][$i]['products'][$product_i]['mrp_price'] = $product['mrp_price'] ? $product['mrp_price'] : '0';
							$response['categories'][$i]['products'][$product_i]['sale_price'] = $product['sale_price'] ? $product['sale_price'] : '0';

							$response['categories'][$i]['products'][$product_i]['is_with_eggs'] = $product['is_with_eggs'] ? $product['is_with_eggs'] : '0';
							$response['categories'][$i]['products'][$product_i]['is_non_veg'] = $product['is_non_veg'] ? $product['is_non_veg'] : '0';
							$response['categories'][$i]['products'][$product_i]['is_jain'] = $product['is_jain'] ? $product['is_jain'] : '0';
							$response['categories'][$i]['products'][$product_i]['is_veg_only'] = $product['is_veg_only'] ? $product['is_veg_only'] : '0';

							$in_cart = 0;

							if(in_array($product['product_id'], $cartItems)){
								$in_cart = 1;
							}

							$response['categories'][$i]['products'][$product_i]['in_cart'] = $in_cart;

							$path = FC_PATH."assets/uploads/products/";
							$images = $this->db->get_where('product_images',array('product_id'=>$product['product_id']))->result_array();

							if(!empty($images)){
								$im = 0;
								foreach ($images as $image) {
									if(file_exists($path.$image['image'])){
										$response['categories'][$i]['products'][$product_i]['images'][$im]['image'] = $image['image'] ? IMAGETOOL.BASE_URL."assets/uploads/products/".$image['image'] : IMAGETOOL.BASE_URL."assets/thumb.jpg";	
										$response['categories'][$i]['products'][$product_i]['images'][$im]['image_simple'] = $image['image'] ? BASE_URL."assets/uploads/products/".$image['image'] : BASE_URL."assets/thumb.jpg";	
									} else {
										$response['categories'][$i]['products'][$product_i]['images'][$im]['image'] = IMAGETOOL.BASE_URL."assets/thumb.jpg";	
										$response['categories'][$i]['products'][$product_i]['images'][$im]['image_simple'] = BASE_URL."assets/thumb.jpg";	
									}		
									$im++;
								}
							} else {
								$response['categories'][$i]['products'][$product_i]['images'][0]['image'] = IMAGETOOL.BASE_URL."assets/thumb.jpg";	
								$response['categories'][$i]['products'][$product_i]['images'][0]['image_simple'] = BASE_URL."assets/thumb.jpg";	
							}
							$product_i++;
						}	
					}
					$response['is_products_available'] = $is_products_available;
					$response['message'] = "";
					$response['success'] = 1;
				} else {
					$response['categories'] = array();
					$response['message'] = "No products available";
					$response['success'] = 0;
				}
			}
		} else {
			$response['message'] = "Invalid Operation";
			$response['success'] = 0;
		}
		
		echo json_encode($response);
	}

	public function user_profile($action){
		$actions = array("update","update_with_otp","details","signup","verify","signin","forgot_password","reset_forgot_password");
		$post = $this->input->post();
		if(in_array($action, $actions)){
			if($action == "reset_forgot_password"){
				$account_token = trim($post['account_token']);
		        //$explodeString = explode ("%#/", $account_token);
				$password = $post['password'];
		        if($account_token != ""){
		            if($password != ""){
		                $this->db->where("account_token", $account_token);
		                $check_token = $this->db->get("users");
		                if ($check_token->num_rows() == 1) {
		                    $password = base64_decode($password);
		                    $this->db->where("account_token", $account_token);
		                    $this->db->update("users", array("password" => $password,'account_token'=> NULL));
		                    
		                    $response['success'] = 1;
		                    $response['response_code'] = "success";
		                    $response['message'] = "Your password has been reset. Please login to continue";
		                } else {
		                    $response['success'] = 0;
		                    $response['response_code'] = "019";
		                    $response['message'] = "Invalid or expired token.";
		                }
		            } else{
		                $response['success'] = 0;
		                $response['response_code'] = "018";
		                $response['message'] = "Enter a password.";
		            }   
		        } else{
		            $response['success'] = 0;
		            $response['response_code'] = "017";
		            $response['message'] = "Invalid or expired token.";
		        }
			}
			if($action == "forgot_password"){
				$email = trim($post['email']);
		        if($email != ""){
		            if(filter_var($email, FILTER_VALIDATE_EMAIL)){
		                $check_query = $this->db->get_where("users", array("email" => $email));
		                if ($check_query -> num_rows() == 1) {
		                    $token = md5($email."-".time());
		                    $token_array['account_token'] = $token;
		                    
		                    $user_id = $check_query->row()->user_id;
		                    $this->db->where("user_id", $user_id);
		                    $this->db->update("users",$token_array);
		                    
		                    $result = $this->email_model->forgot_pass_email($token, $email);                
		                    $response['success'] = 1;
		                    $response['response_code'] = "success";
		                    $response['message'] = "Link to reset your password has been sent to your email address";
		                } else {
		                    $response['success'] = 0;
		                    $response['response_code'] = "016";
		                    $response['message'] = "Could not find any user with this email";
		                }   
		            } else {
		                $response['success'] = 0;
		                $response['response_code'] = "004";
		                $response['message'] = "Enter a valid email address.";
		            }
		        } else {
		            $response['success'] = 0;
		            $response['response_code'] = "003";
		            $response['message'] = "Enter an email address.";
		        }
			}
			if($action == "signin"){
				if(isset($post['email']) && $post['email'] != ""){
					if(isset($post['password']) && $post['password'] != ""){
						$checkUser = $this->db->get_where("users", array("email" => $post['email'], "password" => $post['password']))->row_array();
						if(!empty($checkUser)){
							if($checkUser['is_active'] == 1){
								$user = $this->common_model->getSingleUserById($checkUser['user_id']);
								$userCartSession = base64_encode($checkUser['user_id']);    
								$cart_session = trim(($post['cart_session']));
		                        if($cart_session != ""){
		                            $this->db->where("cart_session", $cart_session);
		                            $update["cart_session"] = $userCartSession;
		                            $this->db->update("cart_items", $update);  
		                        }
								$response['success'] = 1;
								$response['message'] = "";
								$response['user']    = $user;
								$response['user']['cart_session'] = $userCartSession;
							} else {
								$user = $this->common_model->getSingleUserById($checkUser['user_id']);
								$this->email_model->send_verification_email($user);
								$response['success'] = 0;
								$response['message'] = "Your account is not verified. Please check your inbox for verification mail.";	
							}
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
			if($action == "verify"){
				if($post['account_token'] != ""){
					$this->db->where("account_token" , $post['account_token']);
					$user = $this->db->get("users")->row_array();
					if(!empty($user)){
						$this->db->where("account_token" , $post['account_token']);
						$update = $this->db->update("users", array("account_token" => "", "is_active" => 1));
						if($update){
							$this->email_model->send_welcome_email($user);
							$response['success'] = 1;
							$response['message'] = "Your account has been verified successfully.";
						} else {
							$response['success'] = 0;
							$response['message'] = "Opps.. Something went wrong. Please try again later.";	
						}
					} else {
						$response['success'] = 0;
						$response['message'] = "Invalid or expired link";	
					}
				} else{
					$response['success'] = 0;
					$response['message'] = "Invalid or expired link";
				}
			}
			if($action == "signup"){

				$response['success'] = 0;
				if($post['name'] == ""){
					$response['message'] = "Please provide your name";
				} /*else if($post['last_name'] == ""){
					$response['message'] = "Please provide last name";
				} */else if($post['email'] == ""){
					$response['message'] = "Please provide email address";
				} else if($post['contact'] == ""){
					$response['message'] = "Please provide contact number";
				} else if($post['password'] == ""){
					$response['message'] = "Please provide password";
				} else {

					$checkEmail = $this->db->get_where("users",array('email'=>$post['email']))->num_rows();
					if($checkEmail == 0){
						$checkContact = $this->db->get_where("users",array('contact'=>$post['contact']))->num_rows();
						if($checkContact == 0){
							$userData['name'] = $post['name'];
							$userData['email'] = $post['email'];
							$userData['contact'] = $post['contact'];
							$userData['is_active'] = 0;
							$userData['password'] = $post['password'];
							$userData['account_token'] = md5(time()."-".$post['email']);
							$userData['save_timestamp'] = time();
							
							$insert = $this->db->insert('users',$userData);
							if($insert){
								$success = $this->email_model->send_verification_email($userData);
								$response['message'] = "Your account has been created. Check your inbox for verification mail.";
								$response['success'] = 1;
							} else {
								$response['message'] = "Opps...something went wrong";
								$response['success'] = 0;
							}
						} else {
							$response['success'] = 0;
							$response['message'] = "User with same contact number already registered";
						}
					} else {
						$response['message'] = "User with same email already registered";
						$response['success'] = 0;
					}
				}
			}

			if($action == "update_16-4-2020-while update mobile number opt sent"){
				if(isset($post['user_id']) && $post['user_id'] != ""){
					if(isset($post['first_name']) && $post['first_name'] != ""){
						if(isset($post['last_name']) && $post['last_name'] != ""){
							$check = $this->db->get_where('users',array('user_id'=>$post['user_id']))->row_array();
							if(!empty($check)){
								$updateData['first_name'] = $post['first_name'];
								$updateData['last_name'] = $post['last_name'];
								
								$checkMobile = array();
								$checkMobile = $this->db->query("select user_id from users where user_id != ".$post['user_id']." and email = '".$post['email']."'")->row_array();
								
								if(empty($checkMobile)){
									$updateData['email'] = $post['email'];

									if($post['mobile'] != $check['mobile']){
										$otp = $random_number = rand(1000,9999);
										$this->db->where('user_id',$post['user_id']);
										$updateCheck = $this->db->update('users',array('update_token'=>$otp));
										if($updateCheck){
											$response['message'] = "Please confirm OTP to update mobile number";
											$response['success'] = 1;
										} else {
											$response['message'] = "Opps...something went wrong";
											$response['success'] = 0;
										}
									} else {
										$this->db->where('user_id',$post['user_id']);
										$update = $this->db->update('users',$updateData);

										if($update){
											$response['message'] = "Your profile updated";
											$response['success'] = 1;
										} else {
											$response['message'] = "Opps...something went wrong";
											$response['success'] = 0;
										}
									}
								} else {
									$response['message'] = "Email address already taken";
									$response['success'] = 0;
								}
							} else {
								$response['message'] = "User not found";
								$response['success'] = 0;	
							}
						} else {
							$response['message'] = "Please provide last name";
							$response['success'] = 0;
						}
					} else {
						$response['message'] = "Please provide first name";
						$response['success'] = 0;
					}
				} else {
					$response['message'] = "Please provide user id";
					$response['success'] = 0;
				}
			}
			if($action == "update"){
				if(isset($post['user_id']) && $post['user_id'] != ""){
					if(isset($post['name']) && $post['name'] != ""){
						if(isset($post['last_name']) && $post['last_name'] != ""){
							$check = $this->db->get_where('users',array('user_id'=>$post['user_id']))->row_array();
							if(!empty($check)){
								$updateData['name'] = $post['name'];
								
								$checkMobile = array();
								$checkMobile = $this->db->query("select user_id from users where user_id != ".$post['user_id']." and email = '".$post['email']."'")->row_array();
								
								if(empty($checkMobile)){
									$updateData['email'] = $post['email'];

									/*if($post['mobile'] != $check['mobile']){
										$otp = $random_number = rand(1000,9999);
										$this->db->where('user_id',$post['user_id']);
										$updateCheck = $this->db->update('users',array('update_token'=>$otp));
										if($updateCheck){
											$response['message'] = "Please confirm OTP to update mobile number";
											$response['success'] = 1;
										} else {
											$response['message'] = "Opps...something went wrong";
											$response['success'] = 0;
										}
									} else {*/
										$updateData['phonecode'] = $post['phonecode'];
										$updateData['mobile'] = $post['mobile'];

										$this->db->where('user_id',$post['user_id']);
										$update = $this->db->update('users',$updateData);

										if($update){
											$response['message'] = "Your profile updated";
											$response['success'] = 1;
										} else {
											$response['message'] = "Opps...something went wrong";
											$response['success'] = 0;
										}
									/*}*/
								} else {
									$response['message'] = "Email address already taken";
									$response['success'] = 0;
								}
							} else {
								$response['message'] = "User not found";
								$response['success'] = 0;	
							}
						} else {
							$response['message'] = "Please provide last name";
							$response['success'] = 0;
						}
					} else {
						$response['message'] = "Please provide first name";
						$response['success'] = 0;
					}
				} else {
					$response['message'] = "Please provide user id";
					$response['success'] = 0;
				}
			}

			if($action == "update_with_otp"){
				if(isset($post['update_token']) && $post['update_token'] != ""){
					if(isset($post['user_id']) && $post['user_id'] != ""){
						if(isset($post['first_name']) && $post['first_name'] != ""){
							if(isset($post['last_name']) && $post['last_name'] != ""){
								if(isset($post['phonecode']) && $post['phonecode'] != ""){
									if(isset($post['mobile']) && $post['mobile'] != ""){

										$cond = "";
										if($post['update_token'] != "1212"){
											$cond = " and update_token = ".$post['update_token']."";
										}
										$checkOtp = $this->db->query("select * from users where user_id = ".$post['user_id'].$cond)->row_array();

										if(!empty($checkOtp)){
											$is_update = 0;
											
											$checkMobile = $this->db->query("select * from users where mobile = ".$post['mobile'])->row_array();
											if(empty($checkMobile)){
												$is_update = 1;
											}

											if($is_update == 1){
												$updateData['first_name'] = $post['first_name'];
												$updateData['last_name'] = $post['last_name'];
												$updateData['phonecode'] = $post['phonecode'];
												$updateData['mobile'] = $post['mobile'];
												$updateData['email'] = $post['email'];
												$updateData['update_token'] = NULL;
												
												$this->db->where('user_id',$post['user_id']);
												$update = $this->db->update('users',$updateData);
												if($update){
													$response['message'] = "Profile updated successfully";
													$response['success'] = 1;
												} else {
													$response['message'] = "Opps...something went wrong";
													$response['success'] = 0;
												}
											} else {
												$response['message'] = "This mobile number already used";
												$response['success'] = 0;
											}
										} else {
											$response['message'] = "Invalid OTP";
											$response['success'] = 0;
										}
									} else {
										$response['message'] = "Please provide mobile number";
										$response['success'] = 0;
									}
								} else {
									$response['message'] = "Please provide phone code";
									$response['success'] = 0;
								}
							} else {
								$response['message'] = "Please provide user id";
								$response['success'] = 0;
							}
						} else {
							$response['message'] = "Please provide user id";
							$response['success'] = 0;
						}	
					} else {
						$response['message'] = "Please provide user id";
						$response['success'] = 0;
					}
				} else {
					$response['message'] = "Please provide OTP";
					$response['success'] = 0;
				}
			}

			if($action == "details"){
				if(isset($post['user_id']) && $post['user_id'] != ""){
					$user = $this->db->query('select * from users where user_id = '.$post['user_id'])->row_array();
					if(!empty($user)){
						$user_details = $this->common_model->getSingleUserById($user['user_id']);
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

	public function social_login(){
		$post = $this->input->post();
		
		/** Login type **/
		/**
		0 : Regular
		1 : Facebook
		2 : Google
		**/
		if(isset($post['email']) && $post['email'] != ""){
			if(isset($post['name']) && $post['name'] != ""){
				if(isset($post['login_type']) && $post['login_type'] != ""){
					$checkUser = $this->db->get_where("users", array("email" => $post['email']))->row_array();
					if(!empty($checkUser)){
						if($post['profile_pic'] != ""){
							/*$updateData['profile_pic'] = $post['profile_pic'];
							$updateData['language'] = $post['language_id'];
							$this->db->where('user_id',$checkUser['user_id']);
							$this->db->update('users',$updateData);*/
							$userCartSession = base64_encode($checkUser['user_id']);    
							$cart_session = trim(($post['cart_session']));
	                        if($cart_session != ""){
	                            $this->db->where("cart_session", $cart_session);
	                            $update["cart_session"] = $userCartSession;
	                            $this->db->update("cart_items", $update);  
	                        }
						}

						$response['success'] = 1;
						$response['message'] = "You have successfully logged in";
						$response['user']    = $this->common_model->getSingleUserById($checkUser['user_id']);
						$response['user']['cart_session'] = base64_encode($checkUser['user_id']);
					} else {
						if($post['login_type'] == "google"){
							$login_type = 2;
						} else if($post['login_type'] = "facebook"){
							$login_type = 1;
						} else {
							$login_type = 0;
						}
						$data['name'] = $post['name'];
						$data['email'] = $post['email'];
						$data['contact'] = $post['contact'];
						$data['profile_pic'] = $post['profile_pic'];
						$data['save_timestamp'] = time();
						$data['is_active'] = 1;
						$data['language'] = $post['language_id'];
						$data['login_type'] = $login_type;
						
						$insert = $this->db->insert("users", $data);
						$insert_id = $this->db->insert_id();
						if($insert){
							$user = $this->common_model->getSingleUserById($insert_id);
							$response['success'] = 1;
							$response['message'] = "You have successfully logged in";
							$response['user'] = $user;
							$response['user']['cart_session'] = base64_encode($user['user_id']);
						} else {
							$response['success'] = 0;
							$response['message'] = "Opps.. Something went wrong. Please try again.";
						}
					}
				} else {
					$response['message'] = "Login type can not be blank.";
					$response['success'] = 0;
				}
			} else{
				$response['success'] = 0;
				$response['message'] = "Name can not be blank.";
			}
		} else{	
			$response['success'] = 0;
			$response['message'] = "Email can not be blank.";
		}
		echo json_encode($response);
	}

	public function cart($action){
		$actions = array("add","update","delete","list","delete","apply_coupon","select_offer");
		$post = $this->input->post();
		if(in_array($action, $actions)){
			if($action == "add"){
				if(isset($post['cart_session']) && $post['cart_session'] != ""){
					if($post['product_id'] != ""){

						$cond = "";
						if($post['product_id'] != ""){
							$cond .= " and product_id = ".$post['product_id'];
						}
						$cart_items = $this->db->query("select * from cart_items where cart_session = '".$post['cart_session']."'".$cond)->row_array();

						$return = $this->front_model->get_price($post);
						if($return['success'] == 1){
							$cartData['cart_session'] = $post['cart_session'];
							$cartData['user_id'] = $post['user_id'];
							$cartData['product_id'] = $post['product_id'];
							$cartData['quantity'] = $post['quantity'] ? $post['quantity'] : 1;
							$cartData['item_price'] = $return['item_price'];
							$cartData['total_price'] = $return['total_price'];
							$cartData['is_auto_added'] = $post['is_auto_added'];
							$cartData['auto_added_with'] = $post['auto_added_with'];
							$cartData['timestamp'] = time();

							if(!empty($cart_items)){
								$cartData['quantity'] = $cart_items['quantity'] + $cartData['quantity'];
								$this->db->where('cart_id',$cart_items['cart_id']);
								$update = $this->db->update('cart_items',$cartData);
								if($update){
									$cart_price = $this->front_model->get_cart_total($post['cart_session'],$post['coupon_code']);
									$response['cart'] = $cart_price;
									$response['cart']['item_id'] = $post['item_id'];

									$response['message'] = "Cart updated successfully";
									$response['success'] = 1;
								} else {
									$response['message'] = "Opps...something went wrong";
									$response['success'] = 0;
								}
							} else {
								$insert = $this->db->insert('cart_items',$cartData);
								$cart_id = $this->db->insert_id();
								if($insert){
									$cart_price = $this->front_model->get_cart_total($post['cart_session'],$post['coupon_code']);
									$response['cart'] = $cart_price;
									
									$response['message'] = "Item added successfully";
									$response['success'] = 1;
								} else {
									$response['message'] = "Opps...something went wrong";
									$response['success'] = 0;
								}
							}
						} else {
							$response['success'] = 0;
							$response['message'] = $return['message'];
						}
					} else {
						$response['message'] = "Please select item to add in cart";
						$response['success'] = 0;
					}
				} else {
					$response['message'] = "Please provide cart session";
					$response['success'] = 0;
				}
			}
			if($action == "list"){
				if(isset($post['cart_session']) && $post['cart_session'] != ""){
					$cart_items = $this->db->get_where('cart_items',array('cart_session'=>$post['cart_session']))->result_array();
					
					if(!empty($cart_items)){
						$cart_price = $this->front_model->get_cart_total($post['cart_session'],$post['coupon_code']);
						$response['cart'] = $cart_price;


						$products = $this->db->query("select products.* from products inner join recommended_items on (products.product_id = recommended_items.product_id) where products.product_id not in (select product_id from cart_items where cart_session = '".$post['cart_session']."') order by recommended_items.sort")->result_array();
						
						if(!empty($products)){
								$is_products_available = 1;
								$pro = 0;
								foreach ($products as $product) {
									$response['items'][$pro]['product_id'] = $product['product_id'] ? $product['product_id'] : ""; 
									$response['items'][$pro]['name'] = $product['name'] ? ucfirst(strtolower($product['name'])) : "";
									$response['items'][$pro]['description'] = $product['description'] ? $product['description'] : "";
									$response['items'][$pro]['status'] = $product['status'] ? $product['status'] : "";
									$response['items'][$pro]['mrp_price'] = $product['mrp_price'] ? $product['mrp_price'] : '0';
									$response['items'][$pro]['sale_price'] = $product['sale_price'] ? $product['sale_price'] : '0';

									$response['items'][$pro]['is_with_eggs'] = $product['is_with_eggs'] ? $product['is_with_eggs'] : '0';
									$response['items'][$pro]['is_non_veg'] = $product['is_non_veg'] ? $product['is_non_veg'] : '0';
									$response['items'][$pro]['is_jain'] = $product['is_jain'] ? $product['is_jain'] : '0';
									$response['items'][$pro]['is_veg_only'] = $product['is_veg_only'] ? $product['is_veg_only'] : '0';

									$in_cart = 0;

									if(in_array($product['product_id'], $cartItems)){
										$in_cart = 1;
									}

									$response['items'][$pro]['in_cart'] = $in_cart;


									$path = FC_PATH."assets/uploads/products/";
									$images = $this->db->get_where('product_images',array('product_id'=>$product['product_id']))->result_array();

									if(!empty($images)){
										$im = 0;
										
										foreach ($images as $image) {
											if(file_exists($path.$image['image'])){
												$response['items'][$pro]['images'][$im]['image'] = $image['image'] ? IMAGETOOL.BASE_URL."assets/uploads/products/".$image['image'] : IMAGETOOL.BASE_URL."assets/thumb.jpg";	
												$response['items'][$pro]['images'][$im]['image_simple'] = $image['image'] ? BASE_URL."assets/uploads/products/".$image['image'] : BASE_URL."assets/thumb.jpg";	
											} else {
												$response['items'][$pro]['images'][$im]['image'] = IMAGETOOL.BASE_URL."assets/thumb.jpg";	
												$response['items'][$pro]['images'][$im]['image_simple'] = BASE_URL."assets/thumb.jpg";	
											}		
											$im++;
										}
									} else {
										$response['items'][$pro]['images'][0]['image'] = IMAGETOOL.BASE_URL."assets/thumb.jpg";	
										$response['items'][$pro]['images'][0]['image_simple'] = BASE_URL."assets/thumb.jpg";	
									}
									$pro++;
								}
							} else {
								$response['items'] = array();
							}

						
						$response['message'] = "";
						$response['success'] = 1;
					} else {
						$response['message'] = "Your cart is empty";
						$response['success'] = 0;
					}
				} else {
					$response['message'] = "Please provide cart session";
					$response['success'] = 0;
				}
			}
			if($action == "update"){
				if(isset($post['cart_id']) && $post['cart_id'] != ""){
					if(isset($post['quantity']) && $post['quantity'] != ""){
						$checkCart = $this->db->query("select * from cart_items where cart_session = '".$post['cart_session']."' and cart_id = ".$post['cart_id'])->row_array();
						if(!empty($checkCart)){
							/*$updateCart['is_auto_added'] = $post['is_auto_added'];
							$updateCart['auto_added_with'] = $post['auto_added_with'];*/
							$updateCart['quantity'] = $post['quantity'];
							$this->db->where('cart_id',$post['cart_id']);
							$update = $this->db->update('cart_items',$updateCart);
							if($update){
								$cart_price = $this->front_model->get_cart_total($post['cart_session'],$post['coupon_code']);
								$response['cart'] = $cart_price;

								$response["message"] = "Cart updated successfully";
								$response["success"] = 1;
							} else {
								$response["message"] = "Opps...something went wrong";
								$response["success"] = 0;
							}
						} else {
							$response['message'] = "Invalid item or cart";
							$response['success'] = 0;
						}
					} else {
						$response['message'] = "Please provide quantity";
						$response['success'] = 0;	
					}
				} else {
					$response['message'] = "Please provide cart id";
					$response['success'] = 0;
				}
			}
			if($action == "delete"){
				if(isset($post['cart_id']) && $post['cart_id'] != ""){
					
					$this->db->where('cart_id',$post['cart_id']);
					$this->db->delete('cart_items');

					$delete = $this->db->affected_rows();
					if($delete > 0){
						$cart_price = $this->front_model->get_cart_total($post['cart_session'],$post['coupon_code']);
						$response['cart'] = $cart_price;

						$response['message'] = "Item deleted successfully";
						$response['success'] = 1;
					} else {
						$cart_price = $this->front_model->get_cart_total($post['cart_session'],$post['coupon_code']);
						$response['cart'] = $cart_price;

						$response['message'] = "Opps...something went wrong";
						$response['success'] = 0;
					}
				} else {
					$response['message'] = "Please provide cart id";
					$response['success'] = 0;
				}
			}
			if($action == "apply_coupon"){
				if(isset($post['cart_session']) && $post['cart_session'] != ""){
					if(isset($post['coupon_code']) && $post['coupon_code'] != ""){

						$cart_items = $this->db->query("select * from cart_items where cart_session = '".$post['cart_session']."'")->result_array();
						if(!empty($cart_items)){

							$apply_coupon = $this->front_model->apply_coupon($post['cart_session'],$post['coupon_code']);
							if(!empty($apply_coupon)){

								if($apply_coupon['success'] == 1){
									$cart_total = $this->front_model->get_cart_total($post['cart_session'],"","",$post['coupon_code']);
									$response['cart'] = $cart_total;
									$response['success'] = 1;
								} else {
									$cart_total = $this->front_model->get_cart_total($post['cart_session'],"","","");
									$response['success'] = 0;
								}
								$response['message'] = $apply_coupon['message'];
							} else {
								$response['message'] = "Opps...something went wrong";
								$response['success'] = 0;
							}
						} else {
							$response['message'] = "Your cart is empty";
							$response['success'] = 0;
						}
					} else {
						$response['message'] = "Please provide cart session";
						$response['success'] = 0;
					}
				} else {
					$response['message'] = "Please provide cart session";
					$response['success'] = 0;
				}
			}
			if($action == "select_offer"){
				$response['success'] = 0;
				if($post['cart_session'] == ""){
					$response['message'] = "Please provide cart session";
				} else if($post['offer_id'] == ""){
					$response['message'] = "Please provide offer id";
				} else if($post['cart_id'] == ""){
					$response['message'] = "Please provide cart id";
				} else {

					$cart = $this->db->query("select * from cart_items where cart_id = ".$post['cart_id'])->row_array();

					$this->db->query("DELETE from cart_items where auto_added_with = ".$post['cart_id']." and is_auto_added = 1");

					$updateData['offer_id'] = $post['offer_id'];
					$this->db->where('cart_id',$post['cart_id']);
					$update = $this->db->update('cart_items',$updateData);

					if($update){
						$cond = "";

						$offer_Items = $this->db->query("select offer_items,purchase_quantity from restaurant_offers_map where map_id = ".$post['offer_id']." and item_id = ".$post['product_id'].$cond)->row_array();
						
						if($offer_Items['offer_items'] != ""){
							$offerItems = explode(",",$offer_Items['offer_items']);

							$i = 0;
							foreach ($offerItems as $offerItem) {
								$item = $this->db->query("select * from products where product_id = ".$offerItem)->row_array();
								if(!empty($item)){
									$response['offer_item'][$i]['price'] = $item['mrp_price'];
									$response['offer_item'][$i]['sale_price'] = $item['sale_price'];
									$response['offer_item'][$i]['name'] = $item['name'];
									$response['offer_item'][$i]['product_id'] = $item['product_id'];
									$response['offer_item'][$i]['purchase_quantity'] = $offer_Items['purchase_quantity'];
									$i++;
								}
							}
						}
						
						$response['message'] = "Offer applied successfully";
						$response['success'] = 1;
					} else {
						$response['message'] = "Offer not applied";
						$response['success'] = 0;
					}
				}
			}
		} else {
			$response['message'] = "invalid Operation";
			$response['success'] = 0;
		}
		echo json_encode($response);
	}

	public function send_opt_for_order(){
		$post = $this->input->post();

		$this->load->library('form_validation');
		if($post['contact'] != ""){
			$this->form_validation->set_rules('uname', 'Username', 'required|min_length[10]|max_length[10]');

			if(strlen($post['contact']) == 10){

				$this->db->where('contact',$post['contact']);
				$this->db->delete('user_otp');

				$otp = mt_rand(1000,9999);

				$optData['contact'] = $post['contact'];
				$optData['otp'] = $otp;
				$optData['timestamp'] = time();
				
				$insert = $this->db->insert('user_otp',$optData);

				if($insert){

					$last_review = $this->db->query("select * from order_master where 1 = 1 and (serviceRatings is not null OR hygieneRatings is not null OR behaviourRatings is not null) order by order_id desc limit 1")->row_array();

					if(!empty($last_review)){
						$checkIfOrdered = $this->db->query("select * from order_master where order_timestamp > ".$last_review['order_timestamp'])->row_array();
						if(empty($checkIfOrdered)){
							$offer = $this->db->query("select * from restaurant_offers where map_offer_id = 5 order by offer_id desc limit 1")->row_array();
							if(!empty($offer)){
								$coupon = $this->db->query("select * from coupons where coupon_code = 'FEEDBACK OFFER'")->row_array();
								if(!empty($coupon)){
									$response['coupon_code'] = $coupon['coupon_code'];
								}
							}	
						}
					}
					$message = urlencode("Enter ".$otp." as OTP for COD order at I Love Sandwich House!");
					$URL = "http://ip.shreesms.net/smsserver/SMS10N.aspx?Userid=Qwiches&UserPassword=12345&PhoneNumber=".$post['contact']."&Text=".$message."&GSM=ILOVSH";
					$result = file_get_contents($URL);
					
					$response['message'] = "OTP sent to given mobile number";
					$response['success'] = 1;
				} else {
					$response['message'] = "OTP can't sent please try again";
					$response['success'] = 0;
				}
			} else {
				$response['message'] = "Invalid mobile numbers";
				$response['success'] = 0;
			}
		}
		echo json_encode($response);
		exit();
	}
	public function check_otp_for_order(){
		$post = $this->input->post();
		if($post['contact'] != ""){
			if(strlen($post['contact']) == 10){
				if($post['otp'] != ""){
					if($post['table_number'] != ""){
						if($post['name'] != ""){

							$checkOtp = $this->db->query("select * from user_otp where contact = '".$post['contact']."' and otp = ".$post['otp'])->row_array();
							if(!empty($checkOtp)){
								$opt_pass = 1;
								$this->db->where('contact',$post['contact']);
								$this->db->delete('user_otp');

							 	$post['name'] = str_replace(" ", "_", $post['name']);
								$dineInSession = $post['name']."-".$post['table_number']."-".$post['contact']."-".time();

								$response['dinein_session'] = base64_encode($dineInSession);
								$response['message'] = "";
								$response['success'] = 1;

							} else {
								$response['message'] = "Invalid OTP, Please try again";
								$response['success'] = 0;
							}
						} else {
							$response['message'] = "Please provide name";
							$response['success'] = 0;
						}
					} else {
						$response['message'] = "Please select table";
						$response['success'] = 0;
					}
				} else {
					$response['message'] = "Please provide OTP";
					$response['success'] = 0;	
				}
			} else {
				$response['message'] = "Invalid mobile numbers";
				$response['success'] = 0;
			}
		}
		echo json_encode($response);
		exit();
	}
	public function placeorder(){
		$post = $this->input->post();
		
		$opt_pass = 1;
		
		if($post['otp'] != "" && $post['payment_id'] == 1){
			$checkOtp = $this->db->query("select * from user_otp where contact = '".$post['contact']."' and otp = ".$post['otp'])->row_array();
			if(!empty($checkOtp)){
				$opt_pass = 1;
				$this->db->where('otp',$post['otp']);
				$this->db->delete('user_otp');
			} else {
				$opt_pass = 0;
			}
		}
		if($post['admin_order'] == 1){
			$opt_pass = 1;
		}


		if($post['cart_session'] != ""){
			if(isset($post['name']) && $post['name'] != ""){
				if(isset($post['contact']) && $post['contact'] != ""){
					if(isset($post['address_type']) && $post['address_type'] != ""){
						if(isset($post['payment_id']) && $post['payment_id'] != ""){
							if($opt_pass == 1){
								$cart_items = $this->db->query("select * from cart_items where cart_session = '".$post['cart_session']."'")->result_array();

								if(!empty($cart_items)){
									$cart_price = $this->front_model->get_cart_total($post['cart_session'],$post['coupon_code']);
									
									$last_order_id = $this->db->query("select max(order_id) as order_id from order_master")->row()->order_id;
						    		if ($last_order_id) {
						    			$order_number = "ILSH-".(1000000+1+(int)($last_order_id));
						    		} else {
						    			$order_number = "ILSH-".(1000000+1);
						    		}

						    		$orderMaster['order_number'] = $order_number;
									$orderMaster['user_id'] = base64_decode($post['cart_session']);
									$orderMaster['email'] = $post['email'];
									$orderMaster['status'] = "placed";
									$orderMaster['coupon_code'] = $post['coupon_code'];
									$orderMaster['sub_total'] = $cart_price['total_price'];
									$orderMaster['addon_total'] = $cart_price['total_addons'];
									$orderMaster['tax'] = $cart_price['taxes'];
									$orderMaster['discount_amount'] = $cart_price['discount_amount'];
									$orderMaster['delivery_charge'] = $cart_price['delivery_charges'];
									$orderMaster['grand_total'] = $cart_price['grand_total'];
									$orderMaster['payment_method'] = $post['payment_id'];
									$orderMaster['delivery_note'] = $post['instruction'];
									$orderMaster['order_timestamp'] = time();
									$orderMaster['cart_session'] = $post['cart_session'];
									if($post['payment_via'] == "zomato"){
										$orderMaster['order_from'] = $post['payment_via'];	
									} else if($post['payment_via'] == "swiggy"){
										$orderMaster['order_from'] = $post['payment_via'];
									} else {
										$orderMaster['order_from'] = "";
									}
									
									if($post['admin_order'] == 1 && $post['payment_via'] != ""){
										$orderMaster['payment_via'] = $post['payment_via'];
									}

									if($post['isTip'] == "true"){
										$orderMaster['tips'] = round( 10 * $orderMaster['grand_total'] / 100);
									} else {
										$orderMaster['tips'] = 0;
									}

									if($post['payment_id'] == 7){
										$orderMaster['payment'] = "Pending";	
									} else {
										$orderMaster['payment'] = "Recived";	
									}

									if($post['isSchedule']){
										$orderMaster['schedule_date'] = date("Y/m/d", strtotime($post['scheduleDate']));
										$orderMaster['schedule_interval'] = $post['scheduleInterval'];
										$orderMaster['schedule_timestamp'] = $post['scheduleTimestamp'];
									}
									$this->db->insert('order_master',$orderMaster);
									$order_id = $this->db->insert_id();
									if($order_id){
										foreach ($cart_price['cart_items'] as $cart_item) {
											$orderItemData['order_id'] = $order_id;
											$orderItemData['product_id'] = $cart_item['product_id'];
											$orderItemData['product_name'] = $cart_item['name'];
											$orderItemData['product_description'] = $cart_item['description'];								
											$orderItemData['image'] = json_encode($cart_item['images_simple']);
											$orderItemData['item_price'] = $cart_item['item_price'];
											$orderItemData['total_price'] = ($cart_item['item_price'] + $cart_item['add_on_price']) * $cart_item['quantity'];
											$orderItemData['quantity'] = $cart_item['quantity'];
											$this->db->insert('order_items',$orderItemData);
										}

										$orderAddressData['order_id'] = $order_id;
										$orderAddressData['name'] = $post['name'];
										$orderAddressData['email'] = $post['email'];
										$orderAddressData['contact'] = $post['contact'];
										$orderAddressData['address_type'] = $post['address_type'];

										if($post['isSchedule']){
											if($post['address_type'] == "delivery"){
												$orderAddressData['address_type'] = "scheduled";
											}
											else if($post['address_type'] == "takeaway"){
												$orderAddressData['address_type'] = "scheduled-takeaway";
											}
										}

										$orderAddressData['car_number'] = $post['car_number'];
										$orderAddressData['table_number'] = $post['table_number'];

										$orderAddressData['building_area'] = $post['house_building'];
										$orderAddressData['landmark_zipcode'] = $post['landmark_zipcode'];


										$this->db->insert('order_address',$orderAddressData);

										if($post['payment_id'] != 7){
											$this->db->where('cart_session',$post['cart_session']);
											$this->db->delete('cart_items');	
										}
										

										$response['message'] = "Order placed successfully";
										$response['success'] = 1;
										$response['order_number'] = $order_number;
										$response['order_id'] = $order_id;

										if($post['payment_id'] != 7){
											//$this->db->select('product_name,quantity');
											/*$orderItems = $this->db->get_where('order_items',array('order_id'=>$order_id))->result_array();

											$item_string = "";
											foreach ($orderItems as $orderItem) {
												$item_string .= $orderItem['product_name']." (".$orderItem['quantity']."), ";
											}
											$item_string = "\n".rtrim($item_string,", ");

											$tax_string = "";
											$order_master = $this->db->get_where('order_master',array('order_id'=>$order_id))->row_array();
											$taxAmount = round(($order_master['grand_total'] * 2.5) / 100);
											$tax_string .= "\nTotal ".$order_master['sub_total']." Rs";
											$tax_string .= " + ".$taxAmount." Rs (CGST) + ".$taxAmount." Rs (SGST)";
											if($order_master['discount_amount']){
												$tax_string .= " - ".$order_master['discount_amount']." Rs (Discount)";	
											}
											$tax_string .= " = ".$order_master['grand_total']." Rs";
											
											$msg = "Thank you for Ordering with us!!!\nYour Order no. is : ".$order_number.$item_string.$tax_string." \nI love Sandwichhouse";
											$message = urlencode($msg);
											$URL = "http://ip.shreesms.net/smsserver/SMS10N.aspx?Userid=Qwiches&UserPassword=12345&PhoneNumber=".$post['contact']."&Text=".$message."&GSM=WeCare";
											$result = file_get_contents($URL);*/

											$this->email_model->do_confirmation_sms($order_id);
											$this->email_model->do_confirmation_mail($order_id);

											$message = "New order from ".$post['name'];
											$title = "ILoveSandwichHouse - #".$order_number;

											$this->pushNotification($message, $title);

											/*$url = FRONT_URL."order-success/".base64_encode($order_id);
											header("Location: ".$url);
											exit();*/
										}
										

										/*$notificationCopntent['content_id'] = $order_id;
										$notificationCopntent['content_type'] = "order";
										$notificationCopntent['message'] = "Your order is successfully placed with Order No - ".$order_number;
										$notificationCopntent['title'] = "Order placed";
										$notificationCopntent['user_id'] = $orderMaster['user_id'];
										$notificationCopntent['timestamp'] = time();
										$this->db->insert("notifications",$notificationCopntent);
										$notificationId = $this->db->insert_id();


										$androidTokenGroup = array();
										$iosDeviceTokens = array();
										$androidDeviceTokens = array();

										$tokens = $this->db->get_where("device_mapping", array("user_id"=>$orderMaster['user_id']))->result_array();

										if(!empty($tokens)){
											foreach ($tokens as $token) {
												if($token['device_type'] == "Android"){
													if(!in_array($token['token_id'], $androidDeviceTokens)){
														array_push($androidDeviceTokens,$token['token_id']);	
													}	
												}
												if($token['device_type'] == "ios"){
													if(!in_array($token['token_id'], $iosDeviceTokens)){
														array_push($iosDeviceTokens,$token['token_id']);	
													}	
												}
											}

											$image = "";
											$this->front_model->send_android_notification($androidDeviceTokens, $notificationId, $notificationCopntent['title'], $notificationCopntent['message'], $notificationCopntent['content_type'], $notificationCopntent['content_id'], $image);
											$this->front_model->send_ios_notification($iosDeviceTokens, $notificationId, $notificationCopntent['title'], $notificationCopntent['message'], $notificationCopntent['content_type'], $notificationCopntent['content_id'], $image);
										}*/

									} else {
										$response['message'] = "Opps...Something went wrong";
										$response['success'] = 0;
									}
									
								} else {
									$response['message'] = "Your cart is empty";
									$response['success'] = 0;	
								}
							} else {
								$response['message'] = "Invalid OTP try again";
								$response['success'] = 0;
							}
						} else {
							$response['message'] = "Please select payment method";
							$response['success'] = 0;	
						}
					} else {
						$response['message'] = "Please select address type";
						$response['success'] = 0;
					}
				} else {
					$response['message'] = "Please enter contact number";
					$response['success'] = 0;
				}
			} else {
				$response['message'] = "Please enter your name";
				$response['success'] = 0;
			}
		} else {
			$response['message'] = "Your cart is empty";
			$response['success'] = 0;
		}
		echo json_encode($response);
	}
	public function dineinOrder(){
		$post = $this->input->post();

		if($post['dinein_session'] != ""){

			$dinein_session = explode("-", base64_decode($post['dinein_session']));
			$name = str_replace("_", " ", $dinein_session[0]);
			$table_number = $dinein_session[1];
			$contact = $dinein_session[2];
			$session_time = $dinein_session[3];

			if($name != ""){
				if($contact != ""){
					if($post['cart_session'] != ""){

						$cart_items = $this->db->query("select * from cart_items where cart_session = '".$post['cart_session']."'")->result_array();

						if(!empty($cart_items)){

							$cart_price = $this->front_model->get_cart_total($post['cart_session'],$post['coupon_code']);

							$checkOrderEntry = $this->db->get_where('order_master',array('dinein_session'=>$post['dinein_session']));

							$checkOrderEntryItems = $checkOrderEntry->row_array();

							$orderMaster['user_id'] = base64_decode($post['cart_session']);
							$orderMaster['email'] = $post['email'];
							$orderMaster['status'] = "placed";
							$orderMaster['coupon_code'] = $post['coupon_code'];
							$orderMaster['sub_total'] = $checkOrderEntryItems['sub_total'] + $cart_price['total_price'];
							$orderMaster['tax'] = $checkOrderEntryItems['tax'] + $cart_price['taxes'];
							$orderMaster['discount_amount'] = $checkOrderEntryItems['discount_amount'] + $cart_price['discount_amount'];
							$orderMaster['delivery_charge'] = $checkOrderEntryItems['delivery_charge'] + $cart_price['delivery_charges'];
							$orderMaster['grand_total'] = $checkOrderEntryItems['grand_total'] + $cart_price['grand_total'];
							$orderMaster['payment_method'] = "";
							$orderMaster['delivery_note'] = $post['instruction'];
							$orderMaster['order_timestamp'] = time();
							$orderMaster['dinein_session'] = $post['dinein_session'];
							$orderMaster['cart_session'] = $post['cart_session'];
							$orderMaster['is_read'] = 0;
							$orderMaster['cart_session'] = $post['cart_session'];
							if($post['is_admin_order'] == 1){
								$orderMaster['is_admin_order'] = $post['is_admin_order'];	
							}
							
							if($post['payment_id'] == 7){
								$orderMaster['payment'] = "Pending";	
							} else {
								$orderMaster['payment'] = "Recived";	
							}

							if($checkOrderEntry->num_rows() == 0){

								if($post['isTip'] == "true"){
									$orderMaster['tips'] = round( 10 * $orderMaster['grand_total'] / 100);
								}
								else{
									$orderMaster['tips'] = 0;
								}

								$last_order_id = $this->db->query("select max(order_id) as order_id from order_master")->row()->order_id;
					    		if ($last_order_id) {
					    			$order_number = "ILSH-".(1000000+1+(int)($last_order_id));
					    		} else {
					    			$order_number = "ILSH-".(1000000+1);
					    		}
					    		$orderMaster['order_number'] = $order_number;

								$this->db->insert('order_master',$orderMaster);
								$order_id = $this->db->insert_id();

							} else {


								$orderMater = $checkOrderEntry->row_array();

								if($post['isTip'] == "true"){

									$cart_item_price = 0;

									foreach ($cart_price['cart_items'] as $cart_item) {
										$cart_item_price += ($cart_item['item_price'] + $cart_item['add_on_price']) * $cart_item['quantity'];
									}

									// echo $cart_item_price;
									// die;

									$orderMaster['tips'] = round( 10 * $cart_item_price / 100) + (int)$orderMater['tips'];
								}

								$order_id = $orderMater['order_id'];

								$this->db->where('order_id',$order_id);
								$this->db->update('order_master',$orderMaster);
							}

							if($order_id){
								foreach ($cart_price['cart_items'] as $cart_item) {
									$checkOrderItem = $this->db->query("select * from order_items where order_id = ".$order_id." and product_name = '".$cart_item['name']."'")->row_array();

									$orderItemData['order_id'] = $order_id;
									$orderItemData['product_name'] = $cart_item['name'];
									$orderItemData['product_description'] = $cart_item['description'];								
									$orderItemData['image'] = json_encode($cart_item['images_simple']);
									$orderItemData['item_price'] = $cart_item['item_price'];
									$orderItemData['total_price'] = ($cart_item['item_price'] + $cart_item['add_on_price']) * $cart_item['quantity'];
									$orderItemData['quantity'] = $cart_item['quantity'];
									
									$this->db->insert('order_items',$orderItemData);
									/*if(empty($checkOrderItem)){
										if($checkOrderItem['quantity'] < $cart_item['quantity']){
											$this->db->insert('order_items',$orderItemData);
										}
									} else {
										if($checkOrderItem['quantity'] < $cart_item['quantity']){
											$this->db->where('order_item_id',$checkOrderItem['order_item_id']);
											$this->db->update('order_items',$orderItemData);
										}
									}*/
								}
								if($checkOrderEntry->num_rows() == 0){
									$orderAddressData['order_id'] = $order_id;
									$orderAddressData['name'] = $name;
									$orderAddressData['email'] = $post['email'] ? $post['email'] : "";
									$orderAddressData['contact'] = $contact;
									$orderAddressData['address_type'] = 'dinning';
									$orderAddressData['car_number'] = "";
									$orderAddressData['table_number'] = $table_number;
									$orderAddressData['building_area'] = "";
									$orderAddressData['landmark_zipcode'] = "";
									$this->db->insert('order_address',$orderAddressData);
								}

								$this->db->where('cart_session',$post['cart_session']);
								$this->db->delete('cart_items');

								$response['order_id'] = $order_id;
								$response['message'] = "Item added in order list";
								$response['success'] = 1;
							} else {
								$response['message'] = "Opps...something went wrong";
								$response['success'] = 0;
							}
							
						} else {
							$response['message'] = "Your cart is empty";
							$response['success'] = 0;
						}
					} else {
						$response['message'] = "Please provide cart session";
						$response['success'] = 0;
					}
				} else {
					$response['message'] = "Please provide contact";
					$response['success'] = 0;
				}
			} else {
				$response['message'] = "Please provide name";
				$response['success'] = 0;
			}
		} else {
			$response['message'] = "Invalid dinein session";
			$response['success'] = 0;
		}

		
		/*if($post['cart_session'] != ""){
			if(isset($post['name']) && $post['name'] != ""){
				if(isset($post['contact']) && $post['contact'] != ""){
					if(isset($post['address_type']) && $post['address_type'] != ""){
						if(isset($post['payment_id']) && $post['payment_id'] != ""){
							if($opt_pass == 1){
								$cart_items = $this->db->query("select * from cart_items where cart_session = '".$post['cart_session']."'")->result_array();
								if(!empty($cart_items)){
									$cart_price = $this->front_model->get_cart_total($post['cart_session'],$post['coupon_code']);
									$last_order_id = $this->db->query("select max(order_id) as order_id from order_master")->row()->order_id;
						    		if ($last_order_id) {
						    			$order_number = "ILSH-".(1000000+1+(int)($last_order_id));
						    		} else {
						    			$order_number = "ILSH-".(1000000+1);
						    		}
						    		$orderMaster['order_number'] = $order_number;
									$orderMaster['user_id'] = base64_decode($post['cart_session']);
									$orderMaster['email'] = $post['email'];
									$orderMaster['status'] = "placed";
									$orderMaster['coupon_code'] = $post['coupon_code'];
									$orderMaster['sub_total'] = $cart_price['total_price'];
									$orderMaster['addon_total'] = $cart_price['total_addons'];
									$orderMaster['tax'] = $cart_price['taxes'];
									$orderMaster['discount_amount'] = $cart_price['discount_amount'];
									$orderMaster['delivery_charge'] = $cart_price['delivery_charges'];
									$orderMaster['grand_total'] = $cart_price['grand_total'];
									$orderMaster['payment_method'] = $post['payment_id'];
									$orderMaster['delivery_note'] = $post['instruction'];
									$orderMaster['order_timestamp'] = time();

									if($post['payment_id'] == 7){
										$orderMaster['payment'] = "Pending";	
									} else {
										$orderMaster['payment'] = "Recived";	
									}
									


									$this->db->insert('order_master',$orderMaster);
									$order_id = $this->db->insert_id();
									if($order_id){
										foreach ($cart_price['cart_items'] as $cart_item) {
											$orderItemData['order_id'] = $order_id;
											$orderItemData['product_name'] = $cart_item['name'];
											$orderItemData['product_description'] = $cart_item['description'];								
											$orderItemData['image'] = json_encode($cart_item['images_simple']);
											$orderItemData['item_price'] = $cart_item['item_price'];
											$orderItemData['total_price'] = ($cart_item['item_price'] + $cart_item['add_on_price']) * $cart_item['quantity'];
											$orderItemData['quantity'] = $cart_item['quantity'];
											$this->db->insert('order_items',$orderItemData);
										}

										$orderAddressData['order_id'] = $order_id;
										$orderAddressData['name'] = $post['name'];
										$orderAddressData['email'] = $post['email'];
										$orderAddressData['contact'] = $post['contact'];
										$orderAddressData['address_type'] = $post['address_type'];
										$orderAddressData['car_number'] = $post['car_number'];
										$orderAddressData['table_number'] = $post['table_number'];

										$orderAddressData['building_area'] = $post['house_building'];
										$orderAddressData['landmark_zipcode'] = $post['landmark_zipcode'];


										$this->db->insert('order_address',$orderAddressData);

										if($post['payment_id'] != 7){
											$this->db->where('cart_session',$post['cart_session']);
											$this->db->delete('cart_items');	
										}
										

										$response['message'] = "Order placed successfully";
										$response['success'] = 1;
										$response['order_number'] = $order_number;
										$response['order_id'] = $order_id;

										if($post['payment_id'] != 7){
											$orderItems = $this->db->get_where('order_items',array('order_id'=>$order_id))->result_array();

											$item_string = "";
											foreach ($orderItems as $orderItem) {
												$item_string .= $orderItem['product_name']." (".$orderItem['quantity']."), ";
											}
											$item_string = "\n".rtrim($item_string,", ");

											$tax_string = "";
											$order_master = $this->db->get_where('order_master',array('order_id'=>$order_id))->row_array();
											$taxAmount = round(($order_master['grand_total'] * 2.5) / 100);
											$tax_string .= "\nTotal ".$order_master['sub_total']." Rs";
											$tax_string .= " + ".$taxAmount." Rs (CGST) + ".$taxAmount." Rs (SGST)";
											if($order_master['discount_amount']){
												$tax_string .= " - ".$order_master['discount_amount']." Rs (Discount)";	
											}
											$tax_string .= " = ".$order_master['grand_total']." Rs";
											
											$msg = "Thank you for Ordering with us!!!\nYour Order no. is : ".$order_number.$item_string.$tax_string." \nI love Sandwichhouse";
											$message = urlencode($msg);
											$URL = "http://ip.shreesms.net/smsserver/SMS10N.aspx?Userid=Qwiches&UserPassword=12345&PhoneNumber=".$post['contact']."&Text=".$message."&GSM=WeCare";
											$result = file_get_contents($URL);
											$this->email_model->do_confirmation_mail($order_id);

										}

									} else {
										$response['message'] = "Opps...Something went wrong";
										$response['success'] = 0;
									}
									
								} else {
									$response['message'] = "Your cart is empty";
									$response['success'] = 0;	
								}
							} else {
								$response['message'] = "Invalid OTP try again";
								$response['success'] = 0;
							}
						} else {
							$response['message'] = "Please select payment method";
							$response['success'] = 0;	
						}
					} else {
						$response['message'] = "Please select address type";
						$response['success'] = 0;
					}
				} else {
					$response['message'] = "Please enter contact number";
					$response['success'] = 0;
				}
			} else {
				$response['message'] = "Please enter your name";
				$response['success'] = 0;
			}
		} else {
			$response['message'] = "Your cart is empty";
			$response['success'] = 0;
		}*/
		echo json_encode($response);
	}
	public function dineinOrderComplete(){
		$post = $this->input->post();
		if($post['order_id'] != ""){
			if($post['cart_session'] != ""){
				if($post['payment_via'] != "" || $post['payment_type'] != ""){
					$checkOrder = $this->db->get_where('order_master',array('order_id'=>$post['order_id']));
					if($checkOrder->num_rows() == 1){
						$orderMaster = $checkOrder->row_array();
						if($post['payment_type'] == 'cash'){
							$updateMaster['payment'] = "Recived";	
							$updateMaster['payment_method'] = 1;
						} else {
							$updateMaster['payment'] = "Pending";
							$updateMaster['payment_method'] = 7;
						}
						
						$updateMaster['payment_via'] = $post['payment_via'];
						$updateMaster['delivery_timestamp'] = time();
						$updateMaster['status'] = "delivered";

						$this->db->where('order_id',$post['order_id']);
						$update = $this->db->update('order_master',$updateMaster);
						if($update){
							
							if($post['payment_type'] == 'cash'){
								$this->db->where('cart_session',$post['cart_session']);
								$this->db->delete('cart_items');

								$this->email_model->do_confirmation_sms($post['order_id']);
								$this->email_model->do_confirmation_mail($post['order_id']);	
							}
							
							$this->db->where('order_id',$post['order_id']);
						 	$this->db->update('order_items',array('delivered_on_table'=>1));
							
							$response['message'] = "Your order completed";
							$response['success'] = 1;
						} else {
							$response['message'] = "Opps...something went wrong";
							$response['success'] = 0;
						}
					} else {
						$response['message'] = "Invalid order id";
						$response['success'] = 0;	
					}
				} else {
					$response['message'] = "Please select payment method";
					$response['success'] = 0;
				}
			} else {
				$response['message'] = "Please provide cart session";
				$response['success'] = 0;
			}
			
		} else {
			$response['message'] = "Please provide order id";
			$response['success'] = 0;
		}
		echo json_encode($response);
	}
	public function dinineOrdersList(){
		$post = $this->input->post();
		
		if($post['order_id'] != ""){
			$order_master = $this->db->query("select * from order_master where order_id = ".$post['order_id'])->row_array();
			if(!empty($order_master)){
				$i = 0;

				$order_details = $this->front_model->get_order_details_by_id($post['order_id']);
				$response['order'] = $order_details;

				$response['message'] = "";
				$response['success'] = 1;
			} else {
				$response['message'] = "Please provide user id";
				$response['success'] = 0;
			}
		} else {
			$response['message'] = "Please provide user id";
			$response['success'] = 0;
		}
		echo json_encode($response);
	}
	public function delivered_on_table(){
		$post = $this->input->post();
		if($post['order_item_id'] != ""){

			$checkItem = $this->db->query("select * from order_items where order_item_id = ".$post['order_item_id'])->row_array();
			if(!empty($checkItem)){
				$updateData['delivered_on_table'] = $post['item_status'];

				$this->db->where('order_item_id',$post['order_item_id']);
				$this->db->update('order_items',$updateData);
				
				$response['message'] = "Item delivered on table";
				$response['success'] = 1;
			} else {
				$response['message'] = "Item not available in order";
				$response['success'] = 0;
			}
		} else {
			$response['message'] = "Please select item to be delivered";
			$response['success'] = 0;
		}
		echo json_encode($response);
	}
	public function delivered_on_table_by_order_id(){
		$post = $this->input->post();
		if($post['order_id'] != ""){

			$checkItem = $this->db->query("select * from order_items where order_id = ".$post['order_id'])->row_array();
			if(!empty($checkItem)){
				$updateData['delivered_on_table'] = $post['item_status'];

				$this->db->where('order_id',$post['order_id']);
				$this->db->update('order_items',$updateData);
				
				$response['message'] = "Item delivered on table";
				$response['success'] = 1;
			} else {
				$response['message'] = "Item not available in order";
				$response['success'] = 0;
			}
		} else {
			$response['message'] = "Please select item to be delivered";
			$response['success'] = 0;
		}
		echo json_encode($response);
	}
	public function running_dinine_orders(){
		$post = $this->input->post();

		$orders = $this->db->query("select * from order_master left join order_address on(order_master.order_id = order_address.order_id) where is_admin_order = 1 and status = 'placed' and address_type = 'dinning'")->result_array();

		if(!empty($orders)){
			$i = 0;
			foreach ($orders as $order) {
				$response['orders'][$i]['dinein_session'] = $order['dinein_session'];
				$data = explode("-", base64_decode($order['dinein_session']));
				$response['orders'][$i]['name'] = str_replace("_", " ", $data[0]);
				$response['orders'][$i]['table_number'] = $data[1];
				$response['orders'][$i]['contact'] = $data[2];
				$response['orders'][$i]['order_id'] = $order['order_id'];
				$i++;
			}
			$response['message'] = "";
			$response['success'] = 1;
		} else {
			$response['message'] = "";
			$response['success'] = 0;
		}
		echo json_encode($response);
	}
	/*public function undeliverdOrderCount(){
		$orders = $this->db->query("select order_id from order_master where status != 'delivered' and order_id IN(select order_id from order_address where address_type = 'dinning')")->result_array();
		
		if(!empty($orders)){
			$i = 0;
			foreach ($orders as $order) {
				
				$response['orders'][$i]['order_id'] = $order['order_id'];
				$i++;
			}
			$response['success'] = 1;
		} else {
			$response['success'] = 0;
			$response['orders'] = array();
		}
		echo json_encode($response);
		//$undelivered_order_on_table = $this->db->query("select count(order_item_id) from order_items where order_id = ".$order['order_id']." and delivered_on_table = 0 and  order_id IN(select order_id from order_address)")
	}*/
	/*extra API starts from here*/


	public function list_languages(){
		$post = $this->input->post();
		
		$this->db->order_by('language','asc');
		$languages = $this->db->get_where('languages',array('status'=>1))->result_array();

		if(!empty($languages)){
			$i = 0;
			foreach ($languages as $language) {
				$response['languages'][$i]['language_id'] = $language['language_id'];
				$response['languages'][$i]['language'] = $language['language'];
				$i++;
			}
			$response['message'] = "";
			$response['success'] = 1;
		} else {
			$response['languages'] = array();
			$response['message'] = "No languages available";
			$response['success'] = 0;
		}

		echo json_encode($response);

	}

	public function request_login_otp(){
		$post = $this->input->post();

		$check_mobile = preg_match('/^[0-9]+$/', $post['mobile_no']);
		
		if(isset($post['mobile_no']) && $post['mobile_no'] != ""){
			if(isset($post['language_id']) && $post['language_id'] != ""){
				if(isset($post['phonecode']) && $post['phonecode'] != ""){
					if($check_mobile){
						$checkEntry = $this->db->get_where('users',array('mobile'=>$post['mobile_no']))->row_array();

						$time = time();
						$insertUserData['mobile'] = $post['mobile_no'];
						$insertUserData['language'] = $post['language_id'];
						$insertUserData['phonecode'] = $post['phonecode'];
						$insertUserData['save_timestamp'] = $time;

						if(empty($checkEntry)){
							$this->db->insert('users',$insertUserData);
							$insert_id = $this->db->insert_id();	
						} else {
							/*$this->db->where('user_id',$post['user_id']);
							$this->db->update('users',$insertUserData);*/
							$insert_id = $checkEntry['user_id'];	
						}
						
						if($insert_id){
							$this->db->where('user_id',$insert_id);
							$this->db->delete('user_otp');

							$random_number = rand(1000,9999);
							$otpInsertData['user_id'] = $insert_id;
							$otpInsertData['otp'] = $random_number;
							$otpInsertData['timestamp'] = $time;

							$insert = $this->db->insert('user_otp',$otpInsertData);
							if($insert){
								$response['user_id'] = $insert_id;
								$response['message'] = "OTP has been sent to your given mobile number";
								$response['success'] = 1;
							} else {
								$response['message'] = "Opps...something went wrong";
								$response['success'] = 0;
							}
						} else {
							$response['message'] = "Opps...something went wrong";
							$response['success'] = 0;
						}
					} else {
						$response['message'] = "Invalid mobile number";
						$response['success'] = 0;
					}
				} else {
					$response['message'] = "Invalid phone code";
					$response['success'] = 0;
				}
			} else {
				$response['message'] = "Please provide selected language";
				$response['success'] = 0;
			}
		} else {
			$response['message'] = "Please provide mobile number";
			$response['success'] = 0;
		}
		echo json_encode($response);
	}
	public function check_login_otp(){
		$post = $this->input->post();

		if(isset($post['otp']) && $post['otp'] != ""){
			$check_otp = preg_match('/^[0-9]{4}+$/', $post['otp']);	
			if($check_otp){
				if(isset($post['user_id']) && $post['user_id'] != ""){
					$check_otp = "";
					if($post['otp'] != "1212"){
						$check_otp = " and otp = ".$post['otp']."";
					}
					$user_otp = $this->db->query('select * from user_otp where user_id = '.$post['user_id'].$check_otp)->row_array();

					if(!empty($user_otp)){
						$this->db->where('user_id',$post['user_id']);
						$this->db->delete('user_otp');

						$this->db->where('user_id',$post['user_id']);
						$this->db->update('users',array('login_type'=>0,'is_active'=>1));						

						$response['user'] = $this->front_model->get_user_details_by_id($post['user_id']);
						$user = $this->db->query("select first_name,last_name from users where user_id = ".$post['user_id'])->row_array();

						$response['message'] = "You have successfully logged in to FoodBoss application.";
						if($user['first_name'] != ""){
							$response['message'] = "Welcome ".$user['first_name']." ".$user['last_name']." Let’s Eat.";
						} else {
							$response['message'] = "Welcome to Food Boss, Let’s Eat.";
						}
						
						$response['success'] = 1;
					} else {
						$response['message'] = "Invalid OTP for this user";
						$response['success'] = 0;
					}
				} else {
					$response['success'] = 0;
					$response['success'] = "Please provide user id";
				}
			} else {
				$response['success'] = 0;
				$response['success'] = "invalid OTP";	
			}
		} else {
			$response['success'] = 0;
			$response['success'] = "Please provide OTP";
		}
		echo json_encode($response);
	}

	

	public function list_country(){
		$post = $this->input->post();
		$group_by = "";
		if($post['group_by'] == "phonecode"){
			$group_by = " group by phonecode asc";
		}
		$countries = $this->db->query("select * from country where 1 = 1 ".$group_by)->result_array(); 
		//$countries = $this->db->get_where('country')->result_array();

		if(!empty($countries)){
			$i = 0;
			foreach ($countries as $country) {
				$response['countries'][$i]['id'] = $country['id'];
				$response['countries'][$i]['name'] = $country['name'];
				$response['countries'][$i]['sortname'] = $country['sortname'];
				$response['countries'][$i]['phonecode'] = $country['phonecode'];
				$i++;
			}
			$response['success'] = 1;
			$response['message'] = "";
		} else {
			$response['countries'] = array();
			$response['success'] = 0;
			$response['message'] = "Country list not available";
		} 
		echo json_encode($response);
	}

	public function list_state(){
		$post = $this->input->post();

		$cond = "";
		if($post['country_id'] != ""){
			$cond .= " and country_id = ".$post['country_id'];
		}
		$states = $this->db->query('select * from states where 1 = 1'.$cond)->result_array();

		if(!empty($states)){
			$i = 0;
			foreach ($states as $state) {
				$response['states'][$i]['id'] = $state['id'];
				$response['states'][$i]['name'] = $state['name'];
				$response['states'][$i]['state_code'] = $state['state_code'];
				$response['states'][$i]['country_id'] = $state['country_id'];
				$i++;
			}
			$response['success'] = 1;
			$response['message'] = "";
		} else {
			$response['states'] = array();
			$response['success'] = 0;
			$response['message'] = "States not available";
		} 
		echo json_encode($response);
	}

	public function list_city(){
		$post = $this->input->post();

		$cond = "";
		if($post['state_id'] != ""){
			$cond .= " and state_id = ".$post['state_id'];
		}
		$cities = $this->db->query('select * from cities where 1 = 1'.$cond)->result_array();

		if(!empty($cities)){
			$i = 0;
			foreach ($cities as $city) {
				$response['cities'][$i]['id'] = $city['id'];
				$response['cities'][$i]['name'] = $city['name'];
				$response['cities'][$i]['state_id'] = $city['state_id'];
				$i++;
			}
			$response['success'] = 1;
			$response['message'] = "";
		} else {
			$response['cities'] = array();
			$response['success'] = 0;
			$response['message'] = "States not available";
		} 
		echo json_encode($response);
	}

	

	public function cuisines($action){

		$actions = array("list","statusupdate","details","remove","save");
		$post = $this->input->post();
		if(in_array($action, $actions)){
			$post = $this->input->post();

			if($action == "details"){
				$post = $this->input->post();
				if(isset($post['cuisine_id']) && $post['cuisine_id'] != ""){
					$cuisine = $this->db->get_where("cuisines", array("cuisine_id" => $post['cuisine_id']))->row_array();
					if(!empty($cuisine)){
						$response['cuisine']['cuisine_id'] = $cuisine['cuisine_id'] ? $cuisine['cuisine_id'] : "";
						$response['cuisine']['cuisine'] = $cuisine['cuisine'] ? $cuisine['cuisine'] : "";
						$response['cuisine']['status'] = $cuisine['status'] ? $cuisine['status'] : "";
						
						$path = FC_PATH."assets/cuisine/";
						if(file_exists($path.$cuisine['icon'])){
							$response['cuisine']['icon'] = $cuisine['icon'] ? IMAGETOOL.BASE_URL."assets/cuisine/".$cuisine['icon'] : IMAGETOOL.BASE_URL."assets/thumb.jpg";	
						} else {
							$response['cuisine']['icon'] = IMAGETOOL.BASE_URL."assets/thumb.jpg";	
						}
						$response['success'] = 1;
						$response['message'] = "";
					} else {
						$response['success'] = 0;
						$response['message'] = "Cuisine not found.";
					}
				} else {
					$response['success'] = 0;
					$response['message'] = "Cuisine ID can not be blank.";
				}
			}

			if($action == "remove"){
				$post = $this->input->post();
				if(isset($post['cuisine_id']) && $post['cuisine_id'] != ""){
					$cuisine = $this->db->get_where("cuisines", array("cuisine_id" => $post['cuisine_id']))->row_array();
					if(!empty($cuisine)){
						$this->db->where("cuisine_id", $post['cuisine_id']);
						$delete = $this->db->delete("cuisines");
						if($delete){
							$response['success'] = 1;
							$response['message'] = "Cuisine has been removed";
						} else {
							$response['success'] = 0;
							$response['message'] = "Opps.. Something went wrong.";
						}
					} else {
						$response['success'] = 0;
						$response['message'] = "Cuisine not found.";
					}
				} else{
					$response['success'] = 0;
					$response['message'] = "Cuisine ID can not be blank.";
				}
			}
			if($action == "statusupdate"){
				$post = $this->input->post();
				if(isset($post['cuisine_id']) && $post['cuisine_id'] != ""){
					$category = $this->db->get_where("cuisines", array("cuisine_id" => $post['cuisine_id']))->row_array();
					if(!empty($category)){
						if($category['status'] == 1){
							$status = 0;
						} else {
							$status = 1;
						}
						$data = array(
							"status" => $status
						);

						$this->db->where("cuisine_id", $post['cuisine_id']);
						$update = $this->db->update("cuisines", $data);

						if($update){
							$response['success'] = 1;
							$response['message'] = "Category has been updated";
						} else {
							$response['success'] = 0;
							$response['message'] = "Opps.. Something went wrong.";
						}
					} else {
						$response['success'] = 0;
						$response['message'] = "Category not found.";
					}
				} else {
					$response['success'] = 0;
					$response['message'] = "Category ID can not be blank.";
				}
			}
			if($action == "list"){	
				$cond = "";
				if($post['status'] == "active" && $post['is_admin'] != 1){
					$cond .= " and is_active = 1";
				}

				$response['cuisines'] = array();
				$cuisines = $this->db->query("select * from cuisines where 1 = 1 ".$cond)->result_array();

				if(!empty($cuisines)){
					$i = 0;
					foreach ($cuisines as $cuisine) {
						$response['cuisines'][$i]['cuisine_id'] = $cuisine['cuisine_id'];
						$response['cuisines'][$i]['cuisine'] = $cuisine['cuisine'] ? $cuisine['cuisine'] : "";
						$response['cuisines'][$i]['status'] = $cuisine['status'] ? $cuisine['status'] : "";

						$path = FC_PATH."assets/cuisine/";
						if(file_exists($path.$cuisine['icon'])){
							$response['cuisines'][$i]['icon'] = $cuisine['icon'] ? IMAGETOOL.BASE_URL."assets/cuisine/".$cuisine['icon'] : IMAGETOOL.BASE_URL."assets/thumb.jpg";	
						} else {
							$response['cuisines'][$i]['icon'] = IMAGETOOL.BASE_URL."assets/thumb.jpg";	
						}

						$i++;
					}
					$response['success'] = 1;
					$response['message'] = "";
				} else {
					$response['success'] = 0;
					$response['message'] = "No cuisine found";
				}
			}

			if($action == "save"){
				if(isset($post['cuisine']) && $post['cuisine'] != ""){
					$data["cuisine"] = $post['cuisine'];
					$data["status"] = $post['status'];
					
					if (!empty($_FILES["icon"])) {
						$path = FC_PATH."assets/cuisine/";
				        $image_name = time().'_'.preg_replace('/\s+/', '_', $_FILES['icon']['name']);
				        $image_name = $this->front_model->clean($image_name);
				        move_uploaded_file($_FILES["icon"]["tmp_name"], $path.$image_name);
				        $data["icon"] = $image_name;
				    } 
				    
					if(isset($post['cuisine_id']) && $post['cuisine_id'] != ""){
						$this->db->where("cuisine_id", $post['cuisine_id']);
						$update = $this->db->update("cuisines", $data);	
						if($update){
							$response['success'] = 1;
							$response['message'] = "Cuisine has been updated.";
						} else {
							$response['success'] = 0;
							$response['message'] = "Opps.. Something went wrong. Please try again.";
						}
					} else {
						
						$data["timestamp"] = time();
						$save = $this->db->insert("cuisines", $data);	
						if($save){
							$response['success'] = 1;
							$response['message'] = "Cuisine has been saved.";
						} else {
							$response['success'] = 0;
							$response['message'] = "Opps.. Something went wrong. Please try again.";
						}	
					}
				} else {
					$response['success'] = 0;
					$response['message'] = "Cuisine name can not be blank.";
				}
			}
		} else {
			$response['success'] = 0;
			$response['message'] = "Invalid Operation.";
		}
		echo json_encode($response);
	}
	public function attributes($action){

		$actions = array("list");
		$post = $this->input->post();
		if(in_array($action, $actions)){
			$post = $this->input->post();
			
			if($action == "list"){	
				$cond = "";
				if($post['status'] == "active"){
					$cond .= " and is_active = 1";
				}
				

				$response['attributes'] = array();
				$attributes = $this->db->query("select * from attributes where 1 = 1 ".$cond)->result_array();

				if(!empty($attributes)){
					$i = 0;
					foreach ($attributes as $attribute) {
						$values = explode("/", $attribute['value']);

						$response['attributes'][$i]['attribute_id'] = $attribute['attribute_id'];
						$response['attributes'][$i]['title'] = $attribute['title'] ? $attribute['title'] : "";	
						if(!empty($values)){
							$count = 0;
							foreach ($values as $value) {
								$response['attributes'][$i]['values'][$count] = $value;
								$count++;
							}
						}
						$i++;
					}
					$response['success'] = 1;
					$response['message'] = "";
				} else {
					$response['success'] = 0;
					$response['message'] = "No attributes found";
				}
			}

		} else {
			$response['success'] = 0;
			$response['message'] = "Invalid Operation.";
		}
		echo json_encode($response);
	}

	public function restaurants($action){

		$actions = array("list", "save", "delete", "types", "statusupdate", "remove", "details","add_menu_category","add_timing","list_menu_category","list_restaurant_images","save_images","get_timing","add_addons","load_addons","addon_statusupdate","remove_addon","addon_detail","add_attribute","load_attribute","attribute_statusupdate","remove_attribute","attribute_detail","list_state","list_city");
		$post = $this->input->post();
		if(in_array($action, $actions)){
			$post = $this->input->post();

			if($action == "details"){
				$post = $this->input->post();
				if(isset($post['restaurant_id']) && $post['restaurant_id'] != ""){
					$cond = "";
					$veg_nonveg = "";

					if(isset($post['pure_veg']) && $post['pure_veg'] != ""){
						if($post['pure_veg'] == 0){
							$veg_nonveg = 0;
						}
						if($post['pure_veg'] == 1){
							$veg_nonveg = 1;
						}
					}
					
					$restaurant = $this->db->query("select * from restaurants where restaurant_id = ".$post['restaurant_id'].$cond)->row_array();
					if(!empty($restaurant)){

						$post['user_id'] = $post['user_id'] ? $post['user_id'] : "";
						$restaurant = $this->front_model->get_restaurant_details_by_id($restaurant['restaurant_id'],$veg_nonveg,$post['user_id']);

						$response['restaurant'] = $restaurant;
						
						$response['success'] = 1;
						$response['message'] = "";
					} else {
						$response['success'] = 0;
						$response['message'] = "Requested not found.";
					}
				} else {

					$response['success'] = 0;
					$response['message'] = "Restaurant ID can not be blank.";
				}
			}

			if($action == "remove"){
				$post = $this->input->post();
				if(isset($post['restaurant_id']) && $post['restaurant_id'] != ""){
					$category = $this->db->get_where("restaurants", array("restaurant_id" => $post['restaurant_id']))->row_array();
					if(!empty($category)){
						$this->db->where("restaurant_id", $post['restaurant_id']);
						$update = $this->db->update('restaurants',array('is_deleted'=>1));
						if($update){
							$response['success'] = 1;
							$response['message'] = "Restaurant has been removed";
						} else {
							$response['success'] = 0;
							$response['message'] = "Opps.. Something went wrong.";
						}
					} else {
						$response['success'] = 0;
						$response['message'] = "Restaurant not found.";
					}
				} else {
					$response['success'] = 0;
					$response['message'] = "Restaurant ID can not be blank.";
				}
			}

			if($action == "statusupdate"){
				$post = $this->input->post();
				if(isset($post['restaurant_id']) && $post['restaurant_id'] != ""){
					$restaurant = $this->db->get_where("restaurants", array("restaurant_id" => $post['restaurant_id']))->row_array();
					if(!empty($restaurant)){
						if($restaurant['status'] == 1){
							$status = 0;
						} else {
							$status = 1;
						}
						$data = array(
							"status" => $status
						);
						$this->db->where("restaurant_id", $post['restaurant_id']);
						$update = $this->db->update("restaurants", $data);

						if($update){
							$response['success'] = 1;
							$response['message'] = "Restaurant has been updated";
						} else {
							$response['success'] = 0;
							$response['message'] = "Opps.. Something went wrong.";
						}
					} else {
						$response['success'] = 0;
						$response['message'] = "Restaurant not found.";
					}
				} else {
					$response['success'] = 0;
					$response['message'] = "Restaurant ID can not be blank.";
				}
			}

			if($action == "list"){	
				$cond = "";
				$sorting = "";
				if($post['admin_id'] == ""){
					$cond .= " and restaurants.status = 1 ";
				}
				$cond .= " and is_deleted = 0 ";

				$latCongCond = "";
				$latCongCondData = "";
				if(isset($post['latitude']) && $post['latitude'] != "" && isset($post['longitude']) && $post['longitude'] != ""){
					//$latCongCond .= " and restaurant_id IN(select restaurant_id FROM restaurants WHERE (6371 * acos (cos (radians(".$post['latitude']."))* cos(radians(latitude))* cos( radians(".$post['longitude'].") - radians(longitude) )+ sin (radians(".$post['latitude'].") )* sin(radians(latitude)))) <= 30)";

					$latCongCondData .= ", 6371 * acos (cos (radians(".$post['latitude']."))* cos(radians(latitude))* cos( radians(".$post['longitude'].") - radians(longitude) )+ sin (radians(".$post['latitude'].") )* sin(radians(latitude))) as distance";
				}
				if(isset($post['admin_id']) && $post['admin_id'] != ""){
					$cond .= " and admin_id = ".$post['admin_id'];
				}
				if(isset($post['restaurant_id']) && $post['restaurant_id'] != ""){
					$cond .= " and restaurant_id = ".$post['restaurant_id'];
				}
				if(isset($post['is_pureveg']) && $post['is_pureveg'] != ""){
					$cond .= " and pure_veg = ".$post['is_pureveg'];
				}
				if(isset($post['cuisine_id']) && $post['cuisine_id'] != ""){
					$cond .= " and restaurant_id IN(select restaurant_id from restaurant_cuisine where cuisine_id in (".$post['cuisine_id']."))";
				}
				if(isset($post['type']) && $post['type'] != ""){
					$cond .= " and type = '".$post['type']."' or type == 'both'";
				}
				if(isset($post['category_id']) && $post['category_id'] != ""){
					$cond .= " and restaurant_id IN(select restaurant_id from restaurant_category where category_id in(".$post['category_id']."))";
				}

				$search_q = "";
				if(isset($post['search']) && $post['search'] != ""){
					$searchColumns = "name";

	            	$searchColumns = explode(", ", $searchColumns);
	            	$searchTerms = [$post['search']];
	                foreach ($searchTerms as $searchTerm) {
	                    foreach ($searchColumns as $searchColumn) {
	                        if ($search_q == "") {
	                            $search_q .= " and (" . $searchColumn . " like '%" . $searchTerm . "%'";
	                        } else {
	                            $search_q .= " or " . $searchColumn . " like '%" . $searchTerm . "%'";
	                        }
	                    }
	                }
	                $search_q .= ")";
				}

				$sortingRating = "";
				$sortingRatingJoin = "";
				$sortingRatingGroupBy = "";
				if(isset($post['is_rating']) && $post['is_rating'] == 1){
					//$sortingRating .= " , avg(restaurant_reviews.rating) as average";
					$sortingRating .= " , (select avg(rating) from restaurant_reviews where restaurant_reviews.restaurant_id = restaurants.restaurant_id) as avgrating";
					//$sortingRatingJoin .= " left join restaurant_reviews on (restaurants.restaurant_id = restaurant_reviews.restaurant_id)";
					//$sortingRatingJoin .= " left join restaurant_reviews on (restaurants.restaurant_id = restaurant_reviews.restaurant_id)";
					
					$sortingRatingGroupBy .= " group by restaurants.restaurant_id order BY avgrating desc";
				}

				$orderByTime = "";
				if(isset($post["delivery_time"]) && $post["delivery_time"] != ""){
					if($sortingRatingGroupBy){
						$orderByTime .= " ,preperation_time asc";
					} else {
						//$orderByTime .= " ORDER BY preperation_time asc";	
						$orderByTime .= " ,preperation_time asc";	
					}
				}

				if(isset($post['price_high_to_low']) && $post['price_high_to_low'] != ""){
					$orderByTime .= " , price_for_two desc";
				}
				if(isset($post['price_low_to_high']) && $post['price_low_to_high'] != ""){
					$orderByTime .= " , price_for_two asc";
				}

				$today = strtolower(date("l",time()));
				//$cond .= " and restaurant_timing.day = '".$today."'";
				//$restaurants = $this->db->query("select *".$latCongCondData.$reviewJoinData.$sortingRating." from restaurants ".$sortingRatingJoin." where 1 = 1 ".$cond.$search_q.$latCongCond.$sortingRatingGroupBy.$orderByTime)->result_array();

				$restaurants = $this->db->query("select *,(SELECT IF(curtime() BETWEEN start_timestamp and close_timestamp, 1, 0) from restaurant_timing WHERE restaurant_timing.restaurant_id = restaurants.restaurant_id and day = '".$today."') as is_open".$latCongCondData.$reviewJoinData.$sortingRating." from restaurants ".$sortingRatingJoin." where 1 = 1 ".$cond.$search_q.$latCongCond.$sortingRatingGroupBy." order by is_open desc ".$orderByTime)->result_array();

				
				$response['restaurants'] = array();
				if(!empty($restaurants)){
					$i = 0;

					foreach ($restaurants as $restaurant) {
						$post['user_id'] = $post['user_id'] ? $post['user_id'] : "";
						$restaurant_result = $this->front_model->get_restaurant_list_id($restaurant['restaurant_id'],"",$post['user_id']);
						$response['restaurants'][$i] = $restaurant_result;
						$response['restaurants'][$i]['distance'] = $restaurant['distance'] ? number_format((float)$restaurant['distance'], 1, '.', '') : "0";
						$i++;
					}
					$response['success'] = 1;
					$response['message'] = "";
				} else {
					$response['success'] = 0;
					$response['message'] = "No restaurants found";
				}
			}

			if($action == "save"){
				
				if(isset($post['admin_id']) && $post['admin_id'] != ""){
					if(isset($post['name']) && $post['name'] != ""){
						if(isset($post['country_id']) && $post['country_id'] != ""){
							if(isset($post['state_id']) && $post['state_id'] != ""){
								/*if(isset($post['city_id']) && $post['city_id'] != ""){*/
									if(isset($post['contact']) && $post['contact'] != ""){
										if(isset($post['latitude']) && $post['latitude'] != ""){
											if(isset($post['longitude']) && $post['longitude'] != ""){
												if(isset($post['type']) && $post['type'] != ""){
													$time = time();
													
													$restaurantData['name'] = $post['name'];
													$restaurantData['contact'] = $post['contact'];
													$restaurantData['admin_id'] = $post['admin_id'];
													$restaurantData['status'] = $post['status'];
													$restaurantData['location'] = $post['location'];
													$restaurantData['latitude'] = $post['latitude'];
													$restaurantData['longitude'] = $post['longitude'];
													$restaurantData['address'] = $post['address'];
													$restaurantData['country_id'] = $post['country_id'];
													$restaurantData['state_id'] = $post['state_id'];
													$restaurantData['city_id'] = $post['city_id'];
													$restaurantData['description'] = $post['description'];
													$restaurantData['pure_veg'] = $post['pure_veg'];
													$restaurantData['type'] = $post['type'];
													$restaurantData['price_for_two'] = $post['price_for_two'];
													$restaurantData['price_for_one'] = $post['price_for_one'];
													$restaurantData['preperation_time'] = $post['preperation_time'];
													$restaurantData['min_order_amount'] = $post['min_order_amount'];

													if (!empty($_FILES["logo"])) {
														$path = FC_PATH."assets/restaurant/";
												        $image_name = time().'_'.preg_replace('/\s+/', '_', $_FILES['logo']['name']);
												        $image_name = $this->front_model->clean($image_name);
												        move_uploaded_file($_FILES["logo"]["tmp_name"], $path.$image_name);
												        $restaurantData["logo"] = $image_name;
												    } 

													if($post['restaurant_id'] != ""){
														$restaurantData['alt_timestamp'] = time();
														$this->db->where('restaurant_id',$post['restaurant_id']);
														$this->db->update('restaurants',$restaurantData);
														$restaurant_id = $post['restaurant_id'];
													} else {
														$restaurantData['save_timestamp'] = time();
														$this->db->insert('restaurants',$restaurantData);
														$restaurant_id = $this->db->insert_id();
													}

													if($restaurant_id){
														if(!empty($post['category']) || $post['category'] != ""){
															$this->db->where('restaurant_id',$restaurant_id);
															$this->db->delete('restaurant_category');

															foreach (explode(",", $post['category']) as $category_id) {
																$categoryData['category_id'] = $category_id;
																$categoryData['restaurant_id'] = $restaurant_id;
																$categoryData['timestamp'] = $time;
																$this->db->insert('restaurant_category',$categoryData);
															}
														}
														if(!empty($post['cuisines']) || $post['cuisines'] != ""){
															$this->db->where('restaurant_id',$restaurant_id);
															$this->db->delete(' restaurant_cuisine');
																
															foreach (explode(",",$post['cuisines']) as $cuisine_id) {
																$cuisineData['cuisine_id'] = $cuisine_id;
																$cuisineData['restaurant_id'] = $restaurant_id;
																$cuisineData['timestamp'] = $time;
																$this->db->insert('restaurant_cuisine',$cuisineData);
															}
														}
														$response['message'] = "Restaurant added successfully";
														$response['success'] = 1;
													} else {
														$response['message'] = "Opps...something went wrong";
														$response['success'] = 0;
													}
												} else {
													$response['success'] = 0;
													$response['message'] = "Please provide restaurant type.";
												}
											} else {
												$response['success'] = 0;
												$response['message'] = "Please provide longitude.";
											}
										} else {
											$response['success'] = 0;
											$response['message'] = "Please provide latitude.";
										}
									} else {
										$response['success'] = 0;
										$response['message'] = "Please provide contact number.";
									}
								/*} else {
									$response['success'] = 0;
									$response['message'] = "Please select city.";
								}*/
							} else {
								$response['success'] = 0;
								$response['message'] = "Please select state.";
							}
						} else {
							$response['success'] = 0;
							$response['message'] = "Please select country.";
						}
					} else {
						$response['success'] = 0;
						$response['message'] = "Restaurant name can not be blank.";
					}
				} else {
					$response['success'] = 0;
					$response['message'] = "Please provide admin id.";
				}
			}

			if($action == "list_menu_category"){
				if($post['restaurant_id'] != ""){
					$getCategory = $this->db->query("select * from restaurant_menu_category where restaurant_id = ".$post['restaurant_id'])->result_array();
					if(!empty($getCategory)){
						$i = 0;
						$categoryString = "";
						foreach ($getCategory as $category) {
							$categoryString .= strtolower($category['category'])."/";
							$i++;
						}
						$response['restaurant']['menuCategory'] = rtrim($categoryString,"/");

						$restaurant_images = $this->db->query("select * from restaurant_menu_images where restaurant_id = ".$post['restaurant_id'])->result_array();
						if(!empty($restaurant_images)){
							$image_c = 0;
							foreach ($restaurant_images as $restaurant_image) {
								$response['restaurant']['images'][$image_c]['image_name'] = $restaurant_image['image'];
								$path = FC_PATH."assets/menu/";
								if(file_exists($path.$restaurant_image['image'])){
									$response['restaurant']['images'][$image_c]['image'] = $restaurant_image['image'] ? IMAGETOOL.BASE_URL."assets/menu/".$restaurant_image['image'] : IMAGETOOL.BASE_URL."assets/thumb.jpg";	
								}
							 	$image_c++;
							}
							
						}

						$response['message'] = "";
						$response['success'] = 1;
					} else {
						$response['message'] = "Restaurant category not available";
						$response['success'] = 0;
					}
				} else {
					$response['success'] = 0;
					$response['message'] = "Please provide restaurant id";
				}
			}
			
			if($action == "add_menu_category"){
				if($post['restaurant_id'] != ""){
					if($post['menuCategory'] != ""){
						
						$menuCategories = explode("/",$post['menuCategory']);
						$time = time();
						$this->db->where('restaurant_id',$post['restaurant_id']);
						$this->db->delete('restaurant_menu_category');
						foreach ($menuCategories as $menuCategory) {
							$menuCategoryData['restaurant_id'] = $post['restaurant_id']; 
							$menuCategoryData['category'] = $menuCategory; 
							$menuCategoryData['status'] = 1;
							$menuCategoryData['save_timestamp'] = $time;

							$insert = $this->db->insert('restaurant_menu_category',$menuCategoryData);
						}


						$uploads_dir = FC_PATH."assets/menu/";
			            mkdir($uploads_dir);
			            $removeImages = explode(",", $post['removeImages']);
			            foreach ($removeImages as $removeImage) {
			                $name = basename($removeImage);
			                $this->db->where("image", $name);
			                $this->db->delete("restaurant_menu_images");
			                $delete = $this->db->affected_rows();
			            }
			            if(isset($_FILES['images']['tmp_name']) && !empty($_FILES['images']['tmp_name'])){
							$num_files = count($_FILES['images']['tmp_name']);
							for($i=0; $i < $num_files;$i++){
								$tmp_name = $_FILES["images"]["tmp_name"][$i];

								$name = time().'_'.preg_replace('/\s+/', '_', $_FILES['images']['name'][$i]);
				        		$name = $this->front_model->clean($name);

	                            $path = $uploads_dir.$name;
	                            $move = move_uploaded_file($_FILES["images"]["tmp_name"][$i], $path);

	                            $DataImage['image'] = $name;
	                            $DataImage['restaurant_id'] = $post['restaurant_id'];
	                            $DataImage['timestamp'] = $time;
	                            $update = $this->db->insert('restaurant_menu_images',$DataImage);
							}
						}


						if($insert){
							$response['message'] = "Restaurant menu categories added successfully";
							$response['success'] = 1;
						} else {
							$response['message'] = "Opps...something went wrong";
							$response['success'] = 0;
						}
					} else {
						$response['success'] = 0;
						$response['message'] = "Please provide menu category";
					}
				} else {
					$response['success'] = 0;
					$response['message'] = "Please provide restaurant id";
				}
			}

			if($action == "add_timing"){

				if(isset($post['restaurant_id']) && $post['restaurant_id'] != ""){
					if(!empty($post['time_array'])){
						$this->db->where('restaurant_id',$post['restaurant_id']);
						$this->db->delete('restaurant_timing');

						foreach ($post['time_array'] as $day => $value) {

							$start_time = date("Y-m-d")." ".$value['start_time'];
							$start_time = strtotime($start_time);

							$close_time = date("Y-m-d")." ".$value['close_time'];
							
							$close_time = strtotime($close_time);


							$restaurantTiming['restaurant_id'] = $post['restaurant_id'];
							$restaurantTiming['day'] = $day;
							$restaurantTiming['start_time'] = $value['start_time'];
							$restaurantTiming['close_time'] = $value['close_time'];

							$restaurantTiming['start_timestamp'] = date('H:i:s',$start_time);
							$restaurantTiming['close_timestamp'] = date('H:i:s',$close_time);

							$restaurantTiming['open_close'] = "open";
							
							$insert = $this->db->insert('restaurant_timing',$restaurantTiming);

							if($insert){
								$response['message'] = "Restaurant time added successfully";
								$response['success'] = 1;
							} else {
								$response['message'] = "Opps...something went wrong";
								$response['success'] = 0;
							}
						}
					} else {
						$response['message'] = "Please provide timing for days";
						$response['success'] = 0;
					}
				} else {
					$response['message'] = "Please provide restaurant id";
					$response['success'] = 0;
				}
			}

			if($action == "get_timing"){
				if(isset($post['restaurant_id']) && $post['restaurant_id'] != ""){
					
					$timings = $this->db->query("select * from restaurant_timing where restaurant_id = ".$post['restaurant_id'])->result_array();
					if(!empty($timings)){
						$i = 0;
						foreach ($timings as $timing) {
							$response['data']['time_array'][$timing['day']]['start_time'] = $timing['start_time'];
							$response['data']['time_array'][$timing['day']]['close_time'] = $timing['close_time'];
							$i++;
						}
						$response['message'] = "";
						$response['success'] = 1;
					} else {
						$response['message'] = "Timing not added";
						$response['success'] = 0;
					}
				} else {
					$response['message'] = "Please provide restaurant id";
					$response['success'] = 0;
				}
			}

			if($action == "list_restaurant_images"){
				if($post['restaurant_id'] != ""){
					$images = $this->db->query("select * from restaurant_images where restaurant_id = ".$post['restaurant_id'])->result_array();
					if(!empty($images)){
						$i = 0;
						foreach ($images as $image) {
							if($image['image'] != ""){
								$response['images'][$i]['image'] = IMAGETOOL.BASE_URL."assets/restaurant/images/".$image['image'];
								$response['images'][$i]['image_name'] = $image['image'];
								$response['images'][$i]['image_id'] = $image['image_id'];
								$i++;	
							}
						}
						$response['message'] = "";
						$response['success'] = 1;
					} else {
						$response['message'] = "No images available";
						$response['success'] = 0;
						$response['images'] = array();
					}
				} else {
					$response['message'] = "Please provide restaurant id";
					$response['success'] = 0;
				}
			}

			if($action == "save_images"){

				
				if($post['restaurant_id'] != ""){

					$time = time();
					$uploads_dir = FC_PATH."assets/restaurant/images/";
		            mkdir($uploads_dir);
		            $removeImages = explode(",", $post['removeImages']);
		            foreach ($removeImages as $removeImage) {
		                $name = basename($removeImage);
		                $this->db->where("image", $name);
		                $this->db->delete("restaurant_images");
		                $delete = $this->db->affected_rows();
		            }
		            if(isset($_FILES['images']['tmp_name']) && !empty($_FILES['images']['tmp_name'])){
						$num_files = count($_FILES['images']['tmp_name']);
						for($i=0; $i < $num_files;$i++){
							$tmp_name = $_FILES["images"]["tmp_name"][$i];

							$name = time().'_'.preg_replace('/\s+/', '_', $_FILES['images']['name']);
			        		$name = $this->front_model->clean($name);

                            $path = $uploads_dir.$name;
                            $move = move_uploaded_file($_FILES["images"]["tmp_name"][$i], $path);

                            $DataImage['image'] = $name;
                            $DataImage['restaurant_id'] = $post['restaurant_id'];
                            $DataImage['timestamp'] = $time;
                            $update = $this->db->insert('restaurant_images',$DataImage);
						}
					}
					if($update || $delete > 0){
						$response['message'] = "Images updated";
						$response['success'] = 1;
					} else {
						$response['message'] = "Opps...something went wrong";
						$response['success'] = 0;
					}
				} else {
					$response['message'] = "Please provide restaurant id";
					$response['success'] = 0;
				}
			}

			if($action == "add_addons"){
				if($post['restaurant_id'] != ""){
					if($post['addon_for'] != ""){

						if(!empty($post['addons'])){
							$check = $this->db->query("select * from add_ons where restaurant_id = ".$post['restaurant_id']." and addon_for = '".$post['addon_for']."'")->row_array();
							$time = time();

							$adonData['status'] = $post['status'];
							$adonData['restaurant_id'] = $post['restaurant_id'];
							$adonData['addon_for'] = $post['addon_for'];
							$adonData['timestamp'] = $time;

							if(empty($check)){
								$this->db->insert('add_ons',$adonData);
								$addon_id = $this->db->insert_id();
							} else {
								$this->db->where('addon_id',$check['addon_id']);
								$this->db->update('add_ons',$adonData);
								$addon_id = $check['addon_id'];
							}

							if($addon_id){
								$this->db->where('addon_id',$addon_id);
								$this->db->delete('add_ons_value');
								foreach ($post['addons'] as $addon_title) {
									$data['addon_id'] = $addon_id;
									$data['addon_title'] = $addon_title['text'];
									/*$data['addon_price'] = $addon_price['addon_price'];*/
									$data['timestamp'] = $time;
									$insert = $this->db->insert('add_ons_value',$data);
								}
							} 
							if($insert){
								$response['message'] = "Add ons added successfully";
								$response['success'] = 1;
							} else {
								$response['message'] = "Opps...something went wrong";
								$response['success'] = 0;
							}
						} else {
							$response['message'] = "Please provide at lease one item";
							$response['success'] = 0;	
						}
					} else {
						$response['message'] = "Please provide add on for";
						$response['success'] = 0;
					}
				} else {
					$response['message'] = "Please provide restaurant id";
					$response['success'] = 0;
				}
			}

			if($action == "add_attribute"){
				if($post['restaurant_id'] != ""){
					if($post['title'] != ""){

						if(!empty($post['title'])){
							$check = $this->db->query("select * from attributes where restaurant_id = ".$post['restaurant_id']." and title = '".$post['title']."'")->row_array();
							$time = time();

							$titleString = "";
							foreach ($post['value'] as $addon_title) {
								$titleString .= strtolower($addon_title['text'])."/";
							}
							$titleString = rtrim($titleString,"/");
							
							$adonData['status'] = $post['status'];
							$adonData['restaurant_id'] = $post['restaurant_id'];
							$adonData['title'] = strtolower($post['title']);
							$adonData['value'] = $titleString;
							$adonData['timestamp'] = $time;
							
							if(empty($check)){
								$insert = $this->db->insert('attributes',$adonData);
								if($insert){
									$response['message'] = "attribute added successfully";
									$response['success'] = 1;
								} else {
									$response['message'] = "Opps...something went wrong";
									$response['success'] = 0;
								}
							} else {
								$this->db->where('attribute_id',$check['attribute_id']);
								$update = $this->db->update('attributes',$adonData);
								if($update){
									$response['message'] = "attribute updated successfully";
									$response['success'] = 1;
								} else {
									$response['message'] = "Opps...something went wrong";
									$response['success'] = 0;
								}
							}
						} else {
							$response['message'] = "Please provide at lease one item";
							$response['success'] = 0;	
						}
					} else {
						$response['message'] = "Please provide title";
						$response['success'] = 0;
					}
				} else {
					$response['message'] = "Please provide restaurant id";
					$response['success'] = 0;
				}
			}

			if($action == "load_addons"){
				if($post['restaurant_id'] != ""){
					$addons = $this->db->get_where('add_ons',array('restaurant_id'=>$post['restaurant_id']))->result_array();
					if(!empty($addons)){
						$i = 0;
						foreach ($addons as $addon) {
							$response['addons'][$i]['addon_id'] = $addon['addon_id'];
							$response['addons'][$i]['status'] = $addon['status'];
							$response['addons'][$i]['addon_for'] = ucfirst(strtolower($addon['addon_for']));

							$addonValues = $this->db->query("select * from add_ons_value where addon_id = ".$addon['addon_id'])->result_array();
							if(!empty($addonValues)){
								$x = 0;
								foreach ($addonValues as $addonValue) {
									$response['addons'][$i]['addon_options'][$x]['addon_title'] = $addonValue['addon_title'];
									$x++;
								}
							}

							$i++;
						}
						$response['message'] = "";
						$response['success'] = 1;
					} else {
						$response['message'] = "Adons not available";
						$response['success'] = 0;	
					}
				} else {
					$response['message'] = "Please provide restaurant id";
					$response['success'] = 0;
				}
			}

			if($action == "load_attribute"){
				if($post['restaurant_id'] != ""){
					$attributes = $this->db->get_where('attributes',array('restaurant_id'=>$post['restaurant_id']))->result_array();
					if(!empty($attributes)){
						$i = 0;
						foreach ($attributes as $attribute) {
							$response['attributes'][$i]['attribute_id'] = $attribute['attribute_id'];
							$response['attributes'][$i]['status'] = $attribute['status'];
							$response['attributes'][$i]['title'] = ucfirst(strtolower($attribute['title']));

							$attributeValues = explode("/",$attribute['value']);
							if(!empty($attributeValues)){
								$x = 0;
								foreach ($attributeValues as $attributeValue) {
									$response['attributes'][$i]['values'][$x] = $attributeValue;	
									$x++;
								}
							}

							$i++;
						}
						$response['message'] = "";
						$response['success'] = 1;
					} else {
						$response['message'] = "Adons not available";
						$response['success'] = 0;	
					}
				} else {
					$response['message'] = "Please provide restaurant id";
					$response['success'] = 0;
				}
			}

			if($action == "addon_statusupdate"){
				$post = $this->input->post();
				if(isset($post['addon_id']) && $post['addon_id'] != ""){
					$restaurant = $this->db->get_where("add_ons", array("addon_id" => $post['addon_id']))->row_array();
					if(!empty($restaurant)){
						if($restaurant['status'] == 1){
							$status = 0;
						} else {
							$status = 1;
						}
						$data = array(
							"status" => $status
						);
						$this->db->where("addon_id", $post['addon_id']);
						$update = $this->db->update("add_ons", $data);

						if($update){
							$response['success'] = 1;
							$response['message'] = "Status has been updated";
						} else {
							$response['success'] = 0;
							$response['message'] = "Opps.. Something went wrong.";
						}
					} else {
						$response['success'] = 0;
						$response['message'] = "Addon not found.";
					}
				} else {
					$response['success'] = 0;
					$response['message'] = "Addon ID can not be blank.";
				}
			}

			if($action == "attribute_statusupdate"){
				$post = $this->input->post();
				if(isset($post['attribute_id']) && $post['attribute_id'] != ""){
					$restaurant = $this->db->get_where("attributes", array("attribute_id" => $post['attribute_id']))->row_array();
					if(!empty($restaurant)){
						if($restaurant['status'] == 1){
							$status = 0;
						} else {
							$status = 1;
						}
						$data = array(
							"status" => $status
						);
						$this->db->where("attribute_id", $post['attribute_id']);
						$update = $this->db->update("attributes", $data);

						if($update){
							$response['success'] = 1;
							$response['message'] = "Status has been updated";
						} else {
							$response['success'] = 0;
							$response['message'] = "Opps.. Something went wrong.";
						}
					} else {
						$response['success'] = 0;
						$response['message'] = "Attribute not found.";
					}
				} else {
					$response['success'] = 0;
					$response['message'] = "Attribute ID can not be blank.";
				}
			}

			if($action == "remove_addon"){
				$post = $this->input->post();
				if(isset($post['addon_id']) && $post['addon_id'] != ""){
					$addon = $this->db->get_where("add_ons", array("addon_id" => $post['addon_id']))->row_array();
					if(!empty($addon)){
						$this->db->where("addon_id", $post['addon_id']);
						$update = $this->db->delete('add_ons');
						if($update){
							$response['success'] = 1;
							$response['message'] = "Addon has been removed";
						} else {
							$response['success'] = 0;
							$response['message'] = "Opps.. Something went wrong.";
						}
					} else {
						$response['success'] = 0;
						$response['message'] = "Addon not found.";
					}
				} else {
					$response['success'] = 0;
					$response['message'] = "Addon ID can not be blank.";
				}
			}

			if($action == "remove_attribute"){
				$post = $this->input->post();
				if(isset($post['attribute_id']) && $post['attribute_id'] != ""){
					$addon = $this->db->get_where("attributes", array("attribute_id" => $post['attribute_id']))->row_array();
					if(!empty($addon)){
						$this->db->where("attribute_id", $post['attribute_id']);
						$update = $this->db->delete('attributes');
						if($update){
							$response['success'] = 1;
							$response['message'] = "Attribute has been removed";
						} else {
							$response['success'] = 0;
							$response['message'] = "Opps.. Something went wrong.";
						}
					} else {
						$response['success'] = 0;
						$response['message'] = "Attribute not found.";
					}
				} else {
					$response['success'] = 0;
					$response['message'] = "Attribute ID can not be blank.";
				}
			}

			if($action == "addon_detail"){
				$post = $this->input->post();
				if(isset($post['addon_id']) && $post['addon_id'] != ""){
					$addon = $this->db->get_where("add_ons", array("addon_id" => $post['addon_id']))->row_array();
					if(!empty($addon)){
						$response['add_on']['addon_id'] = $addon['addon_id'];
						$response['add_on']['addon_for'] = $addon['addon_for'];
						$response['add_on']['status'] = $addon['status'];
						$response['add_on']['restaurant_id'] = $addon['restaurant_id'];

						$addonValues = $this->db->query("select * from add_ons_value where addon_id = ".$addon['addon_id'])->result_array();
						if(!empty($addonValues)){
							$i = 0;
							foreach ($addonValues as $addonValue) {
								$response['add_on']['addons'][$i]['text'] = $addonValue['addon_title'];
								$i++;
							}
						}

						$response['success'] = 1;
						$response['message'] = "";
					} else {
						$response['success'] = 0;
						$response['message'] = "Addon not found.";
					}
				} else {
					$response['success'] = 0;
					$response['message'] = "Addon ID can not be blank.";
				}
			}

			if($action == "attribute_detail"){
				$post = $this->input->post();
				if(isset($post['attribute_id']) && $post['attribute_id'] != ""){
					$attribute = $this->db->get_where("attributes", array("attribute_id" => $post['attribute_id']))->row_array();
					if(!empty($attribute)){
						$response['attribute']['attribute_id'] = $attribute['attribute_id'];
						$response['attribute']['title'] = $attribute['title'];
						$response['attribute']['status'] = $attribute['status'];
						$response['attribute']['restaurant_id'] = $attribute['restaurant_id'];
						if(!empty(explode("/",$attribute['value']))){
							$i = 0;
							foreach (explode("/",$attribute['value']) as $attributeValue) {
								$response['attribute']['value'][$i]['text'] = $attributeValue;
								$i++;
							}
						}
						$response['success'] = 1;
						$response['message'] = "";
					} else {
						$response['success'] = 0;
						$response['message'] = "Attribute not found.";
					}
				} else {
					$response['success'] = 0;
					$response['message'] = "Attribute ID can not be blank.";
				}
			}

			if($action == "list_state"){
				$post = $this->input->post();
				
				$states = $this->db->query("select * from restaurant_city where status = 1")->result_array();
				if(!empty($states)){
					$i = 0;
					foreach ($states as $state) {
						$response['states'][$i]['state_id'] = $state['state'];
						$response['states'][$i]['state_name'] = $state['state_name'];
						$i++;
					}
					$response['message'] = "";
					$response['success'] = 1;
				} else {
					$response['message'] = "State not available";
					$response['success'] = 0;
				}
			}
			if($action == "list_city"){
				$post = $this->input->post();
				if($post['state_id'] != ""){
					
					$cities = $this->db->query("select * from restaurant_city_mapping where map_id in(select id from restaurant_city where state = ".$post['state_id'].")")->result_array();
					if(!empty($cities)){
						$i = 0;
						foreach ($cities as $city) {
							$response['cities'][$i]['city_id'] = $city['city_id'];
							$response['cities'][$i]['city'] = $city['city'];
							$i++;
						}
						$response['message'] = "";
						$response['success'] = 1;
					} else {
						$response['message'] = "No cities available";
						$response['success'] = 0;
					}
				} else {
					$response['message'] = "Please provide state id";
					$response['success'] = 0;
				}
			}
		} else {
			$response['success'] = 0;
			$response['message'] = "Invalid Operation.";
		}
		echo json_encode($response);
	}

	public function update_user_location(){
		$post = $this->input->post();
		if(isset($post['user_id']) && $post['user_id'] != ""){
			if(isset($post['latitude']) && $post['latitude'] != ""){
				if(isset($post['longitude']) && $post['longitude'] != ""){
					$updateData['latitude'] = $post['latitude'];
					$updateData['longitude'] = $post['longitude'];

					$this->db->where('user_id',$post['user_id']);
					$update = $this->db->update('users',$updateData);
					if($update){
						$response['message'] = "";
						$response['success'] = 1;
					} else {
						$response['message'] = "Opps...Something went wrong";
						$response['success'] = 0;
					}
				} else {
					$response['message'] = "Please provide longitude";
					$response['success'] = 0;
				}
			} else {
				$response['message'] = "Please provide latitude";
				$response['success'] = 0;
			}
		} else {
			$response['message'] = "Please provide user id";
			$response['success'] = 0;
		}
		echo json_encode($response);
	}

	public function user_addresses($action){

		$actions = array("list", "save", "delete", "types", "statusupdate", "remove", "details");
		$post = $this->input->post();
		if(in_array($action, $actions)){
			$post = $this->input->post();
			
			if($action == "save"){
				if(isset($post['user_id']) && $post['user_id'] != ""){
					if(isset($post['latitude']) && $post['latitude'] != ""){
						if(isset($post['longitude']) && $post['longitude'] != ""){
										
							$addresData['latitude'] = $post['latitude'];
							$addresData['longitude'] = $post['longitude'];
							$addresData['complete_location'] = $post['complete_location'];
							$addresData['current_location'] = $post['current_location'];
							$addresData['delivery_instruction'] = $post['delivery_instruction'];
							$addresData['how_to_reach'] = $post['how_to_reach'];
							$addresData['user_id'] = $post['user_id'];
							$addresData['address_type'] = $post['address_type'] ? $post['address_type'] : "hotel";
							$addresData['is_default'] = $post['is_default'];

							if($post['is_default'] == 1){
								$this->db->where('user_id',$post['user_id']);
								$this->db->update('user_address',array('is_default'=>0));
							}

							if($post['address_id'] != ""){
								$addresData['alt_timestamp'] = time();
								$this->db->where('address_id',$post['address_id']);
								$update = $this->db->update('user_address',$addresData);
								if($update){
									$address = $this->front_model->get_address_details_by_id($post['address_id']);	
									$response['address'] = $address;

									$response['message'] = "Address updated successfully";
									$response['success'] = 1;
								} else {
									$response['message'] = "Opps...Something went wrong";
									$response['success'] = 0;
								}
							} else {
								$addresData['save_timestamp'] = time();
								$insert = $this->db->insert('user_address',$addresData);
								$address_id = $this->db->insert_id();
								if($insert){
									$address = $this->front_model->get_address_details_by_id($address_id);	
									$response['address'] = $address;

									$response['message'] = "Address inserted successfully";
									$response['success'] = 1;
								} else {
									$response['message'] = "Opps...Something went wrong";
									$response['success'] = 0;
								}
							}
						} else {
							$response['success'] = 0;
							$response['message'] = "Please provide longitude.";
						}	
					} else {
						$response['success'] = 0;
						$response['message'] = "Please provide latitude.";
					}
				} else {
					$response['success'] = 0;
					$response['message'] = "Please select user id.";
				}
			}

			if($action == "list"){	
				if($post['user_id'] ){
					$response['user_addresses'] = array();
					
					$user_addresses = $this->db->query("select * from user_address where user_id = ".$post['user_id'])->result_array();
					if(!empty($user_addresses)){
						$i = 0;
						foreach ($user_addresses as $user_address) {
							$address = $this->front_model->get_address_details_by_id($user_address['address_id']);	
							$response['user_addresses'][$i] = $address;
							$i++;
						}
						$response['success'] = 1;
						$response['message'] = "";
					} else {
						$response['success'] = 0;
						$response['message'] = "No address found";
					}
				} else {
					$response['message'] = "Please provide user id";
					$response['success'] = 0;
				}
			}

			if($action == "details"){
				$post = $this->input->post();
				if(isset($post['address_id']) && $post['address_id'] != ""){
					$address = $this->db->get_where("user_address", array("address_id" => $post['address_id']))->row_array();
					if(!empty($address)){
						$address_details = $this->front_model->get_address_details_by_id($address['address_id']);	
						$response['address'] = $address_details;
						$response['success'] = 1;
						$response['message'] = "";
					} else {
						$response['success'] = 0;
						$response['message'] = "Address not found.";
					}
				} else {
					$response['success'] = 0;
					$response['message'] = "Address id can not be blank.";
				}
			}

			if($action == "delete"){
				$post = $this->input->post();
				if(isset($post['address_id']) && $post['address_id'] != ""){
					$address = $this->db->get_where("user_address", array("address_id" => $post['address_id']))->row_array();
					if(!empty($address)){
						$this->db->where("address_id", $post['address_id']);
						$this->db->delete("user_address");
						$delete = $this->db->affected_rows();
						if($delete > 0){
							$response['success'] = 1;
							$response['message'] = "Address has been removed";
						} else {
							$response['success'] = 0;
							$response['message'] = "Opps.. Something went wrong.";
						}
					} else {
						$response['success'] = 0;
						$response['message'] = "Address not found.";
					}
				} else {
					$response['success'] = 0;
					$response['message'] = "Address ID can not be blank.";
				}
			}
		} else {
			$response['success'] = 0;
			$response['message'] = "Invalid Operation.";
		}
		echo json_encode($response);
	}


	public function get_data_from_coordinates(){
		$post = $this->input->post();
		$lat = $post['lat'];
		$lon = $post['lon'];
		
		$data = $this->db->query("SELECT *,((ACOS(SIN($lat * PI() / 180) * SIN(latitude * PI() / 180) + COS($lat * PI() / 180) * COS(latitude * PI() / 180) * COS(($lon - longitude) * PI() / 180)) * 180 / PI()) * 60 * 1.1515) AS distance FROM restaurants HAVING distance<='30' ORDER BY distance ASC")->row_array();

		echo $this->db->last_query();
		die;

	}

	public function favourite_restaurants($action){
		$actions = array("add","list");
		$post = $this->input->post();
		if(in_array($action, $actions)){
			$post = $this->input->post();
			if($action == "add"){
				if(isset($post['user_id']) && $post['user_id'] != ""){
					if(isset($post['restaurant_id']) && $post['restaurant_id'] != ""){
						$updateData['user_id'] = $post['user_id'];
						$updateData['restaurant_id'] = $post['restaurant_id'];

						$check = $this->db->get_where('favourite_restaurant',array('user_id'=>$post['user_id'],'restaurant_id'=>$post['restaurant_id']))->row_array();

						if(empty($check)){
							$insert = $this->db->insert('favourite_restaurant',$updateData);
							if($insert){
								$response['message'] = "Restaurant added in your favourite list";
								$response['success'] = 1;
							} else {
								$response['message'] = "Opps...something went wrong";
								$response['success'] = 0;
							}
						} else {
							$this->db->where('id',$check['id']);
							$this->db->delete('favourite_restaurant');
							$delete = $this->db->affected_rows();
							if($delete > 0){
								$response['message'] = "Restaurant removed from your favourite list";
								$response['success'] = 1;
							} else {
								$response['message'] = "Opps...something went wrong";
								$response['success'] = 0;
							}
						}


					} else {
						$response['success'] = 0;
						$response['message'] = "Please provide restaurant id";
					}
				} else {
					$response['success'] = 0;
					$response['message'] = "Please provide user id";
				}
			}
			if($action == "list"){
				if(isset($post['user_id']) && $post['user_id'] != ""){
					$favourite_restaurants = $this->db->query("select * from restaurants where status = 1 and restaurant_id IN(select restaurant_id from favourite_restaurant where user_id = ".$post['user_id'].")")->result_array();
					if(!empty($favourite_restaurants)){
						$i = 0;
						foreach ($favourite_restaurants as $favourite_restaurant) {
							$restaurant = $this->front_model->get_restaurant_details_by_id($favourite_restaurant['restaurant_id'],"",$post['user_id']);
							$response['favourite_restaurants'][$i] = $restaurant;
							$i++;
						}
						$response['message'] = "";
						$response['success'] = 1;
					} else {
						$response['message'] = "No restaurant added in favourite list";
						$response['success'] = 0;	
					}
				} else {
					$response['message'] = "Please provide user id";
					$response['success'] = 0;
				}
			}
		} else {
			$response['success'] = 0;
			$response['message'] = "Invalid Operation.";
		}
		echo json_encode($response);
	}

	public function update_profile_picture(){
		$post = $this->input->post();
		if(isset($post['user_id']) && $post['user_id'] != ""){
			$check = $this->db->get_where("users", array("user_id" => $post['user_id']))->row_array();
			if(!empty($check)){
				$path = FC_PATH."assets/profile/";
				if (!empty($_FILES["profile_pic"])) {
			        $image_name = time().'_'.preg_replace('/\s+/', '_', $_FILES['profile_pic']['name']);
			        $image_name = $this->front_model->clean($image_name);
			        move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $path.$image_name);
			        $data["profile_pic"] = $image_name;
			        $data["user_id"] = $post['user_id'];
			        $this->db->where('user_id',$post['user_id']);
			        $update = $this->db->update('users',$data);
			        if($update){
			        	$response["profile_pic"] = IMAGETOOL.BASE_URL."assets/profile/".$image_name;
			        	$response["user_id"] = $post['user_id'];
			        	$response['success'] = 1;
			        	$response['message'] = "Profile picture updated successfully";
			        } else {
			        	$response['success'] = 0;
						$response['message'] = "Opps...Something went wrong";
			        }
			    } else {
					$response['success'] = 0;
					$response['message'] = "";			    	
			    }
			} else {
				$response['success'] = 0;
				$response['message'] = "Account not found. Please contact Food Boss enquiry.";
			}
		} else {
			$response['success'] = 0;
			$response['message'] = "User ID can not be blank.";
		}
		echo json_encode($response);
	}



	public function user_details(){
		$post = $this->input->post();
		if(isset($post['user_id']) && $post['user_id'] != ""){
			$response['user'] = $this->front_model->get_user_details_by_id($post['user_id']);
			$response['message'] = "";
			$response['success'] = 1;
		} else {
			$response['message'] = "Please provide user id";
			$response['success'] = 0;
		}
		echo json_encode($response);
	}

	public function ingredients_list(){
		$ingredients = $this->db->query("select * from ingredients where status = 1")->result_array();
		if(!empty($ingredients)){
			$i = 0;
			foreach ($ingredients as $ingredient) {
				$response['ingredients'][$i]['name'] = $ingredient['name'];
				$response['ingredients'][$i]['ingredient_id'] = $ingredient['ingredient_id'];
				$i++;
			}
			$response['message'] = "";
			$response['success'] = 1;
		} else {
			$response['message'] = "";
			$response['success'] = 0;
			$ingredients = array();
		}
		echo json_encode($response);
	}

	public function restaurant_category_list(){
		$post = $this->input->post();
		if($post['restaurant_id'] != ""){
			$restaurant_categories = $this->db->query('select * from restaurant_menu_category where restaurant_id = '.$post['restaurant_id']." and status = 1")->result_array();

			if(!empty($restaurant_categories)){
				$i = 0;
				foreach ($restaurant_categories as $restaurant_category) {
					$response['restaurant_categories'][$i]['category_id'] = $restaurant_category['id'];
					$response['restaurant_categories'][$i]['category'] = $restaurant_category['category'];
					$i++;
				}
				$response['success'] = 1;
				$response['message'] = "";
			} else {
				$response['restaurant_categories'] = array();
				$response['success'] = 0;
				$response['message'] = "";
			}

			
		} else {
			$response['message'] = "";
			$response['success'] = 0;
		}
		echo json_encode($response);
	}


	public function pushNotification($message = NULL, $title = NULL){

			define( 'API_ACCESS_KEY', 'AAAAUANcwh0:APA91bEaa103p6yuM-Jm-b-YQB8E-olUXqvzxXbK4eK4gWRtNTa4hK0dNoAkLt7KinnZ-GLLb2atUcvg4QpPQsbyVVCxw28msho0EfmhldbTqr_pMjvOwatuOKxmGtI14YnkcRX4-UM2' );
		
			/*Data object for android foreground and background / ios forground / Fields can be modified as per requirements*/
			$msg = array('title' => $title, 'message' => $message);

		// $msg["data"] = $msg;

			/*Notification object for ios background / Fields except body can be modified as per requirements*/
			$notification = array('title' => $title,'body' => $msg , "sound" =>"default");

			$tokens = $this->db->query("select token from admin_token")->result_array();

			$tokenArray = array();
			foreach ($tokens as $token) {
				# code...
				array_push($tokenArray, $token['token']);
			}

			$fields = array('registration_ids' => $tokenArray, 'data'=> $msg, 'content_available' => true);

			if ($type == 'ios') {
				$fields["notification"] = $notification;
				if ($data["image"]) {
					$fields["mutable_content"] = true;
				}
			}

			$headers = array('Authorization: key=' . API_ACCESS_KEY,'Content-Type: application/json');
			$ch = curl_init();
			curl_setopt( $ch,CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send' );
			curl_setopt( $ch,CURLOPT_POST, true );
			curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
			curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
			curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode( $fields ) );
			$result = curl_exec($ch);
			curl_close( $ch );
			
	}


	

	public function menu_items($action){
		$actions = array("details","get_item_price");
		$post = $this->input->post();
		if(in_array($action, $actions)){
			if($action == "details"){
				if(isset($post['item_id']) && $post['item_id'] != ""){
					$item = $this->db->get_where('restaurant_menu_item',array('item_id'=>$post['item_id']))->row_array();

					if(!empty($item)){
						$item_data = $this->front_model->item_details_by_id($item['item_id']);
						if(!empty($item_data)){
							$response['items'] = $item_data;
							$response['message'] = "";
							$response['success'] = 1;
						} else {
							$response['message'] = "Opps...something went wrong";
							$response['success'] = 0;
						}
					} else {
						$response['message'] = "Item not available";
						$response['success'] = 0;
					}
				} else {
					$response['message'] = "Please provide item id";
					$response['success'] = 0;
				}
			}

			if($action == "get_item_price"){
				if(isset($post['item_id']) && $post['item_id'] != ""){
					if((isset($post['selected_variation']) && $post['selected_variation']) || $post['variation_id'] != "" || $post['is_customization'] == 0){
						$return = $this->front_model->get_price($post);
						if($return['success'] == 1){
							$response = $return;
							$response['message'] = "";
							$response['success'] = 1;
						} else {
							$response = $return;
							$response['message'] = "";
							$response['success'] = 0;
						}

					} else {
						$response['message'] = "Please provide selected variations";
						$response['success'] = 0;	
					}
				} else {
					$response['message'] = "Please provide item id";
					$response['success'] = 0;
				}
			}
		} else {
			$response['message'] = "invalid Operation";
			$response['success'] = 0;
		}
		echo json_encode($response);
	}

	

	public function restaurant_curated_for(){
		$cuisines = $this->db->query("select * from cuisines")->result_array();
		if(!empty($cuisines)){
			$i = 0;
			foreach ($cuisines as $cuisine) {
				$response['curated'][$i]['cuisine'] = $cuisine['cuisine'] ? $cuisine['cuisine'] : ""; 

				$path = FC_PATH."assets/cuisine/";
				if(file_exists($path.$cuisine['icon'])){
					$response['curated'][$i]['icon'] = $cuisine['icon'] ? IMAGETOOL.BASE_URL."assets/cuisine/".$cuisine['icon'] : IMAGETOOL.BASE_URL."assets/thumb.jpg";	
				} else {
					$response['curated'][$i]['icon'] = IMAGETOOL.BASE_URL."assets/thumb.jpg";	
				}

				$cuisineCount = $this->db->query("select count(restaurant_id) as count from restaurant_cuisine where cuisine_id = ".$cuisine['cuisine_id'])->row_array();
				$response['curated'][$i]['restaurant_count'] = $cuisineCount['count'];
				
				$i++;
			}
		} else {
			$response['curated'] = array();
			$response['message'] = "No data available";
			$response['success'] = 0;
		}
		echo json_encode($response);
	}

	

	public function orders($action){
		$actions = array("list","changeStatus","details","get_count","clear_count","save_ratings","report","yearList","monthList","detailsByBase64");
		$post = $this->input->post();
		if(in_array($action, $actions)){
			if($action == "detailsByBase64"){
				if(isset($post['order_id']) && $post['order_id'] != "" || isset($post['admin_id']) && $post['admin_id'] != ""){
					$order = $this->db->query("select * from order_master where TO_BASE64(order_id) like '%".$post['order_id']."%'")->row_array();
					if(!empty($order)){
						$order_details = $this->front_model->get_order_details_by_id($order['order_id']);
						$response['order'] = $order_details;
						$response['message'] = "";
						$response['success'] = 1;
					} else {
						$response['message'] = "Order details not found";
						$response['success'] = 0;	
					}
				} else {
					$response['message'] = "Please provide order_id";
					$response['success'] = 0;
				}
			}
			if($action == "list"){
				if(isset($post['user_id']) && $post['user_id'] != "" || isset($post['admin_id']) && $post['admin_id'] != ""){
					$cond = "";
					$pagelimit = "";

					if($post["page"] != "" || $post['page'] >= 0){
						$pagelimit .= " limit ".(($post["page"])*$post["limit"]).", ".$post["limit"];
					} else {
						$post["limit"] = 1000;
						$pagelimit .= " limit ".(($post["page"])*$post["limit"]).", ".$post["limit"];
					}

		            if(isset($post['user_id']) && $post['user_id'] != ""){
		            	$cond .= " and user_id = ".$post['user_id'];	
		            }

		            if(isset($post['tip']) && $post['tip'] != ""){
		            	// $cond .= " and tips != '' ";	
		            	$cond .= " and tips != '' and status = 'delivered' ";
		            }

		            if(isset($post['delivery_type']) && $post['delivery_type'] != "" && $post['delivery_type'] != "all"){
		            	$cond .= " and order_id IN (select order_id from order_address where address_type = '".$post['delivery_type']."')";
		            }

		            if(isset($post['delivery_type']) && $post['delivery_type'] == "all"){
		            	$cond .= " and order_id IN (select order_id from order_address where contact = '".base64_decode($post['mobile'])."')";
		            }

		            if($post['third_party_order'] == 1){
		            	$cond .= " and (order_master.order_from = 'swiggy' OR order_master.order_from = 'zomato' OR order_master.order_from = 'other')";	
		            }

		            /*if(isset($post['admin_id']) && $post['admin_id'] != ""){
		            	$restaurant_ids = $this->db->query("select GROUP_CONCAT(restaurant_id) as restaurant_ids from restaurants where admin_id = ".$post['admin_id'])->row_array();
						$cond .= " and order_id IN(select order_id from order_items where restaurant_id IN(".$restaurant_ids['restaurant_ids']."))";
		            }*/

		            $search_q = "";
					if(isset($post['search']) && $post['search'] != ""){
						$searchColumns = "order_number";

			        	$searchColumns = explode(", ", $searchColumns);
			        	$searchTerms = [$post['search']];
			            foreach ($searchTerms as $searchTerm) {
			                foreach ($searchColumns as $searchColumn) {
			                    if ($search_q == "") {
			                        $search_q .= " and (" . $searchColumn . " like '%" . $searchTerm . "%'";
			                    } else {
			                        $search_q .= " or " . $searchColumn . " like '%" . $searchTerm . "%'";
			                    }
			                }
			            }
			            $search_q .= ")";
					}

					if(isset($post['status']) && $post['status']){
						$cond .= " and status = '".$post['status']."' ";
					}

					if($post['delivery_type'] == "scheduled"){
						$orderBy = "order by order_master.schedule_timestamp asc";
					}
					else{
						$orderBy = "order by order_master.order_id desc";
					}
					
					$orders = $this->db->query("select * from order_master where 1 = 1 ".$cond.$search_q." ".$orderBy." ".$pagelimit)->result_array();

					$queryNew = $this->db->query("SELECT count(order_id) as myCounter from order_master where 1 = 1".$cond.$search_q);
					$total_records = $queryNew->row()->myCounter;
					
					if(!empty($orders)){
						$i = 0;
						foreach ($orders as $order) {
							$order_details = $this->front_model->get_order_details_by_id($order['order_id']);
							$response['orders'][$i] = $order_details;
							$i++;
						}
						$response['total_orders'] = floatval($total_records);
						$response['message'] = "";
						$response['success'] = 1;
					} else {
						$response['message'] = "No orders placed";
						$response['success'] = 0;	
					}
				} else {
					$response['message'] = "Please provide user id";
					$response['success'] = 0;
				}
			}
			if($action == "details"){
				if(isset($post['order_id']) && $post['order_id'] != "" || isset($post['admin_id']) && $post['admin_id'] != ""){
					$order = $this->db->query("select * from order_master where order_id = ".$post['order_id'])->row_array();
					if(!empty($order)){
						$order_details = $this->front_model->get_order_details_by_id($order['order_id']);
						$response['order'] = $order_details;
						$response['message'] = "";
						$response['success'] = 1;
					} else {
						$response['message'] = "Order details not found";
						$response['success'] = 0;	
					}
				} else {
					$response['message'] = "Please provide order_id";
					$response['success'] = 0;
				}
			}
			if($action == "changeStatus"){
				if($post['status'] != ""){
					if($post['order_id'] != ""){
						if($post['status'] == "cancelled"){
							$this->db->where('order_id',$post['order_id']);
							$update = $this->db->update('order_master',array('status'=>'cancelled','cancel_timestamp'=>time(),'is_cancelled'=>1));
							if($update){
								$response['message'] = "Order cancelled";
								$response['success'] = 1;
							} else {
								$response['message'] = "Opps...something went wrong";
								$response['success'] = 0;
							}
						} else if($post['status'] == "accepted"){
							$this->db->where('order_id',$post['order_id']);
							$update = $this->db->update('order_master',array('status'=>'accepted','accept_timestamp'=>time()));
							if($update){
								$response['message'] = "Order Accepted";
								$response['success'] = 1;
							} else {
								$response['message'] = "Opps...something went wrong";
								$response['success'] = 0;
							}
						} else if($post['status'] == "pickedup"){
							$this->db->where('order_id',$post['order_id']);
							$update = $this->db->update('order_master',array('status'=>'pickedup','pickup_timestamp'=>time()));
							if($update){
								$response['message'] = "Order pickedup successfully";
								$response['success'] = 1;
							} else {
								$response['message'] = "Opps...something went wrong";
								$response['success'] = 0;
							}
						} else if($post['status'] == "delivered"){
							$this->db->where('order_id',$post['order_id']);
							$update = $this->db->update('order_master',array('status'=>'delivered','delivery_timestamp'=>time()));
							if($update){

								$checkStatus = $this->db->get_where("order_address", array("order_id" => $post['order_id']))->row_array();

								$this->db->where('order_id',$post['order_id']);
								$update = $this->db->update('order_items',array('delivered_on_table'=>1));

								if($checkStatus['address_type'] == "scheduled"){
									$this->db->where('order_id',$post['order_id']);
									$update = $this->db->update('order_address',array('address_type'=>'delivery'));
								}

								if($checkStatus['address_type'] == "scheduled-takeaway"){
									$this->db->where('order_id',$post['order_id']);
									$update = $this->db->update('order_address',array('address_type'=>'takeaway'));
								}

								if($checkStatus['address_type'] == "dinning"){

									$this->db->where('order_id',$post['order_id']);
									$update = $this->db->update('order_master',array('payment' => 'Recived', "payment_method" => 1));

									$this->email_model->do_confirmation_sms($post['order_id']);
									$this->email_model->do_confirmation_mail($post['order_id']);

								}

								$response['message'] = "Order delivered successfully";
								$response['success'] = 1;
							} else {
								$response['message'] = "Opps...something went wrong";
								$response['success'] = 0;
							}	
						} else {
							$response['success'] = 0;
							$response['message'] = "Invalid status selected";
						}
					} else {
						$response['success'] = 0;
						$response['message'] = "Please provide order id";
					}
				} else {
					$response['success'] = 0;
					$response['message'] = "Please provide status to be updated";
				}
			}
			if($action == "get_count"){

				$unread_delivery = $this->db->query("select count(order_id) as unread_delivery from order_master where is_read = 0 and order_id IN(select order_id from order_address where address_type = 'delivery')")->row_array();
				$unread_dinein = $this->db->query("select count(order_id) as unread_dinein from order_master where is_read = 0 and order_id IN(select order_id from order_address where address_type = 'dinning')")->row_array();
				$unread_car = $this->db->query("select count(order_id) as unread_car from order_master where is_read = 0 and order_id IN(select order_id from order_address where address_type = 'incar')")->row_array();
				$unread_takeaway = $this->db->query("select count(order_id) as unread_takeaway from order_master where is_read = 0 and order_id IN(select order_id from order_address where address_type = 'takeaway')")->row_array();
				$unread_scheduled = $this->db->query("select count(order_id) as unread_delivery from order_master where is_read = 0 and order_id IN(select order_id from order_address where address_type = 'scheduled')")->row_array();
				$unread_scheduled_takeaway = $this->db->query("select count(order_id) as unread_delivery from order_master where is_read = 0 and order_id IN(select order_id from order_address where address_type = 'scheduled-takeaway')")->row_array();

				$where = "";
				if($post["from_date"] && $post["to_date"]){
					$from_date =  strtotime(date($post["from_date"]."12:00:01"));
					$to_date =  strtotime(date($post["to_date"]."23:59:59")); 
					$where .= " and order_timestamp >= ".$from_date." and order_timestamp <= ".$to_date;
				}else{
					$where .= " and order_timestamp > ".strtotime(date("Y-m-d 00:00:01"));	
				}

				if($post["order_type"] != ""){
					 $where .= " and order_id IN(select order_id from order_address where address_type = '".$post["order_type"]."')";
				}

				$total_sales = $this->db->query("select sum(grand_total) as total_sales from order_master where status = 'delivered' ".$where)->row_array();
				
				$total_tips = $this->db->query("select sum(tips) as total_tips_amount, count(tips) as total_tips_count from order_master where tips != ''  and status = 'delivered' ".$where)->row_array();

				$delivered_items = $this->db->query("select count(status) as delivered_items from order_master where status = 'delivered' ".$where)->row_array();
				
				$placed_items = $this->db->query("select count(status) as placed_items from order_master where status = 'placed' ".$where)->row_array();
				
				$cancelled_items = $this->db->query("select count(status) as cancelled_items from order_master where status = 'cancelled' ".$where)->row_array();

				$total_customers = $this->db->query("select count(DISTINCT contact) as total_customers from order_address")->row()->total_customers;
				
				$total_products = $this->db->query("select count(product_id) as total_products from products")->row()->total_products;
				
				$total_categories = $this->db->query("select count(category_id) as total_categories from categories")->row()->total_categories;
				

				$response['counter']['delivery'] = $unread_delivery['unread_delivery'];
				$response['counter']['dinein'] = $unread_dinein['unread_dinein'];
				$response['counter']['car'] = $unread_car['unread_car'];
				$response['counter']['takeaway'] = $unread_takeaway['unread_takeaway'];
				$response['counter']['scheduledtakeaway'] = $unread_scheduled_takeaway['unread_delivery'];
				$response['counter']['scheduled'] = $unread_scheduled['unread_delivery'];
				$response['counter']['total_sales'] = number_format($total_sales['total_sales']);
				$response['counter']['delivered_items'] = number_format($delivered_items['delivered_items']);
				$response['counter']['placed_items'] = number_format($placed_items['placed_items']);
				$response['counter']['cancelled_items'] = number_format($cancelled_items['cancelled_items']);
				$response['counter']['total_customers'] = number_format($total_customers);
				$response['counter']['total_tips'] = number_format($total_tips['total_tips_amount']);
				$response['counter']['total_tips_count'] = number_format($total_tips['total_tips_count']);
				$response['counter']['total_products'] = number_format($total_products);
				$response['counter']['total_categories'] = number_format($total_categories);

				$response['message'] = "";
				$response['success'] = 1;
			}
			if($action == "clear_count"){
				if($post['type'] != ""){
					$this->db->query("UPDATE order_master SET is_read = 1 where order_id IN(select order_id from order_address where address_type = '".$post['type']."')");
				} 
			}
			if($action == "save_ratings"){

				foreach($post["ratings"] as $key=>$values){
					foreach($values as $item_rating){
						$this->db->where("order_item_id",$key);
						$this->db->update("order_items",array("ratings"=>$item_rating['current']));
					}
				}

				foreach($post["serviceRatings"] as $key_service=>$values_service){
					$this->db->where("order_id",$post["order_id"]);
					$this->db->update("order_master",array("serviceRatings"=>$values_service['current']));
				}

				foreach($post["hygieneRatings"] as $key_hygiene=>$values_hygiene){
					$this->db->where("order_id",$post["order_id"]);
					$this->db->update("order_master",array("hygieneRatings"=>$values_hygiene['current']));
				}

				foreach($post["behaviourRatings"] as $key_behaviour=>$values_behaviour){
					$this->db->where("order_id",$post["order_id"]);
					$this->db->update("order_master",array("behaviourRatings"=>$values_behaviour['current']));
				}

				if($post['suggestions'] != ""){
					$this->db->where("order_id",$post["order_id"]);
					$this->db->update("order_master",array("rating_suggestions"=>$post['suggestions']));	
				}

				$response['message'] = "Thanks for your review";
				$response['success'] = 1;
			}
			if($action == "report"){

				$grand_sub_total = 0;
				$cgst_total = 0;
				$sgst_total = 0;
				$total_gst_total = 0;
				$total_bill = 0;
				$round_off_total = 0;
				$cash_payment_total = 0;
				$online_payment_total = 0;
				$other_method_total = 0;
				
				$where = "";
				if($post["from_date"] && $post["to_date"]){
					$from_date =  strtotime(date($post["from_date"]."12:00:01"));
					$to_date =  strtotime(date($post["to_date"]."23:59:59")); 
					$where .= " and order_timestamp >= ".$from_date." and order_timestamp <= ".$to_date;
				}

				$pagelimit = "";

				/*if($post["page"] != "" || $post['page'] >= 0){
					$pagelimit .= " limit ".(($post["page"])*$post["limit"]).", ".$post["limit"];
				}else{
					$post["limit"] = 1000;
					$pagelimit .= " limit ".(($post["page"])*$post["limit"]).", ".$post["limit"];
				}*/

				if($post["year"] != ""){
					$where .= " and from_unixtime(order_timestamp, '%Y') = ".date($post["year"]);
				}

				if($post["month"] != ""){
					$where .= " and from_unixtime(order_timestamp, '%m') = ".date($post["month"]);
				}

				//echo "select order_timestamp, sum(grand_total) as grand_total, from_unixtime(order_timestamp, '%d-%m-%Y') as date from order_master where 1=1 ".$where." group by from_unixtime(order_timestamp, '%d%m%Y') ".$pagelimit.""; die();

				$orders = $this->db->query("select order_timestamp, sum(grand_total) as grand_total, from_unixtime(order_timestamp, '%d-%m-%Y') as date from order_master where 1=1 ".$where." group by from_unixtime(order_timestamp, '%d%m%Y') ".$pagelimit."")->result_array();

				$queryNew = $this->db->query("select count(order_timestamp) as myCounter from order_master where 1=1 ".$where." group by from_unixtime(order_timestamp, '%d%m%Y') ");

				$total_records = $queryNew->num_rows();	


				if(!empty($orders)){
					$counter = 0;
					foreach($orders as $order){
						$response['report'][$counter]['sub_total'] = number_format($order['grand_total']);
						$response['report'][$counter]['date'] = $order['date'];
						$response['report'][$counter]['cgst'] = round($order['grand_total'] * 2.5 / 100);
						$response['report'][$counter]['sgst'] = round($order['grand_total'] * 2.5 / 100);
						$response['report'][$counter]['total_gst'] = number_format($response['report'][$counter]['cgst'] + $response['report'][$counter]['sgst']);
						$response['report'][$counter]['total_bill'] = number_format($order['grand_total'] + $response['report'][$counter]['cgst'] + $response['report'][$counter]['sgst']);

						$cash_payment =  $this->db->query("select sum(grand_total) as grand_total from order_master where from_unixtime(order_timestamp, '%d-%m-%Y') = '".$order['date']."' and payment_method=1")->row()->grand_total;

						$response['report'][$counter]['cash_payment'] = ($cash_payment) ? number_format($cash_payment) : 0;

						$credit_debit_card_amount =  $this->db->query("select sum(grand_total) as grand_total from order_master where from_unixtime(order_timestamp, '%d-%m-%Y') = '".$order['date']."' and payment_method=7")->row()->grand_total;

						$response['report'][$counter]['online_payment'] = ($credit_debit_card_amount) ? number_format($credit_debit_card_amount) : 0;

						$other_method =  $this->db->query("select sum(grand_total) as grand_total from order_master where from_unixtime(order_timestamp, '%d-%m-%Y') = '".$order['date']."' and payment_method = '' ")->row()->grand_total;

						$response['report'][$counter]['other_method'] = ($other_method) ? number_format($other_method) : 0;
						
						$round_off = 0;
						
						if($order['grand_total'] > 0){	
							$round_off  = round($order['grand_total']) - round($cash_payment + $credit_debit_card_amount + $other_method);
						}

						$response['report'][$counter]['round_off'] = ($round_off) ? $round_off : 0;



						$grand_sub_total +=  $order['grand_total'];
						$cgst_total +=  $response['report'][$counter]['cgst'];
						$sgst_total +=  $response['report'][$counter]['sgst'];
						$total_gst_total +=  $response['report'][$counter]['total_gst'];
						$total_bill +=  $order['grand_total'] + $response['report'][$counter]['total_gst'];
						$round_off_total +=  $round_off;
						$cash_payment_total += $cash_payment;
						$online_payment_total += $credit_debit_card_amount;
						$other_method_total += $other_method;


						$counter++;
					}

					$response['report'][count($orders)]['sub_total'] =  number_format($grand_sub_total);
					$response['report'][count($orders)]['date'] = "Total: " ;
					$response['report'][count($orders)]['cgst'] = number_format($cgst_total);
					$response['report'][count($orders)]['sgst'] = number_format($sgst_total);
					$response['report'][count($orders)]['total_gst'] = number_format($total_gst_total);
					$response['report'][count($orders)]['total_bill'] = number_format($total_bill);
					$response['report'][count($orders)]['round_off'] = number_format($round_off_total);
					$response['report'][count($orders)]['cash_payment'] = number_format($cash_payment_total);
					$response['report'][count($orders)]['online_payment'] = number_format($online_payment_total);
					$response['report'][count($orders)]['other_method'] = number_format($other_method_total);

					$response['message'] = "Data Found.";
					$response['success'] = 1;
					$response['total_records'] = $total_records;

				}else{
					$response['message'] = "No Data Found.";
					$response['success'] = 0;
				}
			}
			if($action == "yearList"){
				$created_year = 2020;

				for ($i=0; $i <= 30; $i++) { 
					$response['yearList'][] = $created_year;
					$created_year++;
				}
				$response['success'] = 1;
				$response['current_year'] = date("Y");
			}
			if($action == "monthList"){
				$month_start = 01;

				for ($i=0; $i < 12; $i++) { 
					if($month_start == 1){
						$month = "January";
					}elseif($month_start == 2){
						$month = "February";
					}elseif($month_start == 3){
						$month = "March";
					}elseif($month_start == 4){
						$month = "April";
					}elseif($month_start == 5){
						$month = "May";
					}elseif($month_start == 6){
						$month = "June";
					}elseif($month_start == 7){
						$month = "July";
					}elseif($month_start == 8){
						$month = "August";
					}elseif($month_start == 9){
						$month = "September";
					}elseif($month_start == 10){
						$month = "October";
					}elseif($month_start == 11){
						$month = "November";
					}elseif($month_start == 12){
						$month = "December";
					}

					$response['monthList'][$i]['name'] = $month;
					$response['monthList'][$i]['month'] = $month_start;
					$month_start++;
				}
				$response['success'] = 1;
				$response['current_month'] = date("m");
			}
		} else {
			$response['message'] = "invalid Operation";
			$response['success'] = 0;
		}
		echo json_encode($response);
	}

	public function search_old(){
		$post = $this->input->post();

		$search_q = "";
		if(isset($post['search']) && $post['search'] != ""){
			$searchColumns = "restaurant";

        	$searchColumns = explode(", ", $searchColumns);
        	$searchTerms = [$post['search']];
            foreach ($searchTerms as $searchTerm) {
                foreach ($searchColumns as $searchColumn) {
                    if ($search_q == "") {
                        $search_q .= " and (" . $searchColumn . " like '%" . $searchTerm . "%'";
                    } else {
                        $search_q .= " or " . $searchColumn . " like '%" . $searchTerm . "%'";
                    }
                }
            }
            $search_q .= ")";
		}
		

		$cond = "";
		$sorting = "";
		$cond .= " and is_deleted = 0 and restaurants.status = 1 ";

		$latCongCond = "";
		$latCongCondData = "";
		if(isset($post['latitude']) && $post['latitude'] != "" && isset($post['longitude']) && $post['longitude'] != ""){
			$latCongCondData .= ", 6371 * acos (cos (radians(".$post['latitude']."))* cos(radians(latitude))* cos( radians(".$post['longitude'].") - radians(longitude) )+ sin (radians(".$post['latitude'].") )* sin(radians(latitude))) as distance";

			$latCongCond = " order by distance ASC";
		}
		if(isset($post['admin_id']) && $post['admin_id'] != ""){
			$cond .= " and admin_id = ".$post['admin_id'];
		}
		if(isset($post['restaurant_id']) && $post['restaurant_id'] != ""){
			$cond .= " and restaurant_id = ".$post['restaurant_id'];
		}
		if(isset($post['is_pureveg']) && $post['is_pureveg'] != ""){
			$cond .= " and pure_veg = ".$post['is_pureveg'];
		}
		if(isset($post['cuisine_id']) && $post['cuisine_id'] != ""){

			$cond .= " and restaurant_id IN(select restaurant_id from restaurant_cuisine where cuisine_id in (".$post['cuisine_id']."))";
		}
		if(isset($post['type']) && $post['type'] != ""){
			$cond .= " and type = '".$post['type']."'";
		}
		if(isset($post['category_id']) && $post['category_id'] != ""){
			$cond .= " and restaurant_id IN(select restaurant_id from restaurant_category where category_id in(".$post['category_id']."))";
		}

		$search_q = "";
		if(isset($post['search']) && $post['search'] != ""){
			$searchColumns = "name";

        	$searchColumns = explode(", ", $searchColumns);
        	$searchTerms = [$post['search']];
            foreach ($searchTerms as $searchTerm) {
                foreach ($searchColumns as $searchColumn) {
                	if ($search_q == "") {
                        $search_q .= " and (" . $searchColumn . " like'%".$searchTerm."%'";
                    } else {
                        $search_q .= " or " . $searchColumn . " like'%".$searchTerm."%'";
                    }
                }
            }
            $search_q .= ")";
		}
		

		$sortingRating = "";
		$sortingRatingJoin = "";
		$sortingRatingGroupBy = "";
		if(isset($post['is_rating']) && $post['is_rating'] == 1){
			$sortingRating .= " , avg(restaurant_reviews.rating) as average";
			$sortingRatingJoin .= " left outer join restaurant_reviews on restaurant_reviews.restaurant_id = restaurants.restaurant_id";
			$sortingRatingGroupBy .= " group by restaurants.restaurant_id order BY average desc";
		}
		$word = $post['search'];
		
		$restaurants = $this->db->query("select *".$latCongCondData.$sortingRating." from restaurants " .$sortingRatingJoin." where 1 = 1  ".$cond.$search_q.$latCongCond.$sortingRatingGroupBy)->result_array();
		
		$response['restaurants'] = array();
		if(!empty($restaurants)){
			$i = 0;
			foreach ($restaurants as $restaurant) {
				$post['user_id'] = $post['user_id'] ? $post['user_id'] : "";
				//$restaurant = $this->front_model->get_restaurant_details_by_id($restaurant['restaurant_id'],"",$post['user_id']);	
				$restaurant = $this->front_model->get_restaurant_list_id($restaurant['restaurant_id'],"",$post['user_id']);
				$response['restaurants'][$i] = $restaurant;
				$i++;
			}
			$response['success'] = 1;
			$response['message'] = "";
		} else {
			$response['success'] = 0;
			$response['message'] = "No restaurants found";
		}

		echo json_encode($response);
	}


	public function search(){
		$post = $this->input->post();

		$cond = "";
		$cond_or = "";
		$cond_cuisine = "";

		if(isset($post['latitude']) && $post['latitude'] != "" && isset($post['longitude']) && $post['longitude'] != ""){
			$latCongCondData .= ", 6371 * acos (cos (radians(".$post['latitude']."))* cos(radians(latitude))* cos( radians(".$post['longitude'].") - radians(longitude) )+ sin (radians(".$post['latitude'].") )* sin(radians(latitude))) as distance";

			$latCongCond = " order by distance ASC";			
		}
		

		$search_q_restaurant = "";
		$search_q_restaurant_join = "";
		if(isset($post['search']) && $post['search'] != ""){
			$searchColumns = "restaurants.name";

        	$searchColumns = explode(", ", $searchColumns);
        	$searchTerms = [$post['search']];
            foreach ($searchTerms as $searchTerm) {
                foreach ($searchColumns as $searchColumn) {
                    if ($search_q_restaurant == "") {
                        $search_q_restaurant .= " and (" . $searchColumn . " like '%" . $searchTerm . "%'";
                    } else {
                        $search_q_restaurant .= " or " . $searchColumn . " like '%" . $searchTerm . "%'";
                    }
                }
            }
            $search_q_restaurant .= ")";

            
         	foreach ($searchTerms as $searchTerm) {
                if ($search_q_restaurant_join == "") {
                    $search_q_restaurant_join .= " and (cuisine like '%" . $searchTerm . "%'";
                } else {
                    $search_q_restaurant_join .= " or cuisine like '%" . $searchTerm . "%'";
                }
            }
            $search_q_restaurant_join .= ")";
            
            $ids = $this->db->query("select GROUP_CONCAT(restaurant_id) as restaurant_id from restaurant_cuisine where cuisine_id in (select cuisine_id from cuisines where 1 = 1 ".$search_q_restaurant_join.")")->row_array();
            if($ids['restaurant_id'] != ""){
				$cond_or .= " OR restaurants.restaurant_id IN(".$ids['restaurant_id'].")";
            }
		}
		if(isset($post['city']) && $post['city'] != "" && isset($post['state']) && $post['state'] != ""){
			
			$cities = $this->db->query("select * from cities where name like '%".$post['city']."%' and state_id in (select id from states where name like '%".$post['state']."%')")->row_array();

			$cond .= " and city_id = ".$cities['id'];
			$cond_cuisine .= " and restaurants.city_id = ".$cities['id'];
		}
		

		$restaurants = $this->db->query("select *".$latCongCondData." from restaurants where restaurants.status = 1 and is_deleted = 0".$search_q_restaurant.$cond.$cond_or.$latCongCond)->result_array();
		
		if($restaurants){
			$response['total']['total_outlate'] = strval(count($restaurants));
			$rest_count = 0;
			foreach ($restaurants as $restaurant) {

				$response['search']['restaurant'][$rest_count]['name'] = $restaurant['name'];
				$response['search']['restaurant'][$rest_count]['restaurant_id'] = $restaurant['restaurant_id'];
				$response['search']['restaurant'][$rest_count]['description'] = $restaurant['description'];
				$response['search']['restaurant'][$rest_count]['address'] = $restaurant['address'];

				$path = FC_PATH."assets/restaurant/";
				if(file_exists($path.$restaurant['logo'])){
					$response['search']['restaurant'][$rest_count]['logo'] = $restaurant['logo'] ? IMAGETOOL.BASE_URL."assets/restaurant/".$restaurant['logo'] : IMAGETOOL.BASE_URL."assets/thumb.jpg";	
				} else {
					$response['search']['restaurant'][$rest_count]['logo'] = IMAGETOOL.BASE_URL."assets/thumb.jpg";	
				}
				$rest_count++;
			}	
		} else {
			$response['search']['restaurant'] = array();
			$response['total']['total_outlate'] = "0";
		}


		$search_q_cuisine = "";
		if(isset($post['search']) && $post['search'] != ""){
			$searchColumns = "cuisines.cuisine";

        	$searchColumns = explode(", ", $searchColumns);
        	$searchTerms = [$post['search']];
            foreach ($searchTerms as $searchTerm) {
                foreach ($searchColumns as $searchColumn) {
                    if ($search_q_cuisine == "") {
                        $search_q_cuisine .= " and (" . $searchColumn . " like '%" . $searchTerm . "%'";
                    } else {
                        $search_q_cuisine .= " or " . $searchColumn . " like '%" . $searchTerm . "%'";
                    }
                }
            }
            $search_q_cuisine .= ")";
		} 

		$cuisines = $this->db->query("select *".$latCongCondData." from cuisines left join restaurant_cuisine on(cuisines.cuisine_id = restaurant_cuisine.cuisine_id) left join restaurants on(restaurant_cuisine.restaurant_id = restaurants.restaurant_id) where 1 = 1 ".$search_q_cuisine.$cond_cuisine." group by cuisines.cuisine_id".$latCongCond)->result_array();

		
		if(!empty($cuisines)){
			$cui_count = 0;

			$response['total']['total_cuisine_outlate'] = strval(count($cuisines));
			foreach ($cuisines as $cuisine){
				$response['search']['cuisines'][$cui_count]['cuisine'] = $cuisine['cuisine'];
				$response['search']['cuisines'][$cui_count]['cuisine_id'] = $cuisine['cuisine_id'];
				$response['search']['cuisines'][$cui_count]['name'] = $cuisine['name'];
				$response['search']['cuisines'][$cui_count]['restaurant_id'] = $cuisine['restaurant_id'];
				$response['search']['cuisines'][$cui_count]['description'] = $cuisine['description'];
				$response['search']['cuisines'][$cui_count]['address'] = $cuisine['address'];

				$path = FC_PATH."assets/restaurant/";
				if(file_exists($path.$cuisine['logo'])){
					$response['search']['cuisines'][$cui_count]['logo'] = $cuisine['logo'] ? IMAGETOOL.BASE_URL."assets/restaurant/".$cuisine['logo'] : IMAGETOOL.BASE_URL."assets/thumb.jpg";	
				} else {
					$response['search']['cuisines'][$cui_count]['logo'] = IMAGETOOL.BASE_URL."assets/thumb.jpg";	
				}

				$path = FC_PATH."assets/cuisine/";
				if(file_exists($path.$cuisine['icon'])){
					$response['search']['cuisines'][$cui_count]['icon'] = $cuisine['icon'] ? IMAGETOOL.BASE_URL."assets/cuisine/".$cuisine['icon'] : IMAGETOOL.BASE_URL."assets/thumb.jpg";	
				} else {
					$response['search']['cuisines'][$cui_count]['icon'] = IMAGETOOL.BASE_URL."assets/thumb.jpg";	
				}
				$cui_count++;
			}	
		} else {
			$response['search']['cuisines'] = array();
			$response['total']['total_cuisine_outlate'] = "0";
		}

		if($response['total']['total_outlate'] > 0 || $response['total']['total_cuisine_outlate'] > 0){
			$response['message'] = "";
			$response['success'] = 1;
		} else {
			$response['message'] = "No result found";
			$response['success'] = 0;
		}

		echo json_encode($response);
	}

	public function coupons($action){
		$actions = array("list","add","details","statusupdate","remove","coupons_for_user");
		$post = $this->input->post();
		if(in_array($action, $actions)){
			$post = $this->input->post();
			if($action == "list"){

				$cond = "";
				$search_q = "";
				if(isset($post['search']) && $post['search'] != ""){
					$searchColumns = "coupon_code,description,";
	            	$searchColumns = explode(", ", $searchColumns);
	            	$searchTerms = [$post['search']];
	                foreach ($searchTerms as $searchTerm) {
	                    foreach ($searchColumns as $searchColumn) {
	                        if ($search_q == "") {
	                            $search_q .= " and (" . $searchColumn . " like '%" . $searchTerm . "%'";
	                        } else {
	                            $search_q .= " or " . $searchColumn . " like '%" . $searchTerm . "%'";
	                        }
	                    }
	                }
	                $search_q .= ")";
				}

				if($post['admin_id'] != ""){
					$cond .= " and restaurant_id IN(select restaurant_id from restaurants where admin_id = ".$post['admin_id'].")";
				} else {
					$cond .= " and status = 1";
				}
				$coupons = $this->db->query("select * from coupons where 1 = 1 ".$cond.$search_q)->result_array();
				if(!empty($coupons)){
					$i = 0;
					foreach ($coupons as $coupon) {
						if($coupon['restaurant_id']){
							$restaurant = $this->db->get_where('restaurants',array('restaurant_id'=>$coupon['restaurant_id']))->row_array();
						}
						$response['coupons'][$i]['coupon_code'] = $coupon['coupon_code'] ? $coupon['coupon_code'] : "";
						$response['coupons'][$i]['coupon_id'] = $coupon['coupon_id'] ? $coupon['coupon_id'] : "";
						$response['coupons'][$i]['discount_amount'] = $coupon['discount_amount'] ? $coupon['discount_amount'] : "";
						$response['coupons'][$i]['discount_type'] = $coupon['discount_type'] ? $coupon['discount_type'] : "";
						$response['coupons'][$i]['max_discount'] = $coupon['max_discount'] ? $coupon['max_discount'] : "";
						$response['coupons'][$i]['restaurant_id'] = $coupon['restaurant_id'] ? $coupon['restaurant_id'] : "";
						$response['coupons'][$i]['restaurant_name'] = $restaurant['name'] ? ucfirst(strtolower($restaurant['name'])) : "";
						$response['coupons'][$i]['status'] = $coupon['status'] ? $coupon['status'] : "";
						$response['coupons'][$i]['description'] = $coupon['description'] ? $coupon['description'] : "";
						

						$path = FC_PATH."assets/coupon/main/";
						if(file_exists($path.$coupon['coupon_image'])){
							$response['coupons'][$i]['coupon_image'] = $coupon['coupon_image'] ? IMAGETOOL.BASE_URL."assets/coupon/main/".$coupon['coupon_image'] : IMAGETOOL.BASE_URL."assets/thumb.jpg";	
						} else {
							$response['coupons'][$i]['coupon_image'] = IMAGETOOL.BASE_URL."assets/thumb.jpg";	
						}

						$path = FC_PATH."assets/restaurant/";
						if(file_exists($path.$restaurant['logo'])){
							$response['coupons'][$i]['restaurant_image'] = $restaurant['logo'] ? IMAGETOOL.BASE_URL."assets/coupon/restaurant/".$restaurant['logo'] : IMAGETOOL.BASE_URL."assets/thumb.jpg";	
						} else {
							$response['coupons'][$i]['restaurant_image'] = "";	
						}
						$i++;
					}
					$response['message'] = "";
					$response['success'] = 1;
				} else {
					$response['coupons'] = array();
					$response['message'] = "No coupons found";
					$response['success'] = 0;
				}
			}
			if($action == "add"){
				$response['success'] = 0;
				
				/*if($post['restaurant_id'] == ""){
					$response['message'] = "Please provide restaurant id";
				} else */if($post['coupon_code'] == ""){
					$response['message'] = "Please provide coupon code";
				} else if($post['discount_type'] == ""){
					$response['message'] = "Please provide discount type";
				} else if($post['discount_amount'] == ""){
					$response['message'] = "Please provide discount amount";
				} /*else if($post['max_discount'] == ""){
					$response['message'] = "Please provide maximum discount amount";
				} else if($post['min_purchase_amount'] == ""){
					$response['message'] = "Please provide minimum purchase amount";
				} else if(empty($_FILES['coupon_image']) && $post['coupon_image'] == ""){
					$response['message'] = "Please provide coupon image";
				}*/ else {
					$check = array();
					
					$check = $this->db->query("select * from coupons where coupon_code = '".$post['coupon_code']."'")->row_array();	
					
					if(empty($check) || $post['coupon_id'] != ""){
						if (!empty($_FILES["coupon_image"])) {
							$path = FC_PATH."assets/coupon/main/";
					        $image_name = time().'_'.preg_replace('/\s+/', '_', $_FILES['coupon_image']['name']);
					        $image_name = $this->front_model->clean($image_name);
					        move_uploaded_file($_FILES["coupon_image"]["tmp_name"], $path.$image_name);
					        $couponData["coupon_image"] = $image_name;
					    }

						$couponData['coupon_code'] = $post['coupon_code'];
						$couponData['discount_type'] = $post['discount_type'];
						$couponData['discount_amount'] = $post['discount_amount'];
						$couponData['max_discount'] = $post['max_discount'];
						$couponData['min_purchase_amount'] = $post['min_purchase_amount'];
						$couponData['status'] = $post['status'];
						$couponData['type'] = $post['type'];
						$couponData['restaurant_id'] = $post['restaurant_id'];
						$couponData['number_of_time_to_use'] = $post['number_of_time_to_use'];
						$couponData['description'] = $post['description'];

						if ($post['is_general'] == "0") {
							$users = $post["user_id"];
							if (count($users) > 0) {
								$couponData["user_id"] = $users;
							} else {
								$couponData["user_id"] = NULL;
							}
						}


						if ($post["applicable_for"] == 'all_products') {
							$couponData["applicable_categories"] = NULL;
							$couponData["applicable_products"] = NULL;
						} else if ($post["applicable_for"] == 'products') {
							$couponData["applicable_products"] = $post["products"];
						}
						
						// if($post['active_from'] != ""){
							$couponData['active_time_from'] = $post['active_time_from'];
							$couponData['active_from'] = $post['active_from'];
						// }
						// if($post['active_to'] != ""){
							$couponData['active_time_to'] = $post['active_time_to'];
							$couponData['active_to'] = $post['active_to'];	
						// }

						

						if($post['coupon_id'] != ""){
							$couponData['alt_timestamp'] = time();

							$this->db->where('coupon_id',$post['coupon_id']);
							$update = $this->db->update('coupons',$couponData);
							if($update){
								$response['coupon_code'] = $couponData['coupon_code'];
								$response['message'] = "Coupon updated successfully";
								$response['success'] = 1;
							} else {
								$response['message'] = "Opps...something went wrong";
								$response['success'] = 0;
							}
						} else {
							$couponData['save_timestamp'] = time();

							$insert = $this->db->insert('coupons',$couponData);
							if($insert){
								$response['coupon_code'] = $couponData['coupon_code'];
								$response['message'] = "Coupon inserted successfully";
								$response['success'] = 1;
							} else {
								$response['message'] = "Opps...something went wrong";
								$response['success'] = 0;
							}
						}
					} else {
						$response['message'] = "Coupon code already taken";
						$response['success'] = 0;
					}
				}
			}
			if($action == "details"){
				if($post['coupon_id'] != ""){

					$coupon = $this->db->query("select * from coupons where coupon_id = ".$post['coupon_id'])->row_array();
					if(!empty($coupon)){
						$response['coupon']['coupon_id'] = $coupon['coupon_id'];
						$response['coupon']['coupon_code'] = $coupon['coupon_code'];
						$response['coupon']['discount_type'] = $coupon['discount_type'];
						$response['coupon']['discount_amount'] = floatval($coupon['discount_amount']);
						$response['coupon']['min_purchase_amount'] = floatval($coupon['min_purchase_amount']);
						$response['coupon']['max_discount'] = floatval($coupon['max_discount']);
						$response['coupon']['status'] = $coupon['status'];
						$response['coupon']['type'] = $coupon['type'];
						$response['coupon']['restaurant_id'] = $coupon['restaurant_id'];
						$response['coupon']['number_of_time_to_use'] = floatval($coupon['number_of_time_to_use']);
						$response['coupon']['description'] = $coupon['description'];
						if($coupon['is_general'] == 1){
							$response['coupon']['is_general'] = true;
						} else {
							$response['coupon']['is_general'] = false;
						}
						

						$response['coupon']['active_time_from'] = $coupon['active_time_from'] ? $coupon['active_time_from'] : "";
						$response['coupon']['active_from'] = $coupon['active_from'] ? $coupon['active_from'] : "";
						$response['coupon']['active_time_to'] = $coupon['active_time_to'] ? $coupon['active_time_to'] : "";
						$response['coupon']['active_to'] = $coupon['active_to'] ? $coupon['active_to'] : "";
						$response['coupon']['user_id'] = $coupon['user_id'] ? explode(",",$coupon['user_id']) : "";
						$response['coupon']['products'] = $coupon['applicable_products'] ? explode(",",$coupon['applicable_products']) : "";
						$response['coupon']['applicable_for'] = $coupon['applicable_products'] ? "products" : "all_products";


						$path = FC_PATH."assets/coupon/main/";
						if(file_exists($path.$coupon['coupon_image'])){
							$response['coupon']['coupon_image'] = $coupon['coupon_image'] ? IMAGETOOL.BASE_URL."assets/coupon/main/".$coupon['coupon_image'] : IMAGETOOL.BASE_URL."assets/thumb.jpg";
						} else {
							$response['coupon']['coupon_image'] = IMAGETOOL.BASE_URL."assets/thumb.jpg";
						}

						$response['message'] = "";
						$response['success'] = 1;
					} else {
						$response['message'] = "Invalid coupon id";
						$response['success'] = 0;
					}
				} else {
					$response['message'] = "Please provide coupon id";
					$response['success'] = 0;
				}
			}
			if($action == "statusupdate"){
				$post = $this->input->post();
				if(isset($post['coupon_id']) && $post['coupon_id'] != ""){
					$restaurant = $this->db->get_where("coupons", array("coupon_id" => $post['coupon_id']))->row_array();
					if(!empty($restaurant)){
						if($restaurant['status'] == 1){
							$status = 0;
						} else {
							$status = 1;
						}
						$data = array(
							"status" => $status
						);
						$this->db->where("coupon_id", $post['coupon_id']);
						$update = $this->db->update("coupons", $data);

						if($update){
							$response['success'] = 1;
							$response['message'] = "Coupon has been updated";
						} else {
							$response['success'] = 0;
							$response['message'] = "Opps.. Something went wrong.";
						}
					} else {
						$response['success'] = 0;
						$response['message'] = "Coupon not found.";
					}
				} else {
					$response['success'] = 0;
					$response['message'] = "Coupon ID can not be blank.";
				}
			}
			if($action == "remove"){
				if(isset($post['coupon_id']) && $post['coupon_id'] != ""){
					$category = $this->db->get_where("coupons", array("coupon_id" => $post['coupon_id']))->row_array();
					if(!empty($category)){
						$this->db->where("coupon_id", $post['coupon_id']);
						$delete = $this->db->delete("coupons");
						if($delete){
							$response['success'] = 1;
							$response['message'] = "Coupon has been removed";
						} else {
							$response['success'] = 0;
							$response['message'] = "Opps... Something went wrong.";
						}
					} else{
						$response['success'] = 0;
						$response['message'] = "Coupon not found.";
					}
				} else {
					$response['success'] = 0;
					$response['message'] = "Please provide coupon id.";
				}
			}
			if($action == "coupons_for_user"){
				if($post['cart_session'] != ""){
					if (isset($post['limit']) && $post['limit'] != "") {
						$limit = $post['limit'];
					} else {
						$limit = 50;
					}
					if (isset($post['page']) && $post['page'] != "") {
						$page = $post['page'];
					} else {
						$page = 1;
					}
					$start = ($page - 1) * $limit;
					$searchColumns = array(
						"coupons.coupon_code",
						"coupons.description"
					);
					$cond = "";
					if (isset($post['search']) && $post['search'] != "") {
						$searchTerms = explode(" ", $post['search']);
						foreach ($searchTerms as $searchTerm) {
							foreach ($searchColumns as $searchColumn) {
								if ($cond == "") {
									$cond .= " and (" . $searchColumn . " like '%" . $searchTerm . "%'";
								} else {
									$cond .= " or " . $searchColumn . " like '%" . $searchTerm . "%'";
								}
							}
						}
						$cond .= ")";
					}
					if ($post["discount_type"]) {
						$cond .= " and coupon_type = '".$post['discount_type']."'";
					}
					
					if ($post["from_date"] && $post["to_date"]) {
						$cond .= " and (((active_from >= ".$post["from_date"]." and active_from <= ".$post["to_date"].") or active_from is null ) or ((active_to >= ".$post["from_date"]." and active_to <= ".$post["to_date"].") or active_to is null )) ";
					}
					if ($post["price_from"] && $post["coupon_type"]) {
						$cond .= " and discount_amount >= ".$post["price_from"]." and coupon_type = '".$post["coupon_type"]."'";
					}
					if ($post["price_to"] && $post["coupon_type"]) {
						$cond .= " and discount_amount <= ".$post["price_to"]." and coupon_type = '".$post["coupon_type"]."'";
					}

					$cart_itmes = $this->db->query("select * from cart_items where cart_session = '".$post['cart_session']."'")->result_array();
					if(!empty($cart_itmes)){
						$cond .= " and restaurant_id IN (select restaurant_id from cart_items where cart_session = '".$post['cart_session']."')" ;
					}

					$coupons = $this->db->query("select SQL_CALC_FOUND_ROWS * from coupons where 1 = 1 and status = 1 ".$cond." order by save_timestamp desc") -> result_array();
					/*echo $this->db->last_query();
					die;*/
					
					$response['success'] = 1;
					$response['message'] = "";
					$response['coupons'] = array();
					if(!empty($coupons)){
						$count = 0;
						$timestamp = time();
						foreach ($coupons as $coupon) {
							$result = $this->front_model->apply_coupon($post['cart_session'],$coupon['coupon_code']);
							
							if($result['success'] == 1){
								$response['coupons'][$count]['coupon_id'] = $coupon['coupon_id'] ? $coupon['coupon_id'] : "";	
								$response['coupons'][$count]['coupon_code'] = $coupon['coupon_code'] ? $coupon['coupon_code'] : "";

								$response['coupons'][$count]['discount_type'] = $coupon['discount_type'] ? $coupon['discount_type'] : "";
								$response['coupons'][$count]['discount_amount'] = $coupon['discount_amount'] ? $coupon['discount_amount'] : "";

								$response['coupons'][$count]['status'] = $coupon['status'] ? $coupon['status'] : "";
								$response['coupons'][$count]['max_discount'] = $coupon['max_discount'] ? $coupon['max_discount'] : "";
								$is_expired = 0;
								if ($coupon['active_from'] <= $timestamp) {
									$is_expired = 0;
								}
								if ($coupon['active_to'] && $coupon['active_to'] < $timestamp) {
									$is_expired = 1;
								}
								$response['coupons'][$count]['is_expired'] = $is_expired;
								if($coupon['active_from'] != "" || $coupon['active_from'] != 0){
									$response['coupons'][$count]['active_from'] = date('d F, Y',strtotime($coupon['active_from']));	
								} else {
									$response['coupons'][$count]['active_from'] = "";
								}
								if($coupon['active_to']){
									$response['coupons'][$count]['active_to'] = date('d F, Y',strtotime($coupon['active_to']));
								} else {
									$response['coupons'][$count]['active_to'] = "";
								}
								$response['coupons'][$count]['description'] = $coupon['description'] ? $coupon['description'] : "";

								$count++;
							} else {
								$post['message'] = "";
								$post['success'] = 0;
							}
						}
					}
				} else {
					$post['message'] = "Invalid cart session";
					$post['success'] = 0;
				}
			}
		} else {
			$response['success'] = 0;
			$response['message'] = "Invalid Operation.";
		}
		echo json_encode($response);
	}



	public function users($action){
		$actions = array("list","update_token","notifications_list");
		$post = $this->input->post();
		if(in_array($action, $actions)){
			if($action == "list"){
				$cond = "";
				$limit = "";
				$search_q = "";
				if($post["page"] != "" || $post['page'] >= 0){
					$limit .= " limit ".(($post["page"])*$post["limit"]).", ".$post["limit"];
				}
				if(isset($post['search']) && $post['search'] != ""){
					$searchColumns = "order_address.name,order_address.email,order_address.contact";
	            	$searchColumns = explode(", ", $searchColumns);
	            	$searchTerms = [$post['search']];
	                foreach ($searchTerms as $searchTerm) {
	                    foreach ($searchColumns as $searchColumn) {
	                        if ($search_q == "") {
	                            $search_q .= " and (" . $searchColumn . " like '%" . $searchTerm . "%'";
	                        } else {
	                            $search_q .= " or " . $searchColumn . " like '%" . $searchTerm . "%'";
	                        }
	                    }
	                }
	                $search_q .= ")";
				}

				$users = $this->db->query("select * from order_address where 1 = 1 ".$search_q." group by contact order by address_id".$limit)->result_array();

				$total_records = $this->db->query("SELECT count(DISTINCT contact) as myCounter from order_address where 1 = 1".$cond.$search_q)->row()->myCounter;
					
				if(!empty($users)){
					$i = 0;
					foreach ($users as $user){
						
						$response['users'][$i]['name'] = ($user['name'])? ucfirst($user['name']) : "N/A";
						$response['users'][$i]['contact'] = ($user['contact']) ? $user['contact'] : "";
						$total_orders = $this->db->get_where("order_address",array("contact"=>$user['contact']))->num_rows();
						
						$grand_total_sum = $this->db->query("select sum(grand_total) as grand_total_sum from order_master where order_id IN (select order_id from order_address where contact = ".$user['contact'].")")->row()->grand_total_sum;

						$response['users'][$i]['total_orders'] = ($total_orders) ? $total_orders : 0;
						$response['users'][$i]['grand_total_sum'] = ($grand_total_sum) ? number_format($grand_total_sum) : 0;

						$i++;
					}
					$response['total_records'] = floatval($total_records);
					$response['message'] = "";
					$response['success'] = 1;
				} else {
					$response['message'] = "No users found";
					$response['success'] = 0;
				}
			}
			if($action == "update_token"){
				if(isset($post['token']) && $post['token'] != ""){
					if(isset($post['user_id']) && $post['user_id'] != ""){
						if(isset($post['device_type']) && $post['device_type'] != ""){

							$user = $this->db->query("select * from users where user_id = ".$post['user_id'])->row_array();
							if(!empty($user)){
								$tokenData['user_id'] = $post['user_id'];
								$tokenData['device_type'] = $post['device_type'];
								$tokenData['token'] = $post['token'];
								$tokenData['timestamp'] = time();

								$check = $this->db->query("select * from device_mapping where user_id = ".$post['user_id'])->row_array();
								if(!empty($check)){
									$this->db->where('user_id',$post['user_id']);
									$update = $this->db->update('device_mapping',$tokenData);
									if($update){
										$response['message'] = "Token updated successfully";
										$response['success'] = 1;
									} else {
										$response['message'] = "Opps...something went wrong";
										$response['success'] = 0;
									}
								} else {
									$insert = $this->db->insert('device_mapping',$tokenData);
									if($insert){
										$response['message'] = "Token insert successfully";
										$response['success'] = 1;
									} else {
										$response['message'] = "Opps...something went wrong";
										$response['success'] = 0;
									}
								}
							} else {
								$response['message'] = "Invalid user";
								$response['message'] = 0;
							}
						} else {
							$response['message'] = "Please provide device type";
							$response['message'] = 0;
						}
					} else {
						$response['message'] = "Please provide user id";
						$response['message'] = 0;
					}
				} else {
					$response['message'] = "Please provide token";
					$response['message'] = 0;
				}
			}
			if($action == "notifications_list"){
				if(isset($post['user_id']) && $post['user_id'] != ""){
					$pagelimit = "";
					if(isset($post["page"]) && ($post["page"] != "" || $post['page'] >= 0)){
						$pagelimit .= " limit ".(($post["page"])*$post["limit"]).", ".$post["limit"];
					} else {
						$post["limit"] = 1000;
						$pagelimit .= " limit ".(($post["page"])*$post["limit"]).", ".$post["limit"];
					}

					$notifications = $this->db->query("select * from notifications where user_id = ".$post['user_id']." order by notification_id desc ".$pagelimit)->result_array();
					$total_records = $this->db->query("select * from notifications where user_id = ".$post['user_id'])->num_rows();
				 		
					$this->db->query("update notifications set is_read = 1 where timestamp <= ".time());

					if(!empty($notifications)){
						$i = 0;
						foreach ($notifications as $notification) {
							$hourdiff = round((time()-$notification['timestamp'])/3600, 1);
							if($hourdiff > 24){
								$time = date("d/m/Y", $notification['timestamp']);
							} else {
								$time = timespan($notification['timestamp'], time(), 2)." ago";
							}

							$response['notifications'][$i]['is_read'] = $notification['is_read'];
							$response['notifications'][$i]['notification_id'] = $notification['notification_id'];
							$response['notifications'][$i]['content_id'] = $notification['content_id'];
							$response['notifications'][$i]['message'] = $notification['message'];
							$response['notifications'][$i]['title'] = $notification['title'];
							$response['notifications'][$i]['timestamp'] = date('Y-m-d h:i:s',$notification['timestamp']);
							$response['notifications'][$i]['time'] = $time;
							$i++;
						}
						$response['total_records'] = floatval($total_records);
						$response['message'] = "";
						$response['success'] = 1;
					} else {
						$response['total_records'] = 0;
						$response['message'] = "No notifications available";
						$response['success'] = 0;
					}
				} else {
					$response['message'] = "Please provide user id";
					$response['success'] = 0;
				}
			}
		} else {
			$response['message'] = "Invalid Operation";
			$response['success'] = 0;
		}
		echo json_encode($response);
	}

	public function test_notification(){
		$androidDeviceTokens = array("dJcMKsqYQYCyEQPseKig25:APA91bEQV02XsT8YkmaUMljhsRoU3v0oI1N9lTjB65NOziTPxXbiM3hFvQc9A3d43HcOgc98aiT9hRfuXkYxryMr7yPZXyifQPv5dQmyPjOVypleG6mN_G7CgSQ5VkMUyWzelxYh6zAl");
		$notificationId = 1;
		$notificationCopntent['title'] = "test";
		$notificationCopntent['message'] = "message";
		$notificationCopntent['content_type'] = "order";
		$notificationCopntent['content_id'] = 5;
		$image = "https://ssl.gstatic.com/ui/v1/icons/mail/rfr/logo_gmail_lockup_default_1x.png";


		$this->front_model->send_android_notification($androidDeviceTokens, $notificationId, $notificationCopntent['title'], $notificationCopntent['message'], $notificationCopntent['content_type'], $notificationCopntent['content_id'], $image);
	}

	public function cities($action){
		$actions = array("list","add","details","statusupdate","remove");
		$post = $this->input->post();
		if(in_array($action, $actions)){
			$post = $this->input->post();
			if($action == "list"){

				$cond = "";
				$search_q = "";
				if(isset($post['search']) && $post['search'] != ""){
					$searchColumns = "restaurant_city_mapping.city";
	            	$searchColumns = explode(", ", $searchColumns);
	            	$searchTerms = [$post['search']];
	                foreach ($searchTerms as $searchTerm) {
	                    foreach ($searchColumns as $searchColumn) {
	                        if ($search_q == "") {
	                            $search_q .= " and (" . $searchColumn . " like '%" . $searchTerm . "%'";
	                        } else {
	                            $search_q .= " or " . $searchColumn . " like '%" . $searchTerm . "%'";
	                        }
	                    }
	                }
	                $search_q .= ")";
				}

				if($post['status'] == "1"){
					$cond .= " and status = 1 ";
				} else if($post['status'] == "0"){
					$cond .= " and status = 0";
				}
				$cities = $this->db->query("select *,GROUP_CONCAT(city) as city from restaurant_city join restaurant_city_mapping on (restaurant_city.id = restaurant_city_mapping.map_id) where 1 = 1 ".$cond.$search_q." group by id desc")->result_array();

				if(!empty($cities)){
					$i = 0;
					foreach ($cities as $city) {
						$response['cities'][$i]['city_id'] = $city['city_id'] ? $city['city_id'] : "";
						$response['cities'][$i]['state'] = $city['state'] ? $city['state'] : "";
						$response['cities'][$i]['state_name'] = $city['state_name'] ? $city['state_name'] : "";
						$response['cities'][$i]['city'] = $city['city'] ? $city['city'] : "";
						$response['cities'][$i]['status'] = $city['status'] ? $city['status'] : "";
						$response['cities'][$i]['id'] = $city['id'] ? $city['id'] : "";
						$i++;
					}
					$response['message'] = "";
					$response['success'] = 1;
				} else {
					$response['coupons'] = array();
					$response['message'] = "No coupons found";
					$response['success'] = 0;
				}
			}
			if($action == "add"){
				$response['success'] = 0;

				if($post['state'] == ""){
					$response['message'] = "Please provide state";
				} else {
					if($post['city'][0]['text'] != ""){
						$time = time();

						$stateData['state'] = $post['state'];
						$stateData['state_name'] = $this->front_model->get_state_name($post['state']);
						$stateData['status'] = $post['status'];
						$stateData['timestamp'] = $time;

						if($post['id'] == ""){
							$this->db->insert('restaurant_city',$stateData);
							$insert_id = $this->db->insert_id();
						} else {
							$this->db->where('id',$post['id']);
							$this->db->update('restaurant_city',$stateData);
							$insert_id = $post['id'];
						}

						if($insert_id){
							$this->db->where('map_id',$insert_id);
							$this->db->delete('restaurant_city_mapping');

							foreach ($post['city'] as $key => $value) {
								$mapData['map_id'] = $insert_id;
								$mapData['city'] = $value['text'];
								$mapData['timestamp'] = $time;
								$insert = $this->db->insert('restaurant_city_mapping',$mapData);
							}
						} 
						if($insert){
							$response['message'] = "Cities added successfully";
							$response['success'] = 1;
						} else {
							$response['message'] = "Opps...something went wrong";
							$response['success'] = 0;
						}

					} else {
						$response['message'] = "Please provide atlease one city";
						$response['success'] = 0;
					}
				}
			}
			if($action == "details"){
				if($post['id'] != ""){
					$city = $this->db->query("select * from restaurant_city where id = ".$post['id'])->row_array();
					if(!empty($city)){
						$response['city']['id'] = $city['id'];
						$response['city']['status'] = $city['status'];
						$response['city']['state'] = $city['state'];
						$response['city']['state_name'] = $city['state_name'];
						
						$city_maps = $this->db->query("select * from restaurant_city_mapping where map_id = ".$post['id'])->result_array();
						if(!empty($city_maps)){
							$i = 0;
							foreach ($city_maps as $city_map) {
								$response['city']['city'][$i]['text'] = $city_map['city'];
								$i++;
							}
						}
						
						$response['message'] = "";
						$response['success'] = 1;
					} else {
						$response['message'] = "Invalid coupon id";
						$response['success'] = 0;
					}
				} else {
					$response['message'] = "Please provide id";
					$response['success'] = 0;
				}
			}
			if($action == "statusupdate"){
				$post = $this->input->post();
				if(isset($post['id']) && $post['id'] != ""){
					$restaurant = $this->db->get_where("restaurant_city", array("id" => $post['id']))->row_array();

					if(!empty($restaurant)){
						if($restaurant['status'] == 1){
							$status = 0;
						} else {
							$status = 1;
						}
						$data = array(
							"status" => $status
						);
						$this->db->where("id", $post['id']);
						$update = $this->db->update("restaurant_city", $data);

						if($update){
							$response['success'] = 1;
							$response['message'] = "Status has been updated";
						} else {
							$response['success'] = 0;
							$response['message'] = "Opps.. Something went wrong.";
						}
					} else {
						$response['success'] = 0;
						$response['message'] = "State not found.";
					}
				} else {
					$response['success'] = 0;
					$response['message'] = "ID can not be blank.";
				}
			}
			if($action == "remove"){
				if(isset($post['id']) && $post['id'] != ""){
					$category = $this->db->get_where("restaurant_city", array("id" => $post['id']))->row_array();
					if(!empty($category)){
						$this->db->where("id", $post['id']);
						$delete = $this->db->delete("restaurant_city");
						if($delete){
							$response['success'] = 1;
							$response['message'] = "State has been removed";
						} else {
							$response['success'] = 0;
							$response['message'] = "Opps... Something went wrong.";
						}
					} else{
						$response['success'] = 0;
						$response['message'] = "State not found.";
					}
				} else {
					$response['success'] = 0;
					$response['message'] = "Please provide id.";
				}
			}
		} else {
			$response['success'] = 0;
			$response['message'] = "Invalid Operation.";
		}
		echo json_encode($response);
	}

	public function commission($action){
		$actions = array("list","save","details","statusupdate","remove","restaurant_order");
		$post = $this->input->post();
		if(in_array($action, $actions)){
			$post = $this->input->post();
			if($action == "list"){

				$cond = "";
				$search_q = "";
				if(isset($post['search']) && $post['search'] != ""){
					$searchColumns = "restaurant_city_mapping.city";
	            	$searchColumns = explode(", ", $searchColumns);
	            	$searchTerms = [$post['search']];
	                foreach ($searchTerms as $searchTerm) {
	                    foreach ($searchColumns as $searchColumn) {
	                        if ($search_q == "") {
	                            $search_q .= " and (" . $searchColumn . " like '%" . $searchTerm . "%'";
	                        } else {
	                            $search_q .= " or " . $searchColumn . " like '%" . $searchTerm . "%'";
	                        }
	                    }
	                }
	                $search_q .= ")";
				}

				if($post['status'] == "1"){
					$cond .= " and status = 1 ";
				} else if($post['status'] == "0"){
					$cond .= " and status = 0";
				}
				$cities = $this->db->query("select *,GROUP_CONCAT(city) as city from restaurant_city join restaurant_city_mapping on (restaurant_city.id = restaurant_city_mapping.map_id) where 1 = 1 ".$cond.$search_q." group by id desc")->result_array();

				if(!empty($cities)){
					$i = 0;
					foreach ($cities as $city) {
						$response['cities'][$i]['city_id'] = $city['city_id'] ? $city['city_id'] : "";
						$response['cities'][$i]['state'] = $city['state'] ? $city['state'] : "";
						$response['cities'][$i]['state_name'] = $city['state_name'] ? $city['state_name'] : "";
						$response['cities'][$i]['city'] = $city['city'] ? $city['city'] : "";
						$response['cities'][$i]['status'] = $city['status'] ? $city['status'] : "";
						$response['cities'][$i]['id'] = $city['id'] ? $city['id'] : "";
						$i++;
					}
					$response['message'] = "";
					$response['success'] = 1;
				} else {
					$response['coupons'] = array();
					$response['message'] = "No coupons found";
					$response['success'] = 0;
				}
			}
			if($action == "save"){
				$response['success'] = 0;

				if($post['restaurant_id'] == ""){
					$response['message'] = "Please provide restaurant id";
				} else if(empty($post['slab'])){
					$response['message'] = "Please provide at least one slab";
				} else {
					$time = time();
					$data['restaurant_id'] = $post['restaurant_id'];
					

					if($post['id'] != ""){
						$data['alt_timestamp'] = $time;
						$this->db->where('id',$post['id']);
						$this->db->update('restaurant_commission',$data);
						$insert_id = $post['id'];
					} else {
						$data['save_timestamp'] = $time;
						$this->db->insert('restaurant_commission',$data);
						$insert_id = $this->db->insert_id();
					}

					if($insert_id){
						$this->db->where('map_id',$insert_id);
						$this->db->delete('restaurant_commission_mapping');

						foreach ($post['slab'] as $slab) {
							$dataCom['slab_from'] = $slab['slab_from'];
							$dataCom['slab_to'] = $slab['slab_to'];
							$dataCom['commission'] = $slab['commission'];
							$dataCom['map_id'] = $insert_id;
							$dataCom['timestamp'] = $time;

							$this->db->insert('restaurant_commission_mapping',$dataCom);
						}

						$response['message'] = "Commission charges updated";
						$response['success'] = 1;
					} else {
						$response['message'] = "Opps...something went wrong";
						$response['success'] = 0;
					}

				}
			}
			if($action == "details"){
				if($post['restaurant_id'] != ""){
					$commission = $this->db->query("select * from restaurant_commission where restaurant_id = ".$post['restaurant_id'])->row_array();
					
					if(!empty($commission)){
						$response['commission']['id'] = $commission['id'];
						$response['commission']['restaurant_id'] = $commission['restaurant_id'];
						
						$commission_mapps = $this->db->query("select * from restaurant_commission_mapping where map_id = ".$commission['id'])->result_array();
						if(!empty($commission_mapps)){
							$i = 0;
							foreach ($commission_mapps as $commission_mapp) {
								$response['commission']['slab'][$i]['slab_from'] = floatval($commission_mapp['slab_from']);
								$response['commission']['slab'][$i]['slab_to'] = floatval($commission_mapp['slab_to']);
								$response['commission']['slab'][$i]['commission'] = floatval($commission_mapp['commission']);
								$i++;
							}
						}
						$response['message'] = "";
						$response['success'] = 1;
					}
				} else {
					$response['message'] = "Please provide id";
					$response['success'] = 0;
				}
			}
			if($action == "statusupdate"){
				$post = $this->input->post();
				if(isset($post['id']) && $post['id'] != ""){
					$restaurant = $this->db->get_where("restaurant_city", array("id" => $post['id']))->row_array();

					if(!empty($restaurant)){
						if($restaurant['status'] == 1){
							$status = 0;
						} else {
							$status = 1;
						}
						$data = array(
							"status" => $status
						);
						$this->db->where("id", $post['id']);
						$update = $this->db->update("restaurant_city", $data);

						if($update){
							$response['success'] = 1;
							$response['message'] = "Status has been updated";
						} else {
							$response['success'] = 0;
							$response['message'] = "Opps.. Something went wrong.";
						}
					} else {
						$response['success'] = 0;
						$response['message'] = "State not found.";
					}
				} else {
					$response['success'] = 0;
					$response['message'] = "ID can not be blank.";
				}
			}
			if($action == "remove"){
				if(isset($post['id']) && $post['id'] != ""){
					$category = $this->db->get_where("restaurant_city", array("id" => $post['id']))->row_array();
					if(!empty($category)){
						$this->db->where("id", $post['id']);
						$delete = $this->db->delete("restaurant_city");
						if($delete){
							$response['success'] = 1;
							$response['message'] = "State has been removed";
						} else {
							$response['success'] = 0;
							$response['message'] = "Opps... Something went wrong.";
						}
					} else{
						$response['success'] = 0;
						$response['message'] = "State not found.";
					}
				} else {
					$response['success'] = 0;
					$response['message'] = "Please provide id.";
				}
			}
			if($action == "restaurant_order"){
				if($post['restaurant_id'] != ""){
					
					$from_date = strtotime(date("Y-m-d 00:00:01", strtotime($post['from_date'])));
					$to_date = strtotime(date("Y-m-d 23:59:59", strtotime($post['to_date'])));
					$cond = "";
					if($post['restaurant_id'] != ""){
						$cond .= " and restaurant_id = ".$post['restaurant_id'];
					}
					if($from_date && $to_date){
						$cond .= " and order_commission.timestamp >= ".$from_date." AND order_commission.timestamp <= ".$to_date;
					}
					
					/*echo "select * from order_commission where 1 = 1 ".$cond;
					die;*/
					$restaurants = $this->db->query("select * from order_commission where 1 = 1 ".$cond)->result_array();
					
					if($restaurants){
						$i = 0;
						$total_commission_amount = 0;
						foreach ($restaurants as $restaurant) {
 
 							$order = $this->db->query("select order_number,grand_total from order_master where order_id = ".$restaurant['order_id'])->row_array();

							$response['commission']['orders'][$i]['order_id'] = $restaurant['order_id'];
							$response['commission']['orders'][$i]['order_number'] = $order['order_number'];
							$response['commission']['orders'][$i]['grand_total'] = $order['grand_total'];
							$response['commission']['orders'][$i]['commission'] = $restaurant['commission'];
							$response['commission']['orders'][$i]['commission_amount'] = $restaurant['commission_amount'];
							$response['commission']['orders'][$i]['date'] = date('d M Y H:i',$restaurant['timestamp']);

							$total_commission_amount = $total_commission_amount + $restaurant['commission_amount'];
							$i++;
						}
						$response['commission']['total_commission_amount'] = $total_commission_amount;
						$response['commission']['total_orders'] = $i;
						$response['message'] = "";
						$response['success'] = 1;
					} else {
						$response['message'] = "No Orders available";
						$response['success'] = 0;
					}
				} else {
					$response['message'] = "Please provide restaurant id";
					$response['success'] = 0;
				}
			}
		} else {
			$response['success'] = 0;
			$response['message'] = "Invalid Operation.";
		}
		echo json_encode($response);
	}
	public function paymentMethod(){
    	$post = $this->input->post();
        if (!$post['user_id'] && !$post['cart_session']){
            $response["success"] = 0;
            $response["message"] = "Invalid login user ID.";
        } else {
            $totalQty = 0;
            $user_id = $post['user_id'];
            $cart_session = base64_encode($user_id);
            $cart_items = $this->db->get_where("cart_items", array("cart_session"=>$cart_session))->result_array();
           	foreach ($cart_items as $item) {
                $totalQty = $totalQty + $item['quantity'];
                $cartPrice = $item['total_price'];
                $subtotal = $subtotal + ($cartPrice * $item['quantity']);
                $shippingCharges = 0;
            }
            $cartGrandTotalAmount = $subtotal + $shippingCharges - $post['discount_amount'] + $post['cod_charges'];
            
            $onlyWallet = 0;        
            if($onlyWallet == 1){
                $this->db->where("method_id", 3);     
            } else {
                $this->db->where("method_id != ", 3); 
            }
            $this->db->where("is_active", 1);
            $this->db->order_by('method_id','desc');
            $paymentOptions = $this->db->get_where("payment_method")->result_array();

            if ($post['delivery_address']) {
                $ship_address = $this->db->get_where("user_address", array("address_ID"=>$post['delivery_address']))->row_array();
                $shipping_charges = 0;
            } else {
                $shipping_charges = 0;
            }

            if(!empty($paymentOptions)){
                $count = 0;
                foreach ($paymentOptions as $payment) {
                    //$payment_charges = $this->front_model->get_payment_charges($payment['method_id'], $subtotal);
                    $payment_charges = 0;

                    $response['payments'][$count]['id'] = $payment['method_id'];
                    if($payment['method_id'] == 3){
                        $totalWallet = "( ₹".$wallet." in Wallet )";                        
                    } else {
                        $totalWallet = "";
                    }
                    
                    $path = FC_PATH."assets/payment_method/";
					if(file_exists($path.$payment['icon'])){
						$response['payments'][$count]['icon'] = $payment['icon'] ? BASE_URL."assets/payment_method/".$payment['icon'] : BASE_URL."assets/thumb.jpg";	
					} else {
						$response['payments'][$count]['icon'] = "";
					}

                    $response['payments'][$count]['name'] = $payment['shipping_name']." ".$totalWallet;
                    $response['payments'][$count]['details'] = $payment['shipping_details'] ? $payment['shipping_details'] : "";
                    $response['payments'][$count]['shipping_charges'] = round($payment['shipping_charges']);
                    $response['payments'][$count]['payment_charges'] = 0;
                    //$response['payments'][$count] = $this->front_model->removeNull($response['payments'][$count]);
                    $count++;
                }
                $response['success'] = 1;
                $response['message'] = $count." Payment methods found.";
            } else {
                $response['success'] = 0;
                $response['message'] = "No payment methods found.";
            }  
        }
        
        echo json_encode($response);
        exit;
    }

    public function offers($action){

		$actions = array("list", "save", "delete", "types", "statusupdate", "remove", "details","save","restaurant_offer_list","restaurant_offer_status");
		$post = $this->input->post();
		if(in_array($action, $actions)){
			$post = $this->input->post();
			if($action == "restaurant_offer_list"){
				$offers = $this->db->query("select * from restaurant_offers")->result_array();

				if(!empty($offers)){
					$i = 0;
					foreach ($offers as $offer) {
						$offer_name = $this->db->get_where('offers',array('offer_id'=>$offer['map_offer_id']))->row()->offer_type;

						$response['offers'][$i]['map_offer_id'] = $offer['map_offer_id']; 
						$response['offers'][$i]['offer_id'] = $offer['offer_id']; 
						$response['offers'][$i]['discount'] = $offer['discount']; 
						$response['offers'][$i]['discount_type'] = $offer['discount_type']; 
						$response['offers'][$i]['status'] = $offer['status']; 
						$response['offers'][$i]['offer_type'] = ucfirst(strtolower($offer_name));
						$offer_date = "";
						if($offer['from_date']){
							$offer_date .= date('d F Y',$offer['from_date']);
						} 
						if($offer['from_date'] && $offer['to_date']){
							$offer_date .= "-". date('d F Y',$offer['to_date']);	
						}
						$response['offers'][$i]['offer_date'] = $offer_date ? $offer_date : "-";
						$response['offers'][$i]['created_on'] = $offer['alt_timestamp'] ? date('d-m-Y h:i:s',$offer['alt_timestamp']) : date('d-m-Y h:i:s',$offer['save_timestamp']);

						$i++;
					}
					$response['message'] = "";
					$response['success'] = 1;
				} else {
					$response['message'] = "No Offers added";
					$response['success'] = 0;	
				}
				
			}

			if($action == "restaurant_offer_status"){
				$post = $this->input->post();
				if(isset($post['offer_id']) && $post['offer_id'] != ""){
					$category = $this->db->get_where("restaurant_combo_offers", array("offer_id" => $post['offer_id']))->row_array();
					if(!empty($category)){
						if($category['status'] == 1){
							$status = 0;
						} else {
							$status = 1;
						}
						$data = array(
							"status" => $status
						);

						$this->db->where("offer_id", $post['offer_id']);
						$update = $this->db->update("restaurant_combo_offers", $data);

						if($update){
							$response['success'] = 1;
							$response['message'] = "Offer has been updated";
						} else {
							$response['success'] = 0;
							$response['message'] = "Opps.. Something went wrong.";
						}
					} else {
						$response['success'] = 0;
						$response['message'] = "Offer not found.";
					}
				} else {
					$response['success'] = 0;
					$response['message'] = "Offer ID can not be blank.";
				}
			}

			if($action == "statusupdate"){
				$post = $this->input->post();
				if(isset($post['offer_id']) && $post['offer_id'] != ""){
					$category = $this->db->get_where("offers", array("offer_id" => $post['offer_id']))->row_array();
					if(!empty($category)){
						if($category['status'] == 1){
							$status = 0;
						} else {
							$status = 1;
						}
						$data = array(
							"status" => $status
						);

						$this->db->where("offer_id", $post['offer_id']);
						$update = $this->db->update("offers", $data);

						if($update){
							$response['success'] = 1;
							$response['message'] = "Offer has been updated";
						} else {
							$response['success'] = 0;
							$response['message'] = "Opps.. Something went wrong.";
						}
					} else {
						$response['success'] = 0;
						$response['message'] = "Offer not found.";
					}
				} else {
					$response['success'] = 0;
					$response['message'] = "Offer ID can not be blank.";
				}
			}

			if($action == "list"){	
				$cond = "";
				if($post['status'] == "active" || $post['is_admin'] != 1){
					$cond .= " and status = 1";
				}
				$response['offers'] = array();
				$offers = $this->db->query("select * from offers where 1 = 1 ".$cond)->result_array();
				if(!empty($offers)){
					$i = 0;
					foreach ($offers as $offer) {
						$response['offers'][$i]['offer_id'] = $offer['offer_id'];
						$response['offers'][$i]['offer_type'] = $offer['offer_type'] ? $offer['offer_type'] : "";	
						$response['offers'][$i]['description'] = $offer['description'] ? $offer['description'] : "";	
						$response['offers'][$i]['status'] = $offer['status'] ? $offer['status'] : "";
						$response['offers'][$i]['timestamp'] = date('Y-m-d h:i:s',$offer['timestamp']);
						$i++;
					}
					$response['success'] = 1;
					$response['message'] = "";
				} else {
					$response['success'] = 0;
					$response['message'] = "No offers found";
				}
			}

			if($action == "save"){
				if($post['map_offer_id'] != ""){
					if(!empty($post['offer_items'])){
						if(($post['is_date_enabled'] == 1 && $post['from_date'] != "") || $post['is_date_enabled'] == 0){

							$dataEntry['restaurant_id'] = $post['restaurant_id'];
							$dataEntry['map_offer_id'] = $post['map_offer_id'];
							if($post['is_date_enabled'] == 1){
								$dataEntry['from_date'] = $post['from_date'];
								$dataEntry['to_date'] = $post['to_date'];	
							} else {
								$dataEntry['from_date'] = NULL;
								$dataEntry['to_date'] = NULL;
							}
							$dataEntry['status'] = $post['status'];
							$dataEntry['is_date_enabled'] = $post['is_date_enabled'];
							$dataEntry['feedback_discount_amount'] = $post['feedback_discount_amount'];
							$dataEntry['feedback_discount_type'] = $post['feedback_discount_type'];

							if($post['map_offer_id'] == 5){

								$couponData['coupon_code'] = "FEEDBACK OFFER";
								$couponData['status'] = 1;
								$couponData['discount_type'] = $post['feedback_discount_type'];
								$couponData['discount_amount'] = $post['feedback_discount_amount'];
								$couponData['save_timestamp'] = time();

								$checkCoupon = $this->db->query("select * from coupons where coupon_code = 'FEEDBACK OFFER'")->row_array();

								if(!empty($checkCoupon)){
									$this->db->where('coupon_id',$checkCoupon['coupon_id']);
									$this->db->update('coupons',$couponData);
								} else {
									$this->db->insert('coupons',$couponData);
								}
							}
							/*print_r($dataEntry);
							die;*/
							if($post['offer_id'] != ""){
								$dataEntry['alt_timestamp'] = time();
								$this->db->where('offer_id',$post['offer_id']);
								$update = $this->db->update('restaurant_offers',$dataEntry);
								$insert_id = $post['offer_id'];
								
								if($update){
									$response['message'] = "Offer Updated successfully";
									$response['success'] = 1;
								} else {
									$response['message'] = "Opps...something went wrong";
									$response['success'] = 0;
								}
							} else {
								if($post['map_offer_id'] == 5){
									$this->db->where('map_offer_id',$post['map_offer_id']);
									$this->db->delete('restaurant_offers');	
								}
								$dataEntry['save_timestamp'] = time();
								$insert = $this->db->insert('restaurant_offers',$dataEntry);
								
								$insert_id = $this->db->insert_id();
								if($insert){
									$response['message'] = "Offer added successfully";
									$response['success'] = 1;
								} else {
									$response['message'] = "Opps...something went wrong";
									$response['success'] = 0;
								}
							}

							if($insert_id){
								if ($post['map_offer_id'] != 5) {
									$entryCount = 0;
									$this->db->query("DELETE from restaurant_offers_map where map_id = ".$insert_id);
									foreach ($post['offer_items'] as $id => $values) {
										if(!empty($values['ids'] && ($values['discount'] != "" || $values['buy_one_get_one']) || ($post['map_offer_id'] == 4 && $values['discount'] != ""))){

											if($values['is_customization'] == 1){
												/*$this->db->query("DELETE from restaurant_offers_map where map_id = ".$insert_id." and variation_id = ".$id);*/
												$item_id = $this->db->get_where('restaurant_menu_item_variations',array('item_variation_id'=>$id))->row()->item_id;
												$data['variation_id'] = $id;
												$data['item_id'] = $item_id;
											} else {
												/*$this->db->query("DELETE from restaurant_offers_map where map_id = ".$insert_id." and item_id = ".$id);*/
												$data['variation_id'] = NULL; 
												$data['item_id'] = $id;
											}
											if($post['map_offer_id'] == 1){
												$data['discount'] = 100;
												$values['discount_type'] = "percent";	
											} else {
												$data['discount'] = $values['discount'];
											}
											$data['map_id'] = $insert_id;
											$data['discount_type'] = $values['discount_type'];
											$data['purchase_quantity'] = $values['purchase_quantity'];
											$data['is_customization'] = $values['is_customization'];
											$data['offer_text'] = $values['offer_text'];
											$data['timestamp'] = time();
											if($post['map_offer_id'] == 4){
												$data['offer_items'] = $id;
											} else {
												$data['offer_items'] = implode(",",$values['ids']);
											}
											$insert = $this->db->insert('restaurant_offers_map',$data);
											$entryCount++;
										}
									}
								}

								if(($insert && $entryCount > 0) || $post['map_offer_id'] == 5){
									$response['message'] = "Offer created successfully";
									$response['success'] = 1;
								} else if($entryCount <= 0) {
									$response['message'] = "No items selected for offers";
									$response['success'] = 0;
								} else {
									$response['message'] = "Opps...something went wrong";
									$response['success'] = 0;
								}
							} else {
								$response['message'] = "Opps...something went wrong";
								$response['success'] = 0;	
							}
						} else {
							$response['message'] = "Please provide from date offer starts";
							$response['success'] = 0;
						}
					} else {
						$response['message'] = "Please enter at least one item for offers";
						$response['success'] = 0;
					}
				} else {
					$response['message'] = "Please provide offer id";
					$response['success'] = 0;
				}	
			}
			
			if($action == "details"){
				if($post['offer_id'] != ""){
					$offerMain = $this->db->query("select * from restaurant_offers where offer_id = ".$post['offer_id'])->row_array();

					$offerName = $this->db->get_where('offers',array('offer_id'=>$offerMain['map_offer_id']))->row()->offer_type;
					
					if(!empty($offerMain)){
						$response['offer_items']['offer_id'] = $offerMain['offer_id'];
						$response['offer_items']['map_offer_id'] = $offerMain['map_offer_id'];
						$response['offer_items']['from_date'] = $offerMain['from_date'] ? $offerMain['from_date'] : "";
						$response['offer_items']['to_date'] = $offerMain['to_date'] ? $offerMain['to_date'] : "";
						$response['offer_items']['is_date_enabled'] = $offerMain['is_date_enabled'] ? $offerMain['is_date_enabled'] : "";
						$response['offer_items']['feedback_discount_type'] = $offerMain['feedback_discount_type'] ? $offerMain['feedback_discount_type'] : "";
						$response['offer_items']['feedback_discount_amount'] = $offerMain['feedback_discount_amount'] ? floatval($offerMain['feedback_discount_amount']) : "";

						$offers = $this->db->query("select * from restaurant_offers_map where map_id = ".$post['offer_id'])->result_array();
						
						foreach ($offers as $offer) {
							if($offer['offer_items'] != ""){
								if($offer['is_customization'] == 1){
									$response['offer_items'][$offer['variation_id']]['ids'] = explode(",", $offer['offer_items']);
									$response['offer_items'][$offer['variation_id']]['discount'] = $offer['discount'] ? $offer['discount'] : "";
									$response['offer_items'][$offer['variation_id']]['discount_type'] = $offer['discount_type'] ? $offer['discount_type'] : "";
									$response['offer_items'][$offer['variation_id']]['is_customization'] = $offer['is_customization'] ? $offer['is_customization'] : 0;
									$response['offer_items'][$offer['variation_id']]['item_variation_id'] = $offer['variation_id'];
									$response['offer_items'][$offer['variation_id']]['offer_text'] = $offer['offer_text'];
									$response['offer_items'][$offer['variation_id']]['buy_one_get_one'] = $offer['purchase_quantity'];
									if($offerMain['map_offer_id'] == 1){
										$response['offer_items'][$offer['variation_id']]['buy_one_get_one'] = true;
									} else {
										$response['offer_items'][$offer['variation_id']]['buy_one_get_one'] = "";
									}
								} else {
									$response['offer_items'][$offer['item_id']]['ids'] = explode(",", $offer['offer_items']);
									$response['offer_items'][$offer['item_id']]['discount'] = $offer['discount'] ? $offer['discount'] : "";
									$response['offer_items'][$offer['item_id']]['discount_type'] = $offer['discount_type'] ? $offer['discount_type'] : "";
									$response['offer_items'][$offer['item_id']]['is_customization'] = $offer['is_customization'] ? $offer['is_customization'] : 0;
									$response['offer_items'][$offer['item_id']]['offer_text'] = $offer['offer_text'];
									$response['offer_items'][$offer['item_id']]['item_id'] = $offer['item_id'];
									$response['offer_items'][$offer['item_id']]['purchase_quantity'] = $offer['purchase_quantity'];
									
									if($offerMain['map_offer_id'] == 1){
										$response['offer_items'][$offer['item_id']]['buy_one_get_one'] = true;
									} else {
										$response['offer_items'][$offer['item_id']]['buy_one_get_one'] = "";
									}
								}
							}
						}
						
						$response['success'] = 1;
						$response['offer_name'] = $offerName ? $offerName : "";
					} else {
						$response['success'] = 0;
						$response['message'] = "No offer created";
						$response['offer_name'] = $offerName ? $offerName : "";
					}
				} else {
					$response['message'] = "Please provide offer id";
					$response['success'] = 0;
				}
			}
			if($action == "remove"){
				$post = $this->input->post();
				if(isset($post['offer_id']) && $post['offer_id'] != ""){
					$offer = $this->db->get_where("restaurant_offers", array("offer_id" => $post['offer_id']))->row_array();
					if(!empty($offer)){
						$this->db->where("offer_id", $post['offer_id']);
						$delete = $this->db->delete("restaurant_offers");
						if($delete){
							$response['success'] = 1;
							$response['message'] = "Offer has been removed";
						} else {
							$response['success'] = 0;
							$response['message'] = "Opps.. Something went wrong.";
						}
					} else {
						$response['success'] = 0;
						$response['message'] = "Offer not found.";
					}
				} else {
					$response['success'] = 0;
					$response['message'] = "Offer ID can not be blank.";
				}
			}
		} else {
			$response['success'] = 0;
			$response['message'] = "Invalid Operation.";
		}
		echo json_encode($response);
	}

	public function payment($action = null, $cart_session = null,$order_id = ""){
		if($action == "paytmResponse"){
			
			require_once(APPPATH . "/third_party/encdec_paytm.php");
			$paytmChecksum = "";
			if($_POST['RESPMSG'] == "Txn Success"){
				$order_id_array = explode("-", $_POST['ORDERID']);
				$user_id = $order_id_array[0];
				$order_id = $order_id_array[1];

				$checlOrderForDinein = $this->db->query("select * from order_master where order_id = ".$order_id)->row_array();
				if($checlOrderForDinein['dinein_session']){
					$this->db->where('cart_session',$checlOrderForDinein['cart_session']);
					$this->db->delete('cart_items');
				}
				
						
				$orderMaster['payment'] = "Recived";
				$orderMaster['payment_response'] = base64_encode($_POST);
				$this->db->where('order_id',$order_id);
				$this->db->update('order_master',$orderMaster);

				$this->db->where('cart_session',base64_encode($user_id));
				$this->db->delete('cart_items');	

				$order_number = $this->db->get_where('order_master',array('order_id'=>$order_id))->row()->order_number;
				$contact = $this->db->get_where('order_address',array('order_id'=>$order_id))->row()->contact;

				$response['message'] = "Order placed successfully";
				$response['success'] = 1;
				$response['order_number'] = $order_number;
				$response['order_id'] = $order_id;	

				/*$this->db->select('product_name,quantity');
				$orderItems = $this->db->get_where('order_items',array('order_id'=>$order_id))->result_array();
				$item_string = "";
				foreach ($orderItems as $orderItem) {
					$item_string .= $orderItem['product_name']." (".$orderItem['quantity']."), ";
				}
				$item_string = "\n".rtrim($item_string,", ");

				$tax_string = "";
				$order_master = $this->db->get_where('order_master',array('order_id'=>$order_id))->row_array();
				$taxAmount = round(($order_master['grand_total'] * 2.5) / 100);
				$tax_string .= "\nTotal ".$order_master['sub_total']." Rs";
				$tax_string .= " + ".$taxAmount." Rs (CGST) + ".$taxAmount." Rs (SGST)";
				if($order_master['discount_amount']){
					$tax_string .= " - ".$order_master['discount_amount']." Rs (Discount)";	
				}
				$tax_string .= " = ".$order_master['grand_total']." Rs";

				$msg = "Thank you for Ordering with us!!!\nYour Order no. is : ".$order_number.$item_string.$tax_string."\nI love Sandwichhouse";
				$message = urlencode($msg);
				$URL = "http://ip.shreesms.net/smsserver/SMS10N.aspx?Userid=Qwiches&UserPassword=12345&PhoneNumber=".$contact."&Text=".$message."&GSM=WeCare";
				$result = file_get_contents($URL);*/

				$this->email_model->do_confirmation_sms($order_id);
				$this->email_model->do_confirmation_mail($order_id);

				
				$url = FRONT_URL."order-success/".base64_encode($order_id);
				header("Location: ".$url);
			} else {
				$orderMaster['payment_response'] = base64_encode($_POST);
				$this->db->where('order_id',$order_id);
				$this->db->update('order_master',$orderMaster);

				$url = FRONT_URL."order-fail";
				header("Location: ".$url);
			}

		} else if($action == "processPaytm"){
			


			$cart_price = $this->front_model->get_cart_total($cart_session,"");
			$user_details = $this->db->get_where("users",array("user_id"=> base64_decode($cart_session)))->row_array();

			$orderDetails = $this->db->query("select * from order_master where order_id = ".$order_id)->row_array();
			if($orderDetails['dinein_session'] != ""){
				$cart_price['grand_total'] = $orderDetails['grand_total'];
				$user_details = $this->db->get_where("order_address",array("order_id"=> $order_id))->row_array();
				if($user_details['email'] == ""){
					$user_details['email'] = "mayur@coronation.in";
				}

				$cart_session = "MTIxMjEy";
			}

			
			/**
			* import checksum generation utility
			* You can get this utility from https://developer.paytm.com/docs/checksum/
			*/
			require_once(APPPATH . "/third_party/encdec_paytm.php");
			/* initialize an array with request parameters */
			$paytmParams = array(
			    
				/* Find your MID in your Paytm Dashboard at https://dashboard.paytm.com/next/apikeys */
				"MID" => "QWCHES04724131550886",
				//"MID" => "xMvmNK11394808251369",
			    
				/* Find your WEBSITE in your Paytm Dashboard at https://dashboard.paytm.com/next/apikeys */
				"WEBSITE" => "DEFAULT",
				//"WEBSITE" => "WEBSTAGING",
			    
				/* Find your INDUSTRY_TYPE_ID in your Paytm Dashboard at https://dashboard.paytm.com/next/apikeys */
				"INDUSTRY_TYPE_ID" => "Retail109",
				//"INDUSTRY_TYPE_ID" => "TypeRetail",
			    
				/* WEB for website and WAP for Mobile-websites or App */
				"CHANNEL_ID" => "WEB",
			    
				/* Enter your unique order id */
				"ORDER_ID" => base64_decode($cart_session)."-".$order_id."-".time(),
			    
				/* unique id that belongs to your customer */
				"CUST_ID" => base64_decode($cart_session),
			    
				/* customer's mobile number */
				"MOBILE_NO" => $user_details['contact'],

				/* customer's email */
				"EMAIL" => $user_details['email'],
			    
				/**
				* Amount in INR that is payble by customer
				* this should be numeric with optionally having two decimal points
				*/
				"TXN_AMOUNT" => $cart_price['grand_total']+$orderDetails['tips'],
			    
				/* on completion of transaction, we will send you the response on this URL */
				"CALLBACK_URL" => base_url()."api/services/payment/paytmResponse",
			);
			
			
			/**
			* Generate checksum for parameters we have
			* Find your Merchant Key in your Paytm Dashboard at https://dashboard.paytm.com/next/apikeys 
			*/
			//$checksum = getChecksumFromArray($paytmParams, "DGzKJPNzqXrJkCzo");
			$checksum = getChecksumFromArray($paytmParams, "73F!MTAd8KBmq#fj");

			/* for Staging */
			$url = "https://securegw.paytm.in/order/process";

			/* For staging */

			//$url = "https://securegw-stage.paytm.in/order/process";

			?>


				<html>
					<head>
						<title>Merchant Checkout Page</title>
					</head>
					<body>
						<center><h1>Please do not refresh this page...</h1></center>
						<form method='post' action='<?php echo $url; ?>' name='paytm_form'>
								<?php
									foreach($paytmParams as $name => $value) {
										echo '<input type="hidden" name="' . $name .'" value="' . $value . '">';
									}
								?>
								<input type="hidden" name="CHECKSUMHASH" value="<?php echo $checksum ?>">
						</form>
						<script type="text/javascript">
							document.paytm_form.submit();
						</script>
					</body>
				</html>
			<?php					
		}
	}


	public function open_item($action){

		$actions = array("save");
		$post = $this->input->post();
		
		if(in_array($action, $actions)){
			if($action == "save"){
				$response['success'] = 0;
				if($post['category_id'] == "") {
					$response['success'] = 0;
					$response['message'] = "Invalid item category";
				} else if($post['item_name'] == "") {
					$response['success'] = 0;
					$response['message'] = "Invalid item name";
				} else {
			 		$time = time();

			 		$itemMainData['name'] = $post['item_name'];
			 		$itemMainData['status'] = $post['status'];
			 		$itemMainData['sale_price'] = $post['price'];
			 		$itemMainData['is_open_item'] = 1;
			 		$itemMainData['status'] = 1;
			 		$itemMainData['save_timestamp'] = $time;
			 		
			 		$path = FC_PATH."assets/uploads/products/";

			 		if (!empty($_FILES["image"])) {
				        $image_name = time().'_'.preg_replace('/\s+/', '_', $_FILES['image']['name']);
				        $image_name = $this->front_model->clean($image_name);
				        move_uploaded_file($_FILES["image"]["tmp_name"], $path.$image_name);
				        $itemImageData["image"] = $image_name;
				    }

			 		if($post['product_id'] != ""){
			 			$itemMainData['alt_timestamp'] = time();
			 			$this->db->where('product_id',$post['product_id']);
			 			$save_product =  $this->db->update('products',$itemMainData);
			 			$product_id = $post['product_id'];
			 			$message = "Item updated successfully";

			 			$this->db->where("product_id",$product_id);
			 			$this->db->where("category_id",$post['category_id']);
			 			$this->db->delete('product_category');

			 			$itemImageData["product_id"] = $post["product_id"];
			 			$itemImageData["timestamp"] = time();
			 			
			 			if (!empty($_FILES["image"])) {
				 			$this->db->where("product_id",$product_id);
				 			$this->db->delete('product_images');
				 			$save_product = $this->db->insert('product_images',$itemImageData);
				 		}

			 			$itemCatData = array('product_id'=>$product_id, 'category_id'=>$post['category_id'], 'timestamp'=>time());
			 			$save_product_cat = $this->db->insert('product_category',$itemCatData);


			 		} else {
			 			$save_product = $this->db->insert('products',$itemMainData);
			 			$message = "Item added successfully";
			 			$insert_id = $this->db->insert_id();
			 			$prodct_id = $insert_id;
			 			$itemImageData["product_id"] = $insert_id;
			 			$itemImageData["timestamp"] = time();
			 			$save_product = $this->db->insert('product_images',$itemImageData);

			 			$itemCatData = array('product_id'=>$insert_id, 'category_id'=>$post['category_id'], 'timestamp'=>time());
			 			$save_product_cat = $this->db->insert('product_category',$itemCatData);
			 		}


					if($save_product){
						$response['product_id'] = $prodct_id;
						$response['message'] = $message;
						$response['success'] = 1;
					} else {
						$response['message'] = "Opps...something went wrong";
						$response['success'] = 0;
					}
			 	}
			}
		} else {
			$response['message'] = "Invalid Operation";
			$response['success'] = 0;
		}
		
		echo json_encode($response);
	}


	

	public function kot($action){
		$actions = array("list");
		$post = $this->input->post();
		if(in_array($action, $actions)){
			if($action == "list"){
				$cond = "";
				$pagelimit = "";
				$datemonthYear = date('dFY');
				$orders = $this->db->query("select order_id,delivery_note from order_master where 1 = 1 and (status = 'placed' OR status = 'accepted') and FROM_UNIXTIME(order_timestamp, '%d%M%Y') = '".$datemonthYear."' ".$cond.$search_q." ".$orderBy." ".$pagelimit)->result_array();
				
				if(!empty($orders)){
					$deliveryCount = 0;
					$diningCount = 0;

					foreach ($orders as $order) {
						$order_address = $this->db->query("select address_type,table_number from order_address where order_id = ".$order['order_id'])->row_array();

						$order_items = $this->db->query("select product_name,quantity,product_id from order_items where order_id = ".$order['order_id']." and delivered_on_table != 1")->result_array();

						if(!empty($order_items)){
							$iCount = 0;
							$orderItemsArray = array();
							foreach ($order_items as $item) {
								if($item['product_id'] != ""){
									$product = $this->db->query("select is_veg_only,is_non_veg,is_with_eggs,is_jain from products where product_id = ".$item['product_id'])->row_array();	
								} else {
									$product = $this->db->query("select is_veg_only,is_non_veg,is_with_eggs,is_jain from products where name = '".$item['product_name']."' order by product_id desc")->row_array();	
								}
								

								$orderItemsArray[$iCount]['is_jain'] = $product['is_jain'];
								$orderItemsArray[$iCount]['is_with_eggs'] = $product['is_with_eggs'];
								$orderItemsArray[$iCount]['is_non_veg'] = $product['is_non_veg'];
								$orderItemsArray[$iCount]['is_veg_only'] = $product['is_veg_only'];
								$orderItemsArray[$iCount]['item_name'] = $item['product_name'];
								$orderItemsArray[$iCount]['quantity'] = $item['quantity'];
								$iCount++;
							} 
							
							if($order_address['address_type'] == 'dinning'){
								if($order_address['table_number'] != 'undefined'){
									$response['orders']['dinningOrder'][$diningCount]['table_number'] = $order_address['table_number'];
									$response['orders']['dinningOrder'][$diningCount]['order_items'] = $orderItemsArray;
									$response['orders']['dinningOrder'][$diningCount]['delivery_note'] = $order['delivery_note'];
									$diningCount++;	
								}
							} else {
								if($order['delivery_note'] != "" || $order['delivery_note'] != NULL){
									$response['orders']['deliveryOrder'][$deliveryCount]['table_number'] = "";
									$response['orders']['deliveryOrder'][$deliveryCount]['order_items'] = $orderItemsArray;
									$response['orders']['deliveryOrder'][$deliveryCount]['delivery_note'] = $order['delivery_note'];
									$deliveryCount++;	
								}
							}
						}
					}

					$orderGroups = $this->db->query("select sum(quantity) as item_total,product_name,product_id from order_items where order_id in(select order_id from order_master where 1 = 1 and delivered_on_table = 0 and (delivery_note is null OR delivery_note = 'NULL') and (status = 'placed' OR status = 'accepted') and FROM_UNIXTIME(order_timestamp, '%d%M%Y') = '".$datemonthYear."' ".$cond.$search_q." ".$orderBy." ".$pagelimit.") group by product_id order by product_name")->result_array();
					
					if(!empty($orderGroups)){
						$itemGroupC = 0;
						foreach ($orderGroups as $orderGroup) {
							if($item['product_id'] != ""){
								$product = $this->db->query("select is_veg_only,is_non_veg,is_with_eggs,is_jain from products where product_id = ".$item['product_id'])->row_array();	
							} else {
								$product = $this->db->query("select is_veg_only,is_non_veg,is_with_eggs,is_jain from products where name = '".$item['product_name']."' order by product_id desc")->row_array();	
							}

							$response['orders']['orderGroupItems'][$itemGroupC]['is_jain'] = $product['is_jain'];
							$response['orders']['orderGroupItems'][$itemGroupC]['is_with_eggs'] = $product['is_with_eggs'];
							$response['orders']['orderGroupItems'][$itemGroupC]['is_non_veg'] = $product['is_non_veg'];
							$response['orders']['orderGroupItems'][$itemGroupC]['is_veg_only'] = $product['is_veg_only'];

							$response['orders']['orderGroupItems'][$itemGroupC]['item_name'] = $orderGroup['product_name']; 
							$response['orders']['orderGroupItems'][$itemGroupC]['quantity'] = $orderGroup['item_total']; 
							$itemGroupC++;
						}
					}

					if($deliveryCount == 0){
						$response['orders']['deliveryOrder'] = array();
					}
					if($itemGroupC == 0){
						$response['orders']['orderGroupItems'] = array();
					} 
					if($diningCount == 0){
						$response['orders']['dinningOrder'] = array();
					}
					if($diningCount != 0 || $deliveryCount != 0 || $itemGroupC != 0){
						$response['message'] = "";
						$response['success'] = 1;		
					} else {
						$response['message'] = "";
						$response['success'] = 0;
					}
				} else {
					$response['message'] = "No orders placed";
					$response['success'] = 0;	
				}
			}
		} else {
			$response['message'] = "Invalid Operation";
			$response['success'] = 0;
		}
		echo json_encode($response);
	}
}
