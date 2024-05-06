<?php

namespace Tests\Repositories\Nmbs;

use Carbon\Carbon;
use Irail\Database\OccupancyDao;
use Irail\Http\Requests\LiveboardRequest;
use Irail\Http\Requests\TimeSelection;
use Irail\Models\OccupancyInfo;
use Irail\Models\OccupancyLevel;
use Irail\Repositories\Gtfs\GtfsTripStartEndExtractor;
use Irail\Repositories\Irail\StationsRepository;
use Irail\Repositories\Nmbs\NmbsHtmlLiveboardRepository;
use Mockery;
use Tests\FakeCurlProxy;
use Tests\TestCase;

class NmbsHtmlLiveboardRepositoryTest extends TestCase
{
    public function testGetLiveboard_departureBoardNormalCase_shouldParseDataCorrectly(): void
    {
        $stationsRepo = new StationsRepository();
        $curlProxy = new FakeCurlProxy();
        $curlProxy->fakeGet('http://www.belgianrail.be/jp/nmbs-realtime/stboard.exe/nn', [
            'ld'                      => 'std',
            'boardType'               => 'dep',
            'time'                    => '12:58:00',
            'date'                    => '15/12/2023',
            'maxJourneys'             => 50,
            'wDayExtsq'               => 'Ma|Di|Wo|Do|Vr|Za|Zo',
            'input'                   => 'Antwerpen-Centraal',
            'inputRef'                => 'Antwerpen-Centraal#8821006',
            'REQ0JourneyStopsinputID' => 'A=1@O=Antwerpen-Centraal@X=4356802@Y=50845649@U=80@L=008821006@B=1@p=1669420371@n=ac.1=FA@n=ac.2=LA@n=ac.3=FS@n=ac.4=LS@n=ac.5=GA@',
            'REQProduct_list'         => '5:1111111000000000',
            'realtimeMode'            => 'show',
            'start'                   => 'yes'
            // language is not passed, since we need to parse the resulting webpage
        ], [], 200, __DIR__ . '/NmbsHtmlLiveboardRepositoryTest_departuresAntwerpen.html');

        $occupancyDao = Mockery::mock(OccupancyDao::class);
        $occupancyDao->shouldReceive('getOccupancy')->andReturn(new OccupancyInfo(OccupancyLevel::UNKNOWN, OccupancyLevel::UNKNOWN));

        $gtfsStartEndExtractor = Mockery::mock(GtfsTripStartEndExtractor::class);
        $gtfsStartEndExtractor->expects('getStartDate')->times(50)->andReturn(Carbon::create(2023, 12, 15));

        $liveboardRepo = new NmbsHtmlLiveboardRepository($stationsRepo, $curlProxy, $gtfsStartEndExtractor, $occupancyDao);
        $request = $this->createRequest('008821006', TimeSelection::DEPARTURE, 'NL', Carbon::create(2023, 12, 15, 12, 58));
        $response = $liveboardRepo->getLiveboard($request);

        self::assertEquals(50, count($response->getStops()));
        self::assertEquals('008821006', $response->getStops()[0]->getStation()->getId());
        self::assertEquals('Antwerpen-Centraal', $response->getStops()[0]->getStation()->getStationName());
        self::assertEquals('008833001', $response->getStops()[0]->getVehicle()->getDirection()->getStation()->getId());
        self::assertEquals('Leuven', $response->getStops()[0]->getVehicle()->getDirection()->getName());
        self::assertEquals(Carbon::create(2023, 12, 15, 12, 58), $response->getStops()[0]->getScheduledDateTime());
        self::assertEquals('http://irail.be/connections/8821006/20231215/L2862', $response->getStops()[0]->getDepartureUri());

        self::assertEquals('008400058', $response->getStops()[12]->getVehicle()->getDirection()->getStation()->getId());
        self::assertEquals('Amsterdam Cs (NL)', $response->getStops()[12]->getVehicle()->getDirection()->getName());
    }

    public function testGetLiveboard_departureBoardPlatformChanges_shouldParsePlatformsCorrectly(): void
    {
        $stationsRepo = new StationsRepository();
        $curlProxy = new FakeCurlProxy();
        $curlProxy->fakeGet('http://www.belgianrail.be/jp/nmbs-realtime/stboard.exe/nn', [
            'ld'                      => 'std',
            'boardType'               => 'dep',
            'time'                    => '15:50:00',
            'date'                    => '28/01/2024',
            'maxJourneys'             => 50,
            'wDayExtsq'               => 'Ma|Di|Wo|Do|Vr|Za|Zo',
            'input'                   => 'Brussel-Zuid/Bruxelles-Midi',
            'inputRef'                => 'Brussel-Zuid/Bruxelles-Midi#8814001',
            'REQ0JourneyStopsinputID' => 'A=1@O=Brussel-Zuid/Bruxelles-Midi@X=4356802@Y=50845649@U=80@L=008814001@B=1@p=1669420371@n=ac.1=FA@n=ac.2=LA@n=ac.3=FS@n=ac.4=LS@n=ac.5=GA@',
            'REQProduct_list'         => '5:1111111000000000',
            'realtimeMode'            => 'show',
            'start'                   => 'yes'
            // language is not passed, since we need to parse the resulting webpage
        ], [], 200, __DIR__ . '/NmbsHtmlLiveboardRepositoryTest_platformChanges.html');
        $occupancyDao = Mockery::mock(OccupancyDao::class);
        $occupancyDao->shouldReceive('getOccupancy')->andReturn(new OccupancyInfo(OccupancyLevel::UNKNOWN, OccupancyLevel::UNKNOWN));

        $gtfsStartEndExtractor = Mockery::mock(GtfsTripStartEndExtractor::class);
        $gtfsStartEndExtractor->expects('getStartDate')->times(52)->andReturn(Carbon::create(2023, 12, 15));

        $liveboardRepo = new NmbsHtmlLiveboardRepository($stationsRepo, $curlProxy, $gtfsStartEndExtractor, $occupancyDao);
        $request = $this->createRequest('008814001', TimeSelection::DEPARTURE, 'NL', Carbon::create(2024, 1, 28, 15, 50));
        $response = $liveboardRepo->getLiveboard($request);

        self::assertEquals(52, count($response->getStops()));
        self::assertEquals('6', $response->getStops()[0]->getPlatform()->getDesignation());
        self::assertEquals(true, $response->getStops()[0]->getPlatform()->hasChanged());
        self::assertEquals('16', $response->getStops()[1]->getPlatform()->getDesignation());
        self::assertEquals(false, $response->getStops()[1]->getPlatform()->hasChanged());
        self::assertEquals('?', $response->getStops()[47]->getPlatform()->getDesignation());
        self::assertEquals(false, $response->getStops()[47]->getPlatform()->hasChanged());
    }

    private function createRequest(string $station, TimeSelection $timeSelection, string $language, Carbon $dateTime): LiveboardRequest
    {
        $mock = Mockery::mock(LiveboardRequest::class);
        $mock->shouldReceive('getStationId')->andReturn($station);
        $mock->shouldReceive('getDateTime')->andReturn($dateTime);
        $mock->shouldReceive('getDepartureArrivalMode')->andReturn($timeSelection);
        $mock->shouldReceive('getLanguage')->andReturn($language);
        $mock->shouldReceive('getCacheId')->andReturn("$station|$timeSelection->value|$language|$dateTime");
        return $mock;
    }
}
