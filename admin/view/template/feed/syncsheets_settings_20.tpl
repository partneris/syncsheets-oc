<?php echo $header; ?>
    <style type="text/css">
    .loading{
        background-image: url('view/image/loading.gif');
        background-repeat: no-repeat;
        height: 12px; width: 12px; float: right; margin: 3px 9px;
    }
    .ld{
        background-image: url('view/image/loading.gif');
        background-repeat: no-repeat;
        height: 12px; width: 12px;
         float: left;
        position: relative;
        right: 12px;
        top: 16px;
        z-index: 9999
    }
    .files{
    height: 300px;
    list-style: none outside none;
    overflow-y: scroll;
    width: 600px;}
    .files li{float: left}
</style>
    <?php echo $column_left; ?>

<div id="content">
  <div class="page-header">
    <div class="container-fluid">
      <div class="pull-right">
        <button type="submit" form="form" data-toggle="tooltip" title="<?php echo $button_save; ?>" class="btn btn-primary"><i class="fa fa-save"></i></button>
        <a href="<?php echo $cancel; ?>" data-toggle="tooltip" title="<?php echo $button_cancel; ?>" class="btn btn-default"><i class="fa fa-reply"></i></a></div>
      <h1><?php echo $heading_title; ?></h1>
      <ul class="breadcrumb">
        <?php foreach ($breadcrumbs as $breadcrumb) { ?>
        <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
        <?php } ?>
      </ul>
    </div>
  </div>
  <div class="panel panel-default">
    <div class="panel-heading">
      <h3 class="panel-title"><i class="fa fa-pencil"></i> <?php echo $text_edit; ?></h3>
    </div>
    <div class="panel-body">
      <div class="container-fluid">
          <?php if ($error_warning) { ?>
            <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo $error_warning; ?>
              <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
        <?php } ?>
          
      <?php if(!$install): ?>
      <div class="buttons">
      <a onclick="$('#form').submit();" class="button">Delete</a>
      <a href="index.php?route=feed/syncsheets/setting&token=<?php echo $_GET['token'] ?>" class="button">Add Setting</a>
      <a href="<?php echo $cancel; ?>" class="button"><?php echo $button_cancel; ?></a>
      </div>
      <?php endif; ?>
   
        
        <ul class="nav nav-tabs">
            <li class="active"><a data-toggle='tab' href="#tab-sheets">Sheets</a></li>
            <li><a data-toggle='tab' href="#tab-settings">Sheets Setting</a></li>
            <li><a data-toggle='tab' href="#tab-about">About</a></li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane active" id="tab-sheets">
            <?php if(!$install): ?>
            <a href="#" id="addblank">Create New Sheet</a>
            <?php endif; ?>
            <form action="" method="post">
                <div class="table-responsive">
                <table class="table table-striped table-bordered table-hover">
                    <thead>
                        <tr>
                            <td class="left">Sr.No.</td>
                            <td class="left">Title</td>
                            <td class="left">Sheet</td>
                            <td class="left">Setting</td>
                            <td class="left">Last Sync</td>
                            <td class="left">Last Updated</td>
                            <td class="left">Action</td>
                        </tr>
                    </thead>
                    <tbody id="sheetlist">
                        <?php if($sheets): foreach($sheets as $key => $sheet): ?>
                            <tr>
                                <td class="left"><?php echo $key+1; ?></td>
                                <td class="left"><?php echo $sheet['title']; ?></td>
                                <td class="left"><a target="_blank" href="https://docs.google.com/spreadsheets/d/<?php echo $sheet['key']; ?>/edit">Launch</a></td>
                                <td class="left"><?php echo $sheet['setting']; ?></td>
                                <td class="left"><?php echo $sheet['last_sync']; ?></td>
                                <td class="left"><?php echo $sheet['updated']; ?></td>
                                <td class="left">
                                    <?php if(!$sheet['status']) : ?><a stid="<?php echo $sheet['setting_id']; ?>" class="pub" sid="<?php echo $sheet['id']; ?>" href="#">Publish</a> | <?php endif; ?>
                                    <a  class="edSheet" sid="<?php echo $sheet['id']; ?>" href="#">Edit</a> | 
                                    <a class="delSheet" sid="<?php echo $sheet['id']; ?>" href="#">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr>
                                <td colspan="7">No Sheet found!</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </form>
        </div>
            <div class="tab-pane" id="tab-settings">
            <form id="form" action="<?php echo $action; ?>" method="post">
                <div class="table-responsive">
                <table class="table table-striped table-bordered table-hover">
                <thead>
                  <tr>
                        <td width="1" style="text-align: center;"><input type="checkbox" onclick="$('input[name*=\'selected\']').attr('checked', this.checked);" /></td>
                        <td class="left">Setting</td>
                        <td class="right">Action</td>
                  </tr>
                </thead>
                <tbody>
                  <?php if ($settings) { ?>
                  <?php foreach ($settings as $setting) { ?>
                  <tr>
                    <td><input type="checkbox" name="selected[]" value="<?php echo $setting['id']; ?>" /></td>
                    <td class="left"><?php echo $setting['title']; ?></td>
                    <td class="right"><a href="index.php?route=feed/syncsheets/setting&token=<?php echo $_GET['token'];?>&id=<?php echo $setting['id']; ?>">Edit</a> | <a href="#">Delete</a></td>
                  </tr>
                  <?php } ?>
                  <?php } else { ?>
                  <tr>
                    <td class="center" colspan="8">No settings found!</td>
                  </tr>
                  <?php } ?>
                </tbody>
                </table>
                </div>
            </form>
        </div>
        <div class="tab-pane" id="tab-about">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                <tr><td class="left"><b>Current Syncsheet Version</b></td><td class="left"><?php echo GSS_VERSION; ?></td></tr>
                <tr class="vcheck">
                    <td class="left"><b>Check for updates</b></td>
                    <td class="left"><a class="_vc" href="#">Click Here</a> <span style="display: none;" class="loading"></span></td>
                </tr>
            </table>
            </div>
            <div id="installNew">
                
            </div>
        </div>
        </div>
  


