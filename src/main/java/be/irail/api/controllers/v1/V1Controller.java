package be.irail.api.controllers.v1;

import be.irail.api.config.Metrics;
import be.irail.api.dto.Format;
import be.irail.api.legacy.DataRoot;
import be.irail.api.legacy.printer.V1JsonPrinter;
import be.irail.api.legacy.printer.V1XmlPrinter;
import com.codahale.metrics.Timer;
import jakarta.ws.rs.core.Response;
import org.jspecify.annotations.NonNull;

import java.time.LocalDate;
import java.time.LocalDateTime;
import java.time.LocalTime;
import java.time.format.DateTimeFormatter;

public abstract class V1Controller {
    private final Timer serializeV1Timer = Metrics.getRegistry().timer("serialize-v1");

    protected LocalDate parseDate(String date) {
        LocalDate localDate = LocalDate.now();

        if (date != null && !date.isEmpty()) {
            try {
                localDate = LocalDate.parse(date, DateTimeFormatter.ofPattern("ddMMyy"));
            } catch (Exception ignored) {
                // Use current date if parsing fails
            }
        }

        return localDate;
    }

    /**
     * Parses V1 date and time parameters into LocalDateTime.
     */
    protected LocalDateTime parseDateTime(String date, String time) {
        LocalDate localDate = LocalDate.now();
        LocalTime localTime = LocalTime.now().withSecond(0).withNano(0);

        if (date != null && !date.isEmpty()) {
            try {
                localDate = LocalDate.parse(date, DateTimeFormatter.ofPattern("ddMMyy"));
            } catch (Exception ignored) {
                // Use current date if parsing fails
            }
        }

        if (time != null && !time.isEmpty()) {
            try {
                localTime = LocalTime.parse(time, DateTimeFormatter.ofPattern("HHmm"));
            } catch (Exception ignored) {
                // Use current time if parsing fails
            }
        }

        return LocalDateTime.of(localDate, localTime);
    }

    protected Response v1Response(DataRoot dataRoot, Format outputFormat) {
        try (Timer.Context ignored = serializeV1Timer.time()) {
            // Serialize to output format
            String body = serializeOutput(dataRoot, outputFormat);
            String contentType = getContentType(outputFormat);
            return Response.ok(body, contentType).build();
        }
    }

    private static @NonNull String getContentType(Format outputFormat) {
        return outputFormat == Format.JSON
                ? "application/json;charset=UTF-8"
                : "application/xml;charset=UTF-8";
    }

    private static String serializeOutput(DataRoot dataRoot, Format format) {
        if (format == Format.JSON) {
            return new V1JsonPrinter(dataRoot).getBody();
        } else {
            return new V1XmlPrinter(dataRoot).getBody();
        }
    }

}
