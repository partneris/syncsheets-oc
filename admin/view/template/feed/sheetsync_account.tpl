<?php echo $header; ?>
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
      <?php if($gs['google_spreadsheet_user'] && $gs['google_spreadsheet_pass'] && $gs['google_spreadsheet_sskey']): ?>
      	<a class="ex2gle button" href="#" class="button">Export To Google</a>
      <?php endif; ?>
      <a onclick="$('#form').submit();" class="button"><?php echo $button_save; ?></a>
      <a href="<?php echo $cancel; ?>" class="button"><?php echo $button_cancel; ?></a>
      </div>
    </div>
    <div class="content">
      <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form">
        <table class="form">
          <tr>
            <td><?php echo $entry_status; ?></td>
            <td>
              <select name="google_spreadsheet_status">
                    <option <?php if($gs['google_spreadsheet_status']==0) echo "selected=selected"; ?> value="0"><?php echo $text_disabled; ?></option>
                    <option <?php if($gs['google_spreadsheet_status']==1) echo "selected=selected"; ?> value="1"><?php echo $text_enabled; ?></option>
              </select>
            </td>
          </tr>
          
          <tr>
              <td>SheetSync Key</td>
            <td>
                <input style="width:310px" value="<?php echo $gs['ss_key']; ?>" type="text" name="ss_key" /><br/><span class="help">Sheetsync key is received while Registering your domain at sheetsync.com. If you don't have sheetsync key, please login to sheetsync.com and signUp now!</span>
            </td>
          </tr>
          
          <tr>
            <td>Client ID</td>
            <td>
                <input style="width:510px"  value="<?php echo $gs['client_id']; ?>" type="password" name="client_id" />
            </td>
          </tr>
          
          <tr>
            <td>Client Secret</td>
            <td>
                <input style="width:510px"  value="<?php echo $gs['client_secret']; ?>" type="password" name="client_secret" />
            </td>
          </tr>
          
          <tr>
              <td>Redirect URI</td>
            <td>
                <input style="width:510px" value="<?php echo HTTP_SERVER; ?>sheetsync_feed.php?action=oauth2callback" type="text" name="redirect_uri" readonly="true" /><br/>
                <span class="help">Enter this REDIRECT URI while creating app in google.</span>
            </td>
          </tr>
          
          <tr style="">
              <td>&nbsp;</td>
            <td>
                <div class="buttons">
                    <a class="sfx button" href="#">Generate Access Token</a>
                </div>
                </br>
                <span class="help">
                    <?php if(isset($gs['accesstoken']) && $gs['accesstoken']): ?>
                    Click the button above to regenerate a valid access token
                    <?php endif; ?>
                </span>
            </td>
          </tr>
          <?php if(isset($gs['accesstoken']) && $gs['accesstoken']): ?>
          <input type="hidden" value='<?php echo $gs['accesstoken']; ?>' name="accesstoken" />
          <?php endif; ?>
          
        </table>
      </form>
    </div>
  </div>
</div>

<script type="text/javascript">
    $('._xcc').change(function(){
       if($('input[name=google_spreadsheet_user]').val() && $('input[name=google_spreadsheet_pass]').val() && $('input[name=google_spreadsheet_sskey]').val()){
           $.post('index.php?route=feed/google_spreadsheet/verify&token=<?php echo $_GET['token']; ?>',{_u:$('input[name=google_spreadsheet_user]').val(),_p:$('input[name=google_spreadsheet_pass]').val(),_s:$('input[name=google_spreadsheet_sskey]').val()},function(r){
               var result = $.parseJSON(r);
               if(result.error){ alert(result.msg)}else if(!result.sheets){alert('Invalid SpreadSheet Key')}
               
               if(result.sheets){
                   var ul='<ul>'; var li='';
                   for(i in result.sheets){
                       li += '<li>'+result.sheets[i].name+'</li>';
                   }
                   ul += li + '</ul>';   
                   
                   $('.sheetList').html(ul);
                   $('.resultant').show();
               }
               
           });
       }
    });
    
    $(document).on('click','.installsheet',function(e){
    e.preventDefault();
        if(confirm('This will install new sheets to this spreadsheet! Are you sure to continue?')){
            $.post('index.php?route=feed/google_spreadsheet/verify&token=<?php echo $_GET['token']; ?>',{_u:$('input[name=google_spreadsheet_user]').val(),_p:$('input[name=google_spreadsheet_pass]').val(),_inst:true,_s:$('input[name=google_spreadsheet_sskey]').val()},function(r){
                var result = $.parseJSON(r);
               if(result.error){ alert(result.msg)}else if(!result.sheets){alert('Invalid SpreadSheet Key')}
               
               if(result.sheets){
                   var ul='<ul>'; var li='';
                   for(i in result.sheets){
                       li += '<li>'+result.sheets[i].name+'</li>';
                   }
                   ul += li + '</ul>';   
                   
                   $('.sheetList').html(ul);
                   $('.resultant').show();
               }
            })
        }
    });
    
    




$('.sfx').click(function(e){
    e.preventDefault();
    $.post('index.php?route=feed/sheetsync/ajax&token=<?php echo $_GET['token']; ?>',
        {
            action:'buildToken',
            client_id:$('input[name=client_id]').val(),
            client_secret:$('input[name=client_secret]').val(),
            ss_key:$('input[name=ss_key]').val(),
            redirect_uri:$('input[name=redirect_uri]').val(),
            google_spreadsheet_status:$('select[name=google_spreadsheet_status]').val()
        },
        function(response){
            res = $.parseJSON(response);
            if(res.url){
                window.open(res.url,'Google App Verification', 800, 600);
            }
        });
});
//callback();
</script>
<?php echo $footer; ?>