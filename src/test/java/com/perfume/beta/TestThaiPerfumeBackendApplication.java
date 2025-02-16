package com.perfume.beta;

import org.springframework.boot.SpringApplication;

public class TestThaiPerfumeBackendApplication {

	public static void main(String[] args) {
		SpringApplication.from(ThaiPerfumeBackendApplication::main).with(TestcontainersConfiguration.class).run(args);
	}

}
