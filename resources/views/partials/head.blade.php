<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="csrf-token" content="{{ csrf_token() }}" />

<title>{{ $title ?? config('app.name') }}</title>

<link rel="icon" href="/favicon.svg" type="image/svg+xml">

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=barlow-condensed:500,600,700|ibm-plex-sans:400,500,600,700|ibm-plex-mono:400,500&amp;display=swap" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
