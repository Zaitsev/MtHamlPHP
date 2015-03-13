# Multi target HAML - IDE compiler (runtime reduced) 

[![Build Status](https://travis-ci.org/Zaitsev/MtHamlPHP.svg)](https://travis-ci.org/Zaitsev/MtHamlPHP)

MtHaml is a PHP implementation of the [HAML language](http://haml.info/) which can target multiple languages.
This is fork based on MtHaml and MtHaml-more (both runtimes supported if needed)
Main target of this fork - implement compiling haml files to php in IDE (to not use any runtime)

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
### compiler settings
```haml
%i.a.b{:class=>['c',$e]}
:haml
  use_runtime => false
  enable_escaper => true
  escape_attrs => true
%i.a.b{:class=>['c',$e]}
```
rendered
```php
<i class="a b <?php echo( implode(' ',array('c',$e))) ;?>"></i>
<i class="a b <?php echo ( htmlspecialchars( implode(' ',array('c',$d)),ENT_QUOTES,"UTF-8")) ;?>"></i>
```

###custom helper functions per HTML tag,attribute or tag & attribute
#####common syntax is  
> `tag_name.attribute_name.helper` to `<tag_name attribute_name="value">`

> `tag.helper` to render all attributes of `<tag_name>`

> `attribute_name.helper` to render attribute with name _attribute_name_ for all tags 

custom helper (renderer) implemented  similar to:
```php
echo sprintf('code',$tag_name,$attribte_name,$attribute_value) 
```
for example:
> `i.class.helper` used to render all `<i>` tags _class_ attribute

> `i.id.helper` used to render all `<i>` tags _id_ attribute

> `i.custom_attr.helper` used to render all `<i>` tags attributes named _custom_attr_

> `data-.helper`  used to render any tag _data_ attribute

> `class.helper` used to render _class_ attribute for all tags

> `custom.helper` used to render any attribute of all tags

```haml
:haml
  use_runtime => false
  enable_escaper => false
  i.class.helper=> <?php /* %1$s.%2$s */ echo render_array('%1$s','%2$s',%3$s) ?>
  i.id.helper=> <?php /* %1$s.%2$s */ echo render_array('%1$s','%2$s',%3$s) ?>  
:php  
  function render_array($tag,$attr,$arr){
      $fl=array() ; array_walk_recursive($arr, function($i,$k) use (&$fl) {$fl[]=$i;});  echo attr.'="'.implode(' ',$fl).'"';
   }
%i#id.a.b{:id=>[$c,'2'],:class=>['c','d'] }
```
render
```php
<i <?php /* i.id */ echo render_array('i','id',array('id',[$c,'2'])) ?>  <?php /* i.class */ echo render_array('i','class',array('a','b',['c','d'])) ?> ></i>
```
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
