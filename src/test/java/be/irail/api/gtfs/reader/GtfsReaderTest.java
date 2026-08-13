package be.irail.api.gtfs.reader;

import org.junit.jupiter.api.Test;
import org.junit.jupiter.api.io.TempDir;

import java.io.IOException;
import java.nio.charset.StandardCharsets;
import java.nio.file.Files;
import java.nio.file.Path;
import java.time.LocalDate;
import java.time.format.DateTimeFormatter;
import java.util.zip.ZipEntry;
import java.util.zip.ZipOutputStream;

import static org.junit.jupiter.api.Assertions.assertEquals;

class GtfsReaderTest {

    @TempDir
    Path tempDir;

    @Test
    void readsNamespacedServiceIds() throws IOException {
        String serviceId = "gc:nmbssncb:004359";
        String serviceDate = LocalDate.now().format(DateTimeFormatter.BASIC_ISO_DATE);
        Path feed = tempDir.resolve("feed.zip");

        try (ZipOutputStream zip = new ZipOutputStream(Files.newOutputStream(feed))) {
            addFile(zip, "agency.txt", """
                    agency_id,agency_name,agency_url,agency_timezone
                    nmbs,NMBS,https://www.belgiantrain.be,Europe/Brussels
                    """);
            addFile(zip, "routes.txt", """
                    route_id,agency_id,route_short_name,route_long_name,route_type
                    route,nmbs,IC,InterCity,2
                    """);
            addFile(zip, "trips.txt", """
                    route_id,service_id,trip_id,trip_headsign,trip_short_name
                    route,%s,trip,Brussels,1234
                    """.formatted(serviceId));
            addFile(zip, "stops.txt", """
                    stop_id,stop_name,stop_lat,stop_lon
                    stop,Brussels,50.845,4.357
                    """);
            addFile(zip, "stop_times.txt", """
                    trip_id,arrival_time,departure_time,stop_id,stop_sequence
                    trip,12:00:00,12:00:00,stop,1
                    """);
            addFile(zip, "calendar_dates.txt", """
                    service_id,date,exception_type
                    %s,%s,1
                    """.formatted(serviceId, serviceDate));
            addFile(zip, "feed_info.txt", """
                    feed_publisher_name,feed_publisher_url,feed_lang
                    NMBS,https://www.belgiantrain.be,en
                    """);
            addFile(zip, "translations.txt", """
                    table_name,field_name,language,translation,record_id,record_sub_id,field_value
                    """);
        }

        GtfsReader.GtfsData data = new GtfsReader(feed.toUri().toString(), 0, 0).readGtfs();

        assertEquals(serviceId, data.calendarDates().getFirst().serviceId());
        assertEquals(serviceId, data.trips().getFirst().serviceId());
    }

    private void addFile(ZipOutputStream zip, String name, String content) throws IOException {
        zip.putNextEntry(new ZipEntry(name));
        zip.write(content.getBytes(StandardCharsets.UTF_8));
        zip.closeEntry();
    }
}
