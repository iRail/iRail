package be.irail.api.riv;

import be.irail.api.dto.Language;
import be.irail.api.dto.Message;
import be.irail.api.dto.MessageLink;
import be.irail.api.dto.MessageType;
import be.irail.api.dto.result.ServiceAlertsResult;
import be.irail.api.exception.upstream.UpstreamServerException;
import be.irail.api.riv.requests.ServiceAlertsRequest;
import org.springframework.stereotype.Service;
import org.w3c.dom.Document;
import org.w3c.dom.Element;
import org.w3c.dom.NodeList;

import javax.xml.parsers.DocumentBuilder;
import javax.xml.parsers.DocumentBuilderFactory;
import java.io.ByteArrayInputStream;
import java.io.IOException;
import java.net.URI;
import java.net.http.HttpClient;
import java.net.http.HttpRequest;
import java.net.http.HttpResponse;
import java.nio.charset.StandardCharsets;
import java.time.OffsetDateTime;
import java.time.format.DateTimeFormatter;
import java.util.ArrayList;
import java.util.List;
import java.util.Map;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

/**
 * Client for fetching and parsing NMBS RSS Disturbances (Service Alerts).
 */
@Service
public class NmbsRssDisturbancesClient {

    private final HttpClient httpClient;

    private record LinkExtractionResult(String filteredDescription, List<MessageLink> links) {
    }

    private final NmbsRivRawDataRepository rivDataRepository;

    private static final Map<Language, String> READ_MORE_STRINGS = Map.of(
            Language.NL, "Lees meer",
            Language.FR, "Lire plus",
            Language.EN, "Read more",
            Language.DE, "Weiterlesen"
    );

    public NmbsRssDisturbancesClient(NmbsRivRawDataRepository rivDataRepository) {
        this.rivDataRepository = rivDataRepository;
        this.httpClient = HttpClient.newBuilder()
                .followRedirects(HttpClient.Redirect.NORMAL)
                .build();
    }

    public ServiceAlertsResult getServiceAlerts(ServiceAlertsRequest request) {
        String xml = fetchData(request);
        List<Message> alerts = parseData(xml, request);
        return new ServiceAlertsResult(alerts);
    }

    private String fetchData(ServiceAlertsRequest request) {
        String url = "http://www.belgianrail.be/jp/sncb-nmbs-routeplanner/help.exe/"
                + request.language().getValue().toLowerCase()
                + "?tpl=rss_feed";
        HttpRequest.Builder builder = HttpRequest.newBuilder()
                .uri(URI.create(url))
                .header("Accept", "application/json");
        HttpRequest httpRequest = builder.build();
        String xml;
        try {
            HttpResponse<String> response = httpClient.send(httpRequest, HttpResponse.BodyHandlers.ofString());
            xml = response.body();
        } catch (IOException | InterruptedException e) {
            throw new UpstreamServerException("Failed to fetch data from NMBS", e);
        }
        return xml;
    }

    private List<Message> parseData(String xml, ServiceAlertsRequest request) {
        List<Message> disturbances = new ArrayList<>();
        try {
            // Very basic XML cleaning as in PHP's Tidy::repairXml (best effort)
            xml = xml.replace("/>>", "/>");

            DocumentBuilderFactory factory = DocumentBuilderFactory.newInstance();
            DocumentBuilder builder = factory.newDocumentBuilder();
            Document doc = builder.parse(new ByteArrayInputStream(xml.getBytes(StandardCharsets.UTF_8)));

            NodeList items = doc.getElementsByTagName("item");
            for (int i = 0; i < items.getLength(); i++) {
                Element item = (Element) items.item(i);

                String title = getTagValue(item, "title").trim();
                String description = getTagValue(item, "description").trim();
                String link = getTagValue(item, "link").trim();
                String pubDate = getTagValue(item, "pubDate").trim();

                String lead = description.split("\\.")[0];

                LinkExtractionResult linkExtractionResult = extractLinks(description, link, request.language());
                String cleanedDescription = cleanHtmlText(linkExtractionResult.filteredDescription, "\n");

                // RFC 1123 date format is common in RSS
                OffsetDateTime timestamp = OffsetDateTime.parse(pubDate, DateTimeFormatter.RFC_1123_DATE_TIME);

                MessageType type = MessageType.TROUBLE;
                if (link.contains("tplParamHimMsgInfoGroup=works")) {
                    type = MessageType.WORKS;
                }

                String id = "0";
                Pattern p = Pattern.compile("messageID=(\\d+)");
                Matcher m = p.matcher(link);
                if (m.find()) {
                    id = m.group(1);
                }

                disturbances.add(new Message(
                        id,
                        timestamp,
                        null,
                        timestamp,
                        type,
                        title,
                        lead,
                        cleanedDescription,
                        "NMBS/SNCB",
                        linkExtractionResult.links
                ));
            }
        } catch (Exception e) {
            // In a real scenario, we would use backup cache here
            throw new RuntimeException("Failed to parse NMBS RSS disturbances", e);
        }

        return disturbances;
    }

    private String getTagValue(Element element, String tagName) {
        NodeList list = element.getElementsByTagName(tagName);
        if (list != null && list.getLength() > 0) {
            return list.item(0).getTextContent();
        }
        return "";
    }

    private String cleanHtmlText(String description, String newlineChar) {
        if (description == null) {
            return "";
        }

        description = description.replace("\u00A0", " ");
        description = description.replaceAll("<br\\s*/?>", "%%NEWLINE%%");

        // Strip tags
        description = description.replaceAll("<[^>]*>", "");
        description = description.replaceAll("\\s+", " ");
        description = description.replace("%%NEWLINE%%", newlineChar);

        return description.trim();
    }

    private LinkExtractionResult extractLinks(String description, String itemLink, Language language) {
        List<MessageLink> links = new ArrayList<>();
        String modifiedDescription = description;

        Pattern p = Pattern.compile("<a href=\"([^\"]+)\"[^>]*>([^<]+)</a>");
        Matcher m = p.matcher(description);
        boolean found = false;
        while (m.find()) {
            links.add(new MessageLink(m.group(1), m.group(2)));
            found = true;
        }

        if (found) {
            // Remove download links as in PHP
            modifiedDescription = description.replaceAll("<a href=\"http://www\\.belgianrail\\.be/jp/download/brail_him/[^\"]+\">[^<]+</a>", "");
        } else {
            String linkText = READ_MORE_STRINGS.getOrDefault(language, "Read more");
            links.add(new MessageLink(itemLink, linkText));
        }

        return new LinkExtractionResult(modifiedDescription, links);
    }
}
