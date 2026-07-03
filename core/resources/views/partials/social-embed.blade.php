{{-- OpenGraph / Twitter-Card tags. $embed is built by XBB\Support\SocialEmbed. --}}
<meta property="og:site_name" content="{{ $embed['siteName'] }}">
<meta property="og:type" content="{{ $embed['ogType'] }}">
<meta property="og:title" content="{{ $embed['title'] }}">
<meta property="og:url" content="{{ $embed['url'] }}">
<meta name="twitter:card" content="{{ $embed['twitterCard'] }}">
<meta name="twitter:title" content="{{ $embed['title'] }}">
@if($embed['description'] !== null)
    <meta property="og:description" content="{{ $embed['description'] }}">
    <meta name="twitter:description" content="{{ $embed['description'] }}">
@endif
@if($embed['image'] !== null)
    <meta property="og:image" content="{{ $embed['image'] }}">
    <meta name="twitter:image" content="{{ $embed['image'] }}">
@endif
@if($embed['video'] !== null)
    <meta property="og:video" content="{{ $embed['video'] }}">
    <meta property="og:video:secure_url" content="{{ $embed['video'] }}">
    <meta property="og:video:type" content="{{ $embed['videoType'] }}">
    <meta property="og:video:width" content="{{ $embed['videoWidth'] }}">
    <meta property="og:video:height" content="{{ $embed['videoHeight'] }}">
    <meta name="twitter:player:stream" content="{{ $embed['video'] }}">
    <meta name="twitter:player:stream:content_type" content="{{ $embed['videoType'] }}">
    <meta name="twitter:player:width" content="{{ $embed['videoWidth'] }}">
    <meta name="twitter:player:height" content="{{ $embed['videoHeight'] }}">
@endif
@if($embed['audio'] !== null)
    <meta property="og:audio" content="{{ $embed['audio'] }}">
    <meta property="og:audio:type" content="{{ $embed['audioType'] }}">
@endif
<meta name="theme-color" content="{{ $embed['themeColor'] }}">
@if($embed['oembedUrl'] !== null)
    <link rel="alternate" type="application/json+oembed" href="{{ $embed['oembedUrl'] }}" title="{{ $embed['title'] }}">
@endif
