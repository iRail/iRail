<?php

namespace Tests\Repositories\Irail;

use Carbon\Carbon;
use Irail\Models\StationInfo;
use Irail\Models\Vehicle;
use Irail\Models\VehicleComposition\RollingMaterialType;
use Irail\Models\VehicleComposition\TrainComposition;
use Irail\Models\VehicleComposition\TrainCompositionOnSegment;
use Irail\Models\VehicleComposition\TrainCompositionUnit;
use Irail\Repositories\Irail\HistoricCompositionRepository;
use Tests\InMemoryTestCase;

class HistoricCompositionRepositoryTest extends InMemoryTestCase
{
    function testRecordComposition_normalCase_shouldStoreCompositionInDatabase()
    {
        Carbon::setTestNow(Carbon::createFromDate(2023, 12, 21));
        $repo = new HistoricCompositionRepository();

        $vehicle = Vehicle::fromTypeAndNumber('IC', 513);
        $origin = new StationInfo('008814001', 'http://irail.be/stations/NMBS/008814001', 'Brussel-Zuid', 'Brussel-Zuid', null, null);
        $destination = new StationInfo('008892007', 'http://irail.be/stations/NMBS/008892007', 'Gent', 'Gent', null, null);
        $composition = new TrainComposition('TEST', [
            $this->createUnit(1, 'AM96', 'A', 20, 0, false),
            $this->createUnit(2, 'AM96', 'B', 20, 0, true),
            $this->createUnit(3, 'AM96', 'C', 0, 20, false),
        ]);
        $compositionOnSegment = new TrainCompositionOnSegment($origin, $destination, $composition);
        $repo->recordComposition($vehicle, $compositionOnSegment, Carbon::createFromDate(2023, 12, 21));

        $results = $repo->getHistoricCompositions($vehicle->getType(), $vehicle->getNumber(), 1);
        self::assertCount(1, $results);
        self::assertEquals(3, $results[0]->getPassengerUnitCount());
        self::assertEquals('AM96', $results[0]->getPrimaryMaterialType());
        self::assertEquals('IC', $results[0]->getJourneyType());
        self::assertEquals(513, $results[0]->getJourneyNumber());
        self::assertEquals('008814001', $results[0]->getFromStationId());
        self::assertEquals('008892007', $results[0]->getToStationId());

        $compositions = $repo->getHistoricComposition($vehicle->getType(), $vehicle->getNumber(), Carbon::createFromDate(2023, 12, 21));
        self::assertCount(1, $compositions);
    }

    function testRecordComposition_mixedCarriageTypes_shouldCorrectlyDeterminePrimaryType()
    {
        Carbon::setTestNow(Carbon::createFromDate(2023, 12, 21));

        $repo = new HistoricCompositionRepository();

        $vehicle = Vehicle::fromTypeAndNumber('IC', 514);
        $origin = new StationInfo('008814001', 'http://irail.be/stations/NMBS/008814001', 'Brussel-Zuid', 'Brussel-Zuid', null, null);
        $destination = new StationInfo('008892007', 'http://irail.be/stations/NMBS/008892007', 'Gent', 'Gent', null, null);
        $composition = new TrainComposition('TEST', [
            $this->createUnit(1, 'HLE', 'HLE18', 0, 0, false),

            $this->createUnit(2, 'AM96', 'A', 20, 0, false),
            $this->createUnit(3, 'AM96', 'B', 20, 0, true),
            $this->createUnit(4, 'AM96', 'C', 0, 20, false),

            $this->createUnit(5, 'M7', 'A', 20, 0, false),
            $this->createUnit(6, 'M7', 'B', 20, 0, true),
            $this->createUnit(7, 'M7', 'C', 0, 20, false),
            $this->createUnit(8, 'M7', 'D', 0, 20, false),
        ]);
        $compositionOnSegment = new TrainCompositionOnSegment($origin, $destination, $composition);
        $repo->recordComposition($vehicle, $compositionOnSegment, Carbon::createFromDate(2023, 12, 21));

        $results = $repo->getHistoricCompositions($vehicle->getType(), $vehicle->getNumber(), 1);
        self::assertCount(1, $results);
        self::assertEquals(7, $results[0]->getPassengerUnitCount());
        self::assertEquals('M7', $results[0]->getPrimaryMaterialType());
        self::assertEquals('IC', $results[0]->getJourneyType());
        self::assertEquals(514, $results[0]->getJourneyNumber());
        self::assertEquals('008814001', $results[0]->getFromStationId());
        self::assertEquals('008892007', $results[0]->getToStationId());
    }


