<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

use App\Models\Empresa;

class Conductor extends Model
{
    /**
     * Tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'conductores';
    protected $appends = ['nombre_completo'];  // Esto fuerza que se incluya el accessor en JSON

    /**
     * Atributos que pueden ser asignados masivamente.
     *
     * @var array
     */
    protected $fillable = [
        'empresa_id',
        'rut',
        'nombre',
        'apellido',
        'telefono',
        'activo',
        'observacion_inactividad',
    ];

    /**
     * Método getNombreCompletoAttribute (->nombre_completo) en Conductor para combinar nombre y apellido fácilmente.
     * 
     */
    public function getNombreCompletoAttribute()
    {
        return "{$this->nombre} {$this->apellido}";
    }

    /**
     * Envío de mensaje por Telegram al Conductor
     * 
     */
    public function notificarPorTelegram(string $mensaje, ?int $chatId = null): bool
    {
        $chatId = $chatId ?? $this->telegram_chat_id;

        if (empty($chatId)) {
            return false;
        }

        try {
            $token = config('services.telegram.bot_token');

            Log::info('[Programa Diario] Preparando envío de notificación por telegram a conductor', [
                'token'   => $token,
                'chat_id' => $chatId
            ]);

            $response = Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text'    => $mensaje,
            ]);

            return $response->successful(); // ✅ true si HTTP status 2xx

        } catch (\Throwable $e) {
            Log::warning('[Telegram] No se pudo notificar al conductor vía Telegram', [
                'conductor_id' => $this->id,
                'chat_id'      => $chatId,
                'exception'    => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Relaciones Eloquent de Conductor con otras tablas
     * 
     */
    // Empresa que emplea al conductor
    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id', 'id');          // Un conductor es empleado por una empresa
    }

    /**
     * Todos los vínculos Telegram históricos
     */
    public function telegramLinks()
    {
        return $this->hasMany(TelegramLink::class, 'conductor_id');
    }

    /**
     * Último vínculo activo (estado vinculado)
     */
    public function telegramLinkActivo()
    {
        return $this->hasOne(TelegramLink::class, 'conductor_id')->where('estado', 'vinculado');
    }

}
