<?php

namespace MtHamlPHP\Filter;

use MtHamlPHP\Dbg;
use Symfony\Component\Yaml\Yaml;
use MtHaml\NodeVisitor\RendererAbstract as Renderer;
use MtHaml\Node\Filter;
use MtHaml\Exception;

class Haml extends \MtHaml\Filter\Plain
{
    public function optimize(Renderer $renderer, Filter $node, $options)
    {
        $this->renderFilter($renderer, $node, $options);
    }

    protected function renderFilter(Renderer $renderer, Filter $node)
    {
//        $yaml = "\nhaml:\n".$this->getContent($node);
//        //Dbg::emsgd($yaml);
//        $array=Yaml::parse($yaml);
//        Dbg::emsgd($array['haml']);
        //Dbg::emsgd($array);
        foreach ($node->getChilds() as $line) {
            foreach ($line->getContent()->getChilds() as $child) {
                list($key, $val) = explode('=>', $child->getContent());
                $key = trim($key);
                $val = trim($val);
                if (!empty($key)) {
//                    if ($key == 'require') {
//                        $filename = dirname($renderer->env->currentMoreEnv['filename']) . '/' . $val;
//                        $file_content = @file_get_contents($filename);
//                        if ($file_content === false) {
//                            throw new  Exception("prepare file $filename");
//                        } else {
//                            $array = Yaml::parse($file_content);
//                            Dbg::emsgd($array);
//
//                        }
//                    }
                    $renderer->envSetOption($key, $val);
                }
            }
        }
    }
}
