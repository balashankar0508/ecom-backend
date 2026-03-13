<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Review;

class ReviewController extends Controller
{
    public function store(Request $request, Product $product)
    {
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $review = Review::updateOrCreate(
            ['product_id' => $product->id, 'user_id' => $user->id],
            [
                'rating' => $validated['rating'],
                'comment' => $validated['comment'] ?? null,
            ]
        );

        return response()->json([
            'message' => 'Review submitted successfully',
            'review' => $review->load('user:id,name') // Load the user's name to send back
        ], 201);
    }

    public function destroy(Request $request, Review $review)
    {
        $user = $request->user();

        if (!$user || $user->id !== $review->user_id) {
            return response()->json(['error' => 'Unauthorized or review does not belong to you'], 403);
        }

        $review->delete();

        return response()->json([
            'message' => 'Review deleted successfully'
        ], 200);
    }
}
