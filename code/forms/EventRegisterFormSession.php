<?php
/**
 * A multiform session that has a registration object attached to it, which is
 * written to the database if place holding during the registration process
 * is enabled.
 *
 * @package silverstripe-eventmanagement
 */
class EventRegisterFormSession extends MultiFormSession {

	public static $has_one = array(
		'Registration' => 'EventRegistration'
	);

	protected $form;
	protected $registration;

	/**
	 * @param EventRegisterForm $form
	 */
	public function setForm(EventRegisterForm $form) {
		$this->form = $form;
	}

	/**
	 * Find ore make the registration associated with this form session.
	 * @return EventRegistration
	 */
	public function getRegistration() {
		if ($this->registration) {
			return $this->registration;
		}
		if ($this->RegistrationID) {
			return $this->registration = $this->Registration();
		}
		$this->registration = new EventRegistration();
		$this->registration->EventID = $this->form->getController()->getEvent()->ID;
		$this->registration->Status = 'Unsubmitted';

		return $this->registration;
		
	}

	public function onBeforeWrite() {
		parent::onBeforeWrite();
		$isInDb = $this->getRegistration()->isInDB();
		$hasAttendees = $this->getRegistration()->Attendees()->exists();
		if ($isInDb || $hasAttendees) {
			$this->getRegistration()->write();
		}
		if($this->registration) {
			$this->RegistrationID = $this->registration->ID;
		}
	}

	/**
	 * Caclculate registration cost, and store in registration + cache.
	 * @return Money cost
	 */
	public function getTotalCost() {
		static $cost;
		//calculate once
		if($cost === null){
			$registration = $this->getRegistration();
			$calculator = Injector::inst()->get(
				"EventRegistrationCostCalculator", true,  array($registration)
			);
			$amount = $calculator->calculate();
			$currency = $registration->Tickets()->first()->PriceCurrency;
			$total = $registration->obj('Total');
			$total->setAmount($amount);
			$total->setCurrency($currency);
			$cost = $total;
		}

		return $cost;
	}


}
