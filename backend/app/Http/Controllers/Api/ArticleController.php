<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleResource;
use App\Models\Article;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    public function index()
    {
        $articles = Article::with(['norm', 'parent', 'children'])
            ->paginate(20);

        return ArticleResource::collection($articles);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'norm_id' => 'required|exists:norms,id',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'order' => 'required|integer',
            'parent_id' => 'nullable|exists:articles,id',
        ]);

        $article = Article::create($validated);

        return new ArticleResource($article->load('norm'));
    }

    public function show(Article $article)
    {
        return new ArticleResource($article->load(['norm', 'parent', 'children']));
    }

    public function update(Request $request, Article $article)
    {
        $validated = $request->validate([
            'norm_id' => 'sometimes|exists:norms,id',
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'order' => 'sometimes|integer',
            'parent_id' => 'nullable|exists:articles,id',
        ]);

        $article->update($validated);

        return new ArticleResource($article->load('norm'));
    }

    public function destroy(Article $article)
    {
        $article->delete();

        return response()->json(null, 204);
    }
}

