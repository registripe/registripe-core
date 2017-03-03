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
		$calculator = new Calculator($this->emptyReg, array("Custom")); 
		$this->assertEquals(6789, $calculator->calculate(), 'should be fixed output');

		$calculator = new Calculator($this->singleReg, array("Custom")); 
		$this->assertEquals(6789, $calculator->calculate(), 'should be fixed output');

		$calculator = new Calculator($this->multipleReg, array("Custom")); 
		$this->assertEquals(6789, $calculator->calculate(), 'should be fixed output');
	}

}

namespace EventRegistration\Calculator;

use \EventAttendee;
use \EventRegistration;

class CustomComponent extends AbstractComponent {

	public function calculateAttendee(EventAttendee $attendee, $total) {
		return 12345;
	}

	public function calculateRegistration($total) {
		return 6789;
	}

}