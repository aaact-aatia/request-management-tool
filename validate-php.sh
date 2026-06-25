#!/bin/bash
cd /Users/shawn.thompson/_git/aaact-aatia/request-management-tool

# Start docker if not running
docker compose up -d web mysql

# Give containers a moment to start
sleep 3

# Validate key modified PHP files
echo "Validating PHP syntax for modified files..."
FILES=(
    "app/includes/helpers.php"
    "app/settings.php"
    "app/teams.php"
    "app/status.php"
    "app/sources.php"
    "app/products.php"
    "app/indexresolved.php"
    "app/index.php"
    "app/clonerequest.php"
    "app/viewrequest.php"
    "app/includes/appmenu.php"
    "app/includes/ecomms.php"
    "app/catalogue-mgmt.php"
    "app/catalogue-sub-mgmt.php"
    "app/includes/add-subservice.php"
    "app/includes/delete-catalogue.php"
    "app/includes/add-source.php"
    "app/includes/delete-users.php"
    "app/includes/delete-status.php"
    "app/includes/delete-teams.php"
    "app/includes/admin-csv-import.php"
    "app/includes/admin-csv-buttons.php"
    "app/includes/admin-csv-export.php"
)

ERRORS=0
for file in "${FILES[@]}"; do
    if docker compose exec -T web php -l "/var/www/html/$file" > /dev/null 2>&1; then
        echo "✓ $file"
    else
        echo "✗ $file"
        docker compose exec -T web php -l "/var/www/html/$file"
        ((ERRORS++))
    fi
done

echo ""
echo "Validation complete. Errors: $ERRORS"
exit $ERRORS
