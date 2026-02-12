package be.irail.api.controllers;

import be.irail.api.db.OccupancyDao;
import be.irail.api.db.OccupancyReport;
import be.irail.api.dto.request.OccupancyReportRequestDTO;
import be.irail.api.exception.request.BadRequestException;
import jakarta.ws.rs.*;
import jakarta.ws.rs.core.MediaType;
import jakarta.ws.rs.core.Response;
import org.springframework.stereotype.Component;

import java.time.LocalDate;
import java.time.format.DateTimeFormatter;

/**
 * Controller for handling occupancy reports.
 * Ported from OccupancyController.php.
 */
@Component
@Path("/occupancy")
public class OccupancyReportController {

    private final OccupancyDao occupancyDao;

    public OccupancyReportController(OccupancyDao occupancyDao) {
        this.occupancyDao = occupancyDao;
    }

    /**
     * Stores a new occupancy report.
     *
     * @param request the report data
     * @return the stored report
     */
    @POST
    @Consumes(MediaType.APPLICATION_JSON)
    @Produces(MediaType.APPLICATION_JSON)
    public Response store(OccupancyReportRequestDTO request) {
        if (request == null) {
            throw new BadRequestException("Missing request body", "body");
        }

        validateRequest(request);

        OccupancyReport report = new OccupancyReport();
        report.setVehicleId(extractId(request.getVehicle()));
        report.setStopId(extractStopId(request.getFrom()));
        report.setJourneyStartDate(LocalDate.parse(request.getDate(), DateTimeFormatter.ofPattern("yyyyMMdd")));
        report.setOccupancy(mapOccupancyLevel(request.getOccupancy()));
        report.setSource(OccupancyReport.OccupancyReportSource.SPITSGIDS);

        occupancyDao.handleReport(report);

        return Response.ok(report).build();
    }

    private void validateRequest(OccupancyReportRequestDTO request) {
        if (request.getVehicle() == null) throw new BadRequestException("Missing vehicle parameter", "vehicle");
        if (request.getFrom() == null) throw new BadRequestException("Missing from parameter", "from");
        if (request.getDate() == null) throw new BadRequestException("Missing date parameter", "date");
        if (request.getOccupancy() == null) throw new BadRequestException("Missing occupancy parameter", "occupancy");

        try {
            LocalDate.parse(request.getDate(), DateTimeFormatter.ofPattern("yyyyMMdd"));
        } catch (Exception e) {
            throw new BadRequestException("Invalid date format, should be YYYYMMDD", "date", request.getDate());
        }
    }

    private String extractId(String uri) {
        if (uri == null) return null;
        return uri.substring(uri.lastIndexOf('/') + 1);
    }

    private Integer extractStopId(String stationUri) {
        String idStr = extractId(stationUri);
        try {
            return Integer.parseInt(idStr.replaceAll("\\D+", ""));
        } catch (NumberFormatException e) {
            throw new BadRequestException("Invalid station ID", "from", stationUri);
        }
    }

    private OccupancyReport.OccupancyLevel mapOccupancyLevel(String uri) {
        if (uri == null) return null;
        return switch (uri) {
            case "http://api.irail.be/terms/low" -> OccupancyReport.OccupancyLevel.LOW;
            case "http://api.irail.be/terms/medium" -> OccupancyReport.OccupancyLevel.MEDIUM;
            case "http://api.irail.be/terms/high" -> OccupancyReport.OccupancyLevel.HIGH;
            default -> throw new BadRequestException("Unknown occupancy value", "occupancy", uri);
        };
    }
}
