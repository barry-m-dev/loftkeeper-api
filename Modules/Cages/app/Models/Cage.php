<?php

namespace Modules\Cages\Models;

use App\Core\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Cage extends Model
{
    use HasFactory, SoftDeletes, BelongsToUser;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'uuid',
        'numero',
        'nom',
        'superficie',
        'statut',
        'user_id',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'superficie' => 'decimal:2',
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

        static::creating(function ($cage) {
            if (empty($cage->uuid)) {
                $cage->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Relation : Une cage appartient à un utilisateur
     */
    public function user()
    {
        return $this->belongsTo(\Modules\Users\Models\User::class);
    }

    /**
     * Relation : Une cage peut contenir un pigeon seul
     */
    public function pigeon()
    {
        return $this->hasOne(\Modules\Pigeons\Models\Pigeon::class);
    }

    /**
     * Relation : Une cage peut contenir un couple
     */
    public function couple()
    {
        return $this->hasOne(\Modules\Couples\Models\Couple::class);
    }

    /**
     * Scope : Cages libres uniquement
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLibres($query)
    {
        return $query->where('statut', 'LIBRE');
    }

    /**
     * Scope : Cages occupées uniquement
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOccupees($query)
    {
        return $query->whereIn('statut', ['OCCUPE_PIGEON', 'OCCUPE_COUPLE']);
    }

    /**
     * Scope : Cages occupées par un pigeon seul
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOccupeesPigeon($query)
    {
        return $query->where('statut', 'OCCUPE_PIGEON');
    }

    /**
     * Scope : Cages occupées par un couple
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOccupeesCouple($query)
    {
        return $query->where('statut', 'OCCUPE_COUPLE');
    }

    /**
     * Vérifie si la cage est libre
     * 
     * @return bool
     */
    public function isLibre(): bool
    {
        return $this->statut === 'LIBRE';
    }

    /**
     * Vérifie si la cage est occupée
     * 
     * @return bool
     */
    public function isOccupee(): bool
    {
        return in_array($this->statut, ['OCCUPE_PIGEON', 'OCCUPE_COUPLE']);
    }

    /**
     * Récupère l'occupant de la cage (pigeon ou couple)
     * 
     * @return \Modules\Pigeons\Models\Pigeon|\Modules\Couples\Models\Couple|null
     */
    public function getOccupant()
    {
        if ($this->statut === 'OCCUPE_PIGEON') {
            return $this->pigeon;
        }

        if ($this->statut === 'OCCUPE_COUPLE') {
            return $this->couple;
        }

        return null;
    }
}
