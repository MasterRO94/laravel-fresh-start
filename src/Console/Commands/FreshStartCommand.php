<?php

namespace MasterRO\LaravelFreshStart\Console\Commands;

use stdClass;
use Illuminate\Console\Command;
use Symfony\Component\Finder\Finder;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Artisan;

class FreshStartCommand extends Command
{
	const PACKAGE_NAME = 'masterro/laravel-fresh-start';

	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'app:fresh-start {--default}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Make some presets for easier working with Laravel';

	/**
	 * @var string
	 */
	protected $composerCmd = 'composer';

	/**
	 * @var string
	 */
	protected $modelsDirectoryName = 'Models';

	/**
	 * @var string
	 */
	protected $abstractModelName = 'Model';

	/**
	 * @var boolean
	 */
	protected $makeAuth = true;

	/**
	 * @var boolean
	 */
	protected $remove = true;

	/**
	 * @var Filesystem
	 */
	private $filesystem;

	/**
	 * @var array
	 */
	private $barryvdhPackages = [
		'barryvdh/laravel-debugbar',
		'barryvdh/laravel-ide-helper',
	];

	/**
	 * @var array
	 */
	protected $packagesToInstall = [];


	/**
	 * GetUpTheApp constructor.
	 *
	 * @param Filesystem $filesystem
	 */
	public function __construct(Filesystem $filesystem)
	{
		parent::__construct();

		$this->filesystem = $filesystem;
	}


	/**
	 * Set properties from options
	 */
	protected function setUp()
	{
		if ($this->option('default')) {
			$this->packagesToInstall = $this->barryvdhPackages;

			return;
		}

		$this->modelsDirectoryName = $this->ask('Name of models directory', 'Models');
		$this->abstractModelName = $this->ask('Name of the abstract Eloquent model', 'Model');
		$this->composerCmd = $this->ask('Composer command (php composer.phar, composer)', 'composer');

		foreach ($this->barryvdhPackages as $package) {
			if ($this->ask("Install {$package}? [yes, no]", 'yes') == 'yes') {
				$this->packagesToInstall[] = $package;
			}
		}

		$this->makeAuth = $this->ask('Run `php artisan make:auth`? [yes, no]', 'yes') == 'yes';
		$this->remove = $this->ask('Remove this package after installation? [yes, no]', 'yes') == 'yes';
	}


	/**
	 * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
	 */
	public function handle()
	{
		$this->setUp();

		$this->createModelsDirectory();
		$this->createAbstractModel();
		$this->moveUserToModelsDirectory();
		$this->changeUserNamespaceEverywhereItUses();
		$this->extendUserFromAbstractModel();

		if (count($this->packagesToInstall)) {
			$this->addIdeHelperAndDebugbarToDontDiscover();
			$this->requireIdeHelperAndDebugbar();
			$this->registerIdeHelperAndDebugbar();

			if (in_array('barryvdh/laravel-ide-helper', $this->packagesToInstall)) {
				$this->addIdeHelperCommandToComposerJson();
			}
		}

		if ($this->makeAuth) {
			$this->makeAuth();
		}

		if ($this->remove) {
			$this->selfRemove();
		}

		$this->composerUpdate();
	}


	protected function requireIdeHelperAndDebugbar()
	{
		$this->info('.........Requiring ' . implode(' and ', $this->packagesToInstall));

		foreach ($this->packagesToInstall as $package) {
			$process = new Process("{$this->composerCmd} require {$package} --dev");

			$process->run(function ($type, $buffer) {
				$this->getOutput()->write('> ' . $buffer);
			});
		}
	}


	/**
	 * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
	 */
	protected function addIdeHelperAndDebugbarToDontDiscover()
	{
		$composerJson = $this->filesystem->get('composer.json');

		$composerData = json_decode($composerJson);

		if (! isset($composerData->extra)) {
			$composerData->extra = new stdClass;
		}

		if (! isset($composerData->extra->laravel)) {
			$composerData->extra->laravel = new stdClass;
		}

		$dontDiscover = collect($composerData->extra->laravel->{'dont-discover'} ?? [])
			->merge($this->packagesToInstall)
			->unique();

		$composerData->extra->laravel->{'dont-discover'} = $dontDiscover;

		$this->filesystem->put(
			'composer.json',
			strtr(json_encode($composerData, JSON_PRETTY_PRINT), ['\/' => '/'])
		);
	}


	/**
	 * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
	 */
	protected function addIdeHelperCommandToComposerJson()
	{
		$composerJson = $this->filesystem->get('composer.json');

		$composerData = json_decode($composerJson);

		if (! isset($composerData->scripts->{'post-install-cmd'})) {
			$composerData->scripts->{'post-install-cmd'} = [];
		}
		if (! isset($composerData->scripts->{'post-update-cmd'})) {
			$composerData->scripts->{'post-update-cmd'} = [];
		}

		$composerData->scripts->{'post-install-cmd'} = collect($composerData->scripts->{'post-install-cmd'})
			->merge(['php artisan ide-helper:generate'])
			->unique();
		$composerData->scripts->{'post-update-cmd'} = collect($composerData->scripts->{'post-update-cmd'})
			->merge(['php artisan ide-helper:generate'])
			->unique();

		$this->filesystem->put(
			'composer.json',
			strtr(json_encode($composerData, JSON_PRETTY_PRINT), ['\/' => '/'])
		);
	}


