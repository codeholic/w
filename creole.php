<?php

/*
 * creole - PHP Creole 1.0 Wiki Markup Parser
 *
 * Copyright (c) 2009, 2010 Ivan Fomichev
 *
 * Portions Copyright (c) 2007 Chris Purcell
 *
 * Permission is hereby granted, free of charge, to any person obtaining a
 * copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included
 * in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 */

function mild_htmlspecialchars($string) {
    $subst = array(
        '"' => '&quot;',
        '&' => '&amp;',
        '<' => '&lt;',
        '>' => '&gt;',
    );
    return preg_replace('/(&(?:\w+|#x[0-9A-Fa-f]+|#\d+);|["&<>])/e',
        'isset(\$subst["$1"]) ? \$subst["$1"] : "$1"', $string);
}

class creole_rule {
    var $regex = false;
    var $capture = false;
    var $replace_regex = false;
    var $replace_string = false;
    var $tag = false;
    var $attrs = array();
    var $children = array();
    var $fallback = false;
   
    function creole_rule($params = array()) {
        foreach ($params as $k => $v) {
            eval('$this->' . $k . ' = $v;');
        }
    }
  
    function build($node, $matches, $options = array()) {
        if ($this->capture !== false) {
            $data = $matches[$this->capture][0];
        }
       
        if ($this->tag !== false) {
            $target = new creole_node($this->tag);
            $node->append($target);
        }
        else {
            $target = $node;
        }
       
        if (isset($data)) {
            if ($this->replace_regex) {
                $data = preg_replace($this->replace_regex, $this->replace_string, $data);
            }
            $this->apply($target, $data, $options);
        }
       
        foreach ($this->attrs as $attr => $value) {
            $target->set_attribute($attr, $value);
        }
    }
   
    function match($data) {
        return preg_match($this->regex, $data, $matches, PREG_OFFSET_CAPTURE)
            ? $matches : false;
    }
   
    function apply($node, $data, $options = array()) {
        $tail = $data;
       
        if (!is_object($this->fallback)) {
            $this->fallback = $this->fallback
                ? new creole_rule($this->fallback)
                : new creole_rule_default_fallback();
        }
       
        while (true) {
            $best = false;
            $rule = false;
           
            for ($i = 0; $i < count($this->children); $i++) {
                if (!isset($matches[$i])) {
                    if (!is_object($this->children[$i])) {
                        $this->children[$i] = new creole_rule($this->children[$i]);
                    }
                    $matches[$i] = $this->children[$i]->match($tail);
                }
               
                if ($matches[$i] && (!$best || $matches[$i][0][1] < $best[0][1])) {
                    $best = $matches[$i];
                    $rule = $this->children[$i];
                    if ($best[0][1] == 0) {
                        break;
                    }
                }
            }
           
            $pos = $best ? $best[0][1] : strlen($tail);
            if ($pos > 0) {
                $this->fallback->apply($node, substr($tail, 0, $pos), $options);
            }
           
            if (!$best) {
                break;
            }
           
            if (!is_object($rule)) {
                $rule = new creole_rule($rule);
            }
            $rule->build($node, $best, $options);
           
            $chopped = $best[0][1] + strlen($best[0][0]);
            $tail = substr($tail, $chopped);
           
            for ($i = 0; $i < count($this->children); $i++) {
                if (isset($matches[$i])) {
                    if ($matches[$i][0][1] >= $chopped) {
                        $matches[$i][0][1] -= $chopped;
                    }
                    else {
                        unset($matches[$i]);
                    }
                }
            }
        }
    }
}

class creole_rule_default_fallback extends creole_rule {
    function apply($node, $data, $options = array()) {
        $node->append(mild_htmlspecialchars($data));
    }
}

class creole_rule_image extends creole_rule {
    function creole_rule_image($params = array()) {
        parent::creole_rule($params);
    }
   
    function build($node, $matches, $options = array()) {
        $img = new creole_node('img');
        $img->set_attribute('src', $matches[1][0]);
        $img->set_attribute('alt', preg_replace('/~(.)/', '$1', $matches[2][0]));

        $node->append($img);
    }
}


class creole_rule_named_uri extends creole_rule {
    function creole_rule_named_uri($params = array()) {
        parent::creole_rule($params);
    }
   
    function build($node, $matches, $options = array()) {
        $link = new creole_node('a');
        $link->set_attribute('href', rawurldecode($matches[1][0]));
       
        $this->apply($link, $matches[2][0], $options);
        $node->append($link);
    }
}

