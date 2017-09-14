<?php

namespace Tests;

use Orchestra\Testbench\TestCase;
use MasterRO\LaravelFreshStart\Console\Commands\FreshStartCommand;

class FreshStartCommandTest extends TestCase
{
	public function setUp()
	{
		parent::setUp();

		$this->app->singleton('Illuminate\Contracts\Console\Kernel', TestKernel::class);

		$this->app['Illuminate\Contracts\Console\Kernel']->registerCommand(app(FreshStartCommand::class));
	}


	/**
	 * @param bool $force
	 * @param array $params
	 */
	protected function call($force = true, array $params = [])
	{
		$this->artisan('app:fresh-start', $params);
	}

}
