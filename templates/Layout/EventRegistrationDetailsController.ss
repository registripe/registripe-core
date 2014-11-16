<h1>$Title</h1>

<% if $Message %>
	<p class="message">$Message</p>
<% end_if %>

<% with $Registration %>
	<% include EventRegistrationMessages %>
	<% include EventRegistrationDetails %>
<% end_with %>
