<?php
namespace Naka507\Koa;
class Template {
    protected $vars = array();
    protected $values = array();
    private $properties = array();
    protected $instances = array();
    protected $modifiers = array();
    private $blocks = array();
    private $parents = array();
    private $parsed = array();
    private $finally = array();
    private $accurate;
    private static $REG_NAME = "([[:alnum:]]|_)+";

    public function __construct($filename, $accurate = false){
        $this->accurate = $accurate;
        $this->loadfile(".", $filename);
    }

    public function addFile($varname, $filename){
        if(!$this->exists($varname)) throw new \InvalidArgumentException("addFile: var $varname does not exist");
        $this->loadfile($varname, $filename);
    }

    public function __set($varname, $value){
        if(!$this->exists($varname)) throw new \RuntimeException("var $varname does not exist");
        $stringValue = $value;
        if(is_object($value)){
            $this->instances[$varname] = $value;
            if(!isset($this->properties[$varname])) $this->properties[$varname] = array();
            if(method_exists($value, "__toString")) $stringValue = $value->__toString();
            else $stringValue = "Object";
        }
        $this->setValue($varname, $stringValue);
        return $value;
    }

    public function __get($varname){
        if(isset($this->values["{".$varname."}"])) return $this->values["{".$varname."}"];
        elseif(isset($this->instances[$varname])) return $this->instances[$varname];
        throw new \RuntimeException("var $varname does not exist");
    }

    public function exists($varname){
        return in_array($varname, $this->vars);
    }

    private function loadfile($varname, $filename) {
        if (!file_exists($filename)) throw new \InvalidArgumentException("file $filename does not exist");
        if($this->isPHP($filename)){
            ob_start();
            require $filename;
            $str = ob_get_contents();
            ob_end_clean();
            $this->setValue($varname, $str);
        } else {
            $str = preg_replace("/<!---.*?--->/smi", "", file_get_contents($filename));
            if (empty($str)) throw new \InvalidArgumentException("file $filename is empty");
            $this->setValue($varname, $str);
            $blocks = $this->identify($str, $varname);
            $this->createBlocks($blocks);
        }
    }

    private function isPHP($filename){
        foreach(array('.php', '.php5', '.cgi') as $php){
            if(0 == strcasecmp($php, substr($filename, strripos($filename, $php)))) return true;
        }
        return false;
    }

    private function identify(&$content, $varname){
        $blocks = array();
        $queued_blocks = array();
        $this->identifyVars($content);
        $lines = explode("\n", $content);
        if(1==sizeof($lines)){
            $content = str_replace('-->', "-->\n", $content);
            $lines = explode("\n", $content);
        }
        foreach (explode("\n", $content) as $line) {
            if (strpos($line, "<!--")!==false) $this->identifyBlocks($line, $varname, $queued_blocks, $blocks);
        }
        return $blocks;
    }

    private function identifyBlocks(&$line, $varname, &$queued_blocks, &$blocks){
        $reg = "/<!--\s*BEGIN\s+(".self::$REG_NAME.")\s*-->/sm";
        preg_match($reg, $line, $m);
        if (1==preg_match($reg, $line, $m)){
            if (0==sizeof($queued_blocks)) $parent = $varname;
            else $parent = end($queued_blocks);
            if (!isset($blocks[$parent])){
                $blocks[$parent] = array();
            }
            $blocks[$parent][] = $m[1];
            $queued_blocks[] = $m[1];
        }
        $reg = "/<!--\s*END\s+(".self::$REG_NAME.")\s*-->/sm";
        if (1==preg_match($reg, $line)) array_pop($queued_blocks);
    }

    private function identifyVars(&$content){
        $r = preg_match_all("/{(".self::$REG_NAME.")((\-\>(".self::$REG_NAME."))*)?((\|.*?)*)?}/", $content, $m);
        if ($r){
            for($i=0; $i<$r; $i++){
                if($m[3][$i] && (!isset($this->properties[$m[1][$i]]) || !in_array($m[3][$i], $this->properties[$m[1][$i]]))){
                    $this->properties[$m[1][$i]][] = $m[3][$i];
                }
                if($m[7][$i] && (!isset($this->modifiers[$m[1][$i]]) || !in_array($m[7][$i], $this->modifiers[$m[1][$i].$m[3][$i]]))){
                    $this->modifiers[$m[1][$i].$m[3][$i]][] = $m[1][$i].$m[3][$i].$m[7][$i];
                }
                if(!in_array($m[1][$i], $this->vars)){
                    $this->vars[] = $m[1][$i];
                }
            }
        }
    }

    private function createBlocks(&$blocks) {
        $this->parents = array_merge($this->parents, $blocks);
        foreach($blocks as $parent => $block){
            foreach($block as $chield){
                if(in_array($chield, $this->blocks)) throw new \UnexpectedValueException("duplicated block: $chield");
                $this->blocks[] = $chield;
                $this->setBlock($parent, $chield);
            }
        }
    }

    private function setBlock($parent, $block) {
        $name = $block.'_value';
        $str = $this->getVar($parent);
        if($this->accurate){
            $str = str_replace("\r\n", "\n", $str);
            $reg = "/\t*<!--\s*BEGIN\s+$block\s+-->\n*(\s*.*?\n?)\t*<!--\s+END\s+$block\s*-->\n*((\s*.*?\n?)\t*<!--\s+FINALLY\s+$block\s*-->\n?)?/sm";
        }
        else $reg = "/<!--\s*BEGIN\s+$block\s+-->\s*(\s*.*?\s*)<!--\s+END\s+$block\s*-->\s*((\s*.*?\s*)<!--\s+FINALLY\s+$block\s*-->)?\s*/sm";
        if(1!==preg_match($reg, $str, $m)) throw new \UnexpectedValueException("mal-formed block $block");
        $this->setValue($name, '');
        $this->setValue($block, $m[1]);
        $this->setValue($parent, preg_replace($reg, "{".$name."}", $str));
        if(isset($m[3])) $this->finally[$block] = $m[3];
    }

