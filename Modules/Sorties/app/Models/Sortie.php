<?php

namespace Modules\Sorties\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Sortie extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'uuid',
        'pigeon_id',
        'type',
        'date_sortie',
        'prix',
        'acheteur',
        'cause',
        'circonstance',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'date_sortie' => 'date',
        'prix' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Boot function pour générer UUID automatiquement
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($sortie) {
            if (empty($sortie->uuid)) {
                $sortie->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Relation : Une sortie appartient à un pigeon
     */
    public function pigeon()
    {
        return $this->belongsTo(\Modules\Pigeons\Models\Pigeon::class);
    }

    /**
     * Scope : Sorties de type VENTE
     */
    public function scopeVentes($query)
    {
        return $query->where('type', 'VENTE');
    }

    /**
     * Scope : Sorties de type DECES
     */
    public function scopeDeces($query)
    {
        return $query->where('type', 'DECES');
    }

    /**
     * Scope : Sorties de type PERTE
     */
    public function scopePertes($query)
    {
        return $query->where('type', 'PERTE');
    }

    /**
     * Scope : Sorties avec prix (ventes)
     */
    public function scopeAvecPrix($query)
    {
        return $query->whereNotNull('prix');
    }

    /**
     * Scope : Sorties par période
     */
    public function scopeParPeriode($query, $dateDebut, $dateFin)
    {
        return $query->whereBetween('date_sortie', [$dateDebut, $dateFin]);
    }
}
