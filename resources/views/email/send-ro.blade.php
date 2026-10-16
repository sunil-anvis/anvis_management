<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 14px;
            color: #333;
            line-height: 1.6;
            background-color: #f8f9fa;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 650px;
            margin: auto;
            background: #ffffff;
            border: 1px solid #dddddd;
            border-radius: 8px;
            overflow: hidden;
        }

        .header {
            background: #1E3A5F;
            color: #ffffff;
            text-align: center;
            padding: 18px;
        }

        .header h2 {
            margin: 0;
            font-size: 22px;
        }

        .content {
            padding: 25px;
        }

        .details {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        .details td {
            padding: 10px;
            border: 1px solid #e5e5e5;
        }

        .details td:first-child {
            background: #f5f7fa;
            font-weight: bold;
            width: 35%;
        }

        .amount {
            color: #1E3A5F;
            font-size: 16px;
            font-weight: bold;
        }

        .note {
            background: #f8f9fc;
            border-left: 4px solid #1E3A5F;
            padding: 12px;
            margin-top: 20px;
            font-size: 13px;
        }

        .footer {
            padding: 20px 25px;
            border-top: 1px solid #eeeeee;
            font-size: 13px;
            color: #666;
        }

        .footer strong {
            color: #1E3A5F;
        }
    </style>
</head>

<body>

<div class="container">

    <div class="header">
        <h2>Service Order Notification</h2>
    </div>

    <div class="content">

        <p>Dear <strong>Accounts Team,</strong></p>

        <p>
            We hope you are doing well.
        </p>

        <p>
            Please find the attached <strong>Service Order (RO)</strong> for your review and further processing.
            The details are summarized below:
        </p>

        <table class="details">

            <tr>
                <td>RO Number</td>
                <td>{{ $roForm->ro_number }}</td>
            </tr>

            <tr>
                <td>Client Name</td>
                <td>{{ $roForm->client_name }}</td>
            </tr>

            <tr>
                <td>Vendor Name</td>
                <td>{{ $roForm->vendor_name }}</td>
            </tr>

            <tr>
                <td>Service</td>
                <td>{{ $roForm->service }}</td>
            </tr>

            <tr>
                <td>Total Amount</td>
                <td class="amount">
                    ₹ {{ number_format($roForm->total_amount,2) }}
                </td>
            </tr>

            <tr>
                <td>Completion Date</td>
                <td>{{ \Carbon\Carbon::parse($roForm->completion_date)->format('d-m-Y') }}</td>
            </tr>

        </table>

        <div class="note">
            <strong>Note:</strong><br>
            Kindly review the attached Service Order and proceed with the necessary accounting and billing activities.
            If any clarification is required, please contact the concerned executive.
        </div>

        <p style="margin-top:25px;">
            Thank you for your cooperation.
        </p>

    </div>

    <div class="footer">

        <strong>Regards,</strong><br><br>

        <strong>ANVIS DIGITAL PVT. LTD.</strong><br>
        Finance & Operations Team<br>
        accounts@anvisdigital.com

    </div>

</div>

</body>
</html>
