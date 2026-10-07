<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

declare(strict_types=1);

namespace Modules\Shop\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Modules\Shop\Gelato\Editor\CustomDesignService;
use Modules\Shop\Gelato\Editor\GelatoEditor;
use Modules\Shop\Gelato\PrintFile;
use Modules\Shop\Gelato\PrintFileNotApprovedException;
use Modules\Shop\Gelato\PrintFileService;
use Modules\Shop\Gelato\PrintFileStatus;
use Modules\Shop\Gelato\PrintPrepException;
use Modules\Shop\Models\Product;
use Modules\Shop\Services\CartService;

/** Éditeur client (drapeau shop.gelato_editor + gelato_zero_erreur). Les routes n'existent que drapeau ON. */
class GelatoEditorController extends Controller
{
    private const SESSION_KEY = 'shop.editor.designs';

    public function __construct(
        private CustomDesignService $designs,
        private PrintFileService $printFiles,
        private CartService $cart,
    ) {}

    public function show(Product $product)
    {
        abort_unless($product->status === 'published' && empty($product->metadata['custom_design']), 404);
        try {
            $uid = $this->designs->resolveProductUid($product, null);
        } catch (ValidationException) {
            abort(404);
        }

        return view('shop::public.editor', [
            'product' => $product,
            'area' => $this->designs->area($product),
            'variants' => collect($product->variants ?? [])->filter(fn ($v) => ! empty($v['gelato_uid']))->values(),
            'defaultUid' => $uid,
            'fonts' => array_map(fn ($file) => route('shop.editor.font', $file), GelatoEditor::FONTS),
        ]);
    }

    public function font(string $file)
    {
        $path = GelatoEditor::fontPath($file);
        abort_if($path === null || ! is_file($path), 404);

        return response()->file($path, ['Content-Type' => 'font/ttf', 'Cache-Control' => 'public, max-age=31536000, immutable']);
    }

    public function upload(Request $request): JsonResponse
    {
        // SVG exclu d'office (mimes), type réel revérifié par le moteur.
        $request->validate(['image' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:'.(int) config('shop.editor.max_upload_kb', 15360)]]);

        return $this->guard(fn () => response()->json($this->designs->uploadAsset($request->file('image'))));
    }

    public function render(Request $request, Product $product): JsonResponse
    {
        abort_unless($product->status === 'published' && empty($product->metadata['custom_design']), 404);
        $data = $request->validate([
            'variant_uid' => 'nullable|string|max:255',
            'design_id' => 'nullable|integer',
            'spec' => 'required|array',
        ]);

        $existing = isset($data['design_id']) ? $this->ownedDesign((int) $data['design_id']) : null;

        return $this->guard(function () use ($product, $data, $existing) {
            $uid = $this->designs->resolveProductUid($product, $data['variant_uid'] ?? null);
            $spec = $this->designs->buildSpec($product, $uid, $data['spec']);
            $out = $this->designs->render($product, $uid, $spec, $existing);

            session()->push(self::SESSION_KEY, $out['design']->id);

            return response()->json([
                'design_id' => $out['design']->id,
                'status' => $out['file']->status->value,
                'content_hash' => $out['file']->print_file_hash,
                'mockup_url' => $this->designs->mockupUrl($out['file']),
                'validation' => $out['result']['validation'] ?? null,
            ]);
        });
    }

    public function approve(Request $request): JsonResponse
    {
        $data = $request->validate(['design_id' => 'required|integer', 'content_hash' => 'required|string|max:128']);
        $design = $this->ownedDesign((int) $data['design_id']);

        $file = PrintFile::where('product_id', $design->id)->where('print_area', 'front')->first();
        if (! $file || ! hash_equals((string) $file->print_file_hash, $data['content_hash'])) {
            return response()->json(['error' => ['code' => 'HASH_CHANGE', 'message' => 'La conception a changé : régénère l\'aperçu avant d\'approuver.']], 409);
        }

        try {
            $approved = $this->printFiles->approve($file, auth()->id());
        } catch (PrintFileNotApprovedException $e) {
            return response()->json(['error' => ['code' => 'NON_APPROUVABLE', 'message' => $e->getMessage()]], 409);
        }

        // Chemin de commande existant : le panier + checkout exigent déjà un print file approuvé (assertOrderable).
        $this->cart->add($design->id, 1, 'Personnalisé', $approved->product_uid);

        return response()->json(['status' => PrintFileStatus::Approved->value, 'cart_url' => route('shop.cart')]);
    }

    private function ownedDesign(int $id): Product
    {
        abort_unless(in_array($id, (array) session(self::SESSION_KEY, []), true), 403);

        return Product::findOrFail($id);
    }

    /** Erreurs propres : refus moteur -> 422 lisible, panne -> 502/503 générique, jamais de détail interne. */
    private function guard(callable $fn): JsonResponse
    {
        try {
            return $fn();
        } catch (PrintPrepException $e) {
            if ($e->isRefusal()) {
                return response()->json(['error' => ['code' => $e->errorCode, 'message' => $e->getMessage(), 'details' => $e->details]], 422);
            }
            report($e);
            $status = $e->errorCode === PrintPrepException::NOT_CONFIGURED ? 503 : 502;

            return response()->json(['error' => ['code' => 'MOTEUR_INDISPONIBLE', 'message' => 'Le service de préparation est momentanément indisponible.']], $status);
        }
    }
}
