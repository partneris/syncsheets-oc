<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class ModelFeedSyncsheets extends Model {

   private $api = "http://api.syncsheets.com/api/v1/";
//    private $api = "http://localhost/spreadserver/v1/";
   public $multiple = array(
        'name',
        'description',
        'meta_keyword',
        'meta_description',
        'tag'
    );
    var $lg=array();
    var $id = null;
    var $product = array();
    var $headers = array();
    var $incoming_headers = array();
    var $option_headers = array();
    var $productMap = array();
    var $product_discount = array();
    var $product_special = array();
    var $productfeed = array();
    var $collection = array();
    var $categories = array();
    var $response = array();
    var $pf = array(); //Product Table Fields
    var $action = '';
    public function install() {
        $this->db->query('CREATE TABLE IF NOT EXISTS `' . DB_PREFIX . 'gs_settings` (
                              `id` int(11) NOT NULL AUTO_INCREMENT,
                              `title` varchar(255) NOT NULL,
                              `settings` longtext NOT NULL,
                              `headers` longtext NOT NULL,
                              `created` datetime NOT NULL,
                              `updated` datetime NOT NULL,
                              PRIMARY KEY (`id`)
                            ) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;');

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "gs_spreadsheets` (
                              `id` int(11) NOT NULL AUTO_INCREMENT,
                              `title` varchar(255) NOT NULL,
                              `key` varchar(255) NOT NULL,
                              `last_sync` datetime NOT NULL,
                              `setting_id` int(11) NOT NULL,
                              `status` tinyint(1) NOT NULL,
                              `created` datetime NOT NULL,
                              `updated` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
                              PRIMARY KEY (`id`)
                            ) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;
                            ");
    }
    
    public function _pf(){
        $query = $this->db->query("desc `".DB_PREFIX."product`");
        foreach($query->rows as $item){
            $this->pf[$item['Field']] = $item['Field'];
        }
        
    }

    public function setId($id) {
        $this->id = $id;
        return $this;
    }

    public function setSetting($setting){
        $this->_pf();
        $this->setting  = $setting;
        return $this;
    }
    
    public function setHeaders($headers) {
        $this->_pf();
        $this->headers = $headers;
        foreach($headers as $item){
            $this->incoming_headers[$item] = '';
        }
        return $this;
    }

    public function setOptionHeaders($optionsHeaders) {
        $this->option_headers = $optionsHeaders;
        return $this;
    }
    
    public function setAction($action){
        $this->action = $action;
        return $this;
    }
    
    public function setProductFeed($products){
        $this->productfeed = $products;
        return $this;
    }
    
    public function extractHeader(){
		
        if(isset($this->productfeed[0])){
            foreach($this->productfeed[0] as $key=>$item){
                $this->headers[] = $key;
            }
        }
        return $this;
    }
    
    public function setOptionFeed($options){
        $this->optionfeed = $options;
        return $this;
    }
    
    public function getLanguages() {
        $languages = array();
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "language` ORDER BY `language_id`");
        if ($query->num_rows) {
            foreach ($query->rows as $language) {
                $languages[$language['language_id']] = $language;
                $this->lg[$language['code']]=$language;
            }
        }
        $this->languages = $languages;
        
        return $this;
    }

    public function prepareGet() {
        $product_id = $this->id;
        $product = $this->getProduct($product_id);
        $description = $this->getProductDescription($product_id);
        if(is_array($product) && is_array($description)){
            $this->product = array_merge($product, $description);
        }
        $this->attributes = array();
        $this->attributes = $this->getProductAttrbutes($product_id);
//        print_r($this->attributes); exit;
        $this->product_discount = array();
        $this->product_special = array();
        $discounts = $this->getProductDiscounts($product_id);
        if ($discounts) {
            $this->product['discount'] = json_encode($discounts);
        }
        $specials = $this->getProductSpecials($product_id);
       
        if ($specials) {
            $this->product['special'] = json_encode($specials);
        }
        $json_option = "";
        $options = $this->getProductOptions($product_id);
        $json_opt = $this->gss_json_encode($options);

        $this->product['options'] = $json_opt;
        return $this;
    }
    
    private function _beforetFilter(){
        foreach ($this->headers as $head=>$fn){
            if(isset($fn['beforetFilter']) && $this->_isCl($fn['beforetFilter'])){
                $fn['beforetFilter']->__invoke($head,$this);
            }
        }
    }
    
    public function applyFilters(){
        $hooks = array();
        
        $path = dirname(__FILE__);
        $hooks_container = $path . DIRECTORY_SEPARATOR . 'hooks' . DIRECTORY_SEPARATOR;
        if(is_dir($hooks_container)){
            foreach (glob($hooks_container."*.php") as $filename){
                require_once($filename);
            }
        }else{
            die('Error: Unable to load hooks!');
        }
        
        $matches = array();
        
        foreach($this->headers as $field){
            if($hook = $this->find($field,$hooks)){
                $matches[$field] = $hook;
            }else{
                $matches[$field] = '';
            }
        }
        
        $this->headers = $matches;
        $this->_beforetFilter();
        
        return $this;
    }
    
    public function array2json($arr) {
    
    $parts = array();
    $is_list = false;

    //Find out if the given array is a numerical array
    $keys = array_keys($arr);
    $max_length = count($arr)-1;
    if(($keys[0] == 0) and ($keys[$max_length] == $max_length)) {//See if the first key is 0 and last key is length - 1
        $is_list = true;
        for($i=0; $i<count($keys); $i++) { //See if each key correspondes to its position
            if($i != $keys[$i]) { //A key fails at position check.
                $is_list = false; //It is an associative array.
                break;
            }
        }
    }

    foreach($arr as $key=>$value) {
        if(is_array($value)) { //Custom handling for arrays
            if($is_list) $parts[] = $this->array2json($value); /* :RECURSION: */
            else $parts[] = '"' . $key . '":' . $this->array2json($value); /* :RECURSION: */
        } else {
            $str = '';
            if(!$is_list) $str = '"' . $key . '":';

            //Custom handling for multiple data types
            if(is_numeric($value)) $str .= $value; //Numbers
            elseif($value === false) $str .= 'false'; //The booleans
            elseif($value === true) $str .= 'true';
            else $str .= '"' . addslashes(html_entity_decode($value)) . '"'; //All other things
            // :TODO: Is there any more datatype we should be in the lookout for? (Object?)

            $parts[] = $str;
        }
    }
    $json = implode(',',$parts);
    
    if($is_list) return '[' . $json . ']';//Return numerical JSON
    return '{' . $json . '}';//Return associative JSON
} 
    
    public function find($field,$hooks){
        foreach($hooks as $item){
            if($item['type']=='normal' && $item['match']==$field){
                return $item;
            }elseif($item['type']=='regex' && preg_match($item['match'],$field)){
                return $item;
            }
        }
    }

    public function get(){
        $this->productMap=array();
        $this->presets = array();
        foreach ($this->headers as $key=>$field){
            if(empty($field)){
                $this->productMap[$key] = (isset($this->product[$key]))?$this->product[$key]:'';
            }else{
               $result = (isset($field['get']))?$field['get']->__invoke($this,$key):'';
               if($result)
               if(is_array($result)){
                   $this->productMap = array_merge($result, $this->productMap);
               }else{
                   $this->productMap[$key]=$result;
               }
            }
        }
//        echo $this->action; exit;
        $this->productMap = array_merge($this->incoming_headers,$this->productMap);
        if($this->action=='export'){
//            $options=array(); $options = $this->_getOptions();
            return $this->productMap;
        }else{
            return $this->productMap;
        }
    }
    
    public function _getOptions(){
        $option_data=array();
       
        if($this->product_option) {
            $ki = 0;
            foreach ($this->product_option as $option) {
                if(in_array('model', $this->option_headers))
                    $option_data[$ki]['productid']=$this->product['product_id'];
                if(in_array('product_id', $this->option_headers))
                $option_data[$ki]['model']=$this->product['model'];
                if(in_array('option_name', $this->option_headers))
                    $option_data[$ki]['optionname']=$option['name'];
                if(in_array('option_type', $this->option_headers))
                    $option_data[$ki]['option_type']=$option['type'];
                if(in_array('option_value', $this->option_headers))
                    $option_data[$ki]['option_value']=$option['option_value'];
                if(in_array('required', $this->option_headers))
                    $option_data[$ki]['required']=($option['required']) ? 'Yes' : 'No';
                if(in_array('quantity', $this->option_headers))
                    $option_data[$ki]['quantity']=$option['quantity'];
                if(in_array('subtract_stock', $this->option_headers))
                    $option_data[$ki]['subtractstock']=($option['subtract']) ? 'Yes' : 'No';
                if(in_array('price', $this->option_headers))
                    $option_data[$ki]['price']=$option['price'];
                if(in_array('point', $this->option_headers))
                    $option_data[$ki]['points']=$option['points'];
                if(in_array('weight', $this->option_headers))
                    $option_data[$ki]['weight']=$option['weight'];
                $ki++;
            }
            return $option_data;
        }
    }
    
    public function fetchProducts($start, $limit, $filter_category='') {
        $sql = "SELECT p.product_id FROM " . DB_PREFIX . "product p ";
        
        $sql .= " LEFT JOIN " . DB_PREFIX . "product_description pd on (p.product_id=pd.product_id) where 1 ";

        $sql .= " AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "' "; 

        $sql .= "GROUP BY p.product_id limit $start, $limit";

        $query = $this->db->query($sql);
        return $query->rows;
    }
    
    public function getProductCategories($product_id) {
		$product_category_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product_id . "'");

		foreach ($query->rows as $result) {
			$product_category_data[] = $result['category_id'];
		}

		return $product_category_data;
	}
    
    public function getProductAttribute($product_id) {
        $language = $this->db->query("SELECT * FROM `" . DB_PREFIX . "language` ORDER BY `language_id`");

        $sql = "SELECT ";
        $i = 0;
        foreach ($language->rows as $lan) {
            if ($i++ != 0)
                $sql .= ",";
            $sql .= " (SELECT CONCAT(ad.attribute_id,name) FROM " . DB_PREFIX . " attribute_description ad WHERE ad.attribute_id = pa.attribute_id AND ad.language_id = '" . $lan['language_id'] . "' limit 0,1) AS name_" . $lan['code'];
            $sql .= ", (SELECT text FROM " . DB_PREFIX . " product_attribute ipa WHERE ipa.attribute_id = pa.attribute_id AND ipa.language_id = '" . $lan['language_id'] . "' AND ipa.product_id='" . $product_id . "' limit 0,1) AS text_" . $lan['code'];
        }

        $sql .= " FROM " . DB_PREFIX . "product_attribute pa, " . DB_PREFIX . "product p where pa.product_id=p.product_id AND pa.product_id = '" . $product_id . "' GROUP BY pa.attribute_id ORDER BY pa.attribute_id";

        $result = $this->db->query($sql);

        if ($result->rows) {
            return $result->rows;
        }
    }
    
    public function getProductAttrbutes($product_id) {
        $attrs = $this->db->query("select * from `" . DB_PREFIX . "product_attribute` where product_id = '$product_id'");
        
        $product_attributes = array();
        foreach ($attrs->rows as $key => $at) {
            if(isset($this->languages[$at['language_id']]['code']))
               $product_attributes['at'.$at['attribute_id'].$this->languages[$at['language_id']]['code']] = $at['text'];
        }
        return $product_attributes;
    }
    
    public function _getAttrNameByLang($attr,$lang){
          $query = $this->db->query("SELECT * FROM  `" . DB_PREFIX . "attribute_description` WHERE  `attribute_id` = '$attr' AND  `language_id` = '$lang'");
          return $query->row['name'];
          
    }

    public function getProductOptions($product_id) {

        $productoptions = $this->model_catalog_product->getProductOptions($product_id);

        $product_options = array();
        foreach ($productoptions as $product_option) {
            if ($product_option['type'] == 'select' || $product_option['type'] == 'radio' || $product_option['type'] == 'checkbox' || $product_option['type'] == 'image') {
                $product_option_value_data = array();

                foreach ($product_option['product_option_value'] as $product_option_value) {
                    $product_options[] = array(
                        'name' => $product_option['name'],
                        'type' => $product_option['type'],
                        'required' => $product_option['required'],
                        'option_value' => $this->getOptionValueText($product_option_value['option_value_id']),
                        'quantity' => $product_option_value['quantity'],
                        'subtract' => $product_option_value['subtract'],
                        'price' => $product_option_value['price_prefix'] . $product_option_value['price'],
                        'weight' => $product_option_value['weight'],
                        'points' => ""
                    );
                }
            } else {
                $product_options[] = array(
//                    'productid' => $product_id,
                    'name' => $product_option['name'],
                    'type' => $product_option['type'],
                    'option_value' => (isset($product_option['option_value']))?$product_option['option_value']:'',
                    'required' => $product_option['required'],
                    'quantity' => "",
                    'subtract' => "",
                    'price' => "",
                    'weight' => (isset($product_option['weight'])) ? $product_option['weight'] : '',
                    'points' => ""
                );
            }
        }
//                print_r($product_options); exit;
        return $product_options;
    }
    
    private function _getOptionValueText($value_id) {
        $result = $this->db->query("select name FROM " . DB_PREFIX . "option_value_description where option_value_id='$value_id'");

        if ($result->row)
            return $result->row['name'];
    }

    public function getProductDiscounts($product_id) {
        $query = $this->db->query("SELECT pd.*,cgd.name as customer_group_id FROM " . DB_PREFIX . "product_discount pd LEFT JOIN " . DB_PREFIX . "customer_group_description cgd ON (cgd.customer_group_id=pd.customer_group_id) WHERE pd.product_id = '" . (int) $product_id . "' group by pd.product_discount_id ORDER BY quantity, priority, price");
        $discounts = array();
        if($query->num_rows){
            foreach($query->rows as $item){
                $discounts[] = array(
                    'group' =>  $item['customer_group_id'],
                    'price' =>  $item['price'],
                    'quantity'  =>  $item['quantity'],
                    'priority'  =>  $item['priority'],
                    'date_start'    =>  $item['date_start'],
                    'date_end'      =>  $item['date_end']
                );
            }
        }
        return $discounts;
    }

    public function getProductSpecials($product_id) {
        $query = $this->db->query("SELECT ps.*,cgd.name as customer_group_id FROM " . DB_PREFIX . "product_special ps LEFT JOIN " . DB_PREFIX . "customer_group_description cgd ON (cgd.customer_group_id=ps.customer_group_id) WHERE ps.product_id = '" . (int) $product_id . "' group by ps.product_special_id ORDER BY priority, price");
        $specials = array();
        if($query->num_rows){
            foreach($query->rows as $item){
                $specials[] = array(
                    'group' =>  $item['customer_group_id'],
                    'price' =>  $item['price'],
                    'priority'  =>  $item['priority'],
                    'date_start'    =>  $item['date_start'],
                    'date_end'      =>  $item['date_end']
                );
            }
        }
        return $specials;
    }

    private function getOptionValueText($value_id) {
        $result = $this->db->query("select name FROM " . DB_PREFIX . "option_value_description where option_value_id='$value_id'");

        if ($result->row)
            return $result->row['name'];
    }

    public function getProductDescription($product_id) {
        $labels = array('name', 'description', 'metadescription', 'metakeyword', 'tag');
        $language = $this->db->query("SELECT * FROM `" . DB_PREFIX . "language` ORDER BY `language_id`");

        $sql = "SELECT p.product_id AS productid";
        foreach ($language->rows as $lan) {
            $sql .= ", (SELECT name FROM " . DB_PREFIX . "product_description pd WHERE pd.product_id = p.product_id AND pd.language_id = '" . $lan['language_id'] . "') AS name_" . $lan['code'];
            $sql .= ", (SELECT description FROM " . DB_PREFIX . "product_description pd WHERE pd.product_id = p.product_id AND pd.language_id = '" . $lan['language_id'] . "') AS description_" . $lan['code'];
            $sql .= ", (SELECT meta_description FROM " . DB_PREFIX . "product_description pd WHERE pd.product_id = p.product_id AND pd.language_id = '" . $lan['language_id'] . "') AS meta_description_" . $lan['code'];
            $sql .= ", (SELECT meta_keyword FROM " . DB_PREFIX . "product_description pd WHERE pd.product_id = p.product_id AND pd.language_id = '" . $lan['language_id'] . "') AS meta_keyword_" . $lan['code'];
            $sql .= ", (SELECT tag FROM " . DB_PREFIX . "product_description pd WHERE pd.product_id = p.product_id AND pd.language_id = '" . $lan['language_id'] . "') AS tag_" . $lan['code'];
            foreach ($labels as $title)
                $head[] = $title . $lan['code'];
        }

        $sql .= " FROM " . DB_PREFIX . "product p where p.product_id = '" . $product_id . "' group by p.product_id ORDER BY p.product_id";


        $result = $this->db->query($sql);

        if ($result->rows)
            return $result->row;
    }

    public function getProductsCount($data) {
        $sql = "SELECT p.product_id";

        $sql .= " FROM " . DB_PREFIX . "product p ";
        if(isset($data['filter_category']) && !empty($data['filter_category'])){
            $sql .= " LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON p.product_id=p2c.product_id ";
        }
        
        $sql .= "LEFT JOIN " . DB_PREFIX . "product_description pd ON (pd.product_id=p.product_id) WHERE 1";
        
        if(isset($data['filter_category']) && !empty($data['filter_category'])){
            $sql .= " AND p2c.category_id IN (".  implode(',', $data['filter_category']).") ";
        }
        
        $sql .= " group by p.product_id";

        $result = $this->db->query($sql);

        return $result->num_rows;
    }

    public function prepareSet(){
       
        $this->setting = unserialize(base64_decode($this->setting['settings']));
        if(!$this->setting)
            die(array('error'=>true,'msg'=>'Settings not found'));
        foreach($this->setting['general']['defaults'] as $key => $def) {
            $this->defaults = $def;
            break;
        }
        
        $this->desc_default = array(
            'name' => '',
            'description' => '',
            'meta_keyword' => '',
            'meta_description' => '',
            'tag' => ''
        );
        
        
        if(!$this->default_discount){
            $this->default_discount = array(
                'customer_group_id' => $this->setting['discount']['customer_group'],
                'quantity'          => $this->setting['discount']['quantity'],
                'priority'          => $this->setting['discount']['priority'],
                'price'             => $this->setting['discount']['price'],
                'date_start'        => $this->setting['discount']['date_start'],
                'date_end'          => $this->setting['discount']['date_end'],
            );
        }
        if(!$this->default_special){
            $this->default_special = array(
                'customer_group_id' => $this->setting['special']['customer_group'],
                'priority'          => $this->setting['special']['priority'],
                'price'             => $this->setting['special']['price'],
                'date_start'        => $this->setting['special']['date_start'],
                'date_end'          => $this->setting['special']['date_end'],
            );
        }
        
        $this->option_default = $this->setting['option'];
        //
        
        $this->languages = $this->getLanguagesByCode();
        $this->prepareCategoriesList();
        return $this;
    }
    
    public function prepareCategoriesList(){
        foreach($this->languages as $language)
            $this->categories[$language['code']] = $this->getPaths($language['language_id']);
    }
    
    public function set(){
        if ($this->setting && $this->productfeed){
//            print_r($this->productfeed); exit;

//don't know why this is needed, commenting out next line
//            $this->load->model('catalog/product');
            
            $this->response=array();
            $update = 0;
            $added = 0;
//            print_r($this->productfeed); exit;
            foreach ($this->productfeed as $i => $product) {
                $this->_bind($product); // uses the 'add' funcion from hooks
                $this->_beforeSave($product);
               
                if(!is_numeric($product->product_id) && $product->product_id!='') {
                    $this->_merge();
                    $new_product_id = $this->addProduct($this->product,$this->languages);
                    $added++;
                    if(!empty($product->product_id) && $new_product_id)
                        $this->response['created'][$product->product_id] = $new_product_id;
                }else{
                    $this->_mergeDiscount();
                    $this->_mergeSpecial();
                    $this->_mergeOption();
                    $update++;
                    $this->editProduct($product->product_id, $this->product);
                    $this->response['updated'] = $update;
                }
                $this->collection[] = $this->product;
                
                $this->product = array();
            }
            $this->repairCategories();
            $this->cache->delete('*');
            die(json_encode($this->response));
        } //endif
    }
    
    private function _bind($product){
        
        foreach ($product as $key=>$value) {
            if(isset($this->headers[$key]['add']) && $this->_isCl($this->headers[$key]['add'])){
                $this->headers[$key]['add']->__invoke($key,$value,$this);
            }else{
                $this->product['product'][$key] = $value;
            }
        }
    }
    
    private function _beforeSave($product){
        foreach ($this->headers as $fn){
            if(isset($fn['_bfs']) && $this->_isCl($fn['_bfs'])){
                $fn['_bfs']->__invoke($this);
            }
        }
    }
    
    //Apllies default values
    private function _merge(){
        if(isset($this->product['product_description']))
           foreach ($this->product['product_description'] as $key => $pDesc)
               $this->product['product_description'][$key] = array_merge($this->desc_default, $pDesc);
//        if($this->action!='update')
        $this->product['product'] = array_merge($this->defaults, $this->product['product']);
        
        if(!isset($product->product['product_store']))
                $this->product['product_store'][0] = 0;
        
        $this->_mergeDiscount();
        $this->_mergeSpecial();
        $this->_mergeOption();
    }
    
    private function _mergeDiscount() {
    	if (isset($this->product['product_discount'])) {
    		foreach($this->product['product_discount'] as $kk => $pdisc) {
    			$this->product['product_discount'][$kk] = array_merge($this->default_discount, $pdisc);
    		}
    	}
    }

    private function _mergeSpecial() {
    	if (isset($this->product['product_special'])) {
    		foreach($this->product['product_special'] as $kk => $pspec) {
    			$this->product['product_special'][$kk] = array_merge($this->default_special, $pspec);
    		}
    	}
    }

    private function _mergeOption() {
    	if (isset($this->product['product_option'])) {
    		foreach($this->product['product_option'] as $kk => $poption) {
				$this->product['product_option'][$kk] = array_merge($this->option_default, $poption);
			}
    	}
    }

    public function _setOptions() {
    	$language_id = $this->config->get('config_language_id');
    	if ($this->optionfeed) {
    		foreach($this->optionfeed as $option) {
    			if (isset($this->response['created']) && !empty($this->response['created']))
    				if (array_key_exists($option->product_id, $this->response['created']))
    					$option->product_id = $this->response['created'][$option->product_id];
    			$this->saveOption($option, $language_id, $this->languages);
    		}
    	}
    }
    
    public function _isCl($t) {
        return is_object($t) && ($t instanceof Closure);
    }
    
    public function repairCategories($parent_id = 0) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "category WHERE parent_id = '" . (int)$parent_id . "'");
		
		foreach ($query->rows as $category) {
			// Delete the path below the current one
			$this->db->query("DELETE FROM `" . DB_PREFIX . "category_path` WHERE category_id = '" . (int)$category['category_id'] . "'");
			
			// Fix for records with no paths
			$level = 0;
			
			$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "category_path` WHERE category_id = '" . (int)$parent_id . "' ORDER BY level ASC");
			
			foreach ($query->rows as $result) {
				$this->db->query("INSERT INTO `" . DB_PREFIX . "category_path` SET category_id = '" . (int)$category['category_id'] . "', `path_id` = '" . (int)$result['path_id'] . "', level = '" . (int)$level . "'");
				
				$level++;
			}
			
			$this->db->query("REPLACE INTO `" . DB_PREFIX . "category_path` SET category_id = '" . (int)$category['category_id'] . "', `path_id` = '" . (int)$category['category_id'] . "', level = '" . (int)$level . "'");
						
			$this->repairCategories($category['category_id']);
		}
	}
    ////////////////////////// New Logic Implimentation here ////////////////////////////////

    public function getFields($table) {
        $query = $this->db->query("DESCRIBE `" . DB_PREFIX . $table . "`");
        $table = array();
        foreach ($query->rows as $item) {
            $table[$item['Field']] = $this->cleanField($item['Field']);
        }
        return $table;
    }

    public function cleanField($field) {
        $field = str_replace('_id', '', $field);
        return ucwords(str_replace('_', ' ', $field));
    }

    public function getStores() {
        $stores = array(
            0 => $this->config->get('config_name')
        );
        $query = $this->db->query("select * from ".DB_PREFIX."store");
        if ($query->num_rows) {
            foreach ($query->rows as $store) {
                $stores[$store['store_id']] = $store['name'];
            }
        }
        return $stores;
    }
    
    public function getStockStatus(){
        $status = array();
        $query = $this->db->query("select * from ".DB_PREFIX."stock_status where language_id = '".(int)$this->config->get('config_language_id')."'");
        if($query->num_rows){
            foreach($query->rows as $row){
                $status[$row['stock_status_id']] = $row['name'];
            }
        }
        return $status;
    }
    
    public function getManufacturers(){
        $manuf = array(''=>'None');
        $query = $this->db->query("select * from ".DB_PREFIX."manufacturer");
        if($query->num_rows){
            foreach($query->rows as $row){
                $manuf[$row['manufacturer_id']] = $row['name'];
            }
        }
        return $manuf;
    }
    
    public function getTaxClass(){
        $taxes = array(''=>'None');
        $query = $this->db->query("select * from ".DB_PREFIX."tax_class");
        //print_r($query->rows); exit;
        if($query->num_rows){
            foreach($query->rows as $row){
                $taxes[$row['tax_class_id']] = $row['title'];
            }
        }
        return $taxes;
    }
    
    public function getWeightClass(){
        $weights = array();
        $query = $this->db->query("select wcd.weight_class_id, wcd.title from ".DB_PREFIX."weight_class wc LEFT JOIN ".DB_PREFIX."weight_class_description wcd ON (wc.weight_class_id = wcd.weight_class_id)");
        if($query->num_rows){
            foreach($query->rows as $row){
                $weights[$row['weight_class_id']] = $row['title'];
            }
        }
        return $weights;
    }
    
    public function getLenghtClass(){
        $lenghts = array();
        $query = $this->db->query("select lcd.length_class_id, lcd.title from ".DB_PREFIX."length_class lc LEFT JOIN ".DB_PREFIX."length_class_description lcd ON (lc.length_class_id = lcd.length_class_id)");
        if($query->num_rows){
            foreach($query->rows as $row){
                $lenghts[$row['length_class_id']] = $row['title'];
            }
        }
        return $lenghts;
    }
    public function getCatPaths() {
        $sql = "SELECT cp.category_id AS category_id, GROUP_CONCAT(cd1.name ORDER BY cp.level SEPARATOR ' > ') AS name, c.parent_id, c.sort_order FROM " . DB_PREFIX . "category_path cp LEFT JOIN " . DB_PREFIX . "category c ON (cp.path_id = c.category_id) LEFT JOIN " . DB_PREFIX . "category_description cd1 ON (c.category_id = cd1.category_id) LEFT JOIN " . DB_PREFIX . "category_description cd2 ON (cp.category_id = cd2.category_id) WHERE cd1.language_id = '" . (int) $this->config->get('config_language_id') . "' AND cd2.language_id = '" . (int) $this->config->get('config_language_id') . "'";
        $sql .= " GROUP BY cp.category_id ORDER BY name";
        $query = $this->db->query($sql);
        if ($query->num_rows)
            return $query->rows;
    }
    public function getFieldSets() {
        $categories = array(''=>'None');
        $language_id = $this->config->get('config_language_id');
        $cats = $this->getCatPaths();
        if($cats)
            foreach ($cats as $cItem) {
                $categories[$cItem['category_id']] = $cItem['name'];
            }
        $field1 = array(
            'store' => array(
                'field' => 'store',
                'required' => true,
                'copy' => true,
                'name' => 'Store',
                'descr' => 'Product store.',
                'multilanguage' => false,
                'options' => $this->getStores()
            ),
            'model' => array(
                'field' => 'model',
                'required' => true,
                'copy' => true,
                'name' => 'Model',
                'descr' => 'A unique product code required by Opencart',
                'multilanguage' => false
            ),
            'sku' => array(
                'field' => 'sku',
                'copy' => true,
                'name' => 'SKU',
                'descr' => '',
                'multilanguage' => false
            ),
            'upc' => array(
                'field' => 'upc',
                'copy' => true,
                'name' => 'UPC',
                'descr' => 'Universal Product Code',
                'multilanguage' => false
            ),
        );

        $fields = array(
            array(
                'field' => 'name',
                'name' => 'Name',
                'descr' => 'Product name',
                'multilanguage' => true
            ),
            array(
                'field' => 'description',
                'name' => 'Description',
                'descr' => 'Product description',
                'multilanguage' => true
            ),
            array(
                'field' => 'category',
                'name' => 'Category',
                'descr' => 'Full category path. If the field is not defined or empty then the default category will be used.',
                'multilanguage' => false,
                'options' => $categories
            ),
            array(
                'field' => 'location',
                'copy' => true,
                'name' => 'Location',
                'descr' => 'This field is not used in front-end but it can be defined for products.',
                'multilanguage' => false
            ),
            array(
                'field' => 'quantity',
                'copy' => true,
                'name' => 'Quantity',
                'descr' => '',
                'multilanguage' => false
            ),
            array(
                'field' => 'minimum',
                'copy' => true,
                'name' => 'Minimum Quantity',
                'descr' => '',
                'multilanguage' => false
            ),
            'subtract' => array(
                'field' => 'subtract',
                'copy' => true,
                'name' => 'Subtract Stock',
                'descr' => "1 - Yes, 0 - No.",
                'multilanguage' => false,
                'options' => array(0=>'No',1=>'Yes')
            ),
            'stock_status_id' => array(
                'field' => 'stock_status_id',
                'name' => 'Out of Stock Status',
                'descr' => 'Name of the stock status. Only stock statuses registered in the store are processed.',
                'multilanguage' => false,
                'options' => $this->getStockStatus()
            ),
            'shipping' => array(
                'field' => 'shipping',
                'copy' => true,
                'name' => 'Requires Shipping',
                'descr' => '1 - Yes, 0 - No.',
                'multilanguage' => false,
                'options' => array(0=>'No',1=>'Yes')
            ),
            array(
                'field' => 'status',
                'name' => 'Status',
                'descr' => "Status 'Enabled' can be defined by '1' or 'Y'. If the status column is not used then behavior depends on the extension settings.",
                'multilanguage' => false,
                'options' => array(0=>'Disable',1=>'Enable')
            ),
            array(
                'field' => 'image',
                'name' => 'Main Product Image',
                'descr' => "A relative path to the image file within 'image' directory or URL.",
                'multilanguage' => false
            ),
            array(
                'field' => 'additional_images',
                'name' => 'Additional Product Images',
                'descr' => "A relative path to the image file within 'image' directory or URL.",
                'multilanguage' => false
            ),
            array(
                'field' => 'manufacturer_id',
                'name' => 'Manufacturer Name',
                'descr' => 'Manufacturer name',
                'multilanguage' => false,
                'options' => $this->getManufacturers()
            ),
            array(
                'field' => 'price',
                'name' => 'Price',
                'descr' => 'Regular product price in primary currency (' . $this->config->get('config_currency') . ')',
                'multilanguage' => false
            ),
            array(
                'field' => 'tax_class_id',
                'name' => 'Tax class',
                'descr' => 'Tax class',
                'multilanguage' => false,
                'options'   => $this->getTaxClass()
            ),
            array(
                'field' => 'weight',
                'name' => 'Weight',
                'descr' => 'Weight class units (declared in the store) can be used with the value. Example: 15.98lbs (no spaces).',
                'multilanguage' => false
            ),
            array(
                'field' => 'weight_class_id',
                'name' => 'Weight Class',
                'descr' => 'Weight class units (declared in the store) can be used with the value. Example: 15.98lbs (no spaces).',
                'multilanguage' => false,
                'options'   => $this->getWeightClass()
            ),
            array(
                'field' => 'length',
                'name' => 'Length',
                'descr' => 'Length class units (declared in the store) can be used with the value. Example: 1.70m (no spaces)',
                'multilanguage' => false
            ),
            array(
                'field' => 'length_class_id',
                'name' => 'Length Class',
                'descr' => 'Length class units (declared in the store) can be used with the value. Example: 1.70m (no spaces)',
                'multilanguage' => false,
                'options'   => $this->getLenghtClass()
            ),
            array(
                'field' => 'width',
                'name' => 'Width',
                'descr' => 'Length class units (declared in the store) can be used with the value. Example: 1.70m (no spaces)',
                'multilanguage' => false
            ),
            array(
                'field' => 'height',
                'name' => 'Height',
                'descr' => 'Length class units (declared in the store) can be used with the value. Example: 1.70m (no spaces)',
                'multilanguage' => false
            ),
            array(
                'field' => 'meta_keyword',
                'name' => 'Meta tag keywords',
                'descr' => '',
                'multilanguage' => true
            ),
            array(
                'field' => 'meta_description',
                'name' => 'Meta tag description',
                'descr' => '',
                'multilanguage' => true
            ),
            array(
                'field' => 'tag',
                'name' => 'Tags',
                'descr' => 'Product Tags',
                'multilanguage' => true
            ),
            'points' => array(
                'field' => 'points',
                'copy' => true,
                'name' => 'Points Required',
                'descr' => 'Number of reward points required to make purchase',
                'multilanguage' => false
            ),
            'sort_order' => array(
                'field' => 'sort_order',
                'copy' => true,
                'name' => 'Sort Order',
                'descr' => '',
                'multilanguage' => false
            ),
            array(
                'field' => 'keyword',
                'name' => 'SEO Keyword',
                'descr' => 'SEO friendly URL for the product. Make sure that it is unique in the store.',
                'multilanguage' => false
            ),
            array(
                'field' => 'date_available',
                'name' => 'Date Available',
                'descr' => 'Format: YYYY-MM-DD, Example: 2012-03-25',
                'multilanguage' => false
            ),
            array(
                'field' => 'related',
                'name' => 'Related Products',
                'descr' => "Define the products related.",
                'multilanguage' => false
            ),
            array(
                'field' => 'downloads',
                'name' => 'Downloads',
                'descr' => 'Define the Product Download',
                'multilanguage' => false
            )
        );

        if (version_compare(VERSION, '1.5.4', '>=')) {
            $fields_ver154 = array(
                'ean' => array(
                    'field' => 'ean',
                    'copy' => true,
                    'name' => 'EAN',
                    'descr' => 'European Article Number',
                    'multilanguage' => false
                ),
                'jan' => array(
                    'field' => 'jan',
                    'copy' => true,
                    'name' => 'JAN',
                    'descr' => 'Japanese Article Number',
                    'multilanguage' => false
                ),
                'isbn' => array(
                    'field' => 'isbn',
                    'copy' => true,
                    'name' => 'ISBN',
                    'descr' => 'International Standard Book Number',
                    'multilanguage' => false
                ),
                'mpn' => array(
                    'field' => 'mpn',
                    'copy' => true,
                    'name' => 'MPN',
                    'descr' => 'Manufacturer Part Number',
                    'multilanguage' => false
                ),
            );

            $fields = array_merge($fields, $fields_ver154);
        }

        $fields = array_merge($field1, $fields);

        $specials = array(
            array(
                'field' => 'customer_group',
                'name' => 'Customer Group',
                'descr' => ''
            ),
            array(
                'field' => 'priority',
                'name' => 'Prioirity',
                'descr' => ''
            ),
            array(
                'field' => 'price',
                'name' => 'Price',
                'descr' => ''
            ),
            array(
                'field' => 'date_start',
                'name' => 'Date Start',
                'descr' => 'Format: YYYY-MM-DD, Example: 2012-03-25'
            ),
            array(
                'field' => 'date_end',
                'name' => 'Date End',
                'descr' => 'Format: YYYY-MM-DD, Example: 2012-03-25'
            ),
        );

        $discounts = array(
            array(
                'field' => 'customer_group',
                'name' => 'Customer Group',
                'descr' => ''
            ),
            'quantity' => array(
                'field' => 'quantity',
                'name' => 'Quantity',
                'descr' => ''
            ),
            'priority' => array(
                'field' => 'priority',
                'name' => 'Prioirity',
                'descr' => ''
            ),
            'price' => array(
                'field' => 'price',
                'name' => 'Price',
                'descr' => ''
            ),
            'date_start' => array(
                'field' => 'date_start',
                'name' => 'Date Start',
                'descr' => 'Format: YYYY-MM-DD, Example: 2012-03-25'
            ),
            'date_end' => array(
                'field' => 'date_end',
                'name' => 'Date End',
                'descr' => 'Format: YYYY-MM-DD, Example: 2012-03-25'
            ),
        );

        $reward_points = array(
            'customer_group' => array(
                'field' => 'customer_group',
                'name' => 'Customer Group',
                'descr' => '',
            ),
            'points' => array(
                'field' => 'points',
                'name' => 'Reward Points',
                'descr' => '',
            ),
        );

        $sets = array(
            'fields' => $fields,
            'discounts' => $discounts,
            'specials' => $specials,
            'reward_points' => $reward_points,
        );

        return $sets;
    }

    public function getMaxCategory() {
        $query = $this->db->query("select max(count) as max_cat from (SELECT count(p2c.product_id) as count FROM `" . DB_PREFIX . "product_to_category` p2c left join " . DB_PREFIX . "product p on (p2c.product_id=p.product_id) group by p2c.product_id) as runtime");
        if ($query->num_rows) {
            return $query->row['max_cat'];
        }
        
    }
    
    public function getMaxDiscount() {
        $query = $this->db->query("select max(count) as max_discount from (SELECT count(pd.product_id) as count FROM `" . DB_PREFIX . "product_discount` pd left join " . DB_PREFIX . "product p on (pd.product_id=p.product_id) group by pd.product_id) as runtime");
        if ($query->num_rows) {
            return $query->row['max_discount'];
        }
        
    }
    
    public function getMaxSpecial() {
        $query = $this->db->query("select max(count) as max_spec from (SELECT count(ps.product_id) as count FROM `" . DB_PREFIX . "product_special` ps left join " . DB_PREFIX . "product p on (ps.product_id=p.product_id) group by ps.product_id) as runtime");
        if ($query->num_rows) {
            return $query->row['max_spec'];
        }
        return 0;
    }
    
    public function buildJsonHeader($settings) {
        $fields = unserialize(base64_decode($settings));
        $languages = $this->getLanguagesByCode();
       
        $headers = array(); 
        $headers['Product ID'] = '{"field":"product_id"}';
        foreach ($fields['general']['required'] as $items) {
            foreach ($fields['general']['defaults'] as $key => $value) {
                if(!isset($languages[$key])) continue;
                $language = $languages[$key];
                $trimmed = ucwords(str_replace('_', ' ', $items));
                if (in_array($items, $this->multiple)) {
                    if (isset($fields['general']['defaults'][$language['code']][$items])) {
                        $headers[$trimmed .' ('. $language['code'].')'] = '{"field":"'.$items.'","lang":"'.$language['code'].'"}';
                    }
                } else {
                    $code = $this->config->get('config_language');
                    if ($items == 'category') {
                        $headers[$trimmed . ' (' . $code . ')'] = '{"field":"'.$items.'","lang":"'.$code.'","del":","}';
                    } else {
                        $headers[$trimmed] = $items;
                        $headers[$trimmed] = '{"field":"'.$items.'"}';
                    }
                }
            }
        }
        
        $query = $this->db->query("SELECT ad.* , l.code
        FROM ".DB_PREFIX."attribute_description ad
        LEFT JOIN ".DB_PREFIX."language l ON ( ad.language_id = l.language_id )");
        if($query->rows){
            foreach($query->rows as $ak=>$av){
                $headers['AT-'.$av['name'].' ('.$av['code'].')'] = '{"field":"attribute","id":'.$av['attribute_id'].',"lang":"'.$av['code'].'","name":"'.$av['name'].'"}';
            }
        }
        $headers['options'] = '{"field":"options"}';
       
        $customer_groups = $this->getCustomerGroup();
        $default_customer_group_id = $this->config->get('config_customer_group_id');
        $headers['discount'] = '{"field":"discount","price":0,"priority":0,"quantity":0,"group":"'.$customer_groups[$default_customer_group_id]['name'].'","date_start":"0000-00-00","date_end":"0000-00-00"}';
        $headers['special'] = '{"field":"special","price":0,"priority":0,"group":"'.$customer_groups[$default_customer_group_id]['name'].'","date_start":"0000-00-00","date_end":"0000-00-00"}';
        
        return $headers;
    }

    public function getLanguagesByCode(){
        $languages = array();
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "language` where status=1 ORDER BY `language_id`");
        if ($query->num_rows) {
            foreach ($query->rows as $language) {
                $languages[$language['code']] = $language;
            }
        }
        return $languages;
    }

    public function getAttributeName($id, $language_id) {
        $query = $this->db->query("select ad.* from " . DB_PREFIX . "attribute_description ad LEFT JOIN  " . DB_PREFIX . "language l ON (ad.language_id=l.language_id) where attribute_id = '$id' AND l.code='" . $language_id . "'");
        if ($query->num_rows) {
            return $query->row['name'];
        }
    }

    public function getOptionName($id, $language_id) {
        $query = $this->db->query("select od.* from " . DB_PREFIX . "option_description od LEFT JOIN  " . DB_PREFIX . "language l ON (od.language_id=l.language_id) where option_id = '$id' AND l.code='" . $language_id . "'");
        if ($query->num_rows) {
            return $query->row['name'];
        }
    }

    public function getOptionValue($id) {
        $query = $this->db->query("select * from " . DB_PREFIX . "option_value_description where option_value_id = '$id'");
        if ($query->num_rows) {
            return $query->row;
        }
    }

    public function getCustomerGroup() {
        $cgroup = array();
        $query = $this->db->query("select * from " . DB_PREFIX . "customer_group_description where language_id='" . $this->config->get('config_language_id') . "' order by customer_group_id");
        if ($query->num_rows) {
            foreach ($query->rows as $item) {
                $cgroup[$item['customer_group_id']] = $item;
            }
        }
        return $cgroup;
    }
    
    public function getCustomerGroupByName($name) {
        $cgroup = array();
        $query = $this->db->query("select * from " . DB_PREFIX . "customer_group_description where name = '{$name}' limit 0, 1");
        if($query->num_rows) {
            return $query->row;
        }
    }
    
    public function addSpreadSheet($data) { if(!isset($data['status'])) $data['status']=1;
        $this->db->query("insert into " . DB_PREFIX . "gs_spreadsheets set `title` = '" . $this->db->escape($data['title']) . "' ,`key` = '" . $this->db->escape($data['key']) . "', `status` = '" . $this->db->escape($data['status']) . "', `setting_id` = '" . $this->db->escape($data['setting_id']) . "', `created` = NOW(),`updated`=NOW()");
        $sheet_id = $this->db->getLastId();
        $data['id'] = $sheet_id;
        $this->call('saveSpreadsheet',$data);
    }

    public function editSpreadSheet($data) {
        $this->db->query("update " . DB_PREFIX . "gs_spreadsheets set `title` = '" . $this->db->escape($data['title']) . "' ,`key` = '" . $this->db->escape($data['key']) . "', `setting_id` = '" . $this->db->escape($data['setting_id']) . "',`updated`=NOW() where id='" . $data['id'] . "'");
        $this->call('saveSpreadsheet',$data);
    }
 
    public function updateSpreadSheet($data) {
        $this->db->query("update " . DB_PREFIX . "gs_spreadsheets set `title` = '" . $this->db->escape($data['title']) . "' ,`key` = '" . $this->db->escape($data['key']) . "', `setting_id` = '" . $this->db->escape($data['setting_id']) . "', `last_sync` = '" . $this->db->escape($data['last_sync']) . "', `created` = NOW(),`updated`=NOW()");
    }

    public function deleteSpreadSheet($id) {
        $this->db->query("delete from " . DB_PREFIX . "gs_spreadsheets where id = '$id'");
        $this->call('deleteSpreadsheet',array('id' => $id));
    }

    public function fetchSpreadSheet($id) {
        $query = $this->db->query("select sp.*, sp.title, st.settings as setting from " . DB_PREFIX . "gs_spreadsheets sp LEFT JOIN " . DB_PREFIX . "gs_settings st ON (st.id=sp.setting_id) where sp.id='$id'");
        if ($query->num_rows)
            return $query->row;
    }

    public function fetchSpreadSheets() {
        $query = $this->db->query("select sp.*, st.title as setting, st.id as setting_id from " . DB_PREFIX . "gs_spreadsheets sp LEFT JOIN " . DB_PREFIX . "gs_settings st ON (st.id=sp.setting_id) order by id desc");
        if ($query->num_rows)
            return $query->rows;
    }
 
    public function fetchSingleSheet($id) {
        $this->db->query("select * from " . DB_PREFIX . "gs_spreadsheets where id = '$id'");
    }

    public function google_acount($e, $p) {
        return $this->call(array('action' => 'saveAccount', 'email' => $e, 'password' => $p));
    }

    public function call($method,array $post = NULL, array $options = array(), $content_type = 'json') {
        $key = $this->config->get('ss_key');
//        if(empty($key))
//            die(json_encode(array('error'=>'Syncsheet Key is missing!')));

        $data = array(
            'token' => $key,
            'server' => 1,
            'domain' => HTTP_SERVER,
            'data' => $post,
            'content_type' => $content_type,
            'ocversion' => VERSION
        );
        $useragent = "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1";
        $defaults = array(
            CURLOPT_POST => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_URL => $this->api . $method,
            CURLOPT_USERAGENT => $useragent,
            CURLOPT_FRESH_CONNECT => 1,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_FORBID_REUSE => 1,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_POSTFIELDS => http_build_query($data, '', "&")
        );
        $ch = curl_init();
        curl_setopt_array($ch, ($options + $defaults));
        $result = curl_exec($ch);
        curl_close($ch);
//          echo "Result";  print_r($result);exit;
        if ($result) {
            return json_decode($result);
        } else {
            return false;
        }
    }

    public function getProduct($id) {
        $query = $this->db->query("select p.*,m.name as manufacturer, (SELECT keyword FROM " . DB_PREFIX . "url_alias WHERE query = 'product_id=" . (int) $id . "' limit 0,1) AS seo_keyword from " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "manufacturer m ON (m.manufacturer_id=p.manufacturer_id) where p.product_id='$id'");
        if ($query->num_rows) {
            return $query->row;
        }
    }

    public function getCategoryPath($category_id, $language_id = 0) {
                
        if (!$language_id) {
            $language_id = $this->config->get('config_language_id');
        }

        $query = $this->db->query("SELECT name, parent_id FROM " . DB_PREFIX . "category c LEFT JOIN " . DB_PREFIX . "category_description cd ON (c.category_id = cd.category_id) WHERE c.category_id = '" . (int) $category_id . "' AND cd.language_id = '" . (int) $language_id . "' ORDER BY c.sort_order, cd.name ASC");

        if (empty($query->row)) {
            return '';
        }

        if ($query->row['parent_id']) {
            return $this->getCategoryPath($query->row['parent_id'], $language_id) . ' > ' . $query->row['name'];
        } else {
            return $query->row['name'];
        }
    }

    public function getIDs($start, $limit) {
        $query = $this->db->query("select p.product_id as id from " . DB_PREFIX . "product p left join " . DB_PREFIX . "product_description pd on (p.product_id=pd.product_id) limit $start, $limit");
        return $query->rows;
    }

    public function getProductOptionDesc($product_id, $option_id, $option_value_id) {
        $query = $this->db->query("SELECT l.code,description FROM `" . DB_PREFIX . "product_option_value_description` ovd LEFT JOIN `" . DB_PREFIX . "language` l ON (ovd.language_id=l.language_id) WHERE `product_id` ='" . $product_id . "' AND option_id='" . $option_id . "' AND option_value_id = '" . $option_value_id . "'");
        if ($query->num_rows) {
            return $query->rows;
        }
        return false;
    }

    public function getOptionDescriptionByLanguage($option_id, $language_id) {
        $query = $this->db->query("SELECT name FROM `" . DB_PREFIX . "option_description` where option_id='" . $option_id . "' AND language_id='" . $language_id . "'");
        if ($query->num_rows)
            return $query->row['name'];
        return false;
    }

    public function getOptionValueDescriptionByLanguage($option_id, $language_id) {
        $query = $this->db->query("SELECT name FROM `" . DB_PREFIX . "option_value_description` where option_id='" . $option_id . "' AND language_id='" . $language_id . "'");
        if ($query->num_rows)
            return $query->row['name'];
        return false;
    }

    public function strip($str, $chars) {
        $str = trim($str);

        if (empty($chars)) {
            return $str;
        }

        if (!is_array($chars)) {
            $chars = array($chars);
        }

        $pat = array();
        $rep = array();
        foreach ($chars as $char) {
            $pat[] = "/(" . preg_quote($char, '/') . ")*$/";
            $rep[] = '';
            $pat[] = "/^(" . preg_quote($char, '/') . ")*/";
            $rep[] = '';
        }

        $res = preg_replace($pat, $rep, $str);

        return $res;
    }

    public function getManufacture($mid) {
        $query = $this->db->query("select name from " . DB_PREFIX . "manufacturer where manufacturer_id = '" . $mid . "'");
        if ($query->row)
            return $query->row['name'];
        else
            return '';
    }

    public function getKeyword($id) {
        $query = $this->db->query("select keyword from " . DB_PREFIX . "url_alias where query = '" . 'product_id=' . $id . "'");
        if ($query->row)
            return $query->row['keyword'];
        else
            return '';
    }

    public function saveCategory($category_chain,$languages) {

        if (empty($category_chain)) {
            return false;
        }

        $category_chain = $this->strip($category_chain, '>');
        $category_names = explode('>', $category_chain);
//		print_r($category_names); exit;
        $categories = array();

        $parent_id = 0;
        $category_id = 0;
        $i = 1;
        $levels = count($category_names);

        foreach ($category_names as $ck => $cv) {

            $cv = trim($cv);

            $new_category = false;
            $queries[] = $sql = "SELECT c.category_id FROM " . DB_PREFIX . "category_description cd
				INNER JOIN " . DB_PREFIX . "category c ON cd.category_id=c.category_id
				WHERE language_id = '" . (int) $this->config->get('config_language_id') . "' AND name='" . $this->db->escape($cv) . "' AND parent_id = '$parent_id'";
            $sel = $this->db->query($sql);
            if (!$sel->num_rows) {
                $queries[] = $sql = "INSERT INTO " . DB_PREFIX . "category SET 
					parent_id = '$parent_id',
					status ='1',
					image = '',
					date_modified = NOW(), date_added = NOW()
				";
                $this->db->query($sql);
                $category_id = $this->db->getLastId();
                $is_new = true;

                if($cv){
                    foreach($languages as $language){
                        $queries[] = $sql = 'INSERT INTO ' . DB_PREFIX . 'category_description SET category_id="' . $category_id . '", language_id = ' . (int) $language['language_id'] . ', name="' . $this->db->escape($cv) . '"';
                        $this->db->query($sql);
                    }
                    $queries[] = $sql = "INSERT INTO " . DB_PREFIX . "category_to_store SET category_id = '" . (int) $category_id . "', store_id = '" . 0 . "'";
                    $this->db->query($sql);
                }else{
                    return false;
                }
                
            } else {
                $category_id = $sel->row['category_id'];
            }
            $parent_id = $category_id;

            $i++;
        }
        return $category_id;
    }
    
    public function saveMulCategory($multi_categories,$languages) {
        
        if (empty($multi_categories)) {
            return false;
        }
        foreach($multi_categories as $code=>$lang_cats){
          foreach($lang_cats as $lang_cat){
            $category_chain[$code] = $this->strip($lang_cat, '>');
            $category_names[$code] = explode('>', $category_chain[$code]);
          }
        }

        $categories = array();

        $parent_id = 0;
        $category_id = 0;
        $i = 1;
        $admin_language = $this->config->get('config_admin_language');
        $admin_language_id = $languages[$admin_language]['language_id'];
        
        if(!isset($category_names[$admin_language])){
            foreach($languages as $code=>$lang){
                if(isset($category_names[$code])){
                    $admin_language = $code;
                    $admin_language_id = $lang['language_id'];
                    $to_add = $category_names[$code];
                }
            }
        }else{
            $to_add = $category_names[$admin_language];
        }
        
        foreach ($to_add as $ck => $cv) {
            $cv = trim($cv);
            if($cv == '') return false;
            $new_category = false;
            $queries[] = $sql = "SELECT c.category_id FROM " . DB_PREFIX . "category_description cd
				INNER JOIN " . DB_PREFIX . "category c ON cd.category_id=c.category_id
				WHERE language_id = '" . (int) $admin_language_id . "' AND name='" . $this->db->escape($cv) . "' AND parent_id = '$parent_id'";
            $this->writeLog($sql,'debug');
            $sel = $this->db->query($sql);
            if (!$sel->num_rows) {
                $queries[] = $sql = "INSERT INTO " . DB_PREFIX . "category SET 
					parent_id = '$parent_id',
					status ='1',
					image = '',
					date_modified = NOW(), date_added = NOW()
				";
                $this->db->query($sql);
                $this->writeLog($sql,'debug');
                $category_id = $this->db->getLastId();
                $is_new = true;

                if($cv){
                    foreach($languages as $language){
                        $cat_value = (isset($category_names[$language['code']][$ck]))?$category_names[$language['code']][$ck]:$cv;
                        $cat_value = trim($cat_value);
                        $queries[] = $sql = 'INSERT INTO ' . DB_PREFIX . 'category_description SET category_id="' . $category_id . '", language_id = ' . (int) $language['language_id'] . ', name="' . $this->db->escape($cat_value) . '"';
                        $this->db->query($sql);
                        $this->writeLog($sql,'debug');
                    }
                    $queries[] = $sql = "INSERT INTO " . DB_PREFIX . "category_to_store SET category_id = '" . (int) $category_id . "', store_id = '" . 0 . "'";
                    $this->db->query($sql);
                    $this->writeLog($sql,'debug');
                }else{
                    return false;
                }
                
            } else {
                $category_id = $sel->row['category_id'];
            }
            $parent_id = $category_id;

            $i++;
        }
       
        return $category_id;
    }

    public function saveManufacurer($name) {
        $sel = $this->db->query("SELECT manufacturer_id FROM " . DB_PREFIX . "manufacturer AS m WHERE name='" . $this->db->escape($name) . "'");
        if (empty($sel->row['manufacturer_id'])) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "manufacturer set name = '" . $name . "'");
            $manufacturer_id = $this->db->getLastId();
            $this->db->query("INSERT INTO " . DB_PREFIX . "manufacturer_to_store set manufacturer_id = '" . $manufacturer_id . "', store_id = '0'");
        } else {
            $manufacturer_id = $sel->row['manufacturer_id'];
        }
        return $manufacturer_id;
    }

    public function getSpreadsheetData($sheet_id='', $setting_id='') {
        return $this->call(array('action' => 'importnew', 'setting_id' => $setting_id, 'sheet_id' => $sheet_id));
    }

    public function getSpreadsheetOptions($sheet_id='', $setting_id='') {
        return $this->call(array('action' => 'importoptions', 'setting_id' => $setting_id, 'sheet_id' => $sheet_id));
    }

    public function updateSetProductId($data, $sheet_id) {
        return $this->call(array('action' => 'updateIds', 'id' => $sheet_id, 'ids' => $data));
    }

    public function updateSetOptionId($data, $sheet_id) {
        return $this->call(array('action' => 'updateoptIds', 'id' => $sheet_id, 'ids' => $data));
    }

    public function getPaths($language_id) {
        $sql = "SELECT cp.category_id AS category_id, GROUP_CONCAT(cd1.name ORDER BY cp.level SEPARATOR ' > ') AS name FROM " . DB_PREFIX . "category_path cp LEFT JOIN " . DB_PREFIX . "category c ON (cp.path_id = c.category_id) LEFT JOIN " . DB_PREFIX . "category_description cd1 ON (c.category_id = cd1.category_id) LEFT JOIN " . DB_PREFIX . "category_description cd2 ON (cp.category_id = cd2.category_id) WHERE cd1.language_id = '" . (int) $language_id . "' AND cd2.language_id = '" . (int) $language_id . "'";
        $sql .= " GROUP BY cp.category_id ORDER BY name";
        $query = $this->db->query($sql);
        $category_path = array();
        if ($query->num_rows){
           foreach($query->rows as $cat){
               $category_path[html_entity_decode($cat['name'])] = $cat['category_id'];
           }
        }
        return $category_path;
    }

    public function fetchLanguageCategory(){
        
        $languages = $this->getLanguagesByCode();
        $cats = array();
        foreach($languages as $language){
        
            $sql = "
			  SELECT 
				cp.category_id AS category_id, 
				c.image, 
				c.top,
				c.sort_order,
				c.status, 
				cd2.name,
				cd2.description,
				cd2.meta_description,
				cd2.meta_keyword, 
				GROUP_CONCAT(cd1.name ORDER BY cp.level SEPARATOR '   > ') AS path, 
				c.parent_id, 
				c.sort_order 
			  FROM " . DB_PREFIX . "category_path cp 
			  LEFT JOIN " . DB_PREFIX . "category c ON (cp.path_id = c.category_id) 
			  LEFT JOIN " . DB_PREFIX . "category_description cd1 ON (c.category_id = cd1.category_id) 
			  LEFT JOIN " . DB_PREFIX . "category_description cd2 ON (cp.category_id = cd2.category_id) 
			  LEFT JOIN ".DB_PREFIX."language l ON (cd1.language_id=l.language_id) 
			  
			  WHERE cd1.language_id = '" .$language['language_id']. "' AND cd2.language_id = '" .$language['language_id']. "'";
            $sql .= " 
			  GROUP BY cp.category_id 
			  
			  ORDER BY path";
			
			$query = $this->db->query($sql);
            
            if ($query->num_rows)
                $cats[$language['code']] = $query->rows;
         }
         return $cats;
    }
    
    public function getCategoryKeyword($category_id) {
        $query = $this->db->query("SELECT keyword as seo_keyword FROM " . DB_PREFIX . "url_alias WHERE query = 'category_id=" . (int)$category_id . "'");
        return $query->row['keyword'];
    } 
    
    public function addProduct($data) {
//        print_r($data); exit;
        $product = $data['product'];
        if(empty($product['model']) && !isset($data['product_description'][$this->config->get('config_language_id')]))
            return false;
        $this->db->query("INSERT INTO " . DB_PREFIX . "product SET model = '" . $this->db->escape($product['model']) . "', sku = '" . $this->db->escape($product['sku']) . "', upc = '" . $this->db->escape($product['upc']) . "', ean = '" . $this->db->escape($product['ean']) . "', jan = '" . $this->db->escape($product['jan']) . "', isbn = '" . $this->db->escape($product['isbn']) . "', mpn = '" . $this->db->escape($product['mpn']) . "', location = '" . $this->db->escape($product['location']) . "', quantity = '" . (int) $product['quantity'] . "', minimum = '" . (int) $product['minimum'] . "', subtract = '" . (int) $product['subtract'] . "', stock_status_id = '" . (int) $product['stock_status_id'] . "', date_available = '" . $this->db->escape($product['date_available']) . "', manufacturer_id = '" . (int) $product['manufacturer_id'] . "', shipping = '" . (int) $product['shipping'] . "', price = '" . (float) $product['price'] . "', points = '" . (int) $product['points'] . "', weight = '" . (float) $product['weight'] . "', weight_class_id = '" . (int) $product['weight_class_id'] . "', length = '" . (float) $product['length'] . "', width = '" . (float) $product['width'] . "', height = '" . (float) $product['height'] . "', length_class_id = '" . (int) $product['length_class_id'] . "', status = '" . (int) $product['status'] . "', tax_class_id = '" . $this->db->escape($product['tax_class_id']) . "', sort_order = '" . (int) $product['sort_order'] . "', date_added = NOW()");

        $product_id = $this->db->getLastId();

        if (isset($product['image'])) {
            $this->db->query("UPDATE " . DB_PREFIX . "product SET image = '" . $this->db->escape(html_entity_decode($product['image'], ENT_QUOTES, 'UTF-8')) . "' WHERE product_id = '" . (int) $product_id . "'");
        }

        foreach ($data['product_description'] as $language_id => $value) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "product_description SET product_id = '" . (int) $product_id . "', language_id = '" . (int) $language_id . "', name = '" . $this->db->escape(htmlentities($value['name'], ENT_QUOTES, "UTF-8")) . "', meta_keyword = '" . $this->db->escape(htmlentities($value['meta_keyword'], ENT_QUOTES, "UTF-8")) . "', meta_description = '" . $this->db->escape(htmlentities($value['meta_description'], ENT_QUOTES, "UTF-8")) . "', description = '" . $this->db->escape(htmlentities($value['description'], ENT_QUOTES, "UTF-8")) . "', tag = '" . $this->db->escape(htmlentities($value['tag'], ENT_QUOTES, "UTF-8")) . "'");
        }

        if (isset($data['product_store'])) {
            foreach ($data['product_store'] as $store_id) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_to_store SET product_id = '" . (int) $product_id . "', store_id = '" . (int) $store_id . "'");
            }
        }

        if (isset($data['product_attribute'])) {
            foreach ($data['product_attribute'] as $product_attribute) {
                if ($product_attribute['attribute_id']) {
                    $this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int) $product_id . "' AND attribute_id = '" . (int) $product_attribute['attribute_id'] . "'");
                    foreach ($product_attribute['product_attribute_description'] as $language_id => $product_attribute_description) {
                        if (!empty($product_attribute_description['text']))
                            $this->db->query("INSERT INTO " . DB_PREFIX . "product_attribute SET product_id = '" . (int) $product_id . "', attribute_id = '" . (int) $product_attribute['attribute_id'] . "', language_id = '" . (int) $language_id . "', text = '" . $this->db->escape($product_attribute_description['text']) . "'");
                    }
                }
            }
        }

        if(isset($data['product_option'])){
            foreach($data['product_option'] as $option){
                $this->saveOption($option,$product_id);
            }
        }

        if (isset($data['product_discount'])) {
            foreach ($data['product_discount'] as $product_discount) {
                if ($product_discount['price'])
                    $this->db->query("INSERT INTO " . DB_PREFIX . "product_discount SET product_id = '" . (int) $product_id . "', customer_group_id = '" . (int) $product_discount['customer_group_id'] . "', quantity = '" . (int) $product_discount['quantity'] . "', priority = '" . (int) $product_discount['priority'] . "', price = '" . (float) $product_discount['price'] . "', date_start = '" . $this->db->escape($product_discount['date_start']) . "', date_end = '" . $this->db->escape($product_discount['date_end']) . "'");
            }
        }

        if (isset($data['product_special'])) {
            foreach ($data['product_special'] as $product_special) {
                if ($product_special['price'])
                    $this->db->query("INSERT INTO " . DB_PREFIX . "product_special SET product_id = '" . (int) $product_id . "', customer_group_id = '" . (int) $product_special['customer_group_id'] . "', priority = '" . (int) $product_special['priority'] . "', price = '" . (float) $product_special['price'] . "', date_start = '" . $this->db->escape($product_special['date_start']) . "', date_end = '" . $this->db->escape($product_special['date_end']) . "'");
            }
        }

        if (isset($data['product_image'])) {
            foreach ($data['product_image'] as $product_image) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_image SET product_id = '" . (int) $product_id . "', image = '" . $this->db->escape(html_entity_decode($product_image['image'], ENT_QUOTES, 'UTF-8')) . "', sort_order = '" . (int) $product_image['sort_order'] . "'");
            }
        }

        if (isset($data['product_download'])) {
            foreach ($data['product_download'] as $download_id) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_to_download SET product_id = '" . (int) $product_id . "', download_id = '" . (int) $download_id . "'");
            }
        }

        if (isset($data['product_category'])) {
            foreach ($data['product_category'] as $category_id) {
                $this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "product_to_category SET product_id = '" . (int) $product_id . "', category_id = '" . (int) $category_id . "'");
            }
        }

        if (isset($data['product_filter'])) {
            foreach ($data['product_filter'] as $filter_id) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_filter SET product_id = '" . (int) $product_id . "', filter_id = '" . (int) $filter_id . "'");
            }
        }

        if (isset($data['product_related'])) {
            foreach ($data['product_related'] as $related_id) {
                $this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int) $product_id . "' AND related_id = '" . (int) $related_id . "'");
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_related SET product_id = '" . (int) $product_id . "', related_id = '" . (int) $related_id . "'");
                $this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int) $related_id . "' AND related_id = '" . (int) $product_id . "'");
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_related SET product_id = '" . (int) $related_id . "', related_id = '" . (int) $product_id . "'");
            }
        }

        if (isset($data['product_reward'])) {
            foreach ($data['product_reward'] as $customer_group_id => $product_reward) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_reward SET product_id = '" . (int) $product_id . "', customer_group_id = '" . (int) $customer_group_id . "', points = '" . (int) $product_reward['points'] . "'");
            }
        }

        if (isset($data['product_layout'])) {
            foreach ($data['product_layout'] as $store_id => $layout) {
                if ($layout['layout_id']) {
                    $this->db->query("INSERT INTO " . DB_PREFIX . "product_to_layout SET product_id = '" . (int) $product_id . "', store_id = '" . (int) $store_id . "', layout_id = '" . (int) $layout['layout_id'] . "'");
                }
            }
        }

        if ($product['keyword']) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "url_alias SET query = 'product_id=" . (int) $product_id . "', keyword = '" . $this->db->escape($product['keyword']) . "'");
        }

        if (isset($data['product_profiles'])) {
            foreach ($data['product_profiles'] as $profile) {
                $this->db->query("INSERT INTO `" . DB_PREFIX . "product_profile` SET `product_id` = " . (int) $product_id . ", customer_group_id = " . (int) $profile['customer_group_id'] . ", `profile_id` = " . (int) $profile['profile_id']);
            }
        }

        $this->cache->delete('product');

        return $product_id;
    }

    public function editProduct($product_id,$data) { //print_r($data); exit;
        $queries = array();
        if (isset($data['product'])) {
            $sql = 'UPDATE `' . DB_PREFIX . 'product` SET ';
            foreach ($data['product'] as $field => $value) { if(!isset($this->pf[$field])) continue;
                $sql .= ' ' . $field . '="' . $this->db->escape($value) . '",';
            }
            $sql = trim($sql, ',');
            $sql .= ' where product_id="' . $product_id . '"';
//            echo $sql; exit;
//            $queries[] = $sql;
           
            $this->db->query($sql);
        }
        if (isset($data['product_description'])){
            foreach($data['product_description'] as $language_id => $row) {
                
                $query = $this->db->query('select product_id from `' . DB_PREFIX . 'product_description` where product_id="' . $product_id . '" AND language_id="' . $language_id . '"');
                if($query->num_rows){
                    $sql = 'UPDATE `' . DB_PREFIX . 'product_description` SET ';
                    foreach ($row as $field => $value) { $sql .= ' ' . $field . '="' . $this->db->escape(htmlentities($value, ENT_QUOTES, "UTF-8")) . '",'; }
                    $sql = trim($sql, ',');
                    $sql .= ' where product_id="' . $product_id . '" AND language_id="' . $language_id . '"';
                }else{
                    $sql = 'INSERT INTO `' . DB_PREFIX . 'product_description` SET product_id="' . $product_id . '", language_id="' . $language_id . '", ';
                    foreach($row as $field => $value) { $sql .= ' ' . $field . '="' . $this->db->escape(htmlentities($value, ENT_QUOTES, "UTF-8")) . '",'; }
                    $sql = trim($sql, ',');
                }
                $queries[] = $sql;
                $this->db->query($sql);
            }
        }

        if (isset($data['product_image'])) {
            $queries[] = $sql = "DELETE FROM " . DB_PREFIX . "product_image WHERE product_id = '" . (int) $product_id . "'";
            $this->db->query($sql);
            if($data['product_image']){
                foreach ($data['product_image'] as $product_image) {
                    if($product_image['image']){
                        $queries[] = $sql = "INSERT INTO " . DB_PREFIX . "product_image SET product_id = '" . (int) $product_id . "', image = '" . $this->db->escape(html_entity_decode($product_image['image'], ENT_QUOTES, 'UTF-8')) . "', sort_order = '" . (int) $product_image['sort_order'] . "'";
                        $this->db->query($sql);
                    }
                }
            }
        }
        

        if (isset($data['product_category'])) {
            $queries[] = $sql = "DELETE FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int) $product_id . "'";
            $this->db->query($sql);
            foreach ($data['product_category'] as $category_id) {
                $queries[] = $sql = "INSERT IGNORE INTO " . DB_PREFIX . "product_to_category SET product_id = '" . (int) $product_id . "', category_id = '" . (int) $category_id . "'";
                $this->db->query($sql);
            }
        }
 
        if (isset($data['product_discount'])) {
            $queries[]= $sql = "DELETE FROM " . DB_PREFIX . "product_discount WHERE product_id = '" . (int) $product_id . "'";
            $this->db->query($sql);
            foreach ($data['product_discount'] as $product_discount) {
                if ($product_discount['price']){
                    $queries[] = $sql = "INSERT INTO " . DB_PREFIX . "product_discount SET product_id = '" . (int) $product_id . "', customer_group_id = '" . (int) $product_discount['customer_group_id'] . "', quantity = '" . (int) $product_discount['quantity'] . "', priority = '" . (int) $product_discount['priority'] . "', price = '" . (float) $product_discount['price'] . "', date_start = '" . $this->db->escape($product_discount['date_start']) . "', date_end = '" . $this->db->escape($product_discount['date_end']) . "'";
                    $this->db->query($sql);
                }
            }
        }

        if (isset($data['product_special'])) {
            $queries[] = $sql =  "DELETE FROM " . DB_PREFIX . "product_special WHERE product_id = '" . (int) $product_id . "'";
            $this->db->query($sql);
            foreach($data['product_special'] as $product_special){
                if ($product_special['price']){
                    $queries[] = $sql = "INSERT INTO " . DB_PREFIX . "product_special SET product_id = '" . (int) $product_id . "', customer_group_id = '" . (int) $product_special['customer_group_id'] . "', priority = '" . (int) $product_special['priority'] . "', price = '" . (float) $product_special['price'] . "', date_start = '" . $this->db->escape($product_special['date_start']) . "', date_end = '" . $this->db->escape($product_special['date_end']) . "'";
                    $this->db->query($sql);
                }
            }
        }
        if (!empty($data['product_attribute'])) { 
//            $queries[] = "DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int) $product_id . "'";
            foreach ($data['product_attribute'] as $product_attribute) {
                if ($product_attribute['attribute_id']) {
                    foreach ($product_attribute['product_attribute_description'] as $language_id => $product_attribute_description) {
                        $queries[] = $sql = "DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int) $product_id . "' AND attribute_id = '" . (int) $product_attribute['attribute_id'] . "' AND language_id = '" . (int) $language_id . "'";
                        $this->db->query($sql);
                        if (!empty($product_attribute_description['text'])){
                            $queries[] = $sql = "INSERT INTO " . DB_PREFIX . "product_attribute SET product_id = '" . (int) $product_id . "', attribute_id = '" . (int) $product_attribute['attribute_id'] . "', language_id = '" . (int) $language_id . "', text = '" . $this->db->escape($product_attribute_description['text']) . "'";
                            $this->db->query($sql);
                        }
                    }
                }
            }
        }
        
        if (isset($data['product']['keyword'])){
            $queries[] = $sql = "DELETE FROM " . DB_PREFIX . "url_alias WHERE query = 'product_id=" . (int)$product_id. "'";
            $this->db->query($sql);
            if(!empty($data['product']['keyword'])){
                $queries[] = $sql = "INSERT INTO " . DB_PREFIX . "url_alias SET query = 'product_id=" . (int) $product_id . "', keyword = '" . $this->db->escape($data['product']['keyword']) . "'";
            }
            $this->db->query($sql);
        }
        foreach ($queries as $query){
            $this->writeLog($query,'sql');
        }
        
        if(isset($data['product_related'])){
            $this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int)$product_id . "'");
