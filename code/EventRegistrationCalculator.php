<?php

/**
 * Interface for defining calculators with
 */
interface EventRegistrationCalculatorComponent {

	/**
	 * First, each calulator works out and manipulates attendee costs.
	 * @return float
	 */
	public function calculateAttendee(EventAttendee $attendee, EventRegistration $registration, $running);

	/**
	 * Second, each caulator works out and manipulates total registration cost.
	 * @return float
	 */
	public function calculateRegistration(EventRegistration $registration, $running);

}

abstract class EventRegistrationCalculatorBaseComponent {

	public function calculateAttendee(EventAttendee $attendee, EventRegistration $registration, $running) {
		return $running;
	}

	public function calculateRegistration(EventRegistration $registration, $running) {
		return $running;
	}

}

/**
 * Calculates each attendee and total registration cost by passing them
 * through all of the calculator components.
 */
class EventRegistrationCalculator {
	
	protected $components = array();

	function __construct($components = null) {
		if (!$components) {
			$components = self::defaultComponents();
		}

		$this->components = $components;
	}

	public function calculate($registration) {
		$total = 0;
		// each attendee
		foreach($registration->Attendees() as $attendee) {
			$cost = 0;
			foreach($this->components as $component) {
				$cost = $component->calculateAttendee($attendee, $registration, $cost);
			}
			$total += $cost;
		}
		// registration
		foreach($this->components as $component) {
			$total = $component->calculateRegistration($registration, $total);
		}
		return $total;
	}

	protected static function defaultComponents(){
		return array(
			new EventRegistrationCostCalculatorComponent()
		);
	}

}