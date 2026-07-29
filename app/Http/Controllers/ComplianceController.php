<?php

namespace App\Http\Controllers;

use App\Models\ComplianceItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ComplianceController extends Controller
{
    public function index(Request $request)
    {
        $clientId = $this->clientId($request);
        $items = ComplianceItem::query()->where('company_id', $clientId);

        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $items->where(function ($query) use ($search): void {
                $query->whereRaw('LOWER(title) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(audience) LIKE ?', ["%{$search}%"]);
            });
        }

        if ($request->filled('status')) {
            $items->where('status', $request->status);
        }

        $items = $items->latest()->get()->map(fn (ComplianceItem $item): array => [
            'id' => $item->id,
            'title' => $item->title,
            'audience' => $item->audience,
            'status' => $item->status,
            'progress' => $item->progress.'%',
            'color' => $this->statusColor($item->status),
            'file_path' => $item->file_path,
            'file_url' => $item->file_path ? route('client.itsm.compliance.file', ['compliance' => $item->id]) : null,
        ]);

        return view('compliance', ['requirements' => $items]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'audience' => 'required|string|max:255',
            'status' => 'required|string',
            'course_file' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg|max:10240',
        ]);

        $filePath = $request->hasFile('course_file')
            ? $request->file('course_file')->store('compliance/courses', 'public')
            : null;

        ComplianceItem::create([
            'company_id' => $this->clientId($request),
            'title' => $validated['title'],
            'audience' => $validated['audience'],
            'status' => $validated['status'],
            'progress' => match ($validated['status']) {
                'Completed' => 100,
                'Active' => 50,
                default => 0,
            },
            'file_path' => $filePath,
        ]);

        return redirect()->route('client.itsm.compliance')
                         ->with('success', 'Compliance requirement added successfully!');
    }

    public function file(Request $request, ComplianceItem $compliance)
    {
        abort_unless((int) $compliance->company_id === $this->clientId($request), 404);
        abort_unless($compliance->file_path && Storage::disk('public')->exists($compliance->file_path), 404);

        return $request->boolean('download')
            ? Storage::disk('public')->download($compliance->file_path)
            : Storage::disk('public')->response($compliance->file_path);
    }

    private function clientId(Request $request): int
    {
        $clientId = (int) $request->user()?->company_id;
        abort_unless($clientId > 0, 403);

        return $clientId;
    }

    private function statusColor(string $status): string
    {
        return match ($status) {
            'Active', 'Completed' => 'bg-[#16A34A]',
            'Urgent' => 'bg-[#DC2626]',
            'Pending Review' => 'bg-[#D97706]',
            default => 'bg-slate-600',
        };
    }
}
