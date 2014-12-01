<?php echo $header; ?>
<script type="text/javascript">
    validate=function(){
        var name = document.getElementById('inpname').value;
        if(name.trim()==''){
            $('#inpname').next().show();
            return false;
        }  return true;
    }
</script>
<div id="content">
  <div class="breadcrumb">
    <?php foreach ($breadcrumbs as $breadcrumb) { ?>
    <?php echo $breadcrumb['separator']; ?><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a>
    <?php } ?>
  </div>
  <?php if ($error_warning) { ?>
  <div class="warning"><?php echo $error_warning; ?></div>
  <?php } ?>
  <div class="box">
    <div class="heading">
      <h1><img src="view/image/feed.png" alt="" /> <?php echo $heading_title; ?></h1>
      <div class="buttons">
      
      <a onclick="$('#form').submit();" class="button"><?php echo $button_save; ?></a>
      <a href="<?php echo $cancel; ?>" class="button"><?php echo $button_cancel; ?></a>
      </div>
    </div>
    <div class="content">
        <form onsubmit="return validate();" action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form">
          
          <table class="form">
              <tr>
                  <td>Save setting as:</td>
                  <td>
                      <input id="inpname" required="true" value="<?php if($edit && isset($title)) echo $title; ?>" type="text" name="title" />
                      <span style="display: none;" class="error">Setting name is required!</span>
                  </td>
              </tr>
          </table>
        <?php //print_r($settings); exit; ?>      
        <div id="tabs" class="htabs">
            <a href="#tab-general">General</a>
            <a href="#tab-attributes">Attributes</a>
            <a href="#tab-options">Options</a>
            <a href="#tab-discount">Discount</a>
            <a href="#tab-special">Specials</a>
            <a href="#tab-misc">Miscellaneous</a>
        </div>
          
      <div id="tab-general">
          <table class="list">
              <thead>
                    <th class="left">Sr. No</th>
                    <th class="left">Field</th>
                    <th class="left">Description</th>
                    <?php foreach($languages->rows as $language): ?>
                    <th class="left">
                        <?php if($language['code']!=$default_langaue): ?><input <?php if(!$edit) echo "checked=true"; ?> <?php if($edit && isset($settings['general']['language'][$language['code']])) echo "checked=checked"; ?> lan="g<?php echo $language['code']; ?>" class="_ev" type="checkbox" name="data[general][language][<?php echo $language['code']; ?>]" value="1" /><?php endif; ?>
                        <?php echo $language['name']; ?> 
                        <?php if($language['code']==$default_langaue): ?>
                        <input type="hidden" name="data[general][language][<?php echo $language['code']; ?>]" value="1" />
                        <?php endif; ?>
                    </th> 
                    <?php endforeach; ?>
              </thead>
                <tbody class="_sort">
                   <?php  $i=1;$k=0; foreach($product_fields['fields'] as $key=>$item): ?>
                  <tr>
                      <td><?php echo $i++; ?></td>
                      
                      <td><input type="hidden" value="<?php echo $item['field'] ?>" name="data[general][required][]" />
                          <?php echo $item['name'] ?></td>
                      <td><?php echo $item['descr'] ?></td>
                      <?php foreach($languages->rows as $language):  ?>
                      <td>
                          <?php if($language['code']!=$default_langaue  && $item['multilanguage']){ ?>
                          <input <?php //if($language['code']!=$default_langaue && !isset($settings['general']['language'][$language['code']])) echo 'disabled=true'; ?> class="_ig<?php echo $language['code']; ?>" type="text" name="data[general][defaults][<?php echo $language['code']; ?>][<?php echo $item['field'] ?>]" value="<?php if($edit && isset($settings['general']['defaults'][$language['code']][$item['field']])) echo $settings['general']['defaults'][$language['code']][$item['field']]; ?>" />
                          <?php }elseif($language['code']==$default_langaue){ if(isset($item['options'])){  ?>
                          <select name="data[general][defaults][<?php echo $language['code']; ?>][<?php echo $item['field'] ?>]">
                           <?php foreach($item['options'] as $keyID=>$optionName): ?>   
                              <option title="<?php echo $optionName; ?>" <?php if($edit && isset($settings['general']['defaults'][$language['code']][$item['field']]) && $settings['general']['defaults'][$language['code']][$item['field']]==$keyID) echo 'selected=selected'; ?> value="<?php echo $keyID; ?>"><?php if(strlen($optionName)>25) echo substr($optionName,0,25).'..'; else echo $optionName; ?></option>
                           <?php endforeach; ?>
                          </select>
                          
                          <?php  }else{ ?>
                          <input class="_ig<?php echo $language['code']; ?>" type="text" name="data[general][defaults][<?php echo $language['code']; ?>][<?php echo $item['field'] ?>]" value="<?php if($edit && isset($settings['general']['defaults'][$language['code']][$item['field']])) echo $settings['general']['defaults'][$language['code']][$item['field']]; ?>" />
                          <?php } } ?>
                      </td>
                      <?php endforeach; ?>
                  </tr>
                   <?php endforeach; ?>
                </tbody>
          </table>
      </div>
          <div id="tab-attributes">
              <table>
                  <tr>
                      <td>Default Attribute Group</td>
                      <td><select name="data[attribute_group]">
                          <?php foreach($attribute_groups as $group): ?>
                              <option <?php if(isset($settings['attribute_group']) && $settings['attribute_group'] == $group['attribute_group_id']) echo "selected=selected"; ?> value="<?php echo $group['attribute_group_id']; ?>"><?php echo $group['name']; ?></option>
                          <?php endforeach; ?>
                          </select></td>
                  </tr>
              </table>
          <table class="list">
              <thead>
                    <th class="left">Sr. No</th>
                    <th class="left">Attribute Name</th>
                    <th class="left">Attribute Group</th>
                    <?php foreach($languages->rows as $language): ?>
                    <th class="left">
                    <?php if($language['code']!=$default_langaue): ?><input <?php if(!$edit) echo "checked=true"; ?> <?php if($edit && isset($settings['attribute']['language'][$language['code']])) echo "checked=checked"; ?> lan="a<?php echo $language['code']; ?>" class="_f0 _ev" type="checkbox" name="data[attribute][language][<?php echo $language['code']; ?>]" value="1" /><?php endif; ?>
                    <?php echo $language['name']; ?> 
                    </th>
                    <?php endforeach; ?>
              </thead>
              <tbody>
                  <?php $k=0; foreach($attributes as $key=>$item): ?>
                  <tr>
                      <td><?php echo $key+1; ?></td>
                      <td><input type="hidden" name="data[attribute][required][]" value="<?php echo $item['attribute_id']; ?>" />
                          <?php echo $item['name']; ?></td>
                      <td><?php echo $item['attribute_group']; ?></td>
