<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Article;
use App\Models\ServiceTicket;
use Illuminate\Support\Facades\Auth;


class ServiceController extends Controller
{

public function resolvedTickets()
{
    $resolvedTickets = ServiceTicket::query()
        ->where('company_id', Auth::user()?->company_id)
        ->whereIn('status', ['Resolved', 'Closed'])
        ->latest()
        ->get();

    return view('service.resolvedtickets', compact('resolvedTickets'));
}


public function knowledgeBase()
{
    $articles = Article::latest()->get();

    return view('service.knowledgebase', compact('articles'));
}



}
