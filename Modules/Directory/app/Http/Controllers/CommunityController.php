<?php

declare(strict_types=1);

namespace Modules\Directory\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Modules\Directory\Models\Tool;
use Modules\Directory\Models\ToolDiscussion;
use Modules\Directory\Models\ToolReport;
use Modules\Directory\Models\ToolResource;
use Modules\Directory\Models\ToolReview;
use Modules\Directory\Models\ToolScreenshot;
use Modules\Directory\Services\ReputationService;

class CommunityController extends Controller
{
    private ReputationService $reputation;

    public function __construct()
    {
        $this->reputation = new ReputationService;
    }

    private function findTool(string $slug): Tool
    {
        return Tool::published()
            ->where('slug->'.app()->getLocale(), $slug)
            ->firstOrFail();
    }

    public function storeReview(Request $request, string $slug): RedirectResponse|JsonResponse
    {
        $tool = $this->findTool($slug);

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'title' => ['required', 'string', 'max:255'],
            'pros' => ['nullable', 'string', 'max:1000'],
            'cons' => ['nullable', 'string', 'max:1000'],
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $user = Auth::user();

        // Anti-spam : vérification qualité du contenu
        if (class_exists(\Modules\Core\Services\ContentQualityService::class)) {
            $quality = (new \Modules\Core\Services\ContentQualityService)->check($validated['body'], $user->id);
            if (! $quality->passed) {
                return back()->withErrors(['body' => implode(' ', $quality->reasons)])->withInput();
            }
        }

        $autoApprove = $this->reputation->shouldAutoApprove($user, 'review');

        ToolReview::create([
            'user_id' => $user->id,
            'directory_tool_id' => $tool->id,
            'rating' => $validated['rating'],
            'title' => $validated['title'],
            'pros' => $validated['pros'] ?? null,
            'cons' => $validated['cons'] ?? null,
            'body' => $validated['body'],
            'is_approved' => $autoApprove,
        ]);

        if ($autoApprove) {
            $this->reputation->addPoints($user, ReputationService::REVIEW_APPROVED, 'review_auto');

            return back()->with('success', __('Votre avis a été publié automatiquement !'));
        }

        return back()->with('success', __('Merci pour votre avis ! Il sera visible après approbation.'));
    }