<!--                      <td><input type="text" name="attribute_default[<?php //echo $item['attribute_id']; ?>]" value="" /></td>-->
                      <?php foreach($languages->rows as $language): ?>
                      <td><input <?php //if($language['code']!=$default_langaue) echo 'disabled=true'; ?> class="_ia<?php echo $language['code']; ?>" type="text" name="data[attribute][default][<?php echo $language['code']; ?>][<?php echo $item['attribute_id']; ?>]" value="<?php if($edit && isset($settings['attribute']['default'][$language['code']][$item['attribute_id']])) echo $settings['attribute']['default'][$language['code']][$item['attribute_id']]; ?>" /></td>
                      <?php endforeach; ?>
                  </tr>
                  <?php endforeach; ?>
              </tbody>
          </table>
      </div>
          <div id="tab-options">
              
              <table class="form">
              
              <tbody>
                    <tr>
                        <td>Default Option Value</td>
                        <td><input type="text" name="data[option][option_value]" id="option_value_text" value="<?php if($edit && isset($settings['option']['option_value'])) echo $settings['option']['option_value']; ?>" /></td>
                    </tr>
                    <tr>
                        <td><input type="hidden" name="data[option][sheet][quantity]" value="1" />Quantity</td>
                        <td><input type="text" name="data[option][quantity]" value="<?php if($edit && isset($settings['option']['quantity'])) echo $settings['option']['quantity']; ?>" /></td>
                    </tr>
                    <tr>
                        <td><input type="hidden" name="data[option][sheet][required]" value="1" />Required</td>
                        <td><select name="data[option][required]">
                                <option <?php if($edit && isset($settings['option']['quantity']) && $settings['option']['quantity']==0) echo "selected=selected"; ?> value="0">No</option>
                                <option <?php if($edit && isset($settings['option']['quantity']) && $settings['option']['quantity']==1) echo "selected=selected"; ?> value="1">Yes</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td><input type="hidden" name="data[option][sheet][substract_stock]" value="1" />Subtract Stock:</td>
                        <td><select name="data[option][substract_stock]">
                                <option <?php if($edit && isset($settings['option']['quantity']) && $settings['option']['substract_stock']==1) echo "selected=selected"; ?> value="1">Yes</option>
                                <option <?php if($edit && isset($settings['option']['quantity']) && $settings['option']['substract_stock']==0) echo "selected=selected"; ?> value="0">No</option>
                              </select>
                        </td>
                    </tr>
                    <tr>
                        <td><input  type="hidden" name="data[option][sheet][price]" value="1" />Price</td>
                        <td><select name="data[option][pre_price]">
                                <option <?php if($edit && isset($settings['option']['pre_price']) && $settings['option']['pre_price']=='+') echo "selected=selected"; ?> value="+">+</option>
                                <option <?php if($edit && isset($settings['option']['pre_price']) && $settings['option']['pre_price']=='-') echo "selected=selected"; ?> value="-">-</option>
                            </select>
                            <input type="text" value="<?php if($edit && isset($settings['option']['price'])) echo $settings['option']['price']; ?>" name="data[option][price]" />
                        </td>
                    </tr>
                    <tr>
                        <td><input type="hidden" name="data[option][sheet][point]" value="1" />Point</td>
                        <td><select name="data[option][point_prefix]">
                                <option <?php if($edit && isset($settings['option']['point_prefix']) && $settings['option']['point_prefix']=='+') echo "selected=selected"; ?> value="+">+</option>
                                <option <?php if($edit && isset($settings['option']['point_prefix']) && $settings['option']['point_prefix']=='-') echo "selected=selected"; ?> value="-">-</option>
                            </select>
                            <input type="text" value="<?php if($edit && isset($settings['option']['point'])) echo $settings['option']['point']; ?>" name="data[option][point]" />
                        </td>
                    </tr>
                    <tr>
                        <td><input type="hidden" name="data[option][sheet][weight]" value="1" />Weight</td>
                        <td><input value="<?php if($edit && isset($settings['option']['weight'])) echo $settings['option']['weight']; ?>" type="text" name="data[option][weight]"/></td>
                    </tr>
              </tbody>
              </table>
          </div>
          
          <div id="tab-discount">
             
              <table class="form">
                    <tbody>
                        <tr>
                        <td>Default Customer Group</td>  
                        <td><select name="data[discount][customer_group]">
                                <?php foreach($customer_groups as $customer): ?>
                                    <option <?php if($edit && ($settings['discount']['customer_group'] ==$customer['customer_group_id']) ) echo "selected=selected"; ?> value="<?php echo $customer['customer_group_id']; ?>"><?php echo $customer['name']; ?></option>
                                <?php endforeach; ?>
                            </select></td>
                        </tr>
                        <tr>
                            <td>Quantity</td>
                            <td><input value="<?php if($edit && isset($settings['discount']['quantity'])) echo $settings['discount']['quantity']; ?>" name="data[discount][quantity]" /></td>
                           
                        </tr>
                        <tr>
                            <td>Priority</td>
                            <td><input value="<?php if($edit && isset($settings['discount']['priority'])) echo $settings['discount']['priority']; ?>" name="data[discount][priority]" /></td>
                        </tr>
                        <tr>
                            <td>Price</td>
                            <td><input value="<?php if($edit && isset($settings['discount']['price'])) echo $settings['discount']['price']; ?>" name="data[discount][price]" /></td>
                        </tr>
                        <tr>
                            <td>Date Start</td>
                            <td><input value="<?php if($edit && isset($settings['discount']['date_start'])) echo $settings['discount']['date_start']; ?>" name="data[discount][date_start]" /></td>
                        </tr>
                        <tr>
                            <td>Date End</td>
                            <td><input value="<?php if($edit && isset($settings['discount']['date_end'])) echo $settings['discount']['date_end']; ?>" name="data[discount][date_end]" /></td>
                        </tr>
                    </tbody>
              </table>
          </div>
          <div id="tab-special">
              <table class="form">
                    <tbody>
                        <tr>
                        <td>Default Customer Group</td>
                        <td><select name="data[special][customer_group]">
                                <?php foreach($customer_groups as $customer): ?>
                                    <option <?php if($edit && ($settings['special']['customer_group'] == $customer['customer_group_id']) ) echo "selected=selected"; ?> value="<?php echo $customer['customer_group_id']; ?>"><?php echo $customer['name']; ?></option>
                                <?php endforeach; ?>
                            </select></td>
                           
                        </tr>
                        <tr>
                            <td>Quantity</td>
                            <td><input value="<?php if($edit && isset($settings['special']['quantity'])) echo $settings['special']['quantity']; ?>" name="data[special][quantity]" /></td>
                            
                        </tr>
                        
                        <tr>
                            <td>Priority</td>
                            <td><input value="<?php if($edit && isset($settings['special']['priority'])) echo $settings['special']['priority']; ?>" name="data[special][priority]" /></td>
                           
                        </tr>
                        
                        <tr>
                            <td>Price</td>
                            <td><input value="<?php if($edit && isset($settings['special']['price'])) echo $settings['special']['price']; ?>" name="data[special][price]" /></td>
                            
                        </tr>
                        
                        <tr>
                            <td>Date Start</td>
                            <td><input value="<?php if($edit && isset($settings['special']['date_start'])) echo $settings['special']['date_start']; ?>" name="data[special][date_start]" /></td>
                          
                        </tr>
                        
                        <tr>
                            <td>Date End</td>
                            <td><input value="<?php if($edit && isset($settings['special']['date_end'])) echo $settings['special']['date_end']; ?>" name="data[special][date_end]" /></td>
                           
                        </tr>
                    </tbody>
                  
              </table>
          </div>
          
          <div id="tab-misc">
            <table class="form">
                    <tbody>
                        <tr>
                        <td>How many discount per product?</td>
                        <td><input name="data[discount_count]" type="text" value="<?php if($edit && isset($settings['discount_count'])) echo $settings['discount_count']; else{ if($max_discount) echo $max_discount; else echo '1'; } ?>" /></td>
                        </tr>
                        <tr>
                            <td>How many special prices per product?</td>
                            <td><input value="<?php if($edit && isset($settings['special_count'])) echo $settings['special_count']; else{ if($max_special) echo $max_special; echo '1'; } ?>" name="data[special_count]" /></td>
                        </tr>
                    </tbody>
            </table>
          </div>
          
