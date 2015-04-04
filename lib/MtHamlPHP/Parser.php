<?php

namespace MtHamlPHP;

use MtHaml\Node\Tag;
use MtHamlPHP\Filter\Haml;
use MtHaml\Node\Text;
use MtHaml\Node\TagAttribute;
use ReflectionClass;

/**
 * MtHamlPHP Parser
 */
class Parser extends \MtHaml\Parser {
	// add Envirmonent object, MtHaml-More  see README.md : Development Rool 2
	private $env;

	public function __construct( \MtHaml\Environment $env = null ) {
		parent::__construct();
		$this->env = $env;
	}

	protected function parseStatement( $buf ) {
		//first parse filters, so we can parse :haml with configs first
//        Dbg::emsgd($this->env->getOptions());
		if ( null !== $node = $this->parseFilter( $buf ) ) {
			if ( $node->getFilter() == 'haml' ) {
				Haml::setOptions( $node, $this->env );
			}

			return $node;
		}

		return parent::parseStatement( $buf );
	}

	protected function parseTagAttributesShortcut( $buf ) {
		/**
		 * @var $buf \MtHaml\Parser\Buffer
		 */
		$attrs = array();
		// short notation for classes and ids

		$arr = $this->env->getOption( 'shortcut' );
//		Dbg::emsgd($arr);
//		if ( empty( $arr ) ) {
//			return parent::parseTagAttributes( $buf );
//		}
		//add default . and # as shortcut
		if ( empty( $arr['.'] ) || ! empty( $arr['.']['tag'] ) ) {
			$arr['.'] = array( 'attr' => 'class' );
		}
		if ( empty( $arr['#'] ) || ! empty( $arr['#']['tag'] ) ) {
			$arr['#'] = array( 'attr' => 'id' );
		}

		foreach ( $arr as $s => $p ) {
			if ( empty( $p['tag'] ) ) {
				//only attributes here
				$prefixes[]      = '\\' . $s;
				$shortcuts[ $s ] = $p;
			}
		}
		$prefixes = implode( '|', $prefixes );
		while ( $buf->match( '/(?P<type>' . $prefixes . ')(?P<name>[\w-]+)/A', $match ) ) {
			if ( ! empty( $shortcuts[ $match['type'] ]['attr'] ) ) {
				$names = $shortcuts[ $match['type'] ]['attr'];
				if ( ! is_array( $names ) ) {
					$names = array( $names );
				}
				foreach ( $names as $name ) {
					$name    = new Text( $match['pos'][0], $name );
					$value   = new Text( $match['pos'][1], $match['name'] );
					$attr    = new TagAttribute( $match['pos'][0], $name, $value );
					$attrs[] = $attr;
				}
			} else {
				if ( $match['type'] == '#' ) {
					$name = 'id';
				} else {
					$name = 'class';
				}

				$name    = new Text( $match['pos'][0], $name );
				$value   = new Text( $match['pos'][1], $match['name'] );
				$attr    = new TagAttribute( $match['pos'][0], $name, $value );
				$attrs[] = $attr;

			}
		}
		$hasRubyAttrs = false;
		$hasHtmlAttrs = false;
		$hasObjectRef = false;

		// accept ruby-attrs, html-attrs, and object-ref in any order,
		// but only one of each

		while ( true ) {
			switch ( $buf->peekChar() ) {
				case '{':
					if ( $hasRubyAttrs ) {
						break 2;
					}
					$hasRubyAttrs = true;
					$newAttrs     = $this->parseTagAttributesRuby( $buf );
					$attrs        = array_merge( $attrs, $newAttrs );
					break;
				case '(':
					if ( $hasHtmlAttrs ) {
						break 2;
					}
					$hasHtmlAttrs = true;
					$newAttrs     = $this->parseTagAttributesHtml( $buf );
					$attrs        = array_merge( $attrs, $newAttrs );
					break;
				case '[':
					if ( $hasObjectRef ) {
						break 2;
					}
					$hasObjectRef = true;
					$newAttrs     = $this->parseTagAttributesObject( $buf );
					$attrs        = array_merge( $attrs, $newAttrs );
					break;
				default:
					break 2;
			}
		}

		return $attrs;
	}

	function path_is_absolute( $path ) {
		/*
		 * This is definitive if true but fails if $path does not exist or contains
		 * a symbolic link.
		 */
		if ( realpath( $path ) == $path ) {
			return true;
		}

		if ( strlen( $path ) == 0 || $path[0] == '.' ) {
			return false;
		}

		// Windows allows absolute paths like this.
		if ( preg_match( '#^[a-zA-Z]:\\\\#', $path ) ) {
			return true;
		}

		// A path starting with / or \ is absolute; anything else is relative.
		return ( $path[0] == '/' || $path[0] == '\\' );
	}

