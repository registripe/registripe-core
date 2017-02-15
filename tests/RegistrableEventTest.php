<?php

class RegistrableEventTest extends FunctionalTest{
	
	protected static $fixture_file = 'fixtures/EventManagement.yml';

	public function setUp() {
		parent::setUp();
		$this->objFromFixture('Calendar', 'calendar')->publish('Stage', 'Live');
		$this->event = $this->objFromFixture('RegistrableEvent', 'event');
		$this->event->publish('Stage', 'Live');
	}

	public function testVisitEventPage(){
		$page = $this->get('calendar/test-event');
		$this->assertEquals(200, $page->getStatusCode());
	}

	public function testCMSFields() {
		$this->markTestIncomplete("Test CMS Fields");
	}

}