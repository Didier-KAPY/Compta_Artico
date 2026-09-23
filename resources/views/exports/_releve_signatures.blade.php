<table style="width:100%;margin-top:28px;border-collapse:collapse;page-break-inside:avoid">
    <tr>
        @foreach(($signatureLabels ?? ['gerant'=>'Le gérant', 'finances'=>'Le chargé des finances', 'cachet'=>'Cachet de l’entreprise']) as $key=>$label)
        <td style="text-align:center;border:0;background:white;padding:12px;vertical-align:top">
            <strong>{{ $label }}</strong><br>
            {{ $signaturesReleve[$key]['nom'] }}<br>
            @if($image = $signaturesReleve[$key]['image'])
                <img src="{{ ($formatSignature ?? 'pdf') === 'excel' ? 'file:///'.$image['name'] : 'data:'.$image['mime'].';base64,'.$image['base64'] }}" alt="{{ $label }}" width="{{ $image['width'] }}" height="{{ $image['height'] }}" style="max-width:140px;max-height:75px;margin-top:8px">
            @else
                <div style="height:75px;padding-top:8px">{{ $key === 'cachet' ? 'Cachet non renseigné' : 'Signature non renseignée' }}</div>
            @endif
        </td>
        @endforeach
    </tr>
</table>
