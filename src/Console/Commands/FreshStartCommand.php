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
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'app:fresh-start {--abstract-model=Model} {--models-directory=Models} {--composer=composer} {--without-auth}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Make some presets for easier working with Laravel';

	/**
	 * @var string
	 */
	protected $composerCmd;

	/**
	 * @var string
	 */
	protected $modelsDirectoryName;

	/**
	 * @var string
	 */
	protected $abstractModelName;

	/**
	 * @var boolean
	 */
	protected $makeAuth;

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
		$this->abstractModelName = $this->option('abstract-model');
		$this->modelsDirectoryName = $this->option('models-directory');
		$this->composerCmd = $this->option('composer');
		$this->makeAuth = ! $this->option('without-auth');
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
		$this->addIdeHelperAndDebugbarToDontDiscover();
		$this->requireIdeHelperAndDebugbar();
		$this->registerIdeHelperAndDebugbar();
		$this->addIdeHelperCommandToComposerJson();
		$this->composerDumpAutoload();

		if ($this->makeAuth) {
			$this->makeAuth();
		}
	}


	protected function requireIdeHelperAndDebugbar()
	{
		$this->info('.........Requiring IdeHelper and Debugbar');

		foreach ($this->barryvdhPackages as $package) {
			$process = new Process("{$this->composerCmd} require {$package}");

			$process->run(function ($type, $buffer) {
				$this->getOutput()->write('> ' . $buffer);
			});
		}
	}


	/**
	 * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
	 */
	protected function addIdeHelperCommandToComposerJson()
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
			->merge($this->barryvdhPackages)
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
	protected function addIdeHelperAndDebugbarToDontDiscover()
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
		$this->info('.........Registering IdeHelper and Debugbar in AppServiceProvider');

		$stub = $this->filesystem->get(__DIR__ . '/../stubs/app_provider.stub');

		$this->filesystem->put(app_path('Providers') . "/AppServiceProvider.php", $stub);
	}


	protected function composerDumpAutoload()
	{
		$this->info(".........Running \"{$this->composerCmd} dump-autoload\"");

		$process = new Process("{$this->composerCmd} dump-autoload");

		$process->run(function ($type, $buffer) {
			$this->getOutput()->write('> ' . $buffer);
		});
	}


	protected function makeAuth()
	{
		$this->info(".........Running 'php artisan make:auth'");

		Artisan::call('php artisan make:auth');

		$this->getOutput()->writeln(Artisan::output());
	}

}