    public function storeDiscussion(Request $request, string $slug): RedirectResponse|JsonResponse
    {
        $tool = $this->findTool($slug);

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:2000'],
            'parent_id' => ['nullable', 'exists:directory_discussions,id'],
        ]);

        $user = Auth::user();

        // Anti-spam : vérification qualité du contenu
        if (class_exists(\Modules\Core\Services\ContentQualityService::class)) {
            $quality = (new \Modules\Core\Services\ContentQualityService)->check($validated['body'], $user->id);
            if (! $quality->passed) {
                return back()->withErrors(['body' => implode(' ', $quality->reasons)])->withInput();
            }
        }

        $isReply = ! empty($validated['parent_id']);
        $autoApprove = $isReply ? true : $this->reputation->shouldAutoApprove($user, 'discussion');

        $sanitizedBody = class_exists(\Mews\Purifier\Facades\Purifier::class)
            ? \Mews\Purifier\Facades\Purifier::clean($validated['body'])
            : strip_tags($validated['body']);

        ToolDiscussion::create([
            'user_id' => $user->id,
            'directory_tool_id' => $tool->id,
            'parent_id' => $validated['parent_id'] ?? null,
            'title' => $validated['title'] ?? null,
            'body' => $sanitizedBody,
            'is_approved' => $autoApprove,
        ]);

        $pts = $isReply ? ReputationService::REPLY_APPROVED : ReputationService::DISCUSSION_APPROVED;
        if ($autoApprove) {
            $this->reputation->addPoints($user, $pts, $isReply ? 'reply' : 'discussion');
        }

        return back()->with('success', __('Votre message a été publié.'));
    }

    public function storeResource(Request $request, string $slug): RedirectResponse|JsonResponse
    {
        $tool = $this->findTool($slug);

        $validated = $request->validate([
            'url' => ['required', 'url', 'max:500'],
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:video,article,tutorial,documentation'],
            'language' => ['required', 'in:fr,en'],
            'video_id' => ['nullable', 'string', 'max:20'],
            'thumbnail' => ['nullable', 'url', 'max:500'],
            'duration' => ['nullable', 'integer'],
            'channel_name' => ['nullable', 'string', 'max:255'],
            'channel_url' => ['nullable', 'url', 'max:500'],
        ]);

        // Vérification doublon URL pour cet outil
        $exists = ToolResource::where('directory_tool_id', $tool->id)->where('url', $validated['url'])->exists();
        if ($exists) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => __('Cette ressource existe déjà pour cet outil.')], 422);
            }

            return back()->withErrors(['url' => __('Cette ressource existe déjà pour cet outil.')]);
        }

        $user = Auth::user();
        $autoApprove = $this->reputation->shouldAutoApprove($user, 'resource');

        $resourceData = [
            'user_id' => $user->id,
            'directory_tool_id' => $tool->id,
            'url' => $validated['url'],
            'title' => $validated['title'],
            'type' => $validated['type'],
            'language' => $validated['language'],
            'video_id' => $validated['video_id'] ?? null,
            'thumbnail' => $validated['thumbnail'] ?? null,
            'duration_seconds' => $validated['duration'] ?? null,
            'channel_name' => $validated['channel_name'] ?? null,
            'channel_url' => $validated['channel_url'] ?? null,
            'is_approved' => $autoApprove,
        ];

        // Auto-résumé YouTube si le module AI est disponible
        if (! empty($validated['video_id']) && class_exists(\Modules\AI\Services\YouTubeService::class)) {
            try {
                $ytService = app(\Modules\AI\Services\YouTubeService::class);
                $transcript = $ytService->extractTranscript($validated['url'], $validated['language']);
                if ($transcript) {
                    $resourceData['video_summary'] = $ytService->summarize($transcript['transcript'], $transcript['video_id']);
                } else {
                    // Fallback : résumé basé sur description YouTube + métadonnées
                    $videoDesc = '';
                    try {
                        $ytResp = Http::withoutVerifying()->timeout(15)->get("https://www.youtube.com/watch?v={$validated['video_id']}");
                        $ytHtml = $ytResp->successful() ? $ytResp->body() : '';
                        if ($ytHtml && preg_match('/"shortDescription":"(.*?)(?<!\\\\)"/', $ytHtml, $dm)) {
                            $videoDesc = str_replace(['\\n', '\\r', '\\"'], ["\n", '', '"'], $dm[1]);
                            $videoDesc = mb_substr($videoDesc, 0, 3000);
                        }
                    } catch (\Throwable $e) {
                    }

                    $resourceData['video_summary'] = $ytService->summarizeFromMeta(
                        $validated['title'] ?? '',
                        $resourceData['channel_name'] ?? '',
                        $tool->name ?? '',
                        $tool->short_description ?? '',
                        $videoDesc
                    );
                }
            } catch (\Throwable $e) {
                // Pas bloquant — la ressource est créée sans résumé
            }
        }

        $resource = ToolResource::create($resourceData);

        // Notifier les admins (non-bloquant si SMTP échoue)
        try {
            if (class_exists(\App\Models\User::class) && class_exists(\Spatie\Permission\Models\Permission::class)) {
                $admins = \App\Models\User::permission('view_admin_panel')->get();
                foreach ($admins as $admin) {
                    $admin->notify(new \Modules\Directory\Notifications\ResourceSubmittedNotification($resource));
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Notification admin échouée', ['error' => $e->getMessage()]);
        }

        if ($autoApprove) {
            $this->reputation->addPoints($user, ReputationService::RESOURCE_APPROVED, 'resource_auto');
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'approved' => $autoApprove]);
        }

        return back()->with('success', __('Merci ! La ressource sera visible après approbation.'));
    }

    public function fetchYoutubeMeta(Request $request, string $slug): JsonResponse
    {
        $request->validate(['url' => 'required|url|max:500']);
        $url = $request->input('url');

        $videoId = null;
        if (class_exists(\Modules\AI\Services\YouTubeService::class)) {
            $videoId = \Modules\AI\Services\YouTubeService::getVideoId($url);
        } elseif (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url, $m)) {
            $videoId = $m[1];
        }

        if (! $videoId) {
            return response()->json(['youtube' => false, 'title' => null, 'thumbnail' => null]);
        }

        // Fetch oEmbed metadata — avec fallback si serveur bloqué
        $oembed = null;
        try {
            $httpClient = Http::withoutVerifying()
                ->withUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36')
                ->timeout(8);

            $oembedResponse = $httpClient->get('https://www.youtube.com/oembed', ['url' => $url, 'format' => 'json']);
            $oembed = $oembedResponse->successful() ? $oembedResponse->json() : null;

            if (! $oembed || empty($oembed['title'])) {
                $noembedResponse = $httpClient->get('https://noembed.com/embed', ['url' => $url]);
                $oembed = ($noembedResponse->successful() ? $noembedResponse->json() : null);
            }
        } catch (\Throwable $e) {
            // Serveur ne peut pas joindre YouTube/noembed — on continue avec le fallback
        }

        // Fallback : si oEmbed échoue mais videoId valide, accepter la vidéo (thumbnail prédictible)
        if (! $oembed || empty($oembed['title'])) {
            $oembed = [
                'title' => null,
                'author_name' => null,
                'html' => '<iframe src="https://www.youtube.com/embed/' . $videoId . '"></iframe>',
            ];
        }

        // Extraire titre + durée + channel + description depuis la page YouTube
        $scrapedTitle = null;
        $scrapedAuthor = null;
        $duration = null;
        $channelUrl = null;
        $videoDescription = null;
        try {
            $ytResponse = Http::withoutVerifying()
                ->withUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36')
                ->timeout(15)
                ->get("https://www.youtube.com/watch?v={$videoId}");
            $html = $ytResponse->successful() ? $ytResponse->body() : '';
            if ($html) {
                // Titre depuis <title>
                if (preg_match('/<title>(.*?)(?:\s*-\s*YouTube)?<\/title>/i', $html, $tm)) {
                    $scrapedTitle = html_entity_decode(trim($tm[1]), ENT_QUOTES, 'UTF-8');
                }
                // Auteur depuis JSON-LD
                if (preg_match('/"author"\s*:\s*"([^"]+)"/', $html, $am)) {
                    $scrapedAuthor = $am[1];
                } elseif (preg_match('/"ownerChannelName"\s*:\s*"([^"]+)"/', $html, $am)) {
                    $scrapedAuthor = $am[1];
                }
                if (preg_match('/"lengthSeconds":"(\d+)"/', $html, $dm)) {
                    $duration = (int) $dm[1];
                }
                if (preg_match('/"channelId":"([\w-]+)"/', $html, $cm)) {
                    $channelUrl = "https://www.youtube.com/channel/{$cm[1]}";
                }
                if (preg_match('/"shortDescription":"(.*?)(?<!\\\\)"/', $html, $descMatch)) {
                    $videoDescription = str_replace(['\\n', '\\r', '\\"'], ["\n", '', '"'], $descMatch[1]);
                    $videoDescription = mb_substr($videoDescription, 0, 2000);
                }
            }
        } catch (\Throwable $e) {
            // Pas bloquant
        }

        // Utiliser les données scrapées si oEmbed n'a pas fourni le titre
        $finalTitle = $oembed['title'] ?? $scrapedTitle;
        $finalAuthor = $oembed['author_name'] ?? $scrapedAuthor;

        return response()->json([
            'youtube' => true,
            'valid' => true,
            'video_id' => $videoId,
            'title' => $finalTitle,
            'thumbnail' => "https://img.youtube.com/vi/{$videoId}/maxresdefault.jpg",
            'author' => $finalAuthor,
            'duration' => $duration,
            'channel_url' => $channelUrl,
            'description' => $videoDescription,
        ]);
    }

    public function toggleLike(Request $request, string $type, int $id): JsonResponse
    {
        $model = match ($type) {
            'review' => ToolReview::findOrFail($id),
            'discussion' => ToolDiscussion::findOrFail($id),
            'resource' => ToolResource::findOrFail($id),
            default => abort(404),
        };

        $model->increment('upvotes');

        // Donner un point de réputation à l'auteur du contenu liké
        if (isset($model->user_id) && $model->user_id) {
            $author = \App\Models\User::find($model->user_id);
            if ($author) {
                $this->reputation->addPoints($author, ReputationService::LIKE_RECEIVED, 'like_received');
            }
        }

        // Auto-approbation si seuil de votes atteint
        $fresh = $model->fresh();
        if (in_array($type, ['review', 'resource']) && isset($fresh->is_approved)) {
            \Modules\Directory\Services\AutoApproveService::checkAndApprove($fresh, $type);
        }

        return response()->json(['upvotes' => $fresh->upvotes]);
    }

    public function report(Request $request, string $type, int $id): RedirectResponse|JsonResponse
    {
        $modelClass = match ($type) {
            'review' => ToolReview::class,
            'discussion' => ToolDiscussion::class,
            'resource' => ToolResource::class,
            'acronym' => \Modules\Acronyms\Models\Acronym::class,
            default => abort(404),
        };

        $modelClass::findOrFail($id);

        $validated = $request->validate([
            'reason' => ['required', 'in:spam,inappropriate,inaccurate,broken,off_topic,other'],
            'details' => ['nullable', 'string', 'max:500'],
            'comment' => ['nullable', 'string', 'max:500'],
        ]);

        ToolReport::create([
            'user_id' => Auth::id(),
            'reportable_type' => $modelClass,
            'reportable_id' => $id,
            'reason' => $validated['reason'],
            'comment' => $validated['details'] ?? $validated['comment'] ?? null,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', __('Merci, le signalement a été envoyé.'));
    }

    public function storeSuggestion(Request $request, string $slug): RedirectResponse|JsonResponse
    {
        $tool = $this->findTool($slug);

        $validated = $request->validate([
            'field' => ['required', $tool->suggestableFieldValidation()],
            'suggested_value' => ['required', 'string', 'max:2000'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        \Modules\Directory\Models\ToolSuggestion::create([
            'user_id' => Auth::id(),
            'directory_tool_id' => $tool->id,
            'suggestable_type' => \Modules\Directory\Models\Tool::class,
            'suggestable_id' => $tool->id,
            'field' => $validated['field'],
            'current_value' => $tool->{$validated['field']} ?? null,
            'suggested_value' => $validated['suggested_value'],
            'reason' => $validated['reason'] ?? null,
            'status' => 'pending',
        ]);

        return back()->with('success', __('Merci ! Votre suggestion sera examinée par notre équipe.'));
    }

    public function storeScreenshot(Request $request, string $slug): RedirectResponse|JsonResponse
    {
        $request->validate([
            'screenshot' => 'required|image|max:5120',
            'caption' => 'nullable|string|max:255',
        ]);

        $tool = Tool::where('slug->'.app()->getLocale(), $slug)->firstOrFail();

        $path = $request->file('screenshot')->store('directory/screenshots', 'public');

        $autoApprove = Auth::user()->can('view_admin_panel');
        if (! $autoApprove && class_exists(\Modules\Directory\Services\ReputationService::class)) {
            $autoApprove = $this->reputation->shouldAutoApprove(Auth::user(), 'resource');
        }

        ToolScreenshot::create([
            'directory_tool_id' => $tool->id,
            'user_id' => Auth::id(),
            'image_path' => 'storage/'.$path,
            'caption' => $request->caption,
            'is_approved' => $autoApprove,
        ]);

        if ($autoApprove && class_exists(\Modules\Directory\Services\ReputationService::class)) {
            $this->reputation->addPoints(Auth::user(), 8, 'screenshot_approved');
        }

        return back()->with('success', $autoApprove
            ? __('Screenshot ajouté ! Merci pour votre contribution.')
            : __('Screenshot soumis ! Il sera visible après modération.'));
    }

    public function voteScreenshot(Request $request, int $id): JsonResponse
    {
        $screenshot = ToolScreenshot::approved()->findOrFail($id);
        $screenshot->increment('votes_count');

        return response()->json(['votes' => $screenshot->votes_count]);
    }

    public function deleteScreenshot(int $id): JsonResponse
    {
        $screenshot = ToolScreenshot::findOrFail($id);

        // Supprimer le fichier image
        $filePath = public_path($screenshot->image_path);
        if (file_exists($filePath)) {
            @unlink($filePath);
        }

        $screenshot->delete();

        return response()->json(['success' => true]);
    }
}
