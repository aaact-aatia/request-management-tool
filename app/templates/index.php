<?php
require('../sql.php');
/** @var mysqli $link */
require('../includes/httpscheck.php');
require('../includes/helpers.php');
require('../includes/loggedincheck.php');
require_once(__DIR__ . '/_preview-definitions.php');

$lang = detectLanguage();
$isFrench = ($lang === 'fr');

if (!(($_SESSION['is_superuser'] ?? 0) || ($_SESSION['is_admin'] ?? 0))) {
    header("location:/settings.php?lang={$lang}&status=forbidden");
    exit();
}

$page = [
    'title' => [
        'en' => 'GC Notify template previews',
        'fr' => 'Apercus des modeles GC Notify',
    ],
    'description' => [
        'en' => 'Browse available GC Notify template preview pages.',
        'fr' => 'Parcourir les pages d apercu des modeles GC Notify disponibles.',
    ],
];

$pageTitle = $page['title'][$lang];
$pageDescription = $page['description'][$lang];

$extraStyles = '
    .wb-eqht-grd .panel.hght-inhrt {
        display: flex;
        flex-direction: column;
    }
    .wb-eqht-grd .panel.hght-inhrt .panel-body {
        flex: 1 1 auto;
    }
';

$templates = rmt_get_template_preview_definitions();

include '../includes/template/head.php';
?>
<?php include '../includes/template/header.php'; ?>
<main role="main" property="mainContentOfPage" class="container">
    <h1 property="name" id="wb-cont"><?php echo htmlspecialchars($pageTitle); ?></h1>

    <p><?php echo htmlspecialchars($pageDescription); ?></p>

    <div class="well">
        <p>
            <?php echo $isFrench
                ? 'Les apercus servent a valider le contenu avant de le copier dans GC Notify.'
                : 'Template previews help validate content before copying it into GC Notify.'; ?>
        </p>
    </div>

    <section class="provisional wb-tagfilter wb-filter" data-wb-filter='{"selector": "[data-wb-tags]", "section": ".wb-tagfilter-items", "uiTemplate": "#template-search-filter"}'>
        <h2 class="wb-inv"><?php echo $isFrench ? 'Options de filtrage' : 'Filter options'; ?></h2>
        <div id="template-search-filter" class="row">
            <div class="col-sm-12">
                <p class="wb-fltr-info mrgn-bttm-sm"><span data-nbitem></span> <?php echo $isFrench ? 'resultats sur' : 'results out of'; ?> <span data-total></span></p>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <fieldset>
                        <legend class="mrgn-bttm-0"><label for="template-audience-filter" class="fnt-nrml"><?php echo $isFrench ? 'Public cible' : 'Audience'; ?></label></legend>
                        <select id="template-audience-filter" name="template-audience-filter" class="full-width wb-tagfilter-ctrl form-control">
                            <option value=""><?php echo $isFrench ? 'Tous' : 'All'; ?></option>
                            <option value="audience-client"><?php echo $isFrench ? 'Destine au client' : 'Client-facing'; ?></option>
                            <option value="audience-internal"><?php echo $isFrench ? 'Interne' : 'Internal'; ?></option>
                        </select>
                    </fieldset>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <fieldset>
                        <legend class="mrgn-bttm-0"><label for="template-language-filter" class="fnt-nrml"><?php echo $isFrench ? 'Ordre des langues' : 'Language order'; ?></label></legend>
                        <select id="template-language-filter" name="template-language-filter" class="full-width wb-tagfilter-ctrl form-control">
                            <option value=""><?php echo $isFrench ? 'Tous' : 'All'; ?></option>
                            <option value="order-en-first"><?php echo $isFrench ? 'Anglais en premier' : 'English first'; ?></option>
                            <option value="order-fr-first"><?php echo $isFrench ? 'Francais en premier' : 'French first'; ?></option>
                        </select>
                    </fieldset>
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    <div class="input-group">
                        <label for="template-search" class="input-group-addon"><?php echo $isFrench ? 'Filtrer' : 'Filter'; ?></label>
                        <input type="search" class="form-control" id="template-search">
                    </div>
                </div>
            </div>
        </div>

        <div class="row wb-eqht-grd wb-tagfilter-items">
            <?php foreach ($templates as $template): ?>
                <?php
                if ($template['slug'] === 'notification-generic') {
                    $cardTags = 'audience-client audience-internal order-en-first order-fr-first';
                } else {
                    $isClientAudience = stripos($template['audience_en'], 'client') !== false;
                    $cardTags = $isClientAudience ? 'audience-client' : 'audience-internal';
                    $cardTags .= $template['fr_first'] ? ' order-fr-first' : ' order-en-first';
                }
                $cardTitle = $isFrench ? $template['name_fr'] : $template['name_en'];
                $cardAudience = $isFrench ? $template['audience_fr'] : $template['audience_en'];
                $cardOrder = $isFrench ? $template['language_order_fr'] : $template['language_order_en'];
                $cardPurpose = $isFrench ? $template['purpose_fr'] : $template['purpose_en'];
                $previewUrl = '/templates/' . $template['slug'] . '.php?lang=' . urlencode($lang);
                ?>
                <div class="col-md-6 col-lg-4 wb-tagfilter-item" data-wb-tags="<?php echo htmlspecialchars($cardTags); ?>">
                    <section class="panel panel-default hght-inhrt">
                        <header class="panel-heading">
                            <h3 class="panel-title">
                                <a href="<?php echo htmlspecialchars($previewUrl); ?>"><?php echo htmlspecialchars($cardTitle); ?></a>
                            </h3>
                        </header>
                        <div class="panel-body">
                            <dl>
                                <dt><?php echo $isFrench ? 'Public cible' : 'Audience'; ?>:</dt>
                                <dd><?php echo htmlspecialchars($cardAudience); ?></dd>
                                <dt><?php echo $isFrench ? 'Ordre des langues' : 'Language order'; ?>:</dt>
                                <dd><?php echo htmlspecialchars($cardOrder); ?></dd>
                            </dl>
                            <p><?php echo htmlspecialchars($cardPurpose); ?></p>
                        </div>
                        <footer class="panel-footer">
                            <a class="btn btn-primary" href="<?php echo htmlspecialchars($previewUrl); ?>">
                                <?php echo $isFrench ? 'Voir l apercu' : 'Open preview'; ?>
                            </a>
                        </footer>
                    </section>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <p>
        <a class="btn btn-default" href="/gcnotify-settings.php?lang=<?php echo urlencode($lang); ?>">
            <?php echo $isFrench ? 'Retour aux parametres GC Notify' : 'Back to GC Notify settings'; ?>
        </a>
    </p>

    <?php include '../includes/template/page-details.php'; ?>
</main>
<?php include '../includes/template/footer.php'; include '../includes/template/scripts.php'; ?>
</body>
</html>
<?php
mysqli_close($link);
