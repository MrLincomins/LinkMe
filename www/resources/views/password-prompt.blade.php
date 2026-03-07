<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Password required — sniplnk</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
<div class="max-w-sm w-full mx-4">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
        <div class="mb-6">
            <h1 class="text-xl font-bold text-gray-900 mb-1">Password required</h1>
            <p class="text-gray-500 text-sm">
                This link is protected. Enter the password to continue.
            </p>
        </div>

        <form method="POST" action="/{{ $code }}">
            @csrf

            <div class="mb-4">
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                    Password
                </label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    autofocus
                    class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm
                               focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent
                               placeholder-gray-400"
                    placeholder="Enter password"
                >
            </div>

            @if(isset($error))
                <div class="mb-4 px-3 py-2 bg-red-50 border border-red-200 rounded-lg">
                    <p class="text-red-600 text-sm">{{ $error }}</p>
                </div>
            @endif

            <button
                type="submit"
                class="w-full px-4 py-2.5 bg-gray-900 text-white text-sm font-medium
                           rounded-lg hover:bg-gray-800 transition-colors"
            >
                Continue
            </button>
        </form>

        <p class="mt-6 text-center text-xs text-gray-400">sniplnk</p>
    </div>
</div>
</body>
</html>
