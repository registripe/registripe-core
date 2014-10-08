<?php

class RegistrableEventTest extends SapphireTest{
	
	public function testCMSFields() {
		$this->markTestIncomplete("Test CMS Fields");
	}

	function testCanRegister(){
		$event = new RegistrableEvent();
		$this->assertFalse($event->canRegister(), "No tickets available yet");
	}

}