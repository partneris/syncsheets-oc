<?php
/* Syncsheet Field Hooks 
 * type: normal/regex
 * match: valid regex expression
 * */

$hooks[] = array(
    'type'  =>  'normal',
    'match' =>  'store',
    'get'   =>  function($product,$field){
        $stores = $product->getProductStore($product->id,true);
        $store_field = '';
        if($stores)
        foreach($stores as $store){
        	if($store['store_id']==0)
        	        $store_field .= $product->config->get('config_name') . ',';
        	else
        		$store_field .= $store['name'] . ',';
        }
        return trim($store_field,',');
       
    },
    'add'   => function($key,$value,$product){
    	$stores = explode(',', $value);
    	
        $store_ids = array();
        foreach($stores as $store){
             $query = $product->db->query("select store_id from ".DB_PREFIX."store where name = '".trim($store)."'");
             if($query->num_rows){
                  $store_ids[] = $query->row['store_id'];
             }elseif($store == $product->config->get('config_name')){
             	  $store_ids[] = 0;
             }
        }
        $product->product['product_store'] = $store_ids;
    }
);
$hooks[] = array(
    'type'  =>  'normal',
    'match' =>  'store_id',
    'get'   =>  function($product,$field){
        $stores = $product->getProductStore($product->id,true);
        $store_field = '';
        
        foreach($stores as $store){
        	if($store['store_id']==0)
        	        $store_field .= $product->config->get('config_name') . ',';
        	else
        		$store_field .= $store['name'] . ',';
        }
        return trim($store_field,',');
    },
    'add'   => function($key,$value,$product){
        $stores = explode(',', $value);
        $store_ids = array();
        
        foreach($stores as $store){
             $query = $product->db->query("select store_id from ".DB_PREFIX."store where name = '".trim($store)."'");
             if($query->num_rows){
                  $store_ids[] = $query->row['store_id'];
             }elseif($store == $product->config->get('config_name')){
             	  $store_ids[] = 0;
             }
        }
        $product->product['product_store'] = $store_ids;
    }
);


$hooks[] = array(
    'type'  =>  'normal',
    'match' =>  'weight_class_unit',
    'get'   =>  function($product,$field){
        return $product->getProductWeightName($product->product['weight_class_id'],'unit');
    },
    'add'   => function($key,$value,$product){
         $product->product['product']['weight_class_id'] = $product->getProductWeightId($value,'unit');
    }
);

$hooks[] = array(
    'type'  =>  'normal',
    'match' =>  'weight_class',
    'get'   =>  function($product,$field){
        return $product->getProductWeightName($product->product['weight_class_id'],'title');
    },
    'add'   => function($key,$value,$product){
        $product->product['product']['weight_class_id'] = $product->getProductWeightId($value,'title');
    }
);

$hooks[] = array(
    'type'  =>  'normal',
    'match' =>  'length_class_unit',
    'get'   =>  function($product,$field){
        return $product->getProductLengthName($product->product['length_class_id'],'unit');
    },
    'add'   => function($key,$value,$product){
         $product->product['product']['length_class_id'] = $product->getProductLengthId($value,'unit');
    }
);

$hooks[] = array(
    'type'  =>  'normal',
    'match' =>  'length_class',
    'get'   =>  function($product,$field){
        return $product->getProductLengthName($product->product['length_class_id'],'title');
    },
    'add'   => function($key,$value,$product){
        $product->product['product']['length_class_id'] = $product->getProductLengthId($value,'title');
    }
);

$hooks[] = array(
    'type'  =>  'normal',
    'match' =>  'tax_class',
    'get'   =>  function($product,$field){
        return $product->getProductTaxName($product->product['tax_class_id']);
    },
    'add'   => function($key,$value,$product){
        $product->product['product']['tax_class_id'] = $product->getProductTaxId($value);
    }
);

$hooks[] = array(
    'type'  =>  'normal',
    'match' =>  'stock_status',
    'get'   =>  function($product,$field){
        return $product->getProductStock($product->product['stock_status_id']);
    },
    'add'   => function($key,$value,$product){
        $product->product['product']['stock_status_id'] = $product->getProductStockId($value);
    }
);

