<?php

namespace MtHamlPHP\Target;

use MtHamlPHP\Target\Php;
use MtHamlPHP\NodeVisitor\PhpRenderer;
use MtHamlPHP\Environment;
use MtHamlMore\Parser;

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

