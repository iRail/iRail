package be.irail.api.exception.notfound;

public class JourneyPlanNotFoundException extends IrailNotFoundException {
    public JourneyPlanNotFoundException() {
        super(404, "Journey planner did not return any result");
    }
}
