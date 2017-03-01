<?php

namespace EventRegistration;

use EventRegistration\Calculator\CostComponent;

/**
 * Calculates each attendee and total registration cost by passing them
 * through all of the calculator components.
 * A composed component approach allows for sub-add-ons to influence
 * calculations.
 */
class Calculator {
	
	protected $storeattendeecost = true;

	protected $components = array();

	function __construct($components = null) {
		if (!$components) {
			$components = self::defaultComponents();
		}

		$this->components = $components;
	}

	public function setStoreAttendeeCost($store = true){
		$this->storeattendeecost = $store;
		return $this;
	}

	public function calculate($registration) {
		$total = 0;
		// each attendee
		foreach($registration->Attendees() as $attendee) {
			$cost = 0;
			foreach($this->components as $component) {
				$cost = $component->calculateAttendee($attendee, $registration, $cost);
			}
			if($this->storeattendeecost) {
				$attendee->Cost = $cost;
				$attendee->write();
			}
			$total += $cost;
		}
		// registration
		foreach($this->components as $component) {
			$total = $component->calculateRegistration($registration, $total);
		}
		return $total;
	}

	/**
	 * Create default calculator components
	 */
	protected static function defaultComponents(){
		return array(new CostComponent());
	}

}