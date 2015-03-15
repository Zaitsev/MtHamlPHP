<?php

namespace MtHamlPHP;

use MtHamlPHP\Dbg;
//use MtHamlPHP\Target\Php;
use MtHamlPHP\Target\PhpMore;
//use MtHaml\Target\Twig;
//use MtHaml\NodeVisitor\Escaping as EscapingVisitor;
//use MtHaml\NodeVisitor\Autoclose;
//use MtHaml\NodeVisitor\Midblock;
use MtHamlPHP\NodeVisitor\MergeAttrs;
//use MtHaml\Filter\FilterInterface;
use \MtHaml\Exception;

class Environment extends \MtHamlMore\Environment
{
    protected $filters = array(
        'haml' => 'MtHamlPHP\\Filter\\Haml',
        'css' => 'MtHaml\\Filter\\Css',
        'cdata' => 'MtHaml\\Filter\\Cdata',
        'escaped' => 'MtHaml\\Filter\\Escaped',
        'javascript' => 'MtHaml\\Filter\\Javascript',
        'php' => 'MtHaml\\Filter\\Php',
        'plain' => 'MtHaml\\Filter\\Plain',
        'preserve' => 'MtHaml\\Filter\\Preserve',
        'twig' => 'MtHaml\\Filter\\Twig',
    );

    public function setOption($k,$v)
    {
        if ($v === 'true' ) {$v = true;}
        if ($v === 'false' ) {$v = false;}
        if ($k == 'reduce_runtime') $this->noReduceRuntime= !$v;
        elseif ($k == 'reduce_runtime_array_tolerant') $this->reduceRuntimeArrayTolerant= $v;

        $this->options[$k] = $v ;
    }
    public function getOption($name)
    {
        return isset($this->options[$name]) ?  $this->options[$name] : null;
    }
    public function getFilter($name)
    {
        //vlz+ remove php open tag if present;
        $name = rtrim(str_replace(array('<?php','<?','<<<php'),"",$name));
        if (!isset($this->filters[$name])) {
            throw new \InvalidArgumentException(sprintf('Unknown filter name "%s"', $name));
        }

        $filter = $this->filters[$name];

        if (is_string($filter)) {
            if (!class_exists($filter)) {
                throw new \RuntimeException(sprintf('Class "%s" for filter "%s" does not exists', $filter, $name));
            }

            $filter = new $filter;
            $this->addFilter($name, $filter);
        }

        return $filter;
    }

    public function getTarget()
    {
        $target = $this->target;
        if (is_string($target)) {
            switch($target) {
                case 'php_more':
                    $target = new PhpMore;
                    break;
                case 'php':
                    $target = new PhpMore;
                    break;
//                case 'twig':
//                    $target = new Twig;
//                    break;
                default:
                    throw new \MtHaml\Exception(sprintf('Unknown target language: %s', $target));
            }
            $this->target = $target;
        }
        return $target;
    }


    protected function prepare($string, $filename)
    {
        $prepareWork = false;

        //  There seems to be some unexpected behavior when using the /m modifier when the line terminators are win32 or mac format.
        //  http://www.php.net/manual/en/function.preg-replace.php#85416
        $string = str_replace(array("\r\n", "\r"), "\n", $string);
        //workaround for:
        //               http://stackoverflow.com/questions/1908175/is-there-a-way-to-force-a-new-line-after-php-closing-tag-when-embedded-among
        //               http://stackoverflow.com/questions/4410704/why-would-one-omit-the-close-tag

        $changed = preg_replace(array(
                '/<\?/',
                '/\{=\s*(.*?)\s*=\}\r\n/', //CRLF
                '/\{=\s*(.*?)\s*=\}\r/', //CR
                '/\{=\s*(.*?)\s*=\}\n/', //LF
                '/\{=\s*(.*?)\s*=\}/', //inline
                '/^\{%\s*([^}]+)\s*%\}$/m',
            ),
            array(
                '~~~',
                '<?php echo \1."\r\n";?>'.PHP_EOL,
                '<?php echo \1."\r"; ?>'.PHP_EOL,
                '<?php echo \1."\n"; ?>'.PHP_EOL,
                '<?php echo \1; ?>',
                '<?php \1; ?>',
            ), $string, -1, $count);
        if ($count > 0) {
            $prepareWork = true;
            $filename = $filename . '.prepare.haml';
            @unlink($filename);
            file_put_contents($filename, $changed);
            file_put_contents($filename.'prep.php', $changed);
            ob_start();
            try {
                @include $filename;
                $string = ob_get_clean();
            } catch (Exception $e) {
                ob_end_clean();
                throw new  \MtHaml\Exception("prepare file $filename : $e");
            }
            // restore <?php
            $string = str_replace('~~~', '<?', $string);


        }

        return array($string, $filename, $prepareWork);

    }

    public function getMergeAttrsVisitor()
    {
        return new MergeAttrs;
    }
}

