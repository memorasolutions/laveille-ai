{{--
    Partial DRY — RÉVISION PAR TROU d'un GLISSER-DÉPOSER SUR TEXTE (ddwtos).

    Rend, pour chaque trou : le mot choisi par l'étudiant et (si autorisé) le bon mot.
    Utilisé en révision DIFFÉRÉE et IMMÉDIATE — le comportement est préservé via params :
      - différé   : showRight = review_options['show_right_answer'], showSummary = ['show_correctness'] ;
      - immédiat  : showRight = true, showSummary = true (rétroaction toujours montrée).

    Paramètres :
      - $answers     : map index_de_trou => index du mot correct dans le pool (serveur) ;
      - $options     : pool de mots MÉLANGÉ (pour traduire un index en libellé) ;
      - $userAns     : réponses soumises par trou (tableau index => index de mot) ou null ;
      - $showRight   : afficher le bon mot des trous ratés ;
      - $showSummary : afficher la ligne de synthèse (« Tous les trous… ») ;
      - $isCorrect   : tous les trous corrects (pour le libellé de synthèse).
--}}
@php
    $answers     = is_array($answers ?? null) ? $answers : [];
    $options     = is_array($options ?? null) ? $options : [];
    $userAns     = $userAns ?? null;
    $showRight   = $showRight ?? false;
    $showSummary = $showSummary ?? false;
    $isCorrect   = $isCorrect ?? false;
    $givenMap    = is_array($userAns) ? $userAns : [];
@endphp
@if($showSummary)
    <p class="mb-1 text-muted" style="font-size: 0.85rem;">
        {{ $isCorrect ? 'Tous les trous sont corrects.' : 'Certains trous sont à revoir.' }}
    </p>
@endif
<ul class="mb-1 text-muted" style="font-size: 0.85rem; padding-left: 1.1rem;">
    @foreach($answers as $bk => $correctIdx)
        @php
            $correctIdx = (int) $correctIdx;
            $ans        = $givenMap[$bk] ?? ($givenMap[(string) $bk] ?? null);
            $givenIdx   = is_numeric($ans) ? (int) $ans : -1;
            $rowOk      = $givenIdx === $correctIdx;
            $rowGiven   = ($givenIdx >= 0 && isset($options[$givenIdx])) ? $options[$givenIdx] : 'Aucune réponse';
            $rowRight   = $options[$correctIdx] ?? '';
        @endphp
        <li>
            Trou {{ $bk + 1 }} : <strong>{{ $rowGiven }}</strong>
            @if($showRight && ! $rowOk && $rowRight !== '')
                <span aria-hidden="true">→</span> bonne réponse : <strong>{{ $rowRight }}</strong>
            @endif
        </li>
    @endforeach
</ul>