<!--          <form action="<?php //echo $action; ?>" method="post" enctype="multipart/form-data" id="form">-->
            <?php if($edit): ?>
            <input type="hidden" name="id" value="<?php echo $id; ?>"/>
            <?php endif; ?>
      </form>
    </div>
  </div>
</div>
<script type="text/javascript">
$(document).ready(function(){

var $form = $('#option-value-form form');
    $('._define').click(function(e){ e.preventDefault();
        $("#option-value-form" ).data('current', this).dialog({width:500,
            open:function(){
                $form.get(0).reset();
                var $this = $.data(this,'current');
                var options = $.parseJSON($($this).attr('data'));
                if(options){
                $('#option_value_text').hide();
                $('#option_values').show().html('');
                    for(i in options){
                        var opt = options[i];
                        $('#option_values').append('<option value="'+opt.option_value_id+'">'+opt.name+'</option>')
                    }
                }else{
                    $('#option_values').hide();
                    $('#option_value_text').show();
                }
//                console.log($($this).next().val());
                    if($($this).next().val()){
                        $form.deserialize($($this).next().val());
                    }
            },
            buttons:{
                "Save":function(){
                    var $this = $.data(this, 'current');
                    $($this).next().val($form.serialize());
                    $(this).dialog('close');
                },
                Cancel:function(){
                    $(this).dialog('close');
                }
            }
        });
    });
    
    $('#tabs a').tabs();
});
unserialize = function(serialized){
    $.each(serialized.split('&'), function (index, elem) {
       var vals = elem.split('=');
       console.log(vals[0]+' '+vals[1]);
       $("[name='" + vals[0] + "']").val(vals[1]);
    });
}
$('._ev').change(function(){
    var l = $(this).attr('lan');
    if($(this).is(':checked')){
        $(this).closest('table').find('._i'+l).attr('disabled',false).addClass('_in');
        
    }else{
        $('._i'+l).attr('disabled',true).addClass('_out');
    }
});

