<?php 
define('GSS_VERSION', "0.0.5");
class ControllerFeedSheetsync extends Controller {
	private $error = array(); 
        
        public function feeds(){
            if(!isset($this->request->get['action'])){
                die(json_encode(array('error'=>'Error 901: Sorry, Unable to serve request!')));
            }
            $action = $this->request->get['action'];
            $this->load->model('feed/sheetsync');
            switch($action){
                case 'oauth2callback':
                        set_include_path(DIR_SYSTEM . 'library' );
                        require_once 'Google/Client.php';
                        require_once 'Google/Service/Urlshortener.php';
                        $client = new Google_Client();
                        
                        $this->load->model('setting/setting');
                        $settings = $this->model_setting_setting->getSetting('sheetsync');
                        $client->setClientId($settings['client_id']);
                        $client->setClientSecret($settings['client_secret']);
                        $client->setRedirectUri($settings['redirect_uri']);
                        $client->authenticate($_GET['code']);
                        $token = $client->getAccessToken();
                        $settings['accesstoken'] = base64_encode($token);
                        $etoken = json_decode($token);
                       
                        if(isset($etoken->refresh_token))
                           $settings['refresh_token'] = $etoken->refresh_token;
                        $settings['google_spreadsheet_auth'] = 1;
                        $this->model_setting_setting->editSetting('sheetsync',$settings,$this->config->get('store_id'));
                        echo "<html><head><title>Syncsheet Account verification</title></head><body>";
                        echo "<center><h1>SyncSheet</h1><h3 style=color:green>A new access token has been generated successfully!</h3><h4><a href='javascript:window.close()'>Go Back</a></h4></center>";
                        echo "<script>
                           window.onunload = refreshParent;
                           function refreshParent() {
                               window.opener.location.reload();
                           }
                           </script>";
                        echo "</body></html>";
                    break;
                case 'setting':
                    $this->setting();
                    break;
                case 'getLanguageMeta':
                    $languages = $this->model_feed_sheetsync->getLanguagesByCode();
                    $default =  $this->config->get('config_language');
                    die(json_encode(array('languages'=>$languages,'default'=>$default)));
                    break;
                case 'getHeaders':
                    $headers = $this->model_feed_sheetsync->buildJsonHeader($this->request->post['settings']);
                    die(json_encode($headers));
                    break;
               case 'getcount':
                       $count = $this->model_feed_sheetsync->getProductsCount(array());
                       die(json_encode(array('count'=>$count)));
                    break;
               case 'oc2gss':
                   $headers = $this->request->post['headers'];
                      
                       $count = $this->request->post['count'];
                       $limit = (isset($this->request->post['limit']))?$this->request->post['limit']:500;
                       $callback = (isset($this->request->post['callback']))?$this->request->post['callback']:1;
                       
                       if($count > 0) { $total_callbacks = ceil($count/$limit); } else { $total_callbacks = 0; }
                       $start = $limit * $callback - $limit;
                       $this->export($headers,$start,$limit);
                    break;
                case 'sync':
                       $rows = json_decode(base64_decode($this->request->post['rows']));
                       $instance = $this->model_feed_sheetsync
                                        ->setSetting($this->request->post['settings'])
                                        ->setProductFeed($rows)
                                        ->extractHeader()
                                        ->applyFilters()
                                        ->prepareSet()
                                        ->set();
                    break; 
                case 'columnpull':
                           $headers = json_decode(base64_decode($this->request->post['headers']));
                           $products = base64_decode($this->request->post['products']);
                           $this->load->model('catalog/product');
                           $this->load->model('catalog/category');
                           $instance = $this->model_feed_sheetsync
                            ->setHeaders($headers)
                            ->applyFilters();
                           
                           if($products){
                               foreach(explode(',', $products) as $product_id){
                                   $json[$product_id] = $instance->setId($product_id)
                                            ->getLanguages()
                                             ->prepareGet()
                                             ->get();
                               }
                           }
                            die(json_encode($json));
                    break;
                case 'columnupdate':
                    
                    $rows = json_decode(base64_decode($this->request->post['rows']));
                    $instance = $this->model_feed_sheetsync
                            ->setAction('update')
                            ->setSetting($this->request->post['settings'])
                            ->setProductFeed($rows)
                            ->extractHeader()
                            ->applyFilters()
                            ->prepareSet()
                            ->set();
                    break;
            }
        }
        
