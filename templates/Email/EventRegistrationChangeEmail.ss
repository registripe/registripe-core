<h1>Event Details Changed For $Time.Event.Title ($SiteConfig.Title)</h1>

<p>To $Name,</p>

<p>
	You recently registered for $Time.Event.Title on {$Time.Summary}. Some of the
	details for the event have changed:
</p>

<dl>
	<% with Changed %>
		<dt>$Label</dt>
		<dd>$After <% if Before %>(was $Before)<% end_if %></dd>
	<% end_with %>
</dl>

<p>
	To view further details for this event, please <a href="$Link">click here</a>
</p>