$hooks[] = array(
    'type' =>  'regex',
    'match'=>  '/^category.*/',
    'get'   =>  function($product,$field){
        $cat = explode('_', $field);
        $language_code = $cat[1];
        $delimiter = $cat[2];
        $language_id = isset($product->lg[$language_code]['language_id']) ? $product->lg[$language_code]['language_id'] : '0';
        $category = array(); $return=array();
        $cats = $product->getProductCategories($product->id);
        if (!empty($cats)){
            foreach ($cats as $category_id){
                $path = $product->getCategoryPath($category_id,$language_id);
                if (!empty($path)) {
                    $category[] = html_entity_decode($path);
                }
            }
        }
        
        return implode($delimiter,$category);
    },
    'add'   =>  function($key,$value,$product){
        $category_id = 0;
        $product->product['product_category']=array();
        
        $cat = explode('_', $key);
        $languageCode = $cat[1];
        $delimiter = $cat[2];
        $categories = explode($delimiter, $value);
        foreach ($categories as $value){
            
                if (isset($product->categories[$languageCode][html_entity_decode($value)])) {
                    $category_id = $product->categories[$languageCode][html_entity_decode($value)];
                    if ($category_id)
                        $product->product['product_category'][] = $category_id;
                }else {
                    if (!empty($value))
                        $category_id = $product->saveCategory($value,$product->languages);
                    if ($category_id)
                        $product->product['product_category'][] = $category_id;
                }
        }
    }
);

$hooks[] = array(
    'type'  => 'regex',
    'match' => '/^name.*/',
    'get'   =>  function($product,$field){ 
        return (isset($product->product[$field]) && !empty($product->product[$field])) ? $product->product[$field] : '';
    },
    'add'  =>  function($key,$value,$product){
        list($name, $language) = explode('_', $key);
        $language_id = isset($product->languages[$language]['language_id']) ? $product->languages[$language]['language_id'] : '0';
        $product->product['product_description'][$language_id]['name'] = $value;
    }
);

$hooks[] = array(
    'type'  => 'regex',
    'match' => '/^tag.*/',
    'get'   =>  function($product,$field){ 
        return (isset($product->product[$field]) && !empty($product->product[$field])) ? $product->product[$field] : '';
    },
    'add'  =>  function($key,$value,$product){ 
        list($name, $language) = explode('_', $key);
        $language_id = isset($product->languages[$language]['language_id']) ? $product->languages[$language]['language_id'] : '0';
        $product->product['product_description'][$language_id]['tag'] = $value;
    }
);

$hooks[] = array(
    'type'  => 'regex',
    'match' => '/^description.*/',
    'get'   =>  function($product,$field){ 
        return (isset($product->product[$field]) && !empty($product->product[$field])) ? html_entity_decode($product->product[$field]) : '';
    },
    'add'  =>  function($key,$value,$product){
        list($name, $language) = explode('_', $key);
        $language_id = isset($product->languages[$language]['language_id']) ? $product->languages[$language]['language_id'] : '0';
        $product->product['product_description'][$language_id]['description'] = $value;
    }
);

$hooks[] = array(
    'type'  => 'regex',
    'match' => '/^meta_key.*/',
    'get'   =>  function($product,$field){ 
        return (isset($product->product[$field]) && !empty($product->product[$field])) ? $product->product[$field] : '';
    },
    'add'  =>  function($key,$value,$product){
        $meta_text = explode('_', $key);
        $language = end($meta_text);
        $language_id = isset($product->languages[$language]['language_id']) ? $product->languages[$language]['language_id'] : '0';
        $product->product['product_description'][$language_id]['meta_keyword'] = $value;
    }
);

$hooks[] = array(
    'type'  => 'regex',
    'match' => '/^meta_desc.*/',
    'get'   =>  function($product,$field){ 
        return (isset($product->product[$field]) && !empty($product->product[$field])) ? $product->product[$field] : '';
    },
    'add'  =>  function($key,$value,$product){
        $meta_text = explode('_', $key);
        $language = end($meta_text);
        $language_id = isset($product->languages[$language]['language_id']) ? $product->languages[$language]['language_id'] : '0';
        $product->product['product_description'][$language_id]['meta_description'] = $value;
    }
);

$hooks[] = array(
    'type'  => 'regex',
    'match' => '/^manufacturer.*/',
    'get'   =>  function($product,$field){
        return (isset($product->product['manufacturer']) && !empty($product->product['manufacturer'])) ? $product->product['manufacturer'] : '';
    },
    'add'  => function($key,$value,$product){
        $product->product['product']['manufacturer_id'] = $product->saveManufacurer($value);
    }
);

