<?php
    if(!defined("CORE_FOLDER")) die();

    $lang           = $module->lang;
    $config         = $module->config;

    Helper::Load(["Money"]);

    $apikey               = Filter::init("POST/apikey","hclear");
    $apitoken             = Filter::init("POST/apitoken","hclear");
    $pixduedate           = Filter::init("POST/pixduedate","hclear");
    $cpfcnpjfield         = Filter::init("POST/cpfcnpjfield","hclear");
    $commission_rate      = Filter::init("POST/commission_rate","amount");
    $commission_rate      = str_replace(",",".",$commission_rate);


    $sets           = [];

    if($apikey != $config["settings"]["apikey"])
        $sets["settings"]["apikey"] = $apikey;

    if($apitoken != $config["settings"]["apitoken"])
        $sets["settings"]["apitoken"] = $apitoken;
    
    if($pixduedate != $config["settings"]["pixduedate"])
        $sets["settings"]["pixduedate"] = $pixduedate;
    
    if($cpfcnpjfield != $config["settings"]["cpfcnpjfield"])
        $sets["settings"]["cpfcnpjfield"] = $cpfcnpjfield;

    if($commission_rate != $config["settings"]["commission_rate"])
        $sets["settings"]["commission_rate"] = $commission_rate;


    if($sets){
        $config_result  = array_replace_recursive($config,$sets);
        $array_export   = Utility::array_export($config_result,['pwith' => true]);

        $file           = dirname(__DIR__).DS."config.php";
        $write          = FileManager::file_write($file,$array_export);

        $adata          = UserManager::LoginData("admin");
        User::addAction($adata["id"],"alteration","changed-payment-module-settings",[
            'module' => $config["meta"]["name"],
            'name'   => $lang["name"],
        ]);
    }

    echo Utility::jencode([
        'status' => "successful",
        'message' => $lang["success1"],
    ]);
