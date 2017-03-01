<?php

namespace EventRegistration\Tests;

class SessionTest extends \SapphireTest{

	protected static $fixture_file = array(
		'fixtures/EventManagement.yml'
	);

	public function testSessionGetStartEnd() {
		$event = $this->objFromFixture("RegistrableEvent", "event");
		$session = new \EventRegistration\Session($event);
		$this->assertNull($session->get(), "No session started");
		$reg = $session->start();
		$this->assertNotNull($reg = $session->get(), "Registration");
		$this->assertEquals($event->ID, $reg->EventID);
		$session->end();
		$this->assertNull($session->get(), "No session anymore");
	}

}