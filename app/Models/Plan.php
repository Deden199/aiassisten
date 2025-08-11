<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model {
    use HasUuids; protected $fillable=['code','features','is_active'];
    protected $casts=['features'=>'array'];
    public function prices(){ return $this->hasMany(Price::class); }
}