<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Domain Created</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f6f9;font-family:'Segoe UI',Arial,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f6f9;padding:40px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background-color:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.08);">

                    {{-- Header --}}
                    <tr>
                        <td style="background:linear-gradient(135deg,#1e3a5f 0%,#2563eb 100%);padding:36px 40px;text-align:center;">

                            <img src="{{ $message->embed(public_path('anvis-favicon.png')) }}"
                                alt="Anvis"
                                style="display:block;
                                        width:64px;
                                        height:64px;
                                        margin:0 auto 18px;
                                        background:#ffffff;
                                        padding:10px;
                                        border-radius:12px;">

                            <h1 style="margin:0;color:#ffffff;font-size:22px;font-weight:700;letter-spacing:0.5px;">
                                New Domain Created
                            </h1>

                            <p style="margin:8px 0 0;color:#bfdbfe;font-size:13px;">
                                A new domain has been added to the system
                            </p>

                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding:36px 40px;">
                            <table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f7ff;border-radius:8px;border-left:4px solid #2563eb;">
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
                                                <td style="font-size:13px;color:#64748b;font-weight:600;padding-bottom:4px;">Booking Date</td>
                                                <td style="font-size:14px;color:#1e293b;">{{ \Carbon\Carbon::parse($domain->booking_date)->format('d M Y') }}</td>
                                            </tr>
                                            <tr>
                                                <td style="font-size:13px;color:#64748b;font-weight:600;padding-bottom:4px;">Expiry Date</td>
                                                <td style="font-size:14px;color:#dc2626;font-weight:600;">{{ $domain->expiry_date ? \Carbon\Carbon::parse($domain->expiry_date)->format('d M Y') : 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <td style="font-size:13px;color:#64748b;font-weight:600;padding-bottom:4px;">Total Amount</td>
                                                <td style="font-size:14px;color:#16a34a;font-weight:700;">₹ {{ number_format($domain->total_amount, 2) }}</td>
                                            </tr>
                                            <tr>
                                                <td style="font-size:13px;color:#64748b;font-weight:600;padding-bottom:4px;">Status</td>
                                                <td style="font-size:13px;"><span style="background:#dcfce7;color:#16a34a;padding:3px 10px;border-radius:20px;font-weight:600;">{{ $domain->system_status ?? 'Active' }}</span></td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            {{-- CTA Button --}}
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:28px;">
                                <tr>
                                    <td align="center">
                                        <a href="https://domain.anvisclients.com/domains/{{ $domain->id }}" style="display:inline-block;background:linear-gradient(135deg,#1e3a5f,#2563eb);color:#ffffff;text-decoration:none;padding:13px 32px;border-radius:8px;font-size:14px;font-weight:600;letter-spacing:0.3px;">View Domain in System →</a>
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
