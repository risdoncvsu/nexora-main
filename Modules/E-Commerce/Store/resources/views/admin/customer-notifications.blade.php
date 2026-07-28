@extends('ecommerce::admin.layout')

@section('content')
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Customer notifications</h1>
            <p class="mt-1 text-sm text-slate-600">Publish a store announcement for customers of this client only.</p>
        </div>

        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('ecommerce.admin.customer-notifications.store') }}" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            <div class="grid gap-4 md:grid-cols-2">
                <label class="block text-sm font-medium text-slate-700">Title
                    <input name="title" value="{{ old('title') }}" required maxlength="255" class="mt-1 block w-full rounded-lg border-slate-300" />
                </label>
                <label class="block text-sm font-medium text-slate-700">Link (optional)
                    <input name="link" value="{{ old('link') }}" maxlength="500" class="mt-1 block w-full rounded-lg border-slate-300" placeholder="/collections" />
                </label>
            </div>
            <label class="mt-4 block text-sm font-medium text-slate-700">Message
                <textarea name="body" rows="3" maxlength="5000" class="mt-1 block w-full rounded-lg border-slate-300">{{ old('body') }}</textarea>
            </label>
            <button class="mt-4 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Publish notification</button>
        </form>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-slate-600"><tr><th class="px-5 py-3">Notification</th><th class="px-5 py-3">Published</th><th class="px-5 py-3"></th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($notifications as $notification)
                        <tr><td class="px-5 py-4"><div class="font-semibold text-slate-900">{{ $notification->title }}</div><div class="mt-1 text-slate-600">{{ $notification->body }}</div></td><td class="px-5 py-4 text-slate-600">{{ $notification->created_at?->format('M j, Y g:i A') }}</td><td class="px-5 py-4 text-right"><form method="POST" action="{{ route('ecommerce.admin.customer-notifications.destroy', $notification->id) }}">@csrf @method('DELETE')<button class="font-medium text-red-600">Remove</button></form></td></tr>
                    @empty
                        <tr><td colspan="3" class="px-5 py-8 text-center text-slate-500">No customer notifications have been published.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $notifications->links() }}
    </div>
@endsection
