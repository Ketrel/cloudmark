<?php

    /* --- BEGIN GLOBAL SECTION --- */

        if(!file_exists(__DIR__.'/../vendor/autoload.php')){
            define('CLASS_DIR',BASEUSEDIR.'/lib/');
            set_include_path(get_include_path().PATH_SEPARATOR.CLASS_DIR);
            spl_autoload_register();
        }

        if(!file_exists(BASEUSEDIR.'/config.ini')){
            die("Config File Missing");
        }else{
            $config = parse_ini_file(BASEUSEDIR.'/config.ini');
        }

        if(file_exists(BASEUSEDIR.'/custom.ini')){
            $tplCustom = parse_ini_file(BASEUSEDIR.'/custom.ini');
        }else{
            $tplCustom = [];
        }

        if(!isset($config['database'])){
            die("No Database Defined In Config");
        }

        $defaultTemplate = "bootstrap";

        $templateRoot = BASEUSEDIR.'/src/templates';
        $templateBase = $templateRoot.'/'.(isset($config['template']) ? $config['template'] : $defaultTemplate);
        $templateRelPath = str_replace(BASERUNDIR,'',BASEUSEDIR).'/src/templates/'.(isset($config['template']) ? $config['template'] : $defaultTemplate);
        $templateDir = $templateBase.'/tpl';

        //echo BASERUNDIR."\n".BASEUSEDIR."\n\n"; //Debug
        //echo "{$templateRoot}\n{$templateBase}\n{$templateDir}\n{$templateRelPath}\n\n\n"; //Debug

        //Set some basic values.
        $debugInfo = "";
        $debugOn = ((isset($_GET['debug']) && $_GET['debug'] == 1) || (isset($config['debug']) && $config['debug'] == 1)) ? TRUE : FALSE;
        $pageBase = basename($_SERVER['PHP_SELF']);
        $getArray = $_GET;

        $globalStrip = [
                        'search',
                        'searchLoc',
                        'searchType',
                       ];

        /*
          This is a rather interesting function.
          It takes the following
            $strip: an array of strings, or a single string
            $withQ: if ? and/or & should be pre-added to the end of the string
              (I plan to update this to make it even more versitile, with empty handling)
            $mode:
              0=remove anything passed to strip
              1=remove anything BUT what was passed to strip
            $useGlobal: whether or not $globalStrip should be merged with $strip.
              This automatically will not be done if using mode 1, since its purpose
              is to automatically remove
            ($globalStrip): an array of strings, coinciding with $_GET keys to remove without
              being explicitly defined in $strip
        */
        $pageQuery = function($strip,$withQ=FALSE,$mode=0,$useGlobal=TRUE) use ($globalStrip) {

            $getArray = $_GET;

            /*
              End result here is that regardless of if $strip is a string or an array,
               it ends up as an array, be it empty, or with a single value
               Doing it this way allows me to simply pass it to foreach without additional checks
            */
            if(is_array($strip) && !empty($strip)){
                $strip = array_merge([],$strip);
            }elseif(!empty($strip)){
                $strip = array_merge([],[$strip]);
            }else{
                $strip = [];
            }

            if($mode == 0 && $useGlobal === TRUE){
                $strip = array_merge($strip,$globalStrip);
            }

            if($mode==0){ //Remove values Mentioned
                foreach($strip as $val){
                    if(isset($getArray[$val])){ unset($getArray[$val]); }
                }
            }elseif($mode==1){ //'Remove' everything BUT values mentioned
                    $tempArray = [];
                foreach($strip as $val){
                    if(isset($getArray[$val])){
                        $tempArray[$val] = $getArray[$val];
                    }
                }
                $getArray = $tempArray;
            }
            $output = http_build_query($getArray,'','&amp;');
            if($withQ === TRUE){
                $output = (empty($output)) ? '?' : '?'.$output.'&amp;';
            }
            return $output;
        };

    /* --- END GLOBAL SECTION --- */


?>
