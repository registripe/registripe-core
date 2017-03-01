<?php

namespace EventRegistration\Calculator\Tests;

class CalculatorTest extends \SapphireTest
{

	protected static $fixture_file = array(
		'../fixtures/Standard.yml',
		'../fixtures/Empty.yml'
	);

	public function setUp() {
		parent::setUp();
		$this->calculator = new \EventRegistration\Calculator(); // defaults to cost calculator
	}

	// helper for asserting calculator results
	protected function assertCalculation($reg, $expected, $message = "") {
		$this->assertEquals($expected, $this->calculator->calculate($reg), $message);
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
