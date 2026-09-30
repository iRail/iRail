package be.irail.api.contract;

import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;

import java.io.IOException;
import java.io.InputStream;
import java.io.UncheckedIOException;
import java.nio.charset.StandardCharsets;

/**
 * Loads recorded upstream responses and expected V1 output from the test resources.
 *
 * <p>A cassette is one real NMBS response, captured once and committed. Tests replay it instead of
 * calling NMBS, which is what makes them deterministic and lets the suite run on every commit
 * without a key and without adding load to an API we do not own.
 *
 * <p>Cassettes are deliberately frozen. When NMBS changes the shape of a response the fixture
 * should be re-recorded on purpose, and the resulting diff reviewed — that diff is the signal.
 */
final class Cassettes {

    private static final ObjectMapper MAPPER = new ObjectMapper();

    private Cassettes() {
    }

    /**
     * Reads a recorded upstream response.
     *
     * @param name file name under {@code /cassettes}
     * @return the parsed response
     */
    static JsonNode upstream(String name) {
        try {
            return MAPPER.readTree(read("/cassettes/" + name));
        } catch (IOException e) {
            throw new UncheckedIOException(e);
        }
    }

    /**
     * Reads an expected V1 response.
     *
     * @param name file name under {@code /golden}
     * @return the file contents
     */
    static String golden(String name) {
        return read("/golden/" + name);
    }

    private static String read(String resource) {
        try (InputStream stream = Cassettes.class.getResourceAsStream(resource)) {
            if (stream == null) {
                throw new IllegalStateException("Missing test resource " + resource);
            }
            return new String(stream.readAllBytes(), StandardCharsets.UTF_8);
        } catch (IOException e) {
            throw new UncheckedIOException(e);
        }
    }
}
