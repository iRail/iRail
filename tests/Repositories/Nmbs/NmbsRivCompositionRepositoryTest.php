<?php

namespace Tests\Repositories\Nmbs;

use Carbon\Carbon;
use Exception;
use Irail\Exceptions\CompositionUnavailableException;
use Irail\Models\CachedData;
use Irail\Models\Vehicle;
use Irail\Repositories\Gtfs\GtfsTripStartEndExtractor;
use Irail\Repositories\Gtfs\Models\JourneyWithOriginAndDestination;
use Irail\Repositories\Irail\StationsRepository;
use Irail\Repositories\Nmbs\NmbsRivCompositionRepository;
use Irail\Repositories\Riv\NmbsRivRawDataRepository;
use Mockery;
use Tests\TestCase;

class NmbsRivCompositionRepositoryTest extends TestCase
{
    public function testGetVehicleComposition_emptyResponse_shouldHandleEmptyResponseAsTrainNotFound()
    {
        $startEndExtractor = Mockery::mock(GtfsTripStartEndExtractor::class);
        $rawRivDataRepo = Mockery::mock(NmbsRivRawDataRepository::class);
        $repo = new NmbsRivCompositionRepository(new StationsRepository(), $rawRivDataRepo, $startEndExtractor);

        $journeyStartEnd = new JourneyWithOriginAndDestination('test', '', 0, '', Carbon::now()->secondsSinceMidnight(), '', 0);
        $startEndExtractor->shouldReceive('getVehicleWithOriginAndDestination')->andReturn($journeyStartEnd);
        $vehicle = Vehicle::fromName('92271', Carbon::create(2024, 4, 27));

        $rawRivDataRepo->expects('getVehicleCompositionData')->with($vehicle, $journeyStartEnd)->andReturn(
            new CachedData('test', json_decode(file_get_contents(__DIR__ . '/../../Fixtures/composition/NmbsRivComposition_ic9271_emptyResponse.json'), true),
                1)
        );

        try {
            $repo->getComposition($vehicle);
            self::fail('Should throw an exception');
        } catch (Exception $e) {
            self::assertInstanceOf(CompositionUnavailableException::class, $e);
        }
    }

    public function testGetVehicleComposition_missingMaterialSubtype_shouldHandleEmptyResponseAsTrainNotFound()
    {
        $vehicle = Vehicle::fromName('13606', Carbon::create(2024, 4, 27));
        $journeyStartEnd = new JourneyWithOriginAndDestination('test', '', 0, '', Carbon::now()->secondsSinceMidnight(), '', 0);
        $rivRawDataRepo = $this->mockFixtureVehicleCompositionResponse($vehicle, $journeyStartEnd,
            'composition/NmbsRivComposition_ic13606_missingDetails_OR.json');
        $startEndExtractor = Mockery::mock(GtfsTripStartEndExtractor::class);
        $startEndExtractor->shouldReceive('getVehicleWithOriginAndDestination')->andReturn($journeyStartEnd);
        $rivCompositionRepo = new NmbsRivCompositionRepository(new StationsRepository(), $rivRawDataRepo, $startEndExtractor);

        $result = $rivCompositionRepo->getComposition($vehicle);
        self::assertCount(1, $result->getSegments());
        self::assertCount(7, $result->getSegments()[0]->getUnits());
    }
}
