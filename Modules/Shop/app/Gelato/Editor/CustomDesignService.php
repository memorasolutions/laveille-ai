<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

declare(strict_types=1);

namespace Modules\Shop\Gelato\Editor;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Shop\Gelato\Moderation\ContentModeratorContract;
use Modules\Shop\Gelato\PrintFile;
use Modules\Shop\Gelato\PrintFileService;
use Modules\Shop\Gelato\PrintPrepClient;
use Modules\Shop\Models\Product;

/**
 * Éditeur client : le client envoie une SPEC déclarative, le serveur la re-rend via le moteur.
 * Jamais confiance au pixel client. Chaque conception = un produit « brouillon » cloné du produit de base,
 * pour que deux clients n'écrasent jamais le fichier d'impression l'un de l'autre.
 */
class CustomDesignService
{
    public function __construct(
        private PrintPrepClient $client,
        private PrintFileService $printFiles,
        private ContentModeratorContract $moderator,
    ) {}

    /**
     * @return array<string,mixed> {assetHash, format, width, height}
     * @throws ValidationException image refusée par la modération
     * @throws \Modules\Shop\Gelato\PrintPrepException
     */
    public function uploadAsset(UploadedFile $file): array
    {
        $binary = (string) file_get_contents($file->getRealPath());
        $mime = (string) ($file->getMimeType() ?: 'application/octet-stream');

        $verdict = $this->moderator->checkImage($binary, $mime);
        if (! $verdict->allowed) {
            throw ValidationException::withMessages(['image' => $verdict->reason ?? 'Image refusée.']);
        }

        // Type réel, plafond de pixels, SVG : vérifiés par le moteur.
        return $this->client->uploadAsset($binary, $mime);
    }

    /**
     * Produit Gelato (uid de variante) autorisé pour ce produit de base : jamais fourni librement par le client.
     */
    public function resolveProductUid(Product $base, ?string $requested): string
    {
        $uids = collect($base->variants ?? [])->pluck('gelato_uid')->filter()->values();
        $fallback = $base->metadata['editor']['product_uid'] ?? null;
        if ($fallback) {
            $uids->push($fallback);
        }

        $uid = $requested ?: $uids->first();
        if (! $uid || ! $uids->contains($uid)) {
            throw ValidationException::withMessages(['variant_uid' => 'Variante non personnalisable.']);
        }

        return (string) $uid;
    }

    /** @return array{widthMm:float,heightMm:float,safeMarginMm:float} zone d'impression + marge (affichage front ET spec) */
    public function area(Product $base): array
    {
        $dim = $base->metadata['editor_area_mm'] ?? config('shop.editor.default_area_mm');

        return [
            'widthMm' => (float) $dim['widthMm'],
            'heightMm' => (float) $dim['heightMm'],
            'safeMarginMm' => (float) config('shop.editor.safe_margin_mm', 10),
        ];
    }

    /**
     * Reconstruit une spec PROPRE (liste blanche de champs, types forcés) depuis l'entrée client.
     *
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     * @throws ValidationException
     */
    public function buildSpec(Product $base, string $productUid, array $input): array
    {
        $elements = $input['elements'] ?? null;
        if (! is_array($elements) || $elements === [] || count($elements) > (int) config('shop.editor.max_elements', 30)) {
            throw ValidationException::withMessages(['spec' => 'Conception vide ou trop chargée.']);
        }

        $clean = [];
        foreach (array_values($elements) as $i => $el) {
            $clean[] = $this->cleanElement($i, is_array($el) ? $el : []);
        }

        $area = $this->area($base);

        return [
            'productUid' => $productUid,
            'printArea' => 'front',
            'overrideDimensionsMm' => ['widthMm' => $area['widthMm'], 'heightMm' => $area['heightMm']],
            'safeMarginMm' => $area['safeMarginMm'],
            'elements' => $clean,
        ];
    }

