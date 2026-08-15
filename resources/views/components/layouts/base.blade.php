@props(['title' => null])

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#16202f">

    <title>{{ $title ? $title.' — '.config('app.name') : config('app.name') }}</title>

    {{-- الخطان الظاهران فوق الطية فقط --}}
    <link rel="preload" href="/fonts/el-messiri-var-arabic.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/fonts/tajawal-400-arabic.woff2" as="font" type="font/woff2" crossorigin>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-dvh bg-cream text-navy antialiased">
    {{ $slot }}

    <x-toast />

    @livewireScripts
</body>
</html>
