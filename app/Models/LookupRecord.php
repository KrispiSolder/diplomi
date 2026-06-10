<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

abstract class LookupRecord extends Model
{
    public $timestamps = false;

    protected $fillable = ['code', 'label', 'description', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public static function idFor(?string $codeOrLabel, ?string $defaultCode = null): ?int
    {
        if ($codeOrLabel === null || $codeOrLabel === '') {
            if ($defaultCode === null) {
                return null;
            }
            $codeOrLabel = $defaultCode;
        }

        return static::query()
            ->where(function ($q) use ($codeOrLabel) {
                $q->where('code', $codeOrLabel)->orWhere('label', $codeOrLabel);
            })
            ->value('id');
    }
}
