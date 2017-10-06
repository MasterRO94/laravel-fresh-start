<p align="center">
    <img src="https://laravel.com/assets/img/components/logo-laravel.svg">
</p>

# Laravel Fresh Start
### Quickly scaffold base application structure

This package provides you an artisan command that creates a App/Models directory and moves App/User.php there. 
Then it creates an abstract Model class and extend App\Models\User from abstract App\Models\Model.
After that it requires ide-helper and debugbar and requires it in the AppServiceProvider for local env and adds to dont-discover in `composer.json`.
Also it adds a `php artisan ide-helper:generate` command to `composer.json` post-install-cmd and post-update-cmd.


## Installation

### Step 1: Composer

From the command line, run:

```
composer require masterro/laravel-fresh-start --dev
```

### Step 2: Service Provider (For Laravel < 5.5)

For your Laravel app, open `config/app.php` and, within the `providers` array, append:

```
MasterRO\LaravelFreshStart\FreshStartServiceProvider::class
```

### Step 3: Run the scaffold
From the command line, run:

```
php artisan app:fresh-start
```

You will be asked some questions to configure scaffolding. If you want to skip configuration yot can run command with `--default option`

```
php artisan app:fresh-start --default
```

### Step 4: Remove the package
If you answered "no" on "Remove this package?" question after scaffolding you can remove 

```
composer remove masterro/laravel-fresh-start
```

##### For Laravel < 5.5 
Open `config/app.php` and remove the provider:

```
MasterRO\LaravelFreshStart\FreshStartServiceProvider::class
```

#### _I will be grateful if you star this project :)_
