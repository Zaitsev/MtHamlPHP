<?php
$env = new MtHamlPHP\Environment('php', array('enable_escaper' => false));
echo $env->compileString($parts['HAML'], "$file.haml");
