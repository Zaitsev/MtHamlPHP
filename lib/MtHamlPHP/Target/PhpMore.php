<?php

namespace MtHamlPHP\Target;

use MtHaml\Target\Php;
use MtHamlPHP\NodeVisitor\PhpRenderer as PhpRenderer;
use MtHamlPHP\Environment as Environment;
use MtHamlPHP\Parser;

class PhpMore extends Php
{

    function __construct(array $options = array())
    {
        $this->setParserFactory(
            function(Environment $env, array $options) {
                return new Parser($env);
            });
        $this->setRendererFactory(
            function(Environment $env, array $options) {
                return new PhpRenderer($env);
            });
        parent::__construct($options);
    }
    public function getDefaultRendererFactory()
    {
        return function(Environment $env, array $options) {
            return new PhpRenderer($env);
        };
    }
}

