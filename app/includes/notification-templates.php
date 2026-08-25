<?php
if (isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) === realpath((string) $_SERVER['SCRIPT_FILENAME'])) {
    http_response_code(404);
    exit();
}

/**
 * Per-team GC Notify message templates (subject + body) for client and employee audiences.
 *
 * Resolution order at send time: team override -> global default (team_id = 0) -> built-in
 * fallback text in rmt_notification_subject_single_language()/rmt_notification_message_single_language().
 */

const RMT_NOTIFICATION_GLOBAL_TEAM_ID = 0;

/**
 * Events each audience is allowed to customize. Enforced server-side on save.
 */
function rmt_notification_events_for_audience(string $audience): array {
    if ($audience === 'client') {
        return ['request_created', 'resolved'];
    }

    if ($audience === 'employee') {
        return ['request_created', 'status_changed', 'reassigned', 'resolved'];
    }

    return [];
}

function rmt_notification_audiences(): array {
    return ['client', 'employee'];
}

function rmt_notification_is_valid_audience_event(string $audience, string $event): bool {
    return in_array($event, rmt_notification_events_for_audience($audience), true);
}

/**
 * Reference list of placeholder tokens available to template editors, with labels for the UI.
 * Tokens map to keys already populated in the notification context arrays built by the send flows.
 */
function rmt_notification_placeholder_catalog(): array {
    return [
        ['token' => 'requestid', 'en' => 'Request ID', 'fr' => 'Numero de demande'],
        ['token' => 'requesttitle', 'en' => 'Request title', 'fr' => 'Titre de la demande'],
        ['token' => 'teamname', 'en' => 'Team name', 'fr' => 'Nom de l\'equipe'],
        ['token' => 'catalogue_name', 'en' => 'Catalogue/topic name', 'fr' => 'Nom du catalogue'],
        ['token' => 'service_name', 'en' => 'Service name', 'fr' => 'Nom du service'],
        ['token' => 'status_label', 'en' => 'Status label', 'fr' => 'Libelle du statut'],
        ['token' => 'client_fname', 'en' => 'Client first name', 'fr' => 'Prenom du client'],
        ['token' => 'client_lname', 'en' => 'Client last name', 'fr' => 'Nom de famille du client'],
        ['token' => 'url', 'en' => 'Link to the request', 'fr' => 'Lien vers la demande'],
        ['token' => 'survey_link_en', 'en' => 'Client survey link (English)', 'fr' => 'Lien du sondage client (anglais)'],
        ['token' => 'survey_link_fr', 'en' => 'Client survey link (French)', 'fr' => 'Lien du sondage client (francais)'],
    ];
}

/**
 * Replace {{token}} placeholders in editor-authored subject/body text with values from $context.
 */
function rmt_notification_render_template(string $template, array $context, string $language, string $recipientType = 'general'): string {
    return preg_replace_callback('/\{\{\s*([a-z_]+)\s*\}\}/i', static function (array $matches) use ($context): string {
        $token = strtolower($matches[1]);

        return rmt_notification_escape((string) ($context[$token] ?? ''));
    }, $template);
}

/**
 * Look up an active template row for an exact scope (team/service/subservice all specified
 * explicitly, unused levels passed as 0). No fallback - use rmt_notification_template_resolve()
 * for send-time lookups.
 */
function rmt_notification_template_fetch(mysqli $link, int $teamId, string $audience, string $event, string $language, int $serviceId = 0, int $subserviceId = 0): ?array {
    return rmt_db_fetch_one(
        $link,
        'SELECT * FROM tblnotificationtemplates WHERE team_id = ? AND service_id = ? AND subservice_id = ? AND audience = ? AND event = ? AND language = ? AND status = 1 LIMIT 1',
        'iiisss',
        [$teamId, $serviceId, $subserviceId, $audience, $event, $language]
    );
}

/**
 * Resolve the template to use, most specific first: subservice override -> service override ->
 * team override -> global default (team_id = 0). Returns null when no admin-authored override
 * exists at any level (callers fall back to built-in text).
 */