	protected function createModelsDirectory()
	{
		$this->info(".........Creating models directory: {$this->modelsDirectoryName}");

		$path = app_path($this->modelsDirectoryName);

		if (! $this->filesystem->exists($path)) {
			$this->filesystem->makeDirectory($path);
		}
	}


	/**
	 * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
	 */
	protected function createAbstractModel()
	{
		$this->info(".........Creating abstract model: {$this->abstractModelName}");

		$stub = $this->filesystem->get(__DIR__ . '/../stubs/abstract_model.stub');

		$this->filesystem->put(
			app_path($this->modelsDirectoryName) . "/{$this->abstractModelName}.php",
			strtr($stub, [
				'{ModelsDirectoryName}' => $this->modelsDirectoryName,
				'{AbstractModelName}'   => $this->abstractModelName,
			])
		);
	}


	/**
	 * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
	 */
	protected function moveUserToModelsDirectory()
	{
		$this->info(".........Moving User.php to app/{$this->modelsDirectoryName}/User.php");

		if ($this->filesystem->exists($userPath = app_path('User.php'))) {
			$this->filesystem->move($userPath, $targetPath = app_path("{$this->modelsDirectoryName}/User.php"));
			$this->filesystem->put(
				$targetPath,
				strtr($this->filesystem->get($targetPath), [
					'App;' => "App\\{$this->modelsDirectoryName};",
				])
			);
		}
	}


	protected function changeUserNamespaceEverywhereItUses()
	{
		$this->info(".........Changing user uses and imports from App\\User to App\\{$this->modelsDirectoryName}\\User");

		$files = Finder::create()
			->in(base_path())
			->contains('App\\User')
			->exclude('vendor')
			->name('*.php');

		foreach ($files as $file) {
			$path = $file->getRealPath();
			if ($this->filesystem->exists($path)) {
				$this->filesystem->put($path, strtr($this->filesystem->get($path), [
					'App\\User' => "App\\{$this->modelsDirectoryName}\\User",
				]));
			}
		}
	}


	/**
	 * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
	 */
	protected function extendUserFromAbstractModel()
	{
		$this->info(".........Extending user from App\\{$this->modelsDirectoryName}\\{$this->abstractModelName}");

		$stub = $this->filesystem->get(__DIR__ . '/../stubs/user.stub');

		$this->filesystem->put(
			app_path($this->modelsDirectoryName) . "/User.php",
			strtr($stub, [
				'{ModelsDirectoryName}' => $this->modelsDirectoryName,
				'{AbstractModelName}'   => $this->abstractModelName,
			])
		);
	}


	/**
	 * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
	 */
	protected function registerIdeHelperAndDebugbar()
	{
		$this->info('.........Registering ' . implode(' and ', $this->packagesToInstall) . ' in AppServiceProvider');

		if (count($this->packagesToInstall) > 1) {
			$stub = $this->filesystem->get(__DIR__ . '/../stubs/app_provider.stub');
		} else {
			$stubSuffix = snake_case(str_replace('-', '_', explode('/', $this->packagesToInstall[0])[1]));

			$stub = $this->filesystem->get(__DIR__ . "/../stubs/app_provider_{$stubSuffix}.stub");
		}

		$this->filesystem->put(app_path('Providers') . "/AppServiceProvider.php", $stub);
	}


	protected function makeAuth()
	{
		$this->info(".........Running 'php artisan make:auth'");

		Artisan::call('make:auth');

		$this->getOutput()->writeln(Artisan::output());
	}


	protected function selfRemove()
	{
		$this->info('.........Removing ' . static::PACKAGE_NAME);

		$composerJson = $this->filesystem->get('composer.json');

		$composerData = json_decode($composerJson);

		if (data_get($composerData, 'require.masterro/laravel-fresh-start')) {
			unset($composerData->require->{"masterro/laravel-fresh-start"});
		} elseif (data_get($composerData, 'require-dev.masterro/laravel-fresh-start')) {
			unset($composerData->{"require-dev"}->{"masterro/laravel-fresh-start"});
		}

		$this->filesystem->put(
			'composer.json',
			strtr(json_encode($composerData, JSON_PRETTY_PRINT), ['\/' => '/'])
		);
	}


	protected function composerUpdate()
	{
		$this->info(".........Running \"{$this->composerCmd} update\"");

		$process = new Process("{$this->composerCmd} update");

		$process->run(function ($type, $buffer) {
			$this->getOutput()->write('> ' . $buffer);
		});
	}

}
