package be.irail.api.riv;

public enum NmbsRivApiTransportTypeFilter {
    TYPE_TRANSPORT_BITCODE_ALL(511),
    TYPE_TRANSPORT_BITCODE_NO_INTERNATIONAL_TRAINS(94),
    TYPE_TRANSPORT_BITCODE_ONLY_TRAINS(95);

    private final int bitcode;

    NmbsRivApiTransportTypeFilter(int bitcode) {
        this.bitcode = bitcode;
    }

    public int getBitcode() {
        return bitcode;
    }

    public static NmbsRivApiTransportTypeFilter forTypeOfTransportFilter(String fromStationId, String toStationId, TypeOfTransportFilter typeOfTransportFilter) {
        if (typeOfTransportFilter == TypeOfTransportFilter.AUTOMATIC) {
            if (fromStationId.startsWith("0088") && toStationId.startsWith("0088")) {
                return TYPE_TRANSPORT_BITCODE_NO_INTERNATIONAL_TRAINS;
            } else {
                return TYPE_TRANSPORT_BITCODE_ONLY_TRAINS;
            }
        } else if (typeOfTransportFilter == TypeOfTransportFilter.NO_INTERNATIONAL_TRAINS) {
            return TYPE_TRANSPORT_BITCODE_NO_INTERNATIONAL_TRAINS;
        } else if (typeOfTransportFilter == TypeOfTransportFilter.TRAINS) {
            return TYPE_TRANSPORT_BITCODE_ONLY_TRAINS;
        } else if (typeOfTransportFilter == TypeOfTransportFilter.ALL) {
            return TYPE_TRANSPORT_BITCODE_ALL;
        }
        return TYPE_TRANSPORT_BITCODE_ONLY_TRAINS;
    }
}
