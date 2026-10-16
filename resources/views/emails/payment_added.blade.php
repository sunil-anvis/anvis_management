<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Added</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f6f9;font-family:'Segoe UI',Arial,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f6f9;padding:40px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background-color:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.08);">

                    {{-- Header --}}
                    <tr>
                        <td style="background:linear-gradient(135deg,#064e3b 0%,#059669 100%);padding:36px 40px;text-align:center;">

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
                                Payment Received
                            </h1>

                            <p style="margin:8px 0 0;color:#a7f3d0;font-size:13px;">
                                A new payment has been successfully recorded
                            </p>

                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding:36px 40px;">

                            {{-- Amount Highlight --}}
                            <table width="100%" cellpadding="0" cellspacing="0" style="background:#f0fdf4;border-radius:10px;margin-bottom:24px;text-align:center;">
                                <tr>
                                    <td style="padding:20px;">
                                        <p style="margin:0;font-size:12px;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:1px;">Amount Paid</p>
                                        <p style="margin:6px 0 0;font-size:32px;font-weight:800;color:#059669;">₹ {{ number_format($payment->amount, 2) }}</p>
                                        <p style="margin:4px 0 0;font-size:12px;color:#94a3b8;">{{ \Carbon\Carbon::parse($payment->payment_date)->format('d M Y') }}</p>
                                    </td>
                                </tr>
                            </table>

                            {{-- Domain details --}}
                            <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;border-radius:8px;border-left:4px solid #059669;">
                                <tr>
                                    <td style="padding:24px 28px;">
                                        <p style="margin:0 0 14px;font-size:12px;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;">Domain Details</p>
                                        <table width="100%" cellpadding="0" cellspacing="12">
                                            <tr>
                                                <td style="font-size:13px;color:#64748b;font-weight:600;padding-bottom:4px;width:160px;">Domain Name</td>
                                                <td style="font-size:14px;color:#1e293b;font-weight:700;">{{ $payment->domain->name ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <td style="font-size:13px;color:#64748b;font-weight:600;padding-bottom:4px;">Payment Date</td>
                                                <td style="font-size:14px;color:#1e293b;">{{ \Carbon\Carbon::parse($payment->payment_date)->format('d M Y') }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            {{-- Payment Summary --}}
                            @php $summary = $payment->domain->payment_summary ?? []; @endphp
                            @if(!empty($summary))
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:20px;border-radius:8px;overflow:hidden;border:1px solid #e2e8f0;">
                                <tr style="background:#1e293b;">
                                    <td style="padding:10px 16px;font-size:12px;color:#94a3b8;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;">Summary</td>
                                    <td style="padding:10px 16px;font-size:12px;color:#94a3b8;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;text-align:right;">Amount</td>
                                </tr>
                                <tr style="background:#f8fafc;">
                                    <td style="padding:10px 16px;font-size:13px;color:#475569;">Total Amount</td>
                                    <td style="padding:10px 16px;font-size:13px;color:#1e293b;font-weight:600;text-align:right;">₹ {{ number_format($summary['total_amount'] ?? 0, 2) }}</td>
                                </tr>
                                <tr style="background:#ffffff;">
                                    <td style="padding:10px 16px;font-size:13px;color:#475569;">Total Paid</td>
                                    <td style="padding:10px 16px;font-size:13px;color:#059669;font-weight:600;text-align:right;">₹ {{ number_format($summary['total_paid'] ?? 0, 2) }}</td>
                                </tr>
                                <tr style="background:#fef2f2;">
                                    <td style="padding:10px 16px;font-size:13px;color:#475569;font-weight:700;">Balance Pending</td>
                                    <td style="padding:10px 16px;font-size:14px;color:#dc2626;font-weight:700;text-align:right;">₹ {{ number_format($summary['balance_pending'] ?? 0, 2) }}</td>
                                </tr>
                            </table>
                            @endif

                            {{-- CTA Button --}}
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:28px;">
                                <tr>
                                    <td align="center">
                                        <a href="https://domain.anvisclients.com/domains/{{ $payment->domain->id ?? '' }}" style="display:inline-block;background:linear-gradient(135deg,#064e3b,#059669);color:#ffffff;text-decoration:none;padding:13px 32px;border-radius:8px;font-size:14px;font-weight:600;letter-spacing:0.3px;">View in System →</a>
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
