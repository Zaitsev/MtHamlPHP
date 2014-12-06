<?php

namespace MtHamlPHP\Target;

use MtHamlPHP\NodeVisitor\PhpRenderer;
use MtHamlPHP\Environment;

class Php extends \MtHaml\Target\TargetAbstract
{
    public function __construct(array $options = array())
    {
        parent::__construct($options + array(
            'midblock_regex' => '~else\b|else\s*if\b|catch\b~A',
        ));
    }

    public function getDefaultRendererFactory()
    {
        return function(Environment $env, array $options) {
            return new PhpRenderer($env);
        };
    }
}

