@extends(fronttheme_layout())

@section('title', 'Demande de retrait de contenu - '.config('app.name'))
@section('meta_description', 'Formulaire de demande de retrait de contenu (droit d\'auteur, marque, vie privée) pour les fiches de l\'annuaire.')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-6">Demande de retrait de contenu</h1>

    <p class="mb-6">
        Ce formulaire sert aux titulaires de droits (droit d’auteur, marque de commerce) ou aux personnes concernées par la diffusion de leurs données personnelles.
        Toute demande sera traitée de bonne foi, sous réserve de la vérification de votre identité et de la légitimité de vos droits.
        Une déclaration sous peine de responsabilité est requise.
    </p>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6" role="alert">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6" role="alert">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('directory.takedown.store') }}">
        @csrf

        @if($tool)
            <input type="hidden" name="directory_tool_id" value="{{ $tool->id }}">
        @endif

        <div style="display:none;" aria-hidden="true">
            <label>Laissez ce champ vide
                <input type="text" name="website" tabindex="-1" autocomplete="off">
            </label>
        </div>

        <fieldset class="mb-6">
            <legend class="font-semibold mb-2">Contenu visé</legend>
            <label for="target_url" class="block mb-1">URL du contenu à retirer <span class="text-red-600">*</span></label>
            <input
                type="url"
                id="target_url"
                name="target_url"
                value="{{ old('target_url', $tool ? route('directory.show', $tool->slug) : '') }}"
                required
                class="w-full border rounded px-3 py-2"
                aria-describedby="target_url_help"
            >
            <p id="target_url_help" class="text-sm text-gray-500 mt-1">L’URL exacte du contenu que vous souhaitez faire retirer.</p>
        </fieldset>

        <fieldset class="mb-6">
            <legend class="font-semibold mb-2">Vos coordonnées</legend>

            <label for="requester_name" class="block mb-1">Nom complet <span class="text-red-600">*</span></label>
            <input
                type="text"
                id="requester_name"
                name="requester_name"
                value="{{ old('requester_name') }}"
                required
                class="w-full border rounded px-3 py-2 mb-4"
                aria-describedby="requester_name_help"
            >
            <p id="requester_name_help" class="text-sm text-gray-500 mb-4">Votre nom ou celui de la personne autorisée à agir.</p>

            <label for="requester_email" class="block mb-1">Courriel <span class="text-red-600">*</span></label>
            <input
                type="email"
                id="requester_email"
                name="requester_email"
                value="{{ old('requester_email') }}"
                required
                class="w-full border rounded px-3 py-2 mb-4"
                aria-describedby="requester_email_help"
            >
            <p id="requester_email_help" class="text-sm text-gray-500 mb-4">Utilisé pour vous contacter au sujet de cette demande.</p>

            <label for="requester_organization" class="block mb-1">Organisation (facultatif)</label>
            <input
                type="text"
                id="requester_organization"
                name="requester_organization"
                value="{{ old('requester_organization') }}"
                class="w-full border rounded px-3 py-2 mb-4"
            >

            <label for="requester_role" class="block mb-1">Votre rôle <span class="text-red-600">*</span></label>
            <select
                id="requester_role"
                name="requester_role"
                required
                class="w-full border rounded px-3 py-2"
                aria-describedby="requester_role_help"
            >
                <option value="">—</option>
                <option value="titulaire" {{ old('requester_role') === 'titulaire' ? 'selected' : '' }}>Titulaire des droits</option>
                <option value="mandataire" {{ old('requester_role') === 'mandataire' ? 'selected' : '' }}>Mandataire</option>
                <option value="avocat" {{ old('requester_role') === 'avocat' ? 'selected' : '' }}>Avocat</option>
                <option value="autre" {{ old('requester_role') === 'autre' ? 'selected' : '' }}>Autre</option>
            </select>
            <p id="requester_role_help" class="text-sm text-gray-500 mt-1">Indiquez votre lien avec les droits invoqués.</p>
        </fieldset>

        <fieldset class="mb-6">
            <legend class="font-semibold mb-2">Droit invoqué</legend>

            <label for="right_type" class="block mb-1">Type de droit <span class="text-red-600">*</span></label>
            <select
                id="right_type"
                name="right_type"
                required
                class="w-full border rounded px-3 py-2 mb-4"
                aria-describedby="right_type_help"
            >
                <option value="">—</option>
                <option value="droit_auteur" {{ old('right_type') === 'droit_auteur' ? 'selected' : '' }}>Droit d’auteur</option>
                <option value="marque" {{ old('right_type') === 'marque' ? 'selected' : '' }}>Marque de commerce</option>
                <option value="vie_privee" {{ old('right_type') === 'vie_privee' ? 'selected' : '' }}>Vie privée / données personnelles</option>
                <option value="autre" {{ old('right_type') === 'autre' ? 'selected' : '' }}>Autre</option>
            </select>
            <p id="right_type_help" class="text-sm text-gray-500 mb-4">Sélectionnez le type de droit que vous invoquez.</p>

            <label for="right_details" class="block mb-1">Preuve ou justification <span class="text-red-600">*</span></label>
            <textarea
                id="right_details"
                name="right_details"
                required
                class="w-full border rounded px-3 py-2 mb-4"
                rows="3"
                placeholder="Preuve : n° d'enregistrement de marque, lien vers l'œuvre originale, date de création…"
                aria-describedby="right_details_help"
            >{{ old('right_details') }}</textarea>
            <p id="right_details_help" class="text-sm text-gray-500">Fournissez des éléments concrets permettant de vérifier vos droits.</p>
        </fieldset>

        <fieldset class="mb-6">
            <legend class="font-semibold mb-2">Description</legend>
            <label for="description" class="block mb-1">Décrivez précisément le problème <span class="text-red-600">*</span></label>
            <textarea
                id="description"
                name="description"
                required
                class="w-full border rounded px-3 py-2"
                rows="4"
                aria-describedby="description_help"
            >{{ old('description') }}</textarea>
            <p id="description_help" class="text-sm text-gray-500 mt-1">Expliquez clairement pourquoi ce contenu devrait être retiré.</p>
        </fieldset>

        <fieldset class="mb-6">
            <legend class="font-semibold mb-2">Déclaration</legend>
            <label class="flex items-start">
                <input
                    type="checkbox"
                    name="declaration_accepted"
                    value="1"
                    required
                    class="mt-1 mr-2"
                    {{ old('declaration_accepted') ? 'checked' : '' }}
                >
                <span>
                    Je déclare de bonne foi, sous peine de responsabilité, être titulaire des droits invoqués (ou son représentant autorisé), et que les informations fournies sont exactes.
                </span>
            </label>
        </fieldset>

        <button type="submit" class="bg-teal-600 hover:bg-teal-700 text-white font-medium py-2 px-4 rounded">
            Envoyer la demande
        </button>
    </form>
</div>
@endsection