	protected function injectFile( $buf, $type ) {
		/**
		 * @var $buf \MtHaml\Parser\Buffer
		 */

		$class    = new ReflectionClass( get_parent_class() );
		$property = $class->getProperty( "indent" );
		$property->setAccessible( true );
		$path = trim( $buf->getLine() );
		if ( null == $base_dir = $this->env->getOption( 'includes_dir' ) ) {
			$base_dir = getcwd();
		};
		$filename = false;
		if ( ( $this->path_is_absolute( $path ) && file_exists( $path ) ) ) {
			$filename = $path;
		} elseif ( file_exists( trim( $base_dir ) . DIRECTORY_SEPARATOR . $path ) ) {
			$filename = trim( $base_dir ) . DIRECTORY_SEPARATOR . $path;
		} elseif ( 'require' === $type ) {
			throw $this->syntaxError( $buf, "@require $path : file not found \n  includes_dir: $base_dir\n " . $base_dir . DIRECTORY_SEPARATOR . $path . "\n" );
		}
		$inj = @file_get_contents( $filename, true );
		if ( $inj !== false ) {

			$this->injectLines( $buf, $inj );

			return null;//restart Parser
		}

		return null;
	}

	/**
	 * @param $buf \MtHaml\Parser\Buffer
	 * @param $string string - multi-line string to inject
	 */
	protected function injectLines( $buf, $string ) {
		$level   = $this->indent->getLevel();
		$prepend = str_repeat( $this->indent->getChar(), $level * $this->indent->getWidth() );
		$lines   = preg_split( '~\r\n|\n|\r~', $string );
		$lines   = array_map(
			function ( $v ) use ( $prepend ) {
				return $prepend . $v;
			},
			array_filter( $lines, function ( $v ) {
				return ! empty( $v );
			} )
		);
		if ( empty( $lines ) ) {
			return false;
		}

		return $buf->injectLines( $lines, 1 );
	}

	protected function parseTag( $buf ) {
		$arr = (array) $this->env->getOption( 'shortcut' );
//		if ( empty( $arr ) ) {
//			return parent::parseTag( $buf );
//		}
		$shortcuts  = array();
		$prefixes[] = '\%';

		foreach ( $arr as $s => $p ) {
			if ( ! empty( $p['tag'] ) ) {
				$prefixes[]      = '\\' . $s;
				$shortcuts[ $s ] = $p;
			}
		}
		//inject @include and @require
		$shortcuts['@'][] = 'include';
		$shortcuts['@'][] = 'require';
		$prefixes[]       = '\@';

		$prefixes = implode( '|', array_unique( $prefixes ) );
//        Dbg::emsgd($prefixes); return;
		$tagRegex = '/
            (?P<shortcut>' . $prefixes . ')(?P<tag_name>[\w:-]+)  # explicit tag name ( %tagname )
            | (?=[.#][\w-])         # implicit div followed by class or id
                                    # ( .class or #id )
            /xA';

		//Dbg::emsgd($tagRegex); return;
		if ( $buf->match( $tagRegex, $match ) ) {
			$new_type = null;
			$tag_name = empty( $match['tag_name'] ) ? 'div' : $match['tag_name'];
			if ( ! empty( $shortcuts[ $match['shortcut'] ] ) ) {
				//@include adn @require
				if ( '@' === $match['shortcut'] && ( 'require' === $match['tag_name'] || 'include' === $match['tag_name'] ) ) {
					return $this->injectFile( $buf, $match['tag_name'] );
				}
				$params   = $shortcuts[ $match['shortcut'] ];
				$tag_name = $params['tag'];
				//Dbg::emsgd($params['tag']);
				$name     = new Text( $match['pos'][0], $params['attr'] );
				$value    = new Text( $match['pos'][1], $match['tag_name'] );
				$new_type = new TagAttribute( $match['pos'][0], $name, $value );
			}

			$attributes = $this->parseTagAttributesShortcut( $buf );

			$flags = $this->parseTagFlags( $buf );

			$node = new Tag( $match['pos'][0], $tag_name, $attributes, $flags );

			if ( $new_type !== null ) {
				$node->addAttribute( $new_type );
			}

			$buf->skipWs();

			if ( null !== $nested = $this->parseNestableStatement( $buf ) ) {

				if ( $flags & Tag::FLAG_SELF_CLOSE ) {
					$msg = 'Illegal nesting: nesting within a self-closing tag is illegal';
					$this->syntaxError( $buf, $msg );
				}

				$node->setContent( $nested );
			}

			return $node;
		} else {
			return parent::parseTag( $buf );
		}
	}

}

