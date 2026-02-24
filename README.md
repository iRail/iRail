# iRail

[![License AGPL-3.0](https://img.shields.io/badge/license-AGPL--3.0-brightgreen.svg)](http://www.gnu.org/licenses/agpl-3.0.html) [![Join the chat at https://gitter.im/iRail/iRail](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/iRail/iRail?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

iRail supports digital creativity concerning mobility in Belgium. This is an attempt to make the railway time schedules
in Belgium easily available for anyone.

Our main site consists of a very easy mobile website to look up time schedules using our own API.

Native applications using the iRail API and created or supported by the iRail team are named BeTrains and RailerApp.

All information can be found on [our blog at hello.iRail.be](http://hello.irail.be/).

## API Documentation ##

API Documentation can be found at [https://docs.irail.be]().

## Installation for development purposes ##

_note: you'll also need to have [nodejs](https://nodejs.org), [composer](http://getcomposer.org) and the PHP extensions
listed in [composer.json](composer.json) installed on your system_

* Step 1: Clone this repo
* Step 2: Set environment variables
    * `nmbs_riv_key` should contain a valid API key for the internal NMBS RIV API.
    * `spring_datasource_url`, `spring_datasource_username` and `spring_datasource_password` should point to a
      PostgreSQL database.
* Step 4: Run the docker container
* Step 5: Enjoy your own iRail API at http://localhost:8080/.

### Caching and performance ###

iRail caches large amounts of data in memory. At least 2GB RAM is required.
When using the docker image, use the `JAVA_TOOL_OPTIONS` environment variable to set memory flags, for example
`-Xms1200M -Xmx1700M`.

## Update stations list ##

Stations are stored in the stations database table. They can be filled with data from
the [irail/stations](https://github.com/irail/stations) composer package.

## More links ##

* Our mailing list: http://list.irail.be/
* Our GTFS data dumps: http://gtfs.irail.be/
* Issue tracker: https://github.com/iRail/iRail/issues
* Just use our HTTP API: http://api.irail.be/
* BeTrains for Android app source code: https://github.com/iRail/BeTrains-for-Android
* Other repositories: https://github.com/iRail