function rmt_notification_template_resolve(mysqli $link, ?int $teamId, string $audience, string $event, string $language, int $serviceId = 0, int $subserviceId = 0): ?array {
    if ($subserviceId > 0) {
        $row = rmt_notification_template_fetch($link, RMT_NOTIFICATION_GLOBAL_TEAM_ID, $audience, $event, $language, 0, $subserviceId);
        if ($row !== null) {
            return $row;
        }
    }

    if ($serviceId > 0) {
        $row = rmt_notification_template_fetch($link, RMT_NOTIFICATION_GLOBAL_TEAM_ID, $audience, $event, $language, $serviceId, 0);
        if ($row !== null) {
            return $row;
        }
    }

    if ($teamId !== null && $teamId > RMT_NOTIFICATION_GLOBAL_TEAM_ID) {
        $row = rmt_notification_template_fetch($link, $teamId, $audience, $event, $language);
        if ($row !== null) {
            return $row;
        }
    }

    return rmt_notification_template_fetch($link, RMT_NOTIFICATION_GLOBAL_TEAM_ID, $audience, $event, $language);
}

/**
 * Effective owning team for a service/subservice, mirroring rmt_resolve_responsible_team_id()'s
 * contactid fallback chain (subservice -> service -> catalogue).
 */
function rmt_notification_owning_team_for_service(mysqli $link, int $serviceId): int {
    $row = rmt_db_fetch_one(
        $link,
        'SELECT COALESCE(NULLIF(s.contactid, 0), NULLIF(c.contactid, 0), 0) AS team_id
         FROM tblservices s JOIN tblcatalogue c ON c.id = s.catalogueid
         WHERE s.id = ?',
        'i',
        [$serviceId]
    );

    return (int) ($row['team_id'] ?? 0);
}

function rmt_notification_owning_team_for_subservice(mysqli $link, int $subserviceId): int {
    $row = rmt_db_fetch_one(
        $link,
        'SELECT COALESCE(NULLIF(ss.contactid, 0), NULLIF(s.contactid, 0), NULLIF(c.contactid, 0), 0) AS team_id
         FROM tblsubservices ss
         JOIN tblservices s ON s.id = ss.serviceid
         JOIN tblcatalogue c ON c.id = s.catalogueid
         WHERE ss.id = ?',
        'i',
        [$subserviceId]
    );

    return (int) ($row['team_id'] ?? 0);
}

/**
 * Services and subservices whose current effective owning team is $teamId, for the scope picker.
 * Each entry: ['type' => 'service'|'subservice', 'id' => int, 'label' => string].
 */
function rmt_notification_scopes_for_team(mysqli $link, int $teamId, string $lang): array {
    $nameField = ($lang === 'fr') ? 'namefr' : 'nameen';
    $scopes = [];

    $statement = rmt_db_execute(
        $link,
        "SELECT s.id, s.{$nameField} AS name, c.{$nameField} AS catalogue_name
         FROM tblservices s JOIN tblcatalogue c ON c.id = s.catalogueid
         WHERE s.status = 1 AND COALESCE(NULLIF(s.contactid, 0), NULLIF(c.contactid, 0), 0) = ?
         ORDER BY c.{$nameField} ASC, s.{$nameField} ASC",
        'i',
        [$teamId]
    );
    $result = mysqli_stmt_get_result($statement);
    while ($row = mysqli_fetch_assoc($result)) {
        $scopes[] = [
            'type' => 'service',
            'id' => (int) $row['id'],
            'label' => $row['catalogue_name'] . ' → ' . $row['name'],
        ];
    }
    mysqli_stmt_close($statement);

    $statement = rmt_db_execute(
        $link,
        "SELECT ss.id, ss.{$nameField} AS name, s.{$nameField} AS service_name, c.{$nameField} AS catalogue_name
         FROM tblsubservices ss
         JOIN tblservices s ON s.id = ss.serviceid
         JOIN tblcatalogue c ON c.id = s.catalogueid
         WHERE ss.status = 1 AND COALESCE(NULLIF(ss.contactid, 0), NULLIF(s.contactid, 0), NULLIF(c.contactid, 0), 0) = ?
         ORDER BY c.{$nameField} ASC, s.{$nameField} ASC, ss.{$nameField} ASC",
        'i',
        [$teamId]
    );
    $result = mysqli_stmt_get_result($statement);
    while ($row = mysqli_fetch_assoc($result)) {
        $scopes[] = [
            'type' => 'subservice',
            'id' => (int) $row['id'],
            'label' => $row['catalogue_name'] . ' → ' . $row['service_name'] . ' → ' . $row['name'],
        ];
    }
    mysqli_stmt_close($statement);

    return $scopes;
}

