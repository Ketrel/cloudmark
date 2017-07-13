<?php

    define('BASERUNDIR',__DIR__);
    define('BASEUSEDIR',__DIR__.'/..');

    if(isset($_SERVER['HTTP_X_FORWARDED_URI'])){
        $_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_FORWARDED_URI'];
        //$_SERVER['PHP_SELF'] = str_replace('?'.$_SERVER['QUERY_STRING'],'',$_SERVER['HTTP_X_FORWARDED_URI']);
    }

    $section = (isset($_GET['s'])) ? $_GET['s'] : FALSE;


    require_once(BASEUSEDIR."/src/global.php");

    if($section === false){
        header("Location: ".'./'.basename($_SERVER['PHP_SELF'])."?s=view");
    }elseif($section == 'qat'){
        header("Content-type: text/plain");
        print_r($_SERVER);
        print("Location: ".'./'.basename($_SERVER['PHP_SELF'])."?s=view");
        die();
    }elseif($section == 'view'){
        require_once(BASEUSEDIR."/src/modules/mod_view.php");
    }elseif($section == 'mod' && isset($config['enableMod']) && $config['enableMod'] == true){
        require_once(BASEUSEDIR."/src/modules/mod_mod.php");
    }else{
        header("Location: ".'./'.basename($_SERVER['PHP_SELF'])."?s=view");
    }

?>
