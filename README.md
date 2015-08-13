# iRail

[![Join the chat at https://gitter.im/iRail/iRail](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/iRail/iRail?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

iRail is an attempt to make transportation time schedules easily available for anyone. 

We are doing this by creating an Application Programming Interface. This interface is implemented in PHP and can be reused by various of other projects.

Our main site consists of a very easy mobile website to look up time schedules using our own API.

Native applications using the iRail API and created or supported by the iRail team are named BeTrains and RailerApp.

All information can be found on [our blog at hello.iRail.be](http://hello.irail.be/).

## Configuration ##

For the configuration we used a package named dotenv. 
There is a `.env example` located in the base folder from the project. 
`composer install` wil write a nex .env file for your confiration. 
The only thing you need to do is change the variables so it fits to your credentails.

## Update stations list ##

Stations are updated through the irail/stations composer package. Just perform a `composer update` in the root of the project

## Some interesting links: ##

  * Source: <http://github.com/iRail/iRail>
  * Mailing: <http://list.irail.be/>
  * Issue tracking: <https://github.com/iRail/iRail/issues>
  * API: <http://api.irail.be/>
  * BeTrains code: <https://github.com/iRail/BeTrains-for-Android>
