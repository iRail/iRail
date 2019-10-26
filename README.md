# iRail

[![License AGPL-3.0](https://img.shields.io/badge/license-AGPL--3.0-brightgreen.svg)](http://www.gnu.org/licenses/agpl-3.0.html) [![Join the chat at https://gitter.im/iRail/iRail](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/iRail/iRail?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

iRail supports digital creativity concerning mobility in Belgium. This is an attempt to make the railway time schedules in Belgium easily available for anyone. 

Our main site consists of a very easy mobile website to look up time schedules using our own API.

Native applications using the iRail API and created or supported by the iRail team are named BeTrains and RailerApp.

All information can be found on [our blog at hello.iRail.be](http://hello.irail.be/).

## API Documentation ##

API Documentation can be found at [https://docs.irail.be]().

## Installation for development purposes ##

_note: you'll also need to have [nodejs](https://nodejs.org), [composer](http://getcomposer.org) and PHP curl extension installed on your system_

 * Step 1: Clone this repo
 * Step 2: If you don't need the occupancy functionality, you can remove the `mongodb/mongodb` requirement from the composer file. You can now run `composer install`. If you'd like to have support for the occupancy scores, read below on how to setup mongo before proceeding to run `composer install`.
 * Step 3: Make sure storage is writable: `chmod 777 storage`
 * Step 4: Run your test server: `php -S localhost:8008 -t api`
 * Step 5: Enjoy your own iRail API at http://localhost:8008/connections.php?from=Gent%20Sint%20Pieters&to=Antwerp

### MongoDB / Spitsgids setup ###
**Optional**: if you want to set up the iRail API with occupancy scores you will need to set up a MongoDB database:

 * Install [MongoDB](https://www.mongodb.com/download-center?jmp=nav#community)
 * Install the MongoDB module for PHP: `pecl install mongodb`
 Make sure PHP loads the module: the conf.d folder for your PHP installation should contain a file with contents `extension=mongodb.so` in order to load the module. Both the CLI and web version need this, as Composer will run from the CLI
 * Add MongoDB environment variables: `cp .env.example .env` (If your MongoDB URL is different or you want another database name you can change this file)
 * Import the data (the structural.csv file) in MongoDB: `mongoimport -d irail -c structural --type csv --file occupancy/data/structural.csv --headerline`
 * Run the startscript to push structural data to the occupancy table: `php occupancy/scripts/startscript.php`
 * Once the startscript has ran, the task of pushing strutural data to the occupancy table should be automated: `crontab -e` => `30 3 * * * php $PATH_TO_IRAIL_FOLDER/occupancy/scripts/cronjob.php`
 * Enjoy the occupancy scores in all the GET requests. [Read the docs](https://docs.irail.be/) on how to post occupancy data.
 
**Imporant**: If you plan on using spitsgids in a production environment, don't forget to add indices. Most queries check either the connection (routes, liveboards endpoints) or vehicle field (vehicle endpoint). Example indices can be found below.
- For queries on vehicles: `db.occupancy.createIndex({vehicle: 1})` or `db.occupancy.createIndex({date: -1, vehicle: 1})`
- For queries on connections: `db.occupancy.createIndex({connection: 1})`

### Improving performance ###
**Optional**: you can improve performance by using [APCu](http://php.net/manual/en/book.apcu.php). APCu in-memory caching will automaticly be used when the APCu extension is available. When installed, every request to the NMBS will be cached for 15 seconds.


## Install with docker
 1. Clone this repo
 2. Run `docker-compose build` on the project root
 3. After building the container, start them using `docker-compose up -d`
 4. Run `docker-compose exec php composer install` to install project dependency
 5. Enjoy your own iRail API at http://localhost:8008/connections.php?from=Gent%20Sint%20Pieters&to=Antwerp
 
 **Optional**: if you want to set up the iRail API with occupancy scores you will need to import data to MongoDB:
 
1. First run `cp .env.example .env`
2. Replace `MONGODB_URL="mongodb://localhost:27017"` with `MONGODB_URL="mongodb://mongo:27017"`
3. Run this to to push structural data to the occupancy table : `docker-compose exec php php cupancy/scripts/startscript.php`
4. Run this to import the data (the structural.csv file) in MongoDB `docker-compose exec mongo mongoimport -d irail -c structural --type csv --file /data/structural.csv --headerline`
5. Once the startscript has ran, the task of pushing structural data to the occupancy table should be automated. In order to do this, edit the  `docker/php/crontab` file and uncomment the following line: `30 3 * * * root /usr/local/bin/php /var/www/html/occupancy/scripts/cronjob.php >> /var/log/cron.log 2>&1`


## Update stations list ##

Stations are updated through the [irail/stations](https://github.com/irail/stations) composer package. Just perform a `composer update` in the root of the project.

## More links ##

 * Our mailing list: http://list.irail.be/
 * Our GTFS data dumps: http://gtfs.irail.be/
 * Issue tracker: https://github.com/iRail/iRail/issues
 * Just use our HTTP API: http://api.irail.be/
 * BeTrains for Android app source code: https://github.com/iRail/BeTrains-for-Android
 * Other repositories: https://github.com/iRail
