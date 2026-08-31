# iRail

[![License AGPL-3.0](https://img.shields.io/badge/license-AGPL--3.0-brightgreen.svg)](http://www.gnu.org/licenses/agpl-3.0.html) [![Join the chat at https://gitter.im/iRail/iRail](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/iRail/iRail?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

iRail supports digital creativity concerning mobility in Belgium. This is an attempt to make the railway time schedules
in Belgium easily available for anyone.

Our main site consists of a very easy mobile website to look up time schedules using our own API.

Native applications using the iRail API and created or supported by the iRail team are named BeTrains and RailerApp.

## API Documentation ##

API Documentation can be found at [https://docs.irail.be]().

### Linked Connections change feed

`GET /v1/feed` publishes a single-page JSON-LD Linked Data Event Stream of
realtime Linked Connections changes. Every member has an immutable,
version-specific IRI and links to the stable connection with
`dct:isVersionOf`. The feed retains all observed changes from the last hour;
this sliding retention window is declared in the response as
`ldes:retentionPolicy` with `ldes:fullLogDuration "PT1H"`. The in-memory
history starts when the application starts and is rebuilt from subsequent
GTFS-Realtime updates.

## Installation for development purposes ##

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
the [provided SQL file](data/irail-stations-20260401.sql). A complete list of stations can be found in
the [irail/stations](https://github.com/irail/stations) repository.

## More links ##

* Our GTFS data dumps: http://gtfs.irail.be/
* Issue tracker: https://github.com/iRail/iRail/issues
* Just use our HTTP API: http://api.irail.be/
* BeTrains for Android app source code: https://github.com/iRail/BeTrains-for-Android
* Other repositories: https://github.com/iRail
