<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Domain Expiry Notification</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f6f9;font-family:'Segoe UI',Arial,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f6f9;padding:40px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background-color:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.08);">

                    {{-- Header (color changes by urgency) --}}
                    @if($daysLeft <= 1)
                        @php $headerGradient = 'linear-gradient(135deg,#7f1d1d 0%,#7f1d1d 100%)'; $badgeBg = '#fef2f2'; $badgeColor = '#dc2626'; $icon = '🚨'; @endphp
                    @elseif($daysLeft <= 7)
                        @php $headerGradient = 'linear-gradient(135deg,#7f1d1d 0%,#7f1d1d 100%)'; $badgeBg = '#fff7ed'; $badgeColor = '#ea580c'; $icon = '⚠️'; @endphp
                    @else
                        @php $headerGradient = 'linear-gradient(135deg,#7f1d1d 0%,#7f1d1d 100%)'; $badgeBg = '#eff6ff'; $badgeColor = '#2563eb'; $icon = '🔔'; @endphp
                    @endif

                    <tr>
                        <td style="background:#7f1d1d;padding:32px 40px;text-align:center;">

                            <img src="{{ $message->embed($logo) }}"
                                alt="Anvis"
                                style="display:block;
                                        width:64px;
                                        height:64px;
                                        margin:0 auto 18px;
                                        background:#ffffff;
                                        padding:10px;
                                        border-radius:12px;">

                            <h1 style="margin:0;
                                    color:#ffffff;
                                    font-size:28px;
                                    font-weight:700;">
                                Domain Expiry Alert
                            </h1>

                            <p style="margin:10px 0 0;
                                    color:#f8d7da;
                                    font-size:14px;">
                                Action required — domain expiring in
                                <strong>{{ $daysLeft }} day(s)</strong>
                            </p>

                        </td>
                    </tr>

                    {{-- Urgency Badge --}}
                    <tr>
                        <td style="padding:20px 40px 0;text-align:center;">
                            <span style="display:inline-block;background:{{ $badgeBg }};color:{{ $badgeColor }};padding:6px 18px;border-radius:20px;font-size:13px;font-weight:700;border:1px solid {{ $badgeColor }};">
                                {{ $daysLeft <= 1 ? 'CRITICAL — Expires Tomorrow!' : ($daysLeft <= 7 ? 'URGENT — ' . $daysLeft . ' Days Left' : 'REMINDER — ' . $daysLeft . ' Days Left') }}
                            </span>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding:24px 40px 36px;">
                            <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;border-radius:8px;border-left:4px solid {{ $badgeColor }};">
                                <tr>
                                    <td style="padding:24px 28px;">
                                        <table width="100%" cellpadding="0" cellspacing="12">
                                            <tr>
                                                <td style="font-size:13px;color:#64748b;font-weight:600;padding-bottom:4px;width:160px;">Domain Name</td>
                                                <td style="font-size:14px;color:#1e293b;font-weight:700;">{{ $domain->name }}</td>
                                            </tr>
                                            @if($domain->company_name)
                                            <tr>
                                                <td style="font-size:13px;color:#64748b;font-weight:600;padding-bottom:4px;">Company</td>
                                                <td style="font-size:14px;color:#1e293b;">{{ $domain->company_name }}</td>
                                            </tr>
                                            @endif
                                            <tr>
                                                <td style="font-size:13px;color:#64748b;font-weight:600;padding-bottom:4px;">Expiry Date</td>
                                                <td style="font-size:14px;color:#dc2626;font-weight:700;">{{ \Carbon\Carbon::parse($domain->expiry_date)->format('d M Y') }}</td>
                                            </tr>
                                            <tr>
                                                <td style="font-size:13px;color:#64748b;font-weight:600;padding-bottom:4px;">Status</td>
                                                <td style="font-size:13px;"><span style="background:{{ $badgeBg }};color:{{ $badgeColor }};padding:3px 10px;border-radius:20px;font-weight:600;">{{ $domain->system_status }}</span></td>
                                            </tr>
                                            @if($domain->branch)
                                            <tr>
                                                <td style="font-size:13px;color:#64748b;font-weight:600;padding-bottom:4px;">Branch</td>
                                                <td style="font-size:14px;color:#1e293b;">{{ $domain->branch->name }}</td>
                                            </tr>
                                            @endif
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <p style="font-size:13px;color:#64748b;margin:20px 0 24px;text-align:center;">Please ensure this domain is renewed before the expiry date to avoid service disruption.</p>

                            {{-- CTA Button --}}
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center">
                                        <a href="https://domain.anvisclients.com/domains/{{ $domain->id }}" style="display:inline-block;background:{{ $headerGradient }};color:#ffffff;text-decoration:none;padding:13px 32px;border-radius:8px;font-size:14px;font-weight:600;letter-spacing:0.3px;">Renew Domain Now →</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background:#f8fafc;padding:20px 40px;text-align:center;border-top:1px solid #e2e8f0;">
                           <p style="margin:0;font-size:12px;color:#94a3b8;">This is an automated notification from <strong>Server</strong>. Please reply to this email.</p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
