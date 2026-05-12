<!DOCTYPE html>
<html lang="hu" class="">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>lyricwriter</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white dark:bg-[#0a0a0a] text-[#0a0a0a] dark:text-white overflow-hidden">
    {{ $slot }}
</body>
</html>
