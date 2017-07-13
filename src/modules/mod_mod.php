<?php

    //Initial Setup

    $linkpage = new cloudmark\db(BASEUSEDIR.'/'.$config['database']);

    $currentCategory = (isset($_GET['cat'])) ? (int)$_GET['cat'] : 0;
    $currentPage = (isset($_GET['page']) && $_GET['page'] > 0) ? (int)$_GET['page'] : 1;

    $debugOn = ((isset($_GET['debug']) && $_GET['debug'] == 1) || (isset($config['debug']) && $config['debug'] == 1)) ? TRUE : FALSE;

    $allCats = $linkpage->getCatsAll();

    function printCatsB($catArray,$sep='-',$pid=0,$depth=0){
        $output = [];
        foreach($catArray as $val){
            if($val['parent'] == $pid){
                $output[] = ['id'=>$val['id'],'title'=>str_repeat($sep,$depth).$val['title']];
                $output = array_merge($output,printCatsB($catArray,$sep,$val['id'],$depth+1));
            }
        }
        return $output;
    }


    $tplVals =  [
                    'TITLE'         =>(isset($config['site_name'])) ? $config['site_name'] : "No Name Set",
                    'NAVLINKS'      =>[
                                       [
                                        'LINKNAME'=>'View',
                                        'URL'=>'./index.php?s=view',
                                       ],
                                       [
                                        'LINKNAME'=>'Mod',
                                        'URL'=>'./index.php?s=mod',
                                        'ACTIVE'=>' active',
                                       ],
                                      ],
                    'SUBSEC'        =>[
                                       [
                                        'LINKNAME'=>'Add Link',
                                        'URL'=>'./index.php?s=mod&amp;act=addl',
                                        'ACTIVE'=>((isset($getArray['act']) && $getArray['act'] == "addl") ? " active" : ''),
                                       ],
                                       [
                                        'LINKNAME'=>'Add Cat',
                                        'URL'=>'./index.php?s=mod&amp;act=addc',
                                        'ACTIVE'=>((isset($getArray['act']) && $getArray['act'] == "addc") ? " active" : ''),
                                       ],
                                       [
                                        'LINKNAME'=>'Change Link',
                                        'URL'=>'./index.php?s=mod&amp;act=changel',
                                        'ACTIVE'=>((isset($getArray['act']) && $getArray['act'] == "changel") ? " active" : ''),
                                       ],
                                       [
                                        'LINKNAME'=>'Change Cat',
                                        'URL'=>'./index.php?s=mod&amp;act=changec',
                                        'ACTIVE'=>((isset($getArray['act']) && $getArray['act'] == "changec") ? " active" : ''),
                                       ],
                                      ],
                ];

    $tplVals['TEMPLATE'] = $templateRelPath;


    $tplVals['FORM'] = '';
    foreach(printCatsB($allCats,"&nbsp;") as $val){
        $tplVals['FORM'] .= '<option value="'.$val['id'].'">'.$val['title'].'</option>'."\n";
    }


    $tpl = new cloudmark\tpl($templateDir.'/mod.htpl',$tplVals,TRUE);
    $tpl->setPath($templateDir);
    print $tpl->buildOutput();

?>
