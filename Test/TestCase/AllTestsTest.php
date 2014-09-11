<?php

namespace Geshi\Test\TestCase;
class AllTestsTest extends PHPUnit_Framework_TestSuite {

/**
 * suite method, defines tests for this suite.
 *
 * @return void
 */
	public static function suite() {
		$suite = new CakeTestSuite('All Tests');
		$suite->addTestDirectoryRecursive(App::pluginPath('Geshi') . 'Test' . DS . 'Case' . DS);

		return $suite;
	}
}
