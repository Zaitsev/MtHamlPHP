<?php

namespace MtHamlPHP\Filter;

use MtHamlPHP\Dbg;
use MtHamlPHP\Environment;
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
    const MAX_RECURSION = 10;
    static private function __setOptionYaml(Array  $arr,  Environment $env)
    {
        foreach ($arr as $key => $val) {
            $env->setOption($key, $val);
        }
    }
    static private function __setOptionRenderer(Array  $arr,  Renderer $renderer)
    {
        /**
         * @var  $renderer \MtHamlPHP\NodeVisitor\PhpRenderer
         */
        foreach ($arr as $key => $val) {
            $renderer->envSetOption($key, $val);
        }
    }

    static private function __do_includesYaml(Array $arr,  Environment $env,$counter=0)
    {
        $counter++;
        if ($counter > self::MAX_RECURSION ) {
            throw new Exception('Infinte includes recursion!');
        }

        if (null == $base_dir = $env->getOption('includes_dir')) {
            $base_dir = getcwd();
        };
        if (!empty($arr['includes'])) {
            $files = $arr['includes'];
            unset($arr['includes']);
            if (!is_array($arr)) $arr = array(); //if only includes provided, ensure is array;
            if (!is_array($files)) {
                $files = array($files);
            }
//            Dbg::emsgd($files); return array();

            $inc = array();
            foreach ($files as $file) {
                $file = $base_dir . DIRECTORY_SEPARATOR . $file;
//                Dbg::emsgd($file);
                if (!file_exists($file)){
                    throw new Exception(sprintf('Include file not exits: %s',$file));
                }
                $inc = array_replace_recursive($inc, Yaml::parse(file_get_contents($file))); //next file overwrites prev file keys
            }
            $arr = array_replace_recursive($inc, $arr);
            if (!empty($arr['includes'])) {
                $arr = self::__do_includesYaml($arr, $env,$counter);
//                Dbg::emsgd($counter);
            }
        }
//        Dbg::emsgd($arr);
        return $arr;
    }

    static public function setOptions($node, Environment $env)
    {
        $yaml = "runtime:\n" . self::getContent($node);
        $array = Yaml::parse($yaml);
        if (is_array($array['runtime'])) {
            $array = self::__do_includesYaml($array['runtime'], $env);
            self::__setOptionYaml($array, $env);
        }
//        Dbg::emsgd($env->getOptions());
    }

    protected function renderFilter(Renderer $renderer, Filter $node)
    {
        /** @var  $renderer \MtHamlPHP\NodeVisitor\PhpRenderer */
        $yaml = "runtime:\n" . self::getContent($node);
        $array = Yaml::parse($yaml);
        if (is_array($array['runtime'])) {
            $array = self::__do_includesYaml($array['runtime'], $renderer->env);
            self::__setOptionRenderer($array, $renderer);
        }
//        Dbg::emsgd($renderer->env->getOptions());
    }


}
