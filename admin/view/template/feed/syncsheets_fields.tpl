<style type="text/css">
    .ui-tabs-active{border-bottom: 2px solid green};
    a{outline: none}
    .list,.form{float: left;}
</style>
<div id="content">
    <center><img style="margin-top: 100px;" class="ld" src="http://dev1.partneris.lv/image/loader.gif" /></center>  
  
  <div class="box">
    
      <div style="display: none;" class="content">
         <form name="gssSettngs" method="post"> 
          <table class="form">
              <tr>
                  <td>Save setting as:</td>
                  <td>
                      <input id="inpname" required="true" value="<?php if($edit && isset($title)) echo $title; ?>" type="text" name="title" />
                      <span style="display: none;" class="error">Setting name is required!</span>
                  </td>
                  <td><input onclick="google.script.run
                .withSuccessHandler(onSuccess).withFailureHandler(onFailure)
                .xxxxxxxxxx(this.form)" type="submit" name="saveSettings" value="Save" />
                  <?php if($edit): ?>
            <input type="hidden" name="id" value="<?php echo $id; ?>"/>
            <?php endif; ?>
                  </td>
              </tr>
          </table>  
            
        <div id="tabs" class="htabs">
            <ul>
                <li><a href="#tab-general">General</a></li>
                <li><a href="#tab-attributes">Attributes</a></li>
                <li><a href="#tab-options">Options</a></li>
                <li><a href="#tab-discount">Discount</a></li>
                <li><a href="#tab-special">Specials</a></li>
                <li><a href="#tab-misc">Miscellaneous</a></li>
            </ul>
        
          
      <div class="tb" id="tab-general">
          <table class="list">
              <thead>
                    <th class="left">Sr. No</th>
                    <th class="left">Required</th>
                    <th class="left">Field</th>
                    <th class="left">Description</th>
                    <?php foreach($languages->rows as $language): ?>
                    <th class="left">
                        <?php if($language['code']!=$default_langaue): ?><input <?php if($edit && isset($settings['general']['language'][$language['code']])) echo "checked=checked"; ?> lan="g<?php echo $language['code']; ?>" class="_ev" type="checkbox" name="data[general][language][<?php echo $language['code']; ?>]" value="1" /><?php endif; ?>
                        <?php echo $language['name']; ?> Default 
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
                      <td><input <?php if($edit && in_array($item['field'],$settings['general']['required'])) echo "checked=checked"; ?> class="_req" type="checkbox" value="<?php echo $item['field'] ?>" name="data[general][required][]" /></td>
                      <td><?php echo $item['name'] ?></td>
                      <td><?php echo $item['descr'] ?></td>
                      <?php foreach($languages->rows as $language):  ?>
                      <td>
                          <?php if($language['code']!=$default_langaue  && $item['multilanguage']){ ?>
                          <input <?php if($language['code']!=$default_langaue && !isset($settings['general']['language'][$language['code']])) echo 'disabled=true'; ?> class="_ig<?php echo $language['code']; ?>" type="text" name="data[general][defaults][<?php echo $language['code']; ?>][<?php echo $item['field'] ?>]" value="<?php if($edit && isset($settings['general']['defaults'][$language['code']][$item['field']])) echo $settings['general']['defaults'][$language['code']][$item['field']]; ?>" />
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
          <div class="tb" id="tab-attributes">
              
              <table>
                  <tr>
                      <td><input <?php if($edit && isset($settings['attribute']['enable'])) echo "checked=checked"; ?> type="checkbox" name="data[attribute][enable]" value="1" /></td>
                      <td>Enable Product Attributes in google sheet</td>
                  </tr>
              </table>   
              
          <table class="list">
              <thead>
              <th class="left">Sr. No</th>
              <th class="left">Required</th>
                    <th class="left">Attribute Name</th>
                    <th class="left">Attribute Group</th>
                    <?php foreach($languages->rows as $language): ?>
                    <th class="left">
                    <?php if($language['code']!=$default_langaue): ?><input <?php if($edit && isset($settings['attribute']['language'][$language['code']])) echo "checked=checked"; ?> lan="a<?php echo $language['code']; ?>" class="_f0 _ev" type="checkbox" name="data[attribute][language][<?php echo $language['code']; ?>]" value="1" /><?php endif; ?>
                    <?php echo $language['name']; ?> Default 
                    </th>
                    <?php endforeach; ?>
              </thead>
              <tbody>
                  <?php $k=0; foreach($attributes as $key=>$item): ?>
                  <tr>
                     <td><?php echo $key+1; ?></td>
                      <td><input <?php if($edit && isset($settings['attribute']['required']) && in_array($item['attribute_id'],$settings['attribute']['required'])) echo "checked=checked"; ?> type="checkbox" name="data[attribute][required][]" value="<?php echo $item['attribute_id']; ?>" /></td>
                      <td><?php echo $item['name']; ?></td>
                      <td><?php echo $item['attribute_group']; ?></td>
<!--                      <td><input type="text" name="attribute_default[<?php //echo $item['attribute_id']; ?>]" value="" /></td>-->
                      <?php foreach($languages->rows as $language): ?>
                      <td><input <?php if($language['code']!=$default_langaue) echo 'disabled=true'; ?> class="_ia<?php echo $language['code']; ?>" type="text" name="data[attribute][default][<?php echo $language['code']; ?>][<?php echo $item['attribute_id']; ?>]" value="<?php if($edit && isset($settings['attribute']['default'][$language['code']][$item['attribute_id']])) echo $settings['attribute']['default'][$language['code']][$item['attribute_id']]; ?>" /></td>
                      <?php endforeach; ?>
                  </tr>
                  <?php endforeach; ?>
              </tbody>
          </table>
      </div>
          <div class="tb" id="tab-options">
              
              <table>
                  <tr>
                      <td><input <?php if($edit && isset($settings['option']['enable'])) echo "checked=checked"; ?> type="checkbox" name="data[option][enable]" value="1" /></td>
                      <td>Enable Product Options in google sheet</td>
                  </tr>
              </table>   
              
              <table class="form">
              
              <tbody>
                    <tr>
                        <td>Default Option Value</td>
                        <td><input type="text" name="data[option][option_value]" id="option_value_text" value="<?php if($edit && isset($settings['option']['option_value'])) echo $settings['option']['option_value']; ?>" /></td>
                    </tr>
                    <tr>
                        <td>Option Type</td>
                        <td>
                        <select name="data[option][type]">
                            <optgroup label="Choose">
                                    <option <?php if($edit && isset($settings['option']['type']) && $settings['option']['type']=='select') echo "selected=selected"; ?> value="select">Select</option>
                                    <option <?php if($edit && isset($settings['option']['type']) && $settings['option']['type']=='radio') echo "selected=selected"; ?> value="radio">Radio</option>
                                    <option <?php if($edit && isset($settings['option']['type']) && $settings['option']['type']=='checkbox') echo "selected=selected"; ?> value="checkbox">Checkbox</option>
                                    <option <?php if($edit && isset($settings['option']['type']) && $settings['option']['type']=='image') echo "selected=selected"; ?> value="image">Image</option>
                            </optgroup>
                            <optgroup label="Input">
                                <option <?php if($edit && isset($settings['option']['type']) && $settings['option']['type']=='text') echo "selected=selected"; ?> value="text">Text</option>
                                <option <?php if($edit && isset($settings['option']['type']) && $settings['option']['type']=='textarea') echo "selected=selected"; ?> value="textarea">Textarea</option>
                                </optgroup>
                            <optgroup label="File">
                                <option <?php if($edit && isset($settings['option']['type']) && $settings['option']['type']=='file') echo "selected=selected"; ?> value="file">File</option>
                            </optgroup>
                            <optgroup label="Date">
                                <option <?php if($edit && isset($settings['option']['type']) && $settings['option']['type']=='date') echo "selected=selected"; ?> value="date">Date</option>
                                <option <?php if($edit && isset($settings['option']['type']) && $settings['option']['type']=='time') echo "selected=selected"; ?> value="time">Time</option>
                                <option <?php if($edit && isset($settings['option']['type']) && $settings['option']['type']=='datetime') echo "selected=selected"; ?> value="datetime">Date &amp; Time</option>
                            </optgroup>
                        </select>
                        </td>
                    </tr>
                    <tr>
                        <td>Quantity</td>
                        <td><input type="text" name="data[option][quantity]" value="<?php if($edit && isset($settings['option']['quantity'])) echo $settings['option']['quantity']; ?>" /></td>
                    </tr>
                    <tr>
                        <td>Required</td>
                        <td><select name="data[option][required]">
                                <option <?php if($edit && isset($settings['option']['quantity']) && $settings['option']['quantity']==0) echo "selected=selected"; ?> value="0">No</option>
                                <option <?php if($edit && isset($settings['option']['quantity']) && $settings['option']['quantity']==1) echo "selected=selected"; ?> value="1">Yes</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td>Subtract Stock:</td>
                        <td><select name="data[option][substract_stock]">
                                <option <?php if($edit && isset($settings['option']['quantity']) && $settings['option']['substract_stock']==1) echo "selected=selected"; ?> value="1">Yes</option>
                                <option <?php if($edit && isset($settings['option']['quantity']) && $settings['option']['substract_stock']==0) echo "selected=selected"; ?> value="0">No</option>
                              </select>
                        </td>
                    </tr>
                    <tr>
                        <td>Price</td>
                        <td><select name="data[option][pre_price]">
                                <option <?php if($edit && isset($settings['option']['pre_price']) && $settings['option']['pre_price']=='+') echo "selected=selected"; ?> value="+">+</option>
                                <option <?php if($edit && isset($settings['option']['pre_price']) && $settings['option']['pre_price']=='-') echo "selected=selected"; ?> value="-">-</option>
                            </select>
                            <input type="text" value="<?php if($edit && isset($settings['option']['price'])) echo $settings['option']['price']; ?>" name="data[option][price]" />
                        </td>
                    </tr>
                    <tr>
                        <td>Point</td>
                        <td><select name="data[option][point_prefix]">
                                <option <?php if($edit && isset($settings['option']['point_prefix']) && $settings['option']['point_prefix']=='+') echo "selected=selected"; ?> value="+">+</option>
                                <option <?php if($edit && isset($settings['option']['point_prefix']) && $settings['option']['point_prefix']=='-') echo "selected=selected"; ?> value="-">-</option>
                            </select>
                            <input type="text" value="<?php if($edit && isset($settings['option']['point'])) echo $settings['option']['point']; ?>" name="data[option][point]" />
                        </td>
                    </tr>
                    <tr>
                        <td>Weight</td>
                        <td><input value="<?php if($edit && isset($settings['option']['weight'])) echo $settings['option']['weight']; ?>" type="text" name="data[option][weight]"/></td>
                    </tr>
              </tbody>
              </table>
          </div>
          
          <div class="tb" id="tab-discount">
              <table>
                  <tr>
                      <td><input <?php if($edit && isset($settings['discount']['enable'])) echo "checked=checked"; ?> type="checkbox" name="data[discount][enable]" value="1" /></td>
                      <td>Enable Product Discount in google sheet</td>
                  </tr>
              </table>   
              
              <table class="form">
                    <tbody>
                        <tr>
                        <td>Default Customer Group</td>  
                        <td><select name="data[discount][customer_group]">
                                <?php foreach($customer_groups as $customer): ?>
                                    <option <?php if($edit && ($settings['discount']['customer_group'] ==$customer['customer_group_id']) ) echo "selected=selected"; ?> value="<?php echo $customer['customer_group_id']; ?>"><?php echo $customer['name']; ?></option>
                                <?php endforeach; ?>
                            </select></td>
                            <td><input type="checkbox" <?php if($edit && isset($settings['discount']['required']['customer_group'])) echo "checked=checked"; ?> value="1" name="data[discount][required][customer_group]" /></td>
                        </tr>
                        <tr>
                            <td>Quantity</td>
                            <td><input value="<?php if($edit && isset($settings['discount']['quantity'])) echo $settings['discount']['quantity']; ?>" name="data[discount][quantity]" /></td>
                            <td><input type="checkbox"  <?php if($edit && isset($settings['discount']['required']['quantity'])) echo "checked=checked"; ?> value="1" name="data[discount][required][quantity]" /></td>
                        </tr>
                        
                        <tr>
                            <td>Priority</td>
                            <td><input value="<?php if($edit && isset($settings['discount']['priority'])) echo $settings['discount']['priority']; ?>" name="data[discount][priority]" /></td>
                            <td><input type="checkbox"  <?php if($edit && isset($settings['discount']['required']['priority'])) echo "checked=checked"; ?> value="1" name="data[discount][required][priority]" /></td>
                        </tr>
                        
                        <tr>
                            <td>Price</td>
                            <td><input value="<?php if($edit && isset($settings['discount']['price'])) echo $settings['discount']['price']; ?>" name="data[discount][price]" /></td>
                          
                            <td><input type="checkbox"  <?php if($edit && isset($settings['discount']['required']['price'])) echo "checked=checked"; ?> value="1" name="data[discount][required][price]" /></td>
                        </tr>
                        
                        <tr>
                            <td>Date Start</td>
                            <td><input value="<?php if($edit && isset($settings['discount']['date_start'])) echo $settings['discount']['date_start']; ?>" name="data[discount][date_start]" /></td>
                            <td><input type="checkbox"  <?php if($edit && isset($settings['discount']['required']['date_start'])) echo "checked=checked"; ?> value="1" name="data[discount][required][date_start]" /></td>
                        </tr>
                        
                        <tr>
                            <td>Date End</td>
                            <td><input value="<?php if($edit && isset($settings['discount']['date_end'])) echo $settings['discount']['date_end']; ?>" name="data[discount][date_end]" /></td>
                            <td><input type="checkbox" <?php if($edit && isset($settings['discount']['required']['date_end'])) echo "checked=checked"; ?> value="1" name="data[discount][required][date_end]" /></td>
                        </tr>
                    </tbody>
                  
              </table>
          </div>
          <div class="tb" id="tab-special">
              
              <table>
                  <tr>
                      <td><input <?php if($edit && isset($settings['special']['enable'])) echo "checked=checked"; ?> type="checkbox" name="data[special][enable]" value="1" /></td>
                      <td>Enable Special Discount in google sheet</td>
                  </tr>
              </table>   
              
              <table class="form">
                    <tbody>
                        <tr>
                        <td>Default Customer Group</td>
                        <td><select name="data[special][customer_group]">
                                <?php foreach($customer_groups as $customer): ?>
                                    <option <?php if($edit && ($settings['special']['customer_group'] == $customer['customer_group_id']) ) echo "selected=selected"; ?> value="<?php echo $customer['customer_group_id']; ?>"><?php echo $customer['name']; ?></option>
                                <?php endforeach; ?>
                            </select></td>
                            <td><input type="checkbox" <?php if($edit && isset($settings['special']['required']['customer_group_id'])) echo 'checked=checked'; ?> value="1" name="data[special][required][customer_group_id]" /></td>
                        </tr>
                        <tr>
                            <td>Quantity</td>
                            <td><input value="<?php if($edit && isset($settings['special']['quantity'])) echo $settings['special']['quantity']; ?>" name="data[special][quantity]" /></td>
                            <td><input type="checkbox" <?php if($edit && isset($settings['special']['required']['quantity'])) echo 'checked=checked'; ?> value="1" name="data[special][required][quantity]" /></td>
                        </tr>
                        
                        <tr>
                            <td>Priority</td>
                            <td><input value="<?php if($edit && isset($settings['special']['priority'])) echo $settings['special']['priority']; ?>" name="data[special][priority]" /></td>
                            <td><input type="checkbox" value="1" <?php if($edit && isset($settings['special']['required']['priority'])) echo 'checked=checked'; ?> name="data[special][required][priority]" /></td>
                        </tr>
                        
                        <tr>
                            <td>Price</td>
                            <td><input value="<?php if($edit && isset($settings['special']['price'])) echo $settings['special']['price']; ?>" name="data[special][price]" /></td>
                            <td><input type="checkbox" <?php if($edit && isset($settings['special']['required']['price'])) echo 'checked=checked'; ?> value="1" name="data[special][required][price]" /></td>
                        </tr>
                        
                        <tr>
                            <td>Date Start</td>
                            <td><input value="<?php if($edit && isset($settings['special']['date_start'])) echo $settings['special']['date_start']; ?>" name="data[special][date_start]" /></td>
                            <td><input type="checkbox" <?php if($edit && isset($settings['special']['required']['date_start'])) echo 'checked=checked'; ?> value="1" name="data[special][required][date_start]" /></td>
                        </tr>
                        
                        <tr>
                            <td>Date End</td>
                            <td><input value="<?php if($edit && isset($settings['special']['date_end'])) echo $settings['special']['date_end']; ?>" name="data[special][date_end]" /></td>
                            <td><input type="checkbox" <?php if($edit && isset($settings['special']['required']['date_end'])) echo 'checked=checked'; ?> value=1 name="data[special][required][date_end]" /></td>
                        </tr>
                    </tbody>
                  
              </table>
          </div>
          
            <div class="tb" id="tab-misc">
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
          
            <?php if($edit): ?>
            <input type="hidden" name="id" value="<?php echo $id; ?>"/>
            <?php endif; ?>
            </div>
      </form>
          </div>
  </div>
</div>
<style type="text/css">
    a{text-decoration: none; color: #000}
    .htabs{float: left; width: 100%}
    .htabs ul{float: left; width: 100%}
    .htabs li{float: left; list-style: none; padding: 5px;}
    .ui-datepicker{background: #fff;}
    .tb{float:left;}
</style>
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
<script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.9.1/jquery-ui.min.js"></script>
<script type="text/javascript">
$(document).ready(function(){
  $('.htabs').tabs();
  $('.content').fadeIn();
  $('.ld').hide();
  
  $( ".date" ).datepicker({
      dateFormat: "yy-mm-dd"
  });
  
  $('._ev').change(function(){
    var l = $(this).attr('lan');
    if($(this).is(':checked')){
        $(this).closest('table').find('._i'+l).attr('disabled',false).addClass('_in');
        
    }else{
        $('._i'+l).attr('disabled',true).addClass('_out');
    }
});
});

function onSuccess(balance) {
//    alert('action complete');
google.script.host.close();
}

function onFailure(e){
    alert('ERROR: S'+e);
}
</script>