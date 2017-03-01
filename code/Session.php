<?php

namespace EventRegistration;

use \Session as SS_Session;
use \EventRegistration;

/**
 * Creates, stores, retrieves and deletes the current registration id.
 */
class Session{

	protected $event;

	public function __construct($event) {
		$this->event = $event;
	}

	protected function sessionKey() {
		return "EventRegistration.".$this->event->ID;
	}

	public function get() {
		return EventRegistration::get()->byID(
			SS_Session::get($this->sessionKey())
		);
	}

	public function set($registration) {
		SS_Session::set($this->sessionKey(), $registration->ID);
	}

	public function start() {
		$registration = EventRegistration::create();
		$registration->EventID = $this->event->ID;
		$registration->write();
		$this->set($registration);
		return $registration;
	}

	public function end() {
		SS_Session::set($this->sessionKey(), null);
		SS_Session::clear($this->sessionKey());
	}

}