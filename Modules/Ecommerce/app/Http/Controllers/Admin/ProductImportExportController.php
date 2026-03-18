<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Ecommerce\Services\ProductImportExportService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductImportExportController extends Controller
{
    public function __construct(
        protected ProductImportExportService $service,
    ) {}

    public function index(): View
    {
        return view('ecommerce::admin.import-export.index');
    }

    public function export(): StreamedResponse
    {
        $csv = $this->service->exportProducts();

        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, 'products-' . date('Y-m-d') . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $csv = file_get_contents($request->file('file')->getRealPath());
        $result = $this->service->importProducts($csv);

        $msg = __(':created produits créés, :updated mis à jour.', [
            'created' => $result['created'],
            'updated' => $result['updated'],
        ]);

        if (! empty($result['errors'])) {
            $msg .= ' ' . count($result['errors']) . ' erreur(s).';
        }

        return redirect()->route('admin.ecommerce.import-export.index')
            ->with('success', $msg)
            ->with('import_errors', $result['errors']);
    }
}
