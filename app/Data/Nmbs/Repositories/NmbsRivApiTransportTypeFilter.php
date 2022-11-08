<?php

namespace Irail\Data\Nmbs\Repositories;

enum NmbsRivApiTransportTypeFilter:int
{
  // Category codes:
    // Eurostar: "catIn": "003", "catCode": "0", "catOutS": "003", bit 2⁰
    // Thalys: "catIn": "001", "catCode": "0", "catOutS": "001",  bit 2⁰
    // IC, both national and international: "catIn": "007", "catCode": "2", "catOutS": "007", bit 2²
    // S: "catIn": "071", "catCode": "4", "catOutS": "071", bit 2⁴
    // P: "catIn": "044", "catCode": "2", "catOutS": "044",
    // L:  "catIn": "046", "catCode": "6", "catOutS": "046",

    // bitcodes for product selection:
    // national_express: bit 0 (Thalys, Eurostar, ...)
    // national: bit 1 (???)
    // regional_exp: bit 2 (IC, P)
    // regional: bit 3
    // suburban: bit 4 (S)
    // bus: bit 5
    // ferry: bit 6 (L ?!)
    // subway: bit 7
    // tram: bit 8
    // taxi: bit 9

    case TYPE_TRANSPORT_BITCODE_ALL = 511; // 0111111111 TODO: VERIFY
    case TYPE_TRANSPORT_BITCODE_NO_INTERNATIONAL_TRAINS = 94; // 0001011110 TODO: VERIFY
    case TYPE_TRANSPORT_BITCODE_ONLY_TRAINS = 95; // 0001011111 TODO: VERIFY
}