//            $this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE related_id = '" . (int)$product_id . "'");
            if (isset($data['product_related'])) {
                foreach ($data['product_related'] as $related_id) {
                    $this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int)$product_id . "' AND related_id = '" . (int)$related_id . "'");
                    $this->db->query("INSERT INTO " . DB_PREFIX . "product_related SET product_id = '" . (int)$product_id . "', related_id = '" . (int)$related_id . "'");
//                    $this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int)$related_id . "' AND related_id = '" . (int)$product_id . "'");
//                    $this->db->query("INSERT INTO " . DB_PREFIX . "product_related SET product_id = '" . (int)$related_id . "', related_id = '" . (int)$product_id . "'");
                }
            }
        }

        if(isset($data['product_store'])) {
             $this->db->query("DELETE FROM " . DB_PREFIX . "product_to_store WHERE product_id = '" . (int)$product_id . "'");
	          foreach ($data['product_store'] as $store_id) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_store SET product_id = '" . (int)$product_id . "', store_id = '" . (int)$store_id . "'");
		  }
        }
        
        if(isset($data['product_option'])){
            $this->db->query("DELETE FROM " . DB_PREFIX . "product_option WHERE product_id = '" . (int)$product_id . "'");
            $this->db->query("DELETE FROM " . DB_PREFIX . "product_option_value WHERE product_id = '" . (int)$product_id . "'");
            
            foreach($data['product_option'] as $option){
                $this->saveOption($option,$product_id);
            }
        }
    }

    public function getOptions($setting_id=17, $sheet_id = 14) {
        $queries = array();
        $option_data = array();
        $options = $this->getSpreadsheetOptions($sheet_id, $setting_id);
        foreach ($options as $key => $option) {
            $options[$key]->rowid = $key + 2;
        }

        foreach ($options as $option) {
            $queries[] = "delete from " . DB_PREFIX . "product_option where product_id='" . $option->productid . "'";
            $queries[] = "delete from " . DB_PREFIX . "product_option_value where product_id='" . $option->productid . "'";
        }

        foreach ($options as $option) {
            $product_id = $option->productid;
            $option_id = $this->getOptionIdByName($option->optionname);
            $option_value_id = $this->getOptionValueIdByName($option->optionvalue);

            if ($product_id && $option_id && $option_value_id) {
                if ($id = $this->checkExistingProductOption($product_id, $option_id)) {
                    $product_option_id = $id;
                } else {
                    $query[] = "insert into " . DB_PREFIX . "product_option  (product_id,option_id,required) value('" . $product_id . "','" . $option_id . "','1')";
                    $product_option_id = $this->db->getLastId();
                }
                $queries[] = "insert into " . DB_PREFIX . "product_option_value SET product_option_id='" . $product_option_id . "', product_id='" . $product_id . "', option_id='" . $option_id . "', option_value_id='" . $option_value_id . "'";
                $product_option_value_id = $this->db->getLastId();
            }

            $option_data[$option->productid] = $option;
        }
    }

    public function getOptionIdByName($option) {
        $query = $this->db->query("select o.option_id from `" . DB_PREFIX . "option` o LEFT JOIN " . DB_PREFIX . "option_description od ON (o.option_id=od.option_id) where od.name = '" . $this->db->escape($option) . "'");
        if ($query->num_rows) {
            return $query->row['option_id'];
        }
        return false;
    }

    public function getOptionValueIdByName($option) {
        $query = $this->db->query("select ov.option_value_id from " . DB_PREFIX . "option_value ov LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (ov.option_value_id=ovd.option_value_id) where ovd.name = '" . $this->db->escape(htmlentities($option)) . "'");
        if ($query->num_rows) {
            return $query->row['option_value_id'];
        }
        return false;
    }

    public function checkExistingProductOption($product_id, $option_id) {
        $query = $this->db->query("select * from " . DB_PREFIX . "product_option where product_id='" . $product_id . "' AND option_id='" . $option_id . "'");
        if ($query->num_rows) {
            return $query->row['product_option_id'];
        }
        return false;
    }

    public function writeLog($message, $op='log') {
        $file = DIR_LOGS . 'google_spreadsheet_' . $op . '.txt';

        $handle = fopen($file, 'a+');

        fwrite($handle, date('Y-m-d G:i:s') . ' - ' . $message . "\n");

        fclose($handle);
    }

    public function updateProductOption($setting_id, $sheet_id) {
        $options = $this->getSpreadsheetOptions($sheet_id, $setting_id);
        
        foreach ($options as $option) {
            $this->saveOption($option, $language_id);
        }
    }

    public function saveOption($option, $product_id){
       
        if(is_array($option))
            $option = (object)$option;
        
        $language_id = $this->config->get('config_language_id');
    
        
        if (!$product_id && !is_numeric($product_id))
            return false;

        $extended_types = array('select', 'radio', 'checkbox', 'image');
        $option_types = array('select', 'radio', 'checkbox', 'image', 'text', 'textarea', 'file', 'date', 'time', 'datetime');

//        $option->required = ($option->required == 'Yes' || $option->required == 'yes' || $option->required == 'Y' || $option->required == 1) ? 1 : 0;
        $is_new = false;
        $data = array();

        // validate parameters
        //
		if (!in_array($option->type, $option_types)) {
                    $this->writeLog("Invalid option type - $option->type");
                    return false;
                }

        // STAGE 1: find the option in the store
        //
        $qry = $this->db->query("SELECT o.option_id FROM `" . DB_PREFIX . "option` o INNER JOIN " . DB_PREFIX . "option_description od ON o.option_id = od.option_id WHERE language_id = '" . $language_id . "' AND  od.name='$option->name'");

        if (empty($qry->row)) {
            // if the option is NOT found
            //
			
            $this->db->query("insert into `" . DB_PREFIX . "option` set type='" . $option->type . "'");
            $option_id = $this->db->getLastId();

            $is_new = true;

            $this->db->query("insert into " . DB_PREFIX . "option_description set option_id='$option_id', language_id='" . $language_id . "', name='" . $this->db->escape($option->name) . "'");
            $this->writeLog("New option created - $option->name");

            // repeat option request
            //
	    $qry = $this->db->query("SELECT o.option_id FROM `" . DB_PREFIX . "option` o INNER JOIN " . DB_PREFIX . "option_description od ON o.option_id = od.option_id WHERE language_id = '" . $language_id . "' AND o.type='$option->type' AND od.name='$option->name'");
        }

        //
        // STAGE 2: option found/created and we are going to assing it to a product
        //		
        $option_id = $option->option_id = $qry->row['option_id'];

        /*
          There are two option types in Opencart:
          simple   - user enters a custom value manually
          extended - options with predefined values
         */
        $extended = false;
        if (in_array($option->type, $extended_types)) {
            $extended = true;
        }

        // find product option id or insert a new one
        //
	$qry = $this->db->query("SELECT product_option_id FROM " . DB_PREFIX . "product_option WHERE product_id='$product_id' AND option_id='$option->option_id'");

        if (empty($qry->row['product_option_id'])) {
            $this->db->query("insert into " . DB_PREFIX . "product_option set product_id='" . $product_id . "',option_id='" . $option->option_id . "', required='" . $option->required . "'");
            $product_option_id = $this->db->getLastId();
        } else {
            $product_option_id = $qry->row['product_option_id'];
            $this->db->query("update " . DB_PREFIX . "product_option set required='" . $option->required . "' where product_option_id = '$product_option_id'");
        }

        if ($extended) {

            // find option value or insert a new one
            //
			$qry = $this->db->query("SELECT option_value_id FROM " . DB_PREFIX . "option_value_description WHERE 
				option_id = '" . $option_id . "'
				AND language_id = '" . $language_id . "'
				AND name='" . $this->db->escape($option->option_value) . "'");



            if (empty($qry->row['option_value_id'])) {

                $this->db->query("insert into " . DB_PREFIX . "option_value set option_id='" . $option->option_id . "'");
                $option_value_id = $this->db->getLastId();

                $this->db->query("insert into " . DB_PREFIX . "option_value_description set option_id='" . $option->option_id . "', option_value_id='" . $option_value_id . "', language_id='" . $language_id . "', name='" . $this->db->escape($option->option_value) . "'");
            } else {
                $option_value_id = $qry->row['option_value_id'];
            }

            // assign option value for product
            //
     		$this->db->query("DELETE FROM " . DB_PREFIX . "product_option_value WHERE product_option_id='$product_option_id' AND option_value_id='$option_value_id'");

            $rec = array(
                'product_option_id' => $product_option_id,
                'product_id' => $product_id,
                'option_id' => $option_id,
                'option_value_id' => $option_value_id,
                'quantity' => $option->quantity,
                'subtract' => $option->subtract,
                'price' => abs($option->price),
                'price_prefix' => ($option->price < 0 ? '-' : '+'),
                'points' => abs($option->point),
                'points_prefix' => ($option->point < 0 ? '-' : '+'),
                'weight' => abs($option->weight),
                'weight_prefix' => ($option->weight < 0 ? '-' : '+'),
            );

            $sql = "insert into " . DB_PREFIX . "product_option_value set";

            foreach ($rec as $key => $val) {
                $sql .= " " . $key . "='" . $val . "',";
            }
            $sql = trim($sql, ',');
            $this->db->query($sql);
            $product_option_value_id = $this->db->getLastId();
        } else {
            $this->db->query("update " . DB_PREFIX . "product_option set required='" . $option->required . "', value='" . $option->option_value . "' where product_option_id='$product_option_id'");
        }
        return true;
    }

    public function clean($string) {
        $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
        return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
    }
    
    public function getProductStore($product_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "store s right join `" . DB_PREFIX . "product_to_store` p2s ON (p2s.store_id=s.store_id) where p2s.product_id='$product_id'");
        if ($query->num_rows) {
            return $query->rows;
        }
    }
    
    public function getProductWeightName($weight_id,$output){
        $query=$this->db->query("select title,unit from ".DB_PREFIX."weight_class_description where weight_class_id='$weight_id'");
        if($query->num_rows){
            return $query->row[$output];
        }
    }
    
    public function getProductWeightId($name,$key){
        $query=$this->db->query("select weight_class_id from ".DB_PREFIX."weight_class_description where {$key}='$name'");
        if($query->num_rows){
            return $query->row['weight_class_id'];
        }
    }
    
    public function getProductLengthName($length_id,$output){
        $query=$this->db->query("select title,unit from ".DB_PREFIX."length_class_description where length_class_id='$length_id'");
        if($query->num_rows){
            return $query->row[$output];
        }
    }
    
    public function getProductLengthId($name,$key){
        $query=$this->db->query("select length_class_id from ".DB_PREFIX."length_class_description where {$key}='$name'");
        if($query->num_rows){
            return $query->row['length_class_id'];
        }
    }
    
    public function getProductTaxName($tax_id){
        $query=$this->db->query("select title from ".DB_PREFIX."tax_class where tax_class_id='$tax_id'");
        if($query->num_rows){
            return $query->row['title'];
        }
    }
    
    public function getProductTaxId($title){
        $query=$this->db->query("select tax_class_id from ".DB_PREFIX."tax_class where title='$title'");
        if($query->num_rows){
            return $query->row['tax_class_id'];
        }
    }
    
    public function getProductStock($id){
        $query=$this->db->query("select name from ".DB_PREFIX."stock_status where stock_status_id='$id'");
        if($query->num_rows){
            return $query->row['name'];
        }
    }
    
    public function getProductStockId($name){
        $query=$this->db->query("select stock_status_id from ".DB_PREFIX."stock_status where name='$name'");
        if($query->num_rows){
            return $query->row['stock_status_id'];
        }
    }
    
    public function getImportRowsCount($setting_id,$sheet_id){
        $this->load->model('feed/google_spreadsheet');
        $result = $this->model_feed_google_spreadsheet->call(
                array(
                    'action'=>'listfeed',
                    'setting_id' => $setting_id, 
                    'sheet_id' => $sheet_id,
                    'wskey'=>'General',
                    'query'=>'rowid != ""',
                    'count'=>true)
                );
    }
    
    public function prepareCategory($data){
        $category = array();
        $category['category_store'][0] = 0;
        $languages = $this->getLanguagesByCode();
        foreach($data as $key=>$value){
            switch ($key){
                case (preg_match('/^name.*/', $key) ? true : false):
                    $name_arr = explode('_', $key);
                    $language = end($name_arr);
                    $language_id = isset($languages[$language]['language_id']) ? $languages[$language]['language_id'] : '0';
                    $category['category_description'][$language_id]['name'] = $value;
                    break;
                case (preg_match('/^description.*/', $key) ? true : false) :
                    $desc_arr = explode('_', $key);
                    $language = end($desc_arr);
                    $language_id = isset($languages[$language]['language_id']) ? $languages[$language]['language_id'] : '0';
                    $category['category_description'][$language_id]['description'] = $value;
                    break;
                case (preg_match('/^meta_key.*/', $key) ? true : false) :
                    $meta_text = explode('_', $key);
                    $language = end($meta_text);
                    $language_id = isset($languages[$language]['language_id']) ? $languages[$language]['language_id'] : '0';
                    $category['category_description'][$language_id]['meta_keyword'] = $value;
                    break;
                case (preg_match('/^meta_desc.*/', $key) ? true : false) :
                    $meta_text = explode('_', $key);
                    $language = end($meta_text);
                    $language_id = isset($languages[$language]['language_id']) ? $languages[$language]['language_id'] : '0';
                    $category['category_description'][$language_id]['meta_description'] = $value;
                    break;
                case (preg_match('/^seo.*/', $key) ? true : false) :
                    $category['keyword'] = $value;
                    break;
                case (preg_match('/^path.*/', $key) ? true : false) :
                    $path = explode(' > ', $value);
                    if(count($path) > 1){
                        end($path);
                        $category['parent_id'] = $this->findCategoryName(prev($path));
                    }else $category['parent_id']= 0;
                    $category['path'] = htmlentities($value);
                    break;
                case (preg_match('/^level.*/', $key) ? true : false) :
                    break;
                default:
                    $category[$key] = $value;
            }
        }
        return $category;
    }
    
    public function findCategoryName($name){
        $query = $this->db->query("select category_id from " . DB_PREFIX . "category_description where name like '{$name}'");
        if($query->num_rows){
            return $query->row['category_id'];
        }
        return 0;
    }
    
    public function addCategory($data) {
       
        $this->writeLog(print_r($data,true),'cat');
       
        $sql = "INSERT INTO " . DB_PREFIX . "category SET parent_id = '" . (int)$data['parent_id'] . "',";
        if(isset($data['top'])){ $sql .= "`top` = '" . (isset($data['top']) ? (int)$data['top'] : 0) . "', ";}
        if(isset($data['column'])){$sql .= "`column` = '" . (int)$data['column'] . "', ";}
        if(isset($data['sort_order'])){$sql .= "sort_order = '" . (int)$data['sort_order'] . "', ";}
        if(isset($data['status'])){$sql .= "status = '" . (int)$data['status'] . "', ";}
        
            $sql .= "date_modified = NOW(), "; 
            $sql .= "date_added = NOW()";
            $this->db->query($sql);
            $this->writeLog($sql,'catsql');
            $category_id = $this->db->getLastId();

            if (isset($data['image'])) {
                    $this->db->query("UPDATE " . DB_PREFIX . "category SET image = '" . $this->db->escape(html_entity_decode($data['image'], ENT_QUOTES, 'UTF-8')) . "' WHERE category_id = '" . (int)$category_id . "'");
            }
            $sql = '';
            foreach ($data['category_description'] as $language_id => $value) {
                $sql = "INSERT INTO " . DB_PREFIX . "category_description SET category_id = '" . (int)$category_id . "', language_id = '" . (int)$language_id . "', ";
                        if(isset($value['name'])) $sql .= "name = '" . $this->db->escape($value['name']) . "', ";
                        if(isset($value['meta_keyword'])) $sql .= "meta_keyword = '" . $this->db->escape($value['meta_keyword']) . "',";
                        if(isset($value['meta_description'])) $sql .= "meta_description = '" . $this->db->escape($value['meta_description']) . "', ";
                        if(isset($value['description'])) $sql .= "description = '" . $this->db->escape($value['description']) . "'";
                $this->db->query($sql);
                $this->writeLog($sql,'catsql');
            }
		
            if (isset($data['category_store'])) {
                    foreach ($data['category_store'] as $store_id) {
                            $this->db->query("INSERT INTO " . DB_PREFIX . "category_to_store SET category_id = '" . (int)$category_id . "', store_id = '" . (int)$store_id . "'");
                    }
            }


            if (isset($data['keyword']) && $data['keyword']){
                    $this->db->query("INSERT INTO " . DB_PREFIX . "url_alias SET query = 'category_id=" . (int)$category_id . "', keyword = '" . $this->db->escape($data['keyword']) . "'");
            }
		
            $this->cache->delete('category');
            return $category_id;
	}
        
    public function editCategory($category_id, $data) {
        $this->writeLog(print_r($data,true),'cat');
		$sql = "UPDATE " . DB_PREFIX . "category SET parent_id = '" . (int)$data['parent_id'] . "',";
                if(isset($data['top'])){ $sql .= "`top` = '" . (isset($data['top']) ? (int)$data['top'] : 0) . "', ";}
                if(isset($data['column'])){$sql .= "`column` = '" . (int)$data['column'] . "', ";}
                if(isset($data['sort_order'])){$sql .= "sort_order = '" . (int)$data['sort_order'] . "', ";}
                if(isset($data['status'])){$sql .= "status = '" . (int)$data['status'] . "', ";}

                    $sql .= "date_modified = NOW() where category_id='{$category_id}'"; 
                    $this->db->query($sql);
           $this->writeLog($sql,'catsql');
		if (isset($data['image'])) {
			$this->db->query("UPDATE " . DB_PREFIX . "category SET image = '" . $this->db->escape(html_entity_decode($data['image'], ENT_QUOTES, 'UTF-8')) . "' WHERE category_id = '" . (int)$category_id . "'");
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "category_description WHERE category_id = '" . (int)$category_id . "'");

		foreach ($data['category_description'] as $language_id => $value) {
			$sql = "INSERT INTO " . DB_PREFIX . "category_description SET category_id = '" . (int)$category_id . "', language_id = '" . (int)$language_id . "', ";
                        if(isset($value['name'])) $sql .= "name = '" . $this->db->escape($value['name']) . "', ";
                        if(isset($value['meta_keyword'])) $sql .= "meta_keyword = '" . $this->db->escape($value['meta_keyword']) . "',";
                        if(isset($value['meta_description'])) $sql .= "meta_description = '" . $this->db->escape($value['meta_description']) . "', ";
                        if(isset($value['description'])) $sql .= "description = '" . $this->db->escape($value['description']) . "'";
                        $this->db->query($sql);
		}
		
		
		$this->db->query("DELETE FROM " . DB_PREFIX . "category_to_store WHERE category_id = '" . (int)$category_id . "'");
		
		if (isset($data['category_store'])) {		
			foreach ($data['category_store'] as $store_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "category_to_store SET category_id = '" . (int)$category_id . "', store_id = '" . (int)$store_id . "'");
			}
		}
		
		
		if (isset($data['keyword']) && $data['keyword']){
                    $this->db->query("DELETE FROM " . DB_PREFIX . "url_alias WHERE query = 'category_id=" . (int)$category_id. "'");
                    $this->db->query("INSERT INTO " . DB_PREFIX . "url_alias SET query = 'category_id=" . (int)$category_id . "', keyword = '" . $this->db->escape($data['keyword']) . "'");
		}
		
		$this->cache->delete('category');
	}
        
    public function saveSetting($data,$setting_id=false) {
        if(!$setting_id){
            $query = $this->db->query("insert into " . DB_PREFIX . "gs_settings set title = '" . $this->db->escape($data['title']) . "', settings = '" . $this->db->escape($data['settings']) . "', created = '" . $this->db->escape($data['created']) . "' ");
            $setting_id = $this->db->getLastId();
        }else
            $query = $this->db->query("update " . DB_PREFIX . "gs_settings set title = '" . $this->db->escape($data['title']) . "', settings = '" . $this->db->escape($data['settings']) . "' where id='$setting_id'");
        $data['id'] = $setting_id;
        $this->call('saveSetting',$data);
        
    }
    
    public function getSetting($id) {
        $query = $this->db->query("select * from " . DB_PREFIX . "gs_settings where id = '$id'");
        if ($query->num_rows) {
            return $query->row;
        }
    } 
    
    public function deleteSelected($ids){
        $query = $this->db->query("delete from ".DB_PREFIX."gs_settings where id IN ($ids)");
        $this->call(array('action'=>'deleteSettings','ids'=>$ids));
    }
 
    public function getSettings() {
        $query = $this->db->query("select * from  " . DB_PREFIX . "gs_settings");
        if ($query->num_rows)
            return $query->rows;
    }
    
    public function getProductByModel($model){
        $query = $this->db->query("select product_id from " . DB_PREFIX . "product where model = '{$model}'");
        if($query->num_rows){
            return $query->row['product_id'];
        }
        return false;
    }
    
    public function getProductRelated($product_id) {
        $product_related_data = array();
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int)$product_id . "'");
        foreach ($query->rows as $result) {
                $product_related_data[] = $result['related_id'];
        }
        return $product_related_data;
    }
    
    public function saveCustomer($data){ 
        if(is_numeric($data['customer_id'])){
            $customer_id = $data['customer_id'];
            $this->db->query("UPDATE " . DB_PREFIX . "customer SET firstname = '" . $this->db->escape($data['firstname']) . "', lastname = '" . $this->db->escape($data['lastname']) . "', email = '" . $this->db->escape($data['email']) . "', telephone = '" . $this->db->escape($data['telephone']) . "', fax = '" . $this->db->escape($data['fax']) . "', newsletter = '" . (int)$data['newsletter'] . "', customer_group_id = '" . (int)$data['customer_group_id'] . "', salt = '" . $data['salt'] . "', password = '" . $data['password'] . "', status = '" . (int)$data['status'] . "' WHERE customer_id = '" . (int)$customer_id . "'");
            $this->addAddress($data, $customer_id);
        }else{
            $this->db->query("INSERT INTO " . DB_PREFIX . "customer SET firstname = '" . $this->db->escape($data['firstname']) . "', lastname = '" . $this->db->escape($data['lastname']) . "', email = '" . $this->db->escape($data['email']) . "', telephone = '" . $this->db->escape($data['telephone']) . "', fax = '" . $this->db->escape($data['fax']) . "', newsletter = '" . (int)$data['newsletter'] . "', customer_group_id = '" . (int)$data['customer_group_id'] . "', salt = '" . $data['salt'] . "', password = '" . $data['password'] . "', status = '" . (int)$data['status'] . "', date_added = NOW()");
      	    $customer_id = $this->db->getLastId();
            $this->addAddress($data, $customer_id);
            return $customer_id;
        }
	return false;
    }
    
    public function addAddress($data,$customer_id){
        $this->db->query("DELETE FROM " . DB_PREFIX . "address WHERE customer_id = '" . (int)$customer_id . "'");
        if (isset($data['address'])) {
            $data['address'] = (array)json_decode($data['address']);
            foreach ($data['address'] as $address) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "address SET customer_id = '" . (int)$customer_id . "', firstname = '" . $this->db->escape($address['firstname']) . "', lastname = '" . $this->db->escape($address['lastname']) . "', company = '" . $this->db->escape($address['company']) . "', company_id = '" . $this->db->escape($address['company_id']) . "', tax_id = '" . $this->db->escape($address['tax_id']) . "', address_1 = '" . $this->db->escape($address['address_1']) . "', address_2 = '" . $this->db->escape($address['address_2']) . "', city = '" . $this->db->escape($address['city']) . "', postcode = '" . $this->db->escape($address['postcode']) . "', country_id = '" . (int)$address['country_id'] . "', zone_id = '" . (int)$address['zone_id'] . "'");
            }
	}
    }
    
    public function getAttributeGroups($language_id){
//        echo "SELECT * FROM `oc_attribute_group` a  LEFT JOIN `oc_attribute_group_description` ad ON (a.`attribute_group_id`=ad.`attribute_group_id`) where ad.language_id='{$language_id}'"; exit;
        $result = $this->db->query("SELECT * FROM `oc_attribute_group` a  LEFT JOIN `oc_attribute_group_description` ad ON (a.`attribute_group_id`=ad.`attribute_group_id`) where ad.language_id='{$language_id}'");
        if($result->num_rows){
            return $result->rows;
        }
        return false;
    }
	
    
    public function json_cb(&$item, $key) {
            if (is_string($item)) $item = mb_encode_numericentity($item, array (0x80, 0xffff, 0, 0xffff), 'UTF-8'); 
        }

    public function gss_json_encode($arr){
        array_walk_recursive($arr,array($this,'json_cb'));
        return mb_decode_numericentity(json_encode($arr), array (0x80, 0xffff, 0, 0xffff), 'UTF-8');
    }

    public function loadHooks(){
        $path = dirname(__FILE__);
        $hooks_container = $path . DIRECTORY_SEPARATOR . 'hooks' . DIRECTORY_SEPARATOR;
        if(is_dir($hooks_container)){
            foreach (glob($hooks_container."*.php") as $filename){
                require_once($filename);
            }
        }else{
            die('Error: Unable to load hooks!');
        }
    }
}

?>
