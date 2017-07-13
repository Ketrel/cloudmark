<?php

namespace cloudmark;

    class paginate{

        protected $pg_total = 0;
        protected $pg_maxDisplay = 0;
        protected $pg_current = 0;
        protected $pg_firstlast = FALSE;
        protected $pg_nextprev = FALSE;
        protected $pg_start = 1;
        protected $pg_callback = NULL;

        public function __construct($total=0,$current=0,$maxdisplay=0){
            if( ((int)$total > 0) && (((int)$current >= 1) || ((int)$current == -1)) && ((int)$maxdisplay > 0)){
                $this->pg_total = $total;
                $this->pg_current = $current;
                $this->pg_maxDisplay = $maxdisplay;
            }else{
                throw new Exception("Error: Invalid Information Supplied");
            }
        }

        protected function doCallback($var){
            if(!is_null($this->pg_callback)){
                if(is_object($this->pg_callback) && get_class($this->pg_callback) == "Closure"){
                    return ($this->pg_callback)($var);
                }elseif(is_callable($this->pg_callback,true)){
                    return call_user_func($this->pg_callback,$var);
                }
            }else{
                return $var;
            }
        }

        public function setCallback($callback=NULL){
            if(!is_null($callback) && ((is_object($callback) && get_class($callback) == "Closure") || is_string($callback))){
                $this->pg_callback = $callback;
            }
        }

        public function setNextPrev(bool $val){
            $this->pg_nextprev = $val;
        }

        public function paginate(){
            $pageList = [];
            $i = 1;

            if(($this->pg_current == -1) || ($this->pg_current - floor($this->pg_maxDisplay/2) < 1)){
                $j = $this->pg_start;
            }else{
                $j = $this->pg_current - floor($this->pg_maxDisplay/2);
            }

            while($i < $this->pg_maxDisplay+1 && $j <= $this->pg_total){
                $pageList[$i][0] = $this->doCallback($j);
                if($j == $this->pg_current){
                    $pageList[$i][1] = "current";
                }
                $j++;
                $i++;
            }


            /*
                This part will handle prepending and appending the next/preious parts
            */
            if($this->pg_nextprev){
                if($this->pg_current > $this->pg_start){
                    array_unshift($pageList,[$this->doCallback(($this->pg_current-1).'@|@'."&lsaquo;&nbsp;Prev")]);
                }
                if($this->pg_current < $this->pg_total){
                    array_push($pageList,[$this->doCallback(($this->pg_current+1).'@|@'."Next&nbsp;&rsaquo;")]);
                }
            }

            return $pageList;
        }
    }
?>
