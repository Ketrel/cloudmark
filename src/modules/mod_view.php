<?php

    //Initial Setup

    $linkpage = new cloudmark\db($config['database']);

    $currentCategory = (isset($_GET['cat'])) ? (int)$_GET['cat'] : 0;
    $currentPage = (isset($_GET['page']) && $_GET['page'] > 0) ? (int)$_GET['page'] : 1;

    $debugOn = ((isset($_GET['debug']) && $_GET['debug'] == 1) || (isset($config['debug']) && $config['debug'] == 1)) ? TRUE : FALSE;

    /*
    if(isset($config['tplpak'])){
        $tplpak = $config['tplpak'];
        $tpldir = "./tplpak/{$tplpak}";
    }else{
        $tplpak = '';
        $tpldir = "./inc";
    }
    */

    //Set default page limit if not in config
    if(isset($config['links_per_page'])){
        $linkpage->setLinkPageLimit((int)$config['links_per_page']);
    }else{
        $linkpage->setLinkPageLimit(20);
    }

    /*
      Set page limit for cats too if not in config
      This one doesn't paginate however
      Maybe in the future?
    */
    if(isset($config['cats_per_page'])){
        $linkpage->setCatPageLimit((int)$config['cats_per_page']);
    }else{
        $linkpage->setCatPageLimit(100);
    }

    /*
        Basic Search Handling
        $SearchInfo should be an empty array if not searching
        I want it to exist, but be empty, rather than be unset
    */
    $searchInfo = [];
    $globalSearch = FALSE;
    if(isset($_GET['search']) && isset($_GET['searchLoc']) && isset($_GET['searchType'])){
        if(in_array($_GET['searchLoc'],['cat','global']) && in_array($_GET['searchType'],['both','url','title'])){
            $searchInfo['location'] = $_GET['searchLoc'];
            $searchInfo['type'] = $_GET['searchType'];
            $searchInfo['search'] = $_GET['search'];
        }
    }
    if(isset($searchInfo['location']) && $searchInfo['location'] == "global"){
        $globalSearch = TRUE;
    }

    //Load and decode category Array
    $cats = $linkpage->getCats($currentCategory);
    /*
        Load links
        If searching, make sure we include the search information
        (an optional argument to cloudmarks::getLinks)
    */
    if(isset($searchInfo['search'])){
        $links = $linkpage->getLinks($currentCategory,$currentPage,$searchInfo);
    }else{
        $links = $linkpage->getLinks($currentCategory,$currentPage);
    }

    if(isset($searchInfo['search'])){
        $totalCatLinks = $linkpage->getLinkCount($currentCategory,$searchInfo);
    }else{
        $totalCatLinks = ($currentCategory == 0) ? 0 : $linkpage->getLinkCount($currentCategory);
    }

    //Get the sub-category count
    $totalSubCats = $linkpage->getCatCount($currentCategory);
    if($globalSearch){
        $totalSubCats = 0;
    }

    //Section to generate pagination
    if($totalCatLinks > $linkpage->getLinkPageLimit()){

        $pageString = $pageBase.$pageQuery('page',TRUE,0,FALSE).'page=';

        $cb = function($pageInfo) use ($currentCategory,$currentPage,$pageString) {
            $pageInfo = explode("@|@",$pageInfo);
            $page = $pageInfo[0];
            $name = (isset($pageInfo[1])) ? $pageInfo[1] : NULL;
            if(is_null($name)){ $name = $page; }
            return [
                    "NUMBER"=>$pageString.$page,
                    "NAME"=>$name,
                    "ACTIVE"=>($page == $currentPage) ? ' active' : '',
                   ];
        };

        $pages = new cloudmark\paginate(ceil(($totalCatLinks/$linkpage->getLinkPageLimit())),$currentPage,3);
        $pages->setCallback($cb);
        $pages->setNextPrev(TRUE);

        $pageList = array_column($pages->paginate(),0);
    }


    //Section for initiation template variables
    $tplVals = [
                'TITLE'         =>(isset($config['site_name'])) ? $config['site_name'] : "No Name Set",
                'NAVLINKS'      =>[
                                   [
                                    'LINKNAME'=>'View',
                                    'URL'=>'./index.php?s=view',
                                    'ACTIVE'=>' active',
                                   ],
                                  ],
               ];

    if(!empty($_GET)){
        $getarr = [];
        foreach($_GET as $g_name=>$g_val){
            if(!in_array($g_name,$globalStrip)){
                $getarr[] = ['GETNAME'=>$g_name,'GETVALUE'=>$g_val];
            }
        }
        $tplVals['GETSTR'] = $getarr;
    }

    if(isset($searchInfo['search'])){
        $tplVals['SEARCHSTRING'] = $searchInfo['search'];
        $tplVals['SEARCHUNDO'] = './'.$pageBase.'?'.$pageQuery('page',false,0);
    }

    $tplVals['TEMPLATE'] = $templateBase;

    /* Begin Debug Code */
    if($debugOn){
        $tplVals['FOOTER'] = 'Debug Info: C:'.$totalSubCats.' L:'.$totalCatLinks.$debugInfo;
    }
    /* End Debug Code */


    //Section to add pagination to template variables
    if(isset($pageList) && count($pageList) > 0){
        $tplVals['PAGINATION'] = $pageList;
    }

    //Generate Breadcrumbs
    //Rework this later
    $breadcrumbs = '<span class="breadcrumb-item">Home</span>';
    if($currentCategory !=0){

        $currentCat = $linkpage->getSingleCat($currentCategory);
        $tplVals['CATEGORY'] = $currentCat['title'];


        if(!empty($currentCat) || $currentCat === FALSE){ //False is in case an invalid catID is requested, aka...0
            $breadcrumbs = '<a class="breadcrumb-item" href="./'.$pageBase.'?s=view">Home</a>';
        }

        if(!empty($currentCat) && $currentCat['parent'] != 0){
            $parentCat = $linkpage->getSingleCat((int)$currentCat['parent']);
            if(!empty($parentCat)){
                if($parentCat['parent'] != 0){
                    $breadcrumbs .= '<span class="breadcrumb-item">&nbsp;...&nbsp;</span>';
                }
                $breadcrumbs .= '<a class="breadcrumb-item" href="'.$pageBase.$pageQuery(['s','debug'],TRUE,1).'cat='.$parentCat['id'].'">'.$parentCat['title'].'</a>';
            }
        }

        if(!empty($currentCat)){
            $breadcrumbs .= '<span class="breadcrumb-item active">'.$currentCat['title'].'</span>';
        }

        if(!empty($searchInfo) && !$globalSearch){
            $breadcrumbs .= '<span class="breadcrumb-item">Search Results</span>';
        }
    }
    if($globalSearch){
        $breadcrumbs ='<a class="breadcrumb-item" href="./'.$pageBase.'?s=view">Home</a><span class="breadcrumb-item">Search Results</span>';
    }
    $tplVals['BREADCRUMBS'] = $breadcrumbs."\n";

    //Section to generate categories
    if(count($cats) > 0 && !$globalSearch){

        $tplVals['CATEGORIES'] = [];
        $i = 0;
        $pq = $pageBase.$pageQuery(['cat','page'],TRUE).'cat=';
        foreach($cats as $cat){
            $tplVals['CATEGORIES'][] = [
                                            'CATID'     =>$pq.$cat['id'],
                                            'CATEGORY'  =>$cat['title'],
                                          ];
            if(isset($cat['description'])){
                $tplVals['CATEGORIES'][$i]['CATDESCRIPTION'] = $cat['description'];
            }
            $i++;
        }
    }

    if(count($links) > 0){
        $tplVals['LINKS'] = [];
        $i = 0;
        foreach($links as $link){
            $tplVals['LINKS'][] = [
                                      'TITLE'     => ($debugOn) ? "[{$link['id']}] ".$link['title'] : $link['title'],  //For Debug Purposes
                                      'URL'       => $link['url']
                                     ];
            if(!is_null($link['description'])){ $tplVals['LINKS'][$i]['DESCRIPTION'] = $link['description']; }
            $i++;
        }

        /* --- Below Bit SHOULD be working how I want now --- */
        if(count($tplVals['LINKS']) > floor($linkpage->getLinkPageLimit()/2)){

            $lpc = $linkpage->getLinkPageLimit()/2;
            $lpc = (floor($lpc) != $lpc && count($tplVals['LINKS']) == $linkpage->getLinkPageLimit()) ? $lpc+1 : $lpc;

            $chunks = array_chunk($tplVals['LINKS'],$lpc);
            $tplVals['LINKS2'] = $chunks[1];
            $tplVals['LINKS']  = $chunks[0];

        }
        /* --- Above Bit SHOULD be working how I want now --- */

    }


    //Template file, template replacements, if true, remove unused tags

    if(empty($tplCustom)){
        $tpl = new cloudmark\tpl($templateDir.'/view.htpl',$tplVals,TRUE);
        $tpl->setPath($templateDir);
        echo $tpl->buildOutput();
    }else{
        $tpl = new cloudmark\tpl($templateDir.'/view.htpl',$tplVals,FALSE);
        $tpl->setPath($templateDir);
        $interim = $tpl->buildOutput();
        $tpl = new cloudmark\tpl($interim,$tplCustom,TRUE,TRUE);
        $tpl->setPath($templateDir);
        echo $tpl->buildOutput();
    }


?>
