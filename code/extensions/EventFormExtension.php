<?php

class EventFormExtension extends Extension {

	/**
	 * Helper for checking if form has session data.
	 *
	 * @return boolean
	 */
	public function hasSessionData() {
		$session = Session::get("FormInfo.{$this->owner->FormName()}");
		return $session && $session["data"];
	}

}