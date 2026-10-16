<x-mail::message>
# Domain Expiry Alert

Friendly reminder that the domain **{{ $domainName }}** is set to expire soon.

- **Domain:** {{ $domainName }}
- **Expiry Date:** {{ $expiryDate }}
- **Days Remaining:** {{ $daysRemaining }} days

Please ensure the renewal process is initiated to avoid any service interruption.

<x-mail::button :url="config('app.url')">
View Domains
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
