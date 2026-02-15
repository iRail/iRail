package be.irail.api;

import be.irail.api.config.HealthCheck;
import be.irail.api.config.Metrics;
import io.dropwizard.metrics.servlets.HealthCheckServlet;
import io.dropwizard.metrics.servlets.MetricsServlet;
import org.apache.logging.log4j.LogManager;
import org.apache.logging.log4j.Logger;
import org.springframework.boot.SpringApplication;
import org.springframework.boot.autoconfigure.SpringBootApplication;
import org.springframework.boot.web.servlet.ServletRegistrationBean;
import org.springframework.context.annotation.Bean;
import org.springframework.scheduling.annotation.EnableScheduling;

@SpringBootApplication
@EnableScheduling
public class IrailJavaApplication {
    private static final Logger log = LogManager.getLogger(IrailJavaApplication.class);

    public static void main(String[] args) {
        SpringApplication.run(IrailJavaApplication.class, args);
    }

    @Bean
    public ServletRegistrationBean metricsServletRegistrationBean() {
        log.info("registering metrics servlet");
        MetricsServlet metricsServlet = new MetricsServlet(Metrics.getRegistry());
        return new ServletRegistrationBean(metricsServlet, "/metrics/*");
    }

    @Bean
    public ServletRegistrationBean healthCheckServletRegistrationBean() {
        log.info("registering metrics servlet");
        HealthCheckServlet healthCheckServlet = new HealthCheckServlet(HealthCheck.HEALTHCHECKREGISTRY);
        return new ServletRegistrationBean(healthCheckServlet, "/healthcheck/*");
    }

}
