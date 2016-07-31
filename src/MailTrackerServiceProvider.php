<?php

namespace amrfayad\MailTracker;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class MailTrackerServiceProvider extends ServiceProvider
{
    /**
     * Check to see if we're using lumen or laravel.
     * 
     * @return bool
     */
    public function isLumen() 
    {
        $lumenClass = 'Laravel\Lumen\Application';
        return ($this->app instanceof $lumenClass);
    }

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        // Publish pieces
        if (!$this->isLumen()) {
            $this->publishes([
                __DIR__.'/../config/mail-tracker.php' => config_path('mail-tracker.php')
            ], 'config');
            $this->publishes([
                __DIR__.'/../migrations/2016_03_01_193027_create_sent_emails_table.php' => database_path('migrations/2016_03_01_193027_create_sent_emails_table.php')
            ], 'config');
        }

        // Hook into the mailer
        $this->app['mailer']->getSwiftMailer()->registerPlugin(new MailTracker());

        // Install the routes
        $config = $this->app['config']->get('mail-tracker.route', []);
        $config['namespace'] = 'amrfayad\MailTracker';

        if (!$this->isLumen()) {
            Route::group($config, function()
            {
                Route::controller('/', 'MailTrackerController');
            });
        } else {
            $app = $this->app;
            $app->group($config, function () use ($app) {
                $app->get('t', 'MailTrackerController@getT');
                $app->get('l', 'MailTrackerController@getL');
            });
        }
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
