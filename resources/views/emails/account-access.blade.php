<!DOCTYPE html>
<html lang="en">
<body style="margin:0;background:#0f1720;font-family:Arial,Helvetica,sans-serif;color:#111827;">

<table width="100%" cellpadding="0" cellspacing="0" style="background:#0f1720;padding:40px 16px;">
<tr>
<td align="center">

<table width="100%" cellpadding="0" cellspacing="0" style="max-width:640px;">

<!-- Header -->
<tr>
<td style="padding:18px 8px 28px;text-align:center;">
    <div style="font-size:30px;letter-spacing:3px;line-height:1.2;">
        <span style="color:#ef233c;font-weight:800;">SOS</span>
        <span style="color:#ffffff;font-weight:300;"> Labour Solutions</span>
    </div>
    <div style="margin-top:10px;color:#cbd5e1;font-size:12px;letter-spacing:5px;">
        PERSONALISED INDUSTRY SERVICES
    </div>
</td>
</tr>

<!-- Main Card -->
<tr>
<td style="
    background:#ffffff;
    border-radius:16px;
    overflow:hidden;
    border:1px solid #1f2937;
    box-shadow:0 18px 45px rgba(0,0,0,0.28);
">

    <!-- Top Accent -->
    <div style="height:6px;background:#06b6d4;"></div>

    <div style="padding:42px 42px 34px;">

        <!-- Small Label -->
        <div style="
            display:inline-block;
            background:#ecfeff;
            color:#0e7490;
            font-size:12px;
            font-weight:700;
            letter-spacing:1.5px;
            padding:7px 12px;
            border-radius:999px;
            margin-bottom:24px;
        ">
            SECURE WORKFORCE PORTAL
        </div>

        <p style="margin:0 0 18px;font-size:18px;font-weight:700;color:#111827;">
            Hi {{ $firstName }},
        </p>

        <h1 style="margin:0 0 18px;font-size:26px;line-height:1.3;color:#111827;font-weight:800;">
            {{ $title }}
        </h1>

        @if (!empty($introText))
            <p style="margin:0 0 12px;font-size:16px;line-height:1.7;color:#374151;">
                {{ $introText }}
            </p>
        @endif

        <p style="margin:0;font-size:16px;line-height:1.7;color:#374151;">
            {{ $bodyText }}
        </p>

        <div style="text-align:center;margin:34px 0 30px;">
            <a href="{{ $actionUrl }}" style="
                background:#111820;
                color:#ffffff;
                text-decoration:none;
                padding:15px 30px;
                border-radius:8px;
                font-weight:700;
                display:inline-block;
                border-bottom:4px solid #ef233c;
                box-shadow:0 8px 18px rgba(17,24,32,0.22);
            ">
                {{ $buttonText }}
            </a>
        </div>

        <div style="
            background:#f8fafc;
            border:1px solid #e5e7eb;
            border-radius:12px;
            padding:18px 20px;
            margin-bottom:26px;
        ">
            <p style="margin:0;font-size:14px;line-height:1.6;color:#475569;">
                {!! $expiryText !!}
            </p>
        </div>

        @if (!empty($noteText))
            <p style="margin:0 0 22px;font-size:15px;line-height:1.7;color:#374151;">
                {{ $noteText }}
            </p>
        @endif

        <p style="margin:0;font-size:15px;line-height:1.7;color:#374151;">
            {{ $closingText }}<br>
            <strong>{{ $brandName }}</strong>
        </p>

        <div style="margin-top:32px;padding-top:22px;border-top:1px solid #e5e7eb;">
            <p style="margin:0 0 8px;font-size:12px;line-height:1.6;color:#6b7280;">
                If the button does not work, copy and paste this URL into your browser:
            </p>

            <a href="{{ $actionUrl }}" style="font-size:12px;line-height:1.6;color:#ef233c;word-break:break-all;">
                {{ $actionUrl }}
            </a>
        </div>

    </div>
</td>
</tr>

<!-- Footer -->
<tr>
<td style="padding:24px 20px 0;text-align:center;color:#94a3b8;font-size:12px;line-height:1.6;">
   &copy; {{ now()->year }} SOS Labour Solutions. All rights reserved.<br>
</td>
</tr>

</table>

</td>
</tr>
</table>

</body>
</html>
