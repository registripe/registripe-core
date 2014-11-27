<?php

class EventConfirmationController extends Page_Controller{
	
	/**
	 * Handles a user clicking on a registration confirmation link in an email.
	 */
	public function confirm($request) {
		$id    = $request->param('ID');
		$token = $request->getVar('token');

		if (!$rego = EventRegistration::get()->byID($id)) {
			return $this->httpError(404);
		}
		if ($rego->Token != $token) {
			return $this->httpError(403);
		}
		if ($rego->Status != 'Unconfirmed') {
			return $this->redirect($rego->Link());
		}
		try {
			$rego->Status = 'Valid';
			$rego->write();

			EventRegistrationDetailsEmail::factory($rego)->send();
		} catch (ValidationException $e) {
			return array(
				'Title'   => 'Could Not Confirm Registration',
				'Content' => '<p>' . $e->getResult()->message() . '</p>'
			);
		}

		return array(
			'Title'   => $this->event->AfterConfirmTitle,
			'Content' => $this->event->obj('AfterConfirmContent')
		);
	}

}