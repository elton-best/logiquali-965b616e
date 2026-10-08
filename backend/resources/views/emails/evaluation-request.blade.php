@component('mail::message')
# {{ $isReminder ? 'Rappel : ' : '' }}{{ $request->subject }}

@if($request->recipient_name)
Bonjour {{ $request->recipient_name }},
@else
Bonjour,
@endif

{{ $request->message ?? "Nous vous invitons à participer à notre enquête d'évaluation." }}

@component('mail::button', ['url' => $publicUrl])
Répondre à l'évaluation
@endcomponent

@if($expiresAt)
**Note :** Ce lien expire le {{ $expiresAt->format('d/m/Y à H:i') }}.
@endif

@if($isReminder)
---
*Ceci est un rappel. Si vous avez déjà répondu, veuillez ignorer ce message.*
@endif

Merci de votre participation.

Cordialement,<br>
{{ $request->site?->name ?? config('app.name') }}
@endcomponent
