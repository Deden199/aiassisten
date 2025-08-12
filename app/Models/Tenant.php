<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model {
    use HasFactory, HasUuids;
    protected $fillable = ['name','slug','default_locale','default_currency','default_timezone','monthly_cost_cap_cents','is_active'];
    public function users(){ return $this->hasMany(User::class); }
    public function projects(){ return $this->hasMany(AiProject::class); }
    public function tasks(){ return $this->hasMany(AiTask::class); }
    public function subscription(){ return $this->hasOne(Subscription::class); }
    public function license(){ return $this->hasOne(License::class); }
}