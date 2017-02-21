<?php

class EventRegistrationSessionTest extends SapphireTest{

	protected static $fixture_file = array(
		'fixtures/EventManagement.yml'
	);

	public function testSessionGetStartEnd() {
		$event = $this->objFromFixture("RegistrableEvent", "event");
		$session = new EventRegistrationSession($event);
		$this->assertNull($session->get(), "No session started");
		$reg = $session->start();
		$this->assertNotNull($reg = $session->get(), "Registration");
		$this->assertEquals($event->ID, $reg->EventID);
		$session->end();
		$this->assertNull($session->get(), "No session anymore");
	}

}