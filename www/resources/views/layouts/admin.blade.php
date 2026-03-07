<!DOCTYPE html>
<html lang="ru" class="h-full dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>LinkMe — Админ</title>
    @vite(['resources/css/app.css', 'resources/js/admin.js'])
</head>
<body class="h-full bg-gray-950 text-gray-200">

<header id="app-header" class="sticky top-0 z-50 bg-gray-900 border-b border-gray-800 shadow-lg">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <span class="text-xl font-bold text-indigo-400">LinkMe</span>
            <nav id="main-nav" class="hidden ml-8 flex items-center gap-1">
                <button data-tab="domains"
                        class="nav-btn px-4 py-2 text-sm font-medium rounded-lg transition text-gray-400
                               hover:bg-gray-800 hover:text-indigo-400
                               data-[active]:bg-indigo-500/15 data-[active]:text-indigo-400">
                    Домены
                </button>
                <button data-tab="links"
                        class="nav-btn px-4 py-2 text-sm font-medium rounded-lg transition text-gray-400
                               hover:bg-gray-800 hover:text-indigo-400
                               data-[active]:bg-indigo-500/15 data-[active]:text-indigo-400">
                    Ссылки
                </button>
            </nav>
        </div>
        <div id="header-right" class="flex items-center gap-4"></div>
    </div>
</header>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    @yield('content')
</main>

<div id="modal-backdrop"
     class="hidden fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div id="modal-content"
         class="bg-gray-900 border border-gray-700 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
    </div>
</div>

</body>
</html>
