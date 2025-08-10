<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AiProject extends Model { use HasUuids; protected $fillable=['tenant_id','user_id','title','source_filename','source_disk','source_path','language','status','error_message']; public function tenant(){return $this->belongsTo(Tenant::class);} public function user(){return $this->belongsTo(User::class);} public function tasks(){return $this->hasMany(AiTask::class,'project_id');}}