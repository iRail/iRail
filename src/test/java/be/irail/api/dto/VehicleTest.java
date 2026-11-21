package be.irail.api.dto;

import com.google.common.cache.Cache;
import com.google.common.cache.CacheBuilder;
import org.junit.jupiter.api.Test;

import java.time.LocalDate;
import java.util.concurrent.TimeUnit;

import static org.junit.jupiter.api.Assertions.assertEquals;
import static org.junit.jupiter.api.Assertions.assertNotEquals;
import static org.junit.jupiter.api.Assertions.assertSame;

class VehicleTest {

    private static final LocalDate JOURNEY_START = LocalDate.of(2026, 8, 17);

    @Test
    void vehiclesForTheSameDatedJourneyAreEqual() {
        Vehicle first = Vehicle.fromTypeAndNumber("IC", 538, JOURNEY_START);
        Vehicle second = Vehicle.fromTypeAndNumber("IC", 538, JOURNEY_START);

        assertEquals(first, second);
        assertEquals(first.hashCode(), second.hashCode());
    }

    @Test
    void aDifferentTypeNumberOrStartDateIsADifferentVehicle() {
        Vehicle vehicle = Vehicle.fromTypeAndNumber("IC", 538, JOURNEY_START);

        assertNotEquals(vehicle, Vehicle.fromTypeAndNumber("S10", 538, JOURNEY_START));
        assertNotEquals(vehicle, Vehicle.fromTypeAndNumber("IC", 539, JOURNEY_START));
        assertNotEquals(vehicle, Vehicle.fromTypeAndNumber("IC", 538, JOURNEY_START.plusDays(1)));
    }

    @Test
    void theDirectionDoesNotAffectEquality() {
        // The direction is filled in after construction, from a different source per endpoint. It describes the
        // same journey, so it must not split cache entries.
        Vehicle withoutDirection = Vehicle.fromTypeAndNumber("IC", 538, JOURNEY_START);
        Vehicle withDirection = Vehicle.fromTypeAndNumber("IC", 538, JOURNEY_START);
        withDirection.setDirection(new VehicleDirection("Oostende", null));

        assertEquals(withoutDirection, withDirection);
        assertEquals(withoutDirection.hashCode(), withDirection.hashCode());
    }

    @Test
    void aVehicleKeyedCacheHitsForAFreshlyBuiltEquivalentVehicle() {
        // NmbsRivCompositionClient caches its RIV responses under a Vehicle. Every request builds a new Vehicle
        // instance from the GTFS data, so without value equality that cache could never be hit and each
        // /v1/composition request reached the upstream API again.
        Cache<Vehicle, String> cache = CacheBuilder.newBuilder()
                .maximumSize(10)
                .expireAfterWrite(30, TimeUnit.MINUTES)
                .build();
        cache.put(Vehicle.fromTypeAndNumber("IC", 538, JOURNEY_START), "composition");

        String cached = cache.getIfPresent(Vehicle.fromTypeAndNumber("IC", 538, JOURNEY_START));

        assertSame("composition", cached);
    }
}
