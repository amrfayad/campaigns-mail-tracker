# MailTracker

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Total Downloads][ico-downloads]][link-downloads]

MailTracker will hook into all outgoing emails from Laravel/Lumen and inject a tracking code into it.  It will also store the rendered email in the database.  There is also an interface to view sent emails.

## Install (Laravel)

Via Composer

``` bash
$ composer require amrfayad/mail-tracker
```

Add the following to the providers array in config/app.php:

``` php
amrfayad\MailTracker\MailTrackerServiceProvider::class,
```

Publish the config file and migration
``` bash
$ php artisan vendor:publish
```

Run the migration
``` bash
$ php artisan migrate
```

## Install (Lumen)

Via Composer

``` bash
$ composer require amrfayad/mail-tracker
```

Register the following service provider in bootstrap/app.php

``` php
amrfayad\MailTracker\MailTrackerServiceProvider::class
```

Copy vendor/amrfayad/mail-tracker/migrations/2016_03_01_193027_create_sent_emails_table.php and vendor/amrfayad/mail-tracker/config/mail-tracker.php to your respective migrations and config folders. You may have to create a config folder if it doesn't already exist.

Run the migration
``` bash
$ php artisan migrate
```

## Usage

Once installed, all outgoing mail will be logged to the database.  The following config options are available in config/mail-tracker.php:

* **inject-pixel**: set to true to inject a tracking pixel into all outgoing html emails.
* **track-links**: set to true to rewrite all anchor href links to include a tracking link. The link will take the user back to your website which will then redirect them to the final destination after logging the click.
* **expire-days**: How long in days that an email should be retained in your database.  If you are sending a lot of mail, you probably want it to eventually expire.  Set it to zero to never purge old emails from the database.
* **route**: The route information for the tracking URLs.  Set the prefix and middlware as desired.
* **admin-route**: The route information for the admin.  Set the prefix and middleware. *Note that this is not yet built.*

## Events

When an email is viewed or a link is clicked, its tracking information is counted in the database using the jdavidbark\MailTracker\Model\SentEmail model. You may want to do additional processing on these events, so an event is fired in both cases:

* amrfayad\MailTracker\Events\ViewEmailEvent
* amrfayad\MailTracker\Events\LinkClickedEvent

To install an event listener, you will want to create a file like the following:

``` php
<?php

namespace App\Listeners;

use amrfayad\MailTracker\Events\ViewEmailEvent;

class EmailViewed
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  ViewEmailEvent  $event
     * @return void
     */
    public function handle(ViewEmailEvent $event)
    {
        // Access the model using $event->sent_email...
    }
}
```

Then you must register the event in your \App\Providers\EventServiceProvider $listen array:

``` php
/**
 * The event listener mappings for the application.
 *
 * @var array
 */
protected $listen = [
    'amrfayad\MailTracker\Events\ViewEmailEvent' => [
        'App\Listeners\EmailViewed',
    ],
];
```

## TODO

Currently this plugin is only tracking the outgoing mail. There is no view yet to explore the existing data.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CONDUCT](CONDUCT.md) for details.

## Security

If you discover any security related issues, please email me@jdavidbaker.com instead of using the issue tracker.

## Credits

- [J David Baker][link-author]
- [All Contributors][link-contributors]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/amrfayad/MailTracker.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/amrfayad/MailTracker/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/amrfayad/MailTracker.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/amrfayad/MailTracker.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/amrfayad/MailTracker.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/amrfayad/mail-tracker
[link-travis]: https://travis-ci.org/amrfayad/MailTracker
[link-scrutinizer]: https://scrutinizer-ci.com/g/amrfayad/MailTracker/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/amrfayad/MailTracker
[link-downloads]: https://packagist.org/packages/amrfayad/mail-tracker
[link-author]: https://github.com/amrfayad
[link-contributors]: ../../contributors
