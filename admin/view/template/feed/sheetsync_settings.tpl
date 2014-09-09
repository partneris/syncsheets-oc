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
      
      <a onclick="$('#form').submit();" class="button">Delete</a>
      <a href="index.php?route=feed/sheetsync/setting&token=<?php echo $_GET['token'] ?>" class="button">Add Setting</a>
      <a href="<?php echo $cancel; ?>" class="button"><?php echo $button_cancel; ?></a>
      </div>
    </div>
    <div class="content">
        
        <div id="tabs" class="htabs">
            <a href="#tab-sheets">Sheets</a>
            <a href="#tab-settings">Sheets Setting</a>
            <a href="#tab-about">About</a>
        </div>
        <div id="tab-settings">
            <form id="form" action="<?php echo $action; ?>" method="post">
                <table class="list">
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
                    <td class="right"><a href="index.php?route=feed/sheetsync/setting&token=<?php echo $_GET['token'];?>&id=<?php echo $setting['id']; ?>">Edit</a> | <a href="#">Delete</a></td>
                  </tr>
                  <?php } ?>
                  <?php } else { ?>
                  <tr>
                    <td class="center" colspan="8">No settings found!</td>
                  </tr>
                  <?php } ?>
                </tbody>
                </table>
            </form>
        </div>
        
        <div id="tab-sheets">
            <a href="#" id="addblank">Create New Sheet</a>
            <form action="" method="post">
                <table class="list">
                    <thead>
                        <tr>
                            <td class="left">Sr.No.</td>
                            <td class="left">Title</td>
                            <td class="left">Setting</td>
                            <td class="left">Sheet</td>
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
                                <td class="left"><?php echo $sheet['setting']; ?></td>
                                <td class="left"><a target="_blank" href="https://docs.google.com/spreadsheets/d/<?php echo $sheet['key']; ?>/edit">Launch</a></td>
                                <td class="left"><?php echo $sheet['last_sync']; ?></td>
                                <td class="left"><?php echo $sheet['updated']; ?></td>
                                <td class="left">
                                    <?php if(!$sheet['status']) : ?><a stid="<?php echo $sheet['setting_id']; ?>" class="pub" sid="<?php echo $sheet['id']; ?>" href="#">Publish</a> | <?php endif; ?>
                                    <a  class="edSheet" sid="<?php echo $sheet['id']; ?>" href="#">Edit</a> | 
                                    <a class="delSheet" sid="<?php echo $sheet['id']; ?>" href="#">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </form>
        </div>
        
        <div id="tab-about">
            <table>
                <tr><td class="left"><?php echo $text_module_version; ?>: </td><td class="left"><?php echo GSS_VERSION; ?></td></tr>
            </table>
        </div>
    </div>
  </div>
</div>

<div style="display:none;" id="sheetForm">
    <form id="addSheetform" action="">
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
        </tbody>
        </table>
    </form>
</div>

<script type="text/javascript">
$('#tabs a').tabs();
var ajax = 'index.php?route=feed/sheetsync/ajax&token=<?php echo $_GET['token']; ?>';
    var $form = $('#addSheetform');
    $(document).ready(function(){
 
    $('#addblank').click(function(e){ e.preventDefault();
        $("#addnewsheet" ).data('current', this).dialog({width:300,
            open:function(){
                var $this = $.data(this,'current');
            },
            beforeClose: function( event, ui ) {
                var $this = $.data(this, 'current');
                $($this).next().val($form.serialize());
                
            },buttons:{
               'Add Sheet':function(){
                   var title=$('#sheetitle').val();
                   var dia = $(this);
                   $.post(ajax,{action:'addTemplate',title:title},function(r){ r=$.parseJSON(r);
                       dia.dialog("close");
                       if(r.error)
                           alert(r.error)
                       else
                            window.location.href="index.php?route=feed/sheetsync&token=<?php echo $_GET['token']; ?>&msg=1";
                   });
               },
                Cancel: function() {
                    $( this ).dialog( "close" );
                }
            }
        });
    });
    /////////////////////////////////
    
});    

    $(document).on('click','.edSheet',function(e){e.preventDefault();
        $("#addSheetform").data('current', this).dialog({width:500,
            open:function(){
                var $this = $.data(this,'current');
                $.post(ajax,{action:'getSheet',id:$($this).attr('sid')},function(r){r=$.parseJSON(r);
                    $("#addSheetform #sTitle").val(r.sheet.title);
                    $("#addSheetform #sKey").val(r.sheet.key);
                    $("#addSheetform #sSetting").val(r.sheet.setting_id);
                });
            },
            beforeClose: function( event, ui ) {
                var $this = $.data(this, 'current');
                $($this).next().val($form.serialize());
                
            },buttons:{
               'Edit Sheet':function(){
                   var $this = $.data(this, 'current');
                   var title=$('#sTitle').val();
                   var key=$('#sKey').val();
                   var setting=$('#sSetting').val();
                   var dia = $(this);
                   $.post(ajax,{action:'editSheet',title:title,key:key,setting_id:setting,id:$($this).attr('sid')},function(r){ r=$.parseJSON(r);
                       dia.dialog("close");
                       window.location.href="index.php?route=feed/sheetsync&token=<?php echo $_GET['token']; ?>&msg=3";
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
                   window.location.href="index.php?route=feed/sheetsync&token=<?php echo $_GET['token']; ?>&msg=4";
               });
       }
       
   });
   
   

   
  
</script>


<div style="display: none;" id="loader" title="Please wait...">Please wait while the task is complete. Do not press refresh button or back button.</div>
<?php echo $footer; ?>