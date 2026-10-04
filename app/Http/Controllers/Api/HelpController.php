<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HelpDocumentCategory;
use App\Models\HelpDocument;
use App\Models\HelpChangelog;

class HelpController extends Controller
{
    /**
     * Get list of documentation categories and articles
     */
    public function getDocumentation(Request $request)
    {
        $search = $request->query('search');
        $categorySlug = $request->query('category');

        $categoriesQuery = HelpDocumentCategory::query()->orderBy('sort_order', 'asc');

        if ($categorySlug && $categorySlug !== 'all') {
            $categoriesQuery->where('slug', $categorySlug);
        }

        $categories = $categoriesQuery->with(['documents' => function ($query) use ($search) {
            $query->where('status', 'published')->orderBy('sort_order', 'asc');
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('excerpt', 'like', "%{$search}%")
                      ->orWhere('content', 'like', "%{$search}%");
                });
            }
        }])->get();

        // Count total matching articles
        $totalArticles = $categories->sum(fn($cat) => $cat->documents->count());

        return response()->json([
            'status' => 'success',
            'data' => [
                'categories' => $categories,
                'total_articles' => $totalArticles,
            ]
        ]);
    }

    /**
     * Get a specific documentation article by slug
     */
    public function getDocument(string $slug)
    {
        $doc = HelpDocument::with('category')->where('slug', $slug)->where('status', 'published')->first();

        if (!$doc) {
            return response()->json([
                'status' => 'error',
                'message' => 'Document not found.'
            ], 404);
        }

        // Get adjacent articles for previous/next navigation
        $prevDoc = HelpDocument::where('category_id', $doc->category_id)
            ->where('sort_order', '<', $doc->sort_order)
            ->where('status', 'published')
            ->orderBy('sort_order', 'desc')
            ->first(['id', 'title', 'slug']);

        $nextDoc = HelpDocument::where('category_id', $doc->category_id)
            ->where('sort_order', '>', $doc->sort_order)
            ->where('status', 'published')
            ->orderBy('sort_order', 'asc')
            ->first(['id', 'title', 'slug']);

        return response()->json([
            'status' => 'success',
            'data' => [
                'document' => $doc,
                'previous' => $prevDoc,
                'next' => $nextDoc,
            ]
        ]);
    }

    /**
     * Get changelog timeline
     */
    public function getChangelog(Request $request)
    {
        $search = $request->query('search');
        $tag = $request->query('tag');

        $query = HelpChangelog::where('status', 'published')->orderBy('release_date', 'desc');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('version', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($tag && $tag !== 'all') {
            $query->where('tag', $tag);
        }

        $changelogs = $query->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'releases' => $changelogs,
                'total' => $changelogs->count(),
            ]
        ]);
    }

    /**
     * Get single changelog entry
     */
    public function getChangelogItem(int $id)
    {
        $item = HelpChangelog::find($id);

        if (!$item) {
            return response()->json([
                'status' => 'error',
                'message' => 'Changelog entry not found.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $item
        ]);
    }
}