        public function export($headers,$start,$limit){
            $this->load->model('catalog/product');
            $this->load->model('catalog/category');
            //alter database shopbeautyfor  default character set utf8 COLLATE utf8_unicode_ci;
            
            $instance = $this->model_feed_sheetsync
                            ->setAction('export')
                            ->setHeaders($headers)
                            ->applyFilters();
            $products = $this->model_feed_sheetsync->fetchProducts($start,$limit);
            $json = array();
            foreach($products as $product){
               $_product = $instance->setId($product['product_id'])
                         ->getLanguages()
                         ->prepareGet()
                         ->get();
               $json[] = $_product;
            }
            die(json_encode($json));
        }

        public function install(){
            $this->load->model('feed/sheetsync');
            $this->model_feed_sheetsync->install();
        }
        
       
        
        public function account() {
            	$this->language->load('feed/sheetsync');

		$this->document->setTitle($this->language->get('heading_title'));
		
		$this->load->model('setting/setting');
			
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()){
                    $this->load->model('feed/sheetsync');
                    $post = $this->request->post;
                    $this->model_setting_setting->editSetting('sheetsync',$post);
                    
                    $this->session->data['success'] = $this->language->get('text_success');
                    $this->redirect($this->url->link('feed/sheetsync', 'token=' . $this->session->data['token'], 'SSL'));
     		}
                
                if (isset($this->session->data['success'])) {
			$this->data['success'] = $this->session->data['success'];
		
			unset($this->session->data['success']);
		} else {
			$this->data['success'] = '';
		}
                
                if (isset($this->session->data['error_warning'])) {
			$this->data['error_warning'] = $this->session->data['error_warning'];
		
			unset($this->session->data['error_warning']);
		} else {
			$this->data['error_warning'] = '';
		}

		$this->data['heading_title'] = $this->language->get('heading_title');
		
		$this->data['text_enabled'] = $this->language->get('text_enabled');
		$this->data['text_disabled'] = $this->language->get('text_disabled');
		
		$this->data['entry_status'] = $this->language->get('entry_status');
                $this->data['entry_user'] = $this->language->get('entry_user');
                $this->data['entry_pass'] = $this->language->get('entry_pass');
                $this->data['entry_ssKey'] = $this->language->get('entry_ssKey');
                $this->data['entry_wsKey'] = $this->language->get('entry_wsKey');
		$this->data['entry_data_feed'] = $this->language->get('entry_data_feed');
		
		$this->data['button_save'] = $this->language->get('button_save');
		$this->data['button_cancel'] = $this->language->get('button_cancel');

		$this->data['tab_general'] = $this->language->get('tab_general');

 		
		
  		$this->data['breadcrumbs'] = array();

