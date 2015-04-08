# Multi target HAML - IDE compiler (runtime reduced) 

[![Build Status](https://travis-ci.org/Zaitsev/MtHamlPHP.svg)](https://travis-ci.org/Zaitsev/MtHamlPHP)

MtHaml is a PHP implementation of the [HAML language](http://haml.info/) which can target multiple languages.
This is fork based on MtHaml and MtHaml-more (both runtimes supported if needed)
Main target of this fork - implement compiling haml files to php in IDE (to not use any runtime).
### differences
This implementation based on and supports all features of [MtHaml](https://github.com/arnaud-lb/MtHaml) and adds many new own.
But its focused mostly to **compile haml files to plain PHP**, not to be used as template engine.

You can use [grunt task](https://github.com/Zaitsev/grunt-mthamlphp-vlz) and [IDEA plugin](https://github.com/Zaitsev/mthamlphp-idea-plugin) to compile haml to php when editing haml-file in IDE.
### for basic usage read [MtHaml](https://github.com/arnaud-lb/MtHaml) and [MtHaml-more](https://github.com/scil/MtHamlMore) documentation.
## support IDE highlight
to allow IDE recognize php code in :php section you can use it as
```
:php <?
```
## simplified array definitions 
    `['c','d']` is equeal to `array('c','d')`
    
    all statements are equial:
    
    ```haml
    %i{:data=>['a'=>'a','b'=>$c, 'e'=> true ? $c : $e]}
    %i{:data=>{'a'=>'a','b'=>$c, 'e'=> true ? $c : $e}}
    %i{:data=>array('a'=>'a','b'=>$c, 'e'=> true ? $c : $e)}
    ```
## attributes mixing
```haml
%i.a.b{:class=>['c',$e]}
```
rendered
```php
<i class="a b <?php echo( implode(' ',array('c',$e))) ;?>"></i>
```
## id attributes with prefixes with '_'
```haml
%i#id{:id=>[$c,'2']}
```
rendered
```php
<i id="id_<?php echo( implode('_',array($c,'2'))) ;?>"></i>
```
## advanced data attribute
```haml
%i{:data=>['a'=>'a','b'=>$c, 'e'=> true ? $c : $e]}
%i(data-a="#{$c}" data="#{array($e=>$c)}")
%input{:data=>$data_array}  
```
rendered
```php
<i <?php foreach(array('a'=>'a','b'=>$c, 'e'=> true ? $c : $e) as $k=>$v) {echo " data-$k=\"$v\" ";} ?>></i>
<i data-a="<?php echo($c) ;?>"<?php foreach(array($e=>$c) as $k=>$v) {echo " data-$k=\"$v\" ";} ?>></i>
<input<?php foreach($data_array as $k=>$v) {echo " data-$k=\"$v\" ";} ?>>
```
## runtime _if_ statement
```haml
 %input{:selected=>if(true), :checked=>if($bool_false), :attribute=>if(!$bool_false)}
```
rendered
```php
<input <?php echo ((true)? "selected" :"") ;?> <?php echo (($bool_false)? "checked" :"") ;?> <?php echo ((!$bool_false)? "attribute" :"") ;?>>
```
 see 05_selected_attribute.test in test/fixtures/environment directory
 
## allowed to mix classes 
```haml
%i.a.b{:class=>['c','d']}
%i.a.b{:class=>['c','d', true ? 'e': 'f', false ? 'g':'h']}
```
rendered
```php
<i class="a b <?php echo( implode(' ',array('c','d'))) ;?>"></i>
<i class="a b <?php echo( implode(' ',array('c','d', true ? 'e': 'f', false ? 'g':'h'))) ;?>"></i>
```
## includes
You can include or require haml partials
```haml
-#file partial.haml
%p
    %i Included

-#file main.haml
.posts
	@@inlude partial.haml
```	
this is same as 
```haml
.posts
    %p
        %i Included
    
```    
use `@@inlude /path/file` to inlude  or `@@require /path/file` to require files.
if file not found, `require` will throw error and stop processing, `include` will not.

##new tag :haml 
This section used to manage Run-time settings of compiler. You can change mostly any behavior or haml-parser and render options.

for `:haml` section  [YAML](http://symfony.com/doc/current/components/yaml/yaml_format.html)  syntax used.
_Symfony YAML Component used to parse configs._
### imports
You an use `imports:` directive to include config files;

file config-1.yaml :
```yaml
enable_escaper: false
shortcut:
        '?':
            tag: input
            attr: type
includes: ./config-2.yaml
```
file config-2.yaml:
```yaml

shortcut:
        '@':
            attr: [role,data-role]
```
_for more info about `shortcut:` directive see below._

```haml
:haml
    includes: ./config-1.yaml
?text.a(value="#{$name}")
%a.cls@admin some text
```
render
```html
<input class="a" value="<?php echo($name) ;?>" type="text">
<a class="cls" role="admin" data-role="admin">some text</a>
```
use `includes_dir` option to set relative path to include configs.
```php
new MtHamlPHP\Environment('php', array('includes_dir' => dirname(__FILE__)));
```

### compiler settings 
You can set or change MtHaml Environment options:
```haml
%i.a.b{:class=>['c',$e]}
:haml
  escape_attrs : true
%i.a.b{:class=>['c',$e]}
```
rendered
```php
<i class="a b <?php echo( implode(' ',array('c',$e))) ;?>"></i>
<i class="a b <?php echo ( htmlspecialchars( implode(' ',array('c',$d)),ENT_QUOTES,"UTF-8")) ;?>"></i>
```
### Shortcuts
this inspired by [Slim Shortcuts](http://www.rubydoc.info/gems/slim/frames#Shortcuts)

define shortcut to render tag with attribute:
```haml
:haml
    shortcut:
        '?':
            tag : input
            attr : type
?text.a
```     
rendered
```html
<input class="a" type="text">
```       

You can use shortcuts to render attributes of any tags:
```haml
:haml
    shortcut:
        '@':
            attr: [class,role,data-role]
%a.cls@admin
```
rednder
```html
<a class="cls admin" role="admin" data-role="admin"></a>
```
#### You can not use PHP code in shortcuts
###Custom helper functions 
you can use own functions to render attributes
And yes, you can  define them in `:haml` section
#####common syntax is  
```yaml
helpers:
  'i' :         #<i> tag only
    class       : <?php /* %1$s.%2$s */ echo render_array('%1$s','%2$s',%3$s) ?>    # class attribute of tag <i>
    id          : <?php /* %1$s.%2$s */ echo render_array('%1$s','%2$s',%3$s) ?>    # id attribute of tag <i>
    custom_attr : <?php /* %1$s.%2$s */ echo custom_attr('%1$s','%2$s',%3$s) ?>     # attribute named "custom_attr" of tag <i>
    
  '*':          #all tags          
    class       : <?php /* %1$s.%2$s */ echo all_class('%1$s','%2$s',%3$s) ?>    
    id          : <?php /* %1$s.%2$s */ echo all_id('%1$s','%2$s',%3$s) ?>
    data-       : <?php /* %1$s.%2$s */ echo render_data('%1$s','%2$s',%3$s) ?>
    '*'         : <?php /* %1$s.%2$s */ echo all_attr('%1$s','%2$s',%3$s) ?>        #all attributes of all tags
  a:            #<a> tag only
    '*'         : <?php /* %1$s.%2$s */ echo all_attr('%1$s','%2$s',%3$s) ?>        #all attributes of tag <a>
```        
The order of lookup - `tag.attribute`, `tag.*`, `*.attribute`, `*.*`

custom helper (renderer) implemented  similar to:
```php
echo sprintf('string_with_function_call',$tag_name,$attribte_name,$attribute_value) 
```
for example:
```haml
:php
  function render_array($tag,$attr,$arr){
      $fl=array() ;
      array_walk_recursive(
        $arr
        , function($i,$k) use (&$fl)
            {
	            $fl[]=$i;
            }
      );
      echo $attr.'="'.implode(' ',$fl).'"';
   }
:haml
  helpers:
    i :
        class: <?php echo render_array('%1$s','%2$s',%3$s) ?>
%i.a.b{class=>['c','d']} text
```
rendered to 
```php
<i <?php echo render_array('i','class',array('a','b',['c','d'])) ?> >text</i>
```
and executed to 
```html
<i class="a b c d" >text</i>
```


####custom helpers used only for interpolated (parsed) attributes

> `%tag.a.b` **will not** use helpers to render _class_ attribute

>  `%tag.a{:class=>[$c,$d]}` **will** use custom helper
   
   
### runtme engine selection 
`use_runtime`  - if true, compiler will use standart runtime

see MtHaml documentation for more info of runtime usage;

```haml
:haml
    use_runtime=>true
#div{:class => array($position,$item2['type'], $item2['urgency']) }
:haml
    use_runtime=>false
#div{:class => array($position,$item2['type'], $item2['urgency']) }
```
render
```php
<div <?php echo MtHaml\Runtime::renderAttributes(array(array('id', 'div'), array('class', (array($position,$item2['type'], $item2['urgency'])))), 'html5', 'UTF-8', false); ?>></div>
<div id="div" <?php echo( implode(' ',array($position,$item2['type'], $item2['urgency']))) ;?>></div>
```
#####_see 06_Custom_helper.test in test/fixtures/environment directory for more custom helpers examples_

##added input type
you can use any type as :type_value for `type="type_value"` after input tag:

```haml
%input:text.cls
%input:submit#id(value="valu")
%input:text#id(class="cls" type="search")
```
rendered
```php
<input class="cls" type="text">
<input id="id" value="valu" type="submit">
<input id="id" class="cls" type="text">
```
## no runtime for data attributes
see 06_Custom_helper.test in test/fixtures/environment directory

##all credits to [Arnaud Le Blanc](https://github.com/arnaud-lb/MtHaml) and [scil](https://github.com/scil/MtHamlMore)


