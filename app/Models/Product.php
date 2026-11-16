<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * Atributos asignables en masa.
     *
     * @var array<int, string>
     */
    public const STATUS_DRAFT = 'draft';

    /** Publicado: visible en el catálogo público. */
    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUSES = [self::STATUS_DRAFT, self::STATUS_ACTIVE, self::STATUS_INACTIVE];

    protected $fillable = [
        'shop_id',
        'category_id',
        'title',
        'slug',
        'description',
        'base_price',
        'stock',
        'tax_rate',
        'available_from',
        'sku',
        'status',
        'featured',
    ];

    /**
     * Tipos de datos que deben castearse.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'base_price' => 'decimal:2',
            'stock' => 'integer',
            'tax_rate' => 'decimal:2',
            'available_from' => 'date',
            'featured' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relaciones Eloquent
    |--------------------------------------------------------------------------
    */

    /**
     * Taller artesano que elabora la pieza (1:N inverso).
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * Categoría a la que pertenece el producto (1:N inverso).
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Variantes de la pieza: tallas, maderas, acabados o colores (1:N).
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * Galería fotográfica del producto ordenada por sort_order (1:N).
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    /**
     * Imagen principal de portada del producto.
     */
    public function primaryImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    /**
     * Materiales y distintivos de procedencia asociados al producto (N:M).
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'product_tag')
            ->withPivot(['is_primary', 'origin_certified']);
    }

    /**
     * Valoraciones y opiniones de compradores verificados (1:N).
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Carritos de compra donde este producto está añadido (1:N).
     */
    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Líneas de compra registradas con precio congelado (1:N).
     */
    public function orderProducts(): HasMany
    {
        return $this->hasMany(OrderProduct::class);
    }

    /**
     * Pedidos en los que figura este producto (N:M con pivote extendido).
     */
    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class, 'order_product')
            ->using(OrderProduct::class)
            ->withPivot([
                'order_shop_id',
                'product_variant_id',
                'product_title',
                'variant_name',
                'quantity',
                'unit_price',
                'applied_tax',
                'artisan_commission_rate',
                'line_status',
                'customization_notes',
            ])
            ->withTimestamps();
    }

    /**
     * Usuarios que han marcado este producto como favorito en su wishlist (N:M).
     */
    public function wishlistUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'wishlists')->withTimestamps();
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers de dominio
    |--------------------------------------------------------------------------
    */

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Si el producto tiene variantes, el stock vendible es la suma de las variantes;
     * si no, el stock propio del producto.
     */
    public function getAvailableStockAttribute(): int
    {
        return $this->variants()->exists()
            ? (int) $this->variants()->sum('stock')
            : (int) $this->stock;
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope simple: productos publicados (estado active).
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope: lo que se puede mostrar en el catálogo público
     * (producto publicado de un taller verificado).
     */
    public function scopeInCatalog(Builder $query): Builder
    {
        return $query->active()->whereHas('shop', fn (Builder $q) => $q->verified());
    }

    /**
     * Scope complejo: búsqueda de texto en título, descripción, etiquetas,
     * categoría (y su categoría padre) y taller.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        $like = '%'.$term.'%';

        return $query->where(function (Builder $q) use ($like) {
            $q->where('title', 'like', $like)
                ->orWhere('description', 'like', $like)
                ->orWhereHas('tags', fn (Builder $t) => $t->where('name', 'like', $like))
                ->orWhereHas('category', function (Builder $c) use ($like) {
                    $c->where('name', 'like', $like)
                        ->orWhereHas('parent', fn (Builder $p) => $p->where('name', 'like', $like));
                })
                ->orWhereHas('shop', function (Builder $s) use ($like) {
                    $s->where('name', 'like', $like)->orWhere('craft_type', 'like', $like);
                });
        });
    }

    /**
     * Scope complejo: productos más vendidos en un periodo.
     * Suma las unidades de líneas no canceladas de pedidos entregados entre
     * $from y $to, descarta los que no vendieron nada y ordena de mayor a menor.
     * Añade la columna calculada units_sold.
     */
    public function scopeBestSelling(Builder $query, ?\DateTimeInterface $from = null, ?\DateTimeInterface $to = null): Builder
    {
        $soldLines = function (Builder $line) use ($from, $to) {
            $line->where('line_status', '!=', OrderProduct::STATUS_CANCELLED)
                ->whereHas('order', function (Builder $order) use ($from, $to) {
                    $order->where('status', Order::STATUS_DELIVERED)
                        ->when($from, fn (Builder $o) => $o->where('created_at', '>=', $from))
                        ->when($to, fn (Builder $o) => $o->where('created_at', '<=', $to));
                });
        };

        return $query->whereHas('orderProducts', $soldLines)
            ->withSum(['orderProducts as units_sold' => $soldLines], 'quantity')
            ->orderByDesc('units_sold');
    }

    /**
     * Scope complejo: productos mejor valorados.
     * Solo cuenta reseñas aprobadas; exige un mínimo de reseñas y una media
     * mínima. Añade avg_rating y approved_reviews_count, ordenado por media.
     */
    public function scopeTopRated(Builder $query, int $minReviews = 1, float $minRating = 4.0): Builder
    {
        $approved = fn (Builder $r) => $r->where('is_approved', true);

        return $query
            ->whereHas('reviews', $approved, '>=', $minReviews)
            // El umbral va como literal numérico (no como parámetro): SQLite compararía
            // la media (REAL) con un parámetro de texto y nunca se cumpliría.
            ->where(function ($sub) {
                $sub->selectRaw('avg(rating)')
                    ->from('reviews')
                    ->whereColumn('reviews.product_id', 'products.id')
                    ->where('is_approved', true);
            }, '>=', DB::raw(sprintf('%.2F', $minRating)))
            ->withAvg(['reviews as avg_rating' => $approved], 'rating')
            ->withCount(['reviews as approved_reviews_count' => $approved])
            ->orderByDesc('avg_rating');
    }

    /**
     * Scope: productos sin variantes con stock bajo.
     */
    public function scopeLowStock(Builder $query, int $threshold): Builder
    {
        return $query->doesntHave('variants')->where('stock', '<=', $threshold);
    }
}
