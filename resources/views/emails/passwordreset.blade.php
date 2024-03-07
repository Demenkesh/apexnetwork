<x-mail::message>
# Introduction

The body of your message.

<x-mail::button :url="$password_Url">
Password_reset
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
