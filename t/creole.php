<?php

require_once('./test-more.php');
require_once('../creole.php');

$tests = array(
    array(
        'name'    => "Basic paragraph markup",
        'input'   => "Basic paragraph test with <, >, & and \"",
        'output'  => "<p>Basic paragraph test with &lt;, &gt;, &amp; and &quot;</p>"
    ),
    array(
        'name'    => "Simple unordered list",
        'input'   => "* list item\n*list item 2",
        'output'  => "<ul><li> list item</li>\n<li>list item 2</li></ul>"
    ),
    array(
        'name'    => "Simple ordered list",
        'input'   => "# list item\n#list item 2",
        'output'  => "<ol><li> list item</li>\n<li>list item 2</li></ol>"
    ),
    array( // Test an ul item with a sublist
        'name'    => "Unordered item with unordered sublist",
        'input'   => "* Item\n** Subitem",
        'output'  => "<ul><li> Item<ul>\n<li> Subitem</li></ul></li></ul>"
    ),
    array( // Test an ol item with a sublist
        'name'    => "Ordered item with ordered sublist",
        'input'   => "# Item\n## Subitem",
        'output'  => "<ol><li> Item<ol>\n<li> Subitem</li></ol></li></ol>"
    ),
    array( // Test a sublist without an initial tag (should not make a list)
        'name'    => "Ordered sublist without initial tag",
        'input'   => "## Sublist item",
        'output'  => "<p>## Sublist item</p>"
    ),
    array( // Test an unordered list with an ordered sublist
        'name'    => "Unordered item with ordered sublist",
        'input'   => "* Item\n*# Subitem",
        'output'  => "<ul><li> Item<ol>\n<li> Subitem</li></ol></li></ul>"
    ),
    array(
        'name'    => "Multiline unordered item",
        'input'   => "* Item\nstill continues",
        'output'  => "<ul><li> Item\nstill continues</li></ul>"
    ),
    array(
        'name'    => "Multiline ordered item",
        'input'   => "# Item\nstill continues",
        'output'  => "<ol><li> Item\nstill continues</li></ol>"
    ),
    array(
        'name'    => "Unordered list and paragraph",
        'input'   => "* Item\n\nParagraph",
        'output'  => "<ul><li> Item</li>\n</ul><p>\nParagraph</p>"
    ),
    array(
        'name'    => "Ordered list and paragraph",
        'input'   => "# Item\n\nParagraph",
        'output'  => "<ol><li> Item</li>\n</ol><p>\nParagraph</p>"
    ),
    array(
        'name'    => "Unordered list with leading whitespace",
        'input'   => " \t* Item",
        'output'  => "<ul><li> Item</li></ul>"
    ),
    array(
        'name'    => "Ordered list with leading whitespace",
        'input'   => " \t# Item",
        'output'  => "<ol><li> Item</li></ol>"
    ),
    array(
        'name'    => "Unordered list with bold item",
        'input'   => "* Item\n* **Bold item**",
        'output'  => "<ul><li> Item</li>\n<li> <strong>Bold item</strong></li></ul>"
    ),
    array(
        'name'    => "Ordered list with bold item",
        'input'   => "# Item\n# **Bold item**",
        'output'  => "<ol><li> Item</li>\n<li> <strong>Bold item</strong></li></ol>"
    ),
    array( // Test hr
        'name'    => "Horizontal rule",
        'input'   => "Some text\n----\nSome more text",
        'output'  => "<p>Some text</p><hr/><p>Some more text</p>"
    ),
    array( // Test pre block
        'name'    => "Preformatted block",
        'input'   => "{{{\nPreformatted block\n}}}",
        'output'  => "<pre>Preformatted block\n</pre>"
    ),
    array( // Test two pre blocks
        'name'    => "Two preformatted blocks",
        'input'   => "{{{\nPreformatted block\n}}}\n{{{Block 2}}}",
        'output'  => "<pre>Preformatted block\n</pre><p><tt>Block 2</tt></p>"
    ),
    array(
        'name'    => "Preformatted block markup with trailing spaces",
        'input'   => "{{{  \t\nPreformatted block\n}}}  \t\n",
        'output'  => "<pre>Preformatted block\n</pre>"
    ),
    array(
        'name'    => "Space escapes nowiki",
        'input'   => "{{{\nPreformatted block\n }}}\n}}}",
        'output'  => "<pre>Preformatted block\n}}}\n</pre>"
    ),
    array(
        'name'    => "Inline nowiki with trailing braces",
        'input'   => "{{{foo}}}}}}",
        'output'  => "<p><tt>foo}}}</tt></p>"
    ),
    array( // Test h1
        'name'    => "h1",
        'input'   => "= Header =",
        'output'  => "<h1>Header</h1>"
    ),
    array( // Test h2
        'name'    => "h2",
        'input'   => "== Header =",
        'output'  => "<h2>Header</h2>"
    ),
    array( // Test h3
        'name'    => "h3",
        'input'   => "=== Header =",
        'output'  => "<h3>Header</h3>"
    ),
    array( // Test h4
        'name'    => "h4",
        'input'   => "==== Header =",
        'output'  => "<h4>Header</h4>"
    ),
    array( // Test h5
        'name'    => "h5",
        'input'   => "===== Header",
        'output'  => "<h5>Header</h5>"
    ),
    array( // Test h6
        'name'    => "h6",
        'input'   => "====== Header =",
        'output'  => "<h6>Header</h6>"
    ),
    array( // Test above h6 (should be ignored)
        'name'    => ">h6",
        'input'   => "======= Header =",
        'output'  => "<p>======= Header =</p>"
    ),
    array( // Test h1 ending with tilde
        'name'    => "h1 ending with tilde",
        'input'   => "= Header ~",
        'output'  => "<h1>Header ~</h1>"
    ),
    array( // Test h2 ending with tilde
        'name'    => "h2 ending with tilde",
        'input'   => "== Header ~",
        'output'  => "<h2>Header ~</h2>"
    ),
    array( // Test h3 ending with tilde
        'name'    => "h3 ending with tilde",
        'input'   => "=== Header ~",
        'output'  => "<h3>Header ~</h3>"
    ),
    array( // Test h4 ending with tilde
        'name'    => "h4 ending with tilde",
        'input'   => "==== Header ~",
        'output'  => "<h4>Header ~</h4>"
    ),
    array( // Test h5 ending with tilde
        'name'    => "h5 ending with tilde",
        'input'   => "===== Header ~",
        'output'  => "<h5>Header ~</h5>"
    ),
    array( // Test h6 ending with tilde
        'name'    => "h6 ending with tilde",
        'input'   => "====== Header ~",
        'output'  => "<h6>Header ~</h6>"
    ),
    array(
        'name'    => "Tables",
        'input'   => "| A | B |\n| C | D |",
        'output'  => "<table><tr><td> A </td><td> B </td></tr>" +
            "<tr><td> C </td><td> D </td></tr></table>"
    ),
    array(
        'name'    => "Tables without trailing pipe",
        'input'   => "| A | B\n| C | D",
        'output'  => "<table><tr><td> A </td><td> B</td></tr>" +
            "<tr><td> C </td><td> D</td></tr></table>"
    ),
    array(
        'name'    => "Table headers",
        'input'   => "|= A | B |\n| C |= D |",
        'output'  => "<table><tr><th> A </th><td> B </td></tr>" +
            "<tr><td> C </td><th> D </th></tr></table>"
    ),
    array(
        'name'    => "Table inline markup",
        'input'   => "| A | B |\n| //C// | **D** \\\\ E |",
        'output'  => "<table><tr><td> A </td><td> B </td></tr>" +
            "<tr><td> <em>C</em> </td>" +
            "<td> <strong>D</strong> <br /> E </td></tr></table>"
    ),
    array(
        'name'    => "Escaped table inline markup",
        'input'   => "| A | B |\n| {{{//C//}}} | {{{**D** \\\\ E}}} |",
        'output'  => "<table><tr><td> A </td><td> B </td></tr>" +
            "<tr><td> <tt>//C//</tt> </td>" +
            "<td> <tt>**D** \\\\ E</tt> </td></tr></table>"
    ),
    array( // Test raw URL
        'name'    => "Raw URL",
        'input'   => "http://example.com/examplepage",
        'output'  => "<p><a href=\"http://example.com/examplepage\">" +
            "http://example.com/examplepage</a></p>"
    ),
    array(
        'name'    => "Raw URL with tilde",
        'input'   => "http://example.com/~user",
        'output'  => "<p><a href=\"http://example.com/~user\">" +
            "http://example.com/~user</a></p>"
    ),
    array( // Test unnamed URL
        'name'    => "Unnamed URL",
        'input'   => "[[http://example.com/examplepage]]",
        'output'  => "<p><a href=\"http://example.com/examplepage\">" +
            "http://example.com/examplepage</a></p>"
    ),
    array(
        'name'    => "Unnamed URL with tilde",
        'input'   => "[[http://example.com/~user]]",
        'output'  => "<p><a href=\"http://example.com/~user\">" +
            "http://example.com/~user</a></p>"
    ),
    array( // Test named URL
        'name'    => "Named URL",
        'input'   => "[[http://example.com/examplepage|Example Page]]",
        'output'  => "<p>" +
            "<a href=\"http://example.com/examplepage\">Example Page</a></p>"
    ),
    array( // Test unnamed link
        'name'    => "Unnamed link",
        'input'   => "[[MyPage]]",
        'output'  => "<p><a href=\"/wiki/MyPage\">MyPage</a></p>"
    ),
    array( // Test named link
        'name'    => "Named link",
        'input'   => "[[MyPage|My page]]",
        'output'  => "<p><a href=\"/wiki/MyPage\">My page</a></p>"
    ),
    array(
        'name'    => "Unnamed interwiki link",
        'input'   => "[[WikiCreole:Creole1.0]]",
        'output'  => "<p><a href=\"http://www.wikicreole.org/wiki/Creole1.0\">WikiCreole:Creole1.0</a></p>"
    ),
    array(
        'name'    => "Named interwiki link",
        'input'   => "[[WikiCreole:Creole1.0|Creole 1.0]]",
        'output'  => "<p><a href=\"http://www.wikicreole.org/wiki/Creole1.0\">Creole 1.0</a></p>"
    ),
    array( // Test images
        'name'    => "Image",
        'input'   => "{{image.gif|my image}}",
        'output'  => "<p><img src=\"image.gif\" alt=\"my image\"/></p>"
    ),
    array( // Test inline tt
        'name'    => "Inline tt",
        'input'   => "Inline {{{tt}}} example {{{here}}}!",
        'output'  => "<p>Inline <tt>tt</tt> example <tt>here</tt>!</p>"
    ),
    array( // Test **strong**
        'name'    => "Strong",
        'input'   => "**Strong**",
        'output'  => "<p><strong>Strong</strong></p>"
    ),
    array( // Test runaway **strong
        'name'    => "Runaway strong #1",
        'input'   => "**Strong",
        'output'  => "<p><strong>Strong</strong></p>"
    ),
    array(
        'name'    => "Runaway strong #2",
        'input'   => "** Strong *",
        'output'  => "<p><strong> Strong *</strong></p>"
    ),
    array( // Test //emphasis//
        'name'    => "Emphasis",
        'input'   => "//Emphasis//",
        'output'  => "<p><em>Emphasis</em></p>"
    ),
    array( // Test runaway //emphasis
        'name'    => "Runaway emphasis #1",
        'input'   => "//Emphasis",
        'output'  => "<p><em>Emphasis</em></p>"
    ),
    array(
        'name'    => "Runaway emphasis #2",
        'input'   => "// Emphasis /",
        'output'  => "<p><em> Emphasis /</em></p>"
    ),

  //// WikiCreole tests
    array( // Tests multi-line emphasis behaviour
        'name'    => "Multi-line emphasis",
        'input'   => "Bold and italics should //be\nable// to cross lines.\n\n" +
            "But, should //not be...\n\n...able// to cross paragraphs.",
        'output'  => "<p>Bold and italics should <em>be\nable</em> to cross lines." +
            "\n</p>" + "<p>\nBut, should <em>not be...\n</em></p>" +
            "<p>\n...able<em> to cross paragraphs.</em></p>"
    ),
    array( // Tests URL/emphasis ambiguity handling
        'name'    => "URL/emphasis ambiguity",
        'input'   => "This is an //italic// text. This is a url: " +
            "http://www.wikicreole.org. This is what can go wrong://this " +
            "should be an italic text//.",
        'output'  => "<p>This is an <em>italic</em> text. This is a url: " +
            "<a href=\"http://www.wikicreole.org\">" +
            "http://www.wikicreole.org</a>. This is what can go wrong:" +
            "<em>this should be an italic text</em>."
    ),

  //// Awkward emphasis edge cases
    array(
        'name'    => "Difficult emphasis #1",
        'input'   => "// http://www.link.org //",
        'output'  => "<p><em> <a href=\"http://www.link.org\">" +
            "http://www.link.org</a> </em></p>"
    ),
    array(
        'name'    => "Difficult emphasis #2",
        'input'   => "// http //",
        'output'  => "<p><em> http </em></p>"
    ),
    array(
        'name'    => "Difficult emphasis #3",
        'input'   => "// httphpthtpht //",
        'output'  => "<p><em> httphpthtpht </em></p>"
    ),
    array(
        'name'    => "Difficult emphasis #4",
        'input'   => "// http: //",
        'output'  => "<p><em> http: </em></p>"
    ),
    array(
        'name'    => "Difficult emphasis #5 (runaway)",
        'input'   => "// http://example.org",
        'output'  => "<p><em> <a href=\"http://example.org\">http://example.org</a></em></p>"
    ),
    array(
        'name'    => "Difficult emphasis #6 (runaway)",
        'input'   => "// http://example.org//",
        'output'  => "<p><em> <a href=\"http://example.org//\">http://example.org//</a></em></p>"
    ),
    array(
        'name'    => "Difficult emphasis #7",
        'input'   => "//httphpthtphtt//",
        'output'  => "<p><em>httphpthtphtt</em></p>"
    ),
    array(
        'name'    => "Difficult emphasis #8",
        'input'   => "// ftp://www.link.org //",
        'output'  => "<p><em> <a href=\"ftp://www.link.org\">" +
            "ftp://www.link.org</a> </em></p>"
    ),
    array(
        'name'    => "Difficult emphasis #9",
        'input'   => "// ftp //",
        'output'  => "<p><em> ftp </em></p>"
    ),
    array(
        'name'    => "Difficult emphasis #10",
        'input'   => "// fttpfptftpft //",
        'output'  => "<p><em> fttpfptftpft </em></p>"
    ),
    array(
        'name'    => "Difficult emphasis #11",
        'input'   => "// ftp: //",
        'output'  => "<p><em> ftp: </em></p>"
    ),
    array(
        'name'    => "Difficult emphasis #12 (runaway)",
        'input'   => "// ftp://example.org",
        'output'  => "<p><em> <a href=\"ftp://example.org\">ftp://example.org</a></em></p>"
    ),
    array(
        'name'    => "Difficult emphasis #13 (runaway)",
        'input'   => "//ftp://username:password@example.org/path//",
        'output'  => "<p><em><a href=\"ftp://username:password@example.org/path//\">" +
            "ftp://username:password@example.org/path//</a></em></p>"
    ),
    array(
        'name'    => "Difficult emphasis #14",
        'input'   => "//fttpfptftpftt//",
        'output'  => "<p><em>fttpfptftpftt</em></p>"
    ),
    array(
        'name'    => "Difficult emphasis #15",
        'input'   => "//ftp://username:password@link.org/path/",
        'output'  => "<p><em><a href=\"ftp://username:password@link.org/path/\">" +
            "ftp://username:password@link.org/path/</a></em></p>"
    ),
    array(
        'name'    => "Escaped emphasis",
        'input'   => "~//Not emphasized~//",
        'output'  => "<p><span class=\"escaped\">/</span>/Not emphasized<span class=\"escaped\">/</span>/</p>"
    ),
    array(
        'name'    => "Tilde escapes self",
        'input'   => "Tilde: ~~",
        'output'  => "<p>Tilde: <span class=\"escaped\">~</span></p>"
    ),
    array(
        'name'    => "Escaped URL",
        'input'   => "~http://link.org",
        'output'  => "<p><span class=\"escaped\">http://link.org</span></p>"
    ),
    array(
        'name'    => "Escaped strong ending",
        'input'   => "Plain ** bold ~** bold too",
        'output'  => "<p>Plain <strong> bold <span class=\"escaped\">*</span>* bold too</strong></p>"
    ),
    array(
        'name'    => "Escaped emphasis ending",
        'input'   => "Plain // emphasized ~// emphasized too",
        'output'  => "<p>Plain <em> emphasized <span class=\"escaped\">/</span>/ emphasized too</em></p>"
    ),
    array(
        'name'    => "Escaped h1 ending",
        'input'   => "= Header ~=",
        'output'  => "<h1>Header <span class=\"escaped\">=</span></h1>"
    ),
    array(
        'name'    => "Escaped h2 ending",
        'input'   => "== Header ~==",
        'output'  => "<h2>Header <span class=\"escaped\">=</span></h2>"
    ),
    array(
        'name'    => "Escaped h3 ending",
        'input'   => "=== Header ~===",
        'output'  => "<h3>Header <span class=\"escaped\">=</span></h3>"
    ),
    array(
        'name'    => "Escaped h4 ending",
        'input'   => "==== Header ~====",
        'output'  => "<h4>Header <span class=\"escaped\">=</span></h4>"
    ),
    array(
        'name'    => "Escaped h5 ending",
        'input'   => "===== Header ~=====",
        'output'  => "<h5>Header <span class=\"escaped\">=</span></h5>"
    ),
    array(
        'name'    => "Escaped h6 ending",
        'input'   => "====== Header ~======",
        'output'  => "<h6>Header <span class=\"escaped\">=</span></h6>"
    ),
    array(
        'name'    => "Escaped link ending #1",
        'input'   => "[[Link~]]]",
        'output'  => "<p><a href=\"/wiki/Link%5D\">Link<span class=\"escaped\">]</span></a></p>"
    ),
    array(
        'name'    => "Escaped link ending #2",
        'input'   => "[[Link]~]]]",
        'output'  => "<p><a href=\"/wiki/Link%5D%5D\">Link]<span class=\"escaped\">]</span></a></p>"
    ),
    array(
        'name'    => "Escaped link ending #3",
        'input'   => "[[Link~]]",
        'output'  => "<p>[[Link<span class=\"escaped\">]</span>]</p>"
    ),
    array(
        'name'    => "Escaped link ending #4",
        'input'   => "[[Link|some text~]]]",
        'output'  => "<p><a href=\"/wiki/Link\">some text<span class=\"escaped\">]</span></a></p>"
    ),
    array(
        'name'    => "Escaped link text separator #1",
        'input'   => "[[Link~|some text]]",
        'output'  => "<p><a href=\"/wiki/Link%7Csome%20text\">Link<span class=\"escaped\">|</span>some text</a></p>"
    ),
    array(
        'name'    => "Escaped link text separator #2",
        'input'   => "[[Link~||some text]]",
        'output'  => "<p><a href=\"/wiki/Link%7C\">some text</a></p>"
    ),
    array(
        'name'    => "Escaped link text separator #3",
        'input'   => "[[Link|~|some text]]",
        'output'  => "<p><a href=\"/wiki/Link\"><span class=\"escaped\">|</span>some text</a></p>"
    ),
    array(
        'name'    => "Escaped img ending #1",
        'input'   => "{{image.png|Alternative text~}}}",
        'output'  => "<p><img src=\"image.png\" alt=\"Alternative text}\"/></p>"
    ),
    array(
        'name'    => "Escaped img ending #2",
        'input'   => "{{image.png|Alternative text}~}}}",
        'output'  => "<p><img src=\"image.png\" alt=\"Alternative text}}\"/></p>"
    ),
    array(
        'name'    => "Escaped img ending #3",
        'input'   => "{{image.png|Alternative text~}}",
        'output'  => "<p>{{image.png|Alternative text<span class=\"escaped\">}</span>}</p>"
    ),
    array(
        'name'    => "Escaped img ending #4",
        'input'   => "{{image.png|Alternative~}} text}}",
        'output'  => "<p><img src=\"image.png\" alt=\"Alternative}} text\"/></p>"
    ),
    array(
        'name'    => "Image URI with tilde #1",
        'input'   => "{{image.png~|Alternative text}}",
        'output'  => "<p><img src=\"image.png~\" alt=\"Alternative text\"/></p>"
    ),
    array(
        'name'    => "Image URI with tilde #2",
        'input'   => "{{image.png~||Alternative text}}",
        'output'  => "<p><img src=\"image.png~\" alt=\"|Alternative text\"/></p>"
    ),
    array(
        'name'    => "Tables with escaped separator",
        'input'   => "| A | B |\n| C | D ~| E |",
        'output'  => "<table><tr><td> A </td><td> B </td></tr>" +
            "<tr><td> C </td><td> D <span class=\"escaped\">|</span> E </td></tr></table>"
    ),
    array(
        'name'    => "Image in link",
        'input'   => "[[Link|Before {{image.png|Alternate}} After]]",
        'output'  => "<p><a href=\"/wiki/Link\">Before <img src=\"image.png\" alt=\"Alternate\"/> After</a></p>"
    ),
    array(
        'name'    => "Formatting interwiki links with function",
        'input'   => "[[Palindrome:Creole]]",
        'output'  => "<p><a href=\"http://www.example.com/wiki/eloerC\">Palindrome:Creole</a></p>"
    ),
    array(
        'name'    => "Named link in table",
        'input'   => "| [[MyLink|My link]] |",
        'output'  => '<table><tr><td> <a href="/wiki/MyLink">My link</a> </td></tr></table>'
    ),
    array(
        'name'    => "Image in table",
        'input'   => "| {{image.png|Alternative text}} |",
        'output'  => '<table><tr><td> <img src="image.png" alt="Alternative text"/> </td></tr></table>'
    ),
    array(
        'name'    => "Image in named link in table",
        'input'   => "| [[Link|{{image.png|Alternative text}}]] |",
        'output'  => '<table><tr><td> <a href="/wiki/Link"><img src="image.png" alt="Alternative text"/></a> </td></tr></table>'
    ),
    array(
        'name'    => "Image without alternative text",
        'input'   => "{{image.png}}",
        'output'  => '<p><img src="image.png" alt=""/></p>'
    ),
    array(
        'name'    => "Image with empty alternative text",
        'input'   => "{{image.png|}}",
        'output'  => '<p><img src="image.png" alt=""/></p>',
    ),
    array(
        'name'    => "Extension in block context",
        'input'   => "Before\n\n<<<php \$node->append('Some text');>>>\n\nAfter",
        'output'  => "<p>Before\n</p>\nSome text\n<p>\nAfter</p>",
    ),
    array(
        'name'    => 'Embedded HTML',
        'input'   => "Before\n\n<<<html <code>Hello, world!</code> >>>\n\nAfter",
        'output'  => "<p>Before\n</p>\n<code>Hello, world!</code> \n<p>\nAfter</p>",
    ),
    array(
        'name'    => 'HTML entity',
        'input'   => "This is a text &mdash; &#119;ith H&#x54;ML entities",
        'output'  => '<p>This is a text &mdash; &#119;ith H&#x54;ML entities</p>',
    ),
    array(
        'name'    => 'HTML entity in alternative text',
        'input'   => "{{image.png|This is hellipsis&hellip;}}",
        'output'  => '<p><img src="image.png" alt="This is hellipsis&hellip;"/></p>',
    ),
);

plan(count($array));

function palindrome_callback($link) {
    return 'http://www.example.com/wiki/' . strrev($link);
}

function php_handler($node, $arg) { // never use it in production!
    eval($arg);
}

function html_handler($node, $arg) {
    $node->append($arg);
}

function extension_callback($node, $data) {
    list($moniker, $arg) = explode(' ', $data, 2);
    call_user_func($moniker . '_handler', $node, $arg);
}

foreach ($tests as $test) {
    $options = array(
        'link_format' => '/wiki/%s',
        'interwiki' => array(
            'MeatBall'   => 'http://www.usemod.com/cgi-bin/mb.pl?%s',
            'WikiCreole' => 'http://www.wikicreole.org/wiki/%s',
            'Palindrome' => 'palindrome_callback'
        ),
        'extension' => 'extension_callback',
    );
    if (isset($test['options'])) {
        $options = array_merge($options, $test['options']);
    }
    $creole = new creole($options);
    is($creole->parse($test['input']), $test['output'], $test['name']);
}

?>
