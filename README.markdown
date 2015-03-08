# Multi target HAML - IDE compiler (runtime reduced) 

[![Build Status](https://travis-ci.org/Zaitsev/MtHamlPHP.svg)](https://travis-ci.org/Zaitsev/MtHamlPHP)

MtHaml is a PHP implementation of the [HAML language][1] which can target multiple languages.
This is fork based on MtHaml and MtHaml-more
Main target of this fork - implement compiling haml files to php in IDE (to not use any runtime)


### runtime if statement
```
 %input{:selected=>if(true), :checked=>if($bool_false), :attribute=>if(!$bool_false)}
```
rendered
```
<input <?php echo ((true)? "selected" :"") ;?> <?php echo (($bool_false)? "checked" :"") ;?> <?php echo ((!$bool_false)? "attribute" :"") ;?>>
```
 see 05_selected_attribute.test in test/fixtures/environment directory
 
### allowed to mix classes as 
```
%i.a.b{:class=>['c','d', true ? 'e': 'f', false ? 'g':'h']}
```
rendered
```
<i class="a b <?php echo( implode(' ',array('c','d', true ? 'e': 'f', false ? 'g':'h'))) ;?>"></i>
```

###new tag :haml for implementing custom runtime functions
see 06_Custom_helper.test in test/fixtures/environment directory
###added input type
```
%input:text(class="cls")
%input:text#id(class="cls")
%input:submit#id(class="cls" value="valu")
%input:text#id(class="cls" type="search")
```
rendered
```
<input class="cls" type="text">
<input id="id" class="cls" type="text">
<input id="id" class="cls" value="valu" type="submit">
<input id="id" class="cls" type="text">
```
### no runtime for data attributes
see 06_Custom_helper.test in test/fixtures/environment directory

##all credits to [Arnaud Le Blanc](https://github.com/arnaud-lb/MtHaml) and [scil](https://github.com/scil/MtHamlMore)
