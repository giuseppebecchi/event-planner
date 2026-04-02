# Laravel Mongo API – Base Architecture

Questa codebase implementa una struttura **API-first** per Laravel + MongoDB, pensata per:
- ridurre duplicazioni di codice
- standardizzare CRUD, paginazione e ricerca
- gestire documenti complessi e annidati (tipici di MongoDB)
- mantenere i controller concreti **leggeri e dichiarativi**

L’architettura ruota attorno a due classi base:
- `App\Models\Base`
- `App\Http\Controllers\Api\BaseController`

---

Utente di test
'email' => admin@local.test
'password' => password123


## 1. Base Model (`Base.php`)

Tutti i model MongoDB estendono `Base`.

Responsabilità:
- imposta la connessione MongoDB
- fornisce il comportamento Eloquent comune

Nei model concreti si definiscono:
- `protected $collection` → nome collection Mongo
- `protected $fillable` → campi ammessi in scrittura (mass assignment)

⚠️ **Nota sui cast**
MongoDB restituisce già array/oggetti PHP per i campi annidati.  
Evitare cast tipo:

```php
protected $casts = [
  'client_data' => 'array'
];
```

Questo cast presume JSON string e può causare errori `json_decode()`.

---

## 2. Base Controller (`BaseController.php`)

`BaseController` fornisce un CRUD completo e riutilizzabile:

- `index()` → lista con paginazione + ricerca
- `store()` → creazione con validazione
- `show()` → dettaglio
- `update()` → aggiornamento parziale (`PATCH-like`)
- `destroy()` → eliminazione

Ogni controller concreto deve indicare almeno:

```php
protected string $modelClass = Model::class;
```

---

## 3. Paginazione standard

Supportata via query string:

```http
GET /api/resource?page=2&per_page=25
```

- `per_page` è limitato a `1..100`
- ordinamento default: `_id DESC`

Risposta:
```json
{
  "data": [],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 120,
    "last_page": 8,
    "from": 1,
    "to": 15,
    "next_page_url": "https://example.com/api/resource?page=2&per_page=15"
  }
}
```

`next_page_url` è `null` in ultima pagina.

---

## 4. Selezione campi (Index / Detail)

Per controllare l’output e ridurre payload:

```php
protected array $indexFields  = ['name', 'status'];
protected array $detailFields = ['name', 'status', 'data', 'created_at'];
```

- `index()` restituisce solo `indexFields`
- `show()` restituisce solo `detailFields`
- `_id` viene aggiunto automaticamente
- se vuoti → restituisce tutti i campi

---

## 5. Scrittura controllata

Campi ammessi in input:
```php
protected array $writeFields = ['name', 'status'];
```

Se non definiti → fallback automatico su `$fillable` del model.

---

## 6. Ricerca e filtri

### 6.1 Ricerca testuale (regex, case-insensitive)

```php
protected array $searchable = [
  'last_name',
  'data.personal.lastName'
];
```

Esempi:
```http
GET /api/customers?last_name=rossi
GET /api/customers?data.personal.lastName=rossi
```

Internamente usa `$regex` MongoDB con flag `i`.

---

### 6.2 Ricerca esatta (match diretto)

```php
protected array $exactSearchable = [
  'status',
  'data.personal.mobile'
];
```

Esempi:
```http
GET /api/customers?status=1
GET /api/customers?data.personal.mobile=3391122334
```

---

## 7. Campi annidati e query string

PHP converte i `.` in `_` nei nomi dei parametri.  
Il BaseController gestisce automaticamente:

- `data.personal.lastName=rossi`
- `data_personal_lastName=rossi`
- `data[personal][lastName]=rossi`

Tutte le forme funzionano senza modifiche lato client.

---

## 8. Validazione

- `storeRules` → validazione in creazione
- `updateRules` → validazione in aggiornamento
- `update()` applica automaticamente `sometimes`

```php
protected array $storeRules = [
  'status' => 'required|string'
];
```

---

## 9. Estendere una nuova risorsa

1. Creare il model:
```php
class Order extends Base {
  protected $collection = 'orders';
  protected $fillable = [...];
}
```

2. Creare il controller:
```php
class OrderController extends BaseController {
  protected string $modelClass = Order::class;
}
```

3. Configurare campi, ricerche e regole

Nessuna logica CRUD duplicata.

---

## 10. Filosofia

- MongoDB è **document-based**, non relazionale
- la struttura privilegia:
  - letture veloci
  - payload controllati
  - filtri flessibili
  - controller sottili
- tutta la complessità resta nel `BaseController`

Questa architettura è pensata per progetti grandi, API pubbliche e backoffice moderni (React / Vue / Next.js).
