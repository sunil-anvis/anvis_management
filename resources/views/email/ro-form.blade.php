<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
</head>
<body>

<h2>New RO Form Created</h2>

<p><strong>RO Number:</strong> {{ $roForm->ro_number }}</p>
<p><strong>Client:</strong> {{ $roForm->client_name }}</p>
<p><strong>Vendor:</strong> {{ $roForm->vendor_name }}</p>
<p><strong>Buying Price:</strong> ₹{{ number_format($roForm->buying_price,2) }}</p>
<p><strong>Selling Price:</strong> ₹{{ number_format($roForm->selling_price,2) }}</p>

<br>

<a href="{{ $roFormUrl }}"
   style="background:#2563eb;
          color:white;
          padding:12px 20px;
          text-decoration:none;
          border-radius:5px;">
    View RO Form
</a>

</body>
</html>
