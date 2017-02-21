<?php

class EventRegistrationCostCalculatorTest extends SapphireTest
{

	protected static $fixture_file = array(
		'fixtures/Standard.yml',
		'fixtures/Empty.yml'
	);

	// helper for asserting calculator results
	protected function assertCalculation($reg, $expected, $message = "") {
		$calculator = new EventRegistrationCostCalculator($reg);
		$this->assertEquals($expected, $calculator->calculate(), $message);
	}

	public function testEmpty() {
		$reg = $this->objFromFixture('EventRegistration', 'empty');
		$this->assertCalculation($reg, 0);
	}

	public function testTicketTypes() {
		$reg = $this->objFromFixture('EventRegistration', 'single');
		$this->assertCalculation($reg, 10);

		$reg = $this->objFromFixture('EventRegistration', 'multiple');
		$this->assertCalculation($reg, 20);
	}

}