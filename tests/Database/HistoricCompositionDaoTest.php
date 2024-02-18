<?php

namespace Tests\Database;

use Carbon\Carbon;
use Irail\Database\HistoricCompositionDao;
use Irail\Models\Station;
use Irail\Models\Vehicle;
use Irail\Models\VehicleComposition\RollingMaterialType;
use Irail\Models\VehicleComposition\TrainComposition;
use Irail\Models\VehicleComposition\TrainCompositionUnit;
use Tests\InMemoryTestCase;

class HistoricCompositionDaoTest extends InMemoryTestCase
{
    function testRecordComposition_normalCase_shouldStoreCompositionInDatabase()
    {
        Carbon::setTestNow(Carbon::createFromDate(2023, 12, 21));
        $repo = new HistoricCompositionDao();

        $vehicle = Vehicle::fromTypeAndNumber('IC', 513, Carbon::createFromDate(2023, 12, 21));
        $origin = new Station('008814001', 'http://irail.be/stations/NMBS/008814001', 'Brussel-Zuid', 'Brussel-Zuid', null, null);
        $destination = new Station('008892007', 'http://irail.be/stations/NMBS/008892007', 'Gent', 'Gent', null, null);
        $compositionOnSegment = new TrainComposition($vehicle, $origin, $destination, 'TEST', [
            $this->createUnit(1, 'AM96', 'A', 20, 0, false),
            $this->createUnit(2, 'AM96', 'B', 20, 0, true),
            $this->createUnit(3, 'AM96', 'C', 0, 20, false),
        ]);
        $repo->recordComposition($compositionOnSegment);

        $results = $repo->getHistoricCompositions($vehicle->getType(), $vehicle->getNumber(), 1);
        self::assertCount(1, $results);
        self::assertEquals(3, $results[0]->getPassengerUnitCount());
        self::assertEquals('AM96', $results[0]->getPrimaryMaterialType());
        self::assertEquals('IC', $results[0]->getJourneyType());
        self::assertEquals(513, $results[0]->getJourneyNumber());
        self::assertEquals('008814001', $results[0]->getFromStationId());
        self::assertEquals('008892007', $results[0]->getToStationId());

        $compositions = $repo->getHistoricComposition($vehicle);
        self::assertCount(1, $compositions);
    }

    function testRecordComposition_mixedCarriageTypes_shouldCorrectlyDeterminePrimaryType()
    {
        Carbon::setTestNow(Carbon::createFromDate(2023, 12, 21));

        $repo = new HistoricCompositionDao();

        $vehicle = Vehicle::fromTypeAndNumber('IC', 514);
        $origin = new Station('008814001', 'http://irail.be/stations/NMBS/008814001', 'Brussel-Zuid', 'Brussel-Zuid', null, null);
        $destination = new Station('008892007', 'http://irail.be/stations/NMBS/008892007', 'Gent', 'Gent', null, null);
        $compositionOnSegment = new TrainComposition($vehicle, $origin, $destination, 'TEST', [
            $this->createUnit(1, 'HLE', 'HLE18', 0, 0, false),

            $this->createUnit(2, 'AM96', 'A', 20, 0, false),
            $this->createUnit(3, 'AM96', 'B', 20, 0, true),
            $this->createUnit(4, 'AM96', 'C', 0, 20, false),

            $this->createUnit(5, 'M7', 'A', 20, 0, false),
            $this->createUnit(6, 'M7', 'B', 20, 0, true),
            $this->createUnit(7, 'M7', 'C', 0, 20, false),
            $this->createUnit(8, 'M7', 'D', 0, 20, false),
        ]);
        $repo->recordComposition($compositionOnSegment);

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
        $repo = new HistoricCompositionDao();

        $vehicle = Vehicle::fromTypeAndNumber('IC', 514, Carbon::createFromDate(2023, 12, 21));

        $origin = new Station('008814001', 'http://irail.be/stations/NMBS/008814001', 'Brussel-Zuid', 'Brussel-Zuid', null, null);
        $split = new Station('008892007', 'http://irail.be/stations/NMBS/008892007', 'Gent', 'Gent', null, null);
        $dest1 = new Station('008891405', 'http://irail.be/stations/NMBS/008891405', 'Blankenberge', 'Blankenberge', null, null);
        $dest2 = new Station('008891660', 'http://irail.be/stations/NMBS/008891660', 'Knokke', 'Knokke', null, null);

        $compositionOnSegment1 = new TrainComposition($vehicle, $origin, $split, 'TEST', [
            $this->createUnit(1, 'HLE', 'HLE18', 0, 0, false),

            $this->createUnit(2, 'AM96', 'A', 20, 0, false),
            $this->createUnit(3, 'AM96', 'B', 20, 0, true),
            $this->createUnit(4, 'AM96', 'C', 0, 20, false),

            $this->createUnit(5, 'M7', 'A', 20, 0, false),
            $this->createUnit(6, 'M7', 'B', 20, 0, true),
            $this->createUnit(7, 'M7', 'C', 0, 20, false),
            $this->createUnit(8, 'M7', 'D', 0, 20, false),
        ]);
        $compositionSplit1 = new TrainComposition($vehicle, $split, $dest1, 'TEST', [
            $this->createUnit(1, 'HLE', 'HLE18', 0, 0, false),

            $this->createUnit(5, 'M7', 'A', 20, 0, false),
            $this->createUnit(6, 'M7', 'B', 20, 0, true),
            $this->createUnit(7, 'M7', 'C', 0, 20, false),
            $this->createUnit(8, 'M7', 'D', 0, 20, false),
        ]);
        $compositionSplit2 = new TrainComposition($vehicle, $split, $dest2, 'TEST', [
            $this->createUnit(2, 'AM96', 'A', 20, 0, false),
            $this->createUnit(3, 'AM96', 'B', 20, 0, true),
            $this->createUnit(4, 'AM96', 'C', 0, 20, false),
        ]);
        $repo->recordComposition($compositionOnSegment1);
        $repo->recordComposition($compositionSplit1);
        $repo->recordComposition($compositionSplit2);

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

        $compositions = $repo->getHistoricComposition($vehicle);
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