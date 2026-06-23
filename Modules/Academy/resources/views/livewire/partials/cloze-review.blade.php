{{--
    Partial DRY (C3) — RÉVISION PAR TROU d'un cloze / texte à trous.

    Rend, pour chaque trou : la réponse de l'étudiant et (si autorisé) la bonne réponse.
    Utilisé en révision DIFFÉRÉE et IMMÉDIATE — le comportement est préservé via params :
      - différé   : showRight = review_options['show_right_answer'], showSummary = ['show_correctness'] ;
      - immédiat  : showRight = true, showSummary = true (rétroaction toujours montrée à la validation).

    Paramètres :
      - $blanks      : map index_de_trou => corrigé {kind, accepted|choices, correct} (serveur) ;
      - $userAns     : réponses soumises par trou (tableau index => valeur) ou null ;
      - $showRight   : afficher la bonne réponse des trous ratés ;
      - $showSummary : afficher la ligne de synthèse (« Tous les trous… ») ;
      - $isCorrect   : tous les trous corrects (pour le libellé de synthèse).
--}}
@php
    $blanks      = is_array($blanks ?? null) ? $blanks : [];
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
    @foreach($blanks as $bk => $blank)
        @php
            $kind = ($blank['kind'] ?? 'short') === 'mcq' ? 'mcq' : 'short';
            $ans  = $givenMap[$bk] ?? ($givenMap[(string) $bk] ?? null);
            if ($kind === 'mcq') {
                $bChoices = is_array($blank['choices'] ?? null) ? $blank['choices'] : [];
                $bCorrect = (int) ($blank['correct'] ?? -1);
                $bGiven   = is_numeric($ans) ? (int) $ans : -1;
                $rowOk    = $bGiven === $bCorrect;
                $rowGiven = $bChoices[$bGiven] ?? 'Aucune réponse';
                $rowRight = $bChoices[$bCorrect] ?? '';
            } else {
                $bAccepted = array_map(fn ($s) => mb_strtolower(trim((string) $s)), (array) ($blank['accepted'] ?? []));
                $bGivenStr = is_string($ans) ? trim($ans) : '';
                $rowOk     = $bGivenStr !== '' && in_array(mb_strtolower($bGivenStr), $bAccepted, true);
                $bRaw      = array_values((array) ($blank['accepted'] ?? []));
                $rowGiven  = $bGivenStr !== '' ? $bGivenStr : 'Aucune réponse';
                $rowRight  = $bRaw[0] ?? '';
            }
        @endphp
        <li>
            Trou {{ $bk + 1 }} : <strong>{{ $rowGiven }}</strong>
            @if($showRight && ! $rowOk && $rowRight !== '')
                <span aria-hidden="true">→</span> bonne réponse : <strong>{{ $rowRight }}</strong>
            @endif
        </li>
    @endforeach
</ul>
