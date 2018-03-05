<?php

namespace Dossierdata\CsvReader;

use Illuminate\Support\ServiceProvider;

class ServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(\Dossierdata\CsvReader\Contracts\CSVReader::class,\Dossierdata\CsvReader\CSVReader::class);
    }

    public function provides()
    {
        return [\Dossierdata\CsvReader\Contracts\CSVReader::class];
    }
}
