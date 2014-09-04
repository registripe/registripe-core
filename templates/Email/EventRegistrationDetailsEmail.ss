<h1>Registration Details For $Registration.Time.Event.Title ($SiteConfig.Title)</h1>

<p>To $Registration.Name,</p>

<p>
	Thank you for registering for $Registration.Time.EventTitle! Below are the
	details of the event and your registration:
</p>

<% with Registration %>
	<h2>Registration Details</h2>
	
	<% include EventRegistrationDetails %>
	
	<ul>
		<li><a href="$Link">Registration details</a></li>
		<li><a href="$Time.Link">Event details</a></li>
	</ul>
<% end_with %>