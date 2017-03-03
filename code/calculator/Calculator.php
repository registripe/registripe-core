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

	protected $registration;

	protected $storeattendeecost = true;

	protected $components = array();

	function __construct(\EventRegistration $registration, $componentNames = null) {
		$this->registration = $registration;
		if (!$componentNames) {
			$componentNames = self::defaultComponentNames();
		}
		$this->components = $this->initComponents($componentNames);
	}

	public function setStoreAttendeeCost($store = true){
		$this->storeattendeecost = $store;
		return $this;
	}

	public function calculate() {		
		$total = 0;
		// each attendee
		foreach($this->registration->Attendees() as $attendee) {
			$cost = 0;
			foreach($this->components as $component) {
				$cost = $component->calculateAttendee($attendee, $cost);
			}
			if($this->storeattendeecost) {
				$attendee->Cost = $cost;
				$attendee->write();
			}
			$total += $cost;
		}
		// registration
		foreach($this->components as $component) {
			$total = $component->calculateRegistration($total);
		}
		return $total;
	}

	/**
	 * Create default calculator components
	 */
	protected static function defaultComponentNames(){
		return array("Cost");
	}

	/**
	 * Creates instances of components from component name strings
	 * @param array componentNames
	 * @return array
	 */
	protected function initComponents($componentNames) {
		$components = array();
		foreach($componentNames as $name) {
			$className = sprintf("EventRegistration\Calculator\%sComponent", $name);
			array_push($components, \Injector::inst()->create($className, $this->registration));
		}
		return $components;
	}

}