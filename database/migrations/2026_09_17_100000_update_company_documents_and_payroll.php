<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $employes = DB::table('rh_employes')->orderBy('id')->get();
            $nouveaux = [];
            foreach ($employes as $employe) {
                $matricule = preg_replace('/^(.+)-20\d{2}-(\d{5})$/', '$1-$2', $employe->matricule);
                $cle = $employe->entreprise_id.'|'.$matricule;
                if (isset($nouveaux[$cle])) {
                    throw new RuntimeException('Matricules en conflit après retrait de l’année : '.$matricule);
                }
                $nouveaux[$cle] = true;
            }
            foreach ($employes as $employe) {
                $matricule = preg_replace('/^(.+)-20\d{2}-(\d{5})$/', '$1-$2', $employe->matricule);
                if ($matricule !== $employe->matricule) DB::table('rh_employes')->where('id', $employe->id)->update(['matricule' => $matricule]);
            }
        });
        Schema::table('users', fn (Blueprint $table) => $table->string('postnom', 120)->nullable());
        Schema::table('rh_paies', fn (Blueprint $table) => $table->decimal('avance_salaire', 15, 2)->default(0));
        foreach (DB::table('rh_employes')->whereNotNull('user_id')->whereNotNull('postnom')->get() as $employe) {
            DB::table('users')->where('id', $employe->user_id)->whereNull('postnom')->update(['postnom' => $employe->postnom]);
        }
    }

    public function down(): void
    {
        Schema::table('rh_paies', fn (Blueprint $table) => $table->dropColumn('avance_salaire'));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('postnom'));
    }
};
