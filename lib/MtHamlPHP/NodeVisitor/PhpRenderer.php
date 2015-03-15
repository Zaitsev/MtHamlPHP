<?php
namespace MtHamlPHP\NodeVisitor;
use MtHamlPHP\Dbg;
use MtHamlPHP\Environment;
use MtHaml\Node\Insert;
use MtHaml\Node\InterpolatedString;
use MtHaml\Node\Tag;
use MtHaml\Node\ObjectRefClass;
use MtHaml\Node\NodeAbstract;
use MtHaml\Node\ObjectRefId;
use MtHaml\Node\TagAttributeInterpolation;

class PhpRenderer extends \MtHamlMore\NodeVisitor\PhpRenderer
//class PhpRenderer extends \MtHamlPHP\Ext\MtHamlMore\PhpRenderer
{
    public $env;
    public $reduceRuntimeArrayTolerant=false;
    /**
     * @param Tag $tag
     */
    public function envSetOption($k, $v)
    {
        if ($v === 'true' ) {$v = true;}
        if ($v === 'false' ) {$v = false;}
        if ($k == 'reduce_runtime_array_tolerant' ) {
            //!! parent reduceRuntimeArrayTolerant is protected !!.
            $class = new \ReflectionClass('\MtHamlMore\NodeVisitor\PhpRenderer');
            $property = $class->getProperty("reduceRuntimeArrayTolerant");
            $property->setAccessible(true);
            $property->setValue($this,$v);
        }
//        if ($k=='uses')
//        {
//            $snipHouse = $this->env->currentMoreEnv->getSnipHouse();
//            $snipHouse->addUse($v);
//
//            Dbg::emsgd($snipHouse->getUses());
//            //echo (dirname($this->env->currentMoreEnv['filename']));
//        }
        $this->env->setOption($k, $v);
    }
//    public function __construct(Environment $env){
//        parent::__construct($env);
//    }

    protected function renderDynamicAttributes(Tag $tag)
    {
        $escape_attrs = $this->env->getOption('enable_escaper') && $this->env->getOption('escape_attrs');
        //Dbg::emsgd($escape_attrs);
        if ($this->env->getOption('use_runtime') === true) {
            parent::renderDynamicAttributes($tag);
            return;
        }
        $list = array();
        $this->setEchoMode(false);
        //$tag->getAttributes()
        foreach ($tag->getAttributes() as $attr) {
            //print_r(get_class($val));
            if ($attr instanceof TagAttributeInterpolation) {
                $attr_name = 'interpolation';
            } else {
                $attr_name = $attr->getName() == null ? 'interpolation' : $attr->getName()->getContent();
            }

            $val = $attr->getValue();
            if (empty($val)) {
                $this->raw(" $attr_name ");
                continue;
            }
            if (isset($list[$attr_name])) {
                $list[$attr_name][] = ' ';
            }
            if ($val instanceof Insert) {
                $list[$attr_name][] = array($val->getNodeName(), $val->getContent());
            } elseif ($val instanceof InterpolatedString) {
                foreach ($val->getChilds() as $ch) {
                    $list[$attr_name][] = array($ch->getNodeName(), $ch->getContent());
                }
            } elseif (is_a($val, 'MtHaml\Node\Text') || $val instanceof \MtHaml\Node\Text) {
                $list[$attr_name][] = array($val->getNodeName(), $val->getContent());
            } else {
                echo("============ OTHER =====" . get_class($val) . " ========\n");
                print_r($val);
                echo "!!!" . $val->getNodeName() . "!!!\n\n";
            }

        }

        //print_r($list);
        $tag_name = $tag->getTagName();
        foreach ($list as $attr => $val) {
            $helpers = $this->env->getOption("helpers");

            if ($helpers) {
                if (isset($helpers[$tag_name][$attr])){
                    $helper = $helpers[$tag_name][$attr];
                }elseif(isset($helpers[$tag_name]['*'])){
                    $helper = $helpers[$tag_name]['*'];
                }elseif(isset($helpers['*'][$attr])){
                    $helper = $helpers['*'][$attr];
                }elseif(isset($helpers['*']['*'])) {
                    $helper = $helpers['*']['*'];
                }
            }
            if ($helper) {
//                print_r($val);
                $r = array();
                foreach ($val as $ch) {
                    if (trim($ch[1]) == false) continue;
	                if ($ch[0] == "echo") {
		                $r[] = $ch[1];
	                } else {
		                $r[] = "'" . $ch[1] . "'";
	                }
                }
	            $this->raw(' ' . sprintf($helper, $tag->getTagName(), $attr, 'array(' . join(',', $r) . ')') . ' ');
            } else {


		        if ($attr == 'data') {
                    $glue_string = ' data-';
                    $r = array();
                    $text = '';
                    //print_r($val);
                    foreach ($val as $ch) {
                        if ($ch[0] == "echo") {
                            $ch[1] = trim($ch[1]);
                            if ($ch[1] == false) continue;
                            if (substr($ch[1], 0, 1) == '[' || substr($ch[1], 0, 1) == '{') {
                                $ch[1] = "array(" . substr($ch[1], 1, -1) . ")";
                            }
//                            elseif (substr($ch[1], 0, 5) != 'array') {
//                                $ch[1] = " array(" . $ch[1] . ")";
//                            }
                            $fmt = $escape_attrs
                                ? '<?php foreach(%1$s as $k=>$v) {echo  \' data-\'.$k.\'="\'.htmlspecialchars($v,ENT_QUOTES,"%2$s").\'"\' ;} ?>'
                                : '<?php foreach(%1$s as $k=>$v) {echo " data-$k=\"$v\" ";} ?>';
                            $r[] = sprintf($fmt, $ch[1], $this->charset);
                        } else {
                            $text .= trim($ch[1]) == false ? $glue_string : $ch[1];
                        }
                    }
                    $this->raw($text . join($glue_string, $r));
                } elseif ($attr == 'class' || $attr == 'id') {
                    //try find helper
                    //print_r($list);
                    $glue_string = $attr == 'id' ? '_' : ' ';
                    $helper_name = $attr == 'id' ? 'id.helper' : 'class.helper';
                    $r = array();
                    $text = '';
                    foreach ($val as $ch) {
                        if ($ch[0] == "echo") {
                            $ch[1] = trim($ch[1]);
                            if ($ch[1] == false) continue;
                            if (substr($ch[1], 0, 1) == '[') {
                                $ch[1] = " implode('" . $glue_string . "',array(" . substr($ch[1], 1, -1) . "))";
                                //$ch[1]='uu';
                            } elseif (substr($ch[1], 0, 5) == 'array') {
                                $ch[1] = " implode('" . $glue_string . "'," . $ch[1] . ")";
                            }
                            $fmt = $escape_attrs
                                ? '<?php echo ( htmlspecialchars(%1$s,ENT_QUOTES,"%2$s")) ;?>'
                                : '<?php echo(%1$s) ;?>';
                            $r[] = sprintf($fmt, $ch[1], $this->charset);

                            /*$r[] = '<?php echo(' . $ch[1] . ') ;?>'; */
                        } else {
                            $text .= trim($ch[1]) == false ? $glue_string : $ch[1];
                            //$text .= print_r($ch[1],true);
                        }
                    }
                    $this->raw(' ' . $attr . '="' . $text . join($glue_string, $r) . '"');
                } elseif ($attr == "interpolation") {
                    //print_r($val);
                    $r = array();
                    $text = "";
                    $r = array();
                    foreach ($val as $ch) {
                        if (trim($ch[1]) == false) continue;
                        if ($ch[0] == "echo") {
                            $fmt = $escape_attrs
                                ? '<?php echo ( htmlspecialchars(%1$s,ENT_QUOTES,"%2$s")) ;?>'
                                : '<?php echo(%1$s) ;?>';
                            $r[] = sprintf($fmt, $ch[1], $this->charset);
                            /* $r[] = '<?php echo(' . $ch[1] . ') ;?>'; */
                        } else {
                            $r[] = $ch[1];
                        }
                    }
                    $this->raw(' ' . join(' ', $r));
                } else {
                    $r = array();
                    $iif = array();
                    //print_r($val);
                    foreach ($val as $ch) {
                        if (trim($ch[1]) == false) continue;
                        if ($ch[0] == "echo") {
                            if (substr($ch[1], 0, 2) == 'if') {

                                $iif[] = '<?php echo (' . substr($ch[1], 2) . '? "' . $attr . '" :"") ;?>';
                            } else {
                                $fmt = $escape_attrs
                                    ? '<?php echo ( htmlspecialchars(%1$s,ENT_QUOTES,%2$s)) ;?>'
                                    : '<?php echo(%1$s) ;?>';
                                $r[] = sprintf($fmt, $ch[1], $this->charset);
                            }
                        } else {
                            $r[] = $ch[1];
                        }

                    }
                    if (count($iif)) {
                        $this->raw(' ' . join(' ', $iif));
                    }
                    if (count($r)) {
                        $this->raw(' ' . $attr . '="' . join('', $r) . '"');
                    }
                }
            }
        }

        $this->setEchoMode(true);

    }

    public function enterObjectRefClass(ObjectRefClass $node)
    {
        if ($this->isEchoMode()) {
            $this->raw('<?php echo ');
        }
        $this->raw('MtHaml\Runtime::renderObjectRefClass(');

        $this->pushEchoMode(false);
    }

    public function leaveObjectRefClass(ObjectRefClass $node)
    {
        $this->raw(')');

        $this->popEchoMode(true);
        if ($this->isEchoMode()) {
            $this->raw('; ?>');
        }
    }

    public function enterObjectRefId(ObjectRefId $node)
    {
        if ($this->isEchoMode()) {
            $this->raw('<?php echo ');
        }
        $this->raw('MtHaml\Runtime::renderObjectRefId(');

        $this->pushEchoMode(false);
    }

    public function leaveObjectRefId(ObjectRefId $node)
    {
        $this->raw(')');

        $this->popEchoMode(true);
        if ($this->isEchoMode()) {
            $this->raw('; ?>');
        }
    }

    public function enterObjectRefPrefix(NodeAbstract $node)
    {
        $this->raw(', ');
    }
}

