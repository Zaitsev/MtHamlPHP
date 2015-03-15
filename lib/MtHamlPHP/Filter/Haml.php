<?php

namespace MtHamlPHP\Filter;

use MtHamlPHP\Dbg;
use Symfony\Component\Yaml\Yaml;
use MtHaml\NodeVisitor\RendererAbstract as Renderer;
use MtHaml\Node\Filter;
use MtHaml\Exception;
use MtHaml\Filter\Plain;

class Haml extends Plain
{
//    public function optimize(Renderer $renderer, Filter $node, $options)
//    {
//        $this->renderFilter($renderer, $node, $options);
//    }

    static public function setOptions($node, \MtHamlPHP\Environment $env)
    {

        $yaml = "runtime:\n" . self::getContent($node);
        $array = Yaml::parse($yaml);
        if (is_array($array['runtime'])) {
            foreach ($array['runtime'] as $key => $val) {
                if (!empty($key)) {
                    $env->setOption($key, $val);
                }
            }
        }
    }
    protected function renderFilter(Renderer $renderer, Filter $node){
        $yaml = "runtime:\n" . self::getContent($node);
        $array = Yaml::parse($yaml);
        if (is_array($array['runtime'])) {
            foreach ($array['runtime'] as $key => $val) {
                if (!empty($key)) {
                    $renderer->envSetOption($key, $val);
                }
            }
        }
    }

    protected function _renderFilter(Renderer $renderer, Filter $node)
    {

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
        //Dbg::emsgd($renderer->env);

    }
}
