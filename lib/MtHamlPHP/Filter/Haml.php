<?php

namespace MtHamlPHP\Filter;
use MtHamlPHP\Dbg;
use MtHaml\NodeVisitor\RendererAbstract as Renderer;
use MtHaml\Node\Filter;

class Haml extends \MtHaml\Filter\Plain
{
    public function optimize(Renderer $renderer, Filter $node, $options)
    {
        $this->renderFilter($renderer, $node, $options);
    }
    protected function renderFilter(Renderer $renderer, Filter $node )
    {
        foreach ($node->getChilds() as $line) {
            foreach ($line->getContent()->getChilds() as $child) {
                list($key,$val) = explode('=>',$child->getContent());
                if (!empty($key)) {
                    $renderer->envSetOption(trim($key), trim($val));
                }
            }
        }
    }
}
