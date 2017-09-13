<?php

namespace MasterRO\LaravelScaffold\Providers;

use Illuminate\Support\ServiceProvider;
use MasterRO\LaravelScaffold\Console\Commands\ScaffoldTheApp;

class ScaffoldServiceProvider extends ServiceProvider
{
	/**
	 * Bootstrap the application services.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->commands([
			ScaffoldTheApp::class,
		]);
	}


	/**
	 * Register the application services.
	 *
	 * @return void
	 */
	public function register()
	{
		//
	}
}