<div style="display:none;" id="sheetForm">
    <form id="editSheetform" action="">
        <table class="form" id="option-form">
            <tbody >
            <tr>
                <td>Title</td>
                <td><input id="sTitle" type="text" name="title" /></td>
            </tr>
            <tr>
                <td>Spread Sheet</td>
                <td><input id="sKey" type="text" name="key"/></td>
            </tr>
            
            <tr>
                <td>Select Field Mapping</td>
                <td><select id="sSetting" name="setting_id">
                        <?php foreach($settings as $setting): ?>
                        <option value="<?php echo $setting['id']; ?>"><?php echo $setting['title']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                
            </tr>
            
        </tbody>
        </table>
    </form>
    
    <form title="Add new sheet" id="addnewsheet" action="">
        <table class="form" id="option-form">
            <tbody >
            <tr>
                <td>Title</td>
                <td><input style="width: 200px;" id="sheetitle" type="text" name="sheettitle" /></td>
            </tr>
            <tr>
                <td>Select Settings</td>
                <td><select id="sSetting" name="setting_id">
                        <?php foreach($settings as $setting): ?>
                        <option value="<?php echo $setting['id']; ?>"><?php echo $setting['title']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                
            </tr>
        </tbody>
        </table>
    </form>
</div>

<?php if($install): ?>
<div style="display: none;" id="installation">
    <div id="step1">
        <div class="content box">
            <h2>Welcome to <b>Syncsheets</b></h2>
            <hr/>
            <p>End Users may use and import Scripts or Add-ons created by third parties, including Google. Scripts and Add-ons are the sole responsibility of the entity that makes it available. Google makes no representations about the performance, quality, content, or continued availability of any Script or Add-on.</p>

            <p>Google does not promise that any Script or Add-on will work for your purposes, or that it is free from viruses, bugs, or other defects. Scripts and Add-ons are provided "as is" and without warranty of any kind. Google provides no express warranties, guarantees and conditions with regard to the Scripts and Add-ons. To the extent permitted under applicable law, Google excludes the implied warranties and conditions of merchantability, fitness for a particular purpose, workmanlike effort, title and non-infringement.</p>

            <p>Scripts and Add-ons are subject to change at any time. Using or importing any Script or Add-ons is at your own risk. You should only run the script if you trust the developer of the Script or Add-on, as you are solely responsible for any compromise or loss of data that may result from using this Script or Add-on.</p>
        </div>
    </div>
    
    <div id="step2">
        <div class="content box">
            <form class="inform" action="" method="post" >
                &nbsp;&nbsp;<b>Attention! Following Information will be used. You can change/remove.</b>
                <table class="form">
                    <tr>
                        <td>Store Name</td>
                        <td><input size="30" type="text" name="inf[store]" value="<?php echo $store_name; ?>" /></td>
                    </tr>
                    <tr>
                        <td>Store Owner</td>
                        <td><input size="30" type="text" name="inf[owner]" value="<?php echo $store_owner; ?>" /></td>
                    </tr>
                    <tr>
                        <td> Address</td>
                        <td><textarea rows="3" cols="30" name="inf[address]"><?php echo $store_address; ?></textarea></td>
                    </tr>
                    <tr>
                        <td>Email</td>
                        <td><input size="30" type="text" name="inf[email]" value="<?php echo $store_email; ?>" /></td>
                    </tr>
                    <tr>
                        <td>Telephone</td>
                        <td><input size="30" type="text" name="inf[telephone]" value="<?php echo $store_telephone; ?>" /></td>
                    </tr>
                    <tr>
                        <td>Store Identifier</td>
                        <td><input size="40" readonly="true" type="text" name="inf[server]" value="<?php echo HTTP_SERVER; ?>" /></td>
                    </tr>
                </table>
                <input type="hidden" name="action" value="signup" />
            </form>
        </div>
    </div>
    <div id="step3">
        <center><h1>You are ready now!</h1>
        <p><a class="activate" target="_blank">Click here</a> to Activate your account</p>
        <em>This action requires you must have google account.</em>
        </center>
    </div>
