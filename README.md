# iRail

[![License AGPL-3.0](https://img.shields.io/badge/license-AGPL--3.0-brightgreen.svg)](http://www.gnu.org/licenses/agpl-3.0.html) [![Join the chat at https://gitter.im/iRail/iRail](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/iRail/iRail?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

iRail supports digital creativity concerning mobility in Belgium. This is an attempt to make the railway time schedules in Belgium easily available for anyone. 

Our main site consists of a very easy mobile website to look up time schedules using our own API.

Native applications using the iRail API and created or supported by the iRail team are named BeTrains and RailerApp.

All information can be found on [our blog at hello.iRail.be](http://hello.irail.be/).

## API Documentation ##

API Documentation can be found at [https://docs.irail.be]().

## Installation for development purposes ##

_note: you'll also need to have [nodejs](https://nodejs.org), [composer](http://getcomposer.org) and the PHP extensions
listed in [composer.json](composer.json) installed on your system_

 * Step 1: Clone this repo
* Step 2: Create an `.env` file which will contain your configuration.
 * `CACHE_DRIVER` should be set to `apc`, an in-memory cache with good performance.
 * `NMBS_RIV_API_KEY` should contain a valid API key for the internal NMBS RIV API.
 * `GTFS_RANGE_DAYS_BACKWARDS` and `GTFS_RANGE_DAYS_FORWARDS` define how long in the past and future GTFS data will be
   read. This will affect memory usage, and limits the date ranges of some API endpoints.
* Step 4: Run the docker-compose configuration, which consists of nginx, mariadb and the actual PHP application. If you
  want to use sqlite, adjust the database configuration. Note that sqlite is untested.
* Step 5: When using mariadb, ensure a database exists on the mariadb server. It should match the database credentials
  specified for the php application in the docker-compose file.
* Step 6: Run the database migrations in order to create the necessary database tables: `php artisan migrate`
* Step 7: Enjoy your own iRail API at http://localhost:8080/. All routes are defined in the [routes](routes/api.php)
  file. See the laravel docs for more information if needed.

### Caching and performance ###

A working cache is required for the API to work correctly. Ensure APC is configured correctly when running this outside
of the docker environment.
**Outgoing requests to NMBS servers should always be limited as much as possible!**

## Update stations list ##

Stations are updated through the [irail/stations](https://github.com/irail/stations) composer package. Just perform a `composer update` in the root of the project.

## More links ##

 * Our mailing list: http://list.irail.be/
 * Our GTFS data dumps: http://gtfs.irail.be/
 * Issue tracker: https://github.com/iRail/iRail/issues
 * Just use our HTTP API: http://api.irail.be/
 * BeTrains for Android app source code: https://github.com/iRail/BeTrains-for-Android
 * Other repositories: https://github.com/iRail
