<?php

namespace cloudmark;

    class tpl{

        protected $tpl_raw = '';
        protected $tpl_working = '';
        protected $tpl_output = '';
        protected $tpl_vars = [];
        protected $tpl_values = [];
        protected $tpl_removeUnused = FALSE;
        protected $tpl_preserveTests = FALSE;
        protected $tpl_path = '';
        protected $tpl_values_build = [];
        protected $tpl_pathSet = FALSE;
        protected $tpl_errorState = 0;
        protected $tpl_errorMsg = [];



        public function __construct($template,array $tplvals=NULL,$removeUnused=FALSE,bool $isIncluded=FALSE,$presetPath=FALSE,$preTests=FALSE){
            if(is_a($template,'tplHelper')){
                echo "THIS SHOULD NOT TRIGGER CURRENTLY";
                $this->tpl_raw = $template->template();
                $this->tpl_working = $this->tpl_raw();
                $this->tpl_values = $template->variables();
                $this->tpl_removeUnused = $template->remUnusued();
            }else{
                if($isIncluded===TRUE){
                    $this->tpl_working = $this->tpl_raw = $template;
                }else{
                    $this->tpl_working = $this->tpl_raw = $this->loadTpl($template);
                }
                if(!is_null($tplvals)){
                    $this->tpl_values = $tplvals;
                }
                if($removeUnused == TRUE){
                    $this->tpl_removeUnused = TRUE;
                }
                if($presetPath !== FALSE){
                    $this->tpl_path = $presetPath;
                    $this->tpl_pathSet = TRUE;
                }
                if($preTests == TRUE){
                    $this->tpl_preserveTests = TRUE;
                }
            }
        }

        private function errorCheck(){
            return ($this->tpl_errorState > 0);
        }

        private function errorSet($msg){
            $this->tpl_errorMsg[] = $msg;
            $this->tpl_errorState++;
            return $msg;
        }

        private function errorMsg(){
            return implode("\n",$this->tpl_errorMsg);
        }

        protected function blankOutSection($section){
            if($this->tpl_preserveTests == FALSE){
                $this->tpl_working = preg_replace('/'.preg_quote($section,'/').'/','',$this->tpl_working,1);
            }
        }

        protected function replaceSection($section,$replacement){
            $this->tpl_working = preg_replace('/'.preg_quote($section,'/').'/',$replacement,$this->tpl_working,1);
        }

        public function setVals(array $tplvals){
            $this->tpl_values = $tplvals;
        }

        public function setPreserveTests(bool $preserve){
            $this->tpl_preserveTests = $preserve;
        }

        public function setPath($path){
            $this->tpl_path = $path;
            $this->tpl_pathSet = TRUE;
        }

        protected function loadTpl($template){
            if(!file_exists($template)){
                throw new \Exception("Error: Invalid Template File: {$template}");
            }else{
                $output = file_get_contents($template);
                if($output != ''){
                    return $output;
                }else{
                    throw new \Exception("Error: Empty Template File");
                }
            }
        }

        protected function procIf(){

            if($this->errorCheck()){
                return;
            }

            $if_controls = [];

            $regex_match = '/(\{% ?IF:(.+?) ?%\}\r?\n?)(.+?)(\{% ?ENDIF:\2 ?%\}\r?\n?)/is';

            preg_match_all($regex_match,$this->tpl_working,$if_controls,PREG_SET_ORDER);

            foreach($if_controls as $x){
                if(!isset($this->tpl_values[$x[2]])){
                    $this->blankOutSection($x[0]);
                }else{
                    $this->replaceSection($x[0],(new tpl($x[3],$this->tpl_values,$this->tpl_removeUnused,TRUE,$this->tpl_path,$this->tpl_preserveTests))->buildOutput());
                }
            }
        }

        protected function procForeach(){
            if($this->errorCheck()){
                return;
            }

            $foreach_controls = [];

            $regex_match = '/\{% ?FOREACH:(.+?) USE \'([\\.A-Za-z0-9\\-_ ]+)\' ?%\}/i';

            preg_match_all($regex_match,$this->tpl_working,$foreach_controls,PREG_SET_ORDER);

            if(!empty($foreach_controls) && $this->tpl_pathSet == FALSE){
                $this->tpl_errorState++;
                $this->errorSet("To use 'foreach' in templates, a path must be set either explicitly with tpl::setPath, or by including during object instantiation.");
                return;
            }

            foreach($foreach_controls as $x){
                if(isset($this->tpl_values[$x[1]]) && is_array($this->tpl_values[$x[1]]) && count($this->tpl_values[$x[1]]) > 0){
                    $build = '';
                    if(isset($this->tpl_values[$x[1]]) && count($this->tpl_values[$x[1]]) > 0){
                        foreach($this->tpl_values[$x[1]] as $y){
                            $build .= (new tpl($this->tpl_path.'/'.$x[2],$y,FALSE,FALSE,$this->tpl_path,$this->tpl_preserveTests))->buildOutput();
                        }
                    }
                    $this->replaceSection($x[0],$build);
                }else{
                    $this->blankOutSection($x[0]);
                }
            }

        }

        protected function procEForeach(){
            /*
                EForeach (Embedded Foreach)

                This is a hybrid of if and foreach.
                It takes the contents of the EForeach block
                and passes them to a new class instance for each
                matching template variable content (which requires an array of template replacements)
            */
            if($this->errorCheck()){
                return;
            }

            $foreach_controls = [];

            $regex_match = '/(\\{% ?EFOREACH:(.+?) ?%\\}\\r?\\n?)(.+?)(\{% ?EEFOREACH:\2 ?%\}\r?\n?)/is';

            preg_match_all($regex_match,$this->tpl_working,$foreach_controls,PREG_SET_ORDER);
            if(!empty($foreach_controls)){
                foreach($foreach_controls as $val){
                    if(isset($this->tpl_values[$val['2']]) && is_array($this->tpl_values[$val['2']])){
                        $build = '';
                        foreach($this->tpl_values[$val['2']] as $rep){
                            $build .= (new tpl($val['3'],$rep,$this->tpl_removeUnused,TRUE,$this->tpl_path,$this->tpl_preserveTests))->buildOutput();
                        }
                        $this->replaceSection($val[0],$build);
                    }else{
                        $this->blankOutSection($val[0]);
                    }
                }
            }
        }

        protected function procInclude(){
            //May be finished
            //Don't forget you need to document all this shit
            //Good luck doing that AFTER you write it....moron

            if($this->errorCheck()){
                return;
            }

            $include_controls = [];

            $regex_match = '/\\{% ?INCLUDE \'(.+?)\' ?%\\}/i';

            preg_match_all($regex_match,$this->tpl_working,$include_controls,PREG_SET_ORDER);

            if(!empty($include_controls) && $this->tpl_pathSet == FALSE){
                $this->errorSet("To use 'include' in templates, a path must be set either explicitly with tpl::setPath, or by including during object instantiation.");
                return;
            }

            foreach($include_controls as $x){

                $file = './'.$x[1].'.htpl';

                if(strstr($file,"..") !== FALSE){
                    $this->errorSet("Filenames for includes cannot contain '..'");
                    return;
                }

                if(file_exists($file)){

                }elseif(file_exists($this->tpl_path.'/'.$x[1].'.htpl')){
                    $file = $this->tpl_path.'/'.$x[1].'.htpl';
                }else{
                    $file = null;
                }
                if($file != null){
                    $this->replaceSection($x[0],(new tpl($file,$this->tpl_values,$this->tpl_removeUnused,FALSE,$this->tpl_path,$this->tpl_preserveTests))->buildOutput());
                }
            }
        }

        public function devderp(){
            header("Content-type: text/plain");
            print_r($this->tpl_vars);
        }

        public function process(){

            $this->procIf();
            $this->procInclude();
            $this->procEForeach();
            $this->procForeach();

            if($this->errorCheck()){
                throw new \Exception($this->errorMsg());
                return;
            }

            //Rework this into a function and make it match the above stuff
            $regex_match = '/\\{% ?([\'\\.A-Za-z0-9:\\-\\/\\\]+) ?%\\}/i';

            preg_match_all($regex_match,$this->tpl_working,$this->tpl_vars,PREG_PATTERN_ORDER);
            $this->tpl_vars = array_values(array_unique($this->tpl_vars[1]));
            foreach($this->tpl_vars as $x){
                if(isset($this->tpl_values[$x]) && !is_array($this->tpl_values[$x])){
                    $this->tpl_working = preg_replace('/\\{% ?'.preg_quote($x,'/').' ?%\\}\r?\n?/',$this->tpl_values[$x],$this->tpl_working);
                }else{
                    if($this->tpl_removeUnused == TRUE){
                        $this->tpl_working = preg_replace('/\\{% ?'.preg_quote($x,'/').' ?%\\}\r?\n?/','',$this->tpl_working);
                    }
                }
            }



        }

        public function buildOutput(){
            $this->process();
            return $this->tpl_working;
        }

    }

/*
    class tplHelper{

        protected $th_template = null;
        protected $th_values = null;
        protected $th_remUnsused = null;
        protected $valid = FALSE;

        public function __construct($type,$template=null,$values,$unused=TRUE){
            switch($type){
                case("file"):
                    if(!is_null($template) && file_exists($template)){
                        $this->th_template=file_get_contents($template);
                        $this->th_remUnused = $unused;
                        $this->th_values = $values;
                    }
                    break;
                case("embedded"):
                    if(!is_null($template)){
                        $this->th_template=$template;
                        $this->th_remUnused = $unused;
                        $this->th_values = $values;
                    }
                    break;
                default:
                    throw new \Exception("Error: First Parameter Must Be Valid (Valid Types Are: 'file' and 'embedded')");
                    break;
            }

        }

        public function valid(){ return $this->valid; }
        public function removeUnused(){ return $this->th_remUnused; }
        public function template(){ return $this->th_template; }
        public function variables(){ return $this->th_values; }

    }

    class tplStatic{
        return "Not Implimented";
    }
*/

?>
