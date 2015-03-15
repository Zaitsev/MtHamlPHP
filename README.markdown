# Multi target HAML - IDE compiler (runtime reduced) 

[![Build Status](https://travis-ci.org/Zaitsev/MtHamlPHP.svg)](https://travis-ci.org/Zaitsev/MtHamlPHP)

MtHaml is a PHP implementation of the [HAML language](http://haml.info/) which can target multiple languages.
This is fork based on MtHaml and MtHaml-more (both runtimes supported if needed)
Main target of this fork - implement compiling haml files to php in IDE (to not use any runtime).
### differences
This implementation based on and supports all features of [MtHaml](https://github.com/arnaud-lb/MtHaml) and [MtHaml-more](https://github.com/scil/MtHamlMore) and adds many new features.
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
%i#id.a.b{:id=>[$c,'2']}
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

##new tag :haml 
### compiler settings - we use [Symphony YAML](http://symfony.com/doc/current/components/yaml/introduction.html)
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

###custom helper functions can be defined at compile time
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
The order of lookup - tag.attribute, tag.*, *.attribute, *.*

custom helper (renderer) implemented  similar to:
```php
echo sprintf('code',$tag_name,$attribte_name,$attribute_value) 
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
    i :
        id :   <?php echo render_array('%1$s','%2$s',%3$s) ?>
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


####custom helpers used only for interpolated attributes

> `%tag.a.b` will **not** use helpers to render _class_ attribute

>  `%tag.a{:class=>[$c,$d]}` will use custom helper
   
   
### runtme engine selection 
```haml
:haml
    use_runtime=>true
    reduce_runtime=>false
#div{:class => array($position,$item2['type'], $item2['urgency']) }
:haml
    use_runtime=>true
    reduce_runtime=>true
    reduce_runtime_array_tolerant=>true
#div{:class => array($position,$item2['type'], $item2['urgency']) }
:haml
    use_runtime=>true
    reduce_runtime=>true
    reduce_runtime_array_tolerant=>false
#div{:class => array($position,$item2['type'], $item2['urgency']) }
```
render
```php
<div <?php echo MtHaml\Runtime::renderAttributes(array(array('id', 'div'), array('class', (array($position,$item2['type'], $item2['urgency'])))), 'html5', 'UTF-8', false); ?>></div>
<div id="div"<?php \MtHamlMoreRuntime\Runtime::renderAttribute('class',array($position,$item2['type'],$item2['urgency']) ,false,true,''); ?>></div>
<div id="div"<?php \MtHamlMoreRuntime\Runtime::renderAttribute('class',array($position,$item2['type'],$item2['urgency']) ,false,false,''); ?>></div>
```
#####_see 06_Custom_helper.test in test/fixtures/environment directory for more custom helpers examples_

##added input type
you can use any type as :type_value after input tag
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

