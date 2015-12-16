# iRail

[![License AGPL-3.0](https://img.shields.io/badge/license-AGPL--3.0-brightgreen.svg)](http://www.gnu.org/licenses/agpl-3.0.html) [![Join the chat at https://gitter.im/iRail/iRail](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/iRail/iRail?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

iRail supports digital creativity concerning mobility in Belgium. This is an attempt to make the railway time schedules in Belgium easily available for anyone. 

Our main site consists of a very easy mobile website to look up time schedules using our own API.

Native applications using the iRail API and created or supported by the iRail team are named BeTrains and RailerApp.

All information can be found on [our blog at hello.iRail.be](http://hello.irail.be/).

## Configuration ##

For the configuration we used a package named dotenv. 
There is a `.env example` located in the base folder from the project. 
`composer install` wil write a nex .env file for your confiration. 
The only thing you need to do is change the variables so it fits to your credentails.

 * Step 1: clone this repo
 * Step 2: `composer install`
 * Step 3: Run your test server: `php -S localhost:8008 -t api`
 * Step 4: Enjoy your own iRail API at http://localhost:8008/connections.php?from=Gent%20Sint%20Pieters&to=Antwerp

## Update stations list ##

Stations are updated through the irail/stations composer package. Just perform a `composer update` in the root of the project

## Some interesting links: ##

  * Source: <http://github.com/iRail/iRail>
  * Mailing: <http://list.irail.be/>
  * Issue tracking: <https://github.com/iRail/iRail/issues>
  * API: <http://api.irail.be/>
  * BeTrains code: <https://github.com/iRail/BeTrains-for-Android>
