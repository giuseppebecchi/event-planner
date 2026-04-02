<?php

namespace App\Http\Controllers\Api;

use App\Models\Post;

class PostController extends BaseController
{
    protected string $modelClass = Post::class;

    protected array $searchable = [
        'title',
    ];

    protected array $storeRules = [
        'title' => 'required|string|max:255',
        'body'  => 'required|string',
        // unique su Mongo: con mongodb/laravel spesso funziona, ma in alcuni casi conviene gestirlo con indice unico DB
        'slug'  => 'required|string|unique:posts,slug',
        //'logo'  => 'nullable|string',
    ];

    protected array $updateRules = [
        'title' => 'string|max:255',
        'body'  => 'string',
        'slug'  => 'string',
        //'logo'  => 'nullable|string',
    ];

    protected array $indexFields = ['title', 'slug', 'logo'];          // + _id aggiunto automaticamente
    protected array $detailFields = ['title', 'slug', 'logo', 'body']; // + _id


    public function random()
    {
        $post = Post::create([
            'title' => 'Random Title ' . rand(1, 1000),
            'body'  => 'This is a random body content for post #' . rand(1, 1000),
            'slug'  => 'random-post-' . rand(1, 1000),
        ]);

        return response()->json(['message' => 'Random post created successfully!', 'data' => $post], 201);
    }
}
