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
        return parent::parseTagAttributes($buf);
    }

    protected function parseTag($buf)
    {
        $tagRegex = '/
            (?P<shortcut>[%&])(?P<tag_name>[\w:-]+)  # explicit tag name ( %tagname )
            | (?=[.#][\w-])         # implicit div followed by class or id
                                    # ( .class or #id )
            /xA';


        if ($buf->match($tagRegex, $match)) {
            $new_type = null;
            if ($match['shortcut'] == '&') {
                //Dbg::emsgd($this->env->getOption('shortcut'));
                $tag_name = 'input';
                $name = new Text($match['pos'][0], 'type');
                $value = new Text($match['pos'][1], $match['tag_name']);
                $new_type = new TagAttribute($match['pos'][0], $name, $value);
            } else {
                $tag_name = empty($match['tag_name']) ? 'div' : $match['tag_name'];
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

