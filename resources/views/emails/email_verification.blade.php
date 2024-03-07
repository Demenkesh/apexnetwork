<x-mail::message>
# Email Verification

The body of your message.

<x-mail::button :url="$verification_Url">
    Email Verification
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