</div>
<script type="text/javascript">
    $(document).ready(function(){
    console.log('Initialize Install process.');
    
    var xhr = 'index.php?route=feed/syncsheets/ajax&token=<?php echo $_GET['token']; ?>';
     $("#step1").data('current', this).dialog({
         title:"Syncsheets: Terms & Conditions",modal:true,width:700,height:400,closeOnEscape: false,open: function(event, ui) { $(".ui-dialog-titlebar-close", ui.dialog || ui).hide(); },
         buttons:{
               'I Agree':function(){
                   $(this).dialog("close");
                   step2();
               },
                Disagree: function() {
                    $(this).dialog( "close" );
                }
            }
     });
     function step2(){
         $("#step2" ).data('current', this).dialog({
         title:"Syncsheets: Installation - Step 2",modal:true,width:700,height:400,closeOnEscape: false,open: function(event, ui) { $(".ui-dialog-titlebar-close", ui.dialog || ui).hide(); },
         buttons:{
               'Next':function(){
                  $('.ui-dialog-buttonset').prepend('<span class="ld" />')
                   $.post(xhr,$('.inform :input'),function(r){ r=$.parseJSON(r);
                       if(r.status){
                           $('.ld').remove();
                           $('#step3 .activate').attr('href',r.link);
                           step3();
                       }
                   });
               },
                'Close': function() {$(this).dialog( "close" );}
            }
        });
     }
     
     function step3(){
         $("#step3" ).data('current', this).dialog({
         title:"Syncsheets: Installation - Step 3",modal:true,width:700,height:400,closeOnEscape: false,open: function(event, ui) { $(".ui-dialog-titlebar-close", ui.dialog || ui).hide(); },
         buttons:{
               'Finish':function(){
                  var link = $('#step3 .activate').attr('href');
                  window.open(link, '_blank');
               },
            }
        });
     }
     
     });
