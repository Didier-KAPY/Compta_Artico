@extends('layouts.app')
@section('title','Scanner QR Code')
@section('module-sidebar') @include('ressources_humaines._sidebar') @endsection
@section('content')
<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div><h2 class="fw-bold mb-1">Scanner QR Code</h2><p class="text-muted mb-0">Pointage automatique des arrivées et départs.</p></div>
        <a href="{{route('parametres.rh.presences')}}" class="btn btn-outline-secondary">Retour aux présences</a>
    </div>
    <div class="row g-4">
        <div class="col-lg-7"><div class="card border-0 shadow-sm"><div class="card-body p-4">
            <div id="qr-reader" class="mx-auto" style="max-width:560px"></div>
            <div id="qr-reader-file" class="d-none"></div>
            <div class="d-grid mt-3">
                <label for="qr-photo" class="btn btn-lg btn-outline-primary"><i class="bi bi-camera-fill me-2"></i>Scanner avec la caméra du téléphone</label>
                <input id="qr-photo" type="file" accept="image/*" capture="environment" class="d-none">
            </div>
            <div id="camera-help" class="alert alert-info mt-3 mb-0"><i class="bi bi-camera me-2"></i>Autorisez l’accès à la caméra. Si la caméra directe est bloquée, utilisez le bouton ci-dessus.</div>
        </div></div></div>
        <div class="col-lg-5"><div id="scan-result" class="card border-0 shadow-sm h-100"><div class="card-body p-4 d-flex flex-column align-items-center justify-content-center text-center"><i class="bi bi-qr-code-scan display-2 text-primary mb-3"></i><h4>En attente d’un QR Code</h4><p class="text-muted">Présentez le code de l’employé devant la caméra.</p></div></div></div>
    </div>
</div>
@endsection
@push('scripts')
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded',()=>{
    const result=document.getElementById('scan-result');
    const help=document.getElementById('camera-help');
    const photoInput=document.getElementById('qr-photo');
    const resultDisplayDuration=8000;
    let busy=false,lastCode='',lastAt=0;
    const esc=value=>String(value??'').replace(/[&<>'"]/g,char=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[char]));
    const message=(title,text,color='danger')=>{result.innerHTML=`<div class="card-body p-4 d-flex flex-column align-items-center justify-content-center text-center"><i class="bi bi-${color==='danger'?'x-circle':'camera'} display-2 text-${color} mb-3"></i><h4 class="text-${color}">${esc(title)}</h4><p>${esc(text)}</p></div>`};

    async function submit(code){
        const now=Date.now();
        if(busy||(code===lastCode&&now-lastAt<10000))return;
        busy=true;lastCode=code;lastAt=now;
        try{
            const response=await fetch(@json(route('parametres.rh.presences.scan')),{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':@json(csrf_token())},body:JSON.stringify({code})});
            const data=await response.json();
            if(!response.ok)throw new Error(data.message||'Pointage refusé.');
            const e=data.employe;
            result.innerHTML=`<div class="card-body p-4 text-center"><div class="display-6 fw-bold text-success mb-3">${esc(data.message)}</div>${e.photo?`<img src="${esc(e.photo)}" class="rounded-circle object-fit-cover mb-3" width="110" height="110" alt="Photo">`:'<i class="bi bi-person-circle display-2 text-secondary"></i>'}<h3>${esc(e.nom)}</h3><p class="mb-1">${esc(e.matricule)} — ${esc(e.departement)}</p><div class="fs-2 fw-bold">${esc(data.heure)}</div><p class="text-muted">${esc(data.date)}</p>${data.retard_minutes?`<span class="badge bg-warning text-dark">Retard : ${data.retard_minutes} min</span>`:''}${data.depart_anticipe_minutes?` <span class="badge bg-warning text-dark">Départ anticipé : ${data.depart_anticipe_minutes} min</span>`:''}</div>`;
            setTimeout(()=>message('Prêt pour le prochain employé','Présentez le prochain QR Code.','primary'),resultDisplayDuration);
        }catch(error){message('Pointage refusé',error.message)}finally{setTimeout(()=>busy=false,1500)}
    }

    if(typeof Html5QrcodeScanner==='undefined'){
        help.className='alert alert-danger mt-3 mb-0';
        help.textContent='Le lecteur QR n’a pas pu être chargé. Vérifiez la connexion Internet du téléphone puis rechargez la page.';
        photoInput.disabled=true;
        return;
    }

    if(!window.isSecureContext){
        help.className='alert alert-warning mt-3 mb-0';
        help.innerHTML='<i class="bi bi-shield-exclamation me-2"></i>La caméra directe est bloquée car cette page utilise HTTP. Utilisez « Scanner avec la caméra du téléphone » ou ouvrez le site en HTTPS.';
    }

    const scanner=new Html5QrcodeScanner('qr-reader',{fps:10,qrbox:(width,height)=>{const size=Math.floor(Math.min(width,height)*.72);return {width:size,height:size}},rememberLastUsedCamera:true,supportedScanTypes:[Html5QrcodeScanType.SCAN_TYPE_CAMERA],videoConstraints:{facingMode:{ideal:'environment'}}},false);
    scanner.render(submit,error=>{});

    const fileScanner=new Html5Qrcode('qr-reader-file');
    photoInput.addEventListener('change',async()=>{
        const file=photoInput.files?.[0];
        if(!file)return;
        message('Lecture en cours','Analyse du code QR photographié…','primary');
        try{await submit(await fileScanner.scanFile(file,true))}catch(error){message('QR Code non détecté','Rapprochez le téléphone du QR Code, assurez-vous que l’image est nette, puis recommencez.')}finally{photoInput.value=''}
    });
});
</script>
@endpush
