package com.perfume.beta;

import org.junit.jupiter.api.Test;
import org.springframework.boot.test.context.SpringBootTest;
import org.springframework.context.annotation.Import;

@Import(TestcontainersConfiguration.class)
@SpringBootTest
class ThaiPerfumeBackendApplicationTests {

	@Test
	void contextLoads() {
	}

}
