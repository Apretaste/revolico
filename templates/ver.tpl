<table width="100%" border=0>
	<tr>
		{if $item->number_of_pictures gt 0}
		<td width="40%" align="center" valign="top">
			{for $counter=1 to $item->number_of_pictures}
				{img src="{$wwwroot}/public/tienda/{$item->source_url|md5}_{$counter}.jpg" alt="Imagen #{$counter} del producto" width="100%"}
				{space5}
			{/for}
		</td>
		{/if}

		<td valign="top">
			<h2>{$item->ad_title|lower|capitalize}</h2>

			<table width="100%" cellpadding="10"><tr><td bgcolor="F2F2F2">
				{if $item->price neq 0 AND $item->price neq ""}<b>${$item->price|number_format} {$item->currency}</b><br/>{/if}
				{if $item->contact_email_1 neq ""}{$item->contact_email_1}<br/>{/if}
				{if $item->contact_email_2 neq ""}{$item->contact_email_2}<br/>{/if}
				{if $item->contact_email_3 neq ""}{$item->contact_email_3}<br/>{/if}
				{if $item->contact_cellphone neq ""}{$item->contact_cellphone}<br/>{/if}
				{if $item->contact_phone neq ""}{$item->contact_phone}<br/>{/if}
				<font color="gray"><small>Publicado el {$item->date_time_posted|date_format:"%d/%m/%Y"}</small></font>
			</td></tr></table>

			{space5}

			{$item->ad_body}
		</td>
	</tr>
</table>
