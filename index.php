<?php

    if(isset($_SERVER['HTTP_X_FORWARDED_URI'])){
        $_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_FORWARDED_URI'];
        //$_SERVER['PHP_SELF'] = str_replace('?'.$_SERVER['QUERY_STRING'],'',$_SERVER['HTTP_X_FORWARDED_URI']);
    }

    $section = (isset($_GET['s'])) ? $_GET['s'] : FALSE;


    require_once("./inc/global.php");

    if($section === false){
        header("Location: ".'./'.basename($_SERVER['PHP_SELF'])."?s=view");
    }elseif($section == 'qat'){
        header("Content-type: text/plain");
        print_r($_SERVER);
        print("Location: ".'./'.basename($_SERVER['PHP_SELF'])."?s=view");
        die();
    }elseif($section == 'view'){
        require_once("./modules/mod_view.php");
    }elseif($section == 'add'){
        require_once("./modules/mod_add.php");
    }else{
        header("Location: ".'./'.basename($_SERVER['PHP_SELF'])."?s=view");
    }

?>
