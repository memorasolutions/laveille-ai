{{-- Champ leurre anti-robot. Un être humain ne le voit jamais et ne peut pas l'atteindre au clavier ; --}}
{{-- un robot qui remplit tous les champs se trahit. Le nom et les attributs viennent d'une seule --}}
{{-- source de vérité : Modules\Core\Support\Honeypot. Ne jamais utiliser display:none ici, certains --}}
{{-- robots le détectent et l'évitent. --}}
<input {!! \Modules\Core\Support\Honeypot::attributesString() !!} style="position:absolute!important;left:-9999px!important;width:1px;height:1px;overflow:hidden;">
