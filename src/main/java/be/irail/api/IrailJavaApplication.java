package be.irail.api;

import org.springframework.boot.SpringApplication;
import org.springframework.boot.autoconfigure.SpringBootApplication;
import org.springframework.scheduling.annotation.EnableScheduling;

@SpringBootApplication
@EnableScheduling
public class IrailJavaApplication {

    public static void main(String[] args) {
        SpringApplication.run(IrailJavaApplication.class, args);
    }

}
