@php
    use NumberToWords\NumberToWords;

    $numberToWords = new NumberToWords();

    $numberTransformer = $numberToWords->getNumberTransformer('en');

    $amountInWords = ucfirst($numberTransformer->toWords((int) $roForm->total_amount));
@endphp

<!DOCTYPE html>
<html>

<head>

    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

    <title>{{ $roForm->ro_number }}</title>

    <style>
        @page {
            margin: 5mm;
            size: a4 portrait;
        }

        body {
            font-family: sans-serif;
            font-size: 10px;
            color: #000;
            margin: 0;
            padding-bottom: 5px;
            border: 1px solid #000;
        }

        table {
            border-collapse: collapse;
        }

        .page {
            width: 100%;
        }

        .page td {
            vertical-align: top;
        }

        .header {
            width: 100%;
            line-height: 15px;
            text-align: center;
            font-size: 10px;
            padding-top: 3px;
        }

        .header td {
            border: none;
        }

        .logo {
            text-align: center;
            padding-top: 15px;
            padding-bottom: 10px;
        }

        .company {
            font-size: 14px;
            padding-bottom: 10px;
        }

        .order {
            font-size: 12px;
            padding-bottom: 6px;
        }

        .content {
            padding: 2px 18px 12px 18px;
            font-size: 10px;
        }

        .info {
            font-size: 10px;
        }

        .info td {
            border: none;
            padding: 3px 0;
            line-height: 15px;
        }


        .service-table {
            border: 2px solid #000;
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            margin-bottom: 8px;
        }

        .service-table th {
            border: 2px solid #000;
            padding-inline: 10px;
            padding: 10px;
            line-height: 18px;
            font-size: 10px;
        }

        .service-table td {
            border: 2px solid #000;
            padding: 10px;
            line-height: 18px;
            font-size: 10px;
            text-align: center;
        }


        p {
            margin: 0 0 3px 0;
            font-size: 12px;
            line-height: 16px;
        }

        ol {
            margin-top: 5px;
            margin-left: 18px;
            padding-left: 0;
        }

        ol li {
            margin-bottom: 6px;
            line-height: 18px;
            font-size: 12px;
        }

        img {
            vertical-align: middle;
        }

        br {
            line-height: 10px;
        }

        .footer {
            width: 100%;
            margin-top: 20px;
        }

        .footer td {
            border: none;
            vertical-align: top;
            font-size: 10px;
        }
    </style>

</head>

