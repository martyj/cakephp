<?php
/**
 * TemplateTask file
 *
 * Test Case for TemplateTask generation shell task
 *
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2009, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2009, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.tests.cases.console.libs.tasks
 * @since         CakePHP(tm) v 1.3
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Shell', 'Shell', false);

if (!defined('DISABLE_AUTO_DISPATCH')) {
	define('DISABLE_AUTO_DISPATCH', true);
}

if (!class_exists('ShellDispatcher')) {
	ob_start();
	$argv = false;
	require CAKE . 'console' .  DS . 'cake.php';
	ob_end_clean();
}

require_once CAKE . 'console' .  DS . 'libs' . DS . 'tasks' . DS . 'template.php';

/**
 * TemplateTaskTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.console.libs.tasks
 */
class TemplateTaskTest extends CakeTestCase {

/**
 * startTest method
 *
 * @return void
 */
	public function startTest() {
		$this->Dispatcher = $this->getMock('ShellDispatcher', array(
			'getInput', 'stdout', 'stderr', '_stop', '_initEnvironment'
		));
		$this->Task = $this->getMock('TemplateTask', 
			array('in', 'err', 'createFile', '_stop'),
			array(&$this->Dispatcher)
		);
		$this->Task->Dispatch->shellPaths = App::path('shells');
	}

/**
 * endTest method
 *
 * @return void
 */
	public function endTest() {
		unset($this->Task, $this->Dispatcher);
		ClassRegistry::flush();
	}

/**
 * test that set sets variables
 *
 * @return void
 */
	public function testSet() {
		$this->Task->set('one', 'two');
		$this->assertTrue(isset($this->Task->templateVars['one']));
		$this->assertEqual($this->Task->templateVars['one'], 'two');

		$this->Task->set(array('one' => 'three', 'four' => 'five'));
		$this->assertTrue(isset($this->Task->templateVars['one']));
		$this->assertEqual($this->Task->templateVars['one'], 'three');
		$this->assertTrue(isset($this->Task->templateVars['four']));
		$this->assertEqual($this->Task->templateVars['four'], 'five');
	}

/**
 * test finding themes installed in
 *
 * @return void
 */
	public function testFindingInstalledThemesForBake() {
		$consoleLibs = CAKE_CORE_INCLUDE_PATH . DS . CAKE . 'console' . DS;
		$this->Task->Dispatch->shellPaths = array($consoleLibs);
		$this->Task->initialize();
		$this->assertEqual($this->Task->templatePaths, array('default' => $consoleLibs . 'templates' . DS . 'default' . DS));
	}

/**
 * test getting the correct theme name.  Ensure that with only one theme, or a theme param
 * that the user is not bugged.  If there are more, find and return the correct theme name
 *
 * @return void
 */
	public function testGetThemePath() {
		$defaultTheme = CAKE_CORE_INCLUDE_PATH . DS . dirname(CONSOLE_LIBS) . 'templates' . DS . 'default' .DS;
		$this->Task->templatePaths = array('default' => $defaultTheme);

		$this->Task->expects($this->exactly(1))->method('in')->will($this->returnValue('1'));

		$result = $this->Task->getThemePath();
		$this->assertEqual($result, $defaultTheme);

		$this->Task->templatePaths = array('default' => $defaultTheme, 'other' => '/some/path');
		$this->Task->params['theme'] = 'other';
		$result = $this->Task->getThemePath();
		$this->assertEqual($result, '/some/path');

		$this->Task->params = array();
		$result = $this->Task->getThemePath();
		$this->assertEqual($result, $defaultTheme);
		$this->assertEqual($this->Dispatcher->params['theme'], 'default');
	}

/**
 * test generate
 *
 * @return void
 */
	public function testGenerate() {
		App::build(array(
			'shells' => array(
				TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS .  'test_app' . DS . 'vendors' . DS . 'shells' . DS
			)
		));
		$this->Task->initialize();
		$this->Task->expects($this->any())->method('in')->will($this->returnValue(1));

		$result = $this->Task->generate('classes', 'test_object', array('test' => 'foo'));
		$expected = "I got rendered\nfoo";
		$this->assertEqual($result, $expected);
	}

/**
 * test generate with a missing template in the chosen theme.
 * ensure fallback to default works.
 *
 * @return void
 */
	public function testGenerateWithTemplateFallbacks() {
		App::build(array(
			'shells' => array(
				TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS .  'test_app' . DS . 'vendors' . DS . 'shells' . DS,
				CAKE_CORE_INCLUDE_PATH . DS . 'console' . DS
			)
		));
		$this->Task->initialize();
		$this->Task->params['theme'] = 'test';
		$this->Task->set(array(
			'model' => 'Article',
			'table' => 'articles',
			'import' => false,
			'records' => false,
			'schema' => ''
		));
		$result = $this->Task->generate('classes', 'fixture');
		$this->assertPattern('/ArticleFixture extends CakeTestFixture/', $result);
	}
}