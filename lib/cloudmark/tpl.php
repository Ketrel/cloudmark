<?php

namespace cloudmark;

    class tpl{

        protected $tpl_raw = '';
        protected $tpl_working = '';
        protected $tpl_output = '';
        protected $tpl_vars = [];
        protected $tpl_values = [];
        protected $tpl_removeUnused = FALSE;
        protected $tpl_path = '';
        protected $tpl_values_build = [];

        public function __construct($template,array $tplvals=NULL,$removeUnused=FALSE,bool $isIncluded=FALSE,$presetPath=FALSE){
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
                }
            }
        }

        protected function blankOutSection($section){
            $this->tpl_working = preg_replace('/'.preg_quote($section,'/').'/','',$this->tpl_working,1);
        }

        protected function replaceSection($section,$replacement){
            $this->tpl_working = preg_replace('/'.preg_quote($section,'/').'/',$replacement,$this->tpl_working,1);
        }

        public function setVals(array $tplvals){
            $this->tpl_values = $tplvals;
        }

        public function setPath($path){
            $this->tpl_path = $path;
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
            $if_controls = [];

            preg_match_all('/(\{%IF:(.+?)%\}\r?\n?)(.+?)(\{%ENDIF:\2%\}\r?\n?)/s',$this->tpl_working,$if_controls,PREG_SET_ORDER);

            foreach($if_controls as $x){
                if(!isset($this->tpl_values[$x[2]])){
                    //$this->tpl_working = preg_replace('/'.preg_quote($x[0],'/').'/','',$this->tpl_working,1);
                    $this->blankOutSection($x[0]);
                }else{
                    //$this->tpl_working = preg_replace('/'.preg_quote($x[0],'/').'/',(new tpl($x[3],$this->tpl_values,TRUE,TRUE))->buildOutput(),$this->tpl_working,1);
                    $this->replaceSection($x[0],(new tpl($x[3],$this->tpl_values,TRUE,TRUE,$this->tpl_path))->buildOutput());
                }
            }
        }

        protected function procForeach(){
            $foreach_controls = [];
            preg_match_all('/\{%FOREACH:(.+?)%\}/',$this->tpl_working,$foreach_controls,PREG_SET_ORDER);

            foreach($foreach_controls as $x){
                if(is_array($this->tpl_values[$x[1]]) && count($this->tpl_values[$x[1]]) > 0){
                    $build = '';

                    if(isset($this->tpl_values[$x[1]][1]) && count($this->tpl_values[$x[1]][1]) > 0){
                        foreach($this->tpl_values[$x[1]][1] as $y){
                            $build .= (new tpl($this->tpl_values[$x[1]][0],$y,FALSE,FALSE,$this->tpl_path))->buildOutput();
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
            $foreach_controls = [];
            preg_match_all('/(\{%EFOREACH:(.+?)%\}\r?\n?)(.+?)(\{%EEFOREACH:\2%\}\r?\n?)/s',$this->tpl_working,$foreach_controls,PREG_SET_ORDER);
            if(!empty($foreach_controls)){
                foreach($foreach_controls as $val){
                    if(isset($this->tpl_values[$val['2']]) && is_array($this->tpl_values[$val['2']])){
                        $build = '';
                        foreach($this->tpl_values[$val['2']] as $rep){
                            $build .= (new tpl($val['3'],$rep,TRUE,TRUE,$this->tpl_path))->buildOutput();
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
            $include_controls = [];
            preg_match_all('/\{%INCLUDE:(.+?)%\}/',$this->tpl_working,$include_controls,PREG_SET_ORDER);
            foreach($include_controls as $x){
                $file = './'.$x[1].'.htpl';
                if(file_exists($file)){

                }elseif(file_exists($this->tpl_path.'/'.$x[1].'.htpl')){
                    $file = $this->tpl_path.'/'.$x[1].'.htpl';
                }else{
                    $file = null;
                }
                if($file != null){
                    $this->replaceSection($x[0],(new tpl($file,$this->tpl_values,TRUE,FALSE,$this->tpl_path))->buildOutput());
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

            //Rework this into a function and make it match the above stuff
            preg_match_all('/\{%([A-Za-z0-9:\-\/\\\]+)?%\}/',$this->tpl_working,$this->tpl_vars,PREG_PATTERN_ORDER);
            $this->tpl_vars = array_values(array_unique($this->tpl_vars[1]));
            foreach($this->tpl_vars as $x){
                if(isset($this->tpl_values[$x]) && !is_array($this->tpl_values[$x])){
                    $this->tpl_working = preg_replace('/\{%'.preg_quote($x,'/').'%\}\r?\n?/',$this->tpl_values[$x],$this->tpl_working);
                }else{
                    if($this->tpl_removeUnused == TRUE){
                        $this->tpl_working = preg_replace('/\{%'.preg_quote($x,'/').'%\}\r?\n?/','',$this->tpl_working);
                    }
                }
            }



        }

        public function buildOutput(){
            $this->process();
            //$this->devderp(); //Testing Only
            return $this->tpl_working; //This is just fo testing
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
