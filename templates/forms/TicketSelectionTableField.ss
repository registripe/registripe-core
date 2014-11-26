<table class="table">
	<thead>
		<tr>
			<th>Name</th>
			<th>Description</th>
			<th>Price</th>
			<th></th>
		</tr>
	</thead>
	<tbody>
		<% loop Tickets %>
			<tr>
				<td>$Name</td>
				<td>$Description</td>
				<td>$Price</td>
				<td><a href="$Up.TicketLink($ID)">Add Ticket</a></td>
			</tr>
		<% end_loop %>
	</tbody>
</table>