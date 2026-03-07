<!DOCTYPE html>
<html lang="ru" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Требуется пароль — LinkMe</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-950 min-h-screen flex items-center justify-center">
<div class="max-w-sm w-full mx-4">
    <div class="bg-gray-900 rounded-2xl shadow-xl border border-gray-800 p-8">
        <div class="mb-6">
            <h1 class="text-xl font-bold text-gray-100 mb-1">Требуется пароль</h1>
            <p class="text-gray-400 text-sm">
                Эта ссылка защищена паролем. Введите пароль, чтобы продолжить.
            </p>
        </div>

        <form method="POST" action="/{{ $code }}">
            @csrf

            <div class="mb-4">
                <label for="password" class="block text-sm font-medium text-gray-400 mb-1">
                    Пароль
                </label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    autofocus
                    class="w-full px-3 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-sm text-gray-200
                           focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500
                           placeholder-gray-500"
                    placeholder="Введите пароль"
                >
            </div>

            @if(isset($error))
                <div class="mb-4 px-3 py-2 bg-red-500/10 border border-red-500/30 rounded-lg">
                    <p class="text-red-400 text-sm">{{ $error }}</p>
                </div>
            @endif

            <button
                type="submit"
                class="w-full px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium
                       rounded-lg hover:bg-indigo-500 transition-colors"
            >
                Продолжить
            </button>
        </form>

        <p class="mt-6 text-center text-xs text-gray-600">LinkMe</p>
    </div>
</div>
</body>
</html>
