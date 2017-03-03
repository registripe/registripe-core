<?php

namespace EventRegistration\Calculator\Tests;

class CalculatorTest extends \SapphireTest
{

	protected static $fixture_file = array(
		'../fixtures/Standard.yml',
		'../fixtures/Empty.yml'
	);

	// helper for asserting calculator results
	protected function assertCalculation($reg, $expected, $message = "") {
		$this->calculator = new \EventRegistration\Calculator($reg);
		$this->assertEquals($expected, $this->calculator->calculate(), $message);
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
