<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use MongoDB\BSON\Regex;

abstract class BaseController
{
    /**
     * La classe model concreta (es: \App\Models\Post::class)
     */
    protected string $modelClass;

    /**
     * Regole validazione per store/update
     * Override nei controller concreti.
     */
    protected array $storeRules = [];
    protected array $updateRules = [];
    protected array $searchable = [];

    protected array $exactSearchable = [];


    // Campi restituiti in lista e dettaglio (vuoti = tutti)
    protected array $indexFields  = [];
    protected array $detailFields = [];


    /**
     * Lista campi ammessi in scrittura (whitelist).
     * Se vuota: usa fillable del model.
     */
    protected array $writeFields = [];

    public function index(Request $request)
    {
        $perPage = max(1, min((int) $request->query('per_page', 15), 100));
        $page    = max(1, (int) $request->query('page', 1));

        $query = $this->newModelQuery()
            ->select($this->selectFieldsFor('index'));

        // 🔎 ricerca globale server-side: ?q=term
        $q = trim((string) $request->query('q', ''));
        if ($q !== '' && !empty($this->searchable)) {
            $regex = new Regex(preg_quote($q), 'i');

            $query->where(function ($subQuery) use ($regex) {
                foreach ($this->searchable as $index => $field) {
                    if ($index === 0) {
                        $subQuery->where($field, 'regex', $regex);
                    } else {
                        $subQuery->orWhere($field, 'regex', $regex);
                    }
                }
            });
        }

        // 🔤 ricerca testuale (anche su campi annidati, es: data.personal.lastName)
        foreach ($this->searchable as $field) {
            if ($this->hasQueryKey($request, $field)) {
                $value = (string) $this->getQueryValue($request, $field);

                $query->where(
                    $field,
                    'regex',
                    new Regex(preg_quote($value), 'i')
                );
            }
        }

        // 🔢 ricerca esatta (anche su campi annidati, es: data.personal.mobile)
        foreach ($this->exactSearchable as $field) {
            if (in_array($field, $this->searchable, true)) {
                continue;
            }

            if ($this->hasQueryKey($request, $field)) {
                $value = $this->getQueryValue($request, $field);

                // cast opzionale per status numerico legacy
                if ($field === 'status' && is_numeric($value)) {
                    $value = (int) $value;
                }

                $query->where($field, $value);
            }
        }

        $paginator = $query
            ->orderBy('_id', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page'  => $paginator->currentPage(),
                'per_page'      => $paginator->perPage(),
                'total'         => $paginator->total(),
                'last_page'     => $paginator->lastPage(),
                'from'          => $paginator->firstItem(),
                'to'            => $paginator->lastItem(),
                'next_page_url' => $paginator->hasMorePages() ? $paginator->nextPageUrl() : null,
            ],
        ], 200);
    }

    public function store(Request $request)
    {
        $this->validateRequest($request, $this->storeRules);

        $model = $this->newModelInstance();
        $model->fill($this->onlyWritable($request));
        $model->save();

        return response()->json([
            'message' => 'Created successfully!',
            'data' => $model
        ], 201);
    }

    public function show(string $id)
    {
        $model = $this->newModelQuery()
            ->select($this->selectFieldsFor('detail'))
            ->find($id);

        if (!$model) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json($model, 200);
    }

    public function update(Request $request, string $id)
    {
        $model = $this->newModelQuery()->find($id);

        if (!$model) {
            return response()->json(['message' => 'Not found'], 404);
        }

        // se non hai regole update dedicate, puoi usare storeRules “rilassate”
        $rules = $this->updateRules ?: $this->storeRules;
        if (!empty($rules)) {
            $this->validateRequest($request, $rules, true);
        }

        $model->fill($this->onlyWritable($request));
        $model->save();

        return response()->json([
            'message' => 'Updated successfully!',
            'data' => $model
        ], 200);
    }

    public function destroy(string $id)
    {
        $model = $this->newModelQuery()->find($id);

        if (!$model) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $model->delete();

        return response()->json(['message' => 'Deleted successfully!'], 200);
    }

    public function random()
    {
        // Base random “vuoto”: override nei controller concreti se serve davvero
        return response()->json(['message' => 'Not implemented'], 501);
    }

    // ----------------- helpers -----------------

    protected function newModelInstance()
    {
        return new $this->modelClass();
    }

    protected function newModelQuery()
    {
        return (new $this->modelClass())->newQuery();
    }

    protected function validateRequest(Request $request, array $rules, bool $partial = false): void
    {
        if (empty($rules)) return;

        // Se partial update: rendi tutte le regole "sometimes"
        if ($partial) {
            foreach ($rules as $field => $rule) {
                $rules[$field] = is_array($rule)
                    ? array_merge(['sometimes'], $rule)
                    : 'sometimes|' . $rule;
            }
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            abort(response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422));
        }
    }

    protected function onlyWritable(Request $request): array
    {
        if (!empty($this->writeFields)) {
            return $request->only($this->writeFields);
        }

        // fallback: fillable del model
        $model = $this->newModelInstance();
        return $request->only($model->getFillable());
    }


    protected function selectFieldsFor(string $context): array
    {
        $fields = $context === 'index' ? $this->indexFields : $this->detailFields;

        // Se non specifichi nulla: ritorna tutti i campi
        if (empty($fields)) {
            return ['*'];
        }

        // Mongo usa _id, quindi assicurati che ci sia
        if (!in_array('_id', $fields, true)) {
            $fields[] = '_id';
        }

        return $fields;
    }
    protected function hasQueryKey(Request $request, string $key): bool
    {
        $q = $request->query();

        // 1) chiave esatta
        if (array_key_exists($key, $q) && $q[$key] !== '' && $q[$key] !== null) {
            return true;
        }

        // 2) fallback: PHP trasforma i "." in "_"
        $alt = str_replace('.', '_', $key);
        if (array_key_exists($alt, $q) && $q[$alt] !== '' && $q[$alt] !== null) {
            return true;
        }

        // 3) fallback: bracket notation (data[personal][lastName])
        $val = $request->input($key);
        return $val !== '' && $val !== null;
    }

    protected function getQueryValue(Request $request, string $key, mixed $default = null): mixed
    {
        $q = $request->query();

        if (array_key_exists($key, $q)) {
            return $q[$key];
        }

        $alt = str_replace('.', '_', $key);
        if (array_key_exists($alt, $q)) {
            return $q[$alt];
        }

        $val = $request->input($key);
        return ($val !== null) ? $val : $default;
    }


}
