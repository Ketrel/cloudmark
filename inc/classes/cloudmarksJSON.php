<?php

/* Extends DB Class, so require it */
    require_once(dirname(__FILE__)."/cloudmarksDB.php");


class cloudmarksJSON extends cloudmarksDB {

    protected $cdb_pretty = FALSE;


    public function setPretty($pretty=false){
        if (is_bool($pretty)){
            $this->cdb_pretty = $pretty;
            return 1;
        }else{
            throw new Exception("Pretty must be set to a boolean value.\n");
        }
    }

    protected function formatOutput($output){
        return $this->cdb_pretty ? json_encode($output,JSON_PRETTY_PRINT) : json_encode($output);
    }

    public function getCats($catID=0,$page=1){
        $output = [];
        $output = parent::getCats($catID,$page);
        return $this->formatOutput($output);
    }

    public function getCatsAll(){
        $output = [];
        $output = parent::getCatsAll();
        return $this->formatOutput($output);
    }

    public function getLinks($catID=0,$page=1,$searchInfo=null){
        $output = [];
        $searchInfo = (is_array($searchInfo)) ? $searchInfo : null;
        $output = parent::getLinks($catID,$page,$searchInfo);
        return $this->formatOutput($output);
    }

    public function getPage($catID=0,$page=1){
        $output = [];
        $output['categories'] = json_decode($this->getCats($catID,$page),TRUE);
        $output['links'] = json_decode($this->getLinks($catID,$page),TRUE);
        return $this->formatOutput($output);
    }

}

?>
