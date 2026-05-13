<?php

namespace Modules\Reproductions\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Reproduction extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'uuid',
        'couple_id',
        'date_ponte',
        'nb_oeufs',
        'date_eclosion',
        'nb_pigeonneaux',
        'date_sevrage',
        'statut',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'date_ponte' => 'date',
        'date_eclosion' => 'date',
        'date_sevrage' => 'date',
        'nb_oeufs' => 'integer',
        'nb_pigeonneaux' => 'integer',
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

        static::creating(function ($reproduction) {
            if (empty($reproduction->uuid)) {
                $reproduction->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Relation : Une reproduction appartient à un couple
     */
    public function couple()
    {
        return $this->belongsTo(\Modules\Couples\Models\Couple::class);
    }

    /**
     * Scope : Reproductions en cours (PONTE, ECLOSION, SEVRAGE)
     */
    public function scopeEnCours($query)
    {
        return $query->whereIn('statut', ['PONTE', 'ECLOSION', 'SEVRAGE']);
    }

    /**
     * Scope : Reproductions terminées (ENREGISTRE, ECHEC)
     */
    public function scopeTerminees($query)
    {
        return $query->whereIn('statut', ['ENREGISTRE', 'ECHEC']);
    }

    /**
     * Scope : Reproductions réussies (ENREGISTRE)
     */
    public function scopeReussies($query)
    {
        return $query->where('statut', 'ENREGISTRE');
    }

    /**
     * Scope : Reproductions échouées (ECHEC)
     */
    public function scopeEchouees($query)
    {
        return $query->where('statut', 'ECHEC');
    }

    /**
     * Scope : Reproductions en phase de ponte
     */
    public function scopePonte($query)
    {
        return $query->where('statut', 'PONTE');
    }

    /**
     * Scope : Reproductions en phase d'éclosion
     */
    public function scopeEclosion($query)
    {
        return $query->where('statut', 'ECLOSION');
    }

    /**
     * Scope : Reproductions en phase de sevrage
     */
    public function scopeSevrage($query)
    {
        return $query->where('statut', 'SEVRAGE');
    }
}