class creole_rule_unnamed_uri extends creole_rule_named_uri {
    function creole_rule_unnamed_uri($params = array()) {
        parent::creole_rule_named_uri($params);
    }
   
    function build($node, $matches, $options = array()) {
        return parent::build($node, array($matches[0], $matches[1], $matches[1]), $options);
    }
}

class creole_rule_named_link extends creole_rule {
    function creole_rule_named_link($params = array()) {
        parent::creole_rule($params);
    }
   
    function format_link($link, $format) {
        if (function_exists($format)) {
            return call_user_func($format, $link);
        }
        return sprintf($format, rawurlencode($link));
    }

    function build($node, $matches, $options = array()) {
        $link = preg_replace('/~(.)/', '$1', $matches[1][0]);
       
        if (isset($options['current_page']) && $options['current_page'] == $link) {
            $self_references = isset($options['self_references']) ? $options['self_references'] : 'allow';

            switch ($self_references) {
                case 'ignore':
                    $this->apply($node, $matches[2][0], $options);
                    return;
               
                case 'emphasize':
                    $child = new creole_node('strong');
                    break;
            }
        }
       
        if (!isset($child)) {
            $child = new creole_node('a');
            $child->set_attribute(
                'href',
                isset($options['link_format']) ? $this->format_link($link, $options['link_format']) : $link
            );
        }
       
        $this->apply($child, $matches[2][0], $options);
        $node->append($child);
    }
}

class creole_rule_unnamed_link extends creole_rule_named_link {
    function creole_rule_unnamed_link($params = array()) {
        parent::creole_rule_named_link($params);
    }
   
    function build($node, $matches, $options = array()) {
        return parent::build($node, array($matches[0], $matches[1], $matches[1]), $options);
    }
}

class creole_rule_named_interwiki_link extends creole_rule_named_link {
    function creole_rule_named_interwiki_link($params = array()) {
        parent::creole_rule_named_link($params);
    }

    function build($node, $matches, $options = array()) {
        if (isset($options['interwiki'])) {
            preg_match('/(.*?):(.*)/', $matches[1][0], $m);
        }
       
        if (!isset($m[1]) || !isset($options['interwiki'][$m[1]])) {
            return parent::build($node, $matches, $options);
        }
       
        $format = $options['interwiki'][$m[1]];

        $link = new creole_node('a');
        $link->set_attribute('href', $this->format_link(preg_replace('/~(.)/', '$1', $m[2]), $format));
       
        $this->apply($link, $matches[2][0], $options);
        $node->append($link);
    }
}

class creole_rule_unnamed_interwiki_link extends creole_rule_named_interwiki_link {
    function creole_rule_unnamed_interwiki_link($params = array()) {
        parent::creole_rule_named_interwiki_link($params);
    }

    function build($node, $matches, $options = array()) {
        return parent::build($node, array($matches[0], $matches[1], $matches[1]), $options);
    }
}

class creole_rule_extension extends creole_rule {
    function creole_rule_extension($params = array()) {
        parent::creole_rule($params);
    }
   
    function build($node, $matches, $options = array()) {
        if (isset($options['extension']) && is_callable($options['extension'])) {
            call_user_func($options['extension'], $node, $matches[1][0]);
        }
        else {
            $node->append(mild_htmlspecialchars($matches[0][0]));
        }
    }
}

class creole_node {
    var $tag;
    var $attrs;
    var $content = array();
   
    function creole_node($tag = false) {
        $this->tag = $tag;
    }
   
    function append($node) {
        $this->content[] = $node;
    }
   
    function set_attribute($attr, $value) {
        $this->attrs[$attr] = $value;
    }
   
    function as_string() {
        $result = '';
        foreach ($this->content as $item) {
            $result .= is_object($item) ? $item->as_string() : $item;
        }
       
        if (!empty($this->tag)) {
            $tag = $this->tag;
           
            $attrs = '';
            if (!empty($this->attrs)) {
                foreach ($this->attrs as $attr => $value) {
                    $attrs .= ' ' . $attr . '="' . mild_htmlspecialchars($value) . '"';
                }
            }
           
            $result = empty($result) ? "<$tag$attrs/>" : "<$tag$attrs>$result</$tag>";
        }
       
        return $result;
    }
}

class creole {
    var $grammar;
    var $options;
   
