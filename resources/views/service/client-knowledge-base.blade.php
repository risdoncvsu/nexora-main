<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nexora | Knowledge Base</title>
    <link rel="icon" href="{{ asset('images/nexora-icon.ico') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#1B365D] font-sans text-white">
    <div class="flex min-h-screen flex-col">
        <x-itsm-header
            :home-route="route('client.itsm.employees')"
            active="service-desk"
            :nav-items="[
                ['label' => 'User Management', 'route' => route('client.itsm.employees'), 'key' => 'employees'],
                ['label' => 'Service Desk', 'route' => route('client.itsm.service-desk'), 'key' => 'service-desk'],
                ['label' => 'Compliance Tracking', 'route' => route('client.itsm.compliance'), 'key' => 'compliance'],
                ['label' => 'Risk Management', 'route' => route('client.itsm.risk'), 'key' => 'risk'],
                ['label' => 'Audit Trail', 'route' => route('client.itsm.audit-trail'), 'key' => 'audit-trail'],
            ]"
        />

        <main class="relative flex-1 p-4 sm:p-6">
            <img src="{{ asset('images/nexora-icon.png') }}" alt="" class="pointer-events-none absolute left-1/2 top-1/2 w-[64rem] -translate-x-1/2 -translate-y-1/2 opacity-10 blur-sm">
            <section class="relative z-10 mx-auto grid min-h-[calc(100vh-10rem)] max-w-[1760px] grid-cols-1 gap-6 xl:grid-cols-[22rem_minmax(0,1fr)]">
                <aside class="rounded-[1.875rem] bg-white p-5 text-slate-950 shadow-xl sm:p-8">
                    <nav class="flex flex-wrap gap-x-6 gap-y-3 text-base sm:text-xl xl:block xl:space-y-6">
                        <a href="{{ route('client.itsm.service-desk') }}" class="block font-medium text-slate-700 transition hover:text-[#346DCB]">Module Ticket Dashboard</a>
                        <a href="{{ route('client.itsm.service-desk.support') }}" class="block font-medium text-slate-700 transition hover:text-[#346DCB]">Account Recovery</a>
                        <a href="{{ route('client.itsm.service-desk.knowledgebase') }}" class="block font-extrabold text-[#346DCB]">Knowledge Base</a>
                    </nav>
                </aside>

                <div class="space-y-6">
                    <section class="rounded-[1.875rem] bg-white/90 px-5 py-5 text-slate-950 shadow-sm sm:px-8 sm:py-6">
                        <p class="text-xs font-bold uppercase tracking-wider text-[#346DCB]">Company admin portal</p>
                        <h1 class="mt-1 text-3xl font-bold sm:text-4xl">Knowledge Base</h1>
                        <p class="mt-2 text-sm text-slate-600">Published guides and answers from your Nexora support team.</p>
                    </section>

                    <section class="overflow-hidden rounded-[1.875rem] bg-white text-slate-900 shadow-xl">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-5 sm:px-8">
                            <h2 class="text-xl font-bold">Available articles</h2>
                            <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-[#346DCB]">{{ $articles->count() }} published</span>
                        </div>
                        <div class="divide-y divide-slate-100">
                            @forelse ($articles as $article)
                                <article class="px-5 py-6 sm:px-8">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div>
                                            <h3 class="text-lg font-bold text-slate-950">{{ $article->title }}</h3>
                                            <p class="mt-1 text-xs font-semibold text-slate-500">{{ $article->category }} &middot; {{ $article->target_module ?: 'General' }} &middot; {{ $article->author_name }}</p>
                                        </div>
                                        <time class="text-xs font-medium text-slate-400">{{ optional($article->created_at)->format('M j, Y') }}</time>
                                    </div>
                                    <p class="mt-4 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $article->content ?: 'No additional article content was provided.' }}</p>
                                </article>
                            @empty
                                <div class="px-5 py-16 text-center text-sm text-slate-500 sm:px-8">No knowledge-base articles have been published yet.</div>
                            @endforelse
                        </div>
                    </section>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
