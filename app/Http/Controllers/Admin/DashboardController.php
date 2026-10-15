<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Story;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', ['stories' => Story::count(), 'published' => Story::published()->count(), 'pending' => Comment::where('status', 'pending')->count(), 'users' => User::count(), 'recentStories' => Story::latest()->limit(6)->get()]);
    }
}
