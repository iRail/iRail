package be.irail.api.riv;

import be.irail.api.exception.IrailHttpException;
import be.irail.api.exception.upstream.UpstreamRateLimitException;
import be.irail.api.exception.upstream.UpstreamServerException;
import be.irail.api.exception.upstream.UpstreamServerParameterException;
import be.irail.api.exception.upstream.UpstreamServerTimeoutException;
import be.irail.api.exception.upstream.UpstreamServerUnavailableException;
import org.junit.jupiter.api.Test;

import static org.junit.jupiter.api.Assertions.assertFalse;
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
}
