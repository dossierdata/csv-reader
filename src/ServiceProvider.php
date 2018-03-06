<?php

namespace Dossierdata\CsvReader;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
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