   		$this->data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
      		'separator' => false
   		);

   		$this->data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('text_feed'),
			'href'      => $this->url->link('extension/feed', 'token=' . $this->session->data['token'], 'SSL'),
      		'separator' => ' :: '
   		);

   		$this->data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('heading_title'),
			'href'      => $this->url->link('feed/sheetsync', 'token=' . $this->session->data['token'], 'SSL'),
      		'separator' => ' :: '
   		);
				
		$this->data['action'] = $this->url->link('feed/sheetsync/account', 'token=' . $this->session->data['token'], 'SSL');
		
		$this->data['cancel'] = $this->url->link('extension/feed', 'token=' . $this->session->data['token'], 'SSL');
		
                $this->data['gs'] = $this->model_setting_setting->getSetting('sheetsync');

                if(!isset($this->data['gs']['google_spreadsheet_status'])){
                    $this->data['gs']['google_spreadsheet_status'] = 0;
                }
                
                if(!isset($this->data['gs']['google_spreadsheet_user'])){
                    $this->data['gs']['google_spreadsheet_user'] = '';
                }
                
                if(!isset($this->data['gs']['google_spreadsheet_pass'])){
                    $this->data['gs']['google_spreadsheet_pass'] = '';
                }
                
                if(!isset($this->data['gs']['google_spreadsheet_sskey'])){
                    $this->data['gs']['google_spreadsheet_sskey'] = '';
                }
                
                if(!isset($this->data['gs']['google_spreadsheet_wskey'])){
                    $this->data['gs']['google_spreadsheet_wskey'] = '';
                }
                if(!isset($this->data['gs']['client_id'])){
                    $this->data['gs']['client_id'] = '';
                }
                if(!isset($this->data['gs']['client_secret'])){
                    $this->data['gs']['client_secret'] = '';
                }
                if(!isset($this->data['gs']['ss_key'])){
                    $this->data['gs']['ss_key'] = '';
                }
               
		$this->data['data_feed'] = HTTP_CATALOG . 'index.php?route=feed/sheetsync';

		$this->template = 'feed/sheetsync_account.tpl';
		$this->children = array(
			'common/header',
			'common/footer'
		);
				
		$this->response->setOutput($this->render());
	} 
        
        public function index(){
            $this->load->model('feed/sheetsync');
            
            $cats = $this->model_feed_sheetsync->getPaths();
            
            $this->data['category'] = $cats;
            
            $response = $this->model_feed_sheetsync->call('version_check');
            $this->data['version_notice'] = '';
            if(version_compare($response->Version->version_no, GSS_VERSION) === 1){
                 $this->data['version_notice'] = "A new version is available now - {".$response->Version->title." - v." . $response->Version->version_no . '}';
            }
            
            if(!$this->config->get('google_spreadsheet_auth')){
                $this->data['error_warning'] = "Please use your google credentials to access Google Spreadsheet. <br/><a href='".$this->url->link('feed/sheetsync/account', 'token=' . $this->session->data['token'], 'SSL')."'>Click Here</a> to setup Now";
            }else{
                $this->data['error_warning'] = '';
            }
         
           unset($this->session->data['uid']);
            if(!isset($this->session->data['uid'])){
                $this->session->data['uid'] = uniqid(rand());
            }

            $this->data['uid'] = $this->session->data['uid'];
            
            if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
                if(isset($this->request->post['selected'])){
                    $ids = implode(',', $this->request->post['selected']);
                    $this->model_feed_sheetsync->deleteSelected($ids);
                    $this->session->data['success'] = $this->language->get('text_success');
                    $this->redirect($this->url->link('feed/sheetsync', 'token=' . $this->session->data['token'], 'SSL'));
                }
            }
            
            $this->data['sheets'] = $this->model_feed_sheetsync->fetchSpreadSheets();
            
            $this->language->load('feed/sheetsync');

            $this->document->setTitle($this->language->get('heading_title'));

            $this->data['heading_title'] = $this->language->get('heading_title');

            $this->data['text_enabled'] = $this->language->get('text_enabled');
            $this->data['text_disabled'] = $this->language->get('text_disabled');

            $this->data['entry_status'] = $this->language->get('entry_status');
            

            $this->data['button_save'] = $this->language->get('button_save');
            $this->data['button_cancel'] = $this->language->get('button_cancel');

            $this->data['tab_general'] = $this->language->get('tab_general');

            $this->data['breadcrumbs'] = array();

            $this->data['breadcrumbs'][] = array(
            'text'      => $this->language->get('text_home'),
                    'href'      => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
            'separator' => false
            );

            $this->data['breadcrumbs'][] = array(
            'text'      => $this->language->get('text_feed'),
                    'href'      => $this->url->link('extension/feed', 'token=' . $this->session->data['token'], 'SSL'),
            'separator' => ' :: '
            );

            $this->data['breadcrumbs'][] = array(
            'text'      => $this->language->get('heading_title'),
            'href'      => $this->url->link('feed/sheetsync', 'token=' . $this->session->data['token'], 'SSL'),
            'separator' => ' :: '
            );

            $this->data['action'] = $this->url->link('feed/sheetsync', 'token=' . $this->session->data['token'], 'SSL');
            $this->data['cancel'] = $this->url->link('extension/feed', 'token=' . $this->session->data['token'], 'SSL');

            $this->template = 'feed/sheetsync_settings.tpl';
            $this->children = array(
                    'common/header',
                    'common/footer'
            );

            $this->response->setOutput($this->render());
                
            
        }
        
        public function setting(){
            $this->load->model('feed/sheetsync');
//            print_r($this->request->post); exit;
            $this->data['edit'] = false;
            if(isset($this->request->post['id'])){
                $settings = $this->request->post;
                
//                print_r(unserialize(base64_decode($settings['settings']))); exit;
                $this->data['edit'] = true;
                $this->data['id'] = $settings['id'];
                $this->data['title'] = $settings['title'];
                $this->data['settings'] = unserialize(base64_decode($settings['settings']));
            }

            $this->load->model('tool/image');
            $this->document->addScript('view/javascript/jquery/jquery.deserialize.js');
            $this->data['product_fields'] = $this->model_feed_sheetsync->getFieldSets('product');
            
            $this->load->model('sale/customer_group');
            $this->data['customer_groups'] = $this->model_feed_sheetsync->getCustomerGroup();
            $this->data['max_discount'] = $this->model_feed_sheetsync->getMaxDiscount();    
            $this->data['max_special'] = $this->model_feed_sheetsync->getMaxSpecial();    
//            echo $this->data['max_discount']; exit;
            $this->load->model('catalog/attribute');
            $this->data['attributes'] = $this->model_catalog_attribute->getAttributes();
            $this->load->model('catalog/option');
            $this->data['options'] = $this->model_catalog_option->getOptions();
            foreach($this->data['options'] as $kv=>$option){
                if ($option['type'] == 'select' || $option['type'] == 'radio' || $option['type'] == 'checkbox' || $option['type'] == 'image') {
                    $option_values = $this->model_catalog_option->getOptionValues($option['option_id']);
                    $option_value_data = array();
                    foreach ($option_values as $option_value) {
                            if ($option_value['image'] && file_exists(DIR_IMAGE . $option_value['image'])) {
                                    $image = $this->model_tool_image->resize($option_value['image'], 50, 50);
                            } else {
                                    $image = '';
                            }
                            $option_value_data[] = array(
                                    'option_value_id' => $option_value['option_value_id'],
                                    'name'            => html_entity_decode($option_value['name'], ENT_QUOTES, 'UTF-8'),
                                    'image'           => $image					
                            );
                    }

                    
                    $this->data['options'][$kv]['values'] = $option_value_data;
                }
            }
            
            $this->language->load('feed/sheetsync');

		$this->document->setTitle($this->language->get('heading_title'));
		
		$this->load->model('setting/setting');
			
		$this->data['heading_title'] = $this->language->get('heading_title');
		
		$this->data['text_enabled'] = $this->language->get('text_enabled');
		$this->data['text_disabled'] = $this->language->get('text_disabled');
		
		$this->data['entry_status'] = $this->language->get('entry_status');
                $this->data['entry_user'] = $this->language->get('entry_user');
                $this->data['entry_pass'] = $this->language->get('entry_pass');
                $this->data['entry_ssKey'] = $this->language->get('entry_ssKey');
                $this->data['entry_wsKey'] = $this->language->get('entry_wsKey');
		$this->data['entry_data_feed'] = $this->language->get('entry_data_feed');
		
		$this->data['button_save'] = $this->language->get('button_save');
		$this->data['button_cancel'] = $this->language->get('button_cancel');

		$this->data['tab_general'] = $this->language->get('tab_general');

 		if (isset($this->error['warning'])) {
			$this->data['error_warning'] = $this->error['warning'];
		} else {
			$this->data['error_warning'] = '';
		}
		
  		
                $languages = $this->db->query("SELECT * FROM `" . DB_PREFIX . "language` ORDER BY `language_id`");
                $this->data['default_langaue'] = $this->config->get('config_language');
                $this->data['languages'] = $languages; 
                if(!defined('GSS'))
                    $this->template = 'feed/sheetsync_fields.tpl';
                else
                    $this->template = 'feed/sheetsync_fields.tpl';
                if(!defined('GSS')){
                    $this->children = array(
                            'common/header',
                            'common/footer'
                    );
                }		
		$this->response->setOutput($this->render());
                
        }
        
        
        protected function validate() {
		if (!$this->user->hasPermission('modify', 'feed/sheetsync')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->error) {
			return true;
		} else {
			return false;
		}	
	}	
        
        
        public function ajax(){
            $action = $this->request->post['action'];
            switch($action){
       
                 case 'editSheet':
                     $this->load->model('feed/sheetsync');
                    if($this->request->post['title'] && $this->request->post['key']){
                        $this->model_feed_sheetsync->editSpreadSheet(array(
                            'title'=>       $this->request->post['title'],
                            'key'  =>       $this->request->post['key'],
                            'setting_id' => 0,
                            'id'         => $this->request->post['id']
                        ));
                        die(json_encode(array('status'=>true,'sheets'=>$this->model_feed_sheetsync->fetchSpreadSheets())));
                    }
                    break;
                 case 'delSheet':
                     $this->load->model('feed/sheetsync');
                     $this->model_feed_sheetsync->deleteSpreadSheet($this->request->post['id']);
                     break;
                 case 'getSheet':
                     $this->load->model('feed/sheetsync');
                     $sheet = $this->model_feed_sheetsync->fetchSpreadsheet($this->request->post['id']);
                     die(json_encode(array('status'=>true,'sheet'=>$sheet)));
                     break;
                 case "buildToken":
                            $this->load->model('setting/setting');
                            $post = $this->request->post;
                            set_include_path(DIR_SYSTEM . 'library' );
                            require_once 'Google/Client.php';
                            require_once 'Google/Service/Urlshortener.php';
                            $client = new Google_Client();
                            $client->setClientId($post['client_id']);
                            $client->setClientSecret($post['client_secret']);
                            $client->setRedirectUri($post['redirect_uri']);
                            $client->addScope("https://www.googleapis.com/auth/drive");
                            $client->setAccessType('offline');
                            $client->setApprovalPrompt('force');
                            unset($post['action']);
                            $this->model_setting_setting->editSetting('sheetsync',$post);
                            die(json_encode(array('url'=>$client->createAuthUrl())));
                        break;
                    case 'addTemplate':
                            $this->load->model('setting/setting');
                            $this->load->model('feed/sheetsync');
                            set_include_path(DIR_SYSTEM . 'library' );
                            require_once 'Google/Client.php';
                            require_once 'Google/Http/MediaFileUpload.php';
                            require_once 'Google/Service/Drive.php';
                           
                            $settings = $this->model_setting_setting->getSetting('sheetsync');
//                            print_r($settings); exit;
                            $client = new Google_Client();
                            $client->setClientId($settings['client_id']);
                            $client->setClientSecret($settings['client_secret']);
                            $client->setRedirectUri($settings['redirect_uri']);
                            $client->addScope("https://www.googleapis.com/auth/drive");
                            $client->setAccessToken(base64_decode($settings['accesstoken']));
                            
                            if ($client->isAccessTokenExpired() && isset($settings['refresh_token']) && !(empty($settings['refresh_token']))) {
                                
                                try{
                                 $client->refreshToken($settings['refresh_token']);
                                }catch(Exception $e){
                                    die(array('error'=>$e->message));
                                }
                            }
                            $service = new Google_Service_Drive($client);
                            $template_key = $this->model_feed_sheetsync->call('getTemplateGet');
                            $template_key = $template_key->_k;
                            if(empty($template_key))
                                die(json_encode(array('error'=>'Template not found')));
                            $result = $this->copyFile($service,$template_key,$this->request->post['title']);
                            
                            $this->load->model('feed/sheetsync');
                            $this->model_feed_sheetsync->addSpreadSheet(array(
                                'title'=>$this->request->post['title'],
                                'key'  =>$result->id,
                                'setting_id' =>0,
                                'status'    => 1
                            ));
                            die(json_encode(array('status'=>true,'result'=>$result)));
                            
                        break;
                        
                 case 'version_check':
                     $this->load->model('feed/sheetsync');
                     $response = $this->model_feed_sheetsync->call('version_check');
                     
                     if(version_compare($response->Version->version_no, GSS_VERSION) === 1){
                         die(json_encode(array('new'=>true,'msg'=>"A new version {".$response->Version->title." v." . $response->Version->version_no . '} is now available','version'=>$response->Version)));
                     }else{
                         die(json_encode(array('new'=>false,'msg'=>'You are using the latest version.')));
                     }
                     break;
                 case 'update_version':
                     $this->load->model('feed/sheetsync');
                     $response = $this->model_feed_sheetsync->call('version_check');
                     
                     if(version_compare($response->Version->version_no, GSS_VERSION) === 1){
                         $this->updateCode($response->Version->github_link);
                     }
                     
                     break;
            }
        }


        private function copyFile($service, $originFileId, $copyTitle) {
              $file = new Google_Service_Drive_DriveFile();
              $file->setTitle($copyTitle);
              try {
                return $service->files->copy($originFileId, $file);
              } catch (Exception $e) {
                print "An error occurred: " . $e->getMessage();
              }
              return NULL;
        }
        
        public function updateCode($link){
            $json = array();
            $cache_file = DIR_CACHE .'gssuploads.zip';
            $application_path = dirname(DIR_APPLICATION);
            file_put_contents($cache_file, file_get_contents($link));
            if(is_file($cache_file)){
              $zip = new ZipArchive;
                if ($zip->open($cache_file) === TRUE) {
                    for($i = 0; $i < $zip->numFiles; $i++) {
                         $filename = $zip->getNameIndex($i);
                         $fileinfo = pathinfo($filename);
                         if($fileinfo['dirname'] == '.'){
                             $zip_root_folder = $filename;
                         }else{
                             if(isset($fileinfo['extension'])){
                                 $folder_path = str_replace($zip_root_folder,'', $fileinfo['dirname']);
                                 $destination_path = $application_path .DIRECTORY_SEPARATOR. $folder_path; //$fileinfo['basename'];
                                 if(!is_dir($destination_path)){
                                     mkdir($destination_path,755,true);
                                 }
                                 $destination = $destination_path . DIRECTORY_SEPARATOR . $fileinfo['basename'];
                                 copy("zip://".$cache_file."#".$filename,$destination);
                                 $json['files'][] = $destination;
                             }
                         }
                     }                  
                     $zip->close();                  
                } 
                $json['msg'] = "Congrats! Syncsheet Now updated to latest version.";
                unlink($cache_file);
            }else{
                $json['error'] = "Sorry, Unable to download updates";
            }
            die(json_encode($json));
        }

}
?>