    protected function setValue($varname, $value) {
        $this->values['{'.$varname.'}'] = $value;
    }

    private function getVar($varname) {
        return $this->values['{'.$varname.'}'];
    }

    public function clear($varname) {
        $this->setValue($varname, "");
    }

    public function setParent($parent, $block){
        $this->parents[$parent][] = $block;
    }

    private function substModifiers($value, $exp){
        $statements = explode('|', $exp);
        for($i=1; $i<sizeof($statements); $i++){
            $temp = explode(":", $statements[$i]);
            $function = $temp[0];
            $parameters = array_diff($temp, array($function));
            $value = call_user_func_array($function, array_merge(array($value), $parameters));
        }
        return $value;
    }

    protected function subst($value) {
        $s = str_replace(array_keys($this->values), $this->values, $value);
        foreach($this->modifiers as $var => $expressions){
            if(false!==strpos($s, "{".$var."|")) foreach($expressions as $exp){
                if(false===strpos($var, "->") && isset($this->values['{'.$var.'}'])){
                    $s = str_replace('{'.$exp.'}', $this->substModifiers($this->values['{'.$var.'}'], $exp), $s);
                }
            }
        }
        foreach($this->instances as $var => $instance){
            foreach($this->properties[$var] as $properties){
                if(false!==strpos($s, "{".$var.$properties."}") || false!==strpos($s, "{".$var.$properties."|")){
                    $pointer = $instance;
                    $property = explode("->", $properties);
                    for($i = 1; $i < sizeof($property); $i++){
                        if(!is_null($pointer)){
                            $obj = strtolower(str_replace('_', '', $property[$i]));
                            if(method_exists($pointer, "get$obj")) $pointer = $pointer->{"get$obj"}();
                            elseif(method_exists($pointer, "__get")) $pointer = $pointer->__get($property[$i]);
                            elseif(property_exists($pointer, $obj)) $pointer = $pointer->$obj;
                            else {
                                $className = $property[$i-1] ? $property[$i-1] : get_class($instance);
                                $class = is_null($pointer) ? "NULL" : get_class($pointer);
                                throw new \BadMethodCallException("no accessor method in class ".$class." for ".$className."->".$property[$i]);
                            }
                        } else {
                            $pointer = $instance->get($obj);
                        }
                    }
                    if(is_object($pointer)){
                        $pointer = method_exists($pointer, "__toString") ? $pointer->__toString() : "Object";
                    } elseif(is_array($pointer)){
                        $value = "";
                        for($i=0; list($key, $val) = each($pointer); $i++){
                            $value.= "$key => $val";
                            if($i<sizeof($pointer)-1) $value.= ",";
                        }
                        $pointer = $value;
                    }
                    $s = str_replace("{".$var.$properties."}", $pointer, $s);
                    if(isset($this->modifiers[$var.$properties])){
                        foreach($this->modifiers[$var.$properties] as $exp){
                            $s = str_replace('{'.$exp.'}', $this->substModifiers($pointer, $exp), $s);
                        }
                    }
                }
            }
        }
        return $s;
    }

    public function block($block, $append = true) {
        if(!in_array($block, $this->blocks)) throw new \InvalidArgumentException("block $block does not exist");
        if(isset($this->parents[$block])) foreach($this->parents[$block] as $child){
            if(isset($this->finally[$child]) && !in_array($child, $this->parsed)){
                $this->setValue($child.'_value', $this->subst($this->finally[$child]));
                $this->parsed[] = $block;
            }
        }
        if ($append) {
            $this->setValue($block.'_value', $this->getVar($block.'_value') . $this->subst($this->getVar($block)));
        } else {
            $this->setValue($block.'_value', $this->getVar($block.'_value'));
        }
        if(!in_array($block, $this->parsed)) $this->parsed[] = $block;
        if(isset($this->parents[$block])) foreach($this->parents[$block] as $child) $this->clear($child.'_value');
    }

    public function parse() {
        foreach(array_reverse($this->parents) as $parent => $children){
            foreach($children as $block){
                if(in_array($parent, $this->blocks) && in_array($block, $this->parsed) && !in_array($parent, $this->parsed)){
                    $this->setValue($parent.'_value', $this->subst($this->getVar($parent)));
                    $this->parsed[] = $parent;
                }
            }
        }
        foreach($this->finally as $block => $content){
            if(!in_array($block, $this->parsed)){
                $this->setValue($block.'_value', $this->subst($content));
            }
        }
        return preg_replace("/{(".self::$REG_NAME.")((\-\>(".self::$REG_NAME."))*)?((\|.*?)*)?}/", "", $this->subst($this->getVar(".")));
    }

    public static function render($file,$data=[]) {
        $tpl = new Template($file);
        try {
            foreach ($data as $key => $value) {
                if ( !is_array($value) ) {
                    $tpl->$key = $value;
                    continue;
                }
                $bool = false;
                foreach($value as $ke => $val){
                    if ( !is_array($val) ) {
                        $tpl->$ke = $val;
                    }else{
                        $bool = true;
                        foreach ($val as $k => $v) {
                            $tpl->$k = $v;
                        }
                        $tpl->block(strtoupper($key));
                    } 
                }
                if ( !$bool ) {
                    $tpl->block(strtoupper($key));
                    continue;
                }
            }
        } catch (\Exception $e){

            return "Template Error!";
    
        }
        return $tpl->parse();
    }   
}