<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 0; }
    body { margin: 0; font-family: 'DejaVu Serif', serif; color: #1c2733; }
    .page { position: absolute; top: 0; left: 0; width: 297mm; height: 210mm; }
    .frame { position: absolute; top: 9mm; left: 9mm; right: 9mm; bottom: 9mm; border: 2.2mm solid {{ $primary }}; }
    .inner { position: absolute; top: 13mm; left: 13mm; right: 13mm; bottom: 13mm; border: 0.4mm solid {{ $accent }}; }
    .content { position: absolute; top: 28mm; left: 28mm; width: 241mm; text-align: center; }
    .brand { font-family: 'DejaVu Sans', sans-serif; font-size: 11pt; letter-spacing: 3pt; text-transform: uppercase; color: {{ $primary }}; font-weight: bold; }
    .logo { height: 16mm; margin-bottom: 3mm; }
    .title { margin-top: 10mm; font-size: 32pt; color: {{ $primary }}; letter-spacing: 1pt; }
    .rule { width: 40mm; height: 0.7mm; background: {{ $accent }}; margin: 5mm auto 6mm; }
    .small { font-family: 'DejaVu Sans', sans-serif; font-size: 10.5pt; color: #5b6570; }
    .name { margin: 6mm 0 3mm; font-size: 30pt; font-style: italic; color: #111a24; }
    .course { margin-top: 4mm; font-size: 18pt; font-weight: bold; color: {{ $primary }}; }
    .meta { margin-top: 7mm; font-family: 'DejaVu Sans', sans-serif; font-size: 10.5pt; color: #3c4650; }
    .footer { position: absolute; left: 28mm; width: 241mm; top: 158mm; }
    .footer td { vertical-align: bottom; font-family: 'DejaVu Sans', sans-serif; font-size: 9pt; color: #5b6570; }
    .sig-img { height: 14mm; }
    .sig-line { border-top: 0.3mm solid #8a939c; padding-top: 2mm; width: 70mm; }
    .sig-name { font-family: 'DejaVu Serif', serif; font-size: 11pt; color: #1c2733; font-weight: bold; }
    .verify { text-align: right; }
    .code { font-size: 11pt; color: #1c2733; font-weight: bold; letter-spacing: 1pt; }
</style>
</head>
<body>
<div class="page">
    <div class="frame"></div>
    <div class="inner"></div>
    <div class="content">
        @if ($logo)<img src="{{ $logo }}" class="logo" alt=""><br>@endif
        <div class="brand">{{ setting('site.name') }}</div>
        <div class="title">Certificate of Completion</div>
        <div class="rule"></div>
        <div class="small">This is to certify that</div>
        <div class="name">{{ $certificate->recipient_name }}</div>
        <div class="small">{{ $statement }}</div>
        <div class="course">{{ $certificate->course_title }}</div>
        <div class="meta">
            Completed on {{ $certificate->issued_at->format('j F Y') }}@if ($certificate->hoursLabel()) &nbsp;·&nbsp; {{ $certificate->hoursLabel() }} of learning @endif
        </div>
    </div>
    <table class="footer" width="100%" cellspacing="0" cellpadding="0">
        <tr>
            <td width="50%">
                @if ($signature)<img src="{{ $signature }}" class="sig-img" alt=""><br>@endif
                <div class="sig-line"><span class="sig-name">{{ $signatory }}</span>@if ($signatoryTitle)<br>{{ $signatoryTitle }}@endif</div>
            </td>
            <td width="50%" class="verify">
                Certificate ID<br><span class="code">{{ $certificate->code }}</span><br>
                Verify at {{ preg_replace('~^https?://~', '', $certificate->verifyUrl()) }}
            </td>
        </tr>
    </table>
</div>
</body>
</html>