</script>
<?php endif; ?>
<script type="text/javascript">
var ajax = 'index.php?route=feed/syncsheets/ajax&token=<?php echo $_GET['token']; ?>';
    var $form = $('#addSheetform');
    $(document).ready(function(){
 
    $('#addblank').click(function(e){ e.preventDefault();
        $("#addnewsheet" ).data('current', this).dialog({width:400,
            open:function(){
                var $this = $.data(this,'current');
            },
            beforeClose: function( event, ui ) {
                var $this = $.data(this, 'current');
                $($this).next().val($form.serialize());
                
            },buttons:{
               'Add Sheet':function(){
                   var title=$('.ui-dialog #sheetitle').val();
                   var setting=$('.ui-dialog #sSetting').val();
                   var dia = $(this);
                   $.post(ajax,{action:'createsheet',title:title,setting_id:setting},function(r){ r=$.parseJSON(r);
                       dia.dialog("close");
                       if(r.error)
                           alert(r.error)
                       else
                            window.location.href="index.php?route=feed/syncsheets&token=<?php echo $_GET['token']; ?>&msg=1";
                   });
               },
                Cancel: function() {
                    $( this ).dialog( "close" );
                }
            }
        });
    });
    /////////////////Version check////////////////
    $('._vc').click(function(e){ e.preventDefault(); $('.loading').show();
        $.post(ajax,{action:'version_check'},function(r){ r=$.parseJSON(r);
            $('.loading, .vcheck').hide();
            if(r.new){
            var html = '<div class="version">';
                html +='        <center>';
                html +='                  <h1>'+r.msg+', <br/>';
                html +='                       Click the install button below to get the latest version.<br/>';
                html +='                       <a class="_install" href="#">Install Now</a><span style="display:none;" class="loading"></span>';
                html +='                  </h1>';
                html +='                       <i>'+r.version.title +' : v.'+r.version.version_no +'. {Requires : GSS V-'+r.version.gss_version +'}</i>';
                html +='               </center>';
                html +='          </div>';
                $('#installNew').html(html);
            }else{
                $('#installNew').html('<h1>'+r.msg+'</h1>');
            }
        });
    });
    
    $(document).on('click','._install',function(e){ e.preventDefault(); $('.loading').show();
        $.post(ajax,{action:'update_version'},function(r){ r=$.parseJSON(r);
            $('.loading').hide();
            var files = r.files;
            var html = '<i>Following files were updated!</i><br/><ul class=files>';
            for(i in files){ var item=files[i];
                html += '<li>'+item+'</li>';
            }
            html += '</ul>';
            $('#installNew').html('<center><h1>'+r.msg+'</h1>'+html+'</center>');
        });
    });
    
    
    ///////////////////////////////////
});    

    $(document).on('click','.edSheet',function(e){e.preventDefault();
        $("#editSheetform").data('current', this).dialog({width:500,
            open:function(){
                var $this = $.data(this,'current');
                $.post(ajax,{action:'getSheet',id:$($this).attr('sid')},function(r){r=$.parseJSON(r);
                    $("#editSheetform #sTitle").val(r.sheet.title);
                    $("#editSheetform #sKey").val(r.sheet.key);
                    $("#editSheetform #sSetting").val(r.sheet.setting_id);
                });
            },
            beforeClose: function( event, ui ) {
                var $this = $.data(this, 'current');
                $($this).next().val($form.serialize());
                
            },buttons:{
               'Edit Sheet':function(){
                   var $this = $.data(this, 'current');
                   var title=$('#editSheetform #sTitle').val();
                   var key=$('#editSheetform #sKey').val();
                   var setting=$('#editSheetform #sSetting').val();
                   var dia = $(this);
                   $.post(ajax,{action:'editSheet',title:title,key:key,setting_id:setting,id:$($this).attr('sid')},function(r){ r=$.parseJSON(r);
                       dia.dialog("close");
                       window.location.href="index.php?route=feed/syncsheets&token=<?php echo $_GET['token']; ?>&msg=3";
                   });
               },
                Cancel: function() {
                    $( this ).dialog( "close" );
                }
            }
        });
    });
    
    getSheetList=function(r){
    var tr = '';
        for(i in r){
            tr += '<tr><td class="left">'+(i)+'</td><td class="left">'+r[i].title+'</td><td class="left">'+r[i].setting+'</td><td class="left">'+r[i].key+'</td><td class="left">'+r[i].last_sync+'</td><td class="left">'+r[i].updated+'</td><td class="left">';
            if(!r[i].status)
            tr += '<a class="pub" sid="'+r[i].id+'">Publish</a>';
            tr += '<a class="edSheet" sid="'+r[i].id+'">Edit</a><a sid="'+r[i].id+'" href="">Delete</a></td>';
        }
        $('#sheetlist').html(tr);
    }
    
    
   $(document).on('click','.delSheet',function(e){
       e.preventDefault(); 
       if(confirm('Are you sure you want to delete this spreadsheet')){
           $('#loader').dialog({ height: 150,width:200}); 
               var sheet = $(this).attr('sid');
               $.post(ajax,{action:'delSheet','id':sheet},function(){
                   window.location.href="index.php?route=feed/syncsheets&token=<?php echo $_GET['token']; ?>&msg=4";
               });
       }
       
   });
   
   

   
  
</script>
        </div>
    </div>
  </div>
</div>
<link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.2/themes/smoothness/jquery-ui.css" />
<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.2/jquery-ui.min.js"></script>
<?php echo $footer; ?>