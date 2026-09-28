package be.irail.api.riv;

import be.irail.api.exception.IrailHttpException;
import be.irail.api.exception.upstream.UpstreamRateLimitException;
import be.irail.api.exception.upstream.UpstreamServerException;
import be.irail.api.exception.upstream.UpstreamServerParameterException;
import be.irail.api.exception.upstream.UpstreamServerTimeoutException;
import be.irail.api.exception.upstream.UpstreamServerUnavailableException;
import org.junit.jupiter.api.Test;

import java.time.LocalDate;
import java.time.LocalTime;
import java.util.List;
import java.util.Map;

import static org.junit.jupiter.api.Assertions.assertEquals;
import static org.junit.jupiter.api.Assertions.assertFalse;
import static org.junit.jupiter.api.Assertions.assertNull;
import static org.junit.jupiter.api.Assertions.assertTrue;

class NmbsRivRawDataRepositoryTest {

    // --- which journey-ref failures may be remembered ---------------------------------

    @Test
    void aParameterErrorIsAnAnswerFromRiv() {
        // RIV returned an errorText for the stretch being searched, so there really is no journey
        // there. Safe to skip to the next stretch and to cache the miss.
        assertTrue(NmbsRivRawDataRepository.isConclusiveJourneyRefFailure(
                new UpstreamServerParameterException("No journey found")));
    }

    @Test
    void aRateLimitIsNotAnAnswerAboutTheVehicle() {
        // The request never left the building. Caching this would pin the vehicle to 404 for the
        // four-hour lifetime of the journeyDetailRef cache entry.
        assertFalse(NmbsRivRawDataRepository.isConclusiveJourneyRefFailure(
                new UpstreamRateLimitException()));
    }

    @Test
    void anUnavailableUpstreamIsNotAnAnswerAboutTheVehicle() {
        assertFalse(NmbsRivRawDataRepository.isConclusiveJourneyRefFailure(
                new UpstreamServerUnavailableException()));
    }

    @Test
    void aTimeoutIsNotAnAnswerAboutTheVehicle() {
        assertFalse(NmbsRivRawDataRepository.isConclusiveJourneyRefFailure(
                new UpstreamServerTimeoutException()));
    }

    @Test
    void ageneric5xxIsNotAnAnswerAboutTheVehicle() {
        assertFalse(NmbsRivRawDataRepository.isConclusiveJourneyRefFailure(
                new UpstreamServerException("upstream blew up")));
    }

    @Test
    void anUnrecognisedHttpFailureIsNotAnAnswerAboutTheVehicle() {
        assertFalse(NmbsRivRawDataRepository.isConclusiveJourneyRefFailure(
                new IrailHttpException(500, "something else went wrong")));
    }

    // --- which stop ids are sent to the journey search ---------------------------------

    private static JourneyWithOriginAndDestination journey(String originStopId, String destinationStopId) {
        return new JourneyWithOriginAndDestination(LocalDate.of(2026, 9, 23), "88____:007::8200102:8200100:3:1600:20261212",
                "IC", 5315, originStopId, 0, destinationStopId, 3600, 3, List.of());
    }

    @Test
    void sendsSevenDigitHafasIdsForNamespacedStopIds() {
        Map<String, String> params = NmbsRivRawDataRepository.journeyRefSearchParams(
                journey("gs:nmbssncb:8200102", "gs:nmbssncb:8200100_4"), LocalTime.of(15, 31));

        assertEquals(Map.of(
                "trainFilter", "IC5315",
                "originExtId", "8200102",
                "destExtId", "8200100",
                "date", "2026-09-23",
                "time", "15:31"
        ), params);
    }

    @Test
    void sendsSevenDigitHafasIdsForLegacyStopIds() {
        Map<String, String> params = NmbsRivRawDataRepository.journeyRefSearchParams(
                journey("8200102", "8200100_4"), LocalTime.of(15, 31));

        assertEquals("8200102", params.get("originExtId"));
        assertEquals("8200100", params.get("destExtId"));
    }

    @Test
    void skipsTheSearchWhenAStopHasNoHafasId() {
        // Name-based international stops have no seven-digit id; sending them is a guaranteed 400.
        assertNull(NmbsRivRawDataRepository.journeyRefSearchParams(
                journey("gs:nmbssncb:8200102", "nmbssncb:s-londonstpancrasgb"), LocalTime.of(15, 31)));
    }
}
