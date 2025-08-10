<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Price extends Model { use HasUuids; protected $fillable=['plan_id','currency','amount_cents','is_active']; public function plan(){ return $this->belongsTo(Plan::class);} }