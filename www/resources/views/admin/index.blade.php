@extends('layouts.admin')

@section('content')

    <div id="auth-screen" class="max-w-md mx-auto mt-16">
        <div class="bg-gray-900 border border-gray-800 rounded-2xl shadow-xl p-8">
            <h2 class="text-2xl font-bold text-center mb-6 text-gray-100">Вход в панель</h2>
            <div id="auth-error" class="hidden mb-4 p-3 bg-red-500/10 border border-red-500/30 text-red-400 rounded-lg text-sm"></div>
            <form id="login-form" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-400">Email</label>
                    <input type="email" name="email" required
                           class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-gray-200 placeholder-gray-500
                               focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-400">Пароль</label>
                    <input type="password" name="password" required
                           class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-gray-200 placeholder-gray-500
                               focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                </div>
                <button type="submit"
                        class="w-full bg-indigo-600 text-white font-medium py-2.5 rounded-lg hover:bg-indigo-500 transition">
                    Войти
                </button>
            </form>
        </div>
    </div>

    <div id="dashboard" class="hidden">

        <section id="tab-domains" class="hidden">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-2xl font-bold text-gray-100">Домены</h1>
                <button onclick="Admin.openDomainModal()"
                        class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-500 transition text-sm font-medium">
                    + Добавить домен
                </button>
            </div>
            <div class="bg-gray-900 border border-gray-800 rounded-xl shadow-lg overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-800/50 text-left text-gray-500 uppercase text-xs">
                    <tr>
                        <th class="px-6 py-3">Домен</th>
                        <th class="px-6 py-3">Цель</th>
                        <th class="px-6 py-3 text-center">Ссылок</th>
                        <th class="px-6 py-3 text-center">Статус</th>
                        <th class="px-6 py-3 text-center">Верифицирован</th>
                        <th class="px-6 py-3 text-right">Действия</th>
                    </tr>
                    </thead>
                    <tbody id="domains-tbody" class="divide-y divide-gray-800"></tbody>
                </table>
            </div>
            <div id="domains-pagination" class="mt-4 flex justify-center gap-2"></div>
        </section>

        <section id="tab-links" class="hidden">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-2xl font-bold text-gray-100">Ссылки</h1>
                <div class="flex gap-3">
                    <input id="links-search" type="text" placeholder="Поиск..."
                           class="bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-gray-200 placeholder-gray-500
                               focus:ring-2 focus:ring-indigo-500 outline-none">
                    <button onclick="Admin.openLinkModal()"
                            class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-500 transition text-sm font-medium">
                        + Новая ссылка
                    </button>
                </div>
            </div>
            <div class="bg-gray-900 border border-gray-800 rounded-xl shadow-lg overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-800/50 text-left text-gray-500 uppercase text-xs">
                    <tr>
                        <th class="px-6 py-3">Код</th>
                        <th class="px-6 py-3">Короткий URL</th>
                        <th class="px-6 py-3">Цель</th>
                        <th class="px-6 py-3 text-center">Клики</th>
                        <th class="px-6 py-3 text-center">Статус</th>
                        <th class="px-6 py-3 text-right">Действия</th>
                    </tr>
                    </thead>
                    <tbody id="links-tbody" class="divide-y divide-gray-800"></tbody>
                </table>
            </div>
            <div id="links-pagination" class="mt-4 flex justify-center gap-2"></div>
        </section>

    </div>

@endsection
