<?php
/* Syncsheet Field Hooks 
 * type: normal/regex
 * match: valid regex expression
 * */

$hooks[] = array(
    'type'  =>  'regular',
    'match' =>  'product_id',
    'add'   => function(){
        return;
    }
);
$hooks[] = array(
    'type'  =>  'regular',
    'match' =>  '/^store.*/',
    'get'   =>  function($product,$field){
        return $product->getProductStore();
    },
    'add'   => function($key,$value,$product){
//        $config_name = $product->config->get('config_name');
//        $stores = explode(',',$value);
        
        return $product->product['product_store'][0] = 0;
    }
);
$hooks[] = array(
    'type' =>  'regex',
    'match'=>  '/^category.*/',
    'get'   =>  function($product,$field){
        if(array_key_exists($field, $product->productMap) && !empty($product->productMap[$field])){
            return false;
        }
        
        $catIndex = filter_var($field, FILTER_SANITIZE_NUMBER_INT);
        $cat = explode($catIndex, $field);
        $language_code = end($cat);
//        print_r($product->languages);
//        echo $language_code; exit;
        $language_id = isset($product->lg[$language_code]['language_id']) ? $product->lg[$language_code]['language_id'] : '0';
        $category = array(); $return=array();
        $cats = $product->getProductCategories($product->id);
        if (!empty($cats)){
            foreach ($cats as $category_id){
                $path = $product->getCategoryPath($category_id,$language_id);
                if (!empty($path)) {
                    $category[] = $path;
                }
            }
        }
        $languageCode = substr($field,  strlen($field)-2,  strlen($field));
        foreach ($category as $catekey => $catitem) {
            $n = $catekey + 1;
            $return['category' . $n.$languageCode] = html_entity_decode($catitem);
        }
        return $return;
    },
    'add'   =>  function($key,$value,$product){
        $category_id = 0;
        $catIndex = filter_var($key, FILTER_SANITIZE_NUMBER_INT);
        $cat = explode($catIndex, $key);
        $languageCode = end($cat);
       
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
);

$hooks[] = array(
    'type'  => 'regex',
    'match' => '/^name.*/',
    'get'   =>  function($product,$field){ 
        return (isset($product->product[$field]) && !empty($product->product[$field])) ? html_entity_decode($product->product[$field]) : '';
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
        return (isset($product->product[$field]) && !empty($product->product[$field])) ? html_entity_decode($product->product[$field]) : '';
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
        return (isset($product->product[$field]) && !empty($product->product[$field])) ? html_entity_decode($product->product[$field]) : '';
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
        return (isset($product->product[$field]) && !empty($product->product[$field])) ? html_entity_decode($product->product[$field]) : '';
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
        return (isset($product->product['manufacturer']) && !empty($product->product['manufacturer'])) ? html_entity_decode($product->product['manufacturer']) : '';
    },
    'add'  => function($key,$value,$product){
        $product->product['product']['manufacturer_id'] = $product->saveManufacurer($value);
    }
);

$hooks[] = array(
    'type'  => 'regex',
    'match' => '/^additional.*/',
    'get'   =>  function($product,$field){
        $imgs = array();
        $images = $product->model_catalog_product->getProductImages($product->id);
        foreach ($images as $image) {
            $imgs[] = $image['image'];
        }
        return implode('|', $imgs);
    },
    'add'  => function($key,$value,$product){
        $images = explode('|', $value);
        foreach ($images as $key => $image) {
            $product->product['product_image'][$key]['image'] = $image;
            $product->product['product_image'][$key]['sort_order'] = $key;
        }
    }
    
);                                    

$hooks[] = array(
    'type'  => 'regex',
    'match' => '/^store.*/',
    'get'   =>  function($product,$field){ //print_r($product); exit;
        return $product->getProductStore($product->id);
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
    'type'  => 'normal',
    'match' =>  'related',
    'get'   =>  function($product,$field){
        $related = '';
        $query = $product->db->query("select p.model from ".DB_PREFIX."product p left join ".DB_PREFIX."product_related rp on (p.product_id=rp.product_id) where rp.product_id='$product->id' group by rp.product_id");
                    foreach($query->rows as $row){
                        $related .=$row['model'].',';
                    }
         return trim($related,',');
    },
    'add' => function($key,$value,$product){
        if($value){
            $related_models = explode('|', $value);
            foreach($related_models as $model){
                $product->product['product_related'][]=$product->getRelatedProducts($model);
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
?>
