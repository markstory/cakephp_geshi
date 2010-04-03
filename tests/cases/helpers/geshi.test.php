<?php
App::import('Helper', 'Geshi.Geshi');

class GeshiTestCase extends CakeTestCase {

	function setUp() {
		ClassRegistry::flush();
	}

	function startTest() {
		$this->geshi = new GeshiHelper();
		$this->geshi->configPath = dirname(dirname(dirname(__FILE__))) . DS;
	}

	function endTest() {
		unset($this->geshi);
	}
/**
 * test basic highlighting
 *
 * @access public
 * @return void
 **/
	function testHighlight() {
		$this->geshi->showPlainTextButton = false;

		//simple one code block
		$text = '<p>This is some text</p><pre lang="php"><?php echo $foo = "foo"; ?></pre><p>More text</p>';
		$result = $this->geshi->highlight($text);
		$expected = array(
			'<p', 'This is some text', '/p',
			'div' => array('class' => "code", 'lang' => "php"),
				'ol' => array("class" => "php"), 
					array('li' => array('class' => "li1")),
					array("div" => array('class' => "de1")),
						array("span" => array("class" => "kw2")), '&lt;?php', '/span',
						array('span' => array('class' => "kw1")), 'echo', '/span',
						array('span' => array('class' => "re0")), '$foo', '/span',
						array('span' => array('class' => "sy0")), '=', '/span',
						array('span' => array('class' => "st0")), '&quot;foo&quot;', '/span',
						array('span' => array('class' => "sy0")), ';', '/span',
						array('span' => array('class' => "sy1")), '?&gt;', '/span', 
					'/div',
				'/li',
			'/ol',
			'/div',
			'<p', 'More text', '/p'
		);
		$this->assertTags($result, $expected);

		//two code blocks
		$text = '<p>Some text</p><pre lang="php"><?php echo $foo; ?></pre><p>text</p><pre lang="php"><?php echo $bar; ?></pre><p>Even more text</p>';
		$result = $this->geshi->highlight($text);
		
		$expected = array(
			'<p', 'Some text', '/p',
			array('div' => array('class' => "code", 'lang' => "php")),
				array('ol' => array("class" => "php")), 
					array('li' => array('class' => "li1")),
					array("div" => array('class' => "de1")),
						array("span" => array("class" => "kw2")), '&lt;?php', '/span',
						array('span' => array('class' => "kw1")), 'echo', '/span',
						array('span' => array('class' => "re0")), '$foo', '/span',
						array('span' => array('class' => "sy0")), ';', '/span',
						array('span' => array('class' => "sy1")), '?&gt;', '/span', 
					'/div',
				'/li',
			'/ol',
			'/div',
			'<p', 'text', '/p',
			array('div' => array('class' => "code", 'lang' => "php")),
				array('ol' => array("class" => "php")),
					array('li' => array('class' => "li1")),
					array("div" => array('class' => "de1")),
						array("span" => array("class" => "kw2")), '&lt;?php', '/span',
						array('span' => array('class' => "kw1")), 'echo', '/span',
						array('span' => array('class' => "re0")), '$bar', '/span',
						array('span' => array('class' => "sy0")), ';', '/span',
						array('span' => array('class' => "sy1")), '?&gt;', '/span', 
					'/div',
				'/li',
			'/ol',
			'/div',
			'<p', 'Even more text', '/p',
		);
		$this->assertTags($result, $expected, true);

		//codeblock with single quotes Fails because of issues in CakeTestCase::assertTags()
		$text = '<pre lang=\'php\'><?php echo $foo = "foo"; ?></pre>';
		$result = $this->geshi->highlight($text);
		$expected = array(
			array('div' => array('class' => "code", 'lang' => 'php')),
				array('ol' => array("class" => "php")), 
					array('li' => array('class' => "li1")),
					array("div" => array('class' => "de1")),
						array("span" => array("class" => "kw2")), '&lt;?php', '/span',
						array('span' => array('class' => "kw1")), 'echo', '/span',
						array('span' => array('class' => "re0")), '$foo', '/span',
						array('span' => array('class' => "sy0")), '=', '/span',
						array('span' => array('class' => "st0")), '"foo"', '/span', 
						array('span' => array('class' => "sy0")), ';', '/span',
						array('span' => array('class' => "sy1")), '?&gt;', '/span',
					'/div',
				'/li',
			'/ol',
			'/div',
		);
		//$this->assertTags($result, $expected);


		//more than one valid code block container
		$this->geshi->validContainers = array('pre', 'code');
		$text = '<pre lang="php"><?php echo $foo = "foo"; ?></pre><p>Text</p><code lang="php">echo $foo = "foo";</code>';
		$result = $this->geshi->highlight($text);
		$expected = array(
			array('div' => array('class' => "code", 'lang' => 'php')),
				array('ol' => array("class" => "php")), 
					array('li' => array('class' => "li1")),
					array("div" => array('class' => "de1")),
						array("span" => array("class" => "kw2")), '&lt;?php', '/span',
						array('span' => array('class' => "kw1")), 'echo', '/span',
						array('span' => array('class' => "re0")), '$foo', '/span',
						array('span' => array('class' => "sy0")), '=', '/span',
						array('span' => array('class' => "st0")), '&quot;foo&quot;', '/span',
						array('span' => array('class' => "sy0")), ';', '/span',
						array('span' => array('class' => "sy1")), '?&gt;', '/span',
					'/div',
				'/li',
			'/ol',
			'/div',
			'<p', 'Text', '/p',
			array('code' => array('lang' => 'php')),
				array('ol' => array("class" => "php")), 
					array('li' => array('class' => "li1")),
					array("div" => array('class' => "de1")),
						array('span' => array('class' => "kw1")), 'echo', '/span',
						array('span' => array('class' => "re0")), '$foo', '/span',
						array('span' => array('class' => "sy0")), '=', '/span',
						array('span' => array('class' => "st0")), '&quot;foo&quot;', '/span',
						array('span' => array('class' => "sy0")), ';', '/span',
					'/div',
				'/li',
			'/ol',
			'/code',
		);
		$this->assertTags($result, $expected, true);

		// No valid languages no highlights
		$this->geshi->validContainers = array('pre');
		$this->geshi->validLanguages = array();
		$text = '<p>text</p><pre lang="php">echo $foo;</pre><p>text</p>';
		$result = $this->geshi->highlight($text);
		$expected = array(
			'<p', 'text', '/p',
			'div' => array('class' => 'code', 'lang' => 'php'),
			'echo $foo;',
			'/div',
			'<p', 'text', '/p'
		);
		$this->assertTags($result, $expected);
	}

/**
 * test the inclusion of the toggle text button
 *
 * @access public
 * @return void
 **/
	function testPlainTextButton() {
		//simple one code block
		$text = '<p>This is some text</p><pre lang="php"><?php echo $foo = "foo"; ?></pre><p>More text</p>';
		$result = $this->geshi->highlight($text);
		$expected = array(
			'<p', 'This is some text', '/p',
			'a' => array('href' => '#null', 'class' => 'geshi-plain-text'), 'Show Plain Text', '/a',
			array('div' => array('class' => 'code', 'lang' => 'php')),
				array('ol' => array("class" => "php")), 
					array('li' => array('class' => "li1")),
					array("div" => array('class' => "de1")),
						array("span" => array("class" => "kw2")), '&lt;?php', '/span',
						array('span' => array('class' => "kw1")), 'echo', '/span',
						array('span' => array('class' => "re0")), '$foo', '/span',
						array('span' => array('class' => "sy0")), '=', '/span',
						array('span' => array('class' => "st0")), '&quot;foo&quot;', '/span',
						array('span' => array('class' => "sy0")), ';', '/span',
						array('span' => array('class' => "sy1")), '?&gt;', '/span',
					'/div',
				'/li',
			'/ol',
			'/div',
			'<p', 'More text', '/p'
		);
		$this->assertTags($result, $expected);
	}

	function testNoTagReplacement() {
		//simple one code block
		$this->geshi->showPlainTextButton = false;
		$this->geshi->containerMap = array();

		$text = '<p>This is some text</p><pre lang="php"><?php echo $foo = "foo"; ?></pre><p>More text</p>';
		$result = $this->geshi->highlight($text);
		$expected = array(
			'<p', 'This is some text', '/p',
			array('pre' => array('lang' => 'php')),
				array('ol' => array("class" => "php")), 
					array('li' => array('class' => "li1")),
					array("div" => array('class' => "de1")),
						array("span" => array("class" => "kw2")), '&lt;?php', '/span',
						array('span' => array('class' => "kw1")), 'echo', '/span',
						array('span' => array('class' => "re0")), '$foo', '/span',
						array('span' => array('class' => "sy0")), '=', '/span',
						array('span' => array('class' => "st0")), '&quot;foo&quot;', '/span',
						array('span' => array('class' => "sy0")), ';', '/span',
						array('span' => array('class' => "sy1")), '?&gt;', '/span',
					'/div',
				'/li',
			'/ol',
			'/pre',
			'<p', 'More text', '/p'
		);
		$this->assertTags($result, $expected);
	}
}
?>