$hooks[] = array(
    'type'  => 'normal',
    'match' => 'image',
    'get'   =>  function($product,$field){ 
        return (isset($product->product[$field]) && !empty($product->product[$field])) ? $product->product[$field] : '';
    },
    'add'  =>  function($key,$value,$product){
        if(isUrl($value)){
            $img = saveImageFromUrl($value);
            if($img)
               $product->product['product']['image'] = $img;
        }else{
            $product->product['product']['image'] = $value;
        }
    }
);
    

$hooks[] = array(
    'type'  => 'regex',
    'match' => '/^additional.*/',
    'get'   =>  function($product,$field){
        $imgs = array();
        $images = $product->model_catalog_product->getProductImages($product->id);
        if($images){
            $product->product['product_image'] = array();
        }
        
        foreach ($images as $image) {
            $imgs[] = $image['image'];
        }
        return implode('|', $imgs);
    },
    'add'  => function($key,$value,$product){
        $images = explode('|', $value);
        $product->product['product_image'] = array();
        foreach($images as $key => $image) {
             if(isUrl($image)){
                 $img = saveImageFromUrl($image);
	            if($img){
	               $product->product['product_image'][$key]['image'] = trim($img);
	               $product->product['product_image'][$key]['sort_order'] = $key;
	             }
             }elseif($image){
                   $product->product['product_image'][$key]['image'] = trim($image);
	           $product->product['product_image'][$key]['sort_order'] = $key;
             }
        }
    }
    
);   


$hooks[] = array(
    'type'  => 'regex',
    'match' => '/^discount.*/',
    'beforetFilter' => function($header,$product){
        $regex = "/\{(.*?)\}/";
        preg_match($regex, $header, $match);
        parse_str($match[1],$parsed_header);
        if(isset($parsed_header['group'])){
            $group = $product->getCustomerGroupByName($parsed_header['group']);
            $parsed_header['customer_group_id'] = $group['customer_group_id'];
            $product->default_discount = $parsed_header;
            if (empty($product->customer_groups)) {
				$customer_groups = $product->getCustomerGroup();
				$customer_group_ids = array();
				foreach ( $customer_groups as $customer_group ) {
					$customer_group_ids[$customer_group['name']] = $customer_group['customer_group_id'];
				}
				$product->customer_group_ids = $customer_group_ids;
			}
        }
    },
    'get' => function($product,$field){
		if(isset($product->product['discount']))      
			return $product->product['discount'];
    },
    'add' => function($key,$value,$product){
        $value = str_replace("'","\"", $value);
        $discounts = json_decode($value);
		$product->product['product_discount'] = array();
        if(is_array($discounts)){
            foreach($discounts as $item){
				$item = (array)$item;
				if (!empty($item['group'])) {
					if (!empty($product->customer_group_ids[ $item['group'] ])) {
						$item['customer_group_id'] = $product->customer_group_ids[ $item['group'] ];
					}
				}

                $product->product['product_discount'][] = $item;
            }
        }
    }
);

$hooks[] = array(
    'type'  => 'regex',
    'match' => '/^special.*/',
    'beforetFilter' => function($header,$product){
        $regex = "/\{(.*?)\}/";
        preg_match($regex, $header, $match);
        parse_str($match[1],$parsed_header);
        if(isset($parsed_header['group'])){
            $group = $product->getCustomerGroupByName($parsed_header['group']);
            $parsed_header['customer_group_id'] = $group['customer_group_id'];
            $product->default_special = $parsed_header;
            if (empty($product->customer_groups)) {
				$customer_groups = $product->getCustomerGroup();
				$customer_group_ids = array();
				foreach ( $customer_groups as $customer_group ) {
					$customer_group_ids[$customer_group['name']] = $customer_group['customer_group_id'];
				}
				$product->customer_group_ids = $customer_group_ids;
			}
        }
    },
    'get' => function($product,$field){
		if(isset($product->product['special']))      
			return $product->product['special'];
    },
    'add' => function($key,$value,$product){
        $value = str_replace("'","\"", $value);
        $special = json_decode($value);
		$product->product['product_special'] = array();
        if (is_array($special)) {
            foreach($special as $item){
				$item = (array)$item;
				if (!empty($item['group'])) {
					if (!empty($product->customer_group_ids[ $item['group'] ])) {
						$item['customer_group_id'] = $product->customer_group_ids[ $item['group'] ];
					}
				}
				$product->product['product_special'][] = $item;
            }
        }
    }
);

