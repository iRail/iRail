package be.irail.api.dto;

import java.util.regex.Pattern;

/**
 * Represents a hyperlink associated with a message.
 * Includes logic to detect the language of the linked content based on the URL.
 */
public class MessageLink {
    private final String link;
    private final String text;

    /**
     * Constructs a new MessageLink.
     *
     * @param link the URL of the link
     * @param text the display text for the link
     */
    public MessageLink(String link, String text) {
        this.link = link;
        this.text = text;
    }

    /**
     * Gets the URL of the link.
     * @return the link URL
     */
    public String getLink() {
        return this.link;
    }

    /**
     * Gets the display text for the link.
     * @return the link text
     */
    public String getText() {
        return this.text;
    }

    /**
     * Attempts to determine the language of the linked content based on its URL.
     *
     * @return the ISO language code (e.g., "nl", "fr"), or null if not detectable
     */
    public String getLanguage() {
        if (this.link.contains("//www.belgiantrain.be/nl")) {
            return "nl";
        }
        if (Pattern.compile("/www.belgianrail.be/jp/download/brail_him/\\d+_NL").matcher(this.link).find()) {
            return "nl";
        }
        if (this.link.contains("//www.belgiantrain.be/fr")) {
            return "fr";
        }
        if (Pattern.compile("/www.belgianrail.be/jp/download/brail_him/\\d+_FR").matcher(this.link).find()) {
            return "fr";
        }
        return null;
    }
}
