<?php

declare(strict_types=1);

namespace Modules\Import\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Modules\Import\Services\ImportService;

class ImportController extends Controller
{
    public function __construct(
        private readonly ImportService $importService,
    ) {}

    public function index(): View
    {
        $modelTypes = ImportService::MODEL_TYPES;

        return view('import::admin.index', compact('modelTypes'));
    }

    public function preview(Request $request): View
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,txt|max:10240',
            'model_type' => 'required|in:article,page,user',
        ]);

        $file = $request->file('file');
        $fileName = time() . '_' . $file->getClientOriginalName();
        $file->storeAs('imports', $fileName, 'local');

        $format = str_contains($file->getClientOriginalExtension(), 'xls') ? 'xlsx' : 'csv';
        $fullPath = \Illuminate\Support\Facades\Storage::disk('local')->path('imports/' . $fileName);

        $preview = $this->importService->preview($fullPath, $format);
        $modelType = $request->input('model_type');
        $availableFields = ImportService::FIELD_MAPS[$modelType] ?? [];
        $filePath = 'imports/' . $fileName;

        return view('import::admin.preview', compact('preview', 'modelType', 'availableFields', 'filePath', 'format'));
    }

    public function execute(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'mapping' => 'required|array',
            'model_type' => 'required|in:article,page,user',
            'file_path' => 'required|string',
            'format' => 'required|in:csv,xlsx',
        ]);

        $fullPath = Storage::disk('local')->path($validated['file_path']);

        if (! file_exists($fullPath)) {
            return redirect()->route('admin.import.index')
                ->with('error', 'Le fichier uploadé n\'existe plus. Veuillez réessayer.');
        }

        $result = $this->importService->import(
            $fullPath,
            $validated['format'],
            $validated['model_type'],
            $validated['mapping']
        );

        Storage::disk('local')->delete($validated['file_path']);

        $message = "{$result->imported}/{$result->total} éléments importés avec succès.";

        if ($result->skipped > 0) {
            $message .= " {$result->skipped} lignes ignorées.";
        }

        return redirect()->route('admin.import.index')
            ->with($result->imported > 0 ? 'success' : 'error', $message);
    }

    public function template(string $type): \Symfony\Component\HttpFoundation\StreamedResponse|RedirectResponse
    {
        $templates = [
            'article' => [
                'headers' => ['title', 'content', 'excerpt', 'category'],
                'example' => ['Mon article', 'Contenu de l\'article...', 'Résumé court...', 'Technologie'],
            ],
            'page' => [
                'headers' => ['title', 'content', 'excerpt'],
                'example' => ['Ma page', 'Contenu de la page...', 'Résumé court...'],
            ],
            'pages' => [
                'headers' => ['title', 'content', 'status'],
                'example' => ['Ma page', 'Contenu...', 'published'],
            ],
            'user' => [
                'headers' => ['name', 'email', 'password'],
                'example' => ['Jean Dupont', 'jean@example.com', 'motdepasse123'],
            ],
            'plans' => [
                'headers' => ['name', 'price', 'interval', 'features'],
                'example' => ['Pro', '29.99', 'monthly', 'Feature A'],
            ],
            'comments' => [
                'headers' => ['article_id', 'guest_name', 'guest_email', 'content'],
                'example' => ['1', 'Jean Dupont', 'jean@example.com', 'Super article !'],
            ],
        ];

        if (! isset($templates[$type])) {
            return redirect()->route('admin.import.index');
        }

        $template = $templates[$type];

        $callback = function () use ($template): void {
            $file = fopen('php://output', 'w');
            fwrite($file, "\xEF\xBB\xBF");
            fputcsv($file, $template['headers']);
            fputcsv($file, $template['example']);
            fclose($file);
        };

        return Response::stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$type}_template.csv\"",
        ]);
    }
}