$hooks[] = array(
    'type'  => 'regex',
    'match' => '/^option.*/',
    'get'   => function($product,$field){
        return $product->product['options'];
    },
    'add' => function($key,$value,$product){
        $value = str_replace("'","\"", $value);
        $options = json_decode($value);
        $product->product['product_option']=array();
        if($options){
            foreach($options as $item){
                $product->product['product_option'][] = (array)$item;
            }
        }
    }
);

$hooks[] = array(
    'type'  => 'regex',
    'match' => '/^at(\d+).*/',
    'get'   =>  function($product,$field){ 
        if (isset($product->attributes[$field])) { 
            return $product->attributes[$field];
        } else {
            return '';
        }
    },
    'add' => function($key,$value,$product){
        preg_match('/at(\d+)/', $key, $matches);
        if (isset($matches[1])) {          
            $attribute_id = $matches[1];
            $language_code = substr($key, strlen($key) - 2, strlen($key));
            $language_id = isset($product->languages[$language_code]['language_id']) ? $product->languages[$language_code]['language_id'] : '0';
            $product->product['product_attribute'][$attribute_id]['name'] = '';
            $product->product['product_attribute'][$attribute_id]['attribute_id'] = $attribute_id;
            $product->product['product_attribute'][$attribute_id]['product_attribute_description'][$language_id]['text'] = $value;
        }
    }
);


$hooks[] = array(
    'type'  => 'regex',
    'match' => '/^keyword.*/',
    'get'   =>  function($product,$field){
        return (isset($product->product['seo_keyword']) && !empty($product->product['seo_keyword'])) ? $product->product['seo_keyword'] : '';
    },
    'add'  => function($key,$value,$product){
        $product->product['product']['keyword'] = $value;
    }
);

$hooks[] = array(
    'type'  => 'normal',
    'match' =>  'related',
    'get'   =>  function($product,$field){
        $related = '';
        $query = $product->db->query("select p.model from ".DB_PREFIX."product p left join ".DB_PREFIX."product_related rp on (p.product_id=rp.related_id) where rp.product_id='$product->id' group by rp.related_id");
        
                    foreach($query->rows as $row){
                        $related .=$row['model'].'|';
                    }
         return trim($related,'|');
    },
    'add' => function($key,$value,$product){
        if($value){
            $related_models = explode('|', $value);
            
            foreach($related_models as $model){
                $product->product['product_related'][]=$product->getProductByModel($model);
            }
            
        }
    }
);



$hooks[] = array(
        'type' => 'normal',
        'match' => 'related_group',
        'get' => function($product, $field) {

    },
    'add' => function($key, $value, $product) {
        if($value){
            $rkey = trim(md5($value));
            $product->product['product_related_group'][$rkey][]=$product->id;
        }
    },
    'cb'  => function($product){
        if(isset($product->product['product_related_group'])){
            $this->db->query("TRUNCATE TABLE " . DB_PREFIX . "product_related2");
            foreach($product->products['product_related_group'] as $groups){
                if(count($groups)>1){
                    for($rp=0;$rp<count($groups);$rp++){
                        $product->setRelatedProducts2($groups[$rp],$groups);
                    }
                }
            }
        }
    }
);

    function isUrl($url){
        if(filter_var($url, FILTER_VALIDATE_URL))
            return true;    
        else
            return false;
    }

    function saveImageFromUrl($image){
	    $root = realpath(DIR_APPLICATION.'..');
	    if($image){
	        $download_path = 'data'.DIRECTORY_SEPARATOR.'gss' . DIRECTORY_SEPARATOR;
	    $name = substr($image,  strrpos($image,'/')+1, strlen($image));
	    $c_name = md5($name) . '_' . $name;
	    if(!is_dir(DIR_IMAGE . $download_path)){
	        mkdir(DIR_IMAGE . $download_path,0777,true);
	        chmod(DIR_IMAGE . $download_path, 0777);
	    }
	    $filename = DIR_IMAGE . $download_path . $c_name;
	    if(!file_exists($filename))
	        file_put_contents($filename, file_get_contents($image));
	    return $download_path . $c_name;
    }
}
?>
