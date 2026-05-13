<!DOCTYPE html>
<html lang="hu" class="">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>lyricwriter</title>
    <link rel="icon" type="image/png" href="/favicon.png">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="lyricwriter">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body style="overflow: hidden;">
    {{ $slot }}
</body>
</html>
