package be.irail.api.gtfs;

import be.irail.api.db.OccupancyDao;
import be.irail.api.db.Station;
import be.irail.api.db.StationsDao;
import be.irail.api.dto.*;
import be.irail.api.dto.result.LiveboardSearchResult;
import be.irail.api.gtfs.dao.GtfsInMemoryDao;
import be.irail.api.gtfs.dao.GtfsRtInMemoryDao;
import be.irail.api.gtfs.reader.models.GtfsRtUpdate;
import be.irail.api.gtfs.reader.models.PickupDropoffType;
import be.irail.api.gtfs.reader.models.Stop;
import be.irail.api.riv.requests.LiveboardRequest;
import org.apache.logging.log4j.LogManager;
import org.apache.logging.log4j.Logger;
import org.springframework.stereotype.Service;

import java.time.LocalDateTime;
import java.util.ArrayList;
import java.util.List;
import java.util.Map;

/**
 * Client for fetching and parsing NMBS liveboard data from their GTFS feed.
 */
@Service
public class GtfsLiveboardClient {
    private static final Logger log = LogManager.getLogger(GtfsLiveboardClient.class);

    private final StationsDao stationsDao;
    private final OccupancyDao occupancyDao;

    public GtfsLiveboardClient(
            StationsDao stationsDao,
            OccupancyDao occupancyDao
    ) {
        this.stationsDao = stationsDao;
        this.occupancyDao = occupancyDao;
    }

    public LiveboardSearchResult getLiveboard(LiveboardRequest request) {
        GtfsInMemoryDao staticDao = GtfsInMemoryDao.getInstance();
        GtfsRtInMemoryDao rtDao = GtfsRtInMemoryDao.getInstance();
        List<GtfsInMemoryDao.CallAtStop> calls = staticDao.getCallsAtStop(request.station().getHafasId(), request.dateTime(), request.dateTime().plusHours(1), request.timeSelection() == TimeSelection.DEPARTURE);
        Map<String, GtfsRtUpdate> rtUpdates = rtDao.getUpdatesByHafasStopId(request.station().getHafasId());
        List<DepartureOrArrival> results = new ArrayList<>();

        Station station = request.station();
        StationDto stationDto = station.toDto(request.language());

        for (GtfsInMemoryDao.CallAtStop call : calls) {
            if (request.timeSelection() == TimeSelection.DEPARTURE && call.stopTime().pickupType() != PickupDropoffType.SCHEDULED
                    || request.timeSelection() == TimeSelection.ARRIVAL && call.stopTime().dropOffType() != PickupDropoffType.SCHEDULED) {
                continue;
            }
            Stop platform = call.platform();
            String parentStationId = platform.parentStation();

            DepartureOrArrival departureOrArrival = new DepartureOrArrival();
            departureOrArrival.setStation(stationDto);
            departureOrArrival.setPlatform(new PlatformInfo(parentStationId, platform.platformCode(), false));
            departureOrArrival.setIsCancelled(false);
            Vehicle vehicle = Vehicle.fromTypeAndNumber(call.route().shortName(), call.trip().shortName(), call.startDate());
            departureOrArrival.setVehicle(vehicle);
            if (request.timeSelection() == TimeSelection.DEPARTURE) {
                departureOrArrival.setScheduledDateTime(call.stopTime().getDepartureTime(call.startDate()));
                StationDto destinationStation = stationDto(call.destinationParentStop(), request.language());
                vehicle.setDirection(new VehicleDirection(call.trip().headsign(), destinationStation));
                departureOrArrival.setOccupancy(occupancyDao.getOccupancy(departureOrArrival));
            } else {
                departureOrArrival.setScheduledDateTime(call.stopTime().getArrivalTime(call.startDate()));
                StationDto originStation = stationDto(call.originParentStop(), request.language());
                vehicle.setDirection(new VehicleDirection(originStation.getLocalizedStationName(), originStation));
            }

            GtfsRtUpdate rtUpdate = rtUpdates.getOrDefault(call.trip().id(), null);
            LocalDateTime now = LocalDateTime.now();
            if (rtUpdate != null
                    && GtfsRtInMemoryDao.isOverlayUsable(rtUpdate.timestamp(), departureOrArrival.getScheduledDateTime(), now)) {
                departureOrArrival.setDelay(request.timeSelection() == TimeSelection.DEPARTURE ? rtUpdate.departureDelay() : rtUpdate.arrivalDelay());

                if (departureOrArrival.getScheduledDateTime().plusSeconds(departureOrArrival.getDelay()).isBefore(now)) {
                    departureOrArrival.setStatus(DepartureArrivalState.REPORTED);
                }

                departureOrArrival.setIsCancelled(rtUpdate.cancelled());
                if (!rtUpdate.stopId().equals(platform.id())) {
                    Stop newPlatform = staticDao.getStop(rtUpdate.stopId());
                    departureOrArrival.setPlatform(new PlatformInfo(rtUpdate.parentStopId(),
                            newPlatform != null ? newPlatform.platformCode() : null, true));
                }
            }
            results.add(departureOrArrival);
        }

        return new LiveboardSearchResult(stationDto, results);
    }

    private StationDto stationDto(Stop gtfsStop, Language language) {
        String hafasId = gtfsStop.getHafasId();
        Station station = hafasId == null ? null : stationsDao.getStationFromId(hafasId);
        if (station != null) {
            return station.toDto(language);
        }
        log.warn("Unknown station {} in station database, using GTFS data", gtfsStop.id());
        String id = hafasId == null ? gtfsStop.id() : "00" + hafasId;
        String uri = hafasId == null ? null : "http://irail.be/stations/NMBS/" + id;
        return new StationDto(id, uri, gtfsStop.name(), gtfsStop.getName(language), gtfsStop.lon(), gtfsStop.lat());
    }

}