/**
 * True if the current session may manage templates for the given team (or the app-wide default).
 * The app-wide default (team_id = 0) is superadmin-only. Regular admins and
 * managers/team leads are scoped to real teams only.
 */
function rmt_notification_user_can_manage_team(mysqli $link, int $teamId): bool {
    if ($teamId === RMT_NOTIFICATION_GLOBAL_TEAM_ID) {
        return isSuperAdmin();
    }

    if (isSuperAdmin() || (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1)) {
        return true;
    }

    $atype = (int) ($_SESSION['atype'] ?? 0);

    if ($atype === 3) {
        $managedTeams = $_SESSION['team'] ?? [];
        return is_array($managedTeams) && in_array((string) $teamId, $managedTeams, true);
    }

    if ($atype === 4) {
        $row = rmt_db_fetch_one(
            $link,
            'SELECT id FROM tblteams WHERE id = ? AND team_lead_user_id = ? AND status = 1 LIMIT 1',
            'ii',
            [$teamId, (int) ($_SESSION['pid'] ?? 0)]
        );
        return $row !== null;
    }

    return false;
}

/**
 * Same permission check as rmt_notification_user_can_manage_team(), but for a service or
 * subservice scoped template: the check is against that service/subservice's current owning
 * team, not the team the editor started from (ownership can differ or drift over time).
 */
function rmt_notification_user_can_manage_scope(mysqli $link, int $teamId, int $serviceId = 0, int $subserviceId = 0): bool {
    if ($subserviceId > 0) {
        return rmt_notification_user_can_manage_team($link, rmt_notification_owning_team_for_subservice($link, $subserviceId));
    }

    if ($serviceId > 0) {
        return rmt_notification_user_can_manage_team($link, rmt_notification_owning_team_for_service($link, $serviceId));
    }

    return rmt_notification_user_can_manage_team($link, $teamId);
}

/**
 * Teams the current session may manage templates for. Superadmins additionally see the
 * app-wide default (team_id = 0); regular admins and managers/team leads only see real teams.
 * Each entry: ['id' => int, 'nameen' => string, 'namefr' => string].
 */
function rmt_notification_manageable_teams(mysqli $link): array {
    $isAdminUser = isSuperAdmin() || (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1);

    if ($isAdminUser) {
        $teams = [];

        if (isSuperAdmin()) {
            $teams[] = [
                'id' => RMT_NOTIFICATION_GLOBAL_TEAM_ID,
                'nameen' => 'App-wide default',
                'namefr' => 'Modele par defaut de l\'application',
            ];
        }

        $statement = rmt_db_execute($link, 'SELECT id, nameen, namefr FROM tblteams WHERE status = 1 ORDER BY nameen ASC');
        $result = mysqli_stmt_get_result($statement);
        while ($row = mysqli_fetch_assoc($result)) {
            $teams[] = ['id' => (int) $row['id'], 'nameen' => $row['nameen'], 'namefr' => $row['namefr']];
        }
        mysqli_stmt_close($statement);

        return $teams;
    }

    $atype = (int) ($_SESSION['atype'] ?? 0);
    $teams = [];

    if ($atype === 3) {
        $managedTeamIds = array_values(array_filter(array_map('intval', (array) ($_SESSION['team'] ?? []))));
        foreach ($managedTeamIds as $teamId) {
            $row = rmt_db_fetch_one($link, 'SELECT id, nameen, namefr FROM tblteams WHERE id = ? AND status = 1 LIMIT 1', 'i', [$teamId]);
            if ($row !== null) {
                $teams[] = ['id' => (int) $row['id'], 'nameen' => $row['nameen'], 'namefr' => $row['namefr']];
            }
        }
    } elseif ($atype === 4) {
        $statement = rmt_db_execute(
            $link,
            'SELECT id, nameen, namefr FROM tblteams WHERE team_lead_user_id = ? AND status = 1 ORDER BY nameen ASC',
            'i',
            [(int) ($_SESSION['pid'] ?? 0)]
        );
        $result = mysqli_stmt_get_result($statement);
        while ($row = mysqli_fetch_assoc($result)) {
            $teams[] = ['id' => (int) $row['id'], 'nameen' => $row['nameen'], 'namefr' => $row['namefr']];
        }
        mysqli_stmt_close($statement);
    }

    return $teams;
}
