<x-service-desk-admin-layout
    title="Knowledge Base"
    subtitle="Publish reusable solutions so support requests can be resolved consistently."
    section="knowledge"
>
    <section class="rounded-[1.875rem] bg-white p-6 text-slate-950 sm:p-8">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold">Published articles</h2>
                <p class="mt-1 text-sm text-slate-500">Create and maintain reusable support guidance for all client companies.</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="rounded-full bg-blue-50 px-4 py-2 text-sm font-bold text-[#346DCB]">{{ $articles->count() }} articles</span>
                <button type="button" data-open-article-modal class="rounded-full bg-[#346DCB] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#2554a3]">Publish article</button>
            </div>
        </div>

        <div class="space-y-4">
            @forelse ($articles as $article)
                <article class="rounded-2xl border border-slate-200 p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-bold">{{ $article->title }}</h3>
                            <p class="mt-1 text-sm text-slate-500">{{ $article->category }} &middot; {{ $article->target_module ?: 'General' }} &middot; {{ $article->author_name }}</p>
                        </div>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ $article->created_at->format('M j, Y') }}</span>
                    </div>
                    <p class="mt-4 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $article->content ?: 'No article content was provided.' }}</p>
                </article>
            @empty
                <div class="py-12 text-center text-slate-500">No knowledge-base articles have been published yet.</div>
            @endforelse
        </div>
    </section>

    <div id="articleModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
        <div class="max-h-[92vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-6 text-slate-950 shadow-2xl sm:p-8">
            <div class="mb-6 flex items-center justify-between gap-4">
                <div><h2 class="text-2xl font-bold">Publish an article</h2><p class="mt-1 text-sm text-slate-500">Make a reusable solution available to client support teams.</p></div>
                <button type="button" data-close-article-modal class="text-2xl font-bold text-slate-500 hover:text-slate-950" aria-label="Close">&times;</button>
            </div>
            <form method="POST" action="{{ route('admin.itsm.service-desk.knowledge-base.store') }}" class="grid gap-5 md:grid-cols-2">
                @csrf
                <label class="block text-sm font-semibold md:col-span-2">Title<input name="title" required value="{{ old('title') }}" class="mt-1.5 h-10 w-full rounded border border-slate-300 px-3 font-normal"></label>
                <label class="block text-sm font-semibold">Category<input name="category" required value="{{ old('category') }}" placeholder="e.g. Account access" class="mt-1.5 h-10 w-full rounded border border-slate-300 px-3 font-normal"></label>
                <label class="block text-sm font-semibold">Target module<input name="target_module" value="{{ old('target_module') }}" placeholder="Optional" class="mt-1.5 h-10 w-full rounded border border-slate-300 px-3 font-normal"></label>
                <label class="block text-sm font-semibold md:col-span-2">Author<input name="author_name" required value="{{ old('author_name', auth()->user()->name) }}" class="mt-1.5 h-10 w-full rounded border border-slate-300 px-3 font-normal"></label>
                <label class="block text-sm font-semibold md:col-span-2">Article content<textarea name="content" required rows="8" class="mt-1.5 w-full rounded border border-slate-300 px-3 py-2 font-normal">{{ old('content') }}</textarea></label>
                <div class="flex justify-end gap-3 md:col-span-2"><button type="button" data-close-article-modal class="rounded-md border border-slate-300 px-5 py-2 font-semibold hover:bg-slate-100">Cancel</button><button type="submit" class="rounded-md bg-[#346DCB] px-5 py-2 font-semibold text-white hover:bg-[#2554a3]">Publish article</button></div>
            </form>
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
</x-service-desk-admin-layout>
