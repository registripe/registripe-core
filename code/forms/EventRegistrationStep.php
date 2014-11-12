<?php

class EventRegistrationStep extends MultiFormStep{

	protected function getRegistration(){
		return $this->form->getSession()->getRegistration();
	}

	protected function getEvent(){
		return $this->form->getController()->getEvent();
	}

	protected function getTotalCost() {
		$calculator = new EventRegistrationCostCalculator($this->getRegistration());
		
		return DBField::create_field('Money', array(
			'Amount'   => $calculator->calculate(),
			'Currency' => $this->getRegistration()->Tickets()->first()->PriceCurrency
		));
	}

	/**
	 * Calculate the total each time we save the form.
	 */
	public function saveData($data) {
		parent::saveData($data);
		$total = $this->getTotalCost();
		if($this->updatemodel){
			$this->registration->Total->setCurrency($total->getCurrency());
			$this->registration->Total->setAmount($total->getAmount());
		}
		
	}

}