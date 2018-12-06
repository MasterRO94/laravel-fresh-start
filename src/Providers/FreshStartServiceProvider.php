<?php

namespace MasterRO\LaravelFreshStart\Providers;

use Illuminate\Support\ServiceProvider;
use MasterRO\LaravelFreshStart\Console\Commands\FreshStartCommand;

class FreshStartServiceProvider extends ServiceProvider
{
	/**
	 * Bootstrap the application services.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->commands([
			FreshStartCommand::class,
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
