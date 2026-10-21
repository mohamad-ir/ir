<table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin:0 0 20px;background:#f7f6f2;border-radius:10px">
    <tr><td style="padding:16px 18px;font-size:15px;line-height:1.6">
        <strong>{{ $webinar->displayStart() }}</strong><br>
        {{ $webinar->duration_minutes }} minutes · online via {{ $webinar->platform?->getLabel() }}
    </td></tr>
</table>
