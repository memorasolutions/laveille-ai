{{--
    @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
    @project laveille.ai

    Écran de consentement OAuth2 (fournisseur d'identité laveille).

    Le client first-party de confiance (le Moodle de formations.laveille.ai) saute cet écran
    (voir Modules\Idp\Models\OAuthClient). Cette vue ne s'affiche donc que pour un éventuel client
    NON first-party. Elle doit malgré tout exister : Passport 13 lie le contrat
    AuthorizationViewResponse à cette vue, et sans elle /oauth/authorize renvoie 500.

    Paramètres fournis par Passport : $client, $user, $scopes, $request, $authToken.
--}}
<!doctype html>
<html lang="fr-CA">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Autorisation - laveille.ai</title>
<style>
  :root{--teal:#064E5A;--or:#E8833A;--bg:#f6f8f9;--card:#fff;--texte:#1b2630;--gris:#52586a;--ligne:#d7dee2;}
  *{box-sizing:border-box}
  body{margin:0;background:var(--bg);color:var(--texte);font:16px/1.6 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;display:flex;min-height:100vh;align-items:center;justify-content:center;padding:20px;}
  .carte{background:var(--card);border:1px solid var(--ligne);border-radius:12px;max-width:440px;width:100%;padding:28px 26px;}
  h1{font-size:20px;color:var(--teal);margin:0 0 14px;}
  p{margin:0 0 14px;}
  ul{margin:0 0 18px;padding-left:20px;} li{margin:6px 0;}
  .ligne{display:flex;gap:12px;margin-top:8px;}
  button{flex:1;border:0;border-radius:8px;padding:12px 16px;font-size:15px;font-weight:600;cursor:pointer;min-height:44px;}
  .oui{background:var(--teal);color:#fff;}
  .non{background:#eef2f3;color:var(--texte);border:1px solid var(--ligne);}
  form{margin:0;}
</style>
</head>
<body>
<div class="carte">
  <h1>Autoriser l'accès</h1>
  <p><strong>{{ $client->name }}</strong> souhaite accéder à votre compte laveille.ai@if (! empty($user?->name)), {{ $user->name }}@endif.</p>

  @if (count($scopes) > 0)
    <p>Renseignements partagés :</p>
    <ul>
      @foreach ($scopes as $scope)
        <li>{{ $scope->description }}</li>
      @endforeach
    </ul>
  @endif

  <div class="ligne">
    <form method="post" action="{{ route('passport.authorizations.approve') }}">
      @csrf
      <input type="hidden" name="state" value="{{ $request->state }}">
      <input type="hidden" name="client_id" value="{{ $client->getKey() }}">
      <input type="hidden" name="auth_token" value="{{ $authToken }}">
      <button type="submit" class="oui">Autoriser</button>
    </form>
    <form method="post" action="{{ route('passport.authorizations.deny') }}">
      @csrf
      @method('delete')
      <input type="hidden" name="state" value="{{ $request->state }}">
      <input type="hidden" name="client_id" value="{{ $client->getKey() }}">
      <input type="hidden" name="auth_token" value="{{ $authToken }}">
      <button type="submit" class="non">Refuser</button>
    </form>
  </div>
</div>
</body>
</html>