    /** @param array<string,mixed> $el @return array<string,mixed> */
    private function cleanElement(int $i, array $el): array
    {
        $geo = ['xMm' => 'required|numeric|between:-5000,5000', 'yMm' => 'required|numeric|between:-5000,5000'];
        $type = $el['type'] ?? null;

        if ($type === 'image') {
            $v = Validator::make($el, $geo + [
                'assetHash' => ['required', 'string', 'regex:/^[A-Za-z0-9_-]{8,128}$/'],
                'widthMm' => 'required|numeric|gt:0|max:5000', 'heightMm' => 'required|numeric|gt:0|max:5000',
                'rotationDeg' => 'nullable|numeric|between:-360,360',
            ]);
            $d = $this->validated($v, $i);

            return array_filter([
                'type' => 'image', 'assetHash' => (string) $d['assetHash'],
                'xMm' => (float) $d['xMm'], 'yMm' => (float) $d['yMm'],
                'widthMm' => (float) $d['widthMm'], 'heightMm' => (float) $d['heightMm'],
                'rotationDeg' => isset($d['rotationDeg']) ? (float) $d['rotationDeg'] : null,
            ], fn ($x) => $x !== null);
        }

        if ($type === 'text') {
            $v = Validator::make($el, $geo + [
                'content' => 'required|string|max:500',
                'fontFamily' => ['required', Rule::in(array_keys(GelatoEditor::FONTS))],
                'fontSizePt' => 'required|numeric|between:4,600',
                'colorHex' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
                'maxWidthMm' => 'nullable|numeric|gt:0|max:5000',
                'align' => ['nullable', Rule::in(['left', 'center', 'right'])],
                'lineHeight' => 'nullable|numeric|between:0.5,3',
                'rotationDeg' => 'nullable|numeric|between:-360,360',
            ]);
            $d = $this->validated($v, $i);

            $verdict = $this->moderator->checkText((string) $d['content']);
            if (! $verdict->allowed) {
                throw ValidationException::withMessages(["elements.$i.content" => $verdict->reason ?? 'Texte refusé.']);
            }

            return array_filter([
                'type' => 'text', 'content' => (string) $d['content'], 'fontFamily' => (string) $d['fontFamily'],
                'fontSizePt' => (float) $d['fontSizePt'], 'colorHex' => strtolower((string) $d['colorHex']),
                'xMm' => (float) $d['xMm'], 'yMm' => (float) $d['yMm'],
                'maxWidthMm' => isset($d['maxWidthMm']) ? (float) $d['maxWidthMm'] : null,
                'align' => $d['align'] ?? null,
                'lineHeight' => isset($d['lineHeight']) ? (float) $d['lineHeight'] : null,
                'rotationDeg' => isset($d['rotationDeg']) ? (float) $d['rotationDeg'] : null,
            ], fn ($x) => $x !== null);
        }

        throw ValidationException::withMessages(["elements.$i.type" => 'Type d\'élément inconnu.']);
    }

    /** @return array<string,mixed> */
    private function validated(\Illuminate\Validation\Validator $v, int $i): array
    {
        if ($v->fails()) {
            throw ValidationException::withMessages(
                collect($v->errors()->messages())->mapWithKeys(fn ($m, $k) => ["elements.$i.$k" => $m])->all()
            );
        }

        return $v->validated();
    }

    /**
     * Rend via le moteur ; ce n'est QU'APRÈS un rendu accepté qu'on crée (ou réutilise) le produit de conception
     * et qu'on enregistre le fichier (PREPARED puis MOCKUP_READY). Un refus 422 ne laisse aucune trace.
     *
     * @param array<string,mixed> $spec spec propre issue de buildSpec()
     * @return array{design:Product,file:PrintFile,result:array<string,mixed>}
     * @throws \Modules\Shop\Gelato\PrintPrepException
     */
    public function render(Product $base, string $productUid, array $spec, ?Product $existingDesign = null): array
    {
        $result = $this->client->renderSpec($spec);

        return DB::transaction(function () use ($base, $productUid, $result, $existingDesign) {
            $design = $existingDesign ?? $this->createDesignProduct($base);
            $file = $this->printFiles->recordPrepared($design, $result, $productUid, 'front', $productUid);
            // Même hash qu'un fichier déjà approuvé : il reste immuable, on ne le régresse pas.
            if ($file->status === \Modules\Shop\Gelato\PrintFileStatus::Prepared) {
                $file = $this->printFiles->markMockupReady($file);
            }

            return ['design' => $design, 'file' => $file, 'result' => $result];
        });
    }

    private function createDesignProduct(Product $base): Product
    {
        return Product::create([
            'name' => $base->name.' (personnalisé)',
            'slug' => 'perso-'.Str::lower(Str::random(20)),
            'price' => $base->price,
            'currency' => $base->currency,
            'images' => $base->images,
            'variants' => $base->variants,
            'category' => $base->category,
            'status' => 'draft', // jamais visible au catalogue
            'metadata' => ['custom_design' => true, 'base_product_id' => $base->id],
        ]);
    }

    /** URL publique de l'aperçu serveur : même dossier que le fichier d'impression (<hash>.preview.png). */
    public function mockupUrl(PrintFile $file): ?string
    {
        if (! PrintFile::isAbsoluteHttpsUrl($file->public_url) || empty($file->mockup_path)) {
            return null;
        }

        return rtrim(dirname((string) $file->public_url), '/').'/'.basename((string) $file->mockup_path);
    }
}
