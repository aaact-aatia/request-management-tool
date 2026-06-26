<?php
if (isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) === realpath((string) $_SERVER['SCRIPT_FILENAME'])) {
    http_response_code(404);
    exit();
}

/**
 * Development Account Type Switcher
 * 
 * This component allows superadmin users to switch between different account types
 * for testing purposes. Only visible when:
 * - User is logged in as superadmin (atype == 1)
 * - User has their actual superadmin credentials
 * 
 * @package RMT
 * @since 2.1.0
 */

// Only show if user is logged in and is superadmin
if (isset($_SESSION['pid']) && ($_SESSION['is_superuser'] == 1)) {
    // Get account types from database
    $accountTypes = [];
    $result = mysqli_query($link, "SELECT id, nameen, namefr FROM tblaccounttype WHERE status = 1 ORDER BY id ASC");
    while ($row = mysqli_fetch_array($result)) {
        $accountTypes[] = $row;
    }
    
    // Determine current language
    $lang_code = $_SESSION['lang'] ?? 'en';
    $nameField = $lang_code == 'fr' ? 'namefr' : 'nameen';
    
    // Get current testing account type (or real if not testing)
    $currentAtype = $_SESSION['atype'];
    ?>
    <div style="background-color: #fff3cd; border: 2px solid #856404; padding: 10px 0; margin-bottom: 10px;">
        <div class="container">
            <form method="post" action="/includes/switch-account-type.php" style="margin: 0;">
                <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                    <strong style="color: #856404;">
                        <?php if ($lang_code == 'fr'): ?>
                            🔧 Mode développement - Tester en tant que:
                        <?php else: ?>
                            🔧 Dev Mode - Test as:
                        <?php endif; ?>
                    </strong>
                    <select name="test_atype" class="form-control" style="width: auto; display: inline-block; min-width: 200px;" onchange="this.form.submit()">
                        <?php foreach ($accountTypes as $type): ?>
                            <option value="<?php echo $type['id']; ?>" <?php echo ($currentAtype == $type['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($type[$nameField]); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ((int)$currentAtype !== 1): ?>
                        <button type="submit" name="reset_atype" value="1" class="btn btn-sm btn-warning">
                            <?php echo $lang_code == 'fr' ? 'Réinitialiser au super admin' : 'Reset to Super Admin'; ?>
                        </button>
                    <?php endif; ?>
                    <span style="color: #856404; font-size: 13px;">
                        <?php if ($lang_code == 'fr'): ?>
                            (Actuellement: <strong><?php 
                                $currentTypeName = '';
                                foreach ($accountTypes as $type) {
                                    if ($type['id'] == $currentAtype) {
                                        $currentTypeName = $type[$nameField];
                                        break;
                                    }
                                }
                                echo htmlspecialchars($currentTypeName);
                            ?></strong>)
                        <?php else: ?>
                            (Currently: <strong><?php 
                                $currentTypeName = '';
                                foreach ($accountTypes as $type) {
                                    if ($type['id'] == $currentAtype) {
                                        $currentTypeName = $type[$nameField];
                                        break;
                                    }
                                }
                                echo htmlspecialchars($currentTypeName);
                            ?></strong>)
                        <?php endif; ?>
                    </span>
                </div>
            </form>
        </div>
    </div>
    <?php
}
?>
