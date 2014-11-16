<?php

class EventRegistrationStep extends MultiFormStep{


	protected function getRegistration() {
		return $this->form->getSession()->getRegistration();
	}

	protected function getEvent() {
		return $this->form->getController()->getEvent();
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

	public function getTotalCost() {
		return $this->form->getSession()->getTotalCost();
	}

}
