<?php
namespace App\Http\Requests;
use Illuminate\Validation\Rule;
class UpdateEmployeRequest extends StoreEmployeRequest { public function authorize():bool{return $this->user()?->can('updateEmployees')??false;} public function rules():array{$r=parent::rules();$r['user_id']=['nullable','exists:users,id',Rule::unique('rh_employes','user_id')->ignore($this->route('employe'))];return $r;} }
