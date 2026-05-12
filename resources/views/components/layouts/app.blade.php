<!DOCTYPE html>
<html lang="hu" class="">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>lyricwriter</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body style="overflow: hidden;">
    {{ $slot }}
</body>
</html>
