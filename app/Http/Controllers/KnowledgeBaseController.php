<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Article;

class KnowledgeBaseController extends Controller
{
    public function index()
    {
        return view('service.admin-knowledge-base', [
            'articles' => Article::query()->whereNull('company_id')->latest()->get(),
        ]);
    }

    public function store(Request $request)
    {
        // validate input
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'target_module' => 'nullable|string|max:100',
            'author_name' => 'required|string|max:100',
            'content' => 'required|string',
        ]);

        // save to database
        // Older installations created `target_module` as NOT NULL. Persist a
        // useful default instead of letting an optional UI field cause a 500.
        $validated['target_module'] = $validated['target_module'] ?: 'General';
        Article::create($validated + ['company_id' => null]);

        // redirect back with success message
        return redirect()->route('admin.itsm.service-desk.knowledge-base')
            ->with('success', 'Article published successfully!');
    }

    /** Publish a knowledge-base article for the signed-in client only. */
    public function storeClient(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'target_module' => 'nullable|string|max:100',
            'content' => 'required|string',
        ]);

        $companyId = (int) $request->user()->company_id;
        abort_unless($companyId > 0, 403);

        Article::create([
            ...$validated,
            'target_module' => $validated['target_module'] ?: 'General',
            'author_name' => (string) $request->user()->name,
            'company_id' => $companyId,
        ]);

        return redirect()->route('client.itsm.service-desk.knowledgebase')
            ->with('success', 'Article published for your company knowledge base.');
    }
   public function knowledgeBase()
{
    $articles = Article::all(); // returns objects with properties
    return view('service.knowledgebase', compact('articles'));
}


}
