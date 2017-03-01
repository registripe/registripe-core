<?php


class EventRegistrationCalculatorComponentTest extends SapphireTest {
	

	protected static $fixture_file = array(
		'fixtures/Standard.yml',
		'fixtures/Empty.yml'
	);

	public function setUp() {
		parent::setUp();
		$this->emptyReg = $this->objFromFixture('EventRegistration', 'empty');
		$this->singleReg = $this->objFromFixture('EventRegistration', 'single');
		$this->multipleReg = $this->objFromFixture('EventRegistration', 'multiple');
	}

	public function testCustomCalculatorComponent() {
		$calculator = new EventRegistrationCalculator(array(
			new EventRegistrationCalculatorTestComponent()
		)); 
		$this->assertEquals(6789, $calculator->calculate($this->emptyReg), 'should be fixed output');
		$this->assertEquals(6789, $calculator->calculate($this->singleReg), 'should be fixed output');
		$this->assertEquals(6789, $calculator->calculate($this->multipleReg), 'should be fixed output');
	}

}

class EventRegistrationCalculatorTestComponent extends EventRegistrationCalculatorBaseComponent {

	public function calculateAttendee(EventAttendee $attendee, EventRegistration $registration, $running) {
		return 12345;
	}

	public function calculateRegistration(EventRegistration $registration, $running) {
		return 6789;
	}

}
