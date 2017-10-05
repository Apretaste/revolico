<h1>{$searchQuery|lower|capitalize}</h1>

{space5}

<table width="100%" cellpadding="5" cellspacing="0">
{foreach from=$items item=item}
	{assign var="bgcolor" value="{cycle values="#f2f2f2,white"}"}
	<tr>
		<td align="left" width="110" valign="middle" bgcolor="{$bgcolor}">
			{if $item->number_of_pictures gt 0}
				{img src="{$wwwroot}/public/tienda/{$item->source_url|md5}_1.jpg" alt="Imagen del producto" width="100"}
			{else}
				{noimage}
			{/if}
		</td>
		<td valign="middle" bgcolor="{$bgcolor}">
			{if $item->price neq 0 AND $item->price neq ""}
				<font color="#5EBB47">${$item->price|number_format} {$item->currency}</font>
				{separator}
			{/if}

			{link href="REVOLICO VER {$item->id}" caption="{$item->ad_title|capitalize|truncate:45:' ...'}"}

			<br/>

			{if $item->ad_body ne ''}
				{$item->ad_body|truncate:175:' ...'}
			{/if}

			<br/>

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
{/foreach}
</table>

{if $numberOfTotalResults gt 10}
	{space15}
	<center>
		<small><font color="gray">{$numberOfDisplayedResults} de {$numberOfTotalResults} art&iacute;culos encontrados {button href="REVOLICO BUSCARTODO {$searchQuery}" caption="Ver m&aacute;s" size="small"}</font></small>
	</center>
{/if}
