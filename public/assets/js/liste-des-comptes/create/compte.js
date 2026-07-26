document.addEventListener('DOMContentLoaded', function () {

    const nature = document.getElementById('nature');
    const observation = document.getElementById('observation');

    function remplirObservation() {

        if (nature.value === 'Actif' || nature.value === 'Passif') {

            observation.value = 'Bilan';

        } else if (nature.value === 'Charge' || nature.value === 'Produit') {

            observation.value = 'Gestion';

        } else {

            observation.value = '';

        }
    }


    if (nature && observation) {

        // Remplir au changement
        nature.addEventListener('change', remplirObservation);

        // Remplir aussi avant l'envoi du formulaire
        nature.closest('form').addEventListener('submit', remplirObservation);

    }

});