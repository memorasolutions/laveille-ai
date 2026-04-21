<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ __('Aperçu') }} — {{ $model->title }}</title>
    <style>
        :root {
            --font-stack: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
            --color-bg: #f9fafb;
            --color-text: #111827;
            --color-banner-bg: #fef3c7;
            --color-banner-text: #92400e;
            --color-badge-type-bg: #e5e7eb;
            --color-badge-type-text: #374151;
            --color-status-draft: #6b7280;
            --color-status-pending: #3b82f6;
            --color-status-published: #10b981;
            --color-status-archived: #f97316;
        }
        body { font-family: var(--font-stack); background-color: var(--color-bg); color: var(--color-text); margin: 0; line-height: 1.5; }
        .preview-banner { background-color: var(--color-banner-bg); color: var(--color-banner-text); padding: 12px 20px; display: flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 500; border-bottom: 1px solid #fcd34d; }
        .preview-banner svg { width: 20px; height: 20px; flex-shrink: 0; }
        .container { max-width: 800px; margin: 0 auto; padding: 40px 20px; }
        .meta-bar { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 9999px; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; }
        .badge-type { background-color: var(--color-badge-type-bg); color: var(--color-badge-type-text); }
        .badge-status { color: #fff; }
        .status-draft { background-color: var(--color-status-draft); }
        .status-pending_review { background-color: var(--color-status-pending); }
        .status-published { background-color: var(--color-status-published); }
        .status-archived { background-color: var(--color-status-archived); }
        h1 { font-size: 2rem; margin: 0 0 24px 0; line-height: 1.2; }
        article { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        article img { max-width: 100%; height: auto; }
        @media (max-width: 600px) { .container { padding: 20px 15px; } article { padding: 20px; } h1 { font-size: 1.5rem; } }
    </style>
</head>
<body>

    <div class="preview-banner">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/>
            <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/>
            <path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/>
            <line x1="2" x2="22" y1="2" y2="22"/>
        </svg>
        <span>{{ __('Aperçu — ce contenu n\'est pas encore publié') }}</span>
    </div>

    <div class="container">
        @php
            $statusRaw = (string) ($model->status ?? 'draft');
            $statusClass = match($statusRaw) {
                'draft' => 'status-draft',
                'pending_review' => 'status-pending_review',
                'published' => 'status-published',
                'archived' => 'status-archived',
                default => 'status-draft',
            };
            $statusLabel = match($statusRaw) {
                'draft' => __('Brouillon'),
                'pending_review' => __('En révision'),
                'published' => __('Publié'),
                'archived' => __('Archivé'),
                default => ucfirst($statusRaw),
            };
        @endphp

        <div class="meta-bar">
            <span class="badge badge-type">{{ $type === 'article' ? __('Article') : __('Page') }}</span>
            <span class="badge badge-status {{ $statusClass }}">{{ $statusLabel }}</span>
        </div>

        <h1>{{ $model->title }}</h1>

        <article>
            {!! $model->content !!}
        </article>
    </div>

</body>
</html>
