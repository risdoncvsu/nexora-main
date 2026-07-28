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

        <main class="relative flex-1 p-6">
            <img src="{{ asset('images/nexora-icon.png') }}" alt="" class="pointer-events-none absolute left-1/2 top-1/2 w-[64rem] -translate-x-1/2 -translate-y-1/2 opacity-10 blur-sm">
            <section class="relative z-10 mx-auto grid max-w-[1760px] gap-6 lg:grid-cols-[16rem_1fr]">
                <aside class="h-fit rounded-[2rem] bg-white p-6 text-slate-900 shadow-2xl">
                    <p class="mb-4 text-xs font-extrabold uppercase tracking-[0.16em] text-slate-400">Service desk</p>
                    <nav class="space-y-2 text-sm font-semibold">
                        <a href="{{ route('client.itsm.service-desk') }}" class="flex items-center gap-3 rounded-xl px-4 py-3 text-slate-600 transition hover:bg-slate-50 hover:text-slate-900">Support tickets</a>
                        <a href="{{ route('client.itsm.service-desk.support') }}" class="flex items-center gap-3 rounded-xl px-4 py-3 text-slate-600 transition hover:bg-slate-50 hover:text-slate-900">Request support</a>
                        <a href="{{ route('client.itsm.service-desk.knowledgebase') }}" class="flex items-center gap-3 rounded-xl bg-slate-100 px-4 py-3 font-bold text-[#132B52]">Knowledge Base</a>
                    </nav>
                </aside>

                <div class="space-y-6">
                    <section class="rounded-[2rem] bg-[#DDE4EC] px-8 py-7 text-slate-950 shadow-sm">
                        <p class="text-sm font-bold uppercase tracking-wide text-[#346DCB]">Nexora Service Desk</p>
                        <h1 class="mt-2 text-4xl font-bold tracking-tight">Knowledge Base</h1>
                        <p class="mt-2 text-sm text-slate-600">Published guides and answers from your Nexora support team.</p>
                    </section>

                    <section class="overflow-hidden rounded-[2rem] bg-white text-slate-900 shadow-2xl">
                        <div class="flex items-center justify-between border-b border-slate-100 px-8 py-5">
                            <h2 class="text-xl font-bold">Available articles</h2>
                            <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-[#346DCB]">{{ $articles->count() }} published</span>
                        </div>
                        <div class="divide-y divide-slate-100">
                            @forelse ($articles as $article)
                                <article class="px-8 py-6">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div>
                                            <h3 class="text-lg font-bold text-slate-950">{{ $article->title }}</h3>
                                            <p class="mt-1 text-xs font-semibold text-slate-500">{{ $article->category }} · {{ $article->target_module ?: 'General' }} · {{ $article->author_name }}</p>
                                        </div>
                                        <time class="text-xs font-medium text-slate-400">{{ optional($article->created_at)->format('M j, Y') }}</time>
                                    </div>
                                    <p class="mt-4 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $article->content ?: 'No additional article content was provided.' }}</p>
                                </article>
                            @empty
                                <div class="px-8 py-16 text-center text-sm text-slate-500">No knowledge-base articles have been published yet.</div>
                            @endforelse
                        </div>
                    </section>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
