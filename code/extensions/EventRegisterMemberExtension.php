<?php

class EventRegisterMemberExtension extends DataExtension {
	
	private static $has_many = array(
		'EventRegistrations' => 'EventRegistration'
	);
}