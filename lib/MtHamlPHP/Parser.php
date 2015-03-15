<?php

namespace MtHamlPHP;

use MtHaml\Node\Tag;
use MtHamlPHP\Filter\Haml;
use MtHaml\Node\Text;
use MtHaml\Node\TagAttribute;

/**
 * MtHamlPHP Parser
 */
class Parser extends \MtHamlMore\Parser
{

    protected function parseStatement($buf)
    {
        //first parse filters, so we can parse :haml with confit first
//        Dbg::emsgd($this->env->getOptions());
        if (null !== $node = $this->parseFilter($buf)) {
            if ($node->getFilter() == 'haml') {
                Haml::setOptions($node, $this->env);
            }
            return $node;
        }

        return parent::parseStatement($buf);
    }

    protected function parseTagAttributesShortcut($buf)
    {
        $attrs = array();
        // short notation for classes and ids

        $arr = $this->env->getOption('shortcut');
        if (empty($arr)) {
            return parent::parseTagAttributes($buf);
        }
        //$arr=[];
        if (empty($arr['.']) || !empty($arr['.']['tag'])) {
            $arr['.'] =array('attr' => 'class');
            }
        if (empty($arr['#']) || !empty($arr['#']['tag'])) {
            $arr['#'] =array('attr' => 'id');
        }

        foreach($arr as $s=>$p){
            if (empty($p['tag'])){
                //only attributes here
                $prefixes[]='\\'.$s;
                $shortcuts[$s]=$p;
            }
        }
//        echo Dbg::emsgds($shortcuts);
        $prefixes = implode('|', $prefixes);
        while ($buf->match('/(?P<type>'.$prefixes.')(?P<name>[\w-]+)/A', $match)) {
                if (!empty($shortcuts[$match['type']]['attr'])){
                    $names = $shortcuts[$match['type']]['attr'];
                    if (!is_array($names)) {
                        $names = array($names);
                    }
                    foreach ($names as $name) {
                        $name = new Text($match['pos'][0], $name);
                        $value = new Text($match['pos'][1], $match['name']);
                        $attr = new TagAttribute($match['pos'][0], $name, $value);
                        $attrs[] = $attr;
                    }
                }
                    else{
                        if ($match['type'] == '#') {
                            $name = 'id';
                        } else {
                            $name = 'class';
                        }


                        $name = new Text($match['pos'][0], $name);
                        $value = new Text($match['pos'][1], $match['name']);
                        $attr = new TagAttribute($match['pos'][0], $name, $value);
                        $attrs[] = $attr;

                    }
        }
        $hasRubyAttrs = false;
        $hasHtmlAttrs = false;
        $hasObjectRef = false;

        // accept ruby-attrs, html-attrs, and object-ref in any order,
        // but only one of each

        while (true) {
            switch ($buf->peekChar()) {
                case '{':
                    if ($hasRubyAttrs) {
                        break 2;
                    }
                    $hasRubyAttrs = true;
                    $newAttrs = $this->parseTagAttributesRuby($buf);
                    $attrs = array_merge($attrs, $newAttrs);
                    break;
                case '(':
                    if ($hasHtmlAttrs) {
                        break 2;
                    }
                    $hasHtmlAttrs = true;
                    $newAttrs = $this->parseTagAttributesHtml($buf);
                    $attrs = array_merge($attrs, $newAttrs);
                    break;
                case '[':
                    if ($hasObjectRef) {
                        break 2;
                    }
                    $hasObjectRef = true;
                    $newAttrs = $this->parseTagAttributesObject($buf);
                    $attrs = array_merge($attrs, $newAttrs);
                    break;
                default:
                    break 2;
            }
        }

        return $attrs;
    }

    protected function parseTag($buf)
    {
        $arr = $this->env->getOption('shortcut');
        if (empty($arr)) {
            return parent::parseTag($buf);
        }
        $shortcuts = array();
        $prefixes[] = '\%';
            foreach($arr as $s=>$p){
                if (!empty($p['tag'])){
                    $prefixes[]='\\'.$s;
                    $shortcuts[$s]=$p;
                }
            }

        $prefixes = implode('|', $prefixes);
//        Dbg::emsgd($prefixes); return;
        $tagRegex = '/
            (?P<shortcut>' . $prefixes . ')(?P<tag_name>[\w:-]+)  # explicit tag name ( %tagname )
            | (?=[.#][\w-])         # implicit div followed by class or id
                                    # ( .class or #id )
            /xA';

        //Dbg::emsgd($tagRegex); return;
        if ($buf->match($tagRegex, $match)) {
            $new_type = null;
            $tag_name = empty($match['tag_name']) ? 'div' : $match['tag_name'];
            if (!empty($shortcuts[$match['shortcut']])) {
                    $params = $shortcuts[$match['shortcut']];
                    $tag_name = $params['tag'];
                    //Dbg::emsgd($params['tag']);
                    $name = new Text($match['pos'][0], $params['attr']);
                    $value = new Text($match['pos'][1], $match['tag_name']);
                    $new_type = new TagAttribute($match['pos'][0], $name, $value);
            }

            $attributes = $this->parseTagAttributesShortcut($buf);

            $flags = $this->parseTagFlags($buf);

            $node = new Tag($match['pos'][0], $tag_name, $attributes, $flags);

            if ($new_type !== null) {
                $node->addAttribute($new_type);
            }

            $buf->skipWs();

            if (null !== $nested = $this->parseNestableStatement($buf)) {

                if ($flags & Tag::FLAG_SELF_CLOSE) {
                    $msg = 'Illegal nesting: nesting within a self-closing tag is illegal';
                    $this->syntaxError($buf, $msg);
                }

                $node->setContent($nested);
            }

            return $node;
        } else {
            return parent::parseTag($buf);
        }
    }

}

