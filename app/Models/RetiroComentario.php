<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Models\Retiro;
use App\Models\User;

class RetiroComentario extends Model
{
    protected $table = 'retiros_comentarios';

    public $timestamps = false; // Solo usamos created_at manualmente, es decir, con apoyo del mySQL/MariaDB

    protected $fillable = [
        'retiro_id',
        'usuario_id',
        'comentario',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * Relación con el retiro al que pertenece el comentario.
     */
    public function retiro(): BelongsTo
    {
        return $this->belongsTo(Retiro::class, 'retiro_id');
    }

    /**
     * Relación con el usuario que hizo el comentario.
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
