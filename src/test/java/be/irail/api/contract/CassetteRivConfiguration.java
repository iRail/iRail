package be.irail.api.contract;

import be.irail.api.dto.CachedData;
import be.irail.api.riv.NmbsRivRawDataRepository;
import be.irail.api.riv.requests.LiveboardRequest;
import com.fasterxml.jackson.databind.JsonNode;
import org.springframework.boot.test.context.TestConfiguration;
import org.springframework.context.annotation.Bean;
import org.springframework.context.annotation.Primary;

/**
 * Replaces the NMBS repository with one that answers from recorded cassettes.
 *
 * <p>This is the seam the whole harness rests on. {@link NmbsRivRawDataRepository} is the single
 * point where iRail talks to NMBS, and it is already constructor-injected into every client, so
 * substituting it needs no production change at all. Everything above it — parsing, conversion,
 * serialisation, the HTTP layer — runs exactly as in production.
 */
@TestConfiguration
public class CassetteRivConfiguration {

    /** UIC code of the station the liveboard cassette was recorded for. */
    public static final String LIVEBOARD_STATION_UIC = "8814001";

    @Bean
    @Primary
    NmbsRivRawDataRepository cassetteRivDataRepository() {
        return new CassetteRivDataRepository();
    }

    /**
     * Serves recorded responses instead of calling NMBS. Any request the cassettes do not cover
     * fails loudly rather than silently returning nothing, so a test cannot quietly assert on an
     * empty result.
     */
    static class CassetteRivDataRepository extends NmbsRivRawDataRepository {

        CassetteRivDataRepository() {
            // Rate limit and key are irrelevant here: no request leaves the process.
            super(10, "cassette");
        }

        @Override
        public CachedData<JsonNode> getLiveboardData(LiveboardRequest request) {
            String uic = request.station().getHafasId();
            if (!LIVEBOARD_STATION_UIC.equals(uic)) {
                throw new IllegalStateException("No liveboard cassette recorded for station " + uic);
            }
            return new CachedData<>(Cassettes.upstream("riv-liveboard-brussels-south.json"), 60);
        }
    }
}
