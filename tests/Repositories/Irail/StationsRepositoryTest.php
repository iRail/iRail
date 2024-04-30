<?php

namespace Tests\Repositories\Irail;

use Irail\Repositories\Irail\StationsRepository;
use Tests\TestCase;

class StationsRepositoryTest extends TestCase
{
    public function testGetStation_getLocalizedName_shouldReturnCorrectTranslation()
    {
        $stationsRepo = new StationsRepository();
        $stationsRepo->setLocalizedLanguage('nl');
        $this->assertEquals('Brussel-Zuid', $stationsRepo->getStationById('008814001')->getLocalizedStationName());

        // Changing the name should have immediate effect, any caching should include the language to ensure different requests get the correct language
        $stationsRepo->setLocalizedLanguage('fr');
        $this->assertEquals('Bruxelles-Midi', $stationsRepo->getStationById('008814001')->getLocalizedStationName());

        $stationsRepo->setLocalizedLanguage('en');
        $this->assertEquals('Brussels-South/Brussels-Midi', $stationsRepo->getStationById('008814001')->getLocalizedStationName());
    }

    public function testGetStation_getLocalizedName_useDefaultNameWhenNotTranslated()
    {
        $stationsRepo = new StationsRepository();
        $stationsRepo->setLocalizedLanguage('de');
        $this->assertEquals('Brussel-Zuid/Bruxelles-Midi', $stationsRepo->getStationById('008814001')->getLocalizedStationName());
    }
}
