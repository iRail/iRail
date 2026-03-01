package be.irail.api.gtfs.reader.models;

public enum PickupDropoffType {
    SCHEDULED(0),
    NONE(1),
    PHONE_TO_ARRANGE(2),
    COORDINATE_WITH_DRIVER(3);


    private final int code;

    PickupDropoffType(int code) {
        this.code = code;
    }

    public static PickupDropoffType fromCode(int code) {
        for (PickupDropoffType type : values()) {
            if (type.code == code) {
                return type;
            }
        }
        return null;
    }
}
