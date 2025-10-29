<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Catalogo extends Model
{
    use SoftDeletes;

    protected $table = 'catalogos';

    protected $fillable = [
        'catalogo_id',
        'nombre',
        'orden',
        'activo',
    ];

    protected $casts = [
        'catalogo_id' => 'integer',
        'orden' => 'integer',
        'activo' => 'boolean',
    ];

    // === Relaciones ===

    public function padre()
    {
        return $this->belongsTo(Catalogo::class, 'catalogo_id');
    }

    public function hijos()
    {
        return $this->hasMany(Catalogo::class, 'catalogo_id')->orderBy('orden');
    }

    // === Scopes ===

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    // === Orden por defecto ===

    protected static function booted(): void
    {
        static::addGlobalScope('orden', function ($query) {
            $query->orderBy('orden')->orderBy('nombre');
        });
    }
}
