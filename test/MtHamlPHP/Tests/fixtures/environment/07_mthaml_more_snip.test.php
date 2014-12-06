<?php
$env = new MtHamlPHP\Environment('php', array('enable_escaper' => false));
$env->addFilter('haml', new MtHamlPHP\Filter\Haml());
echo $env->compileString($parts['HAML'], "$file.haml");
