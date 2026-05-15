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

        // Appliquer un scope global pour ne voir que les reproductions de ses propres couples
        if (auth()->check()) {
            static::addGlobalScope('user_reproductions', function (\Illuminate\Database\Eloquent\Builder $builder) {
                $builder->whereHas('couple');
            });
        }
    }

    /**
     * Relation : Une reproduction appartient à un couple
     * withoutGlobalScopes() pour voir le couple même s'il appartient à un autre user
     */
    public function couple()
    {
        return $this->belongsTo(\Modules\Couples\Models\Couple::class)->withTrashed();
    }

    /**
     * Relation : Les pigeonneaux issus de cette reproduction
     * (via les parents du couple)
     */
    public function pigeonneaux()
    {
        if (!$this->couple) {
            return collect([]);
        }

        return \Modules\Pigeons\Models\Pigeon::where('pere_id', $this->couple->male_id)
            ->where('mere_id', $this->couple->femelle_id)
            ->where('date_naissance', $this->date_eclosion)
            ->get();
    }

    /**
     * Accesseur : La reproduction est-elle modifiable ?
     * RG-R18 : Modification interdite si ENREGISTRE ou ECHEC
     */
    public function getIsModifiableAttribute(): bool
    {
        return !in_array($this->statut, ['ENREGISTRE', 'ECHEC']);
    }

    /**
     * Accesseur : La reproduction est-elle supprimable ?
     * RG-R21 : Suppression interdite si ENREGISTRE
     */
    public function getIsDeletableAttribute(): bool
    {
        return $this->statut !== 'ENREGISTRE';
    }

    /**
     * Scope : Reproductions en cours (PONTE, ECLOSION, SEVRAGE)
     */
    public function scopeEnCours(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->whereIn('statut', ['PONTE', 'ECLOSION', 'SEVRAGE']);
    }

    /**
     * Scope : Reproductions terminées (ENREGISTRE, ECHEC)
     */
    public function scopeTerminees(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->whereIn('statut', ['ENREGISTRE', 'ECHEC']);
    }

    /**
     * Scope : Reproductions réussies (ENREGISTRE)
     */
    public function scopeReussies(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where('statut', 'ENREGISTRE');
    }

    /**
     * Scope : Reproductions échouées (ECHEC)
     */
    public function scopeEchouees(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where('statut', 'ECHEC');
    }

    /**
     * Scope : Reproductions en phase de ponte
     */
    public function scopePonte(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where('statut', 'PONTE');
    }

    /**
     * Scope : Reproductions en phase d'éclosion
     */
    public function scopeEclosion(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where('statut', 'ECLOSION');
    }

    /**
     * Scope : Reproductions en phase de sevrage
     */
    public function scopeSevrage(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where('statut', 'SEVRAGE');
    }
}
