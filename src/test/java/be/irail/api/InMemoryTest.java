package be.irail.api;

import be.irail.api.gtfs.reader.GtfsRtUpdater;
import be.irail.api.gtfs.reader.GtfsUpdater;
import com.opencsv.CSVReader;
import org.junit.jupiter.api.BeforeAll;
import org.junit.jupiter.api.TestInstance;
import org.springframework.boot.test.context.SpringBootTest;
import org.springframework.test.context.ActiveProfiles;
import org.springframework.test.context.bean.override.mockito.MockitoBean;

import javax.sql.DataSource;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.nio.charset.StandardCharsets;
import java.sql.Connection;
import java.sql.PreparedStatement;

@SpringBootTest
@ActiveProfiles("test")
@TestInstance(TestInstance.Lifecycle.PER_CLASS)
public abstract class InMemoryTest {

    private final DataSource dataSource;
    @MockitoBean
    private GtfsUpdater gtfsUpdater;
    @MockitoBean
    private GtfsRtUpdater gtfsRtUpdater;

    protected InMemoryTest(DataSource dataSource) {
        this.dataSource = dataSource;
    }

    private static String emptyToNull(String value) {
        return value == null || value.isEmpty() ? null : value;
    }

    @BeforeAll
    void loadStations() throws Exception {
        String sql = """
                INSERT INTO stations (uri, name, alternative_fr, alternative_nl, alternative_de,
                        alternative_en, taf_tap_code, telegraph_code, country_code, longitude,
                        latitude, avg_stop_times, official_transfer_time)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)""";

        try (Connection connection = dataSource.getConnection();
             InputStream is = InMemoryTest.class.getResourceAsStream("/stations.csv");
             CSVReader csvReader = new CSVReader(new InputStreamReader(is, StandardCharsets.UTF_8));
             PreparedStatement ps = connection.prepareStatement(sql)) {

            String[] fields;
            while ((fields = csvReader.readNext()) != null) {
                if (fields.length < 13) {
                    continue;
                }

                ps.setString(1, fields[0]);                                          // uri
                ps.setString(2, fields[1]);                                          // name
                ps.setString(3, emptyToNull(fields[2]));                             // alternative_fr
                ps.setString(4, emptyToNull(fields[3]));                             // alternative_nl
                ps.setString(5, emptyToNull(fields[4]));                             // alternative_de
                ps.setString(6, emptyToNull(fields[5]));                             // alternative_en
                ps.setString(7, emptyToNull(fields[6]));                             // taf_tap_code
                ps.setString(8, emptyToNull(fields[7]));                             // telegraph_code
                ps.setString(9, emptyToNull(fields[8]));                             // country_code
                ps.setDouble(10, Double.parseDouble(fields[9]));                     // longitude
                ps.setDouble(11, Double.parseDouble(fields[10]));                    // latitude
                ps.setObject(12, fields[11].isEmpty() ? null :
                        Double.parseDouble(fields[11]));                             // avg_stop_times
                ps.setObject(13, fields[12].isEmpty() ? null :
                        Integer.parseInt(fields[12]));                               // official_transfer_time
                ps.addBatch();
            }
            ps.executeBatch();
        }
    }

}
