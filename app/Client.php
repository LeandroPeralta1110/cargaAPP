<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Client
 *
 * @property $client_id
 * @property $razon_social
 * @property $telefono
 * @property $email
 * @property $created_at
 * @property $updated_at
 *
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Client extends Model
{
  protected $primaryKey = 'client_id';
    static $rules = [
		'client_id' => 'required',
		'razon_social' => 'required',
		'telefono' => 'required',
		'email' => 'required',
    ];

    protected $perPage = 20;

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['client_id','razon_social','telefono','email'];



}
