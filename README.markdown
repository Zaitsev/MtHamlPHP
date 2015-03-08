# Multi target HAML - IDE compiler (runtime reduced) 

[![Build Status](https://travis-ci.org/Zaitsev/MtHamlPHP.svg)](https://travis-ci.org/Zaitsev/MtHamlPHP)

MtHaml is a PHP implementation of the [HAML language][1] which can target multiple languages.
This is fork based on MtHaml and MtHaml-more
Main target of this fork - implement compiling haml files to php in IDE (to not use any runtime)


### runtime if statement
 %input{:selected=>if(true), :checked=>if($bool_false), :attribute=>if(!$bool_false)}
 see 05_selected_attribute.test in test/fixtures/environment directory
 
### allowed to mix classes as 
%i.a.b{:class=>['c','d', true ? 'e': 'f', false ? 'g':'h']}

###new tag :haml for implementing custom runtime functions
see 06_Custom_helper.test in test/fixtures/environment directory
###added input type
%input:text(class="cls")
%input:text#id(class="cls")
%input:submit#id(class="cls" value="valu")
%input:text#id(class="cls" type="search")
### no runtime for data attributes
see 06_Custom_helper.test in test/fixtures/environment directory

##all credits to [Arnaud Le Blanc](https://github.com/arnaud-lb/MtHaml) and [scil](https://github.com/scil/MtHamlMore)
