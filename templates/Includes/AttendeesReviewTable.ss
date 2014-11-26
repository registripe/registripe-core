<table class="table">
	<thead>
		<tr>
			<td>Name</td>
			<td>Ticket</td>
			<td>Cost</td>
			<td></td>
		</tr>
	</thead>
	<tbody>
		<% loop	Attendees %>
			<tr>
				<td><label for="attendee_$ID">$Name</label></td>
				<td>$Ticket.Title</td>
				<td>$Cost.Nicer</td>
				<td>
					<a href="$Up.EditLink/$ID" class="btn btn-primary">edit</a>
					<a href="$Up.DeleteLink/$ID" class="btn btn-danger">remove</a>
				</td>
			</tr>
		<% end_loop %>
	</tbody>
	<tfoot>
		<tr>
			<th colspan="2">Total</th>
			<td>$Total.Nicer</td>
			<td colspan="2"></td>
		</tr>
	</tfoot>
</table>