//$('._req').change(function(){
//   if($(this).is(':checked')){
//       $(this).closest('tr').find('input').not('._out').attr('disabled',false)
//   }else{
//       $(this).closest('tr').find('input').not('._out').attr('disabled',true)
//   }
//});
$('._sort').sortable();

$('#inpname').keyup(function(){
    if($(this).val())
        $(this).next().hide();
    else
        $(this).next().show();
})
</script>

<div style="display: none;" id="option-value-form">
    <form method="" action="">
    <table class="form" id="option-form">
        <tbody>
            <tr>
                <td>Default Option Value</td>
                <td><select name="option_value" id="option_values"></select>
                    <input type="text" name="option_value_text" id="option_value_text" value="" />
                </td>
            </tr>
            <tr>
                <td>Quantity</td>
                <td><input type="text" name="quantity"/></td>
            </tr>
            <tr>
                <td>Subtract Stock:</td>
                <td><select name="subtract_stock">
                        <option selected="selected" value="1">Yes</option>
                        <option value="0">No</option>
                      </select>
                </td>
            </tr>
            <tr>
                <td>Price</td>
                <td><select name="pre_price">
                                            <option selected="selected" value="+">+</option>
                                                                  <option value="-">-</option>
                                          </select>
                    <input type="text" name="price"/></td>
            </tr>
            <tr>
                <td>Point</td>
                <td><select name="pre_point">
                        <option selected="selected" value="+">+</option>
                        <option value="-">-</option>
                    </select>
                    <input type="text" name="point"/></td>
            </tr>
            <tr>
                <td>Weight</td>
                <td><input type="text" name="weight"/></td>
            </tr>
        </tbody>
    </table>
    </form>
</div>

<?php echo $footer; ?>