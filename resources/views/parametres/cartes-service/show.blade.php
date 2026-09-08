@extends('layouts.app')
@section('module-sidebar') @include('ressources_humaines._sidebar') @endsection

@section('title', 'Aperçu de la carte')

@section('content')
<div class="container py-4">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div><h2 class="fw-bold mb-1">Aperçu de la carte</h2><p class="text-muted mb-0">Format PVC portrait : 53,98 × 85,60 mm.</p></div>
        <div class="d-flex flex-wrap gap-2"><a href="{{ route('parametres.cartes-service.index') }}" class="btn btn-outline-secondary">Retour</a><a href="{{ route('parametres.cartes-service.edit', $carteService) }}" class="btn btn-outline-primary"><i class="bi bi-pencil me-1"></i>Modifier</a><button onclick="window.print()" class="btn btn-primary"><i class="bi bi-printer me-1"></i>Imprimer</button><div class="dropdown"><button class="btn btn-success dropdown-toggle" type="button" data-bs-toggle="dropdown"><i class="bi bi-file-earmark-image me-1"></i>JPEG</button><ul class="dropdown-menu"><li><a id="download-card-jpeg-front" class="dropdown-item" href="#" data-no-loading><i class="bi bi-download me-2"></i>Télécharger le recto</a></li><li><a id="download-card-jpeg-back" class="dropdown-item" href="#" data-no-loading><i class="bi bi-download me-2"></i>Télécharger le verso</a></li></ul></div><a id="download-card-pdf" href="{{ route('parametres.cartes-service.pdf', $carteService) }}" data-filename="carte-service-{{$carteService->numero}}.pdf" data-no-loading class="btn btn-danger"><i class="bi bi-file-earmark-pdf me-1"></i>PDF identique à l’aperçu</a></div>
    </div>
    @include('parametres.cartes-service.carte')
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.2/dist/jspdf.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded',()=>{
    const button=document.getElementById('download-card-pdf');
    const cards=[...document.querySelectorAll('.service-card')];
    if(!button||!cards.length)return;

    const prepareImages=async root=>{
        const images=[...root.querySelectorAll('img')];
        await Promise.all(images.map(async img=>{
            if(!img.complete)await new Promise(resolve=>{img.addEventListener('load',resolve,{once:true});img.addEventListener('error',resolve,{once:true})});
            if(typeof img.decode==='function')await img.decode().catch(()=>{});
        }));
    };
    const capture=async card=>{
        await prepareImages(card);
        const photo=card.querySelector('img.sc-photo');
        const photoRect=photo?.getBoundingClientRect();
        {
            const result=await html2canvas(card,{scale:4,useCORS:true,allowTaint:false,imageTimeout:15000,backgroundColor:'#ffffff',logging:false,width:card.offsetWidth,height:card.offsetHeight,onclone:documentClone=>{documentClone.querySelectorAll('.service-card img,.service-card canvas').forEach(image=>{image.style.visibility='visible';image.style.opacity='1';image.style.display='block'})}});
            if(photo?.naturalWidth&&photo?.naturalHeight&&photoRect){
                const cardRect=card.getBoundingClientRect();
                const scaleX=result.width/cardRect.width;
                const scaleY=result.height/cardRect.height;
                const insetX=.95*3.7795275591;
                const insetY=.95*3.7795275591;
                const x=(photoRect.left-cardRect.left+insetX)*scaleX;
                const y=(photoRect.top-cardRect.top+insetY)*scaleY;
                const width=(photoRect.width-(insetX*2))*scaleX;
                const height=(photoRect.height-(insetY*2))*scaleY;
                const sourceRatio=photo.naturalWidth/photo.naturalHeight;
                const targetRatio=width/height;
                let sx=0,sy=0,sw=photo.naturalWidth,sh=photo.naturalHeight;
                if(sourceRatio>targetRatio){sw=photo.naturalHeight*targetRatio;sx=(photo.naturalWidth-sw)/2}else{sh=photo.naturalWidth/targetRatio;sy=(photo.naturalHeight-sh)/2}
                result.getContext('2d').drawImage(photo,sx,sy,sw,sh,x,y,width,height);
            }
            return result;
        }
    };

    const download=async event=>{
        if(typeof html2canvas==='undefined'||!window.jspdf?.jsPDF)return;
        event?.preventDefault();
        const original=button.innerHTML;
        button.classList.add('disabled');
        button.innerHTML='<span class="spinner-border spinner-border-sm me-2"></span>Préparation...';
        try{
            if(document.fonts?.ready)await document.fonts.ready;
            await Promise.all(cards.map(prepareImages));
            const pdf=new window.jspdf.jsPDF({orientation:'portrait',unit:'mm',format:[53.98,85.60],compress:true});
            for(let index=0;index<cards.length;index++){
                const card=cards[index];
                const canvas=await capture(card);
                if(index>0)pdf.addPage([53.98,85.60],'portrait');
                pdf.addImage(canvas.toDataURL('image/png',1),'PNG',0,0,53.98,85.60,undefined,'FAST');
            }
            pdf.save(button.dataset.filename);
        }catch(error){
            window.location.assign(button.href);
        }finally{
            button.classList.remove('disabled');
            button.innerHTML=original;
        }
    };

    button.addEventListener('click',download);
    const downloadJpeg=async(card,face)=>{
        if(typeof html2canvas==='undefined'||!card)return;
        if(document.fonts?.ready)await document.fonts.ready;
        const canvas=await capture(card);
        const link=document.createElement('a');
        link.download=`carte-service-{{$carteService->numero}}-${face}.jpg`;
        link.href=canvas.toDataURL('image/jpeg',.96);
        link.click();
    };
    document.getElementById('download-card-jpeg-front')?.addEventListener('click',event=>{event.preventDefault();downloadJpeg(cards[0],'recto')});
    document.getElementById('download-card-jpeg-back')?.addEventListener('click',event=>{event.preventDefault();downloadJpeg(cards[1],'verso')});
    if(new URLSearchParams(window.location.search).get('telecharger')==='pdf')setTimeout(()=>download(),250);
});
</script>
@endpush