    function creole($options = array()) {
        $this->options = $options;
       
        $rx['ext'] = '<<<([^>]*(?:>>?(?!>)[^>]*)*)>>>';
        $rx['link'] = '[^\]|~\n]*(?:(?:\](?!\])|~.)[^\]|~\n]*)*';
        $rx['link_text'] = '[^\]~\n]*(?:(?:\](?!\])|~.)[^\]~\n]*)*';
        $rx['uri_prefix'] = '\b(?:(?:https?|ftp):\\/\\/|mailto:)';
        $rx['uri'] = $rx['uri_prefix'] . $rx['link'];
        $rx['raw_uri'] = $rx['uri_prefix'] . '\S*[^\s!"\',.:;?]';
        $rx['interwiki_prefix'] = '[\w.]+:';
        $rx['interwiki_link'] = $rx['interwiki_prefix'] . $rx['link'];
        $rx['image'] = '\{\{((?!\{)[^|}\n]*(?:}(?!})[^|}\n]*)*)' .
            '(?:\|([^}~\n]*((}(?!})|~.)[^}~\n]*)*))?}}';
       
        $g = array(
            'hr' => array(
                'tag' => 'hr',
                'regex' => '/(^|\n)\s*----\s*(\n|$)/'
            ),
           
            'br' => array(
                'tag' => 'br',
                'regex' => '/\\\\\\\\/'
            ),
           
            'pre' => array(
                'tag' => 'pre',
                'regex' => '/(^|\n)\{\{\{[ \t]*\n((.*\n)*?)}}}[ \t]*(\n|$)/',
                'capture' => 2,
                'replace_regex' => '/^ ([ \t]*}}})/m',
                'replace_string' => '$1'
            ),
            'tt' => array(
                'tag' => 'tt',
                'regex' => '/\{\{\{(.*?}}}+)/',
                'capture' => 1,
                'replace_regex' => '/}}}$/',
                'replace_string' => ''
            ),
           
            'ul' => array(
                'tag' => 'ul',
                'regex' => '/(^|\n)([ \t]*\*[^*#].*(\n|$)([ \t]*[^\s*#].*(\n|$))*([ \t]*[*#]{2}.*(\n|$))*)+/',
                'capture' => 0
            ),
            'ol' => array(
                'tag' => 'ol',
                'capture' => 0,
                'regex' => '/(^|\n)([ \t]*#[^*#].*(\n|$)([ \t]*[^\s*#].*(\n|$))*([ \t]*[*#]{2}.*(\n|$))*)+/'
            ),
            'li' => array(
                'tag' => 'li',
                'capture' => 0,
                'regex' => '/[ \t]*([*#]).+(\n[ \t]*[^*#\s].*)*(\n[ \t]*\1[*#].+)*/',
                'replace_regex' => '/(^|\n)[ \t]*[*#]/',
                'replace_string' => '$1'
            ),

            'table' => array(
                'tag' => 'table',
                'regex' => '/(^|\n)(\|.*?[ \t]*(\n|$))+/',
                'capture' => 0
            ),
            'tr' => array(
                'tag' => 'tr',
                'regex' => '/(^|\n)(\|.*?)\|?[ \t]*(\n|$)/',
                'capture' => 2
            ),
            'th' => array(
                'tag' => 'th',
                'regex' => '/\|+=([^|]*)/',
                'capture' => 1
            ),
            'td' => array(
                'tag' => 'td',
                'regex' => '/\|+([^|~\[{]*((~(.|(?=\n)|$)|' .
                       '\[\[' . $rx['link'] . '(\|' . $rx['link_text'] . ')?\]\]' .
                       '|' . $rx['image'] . '|[\[{])[^|~]*)*)/',
                'capture' => 1
            ),
           
            'single_line' => array(
                'regex' => '/.+/',
                'capture' => 0
            ),
            'text' => array(
                'regex' => '/(^|\n)([ \t]*[^\s].*(\n|$))+/',
                'capture' => 0
            ),
            'p' => array(
                'tag' => 'p',
                'regex' => '/(^|\n)([ \t]*\S.*(\n|$))+/',
                'capture' => 0
            ),

            'strong' => array(
                'tag' => 'strong',
                'regex' => '/\*\*([^*~]*((\*(?!\*)|~(.|(?=\n)|$))[^*~]*)*)(\*\*|\n|$)/',
                'capture' => 1
            ),
            'em' => array(
                'tag' => 'em',
                'regex' => '/\/\/(((?!' . $rx['uri_prefix'] . ')[^\/~])*' .
                       '((' . $rx['raw_uri'] . '|\/(?!\/)|~(.|(?=\n)|$))' .
                       '((?!' . $rx['uri_prefix'] . ')[^\/~])*)*)(\/\/|\n|$)/',
                'capture' => 1
            ),

            'img' => new creole_rule_image(array(
                'regex' => '/' . $rx['image'] . '/',
            )),
           
            'escaped_sequence' => array(
                'regex' => '/~(' . $rx['raw_uri'] . '|.)/',
                'capture' => 1,
                'tag' => 'span',
                'attrs' => array( 'class' => 'escaped' )
            ),
            'escaped_symbol' => array(
                'regex' => '/~(.)/',
                'capture' => 1,
                'tag' => 'span',
                'attrs' => array( 'class' => 'escaped' )
            ),
           
            'named_uri' => new creole_rule_named_uri(array(
                'regex' => '/\[\[(' . $rx['uri'] . ')\|(' . $rx['link_text'] . ')\]\]/'
            )),
            'unnamed_uri' => new creole_rule_unnamed_uri(array(
                'regex' => '/\[\[(' . $rx['uri'] . ')\]\]/'
            )),
            'named_link' => new creole_rule_named_link(array(
                'regex' => '/\[\[(' . $rx['link'] . ')\|(' . $rx['link_text'] . ')\]\]/'
            )),
            'unnamed_link' => new creole_rule_unnamed_link(array(
                'regex' => '/\[\[(' . $rx['link'] . ')\]\]/'
            )),
            'named_interwiki_link' => new creole_rule_named_interwiki_link(array(
                'regex' => '/\[\[(' . $rx['interwiki_link'] . ')\|(' . $rx['link_text'] . ')\]\]/'
            )),
            'unnamed_interwiki_link' => new creole_rule_unnamed_interwiki_link(array(
                'regex' => '/\[\[(' . $rx['interwiki_link'] . ')\]\]/'
            )),

            'raw_uri' => new creole_rule_unnamed_uri(array(
                'regex' => '/(' . $rx['raw_uri'] . ')/',
            )),
           
            'extension' => new creole_rule_extension(array(
                'regex' => '/' . $rx['ext'] . '/',
            ))
        );
       
        for ($i = 1; $i <= 6; $i++) {
            $g['h' . $i] = array(
                'tag' => 'h' . $i,
                'regex' => '/(^|\n)[ \t]*={' . $i . '}[ \t]' .
                       '([^~]*?(~(.|(?=\n)|$))*)[ \t]*=*\s*(\n|$)/',
                'capture' => 2
            );
        }
       
        $g['named_uri']->children = $g['unnamed_uri']->children = $g['raw_uri']->children =
                $g['named_link']->children = $g['unnamed_link']->children =
                $g['named_interwiki_link']->children = $g['unnamed_interwiki_link']->children =
            array(&$g['escaped_symbol'], &$g['img']);
       
        $g['ul']['children'] = $g['ol']['children'] = array(&$g['li']);
        $g['li']['children'] = array(&$g['ul'], &$g['ol']);
        $g['li']['fallback'] = array('children' => array(&$g['text']));
       
        $g['table']['children'] = array(&$g['tr']);
        $g['tr']['children'] = array(&$g['th'], &$g['td']);
        $g['th']['children'] = $g['td']['children'] = array(&$g['single_line']);
       
        $g['h1']['children'] = $g['h2']['children'] = $g['h3']['children'] =
                $g['h4']['children'] = $g['h5']['children'] = $g['h6']['children'] =
                $g['single_line']['children'] = $g['text']['children'] = $g['p']['children'] =
                $g['strong']['children'] = $g['em']['children'] =
            array(
                &$g['escaped_sequence'], &$g['strong'], &$g['em'], &$g['br'], &$g['raw_uri'],
                &$g['named_uri'], &$g['named_interwiki_link'], &$g['named_link'],
                &$g['unnamed_uri'], &$g['unnamed_interwiki_link'], &$g['unnamed_link'],
                &$g['tt'], &$g['img']
            );

        $g['root'] = new creole_rule(array(
            'children' => array(
                &$g['h1'], &$g['h2'], &$g['h3'], &$g['h4'], &$g['h5'], &$g['h6'],
                &$g['hr'], &$g['ul'], &$g['ol'], &$g['pre'], &$g['table'], &$g['extension']
            ),
            'fallback' => array('children' => array(&$g['p']))
        ));
       
        $this->grammar = $g;
    }
   
    function parse($data, $options = array()) {
        $node = new creole_node();
        $data = preg_replace('/\r\n?/', "\n", $data);
        $options = array_merge($this->options, $options);
        $this->grammar['root']->apply($node, $data, $options);
        return $node->as_string();
    }
}

?>
