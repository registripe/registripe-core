<h1>$Title</h1>

<% if $Message %>
	<p class="message">$Message</p>
<% end_if %>

<% with $Registration %>
	<% if $Status = "Unconfirmed" %>
		<p id="registration-unconfirmed" class="message">
			This registration has not yet been confirmed. In order to
			confirm your registration, please check your emails for a
			confirmation email and click on confirmation link contained in
			it.
		</p>

		<% if $ConfirmTimeLimit %>
			<p id="registration-unconfirmed-limit" class="message">
				If you do not confirm your registration within
				$ConfirmTimeLimit.TimeDiff, it will be canceled.
			</p>
		<% end_if %>
	<% end_if %>

	<% if $Status = "Canceled" %>
		<p id="registration-canceled" class="message">
			This registration has been canceled.
		</p>
	<% end_if %>

	<% include EventRegistrationDetails %>
	
<% end_with %>
