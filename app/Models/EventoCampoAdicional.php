<?php

namespace App\Models;

class EventoCampoAdicional extends ModeloCda
{
    protected $table = 'eventos_campos_adicionales';

    protected $casts = [
        'obligatorio' => 'boolean',
        'orden' => 'integer',
        'creado_en' => 'datetime',
    ];

    public function evento()
    {
        return $this->belongsTo(Evento::class, 'evento_id');
    }

    /**
     * @return array<int, string>
     */
    public function opcionesLista(): array
    {
        $crudo = trim((string) $this->opciones);

        if ($crudo === '') {
            return [];
        }

        $json = json_decode($crudo, true);

        if (is_array($json)) {
            return array_values(array_filter(array_map(fn ($item) => trim((string) $item), $json)));
        }

        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\n|\r/', $crudo) ?: [])));
    }
}
