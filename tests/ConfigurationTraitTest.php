<?php

namespace Migrations\Test;

use Cake\Core\Plugin;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;
use Migrations\Command\Create;
use Migrations\ConfigurationTrait;

class ExampleCommand extends Create {

}

/**
 * Tests the create command
 */
class ConfigurationTraitTest extends TestCase {

/**
 * Setup method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->command = new ExampleCommand;
	}

/**
 * Returns the combination of the phinx driver name with
 * the associated cakephp driver instance that should be mapped to it
 *
 * @return void
 */
	public function driversProvider() {
		return [
			['mysql', $this->getMock('Cake\Database\Driver\Mysql')],
			['pgsql', $this->getMock('Cake\Database\Driver\Postgres')],
			['sqlite', $this->getMock('Cake\Database\Driver\Sqlite')]
		];
	}

/**
 * Tests that the correct driver name is inferred from the driver
 * instance that is passed to getAdapterName()
 *
 * @dataProvider driversProvider
 * @return void
 */
	public function testGetAdapterName($expected, $cakeDriver) {
		$this->assertEquals(
			$expected,
			$this->command->getAdapterName($cakeDriver)
		);
	}

/**
 * Tests that the configuration object is created out of the database configuration
 * made for the application
 *
 * @return void
 */
	public function testGetConfig() {
		ConnectionManager::config([
			'default' => [
				'className' => 'Cake\Database\Connection',
				'driver' => 'Cake\Database\Driver\Mysql',
				'host' => 'foo.bar',
				'login' => 'root',
				'password' => 'the_password',
				'database' => 'the_database',
				'encoding' => 'utf-8'
			]
		]);

		$input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
		$this->command->setInput($input);
		$config = $this->command->getConfig();
		$this->assertInstanceOf('Phinx\Config\Config', $config);

		$expected = ROOT . DS . 'config' . DS . 'Migrations';
		$this->assertEquals($expected, $config->getMigrationPath());

		$this->assertEquals(
			'phinxlog',
			$config['environments']['default_migration_table']
		);

		$environment = $config['environments']['default'];
		$this->assertEquals('mysql', $environment['adapter']);
		$this->assertEquals('foo.bar', $environment['host']);

		$this->assertEquals('foo.bar', $environment['host']);
		$this->assertEquals('root', $environment['user']);
		$this->assertEquals('the_password', $environment['pass']);
		$this->assertEquals('the_database', $environment['name']);
		$this->assertEquals('utf-8', $environment['charset']);
	}

/**
 * Tests that another phinxlog table is used when passing the plugin option in the input
 *
 * @return void
 */
	public function testGetConfigWithPlugin() {
		Plugin::load('MyPlugin', ['path' => sys_get_temp_dir()]);
		$input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
		$this->command->setInput($input);

		$input->expects($this->at(0))
			->method('getOption')
			->with('plugin')
			->will($this->returnValue('MyPlugin'));

		$input->expects($this->at(1))
			->method('getOption')
			->with('plugin')
			->will($this->returnValue('MyPlugin'));

		$config = $this->command->getConfig();
		$this->assertInstanceOf('Phinx\Config\Config', $config);

		$this->assertEquals(
			'my_plugin_phinxlog',
			$config['environments']['default_migration_table']
		);
	}

}
