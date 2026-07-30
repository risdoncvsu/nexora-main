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
                ['label' => 'Employee Management', 'route' => route('client.itsm.employees'), 'key' => 'employees'],
                ['label' => 'Service Desk', 'route' => route('client.itsm.service-desk'), 'key' => 'service-desk'],
                ['label' => 'Compliance Tracking', 'route' => route('client.itsm.compliance'), 'key' => 'compliance'],
                ['label' => 'Risk Management', 'route' => route('client.itsm.risk'), 'key' => 'risk'],
                ['label' => 'Audit Trail', 'route' => route('client.itsm.audit-trail'), 'key' => 'audit-trail'],
            ]"
        />

        <main class="relative flex-1 p-4 sm:p-6">
            <img src="{{ asset('images/nexora-icon.png') }}" alt="" class="pointer-events-none absolute left-1/2 top-1/2 w-[64rem] -translate-x-1/2 -translate-y-1/2 opacity-10 blur-sm">
            <section class="relative z-10 grid min-h-[calc(100vh-10rem)] grid-cols-1 gap-6 xl:grid-cols-[22rem_minmax(0,1fr)]">
                <aside class="self-start min-h-[calc(100vh-10rem)] rounded-[1.875rem] bg-white p-5 text-slate-950 sm:p-8">
                    <nav class="flex flex-wrap gap-x-6 gap-y-3 text-base sm:text-xl xl:block xl:space-y-6">
                        <a href="{{ route('client.itsm.service-desk') }}" class="block font-medium text-slate-700 transition hover:text-[#346DCB]">Module Ticket Dashboard</a>
                        <a href="{{ route('client.itsm.service-desk.support') }}" class="block font-medium text-slate-700 transition hover:text-[#346DCB]">Account Recovery</a>
                        <a href="{{ route('client.itsm.service-desk.knowledgebase') }}" class="block font-extrabold text-slate-950">Knowledge Base</a>
                    </nav>
                </aside>

                <div class="space-y-6">
                    <section class="rounded-[1.875rem] bg-white/90 px-5 py-5 text-slate-950 shadow-sm sm:px-8 sm:py-6">
                        <p class="text-xs font-bold uppercase tracking-wider text-[#346DCB]">Company admin portal</p>
                        <h1 class="mt-1 text-3xl font-bold sm:text-4xl">Knowledge Base</h1>
                        <p class="mt-2 text-sm text-slate-600">Create and maintain training guides and support answers for your company IT team.</p>
                    </section>

                    <section class="overflow-hidden rounded-[1.875rem] bg-white text-slate-900">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-5 sm:px-8">
                            <div>
                                <h2 class="text-xl font-bold">Your company articles</h2>
                                <p class="mt-1 text-xs text-slate-500">Visible only to administrators of your company.</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-[#346DCB]">{{ $articles->count() }} published</span>
                                <button type="button" data-open-article-modal class="rounded-full bg-[#346DCB] px-4 py-2 text-xs font-bold text-white transition hover:bg-[#2554a3]">Create article</button>
                            </div>
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
                                <div class="px-5 py-16 text-center text-sm text-slate-500 sm:px-8">No articles have been published for your company yet.</div>
                            @endforelse
                        </div>
                    </section>
                </div>
            </section>
        </main>

        <div id="articleModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/60 p-4">
            <div class="max-h-[92vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-6 text-slate-950 shadow-2xl sm:p-8">
                <div class="mb-6 flex items-start justify-between gap-4 border-b border-slate-100 pb-4">
                    <div><h2 class="text-2xl font-bold">Create company article</h2><p class="mt-1 text-sm text-slate-500">This article is available only to your companyâ€™s IT staff.</p></div>
                    <button type="button" data-close-article-modal class="text-2xl font-bold text-slate-500 hover:text-slate-950" aria-label="Close">&times;</button>
                </div>
                <form method="POST" action="{{ route('client.itsm.service-desk.knowledgebase.store') }}" class="grid gap-5 md:grid-cols-2">
                    @csrf
                    <label class="block text-sm font-semibold md:col-span-2">Title<input name="title" required value="{{ old('title') }}" class="mt-1.5 h-10 w-full rounded border border-slate-300 px-3 font-normal"></label>
                    <label class="block text-sm font-semibold">Category<input name="category" required value="{{ old('category') }}" placeholder="e.g. Internal process" class="mt-1.5 h-10 w-full rounded border border-slate-300 px-3 font-normal"></label>
                    <label class="block text-sm font-semibold">Target module<input name="target_module" value="{{ old('target_module') }}" placeholder="Optional" class="mt-1.5 h-10 w-full rounded border border-slate-300 px-3 font-normal"></label>
                    <label class="block text-sm font-semibold md:col-span-2">Article content<textarea name="content" required rows="8" class="mt-1.5 w-full rounded border border-slate-300 px-3 py-2 font-normal">{{ old('content') }}</textarea></label>
                    <div class="flex justify-end gap-3 md:col-span-2"><button type="button" data-close-article-modal class="rounded-md border border-slate-300 px-5 py-2 font-semibold hover:bg-slate-100">Cancel</button><button type="submit" class="rounded-md bg-[#346DCB] px-5 py-2 font-semibold text-white hover:bg-[#2554a3]">Publish article</button></div>
                </form>
            </div>
        </div>
    </div>
    <script>
        const articleModal = document.getElementById('articleModal');
        const openArticleModal = () => { articleModal.classList.remove('hidden'); articleModal.classList.add('flex'); };
        const closeArticleModal = () => { articleModal.classList.add('hidden'); articleModal.classList.remove('flex'); };
        document.querySelector('[data-open-article-modal]')?.addEventListener('click', openArticleModal);
        document.querySelectorAll('[data-close-article-modal]').forEach((button) => button.addEventListener('click', closeArticleModal));
        articleModal?.addEventListener('click', (event) => { if (event.target === articleModal) closeArticleModal(); });
        @if ($errors->any()) openArticleModal(); @endif
    </script>
</body>
</html>
