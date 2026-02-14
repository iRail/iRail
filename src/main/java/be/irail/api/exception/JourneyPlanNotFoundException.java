package be.irail.api.exception;

public class JourneyPlanNotFoundException extends IrailHttpException {
    public JourneyPlanNotFoundException() {
        super(404, "Journey planner did not return any result");
    }
}
