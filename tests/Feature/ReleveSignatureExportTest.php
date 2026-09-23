<?php

namespace Tests\Feature;

use App\Models\{Entreprise, Role, User};
use App\Services\ReportSignatureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReleveSignatureExportTest extends TestCase
{
    use RefreshDatabase;

    private function context(): Entreprise
    {
        Storage::fake('public');
        $users=[];
        foreach (['Admin','Gérant','Chargé des finances'] as $index=>$designation) {
            $role=Role::firstOrCreate(['designation'=>$designation]);
            $users[]=User::create(['nom'=>'Nom'.$index,'prenom'=>'Prénom'.$index,'email'=>'signatures'.$index.'@test.local','password'=>bcrypt('password'),'role_id'=>$role->id,'statut'=>'Actif','password_default'=>0]);
        }
        $company=Entreprise::create(['user_id'=>$users[0]->id,'nom_entreprise'=>'Entreprise Test']);
        $company->update(['cachet'=>UploadedFile::fake()->image('cachet.png',140,75)->store('cachets','public')]);
        foreach ([$users[1],$users[2]] as $user) $user->update(['signature'=>UploadedFile::fake()->image('signature.png',140,75)->store('signatures','public')]);
        $this->actingAs($users[0]);
        return $company;
    }

    public function test_excel_contains_names_and_all_three_embedded_images(): void
    {
        $company=$this->context();
        $response=$this->get(route('exports.periode',['rapport'=>'releve','format'=>'excel','date_debut'=>'2026-09-01','date_fin'=>'2026-09-18']));
        $response->assertOk()->assertSee('Le gérant')->assertSee('Le chargé des finances')->assertSee('Cachet de l’entreprise')
            ->assertSee('Nom1 Prénom1')->assertSee('Nom2 Prénom2')->assertSee('MIME-Version: 1.0');
        $signatures=app(ReportSignatureService::class)->forCompany($company);
        foreach ($signatures as $item) {
            $response->assertSee('Content-Location: file:///'.$item['image']['name'],false)
                ->assertSee('src="file:///'.$item['image']['name'].'"',false)
                ->assertSee(trim(chunk_split($item['image']['base64'],76,"\r\n")),false);
        }
    }

    public function test_pdf_signature_view_embeds_images_and_pdf_download_works(): void
    {
        $company=$this->context();
        $signatures=app(ReportSignatureService::class)->forCompany($company);
        $html=view('exports._releve_signatures',['signaturesReleve'=>$signatures])->render();
        $this->assertStringContainsString('Nom1 Prénom1',$html);
        $this->assertStringContainsString('Nom2 Prénom2',$html);
        foreach ($signatures as $item) $this->assertStringContainsString('data:image/png;base64,'.$item['image']['base64'],$html);
        $response=$this->get(route('exports.periode',['rapport'=>'releve','format'=>'pdf','date_debut'=>'2026-09-01','date_fin'=>'2026-09-18']));
        $response->assertOk()->assertHeader('content-type','application/pdf');
        $this->assertStringStartsWith('%PDF',$response->getContent());
    }

    public function test_missing_signatures_do_not_prevent_export(): void
    {
        $this->context();
        User::whereHas('role',fn($q)=>$q->where('designation','Gérant'))->update(['signature'=>null]);
        $this->get(route('exports.periode',['rapport'=>'releve','format'=>'excel','date_debut'=>'2026-09-01','date_fin'=>'2026-09-18']))
            ->assertOk()->assertSee('Nom1 Prénom1')->assertSee('Signature non renseignée');
    }

    public function test_bilan_and_resultat_include_signatures_in_excel_and_both_pdf_downloads(): void
    {
        $this->context();
        $period=['date_debut'=>'2026-09-01','date_fin'=>'2026-09-18'];
        foreach (['bilan','compte-resultat'] as $report) {
            $this->get(route('exports.periode', $period+['rapport'=>$report,'format'=>'excel']))
                ->assertOk()->assertSee('Nom1 Prénom1')->assertSee('Nom2 Prénom2')
                ->assertSee('Content-Location: file:///signature-gerant.png',false)
                ->assertSee('Content-Location: file:///signature-finances.png',false);
            $this->get(route('exports.periode', $period+['rapport'=>$report,'format'=>'pdf']))
                ->assertOk()->assertHeader('content-type','application/pdf');
            $this->get(route('comptabilite.etats-financiers.'.$report.'-pdf',$period))
                ->assertOk()->assertHeader('content-type','application/pdf');
        }
    }
}
