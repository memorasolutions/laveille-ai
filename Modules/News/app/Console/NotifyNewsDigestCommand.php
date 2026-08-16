<?php

declare(strict_types=1);

namespace Modules\News\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\News\Mail\NewsDigestMail;
use Modules\News\Models\NewsArticle;
use Modules\Settings\Models\Setting;

/**
 * Courriel de veille quotidien : demande du proprietaire (2026-08-16) - « je prefere recevoir
 * les nouveautes par courriel et ensuite je decide des nouvelles qui vont aller sur le site ».
 * Depuis l'arret de la publication automatique (v1.174.0, config news.autopublish.enabled),
 * la collecte continue chaque heure mais les fiches restent en brouillon (is_published=false) et
 * personne n'est averti - le proprietaire devait fouiller l'administration a l'aveugle. Cette
 * commande envoie un resume quotidien GROUPE des brouillons NOUVEAUX depuis le dernier envoi,
 * tries par score de pertinence decroissant, avec pour chacun un lien direct vers l'ecran de
 * composition manuelle (/admin/news/composition/{article}).
 *
 * REGROUPEMENT (meme piege que Modules\Decido\Console\NotifyPollActivityCommand) : au plus UN
 * courriel par jour, envoye SEULEMENT s'il y a du nouveau - jamais un courriel par article.
 *
 * CURSEUR D'IDEMPOTENCE : contrairement a Decido (curseur activity_notified_at PAR SONDAGE, une
 * ligne precise), ce resume porte sur TOUTE la table news_articles sans entite unique a laquelle
 * rattacher une colonne - c'est un etat GLOBAL du module. Le module Settings expose deja ce
 * mecanisme (Setting::get/set, store cle/valeur PERSISTE EN BASE - pas un simple cache, donc
 * jamais efface par un cache:clear/optimize:clear de deploiement, piege deja rencontre sur
 * l'alerte sante courriel) et sert deja cet usage ailleurs (Booking, Tools) pour un etat global
 * n'appartenant a aucune ligne precise. Reutilise ici plutot qu'une nouvelle migration : clef
 * 'news.digest_last_sent_at' (datetime ISO 8601), avancee UNIQUEMENT apres un envoi reussi -
 * un echec d'envoi isole ne fait pas perdre l'activite, retentee (cumulee) au prochain passage.
 *
 * Premier passage (curseur absent) : aucune borne basse, le brouillon le plus ancien encore non
 * publie est inclus - c'est voulu, le proprietaire doit voir l'arriere accumule depuis l'arret de
 * la publication automatique, pas seulement ce qui arrive a partir d'aujourd'hui.
 */
class NotifyNewsDigestCommand extends Command
{
    protected $signature = 'news:notify-digest';

    protected $description = "Envoie un resume quotidien groupe des actualites collectees non publiees (brouillons) depuis le dernier envoi, si du nouveau est survenu";

    private const SETTING_KEY = 'news.digest_last_sent_at';

    public function handle(): int
    {
        if (! (bool) config('news.digest.enabled', true)) {
            $this->info('news:notify-digest desactive (news.digest.enabled=false) - aucun courriel envoye.');

            return self::SUCCESS;
        }

        $recipient = (string) config('app.superadmin_email', '');

        if ($recipient === '') {
            // Garde-fou defensif : ne devrait jamais arriver (config('app.superadmin_email') a
            // toujours un defaut non vide), mais un envoi vers une adresse vide echouerait
            // silencieusement plus loin - autant l'arreter ici avec un message explicite.
            Log::warning('news:notify-digest - aucun destinataire configure (app.superadmin_email vide), envoi annule.');

            return self::SUCCESS;
        }

        $since = Setting::get(self::SETTING_KEY);
        $sinceDate = $since ? Carbon::parse((string) $since) : null;

        $query = NewsArticle::where('is_published', false)
            ->when($sinceDate !== null, fn ($q) => $q->where('created_at', '>', $sinceDate))
            // Les articles sans score (pas encore traites par l'IA) passent en dernier plutot
            // qu'en premier avec un tri desc naif (NULL y serait traite comme le plus petit par
            // MySQL, ce qui est deja le comportement voulu ici, mais on l'explicite).
            ->orderByRaw('relevance_score IS NULL, relevance_score DESC')
            ->orderByDesc('created_at');

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info('news:notify-digest - aucune nouveaute depuis le dernier envoi, aucun courriel.');

            return self::SUCCESS;
        }

        $maxItems = (int) config('news.digest.max_items', 100);
        $articles = $query->with('source')->limit($maxItems)->get();

        try {
            Mail::to($recipient)->send(new NewsDigestMail($articles, $total, $maxItems));
            Setting::set(self::SETTING_KEY, now()->toISOString(), 'string', 'news');
            $this->info("news:notify-digest - courriel envoye a {$recipient} : {$total} nouveaute(s).");
        } catch (\Throwable $e) {
            // Meme politique que NotifyPollActivityCommand : un echec d'envoi isole (SMTP
            // temporairement indisponible) ne fait pas avancer le curseur - l'activite sera
            // retentee (cumulee, pas perdue) au prochain passage quotidien.
            Log::warning("news:notify-digest - echec d'envoi: {$e->getMessage()}");
        }

        return self::SUCCESS;
    }
}
