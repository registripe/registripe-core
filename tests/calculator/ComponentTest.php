<?php

namespace EventRegistration\Calculator\Tests;

use \SapphireTest;
use EventRegistration\Calculator;
use EventRegistration\Calculator\AbstractComponent;

class ComponentTest extends SapphireTest
{
	
	protected static $fixture_file = array(
		'../fixtures/Standard.yml',
		'../fixtures/Empty.yml'
	);

	public function setUp() {
		parent::setUp();
		$this->emptyReg = $this->objFromFixture('EventRegistration', 'empty');
		$this->singleReg = $this->objFromFixture('EventRegistration', 'single');
		$this->multipleReg = $this->objFromFixture('EventRegistration', 'multiple');
	}

	public function testCustomCalculatorComponent() {
		$calculator = new Calculator(array(
			new CustomComponent()
		)); 
		$this->assertEquals(6789, $calculator->calculate($this->emptyReg), 'should be fixed output');
		$this->assertEquals(6789, $calculator->calculate($this->singleReg), 'should be fixed output');
		$this->assertEquals(6789, $calculator->calculate($this->multipleReg), 'should be fixed output');
	}

}

use \EventAttendee;
use \EventRegistration;

class CustomComponent extends AbstractComponent {

	public function calculateAttendee(EventAttendee $attendee, EventRegistration $registration, $running) {
		return 12345;
	}

	public function calculateRegistration(EventRegistration $registration, $running) {
		return 6789;
	}

}