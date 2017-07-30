<table width="100%">
	<tr>
		<td align="right"><small>
			<font color="gray">
				{$numberOfDisplayedResults} de {$numberOfTotalResults} anuncios encontrados.
				{if $numberOfTotalResults gt 10}
					{link href="TIENDA BUSCARTODO {$searchQuery}" caption="Ver m&aacute;s"}
				{/if}
			</font></small>
			{space10}
		</td>
		<td align="right">{button caption="Comprar en Apretaste" size="small" href="MERCADO"}</td>
	</tr>
</table>

{foreach from=$items item=item name=tienda}
	<table width="100%">
		<tr>
			<td rowspan="3" align="left" width="110" valign="middle">
				{if $item->number_of_pictures gt 0}
					{img src="{$wwwroot}/public/tienda/{$item->source_url|md5}_1.jpg" alt="Imagen del producto" width="100"}
				{else}
					{noimage}
				{/if}
			</td>
			<td>
				{if $item->price neq 0 AND $item->price neq ""}
					<font color="#5EBB47">${$item->price|number_format} {$item->currency}</font>
					{separator}
				{/if}

				{link href="TIENDA VER {$item->id}" caption="{$item->ad_title|capitalize|truncate:45:' ...'}"}
			</td>
		</tr>
		<tr>
			<td valign="top">
				{if $item->ad_body ne ''}
					{$item->ad_body|truncate:175:' ...'}
				{/if}
			</td>
		</tr>
		<tr>
			<td>
				<small><font color="gray">
				{if $item->number_of_pictures gt 1}
					{$item->number_of_pictures} fotos
					{separator}
				{/if}

				{if $item->contact_email_1 neq ""}
					{$item->contact_email_1}{separator}
				{elseif $item->contact_email_2 neq ""}
					{$item->contact_email_2}{separator}
				{elseif $item->contact_email_3 neq ""}
					{$item->contact_email_3}{separator}
				{/if}

				{if $item->contact_cellphone neq ""}
					{$item->contact_cellphone|cuba_phone_format}{separator}
				{elseif $item->contact_phone neq ""}
					{$item->contact_phone|cuba_phone_format}{separator}
				{/if}

				{$item->date_time_posted|date_format:"%d/%m/%Y"}
				</font></small>
			</td>
		</tr>
	</table>

	{if not $smarty.foreach.tienda.last}
		{space10}
		{hr}
		{space10}
	{/if}
{/foreach}
