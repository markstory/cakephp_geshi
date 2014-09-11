<?php
App::uses('View', 'View');
App::uses('GeshiHelper', 'Geshi.View/Helper');

class GeshiHelperTest extends CakeTestCase {

	protected $settings = array(
		'set_header_type' => array('GESHI_HEADER_NONE'),
		'enable_line_numbers' => array('GESHI_FANCY_LINE_NUMBERS', 2),
		'enable_classes' => array(),
		'set_tab_width' => array(4),
	);

	protected $view;
	protected $configPath;

	public function setUp() {
		parent::setUp();
		$this->view = $this->getMock('View');
		$this->geshi = new GeshiHelper($this->view);
		$this->configPath = $this->geshi->configPath = dirname(dirname(dirname(__FILE__))) . DS;
	}

	public function tearDown() {
		parent::tearDown();
		unset($this->view, $this->geshi);
	}

/**
 * Run the tests against the configuration variants.
 *
 * @param string $method The test method to run.
 */
	public function runVariants($method) {
		// Using a config file, traditional.
		$this->geshi = new GeshiHelper($this->view);
		$this->geshi->configPath = $this->configPath;
		call_user_func([$this, $method]);
		unset($this->geshi);

		// Pre-configuration during instantiation, such as from controller.
		$this->geshi = new GeshiHelper($this->view, $this->settings);
		unset($this->geshi->configPath);
		call_user_func([$this, $method]);
		unset($this->geshi);

		// Configuration on the fly, such as from view.
		$this->geshi = new GeshiHelper($this->view);
		unset($this->geshi->configPath);
		$this->geshi->features = $this->settings;
		call_user_func([$this, $method]);
		unset($this->geshi);
	}

/**
 * Test highlighting with variants.
 *
 * @return void
 */
	public function testConfigVariants() {
		$this->runVariants('testPlainTextButton');
	}

/**
 * Test basic highlighting
 *
 * @return void
 */
	public function testHighlight() {
		$this->geshi->showPlainTextButton = false;

		// Simple one code block
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

		// Two code blocks
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

		// Codeblock with single quotes Fails because of issues in CakeTestCase::assertTags()
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
						array('span' => array('class' => "st0")), '&quot;foo&quot;', '/span',
						array('span' => array('class' => "sy0")), ';', '/span',
						array('span' => array('class' => "sy1")), '?&gt;', '/span',
					'/div',
				'/li',
			'/ol',
			'/div',
		);
		$this->assertTags($result, $expected);

		// More than one valid code block container
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
 * Test the inclusion of the toggle text button
 *
 * @return void
 */
	public function testPlainTextButton() {
		// Simple one code block
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

	public function testNoTagReplacement() {
		// Simple one code block
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

	public function testHighlightText() {
		$result = $this->geshi->highlightText("<?php echo 'test';", 'php');
		$expected = array(
			array('ol' => array("class" => "php")),
				array('li' => array('class' => "li1")),
				array("div" => array('class' => "de1")),
					array("span" => array("class" => "kw2")), '&lt;?php', '/span',
					array('span' => array('class' => "kw1")), 'echo', '/span',
					array('span' => array('class' => "st_h")), "'test'", '/span',
					array('span' => array('class' => "sy0")), ';', '/span',
				'/div',
				'/li',
			'/ol',
		);
		$this->assertTags($result, $expected);
	}

	public function testHighlightAsTable() {
		$text = <<<CODE
<?php
echo 'test';
echo 1 + 1;
CODE;
		$result = $this->geshi->highlightAsTable($text, 'php');
		$expected = array(
			array('table' => array('class' => 'code', 'cellspacing' => 0, 'cellpadding' => 0)),
			'<tbody',
			'<tr',
			array('td' => array('class' => 'code-numbers')),
				array('div' => array('class' => 'de1')), 1, '/div',
				array('div' => array('class' => 'de1')), 2, '/div',
				array('div' => array('class' => 'de1')), 3, '/div',
			'/td',
			array('td' => array('class' => 'code-block')),
				array("div" => array('class' => "de1")),
					array("span" => array("class" => "kw2")), '&lt;?php', '/span',
				'/div',
				array('div' => array('class' => 'de1')),
					array('span' => array('class' => "kw1")), 'echo', '/span',
					array('span' => array('class' => "st_h")), "'test'", '/span',
					array('span' => array('class' => "sy0")), ';', '/span',
				'/div',
				array('div' => array('class' => 'de1')),
					array('span' => array('class' => "kw1")), 'echo', '/span',
					array('span' => array('class' => "nu0")), '1', '/span',
					array('span' => array('class' => "sy0")), '+', '/span',
					array('span' => array('class' => "nu0")), '1', '/span',
					array('span' => array('class' => "sy0")), ';', '/span',
				'/div',
			'/td',
			'/tr',
			'/tbody',
			'/table',
		);
		$this->assertTags($result, $expected);
	}

}
