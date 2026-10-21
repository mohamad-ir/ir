@php
    $primary = setting('theme.primary', '#12324f');
    $accent = setting('theme.accent', '#0f8b8d');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>{{ $title ?? setting('site.name') }}</title>
    <style>
        body { margin: 0; padding: 0; background: #f3f2ee; -webkit-text-size-adjust: 100%; }
        a { color: {{ $accent }}; }
        .content p { margin: 0 0 16px; }
        .content h2 { font-family: Georgia, 'Times New Roman', serif; font-size: 22px; color: {{ $primary }}; margin: 28px 0 12px; }
        .content h3 { font-family: Georgia, serif; font-size: 18px; color: {{ $primary }}; margin: 22px 0 10px; }
        .content img { max-width: 100%; height: auto; border-radius: 8px; }
        .content ul, .content ol { padding-left: 22px; margin: 0 0 16px; }
        .content blockquote { margin: 0 0 16px; padding: 4px 0 4px 16px; border-left: 3px solid {{ $accent }}; color: #444; }
        .content .callout { margin: 20px 0; padding: 14px 16px; border-left: 4px solid {{ $accent }}; background: #eef6f6; border-radius: 6px; }
        .content .callout-title { font-size: 11px; letter-spacing: .12em; text-transform: uppercase; font-weight: bold; margin: 0 0 4px; color: {{ $accent }}; }
        .content table { border-collapse: collapse; width: 100%; }
        .content td, .content th { border: 1px solid #e3e1da; padding: 6px 8px; }
        @media (max-width: 620px) { .wrap { padding: 24px 18px !important; } }
    </style>
</head>
<body>
    @isset($preheader)<div style="display:none;max-height:0;overflow:hidden;opacity:0">{{ $preheader }}</div>@endisset
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f2ee;padding:32px 12px">
        <tr><td align="center">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px">
                <tr><td style="padding:0 8px 18px;font-family:Georgia,'Times New Roman',serif;font-size:20px;font-weight:bold;color:{{ $primary }}">
                    <a href="{{ url('/') }}" style="color:{{ $primary }};text-decoration:none">{{ setting('site.name') }}</a>
                </td></tr>
                <tr><td class="wrap" style="background:#ffffff;border-radius:14px;padding:36px 40px;font-family:-apple-system,'Segoe UI',Helvetica,Arial,sans-serif;font-size:16px;line-height:1.65;color:#1c2733">
                    @yield('body')
                </td></tr>
                <tr><td style="padding:22px 8px;font-family:-apple-system,'Segoe UI',Helvetica,Arial,sans-serif;font-size:12px;line-height:1.6;color:#7a7f87">
                    @yield('footer')
                    <p style="margin:8px 0 0">{{ setting('site.medical_disclaimer') }}</p>
                </td></tr>
            </table>
        </td></tr>
    </table>
    @isset($pixel)<img src="{{ $pixel }}" width="1" height="1" alt="" style="display:block;width:1px;height:1px;border:0">@endisset
</body>
</html>
