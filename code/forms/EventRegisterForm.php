<?php

class EventRegisterForm extends Form {

	protected $registrantValidator = null;
	protected $forceRegistrantValidator = false;

	public function setRegistrantValidator(RequiredFields $validator, $force = false) {
		$this->registrantValidator = $validator;
		$this->forceRegistrantValidator = $force;
	}

	public function validate(){
		$data = $this->getData();

		// switch validator based on whether selected RegistrantAttendeeID is present
		$noAttendee = isset($data["RegistrantAttendeeID"]) &&
			$data["RegistrantAttendeeID"] === "0";
		if (
			$this->registrantValidator &&
			$noAttendee || $this->forceRegistrantValidator
		) {
			$this->validator->appendRequiredFields($this->registrantValidator);
		}
		return parent::validate();
	}

}
