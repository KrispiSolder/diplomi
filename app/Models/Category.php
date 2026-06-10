<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'parent_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('name');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'category_product');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * @return Collection<int, int>
     */
    public function subtreeCategoryIds(): Collection
    {
        $ids = collect([$this->id]);
        foreach ($this->children as $child) {
            $ids = $ids->merge($child->subtreeCategoryIds());
        }

        return $ids->unique()->values();
    }
}
