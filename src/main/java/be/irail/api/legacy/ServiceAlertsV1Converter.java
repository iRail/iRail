package be.irail.api.legacy;

import be.irail.api.dto.Message;
import be.irail.api.dto.MessageLink;
import be.irail.api.dto.MessageType;
import be.irail.api.dto.result.ServiceAlertsResult;

/**
 * Converts ServiceAlertsResult to V1 DataRoot format for legacy API compatibility.
 */
public class ServiceAlertsV1Converter extends V1Converter {

    /**
     * Converts a service alerts result to the V1 DataRoot format.
     *
     * @param serviceAlerts the service alerts result
     * @return the DataRoot for V1 output
     */
    public static DataRoot convert(ServiceAlertsResult serviceAlerts) {
        DataRoot result = new DataRoot("disturbances");
        result.disturbance = serviceAlerts.getAlerts().stream()
                .map(ServiceAlertsV1Converter::convertDisturbance)
                .toArray();
        return result;
    }

    private static V1Disturbance convertDisturbance(Message alert) {
        V1Disturbance disturbance = new V1Disturbance();
        disturbance.title = alert.getHeader();
        disturbance.description = stripTags(alert.getMessage());
        disturbance.type = alert.getType() == MessageType.WORKS ? "planned" : "disturbance";
        disturbance.link = alert.getLinks() != null && !alert.getLinks().isEmpty() 
                ? alert.getLinks().getFirst().getLink() : "";
        disturbance.timestamp = alert.getLastModified() != null 
                ? alert.getLastModified().toEpochSecond() : 0;
        disturbance.richtext = alert.getMessage();
        disturbance.descriptionLink = alert.getLinks() != null 
                ? alert.getLinks().stream()
                        .map(ServiceAlertsV1Converter::convertDisturbanceLink)
                        .toArray(V1DescriptionLink[]::new)
                : new V1DescriptionLink[0];
        return disturbance;
    }

    private static V1DescriptionLink convertDisturbanceLink(MessageLink link) {
        V1DescriptionLink result = new V1DescriptionLink();
        result.link = link.getLink();
        result.text = link.getText();
        return result;
    }

    private static String stripTags(String html) {
        if (html == null) return "";
        return html.replaceAll("<[^>]*>", "");
    }

    public static class V1Disturbance {
        public String title;
        public String description;
        public String type;
        public String link;
        public long timestamp;
        public String richtext;
        public V1DescriptionLink[] descriptionLink;
    }

    public static class V1DescriptionLink {
        public String link;
        public String text;
    }
}
