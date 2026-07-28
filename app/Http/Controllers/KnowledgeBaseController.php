<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Article;

class KnowledgeBaseController extends Controller
{
    public function index()
    {
        return view('service.admin-knowledge-base', [
            'articles' => Article::latest()->get(),
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
        Article::create($validated);

        // redirect back with success message
        return redirect()->route('admin.itsm.service-desk.knowledge-base')
            ->with('success', 'Article published successfully!');
    }
   public function knowledgeBase()
{
    $articles = Article::all(); // returns objects with properties
    return view('service.knowledgebase', compact('articles'));
}


}
