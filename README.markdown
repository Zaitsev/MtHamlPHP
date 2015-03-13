# Multi target HAML - IDE compiler (runtime reduced) 

[![Build Status](https://travis-ci.org/Zaitsev/MtHamlPHP.svg)](https://travis-ci.org/Zaitsev/MtHamlPHP)

MtHaml is a PHP implementation of the [HAML language](http://haml.info/) which can target multiple languages.
This is fork based on MtHaml and MtHaml-more (both runtimes supported if needed)
Main target of this fork - implement compiling haml files to php in IDE (to not use any runtime)

### support IDE highlight
to allow IDE recognize php code in :php section you can use it as
```
:php <?
```
### simplified array definitions 
`['c','d']` is equeal to `array('c','d')`
### attributes mixing
```haml
%i.a.b{:class=>['c',$e]}
```
rendered
```php
<i class="a b <?php echo( implode(' ',array('c',$e))) ;?>"></i>
```
### id attributes with prefixes with '_'
```haml
%i#id.a.b{:id=>[$c,'2']}
```
rendered
```php
<i id="id_<?php echo( implode('_',array($c,'2'))) ;?>" class="a b"></i>
```
### advanced data attribute
```haml
:php
  $c='c-php'; $e = 'e-php'; $id = 'php1';$bool_true=false;$data_id=1;
  $data_array = array('ok'=>1,'no'=>$data_id+1);
%i.a.b{:data=>['a'=>'a','b'=>$c, 'e'=> true ? $c : $e]}
%i.a.b{:data=>{'a'=>'a','b'=>$c, 'e'=> true ? $c : $e}}
%i.a.b{:data=>array('a'=>'a','b'=>$c, 'e'=> true ? $c : $e)}
%i.a.b(data-a="#{$c}" data="#{array($e=>$c)}")
%input{:data=>$data_array}  
```
rendered
```php
<i class="a b"<?php foreach(array('a'=>'a','b'=>$c, 'e'=> true ? $c : $e) as $k=>$v) {echo " data-$k=\"$v\" ";} ?>></i>
<i class="a b"<?php foreach(array('a'=>'a','b'=>$c, 'e'=> true ? $c : $e) as $k=>$v) {echo " data-$k=\"$v\" ";} ?>></i>
<i class="a b"<?php foreach(array('a'=>'a','b'=>$c, 'e'=> true ? $c : $e) as $k=>$v) {echo " data-$k=\"$v\" ";} ?>></i>
<i class="a b" data-a="<?php echo($c) ;?>"<?php foreach(array($e=>$c) as $k=>$v) {echo " data-$k=\"$v\" ";} ?>></i>
<input<?php foreach($data_array as $k=>$v) {echo " data-$k=\"$v\" ";} ?>>
```
### runtime if statement
```haml
 %input{:selected=>if(true), :checked=>if($bool_false), :attribute=>if(!$bool_false)}
```
rendered
```php
<input <?php echo ((true)? "selected" :"") ;?> <?php echo (($bool_false)? "checked" :"") ;?> <?php echo ((!$bool_false)? "attribute" :"") ;?>>
```
 see 05_selected_attribute.test in test/fixtures/environment directory
 
### allowed to mix classes as 
```haml
%i.a.b{:class=>['c','d']}
%i.a.b{:class=>['c','d', true ? 'e': 'f', false ? 'g':'h']}
```
rendered
```php
<i class="a b <?php echo( implode(' ',array('c','d'))) ;?>"></i>
<i class="a b <?php echo( implode(' ',array('c','d', true ? 'e': 'f', false ? 'g':'h'))) ;?>"></i>
```

###new tag :haml 
This is for implementing custom runtime functions and compiler settings
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




see 06_Custom_helper.test in test/fixtures/environment directory
###added input type
```haml
%input:text(class="cls")
%input:text#id(class="cls")
%input:submit#id(class="cls" value="valu")
%input:text#id(class="cls" type="search")
```
rendered
```php
<input class="cls" type="text">
<input id="id" class="cls" type="text">
<input id="id" class="cls" value="valu" type="submit">
<input id="id" class="cls" type="text">
```
### no runtime for data attributes
see 06_Custom_helper.test in test/fixtures/environment directory

##all credits to [Arnaud Le Blanc](https://github.com/arnaud-lb/MtHaml) and [scil](https://github.com/scil/MtHamlMore)
