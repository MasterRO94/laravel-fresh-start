<p align="center">
    <img src="https://laravel.com/assets/img/components/logo-laravel.svg">
</p>

# Laravel Get Up
### Quickly scaffold base application structure

LaravelFileCleaner is a package for Laravel 5 that provides deleting temp files and associated model instances(if needed).

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


### Step 4: Remove the package
After scaffolding this package is unnecessary dependency 

```
composer remove masterro/laravel-fresh-start
```

##### For Laravel < 5.5 
Open `config/app.php` and remove the provider:

```
MasterRO\LaravelFreshStart\FreshStartServiceProvider::class
```

#### _I will be grateful if you star this project :)_
