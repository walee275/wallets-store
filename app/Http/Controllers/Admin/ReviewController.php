<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ReviewController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Reviews/Index', [
            'reviews' => Review::query()
                ->with([
                    'product:id,title,slug',
                    'user:id,name,email',
                ])
                ->where('is_approved', false)
                ->latest()
                ->paginate(20),
        ]);
    }

    public function approve(Review $review): RedirectResponse
    {
        $review->update(['is_approved' => true]);

        return back()->with('success', 'Review approved.');
    }

    public function reject(Review $review): RedirectResponse
    {
        $review->delete();

        return back()->with('success', 'Review rejected.');
    }
}
