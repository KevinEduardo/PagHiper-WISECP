<?php
    if(!defined("CORE_FOLDER")) die();
    $LANG           = $module->lang;
    $CONFIG         = $module->config;
    $callback_url   = Controllers::$init->CRLink("payment",['PagHiperPix',$module->get_auth_token(),'callback']);
    $success_url    = Controllers::$init->CRLink("pay-successful");
    $failed_url     = Controllers::$init->CRLink("pay-failed");
?>
<form action="<?php echo Controllers::$init->getData("links")["controller"]; ?>" method="post" id="PagHiperPix">
    <input type="hidden" name="operation" value="module_controller">
    <input type="hidden" name="module" value="PagHiperPix">
    <input type="hidden" name="controller" value="settings">

    <div class="blue-info" style="margin-bottom:20px;">
        <div class="padding15">
            <i class="fa fa-info-circle" aria-hidden="true"></i>
            <p><?php echo $LANG["description"]; ?></p>
        </div>
    </div>

    <div class="formcon">
        <div class="yuzde30"><?php echo $LANG["apikey"]; ?></div>
        <div class="yuzde70">
            <input type="text" name="apikey" value="<?php echo $CONFIG["settings"]["apikey"]; ?>">
            <span class="kinfo"><?php echo $LANG["apikey-desc"]; ?></span>
        </div>
    </div>

    <div class="formcon">
        <div class="yuzde30"><?php echo $LANG["apitoken"]; ?></div>
        <div class="yuzde70">
            <input type="text" name="apitoken" value="<?php echo $CONFIG["settings"]["apitoken"]; ?>">
            <span class="kinfo"><?php echo $LANG["apitoken-desc"]; ?></span>
        </div>
    </div>

    <div class="formcon">
        <div class="yuzde30"><?php echo $LANG["pixduedate"]; ?></div>
        <div class="yuzde70">
            <input type="text" name="pixduedate" value="<?php echo $CONFIG["settings"]["pixduedate"]; ?>">
            <span class="kinfo"><?php echo $LANG["pixduedate-desc"]; ?></span>
        </div>
    </div>

    <div class="formcon">
        <div class="yuzde30"><?php echo $LANG["cpfcnpjfield"]; ?></div>
        <div class="yuzde70">
            <input type="text" name="cpfcnpjfield" value="<?php echo $CONFIG["settings"]["cpfcnpjfield"]; ?>">
            <span class="kinfo"><?php echo $LANG["cpfcnpjfield-desc"]; ?></span>
        </div>
    </div>

    <div class="formcon">
        <div class="yuzde30"><?php echo $LANG["commission-rate"]; ?></div>
        <div class="yuzde70">
            <input type="text" name="commission_rate" value="<?php echo $CONFIG["settings"]["commission_rate"]; ?>" style="width: 80px;">
            <span class="kinfo"><?php echo $LANG["commission-rate-desc"]; ?></span>
        </div>
    </div>

    
    <div class="formcon">
        <div class="yuzde30">Callback URL</div>
        <div class="yuzde70">
            <span style="font-size:13px;font-weight:600;" class="selectalltext"><?php echo $callback_url; ?></span>
        </div>
    </div>


    <div style="float:right;" class="guncellebtn yuzde30"><a id="PagHiperPix_submit" href="javascript:void(0);" class="yesilbtn gonderbtn"><?php echo $LANG["save-button"]; ?></a></div>

</form>


<script type="text/javascript">
    $(document).ready(function(){

        $("#PagHiperPix_submit").click(function(){
            MioAjaxElement($(this),{
                waiting_text:waiting_text,
                progress_text:progress_text,
                result:"PagHiperPix_handler",
            });
        });

    });

    function PagHiperPix_handler(result){
        if(result != ''){
            var solve = getJson(result);
            if(solve !== false){
                if(solve.status == "error"){
                    if(solve.for != undefined && solve.for != ''){
                        $("#PagHiperPix "+solve.for).focus();
                        $("#PagHiperPix "+solve.for).attr("style","border-bottom:2px solid red; color:red;");
                        $("#PagHiperPix "+solve.for).change(function(){
                            $(this).removeAttr("style");
                        });
                    }
                    if(solve.message != undefined && solve.message != '')
                        alert_error(solve.message,{timer:5000});
                }else if(solve.status == "successful"){
                    alert_success(solve.message,{timer:2500});
                }
            }else
                console.log(result);
        }
    }
</script>
