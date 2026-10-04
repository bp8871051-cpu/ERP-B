<?php

namespace App\Services;

use App\Models\KnowledgeBaseArticle;
use App\Models\KnowledgeBaseCategory;
use App\Models\SystemAuditLog;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class KnowledgeBaseService
{
    /**
     * Get paginated knowledge base articles
     */
    public function getArticles(array $filters = [], int $companyId = 1): LengthAwarePaginator
    {
        $query = KnowledgeBaseArticle::where('company_id', $companyId)
            ->with(['category', 'author']);

        if (!empty($filters['search'])) {
            $s = trim($filters['search']);
            $query->where(function ($q) use ($s) {
                $q->where('title', 'like', "%{$s}%")
                    ->orWhere('description', 'like', "%{$s}%")
                    ->orWhere('content', 'like', "%{$s}%");
            });
        }

        if (!empty($filters['category_id']) && $filters['category_id'] !== 'all') {
            $query->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['visibility']) && $filters['visibility'] !== 'all') {
            $query->where('visibility', $filters['visibility']);
        }

        $perPage = min(100, max(5, (int) ($filters['per_page'] ?? 12)));
        return $query->latest('published_at')->paginate($perPage);
    }

    /**
     * Get single article and increment view count
     */
    public function getArticle(int $id, int $companyId = 1): KnowledgeBaseArticle
    {
        $article = KnowledgeBaseArticle::where('company_id', $companyId)
            ->with(['category', 'author'])
            ->findOrFail($id);

        $article->increment('view_count');
        return $article;
    }

    /**
     * Vote on article helpfulness
     */
    public function voteArticle(int $id, bool $isHelpful, int $companyId = 1): KnowledgeBaseArticle
    {
        $article = KnowledgeBaseArticle::where('company_id', $companyId)->findOrFail($id);

        if ($isHelpful) {
            $article->increment('helpful_count');
        } else {
            $article->increment('not_helpful_count');
        }

        return $article;
    }

    /**
     * Get categories with article counts
     */
    public function getCategories(int $companyId = 1)
    {
        return KnowledgeBaseCategory::where('company_id', $companyId)
            ->withCount('articles')
            ->orderBy('sort_order', 'asc')
            ->get();
    }

    /**
     * Store new article
     */
    public function storeArticle(array $data, int $companyId = 1, ?int $userId = null): KnowledgeBaseArticle
    {
        $slug = Str::slug($data['title']) . '-' . rand(100, 999);

        $article = KnowledgeBaseArticle::create([
            'company_id' => $companyId,
            'category_id' => $data['category_id'],
            'author_id' => $userId,
            'title' => $data['title'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'content' => $data['content'],
            'featured_image' => $data['featured_image'] ?? null,
            'status' => $data['status'] ?? 'published',
            'visibility' => $data['visibility'] ?? 'public',
            'tags' => $data['tags'] ?? [],
            'published_at' => Carbon::now(),
        ]);

        SystemAuditLog::log('support', 'create_kb_article', (string) $article->id, null, $article->toArray(), $companyId, $userId);

        return $article->load(['category', 'author']);
    }

    /**
     * Update existing article
     */
    public function updateArticle(int $id, array $data, int $companyId = 1, ?int $userId = null): KnowledgeBaseArticle
    {
        $article = KnowledgeBaseArticle::where('company_id', $companyId)->findOrFail($id);
        $old = $article->toArray();

        $article->update($data);

        SystemAuditLog::log('support', 'update_kb_article', (string) $article->id, $old, $article->toArray(), $companyId, $userId);

        return $article->load(['category', 'author']);
    }

    /**
     * Delete article
     */
    public function deleteArticle(int $id, int $companyId = 1): bool
    {
        $article = KnowledgeBaseArticle::where('company_id', $companyId)->findOrFail($id);
        return $article->delete();
    }
}
