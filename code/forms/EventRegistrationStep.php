<?php

class EventRegistrationStep extends MultiFormStep{

	protected function getRegistration(){
		return $this->form->getSession()->getRegistration();
	}

	protected function getEvent(){
		return $this->form->getController()->getEvent();
	}

	protected function getTotalCost($registration) {
		//TODO: calculator
		return 100;
	}

}