    function testRecordComposition_multipleSegments_shouldStoreCompositionForEachSegment()
    {
        Carbon::setTestNow(Carbon::createFromDate(2023, 12, 21));

        $repo = new HistoricCompositionRepository();

        $vehicle = Vehicle::fromTypeAndNumber('IC', 514);
        $origin = new StationInfo('008814001', 'http://irail.be/stations/NMBS/008814001', 'Brussel-Zuid', 'Brussel-Zuid', null, null);
        $split = new StationInfo('008892007', 'http://irail.be/stations/NMBS/008892007', 'Gent', 'Gent', null, null);
        $dest1 = new StationInfo('008891405', 'http://irail.be/stations/NMBS/008891405', 'Blankenberge', 'Blankenberge', null, null);
        $dest2 = new StationInfo('008891660', 'http://irail.be/stations/NMBS/008891660', 'Knokke', 'Knokke', null, null);
        $composition = new TrainComposition('TEST', [
            $this->createUnit(1, 'HLE', 'HLE18', 0, 0, false),

            $this->createUnit(2, 'AM96', 'A', 20, 0, false),
            $this->createUnit(3, 'AM96', 'B', 20, 0, true),
            $this->createUnit(4, 'AM96', 'C', 0, 20, false),

            $this->createUnit(5, 'M7', 'A', 20, 0, false),
            $this->createUnit(6, 'M7', 'B', 20, 0, true),
            $this->createUnit(7, 'M7', 'C', 0, 20, false),
            $this->createUnit(8, 'M7', 'D', 0, 20, false),
        ]);
        $compositionSplit1 = new TrainComposition('TEST', [
            $this->createUnit(1, 'HLE', 'HLE18', 0, 0, false),

            $this->createUnit(5, 'M7', 'A', 20, 0, false),
            $this->createUnit(6, 'M7', 'B', 20, 0, true),
            $this->createUnit(7, 'M7', 'C', 0, 20, false),
            $this->createUnit(8, 'M7', 'D', 0, 20, false),
        ]);
        $compositionSplit12 = new TrainComposition('TEST', [
            $this->createUnit(2, 'AM96', 'A', 20, 0, false),
            $this->createUnit(3, 'AM96', 'B', 20, 0, true),
            $this->createUnit(4, 'AM96', 'C', 0, 20, false),
        ]);
        $compositionOnSegment1 = new TrainCompositionOnSegment($origin, $split, $composition);
        $compositionSplit1 = new TrainCompositionOnSegment($split, $dest1, $compositionSplit1);
        $compositionSplit2 = new TrainCompositionOnSegment($split, $dest2, $compositionSplit12);
        $repo->recordComposition($vehicle, $compositionOnSegment1, Carbon::createFromDate(2023, 12, 21));
        $repo->recordComposition($vehicle, $compositionSplit1, Carbon::createFromDate(2023, 12, 21));
        $repo->recordComposition($vehicle, $compositionSplit2, Carbon::createFromDate(2023, 12, 21));

        $results = $repo->getHistoricCompositions($vehicle->getType(), $vehicle->getNumber(), 1);
        self::assertCount(3, $results);
        self::assertEquals(7, $results[0]->getPassengerUnitCount());
        self::assertEquals('M7', $results[0]->getPrimaryMaterialType());
        self::assertEquals('M7', $results[1]->getPrimaryMaterialType());
        self::assertEquals('AM96', $results[2]->getPrimaryMaterialType());
        self::assertEquals('IC', $results[0]->getJourneyType());
        self::assertEquals(514, $results[0]->getJourneyNumber());
        self::assertEquals('008814001', $results[0]->getFromStationId());
        self::assertEquals('008892007', $results[0]->getToStationId());
        self::assertEquals('008891405', $results[1]->getToStationId());
        self::assertEquals('008891660', $results[2]->getToStationId());

        $compositions = $repo->getHistoricComposition($vehicle->getType(), $vehicle->getNumber(), Carbon::createFromDate(2023, 12, 21));
        self::assertCount(3, $compositions);
    }

    /**
     * @return TrainCompositionUnit
     */
    public function createUnit(
        int $uicCode,
        string $parentType,
        string $subType,
        int $seatsSecondClass,
        int $seatsFirstClass,
        bool $toilet
    ): TrainCompositionUnit {
        return (new TrainCompositionUnit(new RollingMaterialType($parentType, $subType)))
            ->setUicCode(88000000 + $uicCode)
            ->setMaterialNumber($uicCode)
            ->setSeatsSecondClass($seatsSecondClass)
            ->setSeatsFirstClass($seatsFirstClass)
            ->setHasToilet($toilet)
            ->setHasPrmToilet($toilet)
            ->setHasPrmSection(false)
            ->setHasAirco(false)
            ->setHasBikeSection(false)
            ->setHasAirco(false);
    }
}