<body>

    <table class="page">

        <tr>

            <td>

                <table class="header" width="100%">

                    <tr>

                        <td class="logo">

                            <img src="{{ storage_path('public_storage/logo/logo.webp') }}" width="120">

                        </td>

                    </tr>

                    <tr>

                        <td class="company">

                            <b>ANVIS DIGITAL PVT.LTD</b>

                        </td>

                    </tr>

                    <tr>

                        <td class="order">

                            <b>Service Order</b>

                        </td>
                    </tr>
                    <tr>

                        <td>

                            314 - Parvati Industrial Estate,
                            New Sun Mill Compound,
                            Lower Parel,
                            Mumbai,
                            INDIA - 400013

                        </td>

                    </tr>

                    <tr>

                        <td>

                            GST : 27AAMCA3257A1ZX

                        </td>

                    </tr>

                </table>

                <div class="content">

                    <table class="info">

                        <tr>

                            <td>
                                <b>Service Order No :
                                    {{ $roForm->ro_number }}</b>

                            </td>

                        </tr>

                        <tr>
                            <td>
                                Date :

                                {{ \Carbon\Carbon::parse($roForm->created_at)->format('M. d, Y') }}

                            </td>
                        </tr>

                    </table>


                    <table class="info">

                        <tr>

                            <td>
                                TO,
                            </td>

                        </tr>

                        <tr>
                            <td>

                                <b> {{ $roForm->vendor_name }} </b>

                            </td>
                        </tr>
                        <tr>
                            <td>

                                {{ $roForm->vendor_address }}

                            </td>
                        </tr>

                        <tr>
                            <td>

                                <b>GSTIN :

                                    {{ $roForm->vendor_gst_no }} </b>

                            </td>
                        </tr>

                        <tr>
                            <td>

                                <b>Contact :</b>

                                {{ $roForm->vendor_contact }}
                            </td>
                        </tr>

                        <tr>
                            <td>

                                <b>Email id -</b>

                                {{ $roForm->vendor_email }}

                            </td>
                        </tr>

                    </table>

                    <table width="100%" style="border:none; margin-top:8px; border-collapse:collapse;">

                        <tr>
                            <td style="border:none; padding:3px 0;">
                                Dear Sir/s,
                            </td>
                        </tr>

                        <tr>
                            <td style="border:none; padding:5px 0; text-align:justify;">
                                We hereby place an order on you for the following services on the
                                terms &amp; conditions attached with this:
                            </td>
                        </tr>

                        <tr>
                            <td style="border:none; padding:5px 0 3px 0;">
                                <strong>Service Description:</strong>
                            </td>
                        </tr>

                        <tr>
                            <td style="border:none; padding:3px 0 8px 0;">
                                <strong>Client's Name :- {{ $roForm->client_name }}</strong>
                            </td>
                        </tr>

                    </table>

                    <table class="service-table">

                        <thead>

                            <tr>

                                <th>Service</th>

                                <th>Ad Type</th>

                                <th>Ad Unit</th>

                                <th>{{ $roForm->buy_type }}</th>

                                <th>{{ $roForm->deliverables }}</th>

                                <th>Amount</th>

                            </tr>

                        </thead>

                        <tbody>

                            <tr>

                                <td>
                                    {{ $roForm->service }}
                                </td>

                                <td>
                                    {{ $roForm->ad_type }}
                                </td>

                                <td>
                                    {{ $roForm->ad_unit }}
                                </td>

                                <td>
                                    {{ $roForm->deliverables }}
                                </td>

                                <td>
                                    {{ number_format($roForm->volume, 2) }}
                                </td>

                                <td>
                                    {{ number_format($roForm->total_amount, 2) }}
                                </td>

                            </tr>

                        </tbody>

                    </table>


                    <table width="100%" style="border:none;">

                        <tr>
                            <td style="font-size: 10px">
                                Value : {{ $amountInWords }} Rupees Only
                            </td>
                        </tr> <br>

                        <tr>

                            <td style="font-size: 10px">

                                <b>Delivery/job completion date :</b>

                                {{ \Carbon\Carbon::parse($roForm->completion_date)->format('d-m-Y') }}

                            </td>

                        </tr>

                    </table>

                    <br>

                    <table width="100%" style="border:none; border-collapse:collapse;">

                        <tr>
                            <td style="border:none;">
                                <strong>Payment Terms &amp; Conditions :</strong>
                            </td>
                        </tr>

                        <tr>
                            <td style="border:none; padding-top:5px; ">
                                1. Above price is net to <strong>ANVIS DIGITAL PVT. LTD.</strong> Exclusive of any Govt.
                                Taxes.
                            </td>
                        </tr>

                        <tr>
                            <td style="border:none; padding-top:5px;">
                                2. Payment Terms : Net 60 Days.
                            </td>
                        </tr>

                        <tr>
                            <td style="border:none; padding-top:5px;">
                                3. Publisher to provide access to reporting interface/screenshots.
                            </td>
                        </tr>

                        <tr>
                            <td style="border:none; padding-top:5px;">
                                4. RO details should be kept confidential between the two involved parties.
                            </td>
                        </tr>

                        <tr style="border:none; padding-top:4px;">
                            <td> It shouldn't be disclosed to any other party.</td>
                        </tr>

                        <tr>
                            <td style="border:none; text-align:justify;">
                                Please sign &amp; return the duplicate copy of this Service Order as a
                                token of your acceptance.
                            </td>
                        </tr>

                    </table>

                    <br>

                    <table class="footer" width="100%">

                        <tr>

                            <td width="50%" align="left">

                                <b>

                                    For ANVIS DIGITAL PVT.LTD

                                </b>

                                <br><br><br>

                                <img src="{{ storage_path('public_storage/signatures/sign.png') }}" width="120">

                                <br><br>

                                <b>

                                    Authorized Signatory

                                </b>

                                <br><br>

                                Created by :

                                {{ $roForm->createdBy->first_name ?? '' }}

                                {{ $roForm->createdBy->last_name ?? '' }}

                                <br><br><br><br>

                                Approval Status :

                                {{ ucfirst($roForm->status) }}

                            </td>

                            <td width="50%" align="right">

                                <b>

                                    Accepted &amp; Confirmed

                                </b>

                                <br><br><br><br><br><br><br><br><br>

                                _____________________

                                <br><br>

                                <b>

                                    Signature of Seller

                                </b>

                            </td>

                        </tr>

                    </table>

                </div>

            </td>

        </tr>

    </table>

</body>

</html>
