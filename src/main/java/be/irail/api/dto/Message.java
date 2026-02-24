package be.irail.api.dto;

import java.time.OffsetDateTime;
import java.util.List;

/**
 * Represents a service alert or informational message.
 * Provides details about the message validity, content (with HTML stripping capabilities), and links.
 */
public class Message {
    private final String id;
    private final OffsetDateTime validFrom;
    private final OffsetDateTime validUpTo;
    private final OffsetDateTime lastModified;
    private final String header;
    private final String leadText;
    private final String message;
    private final String publisher;
    private final List<MessageLink> links;
    private final MessageType type;

    /**
     * Constructs a new Message with all its properties.
     *
     * @param id           the unique message identifier
     * @param validFrom    the datetime when this message becomes visible/active
     * @param validUpTo    the datetime until when this message is visible/active
     * @param lastModified the datetime when this message was last updated
     * @param type         the category of the message
     * @param header       the header text
     * @param leadText     the lead text
     * @param message      the complete message text (may contain HTML)
     * @param publisher    the name of the organisation who published this message
     * @param links        a list of associated links
     */
    public Message(
        String id,
        OffsetDateTime validFrom,
        OffsetDateTime validUpTo,
        OffsetDateTime lastModified,
        MessageType type,
        String header,
        String leadText,
        String message,
        String publisher,
        List<MessageLink> links
    ) {
        this.id = id;
        this.validFrom = validFrom;
        this.validUpTo = validUpTo;
        this.lastModified = lastModified;
        this.header = header;
        this.leadText = leadText;
        this.message = message;
        this.publisher = publisher;
        this.links = links;
        this.type = type;
    }

    /**
     * Gets the unique identifier of the message.
     * @return the message ID
     */
    public String getId() {
        return this.id;
    }

    /**
     * Gets the start of the validity period.
     * @return the valid from datetime
     */
    public OffsetDateTime getValidFrom() {
        return this.validFrom;
    }

    /**
     * Gets the end of the validity period.
     * @return the valid up to datetime
     */
    public OffsetDateTime getValidUpTo() {
        return this.validUpTo;
    }

    /**
     * Gets the last modification time of the message.
     * @return the last modified datetime
     */
    public OffsetDateTime getLastModified() {
        return this.lastModified;
    }

    /**
     * Gets the message header, stripped of HTML tags.
     * @return the stripped header text
     */
    public String getHeader() {
        return stripTags(this.header);
    }

    /**
     * Gets the lead text, stripped of HTML tags.
     * @return the stripped lead text
     */
    public String getLeadText() {
        return stripTags(this.leadText);
    }

    /**
     * Gets the raw message content, potentially containing HTML.
     * @return the raw message text
     */
    public String getMessage() {
        return this.message;
    }

    /**
     * Gets the message content stripped of links and HTML tags.
     * Newlines are replaced with spaces.
     * @return the cleanly stripped message text
     */
    public String getStrippedMessage() {
        String messageWithoutLinks = this.message.replaceAll("<a href=\".*?\">.*?</a>", "");
        // Replace newline characters with a space to ensure no whitespace is removed.
        String stripped = stripTags(messageWithoutLinks.replace("<br>", " "));
        stripped = stripped.replace("  ", " "); // Remove double spaces
        return stripped.trim();
    }

    /**
     * Gets the list of links associated with this message.
     * @return the list of message links
     */
    public List<MessageLink> getLinks() {
        return this.links;
    }

    /**
     * Gets the type/category of the message.
     * @return the message type
     */
    public MessageType getType() {
        return this.type;
    }

    /**
     * Utility method to strip HTML tags from a string.
     * @param input the string to strip
     * @return the stripped string
     */
    private String stripTags(String input) {
        if (input == null) {
            return null;
        }
        return input.replaceAll("<[^>]*>", "");
    }
}
