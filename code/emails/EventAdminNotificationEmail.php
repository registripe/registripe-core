<?php

class EventAdminNotificationEmail extends Email {

	protected $ss_template = 'EventAdminNotificationEmail';

	/**
	 * Creates an email instance from a registration object.
	 *
	 * @param  EventRegistration $registration
	 * @return EventRegistrationDetailsEmail
	 */
	public static function factory(EventRegistration $registration) {
		$email      = new self();
		$siteconfig = SiteConfig::current_site_config();
		$email->setSubject(sprintf(
			_t(
				'EventAdminNotificationEmail.SUBJECT',
				'New Registration for %s (%s)'
			),
			$registration->Event()->Title,
			$siteconfig->Title));

		$email->populateTemplate(array(
			'Registration' => $registration,
			'SiteConfig'   => $siteconfig
		));

		singleton(get_class())->extend('updateEmail', $email, $registration);
		return $email;
	}

}